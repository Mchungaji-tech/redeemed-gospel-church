<?php
require __DIR__ . '/includes/auth.php';
requireLogin();

$events = rgcLoadJson('events.json', []);
$error = '';

// Image upload handler
function handleEventImageUpload($fileKey) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $file = $_FILES[$fileKey];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['error' => 'Invalid file type. Only JPG, PNG, GIF, WebP allowed.'];
    }
    $targetDir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Use optimization helper
    $optimizedPath = rgcOptimizeImage($file['tmp_name'], $targetDir, 'event');
    if ($optimizedPath) {
        return '/' . $optimizedPath;
    }
    
    return ['error' => 'Failed to process and optimize image.'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('admin_events');
  $action = $_POST['action'] ?? 'add';

  if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    $events = array_values(array_filter($events, static fn($e) => (int) ($e['id'] ?? 0) !== $id));
    rgcSaveJson('events.json', $events);
    header('Location: ' . rgcUrl('admin/events.php?saved=1'));
    exit;
  }

  $title = trim($_POST['title'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $location = trim($_POST['location'] ?? '');
  $poster = trim($_POST['existing_poster'] ?? '');
  $eventAt = trim($_POST['event_at'] ?? '');
  $parsed = strtotime($eventAt);
  
  // Handle poster image upload
  $uploadResult = handleEventImageUpload('poster');
  if ($uploadResult && !is_array($uploadResult)) {
      $poster = $uploadResult;
  } elseif (isset($uploadResult['error'])) {
      $error = $uploadResult['error'];
  }

  if ($title === '' || $eventAt === '' || $parsed === false) {
    $error = 'Event title and valid date/time are required.';
  } else {
    $events[] = [
      'id' => rgcNextId($events),
      'title' => $title,
      'description' => $description,
      'location' => $location,
      'poster' => $poster,
      'event_at' => date('Y-m-d H:i:s', $parsed),
    ];
    rgcSaveJson('events.json', $events);
    header('Location: ' . rgcUrl('admin/events.php?saved=1'));
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Events</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <section class="rounded-2xl bg-gradient-to-r from-emerald-700 via-teal-600 to-cyan-600 text-white p-6 shadow-lg">
      <h1 class="text-2xl font-bold">Events + Posters Manager</h1>
      <p class="text-emerald-100 mt-2">Create upcoming events with poster design, location, and precise countdown timing.</p>
    </section>

    <section class="grid xl:grid-cols-5 gap-6">
    <section class="bg-white rounded-xl shadow border border-slate-200 p-6 xl:col-span-2">
      <h1 class="text-xl font-bold mb-1">Add Event Poster + Date</h1>
      <p class="text-sm text-slate-500 mb-4">Create events with poster image, location, and exact event date/time.</p>

      <?php if (isset($_GET['saved'])): ?>
      <p class="mb-3 text-sm rounded bg-emerald-50 text-emerald-700 px-3 py-2">Event changes saved.</p>
      <?php endif; ?>

      <?php if ($error !== ''): ?>
      <p class="mb-3 text-sm rounded bg-red-50 text-red-700 px-3 py-2"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>

      <form method="post" class="space-y-3" enctype="multipart/form-data">
        <?php echo rgcCsrfField('admin_events'); ?>
        <input type="hidden" name="action" value="add">
        <div>
          <label class="text-sm font-medium">Event Title</label>
          <input name="title" class="mt-1 w-full border rounded px-3 py-2" placeholder="Event title" required>
        </div>
        <div>
          <label class="text-sm font-medium">Location</label>
          <input name="location" class="mt-1 w-full border rounded px-3 py-2" placeholder="Venue/location">
        </div>
        <div>
          <label class="text-sm font-medium">Date & Time</label>
          <input name="event_at" type="datetime-local" class="mt-1 w-full border rounded px-3 py-2" required>
        </div>
        <div>
          <label class="text-sm font-medium">Poster Image</label>
          <input name="poster" type="file" accept="image/*" class="mt-1 w-full border rounded px-3 py-2 bg-white">
          <input name="existing_poster" type="hidden" value="">
        </div>
        <div>
          <label class="text-sm font-medium">Description</label>
          <textarea name="description" class="mt-1 w-full border rounded px-3 py-2" rows="4" placeholder="Description"></textarea>
        </div>
        <button class="px-5 py-2 rounded bg-slate-900 text-white hover:bg-slate-800">Save Event</button>
      </form>
    </section>

    <section class="bg-white rounded-xl shadow border border-slate-200 p-6 xl:col-span-3">
      <h2 class="text-xl font-bold mb-4">Current Events</h2>
      <div class="space-y-4 max-h-[650px] overflow-y-auto pr-1">
        <?php foreach (array_reverse($events) as $e): ?>
        <article class="border rounded-xl p-3 md:p-4">
          <div class="flex items-start gap-3">
            <?php if (!empty($e['poster'])): ?>
            <img src="<?php echo htmlspecialchars($e['poster']); ?>" alt="<?php echo htmlspecialchars($e['title']); ?>" class="w-20 h-20 rounded-lg object-cover shrink-0 border">
            <?php endif; ?>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($e['title']); ?></p>
              <p class="text-sm text-slate-600"><?php echo htmlspecialchars($e['location'] ?? ''); ?></p>
              <p class="text-xs text-slate-500 mt-1"><?php echo htmlspecialchars(date('D, M j, Y g:i A', strtotime($e['event_at']))); ?></p>
              <?php if (!empty($e['description'])): ?>
              <p class="text-sm text-slate-700 mt-2"><?php echo htmlspecialchars($e['description']); ?></p>
              <?php endif; ?>
            </div>
            <div class="flex items-center gap-2">
              <a href="<?php echo rgcUrl('admin/edit-event.php?id=' . (int) ($e['id'] ?? 0)); ?>" class="text-sm px-3 py-1 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100">Edit</a>
              <form method="post" onsubmit="return confirm('Delete this event?');">
                <?php echo rgcCsrfField('admin_events'); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int) ($e['id'] ?? 0); ?>">
                <button class="text-sm px-3 py-1 rounded bg-red-50 text-red-700 border border-red-200 hover:bg-red-100">Delete</button>
              </form>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
        <?php if (empty($events)): ?>
        <p class="text-slate-500 text-sm">No events created yet.</p>
        <?php endif; ?>
      </div>
    </section>
    </section>
  </main>
</body>
</html>
