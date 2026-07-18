<?php
require __DIR__ . '/includes/auth.php';
requireLogin();

$projects = rgcLoadJson('projects.json', []);
$error = '';
$success = '';

// Available icons
$iconOptions = [
    'food' => 'Food/Community',
    'building' => 'Building',
    'graduation' => 'Graduation/Education',
    'users' => 'People/Users',
    'heart' => 'Heart/Health',
    'home' => 'Home/Family'
];

// Handle file upload
function handleImageUpload($fileKey, $uploadDir = 'assets/uploads/') {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $file = $_FILES[$fileKey];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['error' => 'Invalid file type. Only JPG, PNG, GIF, WebP allowed.'];
    }
    
    $fullDir = __DIR__ . '/../' . $uploadDir;
    if (!is_dir($fullDir)) {
        mkdir($fullDir, 0755, true);
    }
    
    // Use optimization helper
    $optimizedPath = rgcOptimizeImage($file['tmp_name'], $fullDir, 'project');
    if ($optimizedPath) {
        return $optimizedPath;
    }
    
    return ['error' => 'Failed to process and optimize image.'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    rgcRequireCsrf('admin_projects');
    $action = $_POST['action'] ?? 'add';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $projects = array_values(array_filter($projects, static fn($p) => (int)($p['id'] ?? 0) !== $id));
        rgcSaveJson('projects.json', $projects);
        header('Location: ' . rgcUrl('admin/projects.php?saved=1'));
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? 'heart');
    $status = $_POST['status'] ?? 'active';
    $goal = trim($_POST['goal'] ?? '');
    $featured = isset($_POST['featured']);
    $existingImage = trim($_POST['existing_image'] ?? '');
    $existingImage2 = trim($_POST['existing_image2'] ?? '');
    
    // Handle image upload
    $image = $existingImage;
    $uploadResult = handleImageUpload('image');
    if ($uploadResult && !is_array($uploadResult)) {
        $image = '/' . $uploadResult;
    } elseif (isset($uploadResult['error'])) {
        $error = $uploadResult['error'];
    }
    
    // Handle second image upload
    $image2 = $existingImage2;
    $uploadResult2 = handleImageUpload('image2');
    if ($uploadResult2 && !is_array($uploadResult2)) {
        $image2 = '/' . $uploadResult2;
    } elseif (isset($uploadResult2['error'])) {
        $error = $uploadResult2['error'];
    }

    if ($title === '') {
        $error = 'Project title is required.';
    } else {
        $projects[] = [
            'id' => rgcNextId($projects),
            'title' => $title,
            'description' => $description,
            'icon' => $icon,
            'image' => $image,
            'image2' => $image2,
            'status' => $status,
            'goal' => $goal,
            'featured' => $featured
        ];
        rgcSaveJson('projects.json', $projects);
        header('Location: ' . rgcUrl('admin/projects.php?saved=1'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Projects</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <section class="bg-gradient-to-r from-amber-700 via-amber-600 to-yellow-600 text-white rounded-2xl p-6 shadow-lg">
      <h1 class="text-2xl font-bold">Projects Manager</h1>
      <p class="mt-2 text-amber-100">Manage church projects, building initiatives, and community programs.</p>
    </section>

    <?php if (isset($_GET['saved'])): ?>
    <p class="mb-3 text-sm rounded bg-emerald-50 text-emerald-700 px-3 py-2">Changes saved successfully.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <p class="mb-3 text-sm rounded bg-red-50 text-red-700 px-3 py-2"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <div class="grid xl:grid-cols-5 gap-6">
      <!-- Add Project Form -->
      <article class="bg-white rounded-xl shadow border border-slate-200 p-6 xl:col-span-2">
        <h2 class="text-lg font-bold text-slate-900">Add New Project</h2>
        <p class="text-sm text-slate-500 mt-1 mb-4">Create a new project with details and image.</p>

        <form method="post" class="space-y-3" enctype="multipart/form-data">
          <?php echo rgcCsrfField('admin_projects'); ?>
          <input type="hidden" name="action" value="add">
          
          <div>
            <label class="text-sm font-medium text-slate-700">Project Title</label>
            <input name="title" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="e.g., Church Building Project" required>
          </div>
          
          <div>
            <label class="text-sm font-medium text-slate-700">Description</label>
            <textarea name="description" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" rows="3" placeholder="Describe the project..."></textarea>
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
              <label class="text-sm font-medium text-slate-700">Status</label>
              <select name="status" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
                <option value="active">Active</option>
                <option value="ongoing">Ongoing</option>
                <option value="completed">Completed</option>
              </select>
            </div>
          </div>
          
          <div>
            <label class="text-sm font-medium text-slate-700">Project Image</label>
            <input type="file" name="image" accept="image/*" class="mt-1 block w-full cursor-pointer rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-slate-400 hover:bg-white hover:file:bg-slate-800">
            <p class="text-xs text-slate-500 mt-1">Upload project image (JPG, PNG, GIF, WebP)</p>
            <input type="hidden" name="existing_image" value="">
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Second Image (optional)</label>
            <input type="file" name="image2" accept="image/*" class="mt-1 block w-full cursor-pointer rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-slate-400 hover:bg-white hover:file:bg-slate-800">
            <p class="text-xs text-slate-500 mt-1">Upload a second image for the gallery</p>
            <input type="hidden" name="existing_image2" value="">
          </div>
          
          <div>
            <label class="text-sm font-medium text-slate-700">Fundraising Goal (KES)</label>
            <input name="goal" type="number" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="50000000">
          </div>
          
          <label class="text-sm rounded-lg border border-slate-200 px-3 py-2 bg-slate-50 block">
            <input type="checkbox" name="featured" class="mr-2"> Feature on homepage
          </label>
          
          <button class="px-5 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Add Project</button>
        </form>
      </article>

      <!-- Projects List -->
      <article class="bg-white rounded-xl shadow border border-slate-200 p-6 xl:col-span-3">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-bold text-slate-900">Current Projects</h2>
          <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600"><?php echo count($projects); ?> total</span>
        </div>

        <div class="space-y-3 max-h-[600px] overflow-y-auto pr-1">
          <?php foreach (array_reverse($projects) as $p): ?>
          <article class="border border-slate-200 rounded-xl p-4">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-lg bg-brand-100 flex items-center justify-center shrink-0">
                <?php 
                $iconMap = [
                    'food' => '<svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
                    'building' => '<svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
                    'graduation' => '<svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.4-3.2M12 14l-6.4-3.2M12 14v7"/></svg>',
                    'users' => '<svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
                    'heart' => '<svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
                    'home' => '<svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>'
                ];
                echo $iconMap[$p['icon'] ?? 'heart'] ?? $iconMap['heart'];
                ?>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($p['title'] ?? ''); ?></p>
                  <?php if (!empty($p['featured'])): ?>
                  <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Featured</span>
                  <?php endif; ?>
                </div>
                <p class="text-sm text-slate-600 mt-1 line-clamp-2"><?php echo htmlspecialchars($p['description'] ?? ''); ?></p>
                <div class="flex items-center gap-3 mt-2">
                  <span class="text-xs px-2 py-0.5 rounded-full <?php echo ($p['status'] ?? '') === 'ongoing' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'; ?>">
                    <?php echo ucfirst($p['status'] ?? 'active'); ?>
                  </span>
                  <?php if (!empty($p['goal'])): ?>
                  <span class="text-xs text-slate-500">Goal: KES <?php echo number_format($p['goal']); ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <form method="post" onsubmit="return confirm('Delete this project?');">
                <?php echo rgcCsrfField('admin_projects'); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)($p['id'] ?? 0); ?>">
                <button class="px-3 py-1 text-sm rounded-lg bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100">Delete</button>
              </form>
            </div>
          </article>
          <?php endforeach; ?>
          <?php if (empty($projects)): ?>
          <p class="text-sm text-slate-500 text-center py-8">No projects yet. Add your first project!</p>
          <?php endif; ?>
        </div>
      </article>
    </div>
  </main>
</body>
</html>
