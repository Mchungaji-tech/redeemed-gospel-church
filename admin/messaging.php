<?php
require __DIR__ . '/includes/auth.php';
requireSuperAdmin();
$pageTitle = 'Messaging';
$error = '';
$info = '';
$users = [];
$threads = [];
$threadsByKey = [];
$threadMessages = [];
$selectedThread = null;
$selectedThreadKey = trim((string) ($_GET['thread'] ?? $_POST['thread_key'] ?? ''));

if (!function_exists('rgcAdminBuildMessageThreadKey')) {
  function rgcAdminBuildMessageThreadKey(array $row): string {
    $userId = (int) ($row['user_id'] ?? 0);
    $guestToken = trim((string) ($row['guest_token'] ?? ''));
    $email = strtolower(trim((string) ($row['resolved_email'] ?? $row['email'] ?? '')));

    if ($userId > 0) {
      return 'user:' . $userId;
    }

    if ($guestToken !== '') {
      return 'guest:' . $guestToken;
    }

    if ($email !== '') {
      return 'email:' . $email;
    }

    return 'message:' . (int) ($row['id'] ?? 0);
  }
}

if (rgcDbAvailable()) {
  $stmt = rgcDb()->query('SELECT id, full_name, email FROM public_users ORDER BY created_at DESC LIMIT 200');
  $users = $stmt->fetchAll();

  rgcEnsurePublicMessagesPrivacyColumns();

  if ($selectedThreadKey !== '') {
    if (str_starts_with($selectedThreadKey, 'user:')) {
      $userId = (int) substr($selectedThreadKey, 5);
      if ($userId > 0) {
        $markSeen = rgcDb()->prepare(
          "UPDATE public_messages
           SET admin_seen_at = NOW()
           WHERE user_id = :user_id
             AND type = 'chat'
             AND admin_seen_at IS NULL"
        );
        $markSeen->execute([':user_id' => $userId]);
      }
    } elseif (str_starts_with($selectedThreadKey, 'guest:')) {
      $guestToken = substr($selectedThreadKey, 6);
      if ($guestToken !== '') {
        $markSeen = rgcDb()->prepare(
          "UPDATE public_messages
           SET admin_seen_at = NOW()
           WHERE guest_token = :guest_token
             AND type = 'chat'
             AND admin_seen_at IS NULL"
        );
        $markSeen->execute([':guest_token' => $guestToken]);
      }
    } elseif (str_starts_with($selectedThreadKey, 'email:')) {
      $emailKey = substr($selectedThreadKey, 6);
      if ($emailKey !== '') {
        $markSeen = rgcDb()->prepare(
          "UPDATE public_messages
           SET admin_seen_at = NOW()
           WHERE user_id IS NULL
             AND (guest_token IS NULL OR guest_token = '')
             AND LOWER(email) = :email
             AND type = 'chat'
             AND admin_seen_at IS NULL"
        );
        $markSeen->execute([':email' => strtolower($emailKey)]);
      }
    }
  }

  $stmt = rgcDb()->query(
    "SELECT pm.id, pm.user_id, pm.guest_token, pm.name, pm.email, pm.type, pm.message, pm.created_at, pm.admin_seen_at,
            pu.full_name AS user_name, pu.email AS user_email
     FROM public_messages pm
     LEFT JOIN public_users pu ON pu.id = pm.user_id
     ORDER BY pm.id DESC
     LIMIT 300"
  );
  $rawThreads = $stmt->fetchAll();

  foreach ($rawThreads as $row) {
    $row['resolved_name'] = trim((string) ($row['user_name'] ?: $row['name'] ?: 'Anonymous visitor'));
    $row['resolved_email'] = trim((string) ($row['user_email'] ?: $row['email'] ?: ''));
    $key = rgcAdminBuildMessageThreadKey($row);

    if (!isset($threadsByKey[$key])) {
      $threadsByKey[$key] = [
        'key' => $key,
        'user_id' => !empty($row['user_id']) ? (int) $row['user_id'] : null,
        'guest_token' => trim((string) ($row['guest_token'] ?? '')),
        'name' => $row['resolved_name'],
        'email' => $row['resolved_email'],
        'last_type' => (string) ($row['type'] ?? 'chat'),
        'last_message' => (string) ($row['message'] ?? ''),
        'updated_at' => (string) ($row['created_at'] ?? ''),
        'count' => 0,
        'unread' => 0,
      ];
    }

    $threadsByKey[$key]['count']++;
    $isUnreadVisitorMessage = (string) ($row['type'] ?? '') === 'chat' && empty($row['admin_seen_at']);
    if ($isUnreadVisitorMessage) {
      $threadsByKey[$key]['unread']++;
    }
  }

  $threads = array_values($threadsByKey);
  if ($selectedThreadKey === '' && !empty($threads)) {
    $selectedThreadKey = (string) $threads[0]['key'];
  }
  $selectedThread = $threadsByKey[$selectedThreadKey] ?? null;

  if ($selectedThread) {
    $threadStmt = null;
    if (!empty($selectedThread['user_id'])) {
      $threadStmt = rgcDb()->prepare(
        "SELECT id, type, message, created_at, name, email
         FROM public_messages
         WHERE user_id = :user_id
         ORDER BY id DESC
         LIMIT 20"
      );
      $threadStmt->execute([':user_id' => (int) $selectedThread['user_id']]);
    } elseif (!empty($selectedThread['guest_token'])) {
      $threadStmt = rgcDb()->prepare(
        "SELECT id, type, message, created_at, name, email
         FROM public_messages
         WHERE guest_token = :guest_token
         ORDER BY id DESC
         LIMIT 20"
      );
      $threadStmt->execute([':guest_token' => (string) $selectedThread['guest_token']]);
    } elseif (!empty($selectedThread['email'])) {
      $threadStmt = rgcDb()->prepare(
        "SELECT id, type, message, created_at, name, email
         FROM public_messages
         WHERE user_id IS NULL AND (guest_token IS NULL OR guest_token = '') AND email = :email
         ORDER BY id DESC
         LIMIT 20"
      );
      $threadStmt->execute([':email' => (string) $selectedThread['email']]);
    }

    if ($threadStmt) {
      $threadMessages = array_reverse($threadStmt->fetchAll() ?: []);
    }
  }
}

$selectedMode = (string) ($_POST['mode'] ?? ($selectedThread ? 'reply' : 'email'));
$targetEmail = trim((string) ($_POST['email'] ?? ($selectedThread['email'] ?? '')));
$targetPhone = trim((string) ($_POST['phone'] ?? ''));
$messageDraft = trim((string) ($_POST['message'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('admin_messaging');
  $mode = $selectedMode;
  $message = $messageDraft;
  if ($message === '') {
    $error = 'Message is required.';
  } else {
    if ($mode === 'email') {
      if ($targetEmail === '') {
        $error = 'Recipient email is required for email mode.';
      } else {
      $sent = rgcSendMail($targetEmail, 'Message from RGC Admin', $message);
      $info = $sent ? 'Email queued.' : 'Email capture failed (check mail mode).';
      }
    } elseif ($mode === 'whatsapp') {
      $info = 'Use the Open WhatsApp button to send the prepared message.';
    } elseif ($mode === 'reply') {
      if (rgcDbAvailable()) {
        rgcEnsurePublicMessagesPrivacyColumns();
        if (!$selectedThread) {
          $error = 'Choose a conversation before sending a chat reply.';
        } else {
          $stmt = rgcDb()->prepare('INSERT INTO public_messages (user_id, guest_token, name, email, type, message, created_at) VALUES (:user_id, :guest_token, :name, :email, :type, :message, NOW())');
          $stmt->execute([
            ':user_id' => $selectedThread['user_id'] ?? null,
            ':guest_token' => $selectedThread['guest_token'] !== '' ? $selectedThread['guest_token'] : null,
            ':name' => $selectedThread['name'] ?? '',
            ':email' => $selectedThread['email'] ?? $targetEmail,
            ':type' => 'reply',
            ':message' => $message,
          ]);
          $info = 'Reply added to the selected chat thread.';

          $targetEmail = (string) ($selectedThread['email'] ?? $targetEmail);
          $selectedThread['count'] = (int) ($selectedThread['count'] ?? 0) + 1;
          $selectedThread['last_message'] = $message;
          $selectedThread['last_type'] = 'reply';
          $selectedThread['updated_at'] = date('Y-m-d H:i:s');
          $selectedThread['unread'] = 0;
          foreach ($threads as &$threadItem) {
            if ((string) ($threadItem['key'] ?? '') === $selectedThreadKey) {
              $threadItem = $selectedThread;
              break;
            }
          }
          unset($threadItem);
          if (!empty($selectedThread['user_id'])) {
            $stmt = rgcDb()->prepare(
              "SELECT id, type, message, created_at, name, email
               FROM public_messages
               WHERE user_id = :user_id
               ORDER BY id DESC
               LIMIT 20"
            );
            $stmt->execute([':user_id' => (int) $selectedThread['user_id']]);
          } elseif (!empty($selectedThread['guest_token'])) {
            $stmt = rgcDb()->prepare(
              "SELECT id, type, message, created_at, name, email
               FROM public_messages
               WHERE guest_token = :guest_token
               ORDER BY id DESC
               LIMIT 20"
            );
            $stmt->execute([':guest_token' => (string) $selectedThread['guest_token']]);
          } elseif (!empty($selectedThread['email'])) {
            $stmt = rgcDb()->prepare(
              "SELECT id, type, message, created_at, name, email
               FROM public_messages
               WHERE user_id IS NULL AND (guest_token IS NULL OR guest_token = '') AND email = :email
               ORDER BY id DESC
               LIMIT 20"
            );
            $stmt->execute([':email' => (string) $selectedThread['email']]);
          }
          $threadMessages = array_reverse($stmt->fetchAll() ?: []);
          $messageDraft = '';
        }
      }
    } elseif ($mode === 'broadcast') {
      if (rgcDbAvailable()) {
        rgcEnsurePublicMessagesPrivacyColumns();
        $stmt = rgcDb()->prepare('INSERT INTO public_messages (user_id, guest_token, name, email, type, message, created_at) VALUES (:user_id, :guest_token, :name, :email, :type, :message, NOW())');
        $stmt->execute([':user_id' => null, ':guest_token' => null, ':name' => 'Admin', ':email' => $targetEmail, ':type' => 'broadcast', ':message' => $message]);
        $info = 'Broadcast message recorded.';
        $messageDraft = '';
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messaging</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>
  <main class="max-w-6xl mx-auto px-4 py-8 space-y-6">
    <section class="rounded-2xl bg-gradient-to-r from-cyan-700 via-indigo-600 to-sky-600 text-white p-6 shadow-lg">
      <h1 class="text-2xl font-bold">Messaging</h1>
      <p class="mt-2 text-indigo-100">Reply inside saved chat threads, send email, or prepare WhatsApp follow-up.</p>
    </section>
    <section class="grid md:grid-cols-3 gap-6">
      <article class="md:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
        <?php if ($info): ?><div class="mb-3 p-3 rounded-lg text-sm bg-emerald-50 text-emerald-700 border border-emerald-200"><?php echo htmlspecialchars($info); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="mb-3 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($selectedThread): ?>
          <div class="mb-4 rounded-2xl border border-cyan-100 bg-cyan-50/70 p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Selected conversation</p>
                <h2 class="mt-1 text-lg font-bold text-slate-900"><?php echo htmlspecialchars((string) ($selectedThread['name'] ?: 'Anonymous visitor')); ?></h2>
                <p class="text-sm text-slate-600"><?php echo htmlspecialchars((string) ($selectedThread['email'] ?: 'No email captured')); ?></p>
              </div>
              <div class="text-sm text-slate-500">
                <p><?php echo (int) ($selectedThread['count'] ?? 0); ?> messages</p>
                <p><?php echo (int) ($selectedThread['unread'] ?? 0); ?> unread</p>
                <p>Last activity <?php echo htmlspecialchars(date('M j, g:i a', strtotime((string) ($selectedThread['updated_at'] ?? 'now')))); ?></p>
              </div>
            </div>
          </div>
        <?php endif; ?>
        <form method="post" class="space-y-4">
          <?php echo rgcCsrfField('admin_messaging'); ?>
          <input type="hidden" name="thread_key" value="<?php echo htmlspecialchars($selectedThreadKey); ?>">
          <div class="grid md:grid-cols-2 gap-3">
            <div>
              <label class="text-sm font-medium text-slate-700">Mode</label>
              <select name="mode" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
                <option value="reply" <?php echo $selectedMode === 'reply' ? 'selected' : ''; ?>>Chat Reply</option>
                <option value="email" <?php echo $selectedMode === 'email' ? 'selected' : ''; ?>>Email</option>
                <option value="whatsapp" <?php echo $selectedMode === 'whatsapp' ? 'selected' : ''; ?>>WhatsApp</option>
                <option value="broadcast" <?php echo $selectedMode === 'broadcast' ? 'selected' : ''; ?>>Broadcast Record</option>
              </select>
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">Email</label>
              <input name="email" type="email" value="<?php echo htmlspecialchars($targetEmail); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="recipient@email.com">
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">Phone (WhatsApp)</label>
              <input name="phone" value="<?php echo htmlspecialchars($targetPhone); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="+254700000000">
            </div>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Message</label>
            <textarea name="message" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" rows="4" required><?php echo htmlspecialchars($messageDraft); ?></textarea>
          </div>
          <div class="flex gap-2">
            <button class="px-5 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Send</button>
            <a id="waLink" class="px-5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700" target="_blank">Open WhatsApp</a>
          </div>
        </form>
        <?php if ($selectedThread): ?>
          <div class="mt-6 border-t border-slate-200 pt-5">
            <div class="flex items-center justify-between gap-3">
              <h3 class="text-base font-bold text-slate-900">Conversation history</h3>
              <span class="text-xs font-medium uppercase tracking-[0.18em] text-slate-400">Private thread</span>
            </div>
            <div class="mt-4 space-y-3">
              <?php foreach ($threadMessages as $threadMessage): ?>
                <?php
                  $type = (string) ($threadMessage['type'] ?? 'chat');
                  $isAdmin = $type === 'reply' || $type === 'broadcast';
                  $bubbleClass = $isAdmin
                    ? 'ml-auto border-cyan-200 bg-cyan-50 text-cyan-950'
                    : 'mr-auto border-slate-200 bg-slate-50 text-slate-900';
                  $label = $type === 'reply' ? 'Admin reply' : ($type === 'broadcast' ? 'Broadcast' : 'Visitor');
                ?>
                <article class="max-w-xl rounded-2xl border px-4 py-3 shadow-sm <?php echo $bubbleClass; ?>">
                  <div class="flex items-center justify-between gap-3 text-xs uppercase tracking-[0.14em]">
                    <span class="font-semibold"><?php echo htmlspecialchars($label); ?></span>
                    <span class="text-slate-400"><?php echo htmlspecialchars(date('M j, g:i a', strtotime((string) ($threadMessage['created_at'] ?? 'now')))); ?></span>
                  </div>
                  <p class="mt-2 whitespace-pre-line text-sm leading-6"><?php echo htmlspecialchars((string) ($threadMessage['message'] ?? '')); ?></p>
                </article>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </article>
      <article class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
          <div class="flex items-center justify-between gap-3 mb-3">
            <h2 class="text-lg font-bold text-slate-900">Active conversations</h2>
            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400"><?php echo count($threads); ?> threads</span>
          </div>
          <div class="space-y-3 text-sm max-h-[32rem] overflow-y-auto pr-1">
            <?php if (!$threads): ?>
              <p class="rounded-lg bg-slate-50 p-3 text-slate-500">No saved chat threads yet.</p>
            <?php endif; ?>
            <?php foreach ($threads as $thread): ?>
              <?php $isActive = $selectedThreadKey === (string) $thread['key']; ?>
              <a href="?thread=<?php echo urlencode((string) $thread['key']); ?>" class="block rounded-xl border px-4 py-3 transition <?php echo $isActive ? 'border-cyan-300 bg-cyan-50 shadow-sm' : 'border-slate-200 bg-slate-50 hover:border-slate-300 hover:bg-white'; ?>">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="flex items-center gap-2">
                      <p class="font-semibold text-slate-900"><?php echo htmlspecialchars((string) ($thread['name'] ?: 'Anonymous visitor')); ?></p>
                      <?php if ((int) ($thread['unread'] ?? 0) > 0): ?>
                        <span class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.16em] text-white">New</span>
                      <?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-500"><?php echo htmlspecialchars((string) ($thread['email'] ?: 'No email captured')); ?></p>
                  </div>
                  <div class="flex items-center gap-2">
                    <?php if ((int) ($thread['unread'] ?? 0) > 0): ?>
                      <span class="rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-semibold text-rose-700"><?php echo (int) ($thread['unread'] ?? 0); ?> unread</span>
                    <?php endif; ?>
                    <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-500"><?php echo (int) ($thread['count'] ?? 0); ?></span>
                  </div>
                </div>
                <p class="mt-2 text-sm text-slate-600"><?php echo htmlspecialchars((string) ($thread['last_message'] ?? '')); ?></p>
                <div class="mt-2 flex items-center justify-between text-[11px] uppercase tracking-[0.14em] text-slate-400">
                  <span><?php echo htmlspecialchars((string) ucfirst((string) ($thread['last_type'] ?? 'chat'))); ?></span>
                  <span><?php echo htmlspecialchars(date('M j', strtotime((string) ($thread['updated_at'] ?? 'now')))); ?></span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6">
          <h2 class="text-lg font-bold text-slate-900 mb-3">Registered recipients</h2>
          <ul class="space-y-3 text-sm max-h-72 overflow-y-auto pr-1">
            <?php foreach ($users as $usr): ?>
            <li class="flex items-center justify-between gap-3 p-3 rounded-lg bg-slate-50">
              <span class="text-slate-700"><?php echo htmlspecialchars((string) $usr['full_name']); ?></span>
              <span class="text-slate-500"><?php echo htmlspecialchars((string) $usr['email']); ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </article>
    </section>
  </main>
  <script>
    (function(){
      const phoneInput = document.querySelector('input[name="phone"]');
      const msgInput = document.querySelector('textarea[name="message"]');
      const link = document.getElementById('waLink');
      function updateLink() {
        const phone = (phoneInput.value || '').replace(/\D+/g,'');
        const text = encodeURIComponent(msgInput.value || '');
        link.href = phone ? `https://wa.me/${phone}?text=${text}` : 'https://wa.me/?text='+text;
      }
      phoneInput.addEventListener('input', updateLink);
      msgInput.addEventListener('input', updateLink);
      updateLink();
    })();
  </script>
</body>
</html>
