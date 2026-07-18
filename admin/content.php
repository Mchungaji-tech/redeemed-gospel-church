<?php
require __DIR__ . '/includes/auth.php';
requireLogin();

$ministries = rgcLoadJson('ministries.json', []);
$projects = rgcLoadJson('projects.json', []);
$testimonials = rgcLoadJson('testimonials.json', []);
$gallery = rgcLoadJson('gallery.json', []);
$error = '';
$bishopImageMarker = '__BISHOP_IMAGE__';
$bishopTestimonialMarker = '__BISHOP_QUOTE__|';
$homepageSpotlight = array_merge([
  'eyebrow' => 'Bishop Spotlight',
  'quote' => '',
  'author' => 'Bishop',
  'role' => 'Lead Bishop',
  'cta_text' => 'Meet Our Leadership',
  'cta_link' => 'about.php',
], rgcLoadJson('homepage_spotlight.json', []));

function rgcNormalizeIds(array $rows): array {
  $next = 1;
  foreach ($rows as &$row) {
    if (!isset($row['id']) || (int)$row['id'] <= 0) {
      $row['id'] = $next++;
    } else {
      $next = max($next, ((int)$row['id']) + 1);
    }
  }
  unset($row);
  return $rows;
}

function rgcFindIndexById(array $rows, int $id): int {
  foreach ($rows as $index => $row) {
    if ((int)($row['id'] ?? 0) === $id) return $index;
  }
  return -1;
}

function rgcUploadImageFromRequest(string $fieldName, string &$error, string $prefix = 'gallery_'): string {
  $file = $_FILES[$fieldName] ?? null;
  if (!$file || empty($file['name'])) {
    return '';
  }
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $error = 'Image upload failed. Please try again.';
    return '';
  }
  $tmp = $file['tmp_name'] ?? '';
  $original = strtolower((string)($file['name'] ?? ''));
  $ext = pathinfo($original, PATHINFO_EXTENSION);
  $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
  if (!in_array($ext, $allowed, true)) {
    $error = 'Invalid image type. Use jpg, png, webp, or gif.';
    return '';
  }
  $uploadDir = dirname(__DIR__) . '/assets/uploads';
  if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
  }
  
  // Use optimization helper
  $optimizedPath = rgcOptimizeImage($tmp, $uploadDir, $prefix);
  if ($optimizedPath) {
    return '/' . $optimizedPath;
  }
  
  $error = 'Image optimization failed.';
  return '';
}

$ministries = rgcNormalizeIds($ministries);
$projects = rgcNormalizeIds($projects);
$testimonials = rgcNormalizeIds($testimonials);
$gallery = rgcNormalizeIds($gallery);
rgcSaveJson('ministries.json', $ministries);
rgcSaveJson('projects.json', $projects);
rgcSaveJson('testimonials.json', $testimonials);
rgcSaveJson('gallery.json', $gallery);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('admin_content');
  $type = $_POST['type'] ?? '';
  $action = $_POST['action'] ?? '';
  $id = (int)($_POST['id'] ?? 0);

  if ($type === 'ministry') {
    if ($action === 'add') {
      $name = trim($_POST['name'] ?? '');
      $description = trim($_POST['description'] ?? '');
      if ($name !== '') {
        $ministries[] = ['id' => rgcNextId($ministries), 'name' => $name, 'description' => $description];
        rgcSaveJson('ministries.json', $ministries);
      }
    } elseif ($action === 'update') {
      $index = rgcFindIndexById($ministries, $id);
      if ($index >= 0) {
        $ministries[$index]['name'] = trim($_POST['name'] ?? '');
        $ministries[$index]['description'] = trim($_POST['description'] ?? '');
        rgcSaveJson('ministries.json', $ministries);
      }
    } elseif ($action === 'delete') {
      $ministries = array_values(array_filter($ministries, static fn($row) => (int)($row['id'] ?? 0) !== $id));
      rgcSaveJson('ministries.json', $ministries);
    }
  }

  if ($type === 'project') {
    if ($action === 'add') {
      $title = trim($_POST['title'] ?? '');
      $description = trim($_POST['description'] ?? '');
      if ($title !== '') {
        $projects[] = ['id' => rgcNextId($projects), 'title' => $title, 'description' => $description];
        rgcSaveJson('projects.json', $projects);
      }
    } elseif ($action === 'update') {
      $index = rgcFindIndexById($projects, $id);
      if ($index >= 0) {
        $projects[$index]['title'] = trim($_POST['title'] ?? '');
        $projects[$index]['description'] = trim($_POST['description'] ?? '');
        rgcSaveJson('projects.json', $projects);
      }
    } elseif ($action === 'delete') {
      $projects = array_values(array_filter($projects, static fn($row) => (int)($row['id'] ?? 0) !== $id));
      rgcSaveJson('projects.json', $projects);
    }
  }

  if ($type === 'testimonial') {
    if ($action === 'add') {
      $name = trim($_POST['name'] ?? '');
      $message = trim($_POST['message'] ?? '');
      if ($name !== '' && $message !== '') {
        $testimonials[] = ['id' => rgcNextId($testimonials), 'name' => $name, 'message' => $message];
        rgcSaveJson('testimonials.json', $testimonials);
      }
    } elseif ($action === 'update') {
      $index = rgcFindIndexById($testimonials, $id);
      if ($index >= 0) {
        $testimonials[$index]['name'] = trim($_POST['name'] ?? '');
        $testimonials[$index]['message'] = trim($_POST['message'] ?? '');
        rgcSaveJson('testimonials.json', $testimonials);
      }
    } elseif ($action === 'delete') {
      $testimonials = array_values(array_filter($testimonials, static fn($row) => (int)($row['id'] ?? 0) !== $id));
      rgcSaveJson('testimonials.json', $testimonials);
    }
  }

  if ($type === 'gallery') {
    if ($action === 'add') {
      $image = '';
      $caption = trim($_POST['caption'] ?? '');
      $image = rgcUploadImageFromRequest('image_file', $error, 'gallery_');

      if ($error === '' && $image !== '') {
        $gallery[] = ['id' => rgcNextId($gallery), 'image' => $image, 'caption' => $caption];
        rgcSaveJson('gallery.json', $gallery);
      } elseif ($error === '') {
        $error = 'Please choose an image file to upload.';
      }
    } elseif ($action === 'update') {
      $index = rgcFindIndexById($gallery, $id);
      if ($index >= 0) {
        $gallery[$index]['caption'] = trim($_POST['caption'] ?? '');
        $uploaded = rgcUploadImageFromRequest('image_file', $error, 'gallery_');
        if ($uploaded !== '') {
          $gallery[$index]['image'] = $uploaded;
        }
        rgcSaveJson('gallery.json', $gallery);
      }
    } elseif ($action === 'delete') {
      $gallery = array_values(array_filter($gallery, static fn($row) => (int)($row['id'] ?? 0) !== $id));
      rgcSaveJson('gallery.json', $gallery);
    }
  }

  if ($type === 'bishop') {
    if ($action === 'save') {
      $bishopEyebrow = trim($_POST['bishop_eyebrow'] ?? 'Bishop Spotlight');
      $bishopQuote = trim($_POST['bishop_quote'] ?? '');
      $bishopAuthor = trim($_POST['bishop_author'] ?? 'Bishop');
      $bishopRole = trim($_POST['bishop_role'] ?? 'Lead Bishop');
      $bishopCtaText = trim($_POST['bishop_cta_text'] ?? 'Meet Our Leadership');
      $bishopCtaLink = trim($_POST['bishop_cta_link'] ?? 'about.php');
      if ($bishopAuthor === '') {
        $bishopAuthor = 'Bishop';
      }
      if ($bishopEyebrow === '') {
        $bishopEyebrow = 'Bishop Spotlight';
      }
      if ($bishopRole === '') {
        $bishopRole = 'Lead Bishop';
      }
      if ($bishopCtaText === '') {
        $bishopCtaText = 'Meet Our Leadership';
      }
      if ($bishopCtaLink === '') {
        $bishopCtaLink = 'about.php';
      }

      $uploaded = rgcUploadImageFromRequest('bishop_image_file', $error, 'bishop_');
      $imgIndex = -1;
      foreach ($gallery as $i => $row) {
        if (($row['caption'] ?? '') === $bishopImageMarker) {
          $imgIndex = $i;
          break;
        }
      }
      if ($imgIndex === -1 && $uploaded !== '') {
        $gallery[] = ['id' => rgcNextId($gallery), 'image' => $uploaded, 'caption' => $bishopImageMarker];
      } elseif ($imgIndex >= 0 && $uploaded !== '') {
        $gallery[$imgIndex]['image'] = $uploaded;
      }
      rgcSaveJson('gallery.json', $gallery);
      $homepageSpotlight = [
        'eyebrow' => $bishopEyebrow,
        'quote' => $bishopQuote,
        'author' => $bishopAuthor,
        'role' => $bishopRole,
        'cta_text' => $bishopCtaText,
        'cta_link' => $bishopCtaLink,
      ];
      rgcSaveJson('homepage_spotlight.json', $homepageSpotlight);

      $tIndex = -1;
      foreach ($testimonials as $i => $row) {
        if (str_starts_with((string)($row['name'] ?? ''), $bishopTestimonialMarker)) {
          $tIndex = $i;
          break;
        }
      }
      $storedName = $bishopTestimonialMarker . $bishopAuthor;
      if ($bishopQuote !== '') {
        if ($tIndex >= 0) {
          $testimonials[$tIndex]['name'] = $storedName;
          $testimonials[$tIndex]['message'] = $bishopQuote;
        } else {
          $testimonials[] = [
            'id' => rgcNextId($testimonials),
            'name' => $storedName,
            'message' => $bishopQuote,
          ];
        }
        rgcSaveJson('testimonials.json', $testimonials);
      }
    }
  }

  if ($error === '') {
    header('Location: ' . rgcUrl('admin/content.php?saved=1'));
    exit;
  }
}

$bishopImage = '';
$bishopQuote = (string) ($homepageSpotlight['quote'] ?? '');
$bishopAuthor = trim((string) ($homepageSpotlight['author'] ?? 'Bishop')) ?: 'Bishop';
$bishopEyebrow = trim((string) ($homepageSpotlight['eyebrow'] ?? 'Bishop Spotlight')) ?: 'Bishop Spotlight';
$bishopRole = trim((string) ($homepageSpotlight['role'] ?? 'Lead Bishop')) ?: 'Lead Bishop';
$bishopCtaText = trim((string) ($homepageSpotlight['cta_text'] ?? 'Meet Our Leadership')) ?: 'Meet Our Leadership';
$bishopCtaLink = trim((string) ($homepageSpotlight['cta_link'] ?? 'about.php')) ?: 'about.php';
foreach ($gallery as $row) {
  if (($row['caption'] ?? '') === $bishopImageMarker) {
    $bishopImage = (string)($row['image'] ?? '');
    break;
  }
}
foreach ($testimonials as $row) {
  $nameValue = (string)($row['name'] ?? '');
  if (str_starts_with($nameValue, $bishopTestimonialMarker)) {
    if ($bishopQuote === '') {
      $bishopQuote = (string)($row['message'] ?? '');
    }
    if (($homepageSpotlight['author'] ?? '') === '') {
      $bishopAuthor = trim(substr($nameValue, strlen($bishopTestimonialMarker)));
      if ($bishopAuthor === '') {
        $bishopAuthor = 'Bishop';
      }
    }
    break;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Site Content</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>
  <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <section class="rounded-2xl bg-gradient-to-r from-violet-700 via-fuchsia-600 to-pink-600 text-white p-6 shadow-lg">
      <h1 class="text-2xl font-bold">Site Content Manager</h1>
      <p class="text-fuchsia-100 mt-2">Add, edit, and delete ministries, projects, testimonials, gallery assets, and the Bishop section.</p>
    </section>

    <section class="bg-white rounded-xl shadow border border-slate-200 p-6">
      <h1 class="text-xl font-bold">Content Workspace</h1>
      <p class="text-sm text-slate-500 mt-1">Upload images directly from your device, including the homepage Bishop spotlight.</p>
      <?php if (isset($_GET['saved'])): ?>
      <p class="mt-3 text-sm rounded bg-emerald-50 text-emerald-700 px-3 py-2">Content saved.</p>
      <?php endif; ?>
      <?php if ($error !== ''): ?>
      <p class="mt-3 text-sm rounded bg-red-50 text-red-700 px-3 py-2"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>
    </section>

    <section class="grid xl:grid-cols-2 gap-6">
      <div class="bg-white rounded-xl shadow border border-slate-200 p-6 xl:col-span-2">
        <h2 class="font-semibold text-slate-900 mb-3">Homepage Bishop Spotlight</h2>
        <p class="text-sm text-slate-500 mb-4">This controls the full-width bishop section with large photo, stronger quote styling, and the main button shown on the homepage.</p>
        <form method="post" enctype="multipart/form-data" class="space-y-3 rounded-lg border border-slate-200 p-4 bg-slate-50">
          <?php echo rgcCsrfField('admin_content'); ?>
          <input type="hidden" name="type" value="bishop">
          <input type="hidden" name="action" value="save">
          <label class="block text-sm font-medium text-slate-700">Section Label</label>
          <input name="bishop_eyebrow" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-fuchsia-300" value="<?php echo htmlspecialchars($bishopEyebrow); ?>" placeholder="Bishop Spotlight">
          <label class="block text-sm font-medium text-slate-700">Quote</label>
          <textarea name="bishop_quote" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-fuchsia-300" placeholder="Enter Bishop quote for homepage"><?php echo htmlspecialchars($bishopQuote); ?></textarea>
          <label class="block text-sm font-medium text-slate-700">Quoted by</label>
          <input name="bishop_author" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-fuchsia-300" value="<?php echo htmlspecialchars($bishopAuthor); ?>" placeholder="Bishop">
          <label class="block text-sm font-medium text-slate-700">Role / Title</label>
          <input name="bishop_role" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-fuchsia-300" value="<?php echo htmlspecialchars($bishopRole); ?>" placeholder="Lead Bishop">
          <div class="grid md:grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Primary Button Text</label>
              <input name="bishop_cta_text" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-fuchsia-300" value="<?php echo htmlspecialchars($bishopCtaText); ?>" placeholder="Meet Our Leadership">
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Primary Button Link</label>
              <input name="bishop_cta_link" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-fuchsia-300" value="<?php echo htmlspecialchars($bishopCtaLink); ?>" placeholder="about.php or https://...">
            </div>
          </div>
          <label class="block text-sm font-medium text-slate-700">Bishop Image Upload</label>
          <input name="bishop_image_file" type="file" accept="image/*" class="w-full cursor-pointer rounded-2xl border border-dashed border-slate-300 bg-white px-3 py-3 text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-slate-400 hover:file:bg-slate-800">
          <?php if ($bishopImage !== ''): ?>
          <div class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 bg-white">
            <img src="<?php echo htmlspecialchars($bishopImage); ?>" alt="Current Bishop image" class="w-20 h-20 rounded-lg object-cover border">
            <p class="text-sm text-slate-600">Current image is active on homepage. Upload a new file to replace it.</p>
          </div>
          <?php endif; ?>
          <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm">Save Bishop Section</button>
        </form>
      </div>

      <div class="bg-white rounded-xl shadow border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-900 mb-3">Ministries</h2>
        <form method="post" class="space-y-2 mb-4 rounded-lg border border-slate-200 p-3 bg-slate-50">
          <?php echo rgcCsrfField('admin_content'); ?>
          <input type="hidden" name="type" value="ministry">
          <input type="hidden" name="action" value="add">
          <input name="name" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-fuchsia-300" placeholder="Ministry name" required>
          <textarea name="description" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-fuchsia-300" rows="2" placeholder="Description"></textarea>
          <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm">Add Ministry</button>
        </form>
        <div class="space-y-2 max-h-[360px] overflow-y-auto">
          <?php foreach ($ministries as $m): ?>
          <form method="post" class="border rounded-lg p-3 space-y-2 bg-white">
            <?php echo rgcCsrfField('admin_content'); ?>
            <input type="hidden" name="type" value="ministry">
            <input type="hidden" name="id" value="<?php echo (int)($m['id'] ?? 0); ?>">
            <input name="name" value="<?php echo htmlspecialchars($m['name'] ?? ''); ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            <textarea name="description" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" rows="2"><?php echo htmlspecialchars($m['description'] ?? ''); ?></textarea>
            <div class="flex gap-2">
              <button name="action" value="update" class="text-xs px-3 py-1 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">Save</button>
              <button name="action" value="delete" class="text-xs px-3 py-1 rounded bg-red-50 text-red-700 border border-red-200">Delete</button>
            </div>
          </form>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-900 mb-3">Missions Projects</h2>
        <form method="post" class="space-y-2 mb-4 rounded-lg border border-slate-200 p-3 bg-slate-50">
          <?php echo rgcCsrfField('admin_content'); ?>
          <input type="hidden" name="type" value="project">
          <input type="hidden" name="action" value="add">
          <input name="title" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-fuchsia-300" placeholder="Project title" required>
          <textarea name="description" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-fuchsia-300" rows="2" placeholder="Description"></textarea>
          <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm">Add Project</button>
        </form>
        <div class="space-y-2 max-h-[360px] overflow-y-auto">
          <?php foreach ($projects as $p): ?>
          <form method="post" class="border rounded-lg p-3 space-y-2 bg-white">
            <?php echo rgcCsrfField('admin_content'); ?>
            <input type="hidden" name="type" value="project">
            <input type="hidden" name="id" value="<?php echo (int)($p['id'] ?? 0); ?>">
            <input name="title" value="<?php echo htmlspecialchars($p['title'] ?? ''); ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            <textarea name="description" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" rows="2"><?php echo htmlspecialchars($p['description'] ?? ''); ?></textarea>
            <div class="flex gap-2">
              <button name="action" value="update" class="text-xs px-3 py-1 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">Save</button>
              <button name="action" value="delete" class="text-xs px-3 py-1 rounded bg-red-50 text-red-700 border border-red-200">Delete</button>
            </div>
          </form>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-900 mb-3">Testimonials</h2>
        <form method="post" class="space-y-2 mb-4 rounded-lg border border-slate-200 p-3 bg-slate-50">
          <?php echo rgcCsrfField('admin_content'); ?>
          <input type="hidden" name="type" value="testimonial">
          <input type="hidden" name="action" value="add">
          <input name="name" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-fuchsia-300" placeholder="Name" required>
          <textarea name="message" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-fuchsia-300" rows="2" placeholder="Message" required></textarea>
          <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm">Add Testimony</button>
        </form>
        <div class="space-y-2 max-h-[360px] overflow-y-auto">
          <?php foreach ($testimonials as $t): ?>
          <?php if (str_starts_with((string)($t['name'] ?? ''), $bishopTestimonialMarker)) continue; ?>
          <form method="post" class="border rounded-lg p-3 space-y-2 bg-white">
            <?php echo rgcCsrfField('admin_content'); ?>
            <input type="hidden" name="type" value="testimonial">
            <input type="hidden" name="id" value="<?php echo (int)($t['id'] ?? 0); ?>">
            <input name="name" value="<?php echo htmlspecialchars($t['name'] ?? ''); ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            <textarea name="message" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" rows="2"><?php echo htmlspecialchars($t['message'] ?? ''); ?></textarea>
            <div class="flex gap-2">
              <button name="action" value="update" class="text-xs px-3 py-1 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">Save</button>
              <button name="action" value="delete" class="text-xs px-3 py-1 rounded bg-red-50 text-red-700 border border-red-200">Delete</button>
            </div>
          </form>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-900 mb-3">Gallery</h2>
        <form method="post" enctype="multipart/form-data" class="space-y-2 mb-4 rounded-lg border border-slate-200 p-3 bg-slate-50">
          <?php echo rgcCsrfField('admin_content'); ?>
          <input type="hidden" name="type" value="gallery">
          <input type="hidden" name="action" value="add">
          <input name="image_file" type="file" accept="image/*" class="w-full cursor-pointer rounded-2xl border border-dashed border-slate-300 bg-white px-3 py-3 text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-slate-400 hover:file:bg-slate-800" required>
          <input name="caption" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-fuchsia-300" placeholder="Caption">
          <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm">Add Image</button>
        </form>
        <div class="space-y-2 max-h-[360px] overflow-y-auto">
          <?php foreach ($gallery as $g): ?>
          <?php if (($g['caption'] ?? '') === $bishopImageMarker) continue; ?>
          <form method="post" enctype="multipart/form-data" class="border rounded-lg p-3 space-y-2 bg-white">
            <?php echo rgcCsrfField('admin_content'); ?>
            <input type="hidden" name="type" value="gallery">
            <input type="hidden" name="id" value="<?php echo (int)($g['id'] ?? 0); ?>">
            <div class="flex items-center gap-3">
              <img src="<?php echo htmlspecialchars($g['image'] ?? ''); ?>" alt="Gallery image" class="w-14 h-14 rounded-lg object-cover border">
              <input name="image_file" type="file" accept="image/*" class="flex-1 cursor-pointer rounded-2xl border border-dashed border-slate-300 bg-white px-3 py-3 text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-slate-400 hover:file:bg-slate-800">
            </div>
            <input name="caption" value="<?php echo htmlspecialchars($g['caption'] ?? ''); ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            <div class="flex gap-2">
              <button name="action" value="update" class="text-xs px-3 py-1 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">Save</button>
              <button name="action" value="delete" class="text-xs px-3 py-1 rounded bg-red-50 text-red-700 border border-red-200">Delete</button>
            </div>
          </form>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
