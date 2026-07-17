<?php
require __DIR__ . '/../includes/app.php';
rgcEnforcePublicAccess();
rgcPublicRequireLogin('user/dashboard.php');
$u = rgcPublicUser();
$donations = [];
$messages = [];
if (rgcDbAvailable()) {
  $stmt = rgcDb()->prepare('SELECT id, amount_cents, currency, note, status, created_at FROM donations WHERE user_id = :uid ORDER BY id DESC LIMIT 10');
  $stmt->execute([':uid' => (int) ($u['id'] ?? 0)]);
  $donations = $stmt->fetchAll();
  $stmt = rgcDb()->prepare('SELECT id, type, message, created_at FROM public_messages WHERE user_id = :uid ORDER BY id DESC LIMIT 10');
  $stmt->execute([':uid' => (int) ($u['id'] ?? 0)]);
  $messages = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <header class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 text-white border-b border-slate-700">
    <div class="max-w-7xl mx-auto px-4 py-4 flex flex-wrap items-center justify-between gap-3">
      <div>
        <p class="text-xs uppercase tracking-widest text-slate-300">User Panel</p>
        <h1 class="font-semibold text-xl">Welcome, <?php echo htmlspecialchars($u['name'] ?? 'Member'); ?></h1>
        <p class="text-xs text-slate-300">Manage your activity and connect with us.</p>
      </div>
      <div class="flex flex-wrap items-center gap-2 text-sm">
        <a href="<?php echo htmlspecialchars(rgcUrl('index.php')); ?>" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white">View Site</a>
        <a href="<?php echo htmlspecialchars(rgcUrl('user/logout.php?r=' . urlencode('user/dashboard.php'))); ?>" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white">Logout</a>
      </div>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <section class="rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-700 text-white p-6 md:p-8 shadow-xl">
      <div class="flex flex-wrap items-start justify-between gap-5">
        <div>
          <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Quick Access</p>
          <h2 class="text-2xl md:text-3xl font-bold mt-1">Member Control Center</h2>
          <p class="text-slate-200 mt-2 max-w-2xl">Donate, send prayer requests, and review your recent activity.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <a href="<?php echo htmlspecialchars(rgcUrl('donate.php')); ?>" class="px-4 py-2 rounded-lg bg-amber-400 text-slate-900 font-semibold hover:bg-amber-300">Make Donation</a>
          <a href="<?php echo htmlspecialchars(rgcUrl('contact.php')); ?>" class="px-4 py-2 rounded-lg bg-white text-slate-800 font-semibold hover:bg-slate-100">Prayer Request</a>
          <a href="<?php echo htmlspecialchars(rgcUrl('index.php')); ?>" class="px-4 py-2 rounded-lg bg-emerald-500 text-white font-semibold hover:bg-emerald-400">Open Chat</a>
        </div>
      </div>
    </section>

    <section class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <article class="bg-white rounded-xl p-5 shadow border border-slate-200">
        <p class="text-sm font-medium text-slate-500">Donations</p>
        <p class="text-3xl font-bold mt-3 text-slate-900"><?php echo count($donations); ?></p>
        <a href="<?php echo htmlspecialchars(rgcUrl('donate.php')); ?>" class="inline-flex items-center gap-1 text-sm text-indigo-700 mt-3 hover:underline">Make a donation</a>
      </article>
      <article class="bg-white rounded-xl p-5 shadow border border-slate-200">
        <p class="text-sm font-medium text-slate-500">Messages</p>
        <p class="text-3xl font-bold mt-3 text-slate-900"><?php echo count($messages); ?></p>
        <a href="<?php echo htmlspecialchars(rgcUrl('index.php')); ?>" class="inline-flex items-center gap-1 text-sm text-indigo-700 mt-3 hover:underline">Open chat</a>
      </article>
      <article class="bg-white rounded-xl p-5 shadow border border-slate-200">
        <p class="text-sm font-medium text-slate-500">Prayer</p>
        <p class="text-3xl font-bold mt-3 text-slate-900">Send</p>
        <a href="<?php echo htmlspecialchars(rgcUrl('contact.php')); ?>" class="inline-flex items-center gap-1 text-sm text-indigo-700 mt-3 hover:underline">Prayer request</a>
      </article>
      <article class="bg-white rounded-xl p-5 shadow border border-slate-200">
        <p class="text-sm font-medium text-slate-500">Profile</p>
        <p class="text-3xl font-bold mt-3 text-slate-900">View</p>
        <a href="<?php echo htmlspecialchars(rgcUrl('user/dashboard.php')); ?>" class="inline-flex items-center gap-1 text-sm text-indigo-700 mt-3 hover:underline">Refresh</a>
      </article>
    </section>

    <section class="grid lg:grid-cols-3 gap-5">
      <article class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow p-6">
        <div class="flex items-center justify-between mb-5">
          <h3 class="text-lg font-bold text-slate-900">Recent Donations</h3>
          <span class="text-xs text-slate-500">History</span>
        </div>
        <?php if (empty($donations)): ?>
          <p class="text-slate-500 text-sm">No donations yet.</p>
        <?php else: ?>
          <ul class="space-y-3">
            <?php foreach ($donations as $d): ?>
            <li class="flex items-center justify-between p-3 rounded-lg border border-slate-200 bg-slate-50">
              <div class="flex items-center gap-3">
                <span class="px-2 py-1 rounded text-xs <?php echo ($d['status']==='received'?'bg-emerald-100 text-emerald-700':($d['status']==='pending'?'bg-amber-100 text-amber-700':'bg-rose-100 text-rose-700')); ?>"><?php echo htmlspecialchars($d['status']); ?></span>
                <span class="text-slate-700 font-semibold"><?php echo htmlspecialchars($d['currency']); ?> <?php echo number_format(((int) $d['amount_cents']) / 100, 2); ?></span>
              </div>
              <span class="text-slate-500 text-xs"><?php echo htmlspecialchars((string) $d['created_at']); ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </article>

      <article class="bg-white rounded-xl border border-slate-200 shadow p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-4">Messages</h3>
        <?php if (empty($messages)): ?>
          <p class="text-slate-500 text-sm">No messages yet.</p>
        <?php else: ?>
          <ul class="space-y-3 max-h-[440px] overflow-y-auto pr-2">
            <?php foreach ($messages as $m): ?>
            <li class="p-3 rounded-lg bg-slate-50 border border-slate-200">
              <p class="text-xs text-slate-500 mb-1"><?php echo htmlspecialchars((string) $m['type']); ?> · <?php echo htmlspecialchars((string) $m['created_at']); ?></p>
              <p class="text-slate-700 text-sm"><?php echo nl2br(htmlspecialchars((string) $m['message'])); ?></p>
            </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </article>
    </section>
  </main>
</body>
</html>
