<?php
require __DIR__ . '/includes/auth.php';
requireLogin();

$sermons = rgcLoadJson('sermons.json', []);
$events = rgcLoadJson('events.json', []);
$prayers = rgcLoadJson('prayer_requests.json', []);
$ministries = rgcLoadJson('ministries.json', []);
$projects = rgcLoadJson('projects.json', []);
$testimonials = rgcLoadJson('testimonials.json', []);
$gallery = rgcLoadJson('gallery.json', []);
$settings = rgcLoadJson('settings.json', rgcDefaultSettings());
$isSuperAdmin = rgcIsSuperAdmin();
$user = rgcCurrentUser();

$maintenanceOn = !empty($settings['maintenance_mode']);
$broadcastOn = !empty($settings['broadcast_enabled']);
$dbOnline = rgcDbAvailable();
$totalAdmins = rgcCountUsers();
$pendingActivations = rgcCountPendingActivations();
$securityEvents24h = rgcCountRecentSecurityEvents(24);
$uploadStorage = rgcFormatBytes(rgcDirSize(dirname(__DIR__) . '/assets/uploads'));
$contentRows = 0;
if ($dbOnline) {
  $tables = ['sermons', 'events', 'ministries', 'projects', 'testimonials', 'gallery', 'comments', 'prayer_requests'];
  foreach ($tables as $table) {
    $stmt = rgcDb()->query("SELECT COUNT(*) AS c FROM {$table}");
    $contentRows += (int) ($stmt->fetch()['c'] ?? 0);
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <section class="rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-700 text-white p-6 md:p-8 shadow-xl">
      <div class="flex flex-wrap items-start justify-between gap-5">
        <div>
          <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Command Center</p>
          <div class="mt-1 flex items-center gap-3">
            <?php if (!empty($user['avatar'])): ?>
              <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover border border-white/20">
            <?php endif; ?>
            <h2 class="text-2xl md:text-3xl font-bold">Welcome back, <?php echo htmlspecialchars($user['name'] ?? 'Admin'); ?></h2>
          </div>
          <p class="text-slate-200 mt-2 max-w-2xl">Manage public pages, media content, event schedules, and system configuration from one polished workspace.</p>
          <div class="mt-4 flex flex-wrap gap-2 text-xs">
            <span class="px-3 py-1 rounded-full border border-white/20 bg-white/10">Role: <?php echo htmlspecialchars($user['role'] ?? 'admin'); ?></span>
            <span class="px-3 py-1 rounded-full border border-white/20 bg-white/10">Maintenance: <?php echo $maintenanceOn ? 'ON' : 'OFF'; ?></span>
            <span class="px-3 py-1 rounded-full border border-white/20 bg-white/10">Broadcast: <?php echo $broadcastOn ? 'ON' : 'OFF'; ?></span>
          </div>
        </div>

        <div class="flex flex-wrap gap-2">
          <a href="<?php echo rgcUrl('admin/events.php'); ?>" class="px-4 py-2 rounded-lg bg-amber-400 text-slate-900 font-semibold hover:bg-amber-300">Create Event</a>
          <a href="<?php echo rgcUrl('admin/content.php'); ?>" class="px-4 py-2 rounded-lg bg-white text-slate-800 font-semibold hover:bg-slate-100">Update Content</a>
          <a href="<?php echo rgcUrl('admin/profile.php'); ?>" class="px-4 py-2 rounded-lg bg-indigo-500 text-white font-semibold hover:bg-indigo-400">Edit Profile</a>
          <?php if ($isSuperAdmin): ?>
          <a href="<?php echo rgcUrl('admin/settings.php'); ?>" class="px-4 py-2 rounded-lg bg-emerald-500 text-white font-semibold hover:bg-emerald-400">System Settings</a>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <article class="bg-white rounded-xl p-5 shadow border border-slate-200">
        <div class="flex items-center justify-between">
          <p class="text-sm font-medium text-slate-500">Sermons</p>
          <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
          </div>
        </div>
        <p class="text-3xl font-bold mt-3 text-slate-900"><?php echo count($sermons); ?></p>
        <a href="<?php echo rgcUrl('admin/sermons.php'); ?>" class="inline-flex items-center gap-1 text-sm text-indigo-700 mt-3 hover:underline">Manage sermons</a>
      </article>

      <article class="bg-white rounded-xl p-5 shadow border border-slate-200">
        <div class="flex items-center justify-between">
          <p class="text-sm font-medium text-slate-500">Events</p>
          <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </div>
        </div>
        <p class="text-3xl font-bold mt-3 text-slate-900"><?php echo count($events); ?></p>
        <a href="<?php echo rgcUrl('admin/events.php'); ?>" class="inline-flex items-center gap-1 text-sm text-emerald-700 mt-3 hover:underline">Manage events</a>
      </article>

      <article class="bg-white rounded-xl p-5 shadow border border-slate-200">
        <div class="flex items-center justify-between">
          <p class="text-sm font-medium text-slate-500">Prayer Inbox</p>
          <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4v-4z"/></svg>
          </div>
        </div>
        <p class="text-3xl font-bold mt-3 text-slate-900"><?php echo count($prayers); ?></p>
        <a href="<?php echo rgcUrl('admin/prayer_requests.php'); ?>" class="inline-flex items-center gap-1 text-sm text-amber-700 mt-3 hover:underline">Open inbox</a>
      </article>

      <article class="bg-white rounded-xl p-5 shadow border border-slate-200">
        <div class="flex items-center justify-between">
          <p class="text-sm font-medium text-slate-500">Maintenance</p>
          <div class="w-10 h-10 rounded-lg <?php echo $maintenanceOn ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'; ?> flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
          </div>
        </div>
        <p class="text-3xl font-bold mt-3 <?php echo $maintenanceOn ? 'text-rose-700' : 'text-emerald-700'; ?>"><?php echo $maintenanceOn ? 'ON' : 'OFF'; ?></p>
        <?php if ($isSuperAdmin): ?>
        <a href="<?php echo rgcUrl('admin/settings.php'); ?>" class="inline-flex items-center gap-1 text-sm text-slate-700 mt-3 hover:underline">Adjust settings</a>
        <?php endif; ?>
      </article>
    </section>

    <section class="grid lg:grid-cols-3 gap-5">
      <article class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow p-6">
        <div class="flex items-center justify-between mb-5">
          <h3 class="text-lg font-bold text-slate-900">Management Shortcuts</h3>
          <span class="text-xs text-slate-500">Quick actions</span>
        </div>
        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
          <a class="group rounded-xl p-4 border border-slate-200 bg-gradient-to-br from-emerald-50 to-white hover:border-emerald-300 transition" href="<?php echo rgcUrl('admin/events.php'); ?>">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center mb-3">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <p class="font-semibold text-slate-900">Events + Posters</p>
            <p class="text-sm text-slate-600 mt-1">Create upcoming events and poster visuals.</p>
            <span class="inline-flex mt-3 text-xs px-2 py-1 rounded-md bg-emerald-100 text-emerald-700">Open module</span>
          </a>
          <a class="group rounded-xl p-4 border border-slate-200 bg-gradient-to-br from-indigo-50 to-white hover:border-indigo-300 transition" href="<?php echo rgcUrl('admin/sermons.php'); ?>">
            <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center mb-3">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
            </div>
            <p class="font-semibold text-slate-900">Sermons</p>
            <p class="text-sm text-slate-600 mt-1">Manage live flags and message embeds.</p>
            <span class="inline-flex mt-3 text-xs px-2 py-1 rounded-md bg-indigo-100 text-indigo-700">Open module</span>
          </a>
          <a class="group rounded-xl p-4 border border-slate-200 bg-gradient-to-br from-fuchsia-50 to-white hover:border-fuchsia-300 transition" href="<?php echo rgcUrl('admin/content.php'); ?>">
            <div class="w-10 h-10 rounded-lg bg-fuchsia-100 text-fuchsia-700 flex items-center justify-center mb-3">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <p class="font-semibold text-slate-900">Site Content</p>
            <p class="text-sm text-slate-600 mt-1">Ministries, projects, testimonials, gallery.</p>
            <span class="inline-flex mt-3 text-xs px-2 py-1 rounded-md bg-fuchsia-100 text-fuchsia-700">Open module</span>
          </a>
          <a class="group rounded-xl p-4 border border-slate-200 bg-gradient-to-br from-amber-50 to-white hover:border-amber-300 transition" href="<?php echo rgcUrl('admin/prayer_requests.php'); ?>">
            <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center mb-3">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4v-4z"/></svg>
            </div>
            <p class="font-semibold text-slate-900">Prayer Inbox</p>
            <p class="text-sm text-slate-600 mt-1">Review and respond to requests promptly.</p>
            <span class="inline-flex mt-3 text-xs px-2 py-1 rounded-md bg-amber-100 text-amber-700">Open module</span>
          </a>
          <?php if ($isSuperAdmin): ?>
          <a class="group rounded-xl p-4 border border-slate-200 bg-gradient-to-br from-cyan-50 to-white hover:border-cyan-300 transition" href="<?php echo rgcUrl('admin/users.php'); ?>">
            <div class="w-10 h-10 rounded-lg bg-cyan-100 text-cyan-700 flex items-center justify-center mb-3">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.126-1.278-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.126-1.278.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <p class="font-semibold text-slate-900">Users</p>
            <p class="text-sm text-slate-600 mt-1">Add or manage admin and super admin users.</p>
            <span class="inline-flex mt-3 text-xs px-2 py-1 rounded-md bg-cyan-100 text-cyan-700">Open module</span>
          </a>
          <a class="group rounded-xl p-4 border border-slate-200 bg-gradient-to-br from-rose-50 to-white hover:border-rose-300 transition" href="<?php echo rgcUrl('admin/settings.php'); ?>">
            <div class="w-10 h-10 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center mb-3">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317a1 1 0 011.35-.936l1.486.593a1 1 0 00.839 0l1.486-.593a1 1 0 011.35.936l.129 1.598a1 1 0 00.594.84l1.458.657a1 1 0 01.59 1.247l-.46 1.537a1 1 0 000 .84l.46 1.537a1 1 0 01-.59 1.247l-1.458.657a1 1 0 00-.594.84l-.129 1.598a1 1 0 01-1.35.936l-1.486-.593a1 1 0 00-.839 0l-1.486.593a1 1 0 01-1.35-.936l-.129-1.598a1 1 0 00-.594-.84l-1.458-.657a1 1 0 01-.59-1.247l.46-1.537a1 1 0 000-.84l-.46-1.537a1 1 0 01.59-1.247l1.458-.657a1 1 0 00.594-.84l.129-1.598z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <p class="font-semibold text-slate-900">System Settings</p>
            <p class="text-sm text-slate-600 mt-1">Maintenance, banner broadcast, force logout.</p>
            <span class="inline-flex mt-3 text-xs px-2 py-1 rounded-md bg-rose-100 text-rose-700">Open module</span>
          </a>
          <?php endif; ?>
        </div>
      </article>

      <article class="bg-white rounded-xl border border-slate-200 shadow p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-4">Content Footprint</h3>
        <ul class="space-y-3 text-sm">
          <li class="flex items-center justify-between p-3 rounded-lg bg-slate-50"><span class="text-slate-700">Ministries</span><strong class="text-slate-900"><?php echo count($ministries); ?></strong></li>
          <li class="flex items-center justify-between p-3 rounded-lg bg-slate-50"><span class="text-slate-700">Projects</span><strong class="text-slate-900"><?php echo count($projects); ?></strong></li>
          <li class="flex items-center justify-between p-3 rounded-lg bg-slate-50"><span class="text-slate-700">Testimonials</span><strong class="text-slate-900"><?php echo count($testimonials); ?></strong></li>
          <li class="flex items-center justify-between p-3 rounded-lg bg-slate-50"><span class="text-slate-700">Gallery Images</span><strong class="text-slate-900"><?php echo count($gallery); ?></strong></li>
        </ul>
        <a href="<?php echo rgcUrl('admin/content.php'); ?>" class="mt-4 inline-flex items-center text-sm text-slate-700 hover:underline">Go to content manager</a>
      </article>
    </section>

    <section class="grid md:grid-cols-2 xl:grid-cols-4 gap-4">
      <article class="bg-white rounded-xl p-5 shadow border border-slate-200">
        <p class="text-sm font-medium text-slate-500">Auth Database</p>
        <p class="text-2xl font-bold mt-2 <?php echo $dbOnline ? 'text-emerald-700' : 'text-rose-700'; ?>"><?php echo $dbOnline ? 'ONLINE' : 'OFFLINE'; ?></p>
        <p class="text-xs text-slate-500 mt-2">Remote server connectivity status for admin auth.</p>
      </article>
      <article class="bg-white rounded-xl p-5 shadow border border-slate-200">
        <p class="text-sm font-medium text-slate-500">Admin Accounts</p>
        <p class="text-2xl font-bold mt-2 text-slate-900"><?php echo (int) $totalAdmins; ?></p>
        <p class="text-xs text-slate-500 mt-2"><?php echo (int) $pendingActivations; ?> pending activation</p>
      </article>
      <article class="bg-white rounded-xl p-5 shadow border border-slate-200">
        <p class="text-sm font-medium text-slate-500">Security Events (24h)</p>
        <p class="text-2xl font-bold mt-2 text-slate-900"><?php echo (int) $securityEvents24h; ?></p>
        <?php if ($isSuperAdmin): ?>
        <a href="<?php echo rgcUrl('admin/security_logs.php'); ?>" class="inline-flex items-center gap-1 text-sm text-indigo-700 mt-3 hover:underline">Review logs</a>
        <?php endif; ?>
      </article>
      <article class="bg-white rounded-xl p-5 shadow border border-slate-200">
        <p class="text-sm font-medium text-slate-500">Storage</p>
        <p class="text-sm mt-2 text-slate-700">Content Rows: <strong><?php echo (int) $contentRows; ?></strong></p>
        <p class="text-sm mt-1 text-slate-700">Uploads: <strong><?php echo htmlspecialchars($uploadStorage); ?></strong></p>
      </article>
    </section>

    <section class="grid lg:grid-cols-2 gap-5">
      <article class="bg-white rounded-xl border border-slate-200 shadow p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-3">Operational Status</h3>
        <div class="space-y-3 text-sm">
          <div class="flex items-center justify-between p-3 rounded-lg border border-slate-200">
            <span class="text-slate-700">Public Site Visibility</span>
            <span class="font-semibold <?php echo $maintenanceOn ? 'text-rose-700' : 'text-emerald-700'; ?>">
              <?php echo $maintenanceOn ? 'Maintenance Page Active' : 'Publicly Accessible'; ?>
            </span>
          </div>
          <div class="flex items-center justify-between p-3 rounded-lg border border-slate-200">
            <span class="text-slate-700">Broadcast Banner</span>
            <span class="font-semibold <?php echo $broadcastOn ? 'text-emerald-700' : 'text-slate-500'; ?>">
              <?php echo $broadcastOn ? 'Enabled' : 'Disabled'; ?>
            </span>
          </div>
          <div class="flex items-center justify-between p-3 rounded-lg border border-slate-200">
            <span class="text-slate-700">Next Priority</span>
            <span class="font-semibold text-slate-900">Refresh events + homepage highlights</span>
          </div>
        </div>
      </article>

      <article class="rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-sky-500 text-white p-6 shadow-xl">
        <h3 class="text-xl font-bold">Polished Admin Workflow</h3>
        <p class="mt-2 text-indigo-100">Use this sequence for fast publishing: create event -> update poster/content -> verify on public pages.</p>
        <div class="mt-5 flex flex-wrap gap-2">
          <a href="<?php echo rgcUrl('admin/events.php'); ?>" class="px-4 py-2 rounded-lg bg-white text-indigo-700 font-semibold hover:bg-slate-100">Open Events</a>
          <a href="<?php echo rgcUrl('admin/content.php'); ?>" class="px-4 py-2 rounded-lg bg-indigo-700/60 border border-white/30 text-white hover:bg-indigo-700">Open Content</a>
          <a href="<?php echo rgcUrl('admin/view_site.php'); ?>" class="px-4 py-2 rounded-lg bg-indigo-800/60 border border-white/30 text-white hover:bg-indigo-800">Preview Site</a>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
