<?php
$u = $_SESSION['user'] ?? [];
$role = $u['role'] ?? '';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

function rgcNavClasses(string $href, string $currentPath): string {
  $active = str_ends_with($currentPath, $href);
  if ($active) {
    return 'inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-900 text-white shadow-sm';
  }
  return 'inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white text-slate-700 border border-slate-200 hover:bg-slate-50';
}
?>
<header class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 text-white border-b border-slate-700">
  <div class="max-w-7xl mx-auto px-4 py-4 flex flex-wrap items-center justify-between gap-3">
    <div>
      <p class="text-xs uppercase tracking-widest text-slate-300">Admin Workspace</p>
      <h1 class="font-semibold text-xl">RGC Control Center</h1>
      <p class="text-xs text-slate-300">Manage pages, content, users, events, and system controls from one place.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2 text-sm">
      <div class="relative">
        <button id="admAccountBtn" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/20">
          <?php if (!empty($u['avatar'])): ?>
            <img src="<?php echo htmlspecialchars($u['avatar']); ?>" alt="Avatar" class="w-6 h-6 rounded-full object-cover border border-white/30">
          <?php else: ?>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          <?php endif; ?>
          <span><?php echo htmlspecialchars($u['name'] ?? ''); ?> | <?php echo htmlspecialchars($role); ?></span>
          <svg class="w-4 h-4 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="admAccountMenu" class="absolute right-0 mt-2 w-48 rounded-xl border border-white/20 bg-white/10 backdrop-blur-xl text-white shadow-xl hidden">
          <a href="<?php echo rgcUrl('admin/profile.php'); ?>" class="block px-3 py-2 hover:bg-white/20">Edit Profile</a>
          <a href="<?php echo rgcUrl('admin/view_site.php'); ?>" class="block px-3 py-2 hover:bg-white/20">View Site</a>
          <a href="<?php echo rgcUrl('admin/logout.php'); ?>" class="block px-3 py-2 hover:bg-white/20">Logout</a>
        </div>
      </div>
      <a href="<?php echo rgcUrl('admin/profile.php'); ?>" class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white">My Profile</a>
      <a href="<?php echo rgcUrl('admin/view_site.php'); ?>" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white">View Site</a>
      <a href="<?php echo rgcUrl('admin/logout.php'); ?>" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white">Logout</a>
    </div>
  </div>
</header>

<nav class="bg-slate-100 border-b border-slate-200 sticky top-0 lg:relative z-30">
  <div class="max-w-7xl mx-auto px-4 py-2 flex items-center justify-between lg:hidden">
    <span class="text-sm font-semibold text-slate-600 uppercase tracking-wider">Navigation</span>
    <button onclick="toggleAdminNav()" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 shadow-sm">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
  </div>
  <div id="adminNavLinks" class="hidden lg:flex max-w-7xl mx-auto px-4 py-3 flex-wrap gap-2 text-sm">
    <a href="<?php echo rgcUrl('admin/dashboard.php'); ?>" class="<?php echo rgcNavClasses('/admin/dashboard.php', $currentPath); ?>">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      Dashboard
    </a>
    <a href="<?php echo rgcUrl('admin/sermons.php'); ?>" class="<?php echo rgcNavClasses('/admin/sermons.php', $currentPath); ?>">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
      Sermons
    </a>
    <a href="<?php echo rgcUrl('admin/events.php'); ?>" class="<?php echo rgcNavClasses('/admin/events.php', $currentPath); ?>">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      Events + Posters
    </a>
    <a href="<?php echo rgcUrl('admin/content.php'); ?>" class="<?php echo rgcNavClasses('/admin/content.php', $currentPath); ?>">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
      Site Content
    </a>
    <a href="<?php echo rgcUrl('admin/projects.php'); ?>" class="<?php echo rgcNavClasses('/admin/projects.php', $currentPath); ?>">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
      Projects
    </a>
    <a href="<?php echo rgcUrl('admin/ministries.php'); ?>" class="<?php echo rgcNavClasses('/admin/ministries.php', $currentPath); ?>">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
      Ministries
    </a>
    <a href="<?php echo rgcUrl('admin/slider.php'); ?>" class="<?php echo rgcNavClasses('/admin/slider.php', $currentPath); ?>">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
      Slider
    </a>
    <a href="<?php echo rgcUrl('admin/footer.php'); ?>" class="<?php echo rgcNavClasses('/admin/footer.php', $currentPath); ?>">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
      Footer
    </a>
    <a href="<?php echo rgcUrl('admin/prayer_requests.php'); ?>" class="<?php echo rgcNavClasses('/admin/prayer_requests.php', $currentPath); ?>">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4v-4z"/></svg>
      Prayer Inbox
    </a>
    <a href="<?php echo rgcUrl('admin/blog.php'); ?>" class="<?php echo rgcNavClasses('/admin/blog.php', $currentPath); ?>">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21H8a2 2 0 01-2-2V5a2 2 0 012-2h11a2 2 0 012 2v14a2 2 0 01-2 2zM16 3v4h4M9 9h6M9 13h6M9 17h6"/></svg>
      Blog
    </a>
    <a href="<?php echo rgcUrl('admin/comments.php'); ?>" class="<?php echo rgcNavClasses('/admin/comments.php', $currentPath); ?>">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
      Comments
    </a>
    <a href="<?php echo rgcUrl('admin/donations.php'); ?>" class="<?php echo rgcNavClasses('/admin/donations.php', $currentPath); ?>">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-3.314 0-6 1.343-6 3s2.686 3 6 3 6 1.343 6 3-2.686 3-6 3-6-1.343-6-3m6-11v14"/></svg>
      Donations
    </a>
    <a href="<?php echo rgcUrl('admin/profile.php'); ?>" class="<?php echo rgcNavClasses('/admin/profile.php', $currentPath); ?>">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A7 7 0 0112 14a7 7 0 016.879 3.804M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      My Profile
    </a>
    <?php if ($role === 'super_admin'): ?>
    <a href="<?php echo rgcUrl('admin/users.php'); ?>" class="<?php echo rgcNavClasses('/admin/users.php', $currentPath); ?>">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.126-1.278-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.126-1.278.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Users
    </a>
    <a href="<?php echo rgcUrl('admin/public_users.php'); ?>" class="<?php echo rgcNavClasses('/admin/public_users.php', $currentPath); ?>">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20h10M6 8a4 4 0 118 0 4 4 0 01-8 0zm-4 12a6 6 0 1112 0H2zM18 8a3 3 0 100 6 3 3 0 000-6zm4 12a4 4 0 10-8 0"/></svg>
      Public Users
    </a>
    <a href="<?php echo rgcUrl('admin/settings.php'); ?>" class="<?php echo rgcNavClasses('/admin/settings.php', $currentPath); ?>">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317a1 1 0 011.35-.936l1.486.593a1 1 0 00.839 0l1.486-.593a1 1 0 011.35.936l.129 1.598a1 1 0 00.594.84l1.458.657a1 1 0 01.59 1.247l-.46 1.537a1 1 0 000 .84l.46 1.537a1 1 0 01-.59 1.247l-1.458.657a1 1 0 00-.594.84l-.129 1.598a1 1 0 01-1.35.936l-1.486-.593a1 1 0 00-.839 0l-1.486.593a1 1 0 01-1.35-.936l-.129-1.598a1 1 0 00-.594-.84l-1.458-.657a1 1 0 01-.59-1.247l.46-1.537a1 1 0 000-.84l-.46-1.537a1 1 0 01.59-1.247l1.458-.657a1 1 0 00.594-.84l.129-1.598z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Settings
    </a>
    <a href="<?php echo rgcUrl('admin/security_logs.php'); ?>" class="<?php echo rgcNavClasses('/admin/security_logs.php', $currentPath); ?>">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586A1 1 0 0113.293 3.293l4.414 4.414A1 1 0 0118 8.414V19a2 2 0 01-2 2z"/></svg>
      Security Logs
    </a>
    <?php endif; ?>
  </div>
</nav>
<script>
  function toggleAdminNav() {
    const nav = document.getElementById('adminNavLinks');
    nav.classList.toggle('hidden');
    nav.classList.toggle('flex');
    nav.classList.toggle('flex-col');
  }
  (function(){
    const btn = document.getElementById('admAccountBtn');
    const menu = document.getElementById('admAccountMenu');
    if (!btn || !menu) return;
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      menu.classList.toggle('hidden');
    });
    document.addEventListener('click', (e) => {
      if (!btn.contains(e.target) && !menu.contains(e.target)) {
        menu.classList.add('hidden');
      }
    });
  })();
</script>
