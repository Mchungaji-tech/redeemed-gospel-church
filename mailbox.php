<?php
require __DIR__ . '/includes/app.php';

$expectedKey = (string) rgcConfig('mailbox.key', '');
$providedKey = (string) ($_GET['key'] ?? '');
$mailMode = strtolower((string) rgcConfig('mail.mode', 'test'));

if ($mailMode !== 'test') {
  http_response_code(403);
  echo 'Mailbox is only available when RGC_MAIL_MODE=test.';
  exit;
}

if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
  http_response_code(403);
  echo 'Invalid mailbox key.';
  exit;
}

$rows = [];
$total = 0;
$mailboxTableReady = true;
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per'] ?? 20);
if ($perPage < 5) { $perPage = 5; }
if ($perPage > 100) { $perPage = 100; }
$offset = ($page - 1) * $perPage;
$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$to = trim((string) ($_GET['to'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('mailbox');
  $action = (string) ($_POST['action'] ?? '');
  if ($action === 'clear' && rgcDbAvailable()) {
    try {
      $stmt = rgcDb()->prepare('DELETE FROM outbound_emails');
      $stmt->execute();
    } catch (Throwable $e) {
      $mailboxTableReady = false;
    }
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?key=' . urlencode($providedKey));
    exit;
  }
  if ($action === 'delete' && rgcDbAvailable()) {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
      try {
        $stmt = rgcDb()->prepare('DELETE FROM outbound_emails WHERE id = :id');
        $stmt->execute([':id' => $id]);
      } catch (Throwable $e) {
        $mailboxTableReady = false;
      }
    }
    $redir = $_GET;
    $redir['key'] = $providedKey;
    $qs = http_build_query($redir);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . ($qs ? '?' . $qs : ''));
    exit;
  }
}

if (rgcDbAvailable()) {
  $where = [];
  $params = [];
  if ($q !== '') {
    $where[] = '(subject LIKE :q OR body LIKE :q)';
    $params[':q'] = '%' . $q . '%';
  }
  if ($status !== '') {
    $where[] = 'status = :status';
    $params[':status'] = $status;
  }
  if ($to !== '') {
    $where[] = 'mail_to LIKE :to';
    $params[':to'] = '%' . $to . '%';
  }
  $whereSql = '';
  if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
  }
  try {
    $countSql = 'SELECT COUNT(*) AS c FROM outbound_emails ' . $whereSql;
    $stmt = rgcDb()->prepare($countSql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    $total = (int) ($row['c'] ?? 0);

    $sql = 'SELECT id, mail_to, subject, body, status, error_message, created_at
            FROM outbound_emails ' . $whereSql . ' ORDER BY id DESC LIMIT :limit OFFSET :offset';
    $stmt = rgcDb()->prepare($sql);
    foreach ($params as $k => $v) {
      $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
  } catch (Throwable $e) {
    $mailboxTableReady = false;
    $rows = [];
    $total = 0;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test Mailbox</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    function copyText(text) {
      navigator.clipboard.writeText(text);
    }
    function toggleAutoRefresh(cb) {
      if (cb.checked) {
        const params = new URLSearchParams(window.location.search);
        const key = params.get('key') || '';
        window.mailboxInterval = setInterval(function() {
          const url = new URL(window.location.href);
          url.searchParams.set('key', key);
          fetch(url.toString(), {headers: {'X-Requested-With': 'fetch'}})
            .then(r => r.text())
            .then(html => {
              const parser = new DOMParser();
              const doc = parser.parseFromString(html, 'text/html');
              const list = doc.getElementById('mailList');
              if (list) {
                document.getElementById('mailList').innerHTML = list.innerHTML;
              }
            });
        }, 8000);
      } else {
        if (window.mailboxInterval) {
          clearInterval(window.mailboxInterval);
          window.mailboxInterval = null;
        }
      }
    }
  </script>
</head>
<body class="bg-slate-100 min-h-screen">
  <main class="max-w-5xl mx-auto px-4 py-8">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Test Mailbox</h1>
        <p class="text-sm text-slate-600 mt-1">Filter, search, and manage captured emails.</p>
      </div>
      <label class="inline-flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" onchange="toggleAutoRefresh(this)">
        <span>Auto-refresh</span>
      </label>
    </div>

    <form method="get" class="mt-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <input type="hidden" name="key" value="<?php echo htmlspecialchars($providedKey); ?>">
      <input name="q" value="<?php echo htmlspecialchars($q); ?>" class="border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Search subject or body">
      <input name="to" value="<?php echo htmlspecialchars($to); ?>" class="border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Filter to address">
      <select name="status" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
        <option value="">All status</option>
        <option value="queued_test" <?php echo $status==='queued_test'?'selected':''; ?>>queued_test</option>
        <option value="sent" <?php echo $status==='sent'?'selected':''; ?>>sent</option>
        <option value="failed" <?php echo $status==='failed'?'selected':''; ?>>failed</option>
      </select>
      <div class="flex gap-2">
        <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm">Apply</button>
        <a href="?key=<?php echo urlencode($providedKey); ?>" class="px-4 py-2 rounded-lg border border-slate-300 text-sm bg-white">Reset</a>
        <?php
          $exportParams = ['key' => $providedKey];
          if ($q !== '') { $exportParams['q'] = $q; }
          if ($to !== '') { $exportParams['to'] = $to; }
          if ($status !== '') { $exportParams['status'] = $status; }
          $exportUrl = '/rgc/mailbox_export.php?' . http_build_query($exportParams);
        ?>
        <a href="<?php echo htmlspecialchars($exportUrl); ?>" class="px-4 py-2 rounded-lg border border-slate-300 text-sm bg-white">Download CSV</a>
      </div>
    </form>

    <div class="mt-4 flex items-center justify-between">
      <p class="text-sm text-slate-600">Total: <?php echo (int) $total; ?></p>
      <form method="post" onsubmit="return confirm('Clear all captured emails?');">
        <?php echo rgcCsrfField('mailbox'); ?>
        <input type="hidden" name="action" value="clear">
        <button class="px-3 py-1.5 rounded-lg bg-rose-600 text-white text-sm <?php echo !$mailboxTableReady ? 'opacity-50 cursor-not-allowed' : ''; ?>" <?php echo !$mailboxTableReady ? 'disabled' : ''; ?>>Clear All</button>
      </form>
    </div>

    <?php if (!$mailboxTableReady): ?>
    <div class="mt-4 p-3 rounded-lg text-sm bg-amber-50 text-amber-800 border border-amber-200">
      Table <code>outbound_emails</code> is missing. Import <code>database/schema.sql</code>, then refresh this page.
    </div>
    <?php endif; ?>

    <div id="mailList" class="mt-4 space-y-4">
      <?php foreach ($rows as $row): ?>
      <article class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="min-w-0">
            <p class="font-semibold text-slate-900 truncate"><?php echo htmlspecialchars((string) $row['subject']); ?></p>
            <p class="text-sm text-slate-600 mt-1">To: <?php echo htmlspecialchars((string) $row['mail_to']); ?></p>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xs px-2 py-1 rounded bg-slate-100 text-slate-700"><?php echo htmlspecialchars((string) $row['created_at']); ?></span>
            <span class="text-xs px-2 py-1 rounded <?php echo ($row['status']==='failed'?'bg-rose-100 text-rose-700':($row['status']==='sent'?'bg-emerald-100 text-emerald-700':'bg-slate-100 text-slate-700')); ?>">
              <?php echo htmlspecialchars((string) $row['status']); ?>
            </span>
          </div>
        </div>
        <?php if (!empty($row['error_message'])): ?>
        <p class="text-xs text-rose-700 mt-2">Error: <?php echo htmlspecialchars((string) $row['error_message']); ?></p>
        <?php endif; ?>
        <details class="mt-3">
          <summary class="text-sm text-slate-700 cursor-pointer">Show body</summary>
          <pre class="mt-2 text-sm bg-slate-50 border border-slate-200 rounded p-3 whitespace-pre-wrap"><?php echo htmlspecialchars((string) $row['body']); ?></pre>
        </details>
        <div class="mt-3 flex items-center gap-2">
          <button onclick='copyText(<?php echo json_encode((string) $row["subject"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' class="text-xs px-3 py-1 rounded border border-slate-200 bg-white">Copy subject</button>
          <button onclick='copyText(<?php echo json_encode((string) $row["body"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' class="text-xs px-3 py-1 rounded border border-slate-200 bg-white">Copy body</button>
          <form method="post" onsubmit="return confirm('Delete this email item?');" class="ml-auto">
            <?php echo rgcCsrfField('mailbox'); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
            <button class="text-xs px-3 py-1 rounded bg-rose-600 text-white">Delete</button>
          </form>
        </div>
      </article>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
      <p class="text-slate-500">No emails captured yet.</p>
      <?php endif; ?>
    </div>

    <?php
      $totalPages = (int) ceil($total / max(1, $perPage));
      $baseParams = $_GET;
      $baseParams['key'] = $providedKey;
    ?>
    <?php if ($totalPages > 1): ?>
    <nav class="mt-6 flex items-center justify-between">
      <div class="text-sm text-slate-600">
        Page <?php echo (int) $page; ?> of <?php echo (int) $totalPages; ?>
      </div>
      <div class="flex gap-2">
        <?php
          $prevParams = $baseParams; $prevParams['page'] = max(1, $page - 1);
          $nextParams = $baseParams; $nextParams['page'] = min($totalPages, $page + 1);
        ?>
        <a class="px-3 py-1.5 rounded border border-slate-300 bg-white text-sm <?php echo $page<=1?'opacity-50 pointer-events-none':''; ?>"
           href="?<?php echo htmlspecialchars(http_build_query($prevParams)); ?>">Prev</a>
        <a class="px-3 py-1.5 rounded border border-slate-300 bg-white text-sm <?php echo $page>=$totalPages?'opacity-50 pointer-events-none':''; ?>"
           href="?<?php echo htmlspecialchars(http_build_query($nextParams)); ?>">Next</a>
      </div>
    </nav>
    <?php endif; ?>
  </main>
</body>
</html>
