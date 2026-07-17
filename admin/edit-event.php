<?php
require __DIR__ . '/includes/auth.php';
requireLogin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$error = '';

$events = rgcLoadJson('events.json', []);
$index = null;
foreach ($events as $k => $row) {
  if ((int) ($row['id'] ?? 0) === $id) {
    $index = $k;
    break;
  }
}

if ($id <= 0 || $index === null) {
  http_response_code(404);
  echo 'Event not found.';
  exit;
}

$event = $events[$index];

// Image upload handler
function handleEventImageUpload($fileKey, $existingPath = '') {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return $existingPath; // Keep existing if no new upload
    }
    $file = $_FILES[$fileKey];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['error' => 'Invalid file type. Only JPG, PNG, GIF, WebP allowed.'];
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'event_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetDir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $targetPath = $targetDir . $filename;
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return rgcUrl('assets/uploads/' . $filename);
    }
    return ['error' => 'Failed to upload file'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('admin_edit_event');
  $title = trim((string) ($_POST['title'] ?? ''));
  $description = trim((string) ($_POST['description'] ?? ''));
  $location = trim((string) ($_POST['location'] ?? ''));
  $poster = trim((string) ($_POST['existing_poster'] ?? ''));
  $eventAt = trim((string) ($_POST['event_at'] ?? ''));
  $parsed = strtotime($eventAt);

  // Handle poster image upload
  $uploadResult = handleEventImageUpload('poster', $event['poster'] ?? '');
  if ($uploadResult && !is_array($uploadResult)) {
      $poster = $uploadResult;
  } elseif (isset($uploadResult['error'])) {
      $error = $uploadResult['error'];
  }

  if ($title === '' || $eventAt === '' || $parsed === false) {
    $error = 'Event title and valid date/time are required.';
  } else {
    $events[$index] = [
      'id' => $id,
      'title' => $title,
      'description' => $description,
      'location' => $location,
      'poster' => $poster,
      'event_at' => date('Y-m-d H:i:s', $parsed),
      'created_at' => $event['created_at'] ?? date('Y-m-d H:i:s'),
    ];
    rgcSaveJson('events.json', $events);
    header('Location: ' . rgcUrl('admin/events.php?saved=1'));
    exit;
  }

  $event['title'] = $title;
  $event['description'] = $description;
  $event['location'] = $location;
  $event['poster'] = $poster;
  if ($parsed !== false) {
    $event['event_at'] = date('Y-m-d H:i:s', $parsed);
  }
}

$eventAtValue = '';
if (!empty($event['event_at'])) {
  $t = strtotime((string) $event['event_at']);
  if ($t !== false) {
    $eventAtValue = date('Y-m-d\TH:i', $t);
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Event</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-4xl mx-auto px-4 py-8 space-y-6">
    <section class="rounded-2xl bg-gradient-to-r from-emerald-700 via-teal-600 to-cyan-600 text-white p-6 shadow-lg">
      <h1 class="text-2xl font-bold">Edit Event</h1>
      <p class="mt-2 text-emerald-100">Update title, timing, poster, and details.</p>
    </section>

    <article class="bg-white rounded-xl shadow border border-slate-200 p-6">
      <?php if ($error !== ''): ?>
      <p class="mb-3 text-sm rounded bg-red-50 text-red-700 px-3 py-2"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>

      <form method="post" class="space-y-3" enctype="multipart/form-data">
        <?php echo rgcCsrfField('admin_edit_event'); ?>
        <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
        <div>
          <label class="text-sm font-medium text-slate-700">Event Title</label>
          <input name="title" value="<?php echo htmlspecialchars((string) ($event['title'] ?? '')); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" required>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Location</label>
          <input name="location" value="<?php echo htmlspecialchars((string) ($event['location'] ?? '')); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Date & Time</label>
          <input name="event_at" type="datetime-local" value="<?php echo htmlspecialchars($eventAtValue); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" required>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Poster Image</label>
          <?php if (!empty($event['poster'])): ?>
          <div class="mt-2 mb-2">
            <img src="<?php echo htmlspecialchars($event['poster']); ?>" alt="Current poster" class="w-32 h-32 object-cover rounded-lg border">
          </div>
          <?php endif; ?>
          <input name="poster" type="file" accept="image/*" class="mt-1 block w-full cursor-pointer rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-slate-400 hover:bg-white hover:file:bg-slate-800">
          <input name="existing_poster" type="hidden" value="<?php echo htmlspecialchars($event['poster'] ?? ''); ?>">
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Description</label>
          <textarea name="description" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" rows="4"><?php echo htmlspecialchars((string) ($event['description'] ?? '')); ?></textarea>
        </div>
        <div class="flex items-center gap-2">
          <button class="px-5 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Save Changes</button>
          <a href="<?php echo rgcUrl('admin/events.php'); ?>" class="px-5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700">Back</a>
        </div>
      </form>
    </article>
  </main>
</body>
</html>
