<?php
require __DIR__ . '/includes/auth.php';
requireLogin();

$ministries = rgcLoadJson('ministries.json', []);
$error = '';

// Available icons
$iconOptions = [
    'music' => 'Music/Worship',
    'users' => 'Users/People',
    'share' => 'Share/Evangelism',
    'user' => 'User/Men',
    'heart' => 'Heart/Women',
    'home' => 'Home/Children',
    'book' => 'Bible/Study',
    'globe' => 'Globe/Missions',
    'gift' => 'Gift/Giving',
    'light' => 'Light/Youth'
];

// Available colors
$colorOptions = [
    'brand' => 'Brand (Gold)',
    'purple' => 'Purple',
    'green' => 'Green',
    'blue' => 'Blue',
    'pink' => 'Pink',
    'yellow' => 'Yellow',
    'red' => 'Red',
    'indigo' => 'Indigo'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    rgcRequireCsrf('admin_ministries');
    $action = $_POST['action'] ?? 'add';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $ministries = array_values(array_filter($ministries, static fn($m) => (int)($m['id'] ?? 0) !== $id));
        rgcSaveJson('ministries.json', $ministries);
        header('Location: ' . rgcUrl('admin/ministries.php?saved=1'));
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon = $_POST['icon'] ?? 'users';
    $color = $_POST['color'] ?? 'brand';
    $featured = isset($_POST['featured']);

    if ($name === '') {
        $error = 'Ministry name is required.';
    } else {
        $ministries[] = [
            'id' => rgcNextId($ministries),
            'name' => $name,
            'description' => $description,
            'icon' => $icon,
            'color' => $color,
            'featured' => $featured
        ];
        rgcSaveJson('ministries.json', $ministries);
        header('Location: ' . rgcUrl('admin/ministries.php?saved=1'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Ministries</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <section class="bg-gradient-to-r from-purple-700 via-purple-600 to-indigo-600 text-white rounded-2xl p-6 shadow-lg">
      <h1 class="text-2xl font-bold">Ministries Manager</h1>
      <p class="mt-2 text-purple-100">Manage church ministries and their icons.</p>
    </section>

    <?php if (isset($_GET['saved'])): ?>
    <p class="mb-3 text-sm rounded bg-emerald-50 text-emerald-700 px-3 py-2">Changes saved successfully.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <p class="mb-3 text-sm rounded bg-red-50 text-red-700 px-3 py-2"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <div class="grid xl:grid-cols-5 gap-6">
      <!-- Add Ministry Form -->
      <article class="bg-white rounded-xl shadow border border-slate-200 p-6 xl:col-span-2">
        <h2 class="text-lg font-bold text-slate-900">Add New Ministry</h2>
        <p class="text-sm text-slate-500 mt-1 mb-4">Create a ministry with custom icon and color.</p>

        <form method="post" class="space-y-3">
          <?php echo rgcCsrfField('admin_ministries'); ?>
          <input type="hidden" name="action" value="add">
          
          <div>
            <label class="text-sm font-medium text-slate-700">Ministry Name</label>
            <input name="name" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="e.g., Youth Ministry" required>
          </div>
          
          <div>
            <label class="text-sm font-medium text-slate-700">Description</label>
            <textarea name="description" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" rows="3" placeholder="Describe the ministry..."></textarea>
          </div>
          
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-sm font-medium text-slate-700">Icon</label>
              <select name="icon" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
                <?php foreach ($iconOptions as $key => $label): ?>
                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">Color</label>
              <select name="color" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
                <?php foreach ($colorOptions as $key => $label): ?>
                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          
          <label class="text-sm rounded-lg border border-slate-200 px-3 py-2 bg-slate-50 block">
            <input type="checkbox" name="featured" class="mr-2"> Feature on homepage
          </label>
          
          <button class="px-5 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Add Ministry</button>
        </form>
      </article>

      <!-- Ministries List -->
      <article class="bg-white rounded-xl shadow border border-slate-200 p-6 xl:col-span-3">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-bold text-slate-900">Current Ministries</h2>
          <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600"><?php echo count($ministries); ?> total</span>
        </div>

        <div class="space-y-3 max-h-[600px] overflow-y-auto pr-1">
          <?php foreach (array_reverse($ministries) as $m): ?>
          <?php 
            $colorMap = [
                'brand' => 'bg-amber-500',
                'purple' => 'bg-purple-500',
                'green' => 'bg-green-500',
                'blue' => 'bg-blue-500',
                'pink' => 'bg-pink-500',
                'yellow' => 'bg-yellow-500',
                'red' => 'bg-red-500',
                'indigo' => 'bg-indigo-500'
            ];
            $colorClass = $colorMap[$m['color'] ?? 'brand'] ?? $colorMap['brand'];
          ?>
          <article class="border border-slate-200 rounded-xl p-4">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-lg <?php echo $colorClass; ?> flex items-center justify-center shrink-0">
                <?php 
                $iconMap = [
                    'music' => '<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>',
                    'users' => '<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
                    'share' => '<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>',
                    'user' => '<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
                    'heart' => '<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
                    'home' => '<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
                    'book' => '<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>',
                    'globe' => '<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>',
                    'gift' => '<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>',
                    'light' => '<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>'
                ];
                echo $iconMap[$m['icon'] ?? 'users'] ?? $iconMap['users'];
                ?>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($m['name'] ?? ''); ?></p>
                  <?php if (!empty($m['featured'])): ?>
                  <span class="text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">Featured</span>
                  <?php endif; ?>
                </div>
                <p class="text-sm text-slate-600 mt-1 line-clamp-2"><?php echo htmlspecialchars($m['description'] ?? ''); ?></p>
              </div>
              <form method="post" onsubmit="return confirm('Delete this ministry?');">
                <?php echo rgcCsrfField('admin_ministries'); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)($m['id'] ?? 0); ?>">
                <button class="px-3 py-1 text-sm rounded-lg bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100">Delete</button>
              </form>
            </div>
          </article>
          <?php endforeach; ?>
          <?php if (empty($ministries)): ?>
          <p class="text-sm text-slate-500 text-center py-8">No ministries yet. Add your first ministry!</p>
          <?php endif; ?>
        </div>
      </article>
    </div>
  </main>
</body>
</html>
