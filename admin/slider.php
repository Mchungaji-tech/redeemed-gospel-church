<?php
require __DIR__ . '/includes/auth.php';
requireLogin();

$slider = rgcLoadJson('slider.json', []);
$error = '';

// Sort by order
usort($slider, function($a, $b) {
    return ($a['order'] ?? 1) - ($b['order'] ?? 1);
});

// Handle file upload
function handleSliderImageUpload($fileKey, $uploadDir = 'assets/uploads/') {
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
    $optimizedPath = rgcOptimizeImage($file['tmp_name'], $fullDir, 'slider');
    if ($optimizedPath) {
        return $optimizedPath;
    }
    
    return ['error' => 'Failed to process and optimize image.'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    rgcRequireCsrf('admin_slider');
    $action = $_POST['action'] ?? 'add';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $slider = array_values(array_filter($slider, static fn($s) => (int)($s['id'] ?? 0) !== $id));
        rgcSaveJson('slider.json', $slider);
        header('Location: ' . rgcUrl('admin/slider.php?saved=1'));
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $button1_text = trim($_POST['button1_text'] ?? '');
    $button1_link = trim($_POST['button1_link'] ?? '');
    $button2_text = trim($_POST['button2_text'] ?? '');
    $button2_link = trim($_POST['button2_link'] ?? '');
    $background_style = $_POST['background_style'] ?? 'brand';
    $order = (int)($_POST['order'] ?? 1);
    $existingImage = trim($_POST['existing_image'] ?? '');
    
    // Handle image upload
    $image = $existingImage;
    $uploadResult = handleSliderImageUpload('image');
    if ($uploadResult && !is_array($uploadResult)) {
        $image = '/' . $uploadResult;
    } elseif (isset($uploadResult['error'])) {
        $error = $uploadResult['error'];
    }

    if ($title === '') {
        $error = 'Slide title is required.';
    } else {
        if ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            $found = false;
            foreach ($slider as &$s) {
                if ((int)($s['id'] ?? 0) === $id) {
                    $s['title'] = $title;
                    $s['subtitle'] = $subtitle;
                    $s['description'] = $description;
                    $s['image'] = $image;
                    $s['button1_text'] = $button1_text;
                    $s['button1_link'] = $button1_link;
                    $s['button2_text'] = $button2_text;
                    $s['button2_link'] = $button2_link;
                    $s['background_style'] = $background_style;
                    $s['order'] = $order;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $error = 'Slide not found.';
            }
        } else {
            $slider[] = [
                'id' => rgcNextId($slider),
                'title' => $title,
                'subtitle' => $subtitle,
                'description' => $description,
                'image' => $image,
                'button1_text' => $button1_text,
                'button1_link' => $button1_link,
                'button2_text' => $button2_text,
                'button2_link' => $button2_link,
                'background_style' => $background_style,
                'order' => $order
            ];
        }

        if ($error === '') {
            rgcSaveJson('slider.json', $slider);
            header('Location: ' . rgcUrl('admin/slider.php?saved=1'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Slider</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <section class="bg-gradient-to-r from-slate-700 via-slate-600 to-slate-800 text-white rounded-2xl p-6 shadow-lg">
      <h1 class="text-2xl font-bold">Slider Manager</h1>
      <p class="mt-2 text-slate-200">Manage hero slider slides for the homepage.</p>
    </section>

    <?php if (isset($_GET['saved'])): ?>
    <p class="mb-3 text-sm rounded bg-emerald-50 text-emerald-700 px-3 py-2">Changes saved successfully.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <p class="mb-3 text-sm rounded bg-red-50 text-red-700 px-3 py-2"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <div class="grid xl:grid-cols-5 gap-6">
      <!-- Add Slide Form -->
      <article class="bg-white rounded-xl shadow border border-slate-200 p-6 xl:col-span-2">
        <h2 id="formTitle" class="text-lg font-bold text-slate-900">Add New Slide</h2>
        <p id="formDesc" class="text-sm text-slate-500 mt-1 mb-4">Create a new slider slide.</p>

        <form id="sliderForm" method="post" class="space-y-3" enctype="multipart/form-data">
          <?php echo rgcCsrfField('admin_slider'); ?>
          <input type="hidden" name="action" id="formAction" value="add">
          <input type="hidden" name="id" id="formId" value="">
          
          <div>
            <label class="text-sm font-medium text-slate-700">Title</label>
            <input name="title" id="slideTitle" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="Main headline" required>
          </div>
          
          <div>
            <label class="text-sm font-medium text-slate-700">Subtitle</label>
            <input name="subtitle" id="slideSubtitle" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="Small text above title">
          </div>
          
          <div>
            <label class="text-sm font-medium text-slate-700">Description</label>
            <textarea name="description" id="slideDescription" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" rows="2" placeholder="Description text..."></textarea>
          </div>
          
          <div>
            <label class="text-sm font-medium text-slate-700">Background Image</label>
            <input type="file" name="image" accept="image/*" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
            <p class="text-xs text-slate-500 mt-1">Upload background image (optional, shows on right side)</p>
            <input type="hidden" name="existing_image" id="slideExistingImage" value="">
          </div>
          
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-sm font-medium text-slate-700">Button 1 Text</label>
              <input name="button1_text" id="slideBtn1Text" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="Learn More">
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">Button 1 Link</label>
              <input name="button1_link" id="slideBtn1Link" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="<?php echo htmlspecialchars(rgcUrl('about.php')); ?>">
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-sm font-medium text-slate-700">Button 2 Text</label>
              <input name="button2_text" id="slideBtn2Text" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="Contact Us">
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">Button 2 Link</label>
              <input name="button2_link" id="slideBtn2Link" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="<?php echo htmlspecialchars(rgcUrl('contact.php')); ?>">
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-sm font-medium text-slate-700">Background</label>
              <select name="background_style" id="slideBgStyle" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
                <option value="brand">Brand (Gold)</option>
                <option value="slate">Slate</option>
                <option value="gradient">Gradient</option>
              </select>
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">Order</label>
              <input name="order" id="slideOrder" type="number" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" value="1" min="1">
            </div>
          </div>
          
          <div class="flex items-center gap-3">
            <button type="submit" id="submitBtn" class="px-5 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Add Slide</button>
            <button type="button" id="cancelBtn" onclick="cancelEdit()" class="hidden px-5 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">Cancel Edit</button>
          </div>
        </form>
      </article>

      <!-- Slides List -->
      <article class="bg-white rounded-xl shadow border border-slate-200 p-6 xl:col-span-3">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-bold text-slate-900">Current Slides</h2>
          <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600"><?php echo count($slider); ?> slides</span>
        </div>

        <div class="space-y-3 max-h-[600px] overflow-y-auto pr-1">
          <?php foreach ($slider as $s): ?>
          <article class="border border-slate-200 rounded-xl p-4">
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($s['title'] ?? ''); ?></p>
                  <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">Order: <?php echo $s['order'] ?? 1; ?></span>
                </div>
                <p class="text-sm text-slate-600 mt-1"><?php echo htmlspecialchars($s['subtitle'] ?? ''); ?></p>
                <?php if (!empty($s['button1_text'])): ?>
                <div class="flex items-center gap-2 mt-2">
                  <span class="text-xs px-2 py-0.5 rounded bg-brand-100 text-brand-700"><?php echo htmlspecialchars($s['button1_text']); ?></span>
                  <?php if (!empty($s['button2_text'])): ?>
                  <span class="text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-600"><?php echo htmlspecialchars($s['button2_text']); ?></span>
                  <?php endif; ?>
                </div>
                <?php endif; ?>
              </div>
              <div class="flex items-center gap-2">
                <button 
                  onclick='editSlide(<?php echo json_encode($s, JSON_HEX_APOS); ?>)'
                  class="px-3 py-1 text-sm rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100"
                >Edit</button>
                <form method="post" onsubmit="return confirm('Delete this slide?');">
                  <?php echo rgcCsrfField('admin_slider'); ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo (int)($s['id'] ?? 0); ?>">
                  <button class="px-3 py-1 text-sm rounded-lg bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100">Delete</button>
                </form>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
          <?php if (empty($slider)): ?>
          <p class="text-sm text-slate-500 text-center py-8">No slides yet. Add your first slide!</p>
          <?php endif; ?>
        </div>
      </article>
    </div>
  </main>

  <script>
    function editSlide(slide) {
      document.getElementById('formTitle').innerText = 'Edit Slide';
      document.getElementById('formDesc').innerText = 'Modify the selected slider slide.';
      document.getElementById('formAction').value = 'edit';
      document.getElementById('formId').value = slide.id;
      document.getElementById('slideTitle').value = slide.title || '';
      document.getElementById('slideSubtitle').value = slide.subtitle || '';
      document.getElementById('slideDescription').value = slide.description || '';
      document.getElementById('slideExistingImage').value = slide.image || '';
      document.getElementById('slideOrder').value = slide.order || 1;
      document.getElementById('slideBtn1Text').value = slide.button1_text || '';
      document.getElementById('slideBtn1Link').value = slide.button1_link || '';
      document.getElementById('slideBtn2Text').value = slide.button2_text || '';
      document.getElementById('slideBtn2Link').value = slide.button2_link || '';
      document.getElementById('slideBgStyle').value = slide.background_style || 'brand';
      document.getElementById('submitBtn').innerText = 'Save Changes';
      document.getElementById('cancelBtn').classList.remove('hidden');
      document.getElementById('sliderForm').scrollIntoView({ behavior: 'smooth' });
    }

    function cancelEdit() {
      document.getElementById('formTitle').innerText = 'Add New Slide';
      document.getElementById('formDesc').innerText = 'Create a new slider slide.';
      document.getElementById('formAction').value = 'add';
      document.getElementById('formId').value = '';
      document.getElementById('sliderForm').reset();
      document.getElementById('submitBtn').innerText = 'Add Slide';
      document.getElementById('cancelBtn').classList.add('hidden');
    }
  </script>
</body>
</html>
