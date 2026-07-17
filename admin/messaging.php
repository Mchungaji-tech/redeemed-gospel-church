<?php
require __DIR__ . '/includes/auth.php';
requireSuperAdmin();
$pageTitle = 'Messaging';
$error = '';
$info = '';
$users = [];
if (rgcDbAvailable()) {
  $stmt = rgcDb()->query('SELECT id, full_name, email FROM public_users ORDER BY created_at DESC LIMIT 200');
  $users = $stmt->fetchAll();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('admin_messaging');
  $mode = (string) ($_POST['mode'] ?? '');
  $targetEmail = trim((string) ($_POST['email'] ?? ''));
  $targetPhone = trim((string) ($_POST['phone'] ?? ''));
  $message = trim((string) ($_POST['message'] ?? ''));
  if ($message === '') {
    $error = 'Message is required.';
  } else {
    if ($mode === 'email' && $targetEmail !== '') {
      $sent = rgcSendMail($targetEmail, 'Message from RGC Admin', $message);
      $info = $sent ? 'Email queued.' : 'Email capture failed (check mail mode).';
    } elseif ($mode === 'message') {
      if (rgcDbAvailable()) {
        $stmt = rgcDb()->prepare('INSERT INTO public_messages (user_id, name, email, type, message, created_at) VALUES (:user_id, :name, :email, :type, :message, NOW())');
        $stmt->execute([':user_id' => null, ':name' => '', ':email' => $targetEmail, ':type' => 'broadcast', ':message' => $message]);
        $info = 'Message recorded.';
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
      <p class="mt-2 text-indigo-100">Send email, WhatsApp, and record messages.</p>
    </section>
    <section class="grid md:grid-cols-3 gap-6">
      <article class="md:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
        <?php if ($info): ?><div class="mb-3 p-3 rounded-lg text-sm bg-emerald-50 text-emerald-700 border border-emerald-200"><?php echo htmlspecialchars($info); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="mb-3 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="post" class="space-y-4">
          <?php echo rgcCsrfField('admin_messaging'); ?>
          <div class="grid md:grid-cols-2 gap-3">
            <div>
              <label class="text-sm font-medium text-slate-700">Mode</label>
              <select name="mode" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
                <option value="email">Email</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="message">Record Message</option>
              </select>
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">Email</label>
              <input name="email" type="email" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="recipient@email.com">
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">Phone (WhatsApp)</label>
              <input name="phone" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="+254700000000">
            </div>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Message</label>
            <textarea name="message" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" rows="4" required></textarea>
          </div>
          <div class="flex gap-2">
            <button class="px-5 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Send / Record</button>
            <a id="waLink" class="px-5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700" target="_blank">Open WhatsApp</a>
          </div>
        </form>
      </article>
      <article class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-3">Recipients</h2>
        <ul class="space-y-3 text-sm">
          <?php foreach ($users as $usr): ?>
          <li class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
            <span class="text-slate-700"><?php echo htmlspecialchars((string) $usr['full_name']); ?></span>
            <span class="text-slate-500"><?php echo htmlspecialchars((string) $usr['email']); ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
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
