<?php
require __DIR__ . '/includes/auth.php';
requireLogin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$error = '';

$sermons = rgcLoadJson('sermons.json', []);
$index = null;
foreach ($sermons as $k => $row) {
  if ((int) ($row['id'] ?? 0) === $id) {
    $index = $k;
    break;
  }
}

if ($id <= 0 || $index === null) {
  http_response_code(404);
  echo 'Sermon not found.';
  exit;
}

$sermon = $sermons[$index];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('admin_edit_sermon');
  $title = trim((string) ($_POST['title'] ?? ''));
  $speaker = trim((string) ($_POST['speaker'] ?? ''));
  $youtubeUrl = trim((string) ($_POST['youtube_url'] ?? ''));
  $facebookEmbed = trim((string) ($_POST['facebook_embed'] ?? ''));
  $scheduled = trim((string) ($_POST['scheduled_at'] ?? ''));
  $isLive = isset($_POST['is_live']);
  $featured = isset($_POST['featured']);

  // At least one video URL is required
  if ($title === '' || ($youtubeUrl === '' && $facebookEmbed === '')) {
    $error = 'Sermon title and at least one video URL (YouTube or Facebook) are required.';
  } else {
    $sermons[$index] = [
      'id' => $id,
      'title' => $title,
      'speaker' => $speaker,
      'youtube_url' => $youtubeUrl,
      'facebook_embed' => $facebookEmbed,
      'scheduled_at' => $scheduled !== '' ? date('Y-m-d H:i:s', strtotime($scheduled)) : date('Y-m-d H:i:s'),
      'is_live' => $isLive,
      'featured' => $featured,
      'created_at' => $sermon['created_at'] ?? date('Y-m-d H:i:s'),
    ];
    rgcSaveJson('sermons.json', $sermons);
    header('Location: ' . rgcUrl('admin/sermons.php?saved=1'));
    exit;
  }

  $sermon['title'] = $title;
  $sermon['speaker'] = $speaker;
  $sermon['youtube_url'] = $youtubeUrl;
  $sermon['facebook_embed'] = $facebookEmbed;
  $sermon['scheduled_at'] = $scheduled !== '' ? date('Y-m-d H:i:s', strtotime($scheduled)) : (string) ($sermon['scheduled_at'] ?? '');
  $sermon['is_live'] = $isLive;
  $sermon['featured'] = $featured;
}

$scheduledValue = '';
if (!empty($sermon['scheduled_at'])) {
  $t = strtotime((string) $sermon['scheduled_at']);
  if ($t !== false) {
    $scheduledValue = date('Y-m-d\TH:i', $t);
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Sermon</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-4xl mx-auto px-4 py-8 space-y-6">
    <section class="bg-gradient-to-r from-indigo-700 via-indigo-600 to-sky-600 text-white rounded-2xl p-6 shadow-lg">
      <h1 class="text-2xl font-bold">Edit Sermon</h1>
      <p class="mt-2 text-indigo-100">Update sermon details and publish flags.</p>
    </section>

    <article class="bg-white rounded-xl shadow border border-slate-200 p-6">
      <?php if ($error !== ''): ?>
      <p class="mb-3 text-sm rounded bg-red-50 text-red-700 px-3 py-2"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>

      <form method="post" class="space-y-3">
        <?php echo rgcCsrfField('admin_edit_sermon'); ?>
        <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
        <div>
          <label class="text-sm font-medium text-slate-700">Sermon Title</label>
          <input name="title" value="<?php echo htmlspecialchars((string) ($sermon['title'] ?? '')); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" required>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Speaker</label>
          <input name="speaker" value="<?php echo htmlspecialchars((string) ($sermon['speaker'] ?? '')); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">YouTube URL <span class="text-green-600 text-xs">(Recommended)</span></label>
          <input name="youtube_url" value="<?php echo htmlspecialchars((string) ($sermon['youtube_url'] ?? '')); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="https://www.youtube.com/watch?v=...">
          <p class="text-xs text-slate-500 mt-1">Paste a YouTube watch URL or share link</p>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Facebook Embed URL</label>
          <input name="facebook_embed" value="<?php echo htmlspecialchars((string) ($sermon['facebook_embed'] ?? '')); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="https://www.facebook.com/plugins/video.php?...">
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Schedule Date/Time</label>
          <input name="scheduled_at" type="datetime-local" value="<?php echo htmlspecialchars($scheduledValue); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
        </div>
        <div class="grid sm:grid-cols-2 gap-2">
          <label class="text-sm rounded-lg border border-slate-200 px-3 py-2 bg-slate-50">
            <input type="checkbox" name="is_live" class="mr-2" <?php echo !empty($sermon['is_live']) ? 'checked' : ''; ?>> Mark as live now
          </label>
          <label class="text-sm rounded-lg border border-slate-200 px-3 py-2 bg-slate-50">
            <input type="checkbox" name="featured" class="mr-2" <?php echo !empty($sermon['featured']) ? 'checked' : ''; ?>> Feature on homepage
          </label>
        </div>
        <div class="flex items-center gap-2">
          <button class="px-5 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Save Changes</button>
          <a href="<?php echo rgcUrl('admin/sermons.php'); ?>" class="px-5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700">Back</a>
        </div>
      </form>
    </article>
  </main>
</body>
</html>
