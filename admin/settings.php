<?php
require __DIR__ . '/includes/auth.php';
requireSuperAdmin();

$settings = rgcLoadJson('settings.json', rgcDefaultSettings());
$mailMode = strtolower((string) rgcConfig('mail.mode', 'test'));
$mailKey = (string) rgcConfig('mailbox.key', '');
$mailboxUrl = rgcBaseUrl() . '/mailbox.php' . ($mailKey !== '' ? ('?key=' . urlencode($mailKey)) : '');
$bypassMinutes = (int) ($settings['maintenance_bypass_minutes'] ?? 30);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('admin_settings');
  $settings['maintenance_mode'] = isset($_POST['maintenance_mode']);
  $settings['maintenance_message'] = trim($_POST['maintenance_message'] ?? 'Site under maintenance.');
  $settings['broadcast_enabled'] = isset($_POST['broadcast_enabled']);
  $settings['broadcast_message'] = trim($_POST['broadcast_message'] ?? '');
  $rawBypass = (int) ($_POST['maintenance_bypass_minutes'] ?? $bypassMinutes);
  if ($rawBypass < 5) { $rawBypass = 5; }
  if ($rawBypass > 180) { $rawBypass = 180; }
  $settings['maintenance_bypass_minutes'] = $rawBypass;
  if (isset($_POST['force_logout_all'])) {
    $settings['force_logout_at'] = time();
    $u = rgcCurrentUser();
    rgcSecurityLog('force_logout_all', 'Super admin triggered force logout for all sessions.', (int) ($u['id'] ?? 0), (string) ($u['username'] ?? null));
  }
  rgcSaveJson('settings.json', $settings);
  header('Location: ' . rgcUrl('admin/settings.php?saved=1'));
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>System Settings</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-6xl mx-auto px-4 py-8 space-y-6">
    <section class="rounded-2xl bg-gradient-to-r from-rose-700 via-orange-600 to-amber-500 text-white p-6 shadow-lg">
      <h1 class="text-2xl font-bold">System Settings</h1>
      <p class="mt-2 text-amber-100">Super Admin controls for maintenance, broadcast notices, and force logout actions.</p>
    </section>

    <section class="grid lg:grid-cols-3 gap-6">
      <article class="bg-white rounded-xl shadow border border-slate-200 p-6 lg:col-span-2">
        <h2 class="text-lg font-bold text-slate-900 mb-1">Global Controls</h2>
        <p class="text-sm text-slate-500 mb-4">Changes here affect all public visitors and admin sessions.</p>

        <?php if (isset($_GET['saved'])): ?>
        <p class="mb-3 text-sm rounded bg-emerald-50 text-emerald-700 px-3 py-2">Settings saved successfully.</p>
        <?php endif; ?>

        <form method="post" class="space-y-4">
          <?php echo rgcCsrfField('admin_settings'); ?>
          <label class="flex items-center gap-2 rounded-lg border border-slate-200 p-3 bg-slate-50 text-sm">
            <input type="checkbox" name="maintenance_mode" <?php echo !empty($settings['maintenance_mode']) ? 'checked' : ''; ?>>
            <span class="font-medium text-slate-700">Enable maintenance mode</span>
          </label>
          <textarea name="maintenance_message" class="w-full border border-slate-300 rounded-lg px-3 py-2" rows="3" placeholder="Maintenance message"><?php echo htmlspecialchars($settings['maintenance_message'] ?? ''); ?></textarea>

          <label class="flex items-center gap-2 rounded-lg border border-slate-200 p-3 bg-slate-50 text-sm">
            <input type="checkbox" name="broadcast_enabled" <?php echo !empty($settings['broadcast_enabled']) ? 'checked' : ''; ?>>
            <span class="font-medium text-slate-700">Enable broadcast banner</span>
          </label>
          <textarea name="broadcast_message" class="w-full border border-slate-300 rounded-lg px-3 py-2" rows="3" placeholder="Message to all users"><?php echo htmlspecialchars($settings['broadcast_message'] ?? ''); ?></textarea>

          <div class="rounded-lg border border-slate-200 p-3 bg-slate-50 text-sm">
            <label class="font-medium text-slate-700">Maintenance bypass duration (minutes)</label>
            <input type="number" min="5" max="180" name="maintenance_bypass_minutes" value="<?php echo (int) ($settings['maintenance_bypass_minutes'] ?? 30); ?>" class="mt-1 w-32 border border-slate-300 rounded-lg px-3 py-2">
            <p class="text-xs text-slate-500 mt-1">Admins who click “View Site” can preview public pages for this duration while maintenance is ON.</p>
          </div>

          <label class="flex items-center gap-2 rounded-lg border border-rose-200 p-3 bg-rose-50 text-sm">
            <input type="checkbox" name="force_logout_all">
            <span class="font-medium text-rose-700">Force logout all currently logged-in users</span>
          </label>

          <button class="px-5 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Save Settings</button>
        </form>
      </article>

      <article class="bg-white rounded-xl shadow border border-slate-200 p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-3">Current Status</h3>
        <div class="space-y-3 text-sm">
          <div class="p-3 rounded-lg border border-slate-200 flex items-center justify-between">
            <span class="text-slate-600">Maintenance</span>
            <span class="font-semibold <?php echo !empty($settings['maintenance_mode']) ? 'text-rose-700' : 'text-emerald-700'; ?>">
              <?php echo !empty($settings['maintenance_mode']) ? 'ON' : 'OFF'; ?>
            </span>
          </div>
          <div class="p-3 rounded-lg border border-slate-200 flex items-center justify-between">
            <span class="text-slate-600">Broadcast</span>
            <span class="font-semibold <?php echo !empty($settings['broadcast_enabled']) ? 'text-emerald-700' : 'text-slate-500'; ?>">
              <?php echo !empty($settings['broadcast_enabled']) ? 'ON' : 'OFF'; ?>
            </span>
          </div>
          <div class="p-3 rounded-lg border border-slate-200">
            <p class="text-slate-600 mb-1">Broadcast Message</p>
            <p class="text-slate-800 text-xs leading-relaxed"><?php echo htmlspecialchars($settings['broadcast_message'] ?? ''); ?></p>
          </div>
        </div>
      </article>
    </section>

    <section class="grid lg:grid-cols-3 gap-6">
      <article class="bg-white rounded-xl shadow border border-slate-200 p-6 lg:col-span-3">
        <h2 class="text-lg font-bold text-slate-900 mb-1">Test Mailbox Access</h2>
        <p class="text-sm text-slate-500 mb-4">When mail mode is test, outbound emails are captured for review.</p>
        <div class="grid sm:grid-cols-3 gap-4">
          <div class="p-4 rounded-lg border border-slate-200 bg-slate-50">
            <p class="text-xs text-slate-500">Mail Mode</p>
            <p class="font-semibold mt-1 <?php echo $mailMode==='test' ? 'text-emerald-700' : 'text-slate-800'; ?>"><?php echo htmlspecialchars($mailMode); ?></p>
          </div>
          <div class="p-4 rounded-lg border border-slate-200 bg-slate-50">
            <p class="text-xs text-slate-500">Mailbox Key</p>
            <div class="mt-1 flex items-center gap-2">
              <input type="password" value="<?php echo htmlspecialchars($mailKey); ?>" id="rgcMailboxKey" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white" readonly>
              <button type="button" onclick="const i=document.getElementById('rgcMailboxKey'); i.type=i.type==='password'?'text':'password';" class="px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm">Show</button>
              <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('rgcMailboxKey').value)" class="px-3 py-2 rounded-lg bg-slate-900 text-white text-sm">Copy</button>
            </div>
          </div>
          <div class="p-4 rounded-lg border border-slate-200 bg-slate-50">
            <p class="text-xs text-slate-500">Open Mailbox</p>
            <?php if ($mailMode === 'test' && $mailKey !== ''): ?>
              <a href="<?php echo htmlspecialchars($mailboxUrl); ?>" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm mt-1">Open Mailbox</a>
              <button type="button" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($mailboxUrl, ENT_QUOTES); ?>')" class="ml-2 px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm">Copy Link</button>
            <?php else: ?>
              <p class="text-sm text-amber-700 mt-1">Set RGC_MAIL_MODE=test and RGC_MAILBOX_KEY in .env</p>
            <?php endif; ?>
          </div>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
