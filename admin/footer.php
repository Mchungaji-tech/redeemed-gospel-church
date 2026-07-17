<?php
require __DIR__ . '/includes/auth.php';
requireLogin();

$footer = rgcLoadJson('footer.json', []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    rgcRequireCsrf('admin_footer');
    
    $footer['church_name'] = trim($_POST['church_name'] ?? 'RGC Eldoret');
    $footer['tagline'] = trim($_POST['tagline'] ?? '');
    $footer['designer_credit'] = trim($_POST['designer_credit'] ?? '');
    
    // Service times
    $serviceDays = $_POST['service_day'] ?? [];
    $serviceTimes = $_POST['service_time'] ?? [];
    $services = [];
    foreach ($serviceDays as $i => $day) {
        $day = trim($day);
        $time = trim($serviceTimes[$i] ?? '');
        if ($day !== '' && $time !== '') {
            $services[] = ['day' => $day, 'time' => $time];
        }
    }
    $footer['service_times'] = $services;
    
    // Quick links
    $linkTexts = $_POST['link_text'] ?? [];
    $linkUrls = $_POST['link_url'] ?? [];
    $links = [];
    foreach ($linkTexts as $i => $text) {
        $text = trim($text);
        $url = trim($linkUrls[$i] ?? '');
        if ($text !== '' && $url !== '') {
            $links[] = ['text' => $text, 'url' => $url];
        }
    }
    $footer['quick_links'] = $links;
    
    // Contact
    $footer['contact']['address'] = trim($_POST['address'] ?? '');
    $footer['contact']['email'] = trim($_POST['email'] ?? '');
    $footer['contact']['phone'] = trim($_POST['phone'] ?? '');
    $footer['contact']['whatsapp'] = trim($_POST['whatsapp'] ?? '');
    
    rgcSaveJson('footer.json', $footer);
    header('Location: ' . rgcUrl('admin/footer.php?saved=1'));
    exit;
}

// Get current counts
$serviceCount = count($footer['service_times'] ?? []);
$linkCount = count($footer['quick_links'] ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Footer</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-4xl mx-auto px-4 py-8 space-y-6">
    <section class="bg-gradient-to-r from-slate-700 via-slate-600 to-slate-800 text-white rounded-2xl p-6 shadow-lg">
      <h1 class="text-2xl font-bold">Footer Manager</h1>
      <p class="mt-2 text-slate-200">Manage footer content, service times, and contact info.</p>
    </section>

    <?php if (isset($_GET['saved'])): ?>
    <p class="mb-3 text-sm rounded bg-emerald-50 text-emerald-700 px-3 py-2">Footer saved successfully.</p>
    <?php endif; ?>

    <article class="bg-white rounded-xl shadow border border-slate-200 p-6">
      <h2 class="text-lg font-bold text-slate-900 mb-4">Footer Settings</h2>

      <form method="post" class="space-y-6">
        <?php echo rgcCsrfField('admin_footer'); ?>
        
        <!-- Church Info -->
        <div class="border-b border-slate-200 pb-6">
          <h3 class="font-semibold text-slate-800 mb-3">Church Information</h3>
          <div class="grid gap-4">
            <div>
              <label class="text-sm font-medium text-slate-700">Church Name</label>
              <input name="church_name" value="<?php echo htmlspecialchars($footer['church_name'] ?? 'RGC Eldoret'); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">Tagline</label>
              <textarea name="tagline" rows="2" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2"><?php echo htmlspecialchars($footer['tagline'] ?? ''); ?></textarea>
            </div>
          </div>
        </div>

        <!-- Service Times -->
        <div class="border-b border-slate-200 pb-6">
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-slate-800">Service Times</h3>
            <button type="button" onclick="addServiceRow()" class="text-xs px-2 py-1 bg-slate-100 text-slate-600 rounded hover:bg-slate-200">+ Add Service</button>
          </div>
          <div id="serviceRows" class="space-y-3">
            <?php foreach (($footer['service_times'] ?? []) as $i => $service): ?>
            <div class="grid grid-cols-[1fr_1fr_auto] gap-3 items-center service-row">
              <input name="service_day[]" value="<?php echo htmlspecialchars($service['day'] ?? ''); ?>" class="border border-slate-300 rounded-lg px-3 py-2" placeholder="Day (e.g., Sunday Service)">
              <input name="service_time[]" value="<?php echo htmlspecialchars($service['time'] ?? ''); ?>" class="border border-slate-300 rounded-lg px-3 py-2" placeholder="Time (e.g., 9:00 AM)">
              <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">&times;</button>
            </div>
            <?php endforeach; ?>
            <?php if (empty($footer['service_times'])): ?>
            <div class="grid grid-cols-[1fr_1fr_auto] gap-3 items-center service-row">
              <input name="service_day[]" value="" class="border border-slate-300 rounded-lg px-3 py-2" placeholder="Day (e.g., Sunday Service)">
              <input name="service_time[]" value="" class="border border-slate-300 rounded-lg px-3 py-2" placeholder="Time (e.g., 9:00 AM)">
              <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">&times;</button>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="border-b border-slate-200 pb-6">
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-slate-800">Quick Links</h3>
            <button type="button" onclick="addLinkRow()" class="text-xs px-2 py-1 bg-slate-100 text-slate-600 rounded hover:bg-slate-200">+ Add Link</button>
          </div>
          <div id="linkRows" class="space-y-3">
            <?php foreach (($footer['quick_links'] ?? []) as $i => $link): ?>
            <div class="grid grid-cols-[1fr_1fr_auto] gap-3 items-center link-row">
              <input name="link_text[]" value="<?php echo htmlspecialchars($link['text'] ?? ''); ?>" class="border border-slate-300 rounded-lg px-3 py-2" placeholder="Link Text">
              <input name="link_url[]" value="<?php echo htmlspecialchars($link['url'] ?? ''); ?>" class="border border-slate-300 rounded-lg px-3 py-2" placeholder="/page.php">
              <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">&times;</button>
            </div>
            <?php endforeach; ?>
            <?php if (empty($footer['quick_links'])): ?>
            <div class="grid grid-cols-[1fr_1fr_auto] gap-3 items-center link-row">
              <input name="link_text[]" value="" class="border border-slate-300 rounded-lg px-3 py-2" placeholder="Link Text">
              <input name="link_url[]" value="" class="border border-slate-300 rounded-lg px-3 py-2" placeholder="/page.php">
              <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">&times;</button>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Contact Info -->
        <div class="border-b border-slate-200 pb-6">
          <h3 class="font-semibold text-slate-800 mb-3">Contact Information</h3>
          <div class="grid gap-4">
            <div>
              <label class="text-sm font-medium text-slate-700">Address</label>
              <input name="address" value="<?php echo htmlspecialchars($footer['contact']['address'] ?? ''); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">Email</label>
              <input name="email" type="email" value="<?php echo htmlspecialchars($footer['contact']['email'] ?? ''); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">Phone</label>
              <input name="phone" value="<?php echo htmlspecialchars($footer['contact']['phone'] ?? ''); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">WhatsApp Number</label>
              <input name="whatsapp" value="<?php echo htmlspecialchars($footer['contact']['whatsapp'] ?? ''); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="+254700000000">
              <p class="text-xs text-slate-500 mt-1">Enter with country code (no + or spaces)</p>
            </div>
          </div>
        </div>

        <!-- Footer Credit -->
        <div>
          <h3 class="font-semibold text-slate-800 mb-3">Footer Credit</h3>
          <div>
            <label class="text-sm font-medium text-slate-700">Designer Credit</label>
            <input name="designer_credit" value="<?php echo htmlspecialchars($footer['designer_credit'] ?? ''); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
          </div>
        </div>

        <button class="px-5 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Save Footer</button>
      </form>
    </article>
  </main>

  <script>
    function addServiceRow() {
      const container = document.getElementById('serviceRows');
      const div = document.createElement('div');
      div.className = 'grid grid-cols-[1fr_1fr_auto] gap-3 items-center service-row';
      div.innerHTML = `
        <input name="service_day[]" class="border border-slate-300 rounded-lg px-3 py-2" placeholder="Day (e.g., Sunday Service)">
        <input name="service_time[]" class="border border-slate-300 rounded-lg px-3 py-2" placeholder="Time (e.g., 9:00 AM)">
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">&times;</button>
      `;
      container.appendChild(div);
    }

    function addLinkRow() {
      const container = document.getElementById('linkRows');
      const div = document.createElement('div');
      div.className = 'grid grid-cols-[1fr_1fr_auto] gap-3 items-center link-row';
      div.innerHTML = `
        <input name="link_text[]" class="border border-slate-300 rounded-lg px-3 py-2" placeholder="Link Text">
        <input name="link_url[]" class="border border-slate-300 rounded-lg px-3 py-2" placeholder="/page.php">
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">&times;</button>
      `;
      container.appendChild(div);
    }
  </script>
</body>
</html>
