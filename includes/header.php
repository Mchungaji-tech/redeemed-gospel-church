<?php
if (!isset($pageTitle)) { $pageTitle = "Redeemed Gospel Church"; }
$broadcast = rgcBroadcastMessage();
$cssPath = __DIR__ . '/../assets/css/custom.css';
$cssVersion = is_file($cssPath) ? (string) filemtime($cssPath) : '1';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$themePreview = strtolower(trim((string) ($_GET['theme'] ?? '')));
if (!in_array($themePreview, ['renewal'], true)) {
  $themePreview = '';
}
$headerFooterData = rgcLoadJson('footer.json', []);
$headerService = $headerFooterData['service_times'][0] ?? ['day' => 'Sunday Service', 'time' => '9:00 AM'];
$headerAddress = trim((string) ($headerFooterData['contact']['address'] ?? 'Eldoret, Kenya'));
$headerPhone = trim((string) ($headerFooterData['contact']['phone'] ?? ''));
$headerEmail = trim((string) ($headerFooterData['contact']['email'] ?? ''));
$pageName = basename($currentPath !== '' ? $currentPath : '/index.php');
$bodyClasses = ['bg-stone-50', 'text-slate-900', 'font-body', 'page-enter'];
if ($themePreview !== '') {
  $bodyClasses[] = 'theme-preview';
  $bodyClasses[] = 'theme-preview-' . $themePreview;
}
function navClass($path, $currentPath) {
  if ($path === '/index.php' && ($currentPath === '' || $currentPath === '/')) {
    return 'nav-link nav-link-active';
  }
  return str_ends_with($currentPath, $path) ? 'nav-link nav-link-active' : 'nav-link';
}
// Detect user country from IP
$userFlag = '';
$userCountryName = '';
$userIp = $_SERVER['REMOTE_ADDR'] ?? '';
// Always try to detect fresh (unless session has valid value)
if (!isset($_SESSION['detected_country']) || $_SESSION['detected_country'] === '') {
  $userCountryName = rgcGetCountryFromIP($userIp);
  $userCountryCode = rgcGetCountryCode($userCountryName);
  $userFlag = rgcCountryToFlag($userCountryCode);
  $_SESSION['detected_country'] = $userCountryName;
  $_SESSION['detected_flag'] = $userFlag;
} else {
  $userFlag = $_SESSION['detected_flag'] ?? '';
  $userCountryName = $_SESSION['detected_country'] ?? '';
}

// Check for live sermon
$liveSermon = null;
$liveSermons = rgcLoadJson('sermons.json', []);
foreach ($liveSermons as $s) {
  if (!empty($s['is_live'])) {
    $liveSermon = $s;
    break;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              50: '#f8f5ef',
              100: '#efe5d4',
              200: '#e4d1ac',
              300: '#d5b57a',
              400: '#c89a52',
              500: '#ba7f31',
              600: '#986326',
              700: '#74491d',
              800: '#432a18',
              900: '#141c2f'
            }
          },
          fontFamily: {
            'display': ['Playfair Display', 'Georgia', 'serif'],
            'body': ['Inter', '-apple-system', 'Segoe UI', 'sans-serif']
          }
        }
      }
    };
  </script>
  <link rel="stylesheet" href="<?php echo rgcAsset('assets/css/custom.css'); ?>?v=<?php echo urlencode($cssVersion); ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="<?php echo htmlspecialchars(implode(' ', $bodyClasses)); ?>" data-page="<?php echo htmlspecialchars($pageName); ?>">

<?php if ($broadcast): ?>
<div class="broadcast-bar text-white text-sm py-2 px-4 text-center relative overflow-hidden">
  <div class="absolute inset-0 bg-white/5"></div>
  <span class="relative font-medium tracking-[0.01em]"><?php echo htmlspecialchars($broadcast); ?></span>
</div>
<?php endif; ?>

<div class="header-utility">
  <div class="max-w-[1280px] mx-auto px-4 py-2 flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap items-center gap-x-5 gap-y-1 text-[0.78rem] text-slate-600">
      <span class="inline-flex items-center gap-2">
        <span class="header-utility__dot"></span>
        <?php echo htmlspecialchars(($headerService['day'] ?? 'Sunday Service') . ' ' . ($headerService['time'] ?? '9:00 AM')); ?>
      </span>
      <span><?php echo htmlspecialchars($headerAddress); ?></span>
      <?php if ($headerPhone !== ''): ?>
      <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^\d+]/', '', $headerPhone) ?? $headerPhone); ?>" class="hover:text-brand-700"><?php echo htmlspecialchars($headerPhone); ?></a>
      <?php endif; ?>
    </div>
    <div class="flex items-center gap-3 text-[0.78rem]">
      <?php if ($headerEmail !== ''): ?>
      <a href="mailto:<?php echo htmlspecialchars($headerEmail); ?>" class="text-slate-600 hover:text-brand-700"><?php echo htmlspecialchars($headerEmail); ?></a>
      <?php endif; ?>
      <?php if ($liveSermon): ?>
      <a href="<?php echo rgcUrl('sermons.php?id=' . (int)$liveSermon['id']); ?>" class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1 text-rose-700 border border-rose-200">
        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
        Live Now
      </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<header class="site-header">
  <div class="max-w-[1280px] mx-auto px-4 py-4 flex items-center justify-between gap-4">
    <a href="<?php echo rgcUrl('index.php'); ?>" class="site-brand group">
      <div class="site-brand__mark">
        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
        </svg>
      </div>
      <div class="site-brand__copy">
        <span class="block font-display font-semibold tracking-wide text-lg text-slate-900">Redeemed Gospel Church</span>
        <span class="block text-xs uppercase tracking-[0.22em] text-slate-500">Eldoret, Kenya</span>
      </div>
    </a>

    <nav class="nav-desktop hidden lg:flex items-center gap-1">
      <a href="<?php echo rgcUrl('index.php'); ?>" class="<?php echo navClass('/index.php', $currentPath); ?>">Home</a>
      <a href="<?php echo rgcUrl('about.php'); ?>" class="<?php echo navClass('/about.php', $currentPath); ?>">About</a>
      <a href="<?php echo rgcUrl('ministries.php'); ?>" class="<?php echo navClass('/ministries.php', $currentPath); ?>">Ministries</a>
      <a href="<?php echo rgcUrl('missions.php'); ?>" class="<?php echo navClass('/missions.php', $currentPath); ?>">Missions</a>
      <a href="<?php echo rgcUrl('projects.php'); ?>" class="<?php echo navClass('/projects.php', $currentPath); ?>">Projects</a>
      <a href="<?php echo rgcUrl('events.php'); ?>" class="<?php echo navClass('/events.php', $currentPath); ?>">Events</a>
      <a href="<?php echo rgcUrl('sermons.php'); ?>" class="<?php echo navClass('/sermons.php', $currentPath); ?>">Sermons</a>
      <a href="<?php echo rgcUrl('blog.php'); ?>" class="<?php echo navClass('/blog.php', $currentPath); ?>">Blog</a>
    </nav>

    <div class="flex items-center gap-3">
      <?php if ($userFlag): ?>
      <span class="hidden xl:inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full bg-slate-100 border border-slate-200 text-sm text-slate-700" title="Your detected location">
        <span class="text-lg"><?php echo htmlspecialchars($userFlag); ?></span>
      </span>
      <?php endif; ?>
      <a href="<?php echo rgcUrl('contact.php'); ?>" class="hidden md:inline-flex btn btn-outline text-sm">Plan Your Visit</a>
      <a href="<?php echo rgcUrl('donate.php'); ?>" class="hidden md:inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-slate-900 text-white text-sm font-semibold hover:bg-brand-700 transition-all shadow-sm">
        Support the Mission
      </a>
      <?php $pu = rgcPublicUser(); ?>
      <div class="relative hidden md:block">
        <button id="accountBtn" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-slate-100 border border-slate-200 text-slate-700" aria-haspopup="menu" aria-expanded="false" aria-controls="accountMenu">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </button>
        <div id="accountMenu" class="absolute right-0 mt-2 w-52 rounded-[1.5rem] border border-slate-200 bg-white text-slate-700 shadow-xl hidden overflow-hidden" role="menu">
          <?php if ($pu): ?>
            <a href="<?php echo rgcUrl('user/dashboard.php'); ?>" class="block px-3 py-2 hover:bg-slate-50" role="menuitem">Dashboard</a>
            <a href="<?php echo rgcUrl('user/logout.php?r=' . urlencode($currentPath)); ?>" class="block px-3 py-2 hover:bg-slate-50" role="menuitem">Logout</a>
          <?php else: ?>
            <a href="<?php echo rgcUrl('user/login.php?r=' . urlencode($currentPath)); ?>" class="block px-3 py-2 hover:bg-slate-50" role="menuitem">Login</a>
            <a href="<?php echo rgcUrl('user/register.php?r=' . urlencode($currentPath)); ?>" class="block px-3 py-2 hover:bg-slate-50" role="menuitem">Register</a>
          <?php endif; ?>
        </div>
      </div>
      <button class="mobile-menu-btn" type="button" onclick="toggleMobileMenu()" aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>
    </div>
  </div>
</header>

<div class="mobile-menu" id="mobileMenu" aria-hidden="true">
  <button class="mobile-menu-close" type="button" onclick="toggleMobileMenu(false)" aria-label="Close menu">&times;</button>
  <div class="mobile-menu__intro">
    <span class="mobile-menu__eyebrow">Welcome Home</span>
    <p>Join us for worship, prayer, discipleship, and community in Eldoret.</p>
  </div>
  <div class="mobile-menu__primary">
    <a href="<?php echo rgcUrl('index.php'); ?>" onclick="toggleMobileMenu()">Home</a>
    <a href="<?php echo rgcUrl('about.php'); ?>" onclick="toggleMobileMenu()">About</a>
    <a href="<?php echo rgcUrl('ministries.php'); ?>" onclick="toggleMobileMenu()">Ministries</a>
    <a href="<?php echo rgcUrl('sermons.php'); ?>" onclick="toggleMobileMenu()">Sermons</a>
    <a href="<?php echo rgcUrl('contact.php'); ?>" onclick="toggleMobileMenu()">Plan Your Visit</a>
  </div>

  <details class="mobile-menu__group">
    <summary>More Pages</summary>
    <div class="mobile-menu__group-links">
      <a href="<?php echo rgcUrl('missions.php'); ?>" onclick="toggleMobileMenu()">Missions</a>
      <a href="<?php echo rgcUrl('projects.php'); ?>" onclick="toggleMobileMenu()">Projects</a>
      <a href="<?php echo rgcUrl('events.php'); ?>" onclick="toggleMobileMenu()">Events</a>
      <a href="<?php echo rgcUrl('blog.php'); ?>" onclick="toggleMobileMenu()">Blog</a>
      <a href="<?php echo rgcUrl('contact.php'); ?>" onclick="toggleMobileMenu()">Prayer Request</a>
    </div>
  </details>

  <details class="mobile-menu__group">
    <summary><?php echo $pu ? 'My Account' : 'Login Or Register'; ?></summary>
    <div class="mobile-menu__group-links">
      <?php if ($pu): ?>
      <a href="<?php echo rgcUrl('user/dashboard.php'); ?>" onclick="toggleMobileMenu()">Dashboard</a>
      <a href="<?php echo rgcUrl('user/logout.php?r=' . urlencode($currentPath)); ?>" onclick="toggleMobileMenu()">Logout</a>
      <?php else: ?>
      <a href="<?php echo rgcUrl('user/login.php?r=' . urlencode($currentPath)); ?>" onclick="toggleMobileMenu()">Login</a>
      <a href="<?php echo rgcUrl('user/register.php?r=' . urlencode($currentPath)); ?>" onclick="toggleMobileMenu()">Register</a>
      <?php endif; ?>
    </div>
  </details>

  <div class="mobile-menu__actions">
    <a href="<?php echo rgcUrl('donate.php'); ?>" onclick="toggleMobileMenu()" class="mobile-donate">Support the Mission</a>
    <?php if ($liveSermon): ?>
    <a href="<?php echo rgcUrl('sermons.php?id=' . (int) $liveSermon['id']); ?>" onclick="toggleMobileMenu()" class="mobile-live-link">Live Now</a>
    <?php endif; ?>
  </div>
</div>

<script>
function setMobileMenuState(forceOpen) {
  const menu = document.getElementById('mobileMenu');
  const menuBtn = document.querySelector('.mobile-menu-btn');
  const lightbox = document.getElementById('galleryLightbox');
  if (!menu || !menuBtn) {
    return;
  }
  
  if (lightbox && lightbox.classList.contains('is-open')) {
    lightbox.classList.remove('is-open');
    lightbox.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('lightbox-open');
    lightbox.style.display = '';
  }
  
  const isActive = typeof forceOpen === 'boolean' ? forceOpen : !menu.classList.contains('active');
  menu.classList.toggle('active', isActive);
  menu.setAttribute('aria-hidden', isActive ? 'false' : 'true');
  menuBtn.setAttribute('aria-expanded', isActive ? 'true' : 'false');
  
  if (isActive) {
    document.body.classList.add('mobile-menu-open');
  } else {
    document.body.classList.remove('mobile-menu-open');
  }
}

function toggleMobileMenu(forceOpen) {
  setMobileMenuState(forceOpen);
}

const accountBtn = document.getElementById('accountBtn');
const accountMenu = document.getElementById('accountMenu');
if (accountBtn && accountMenu) {
  accountBtn.addEventListener('click', () => {
    const isOpen = accountMenu.classList.toggle('hidden') === false;
    accountBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });
  document.addEventListener('click', (e) => {
    if (!accountBtn.contains(e.target) && !accountMenu.contains(e.target)) {
      accountMenu.classList.add('hidden');
      accountBtn.setAttribute('aria-expanded', 'false');
    }
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      accountMenu.classList.add('hidden');
      accountBtn.setAttribute('aria-expanded', 'false');
      setMobileMenuState(false);
    }
  });
}
</script>

<main>
