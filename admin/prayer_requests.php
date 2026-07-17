<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
$rows = rgcLoadJson('prayer_requests.json', []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Prayer Requests</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <section class="rounded-2xl bg-gradient-to-r from-amber-600 via-orange-500 to-rose-500 text-white p-6 shadow-lg">
      <h1 class="text-2xl font-bold">Prayer Inbox</h1>
      <p class="text-amber-100 mt-2">Review incoming prayer requests and support members with timely follow-up.</p>
    </section>

    <section class="bg-white rounded-xl shadow border border-slate-200 p-6">
      <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h2 class="text-lg font-bold text-slate-900">All Requests</h2>
        <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600"><?php echo count($rows); ?> total</span>
      </div>

      <div class="space-y-3">
        <?php if (empty($rows)): ?>
        <p class="text-sm text-slate-500">No prayer requests yet.</p>
        <?php endif; ?>

        <?php foreach (array_reverse($rows) as $r): ?>
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($r['name'] ?? ''); ?></p>
              <?php if (!empty($r['email'])): ?>
              <p class="text-xs text-slate-600"><?php echo htmlspecialchars($r['email']); ?></p>
              <?php endif; ?>
            </div>
            <span class="text-xs text-slate-500"><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></span>
          </div>
          <p class="text-sm text-slate-700 mt-3 leading-relaxed"><?php echo nl2br(htmlspecialchars($r['request'] ?? '')); ?></p>
        </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
</body>
</html>
