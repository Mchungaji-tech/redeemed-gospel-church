<?php
require __DIR__ . '/includes/auth.php';
requireSuperAdmin();

$limit = (int) ($_GET['limit'] ?? 200);
if ($limit < 50) { $limit = 50; }
if ($limit > 1000) { $limit = 1000; }

$logs = [];
$stmt = rgcDb()->prepare('SELECT id, username, event_type, event_message, ip_address, user_agent, created_at FROM security_logs ORDER BY id DESC LIMIT :limit');
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Security Logs</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <section class="rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-rose-700 text-white p-6 shadow-lg">
      <h1 class="text-2xl font-bold">Security Logs</h1>
      <p class="text-slate-200 mt-2">Authentication events, suspicious client blocks, lockouts, and account actions.</p>
    </section>

    <section class="bg-white rounded-xl shadow border border-slate-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-slate-600">Showing latest <?php echo (int) $limit; ?> records</p>
        <div class="flex gap-2 text-sm">
          <a class="px-3 py-1.5 rounded border border-slate-300 hover:bg-slate-50" href="<?php echo rgcUrl('admin/security_logs.php?limit=200'); ?>">200</a>
          <a class="px-3 py-1.5 rounded border border-slate-300 hover:bg-slate-50" href="<?php echo rgcUrl('admin/security_logs.php?limit=500'); ?>">500</a>
          <a class="px-3 py-1.5 rounded border border-slate-300 hover:bg-slate-50" href="<?php echo rgcUrl('admin/security_logs.php?limit=1000'); ?>">1000</a>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left border-b border-slate-200">
              <th class="py-2 pr-3">Time</th>
              <th class="py-2 pr-3">Type</th>
              <th class="py-2 pr-3">User</th>
              <th class="py-2 pr-3">IP</th>
              <th class="py-2 pr-3">Message</th>
              <th class="py-2 pr-3">Client</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logs as $log): ?>
            <tr class="border-b border-slate-100 align-top">
              <td class="py-2 pr-3 whitespace-nowrap"><?php echo htmlspecialchars((string) ($log['created_at'] ?? '')); ?></td>
              <td class="py-2 pr-3"><span class="px-2 py-1 rounded bg-slate-100 text-slate-700"><?php echo htmlspecialchars((string) ($log['event_type'] ?? '')); ?></span></td>
              <td class="py-2 pr-3"><?php echo htmlspecialchars((string) ($log['username'] ?? '-')); ?></td>
              <td class="py-2 pr-3"><?php echo htmlspecialchars((string) ($log['ip_address'] ?? '-')); ?></td>
              <td class="py-2 pr-3"><?php echo htmlspecialchars((string) ($log['event_message'] ?? '')); ?></td>
              <td class="py-2 pr-3 max-w-sm truncate" title="<?php echo htmlspecialchars((string) ($log['user_agent'] ?? '')); ?>"><?php echo htmlspecialchars((string) ($log['user_agent'] ?? '')); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
            <tr>
              <td colspan="6" class="py-4 text-slate-500">No security logs found.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
