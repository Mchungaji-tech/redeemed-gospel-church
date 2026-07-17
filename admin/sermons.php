<?php
require __DIR__ . '/includes/auth.php';
requireLogin();

$sermons = rgcLoadJson('sermons.json', []);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('admin_sermons');
  $action = $_POST['action'] ?? 'add';

  if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    $sermons = array_values(array_filter($sermons, static fn($s) => (int) ($s['id'] ?? 0) !== $id));
    rgcSaveJson('sermons.json', $sermons);
    header('Location: ' . rgcUrl('admin/sermons.php?saved=1'));
    exit;
  }

  $title = trim($_POST['title'] ?? '');
  $speaker = trim($_POST['speaker'] ?? '');
  $youtubeUrl = trim($_POST['youtube_url'] ?? '');
  $facebookEmbed = trim($_POST['facebook_embed'] ?? '');
  $scheduled = trim($_POST['scheduled_at'] ?? '');
  $isLive = isset($_POST['is_live']);
  $featured = isset($_POST['featured']);

  // At least one video URL is required
  if ($title === '' || ($youtubeUrl === '' && $facebookEmbed === '')) {
    $error = 'Sermon title and at least one video URL (YouTube or Facebook) are required.';
  } else {
    $sermons[] = [
      'id' => rgcNextId($sermons),
      'title' => $title,
      'speaker' => $speaker,
      'youtube_url' => $youtubeUrl,
      'facebook_embed' => $facebookEmbed,
      'scheduled_at' => $scheduled !== '' ? date('Y-m-d H:i:s', strtotime($scheduled)) : date('Y-m-d H:i:s'),
      'is_live' => $isLive,
      'featured' => $featured,
    ];
    rgcSaveJson('sermons.json', $sermons);
    header('Location: ' . rgcUrl('admin/sermons.php?saved=1'));
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Sermons</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <section class="bg-gradient-to-r from-indigo-700 via-indigo-600 to-sky-600 text-white rounded-2xl p-6 shadow-lg">
      <h1 class="text-2xl font-bold">Sermons Manager</h1>
      <p class="mt-2 text-indigo-100">Upload new sermon entries, set live status, and feature messages on the homepage.</p>
    </section>

    <section class="grid xl:grid-cols-5 gap-6">
      <article class="bg-white rounded-xl shadow border border-slate-200 p-6 xl:col-span-2">
        <h2 class="text-lg font-bold text-slate-900">Add Sermon</h2>
        <p class="text-sm text-slate-500 mt-1 mb-4">Add YouTube or Facebook video URLs. YouTube is prioritized.</p>

        <?php if (isset($_GET['saved'])): ?>
        <p class="mb-3 text-sm rounded bg-emerald-50 text-emerald-700 px-3 py-2">Sermon changes saved.</p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
        <p class="mb-3 text-sm rounded bg-red-50 text-red-700 px-3 py-2"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post" class="space-y-3">
          <?php echo rgcCsrfField('admin_sermons'); ?>
          <input type="hidden" name="action" value="add">
          <div>
            <label class="text-sm font-medium text-slate-700">Sermon Title</label>
            <input name="title" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="Sermon title" required>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Speaker</label>
            <input name="speaker" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="Speaker">
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">YouTube URL <span class="text-green-600 text-xs">(Recommended)</span></label>
            <input name="youtube_url" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="https://www.youtube.com/watch?v=...">
            <p class="text-xs text-slate-500 mt-1">Paste a YouTube watch URL or share link</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Facebook Embed URL</label>
            <input name="facebook_embed" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="https://www.facebook.com/plugins/video.php?...">
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Schedule Date/Time</label>
            <input name="scheduled_at" type="datetime-local" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
          </div>
          <div class="grid sm:grid-cols-2 gap-2">
            <label class="text-sm rounded-lg border border-slate-200 px-3 py-2 bg-slate-50">
              <input type="checkbox" name="is_live" class="mr-2"> Mark as live now
            </label>
            <label class="text-sm rounded-lg border border-slate-200 px-3 py-2 bg-slate-50">
              <input type="checkbox" name="featured" class="mr-2"> Feature on homepage
            </label>
          </div>
          <button class="px-5 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Save Sermon</button>
        </form>
      </article>

      <article class="bg-white rounded-xl shadow border border-slate-200 p-6 xl:col-span-3">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-bold text-slate-900">Current Sermons</h2>
          <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600"><?php echo count($sermons); ?> total</span>
        </div>

        <div class="space-y-3 max-h-[650px] overflow-y-auto pr-1">
          <?php foreach (array_reverse($sermons) as $s): ?>
          <article class="border border-slate-200 rounded-xl p-4">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($s['title'] ?? ''); ?></p>
                <p class="text-sm text-slate-600 mt-0.5"><?php echo htmlspecialchars($s['speaker'] ?? ''); ?></p>
                <p class="text-xs text-slate-500 mt-1">Scheduled: <?php echo htmlspecialchars(date('D, M j, Y g:i A', strtotime((string) ($s['scheduled_at'] ?? 'now')))); ?></p>
                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                  <span class="px-2 py-0.5 rounded-full <?php echo !empty($s['is_live']) ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600'; ?>">
                    <?php echo !empty($s['is_live']) ? 'LIVE' : 'Not Live'; ?>
                  </span>
                  <span class="px-2 py-0.5 rounded-full <?php echo !empty($s['featured']) ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'; ?>">
                    <?php echo !empty($s['featured']) ? 'Featured' : 'Standard'; ?>
                  </span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <a href="<?php echo rgcUrl('admin/edit-sermon.php?id=' . (int) ($s['id'] ?? 0)); ?>" class="px-3 py-1 text-sm rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100">Edit</a>
                <form method="post" onsubmit="return confirm('Delete this sermon?');">
                  <?php echo rgcCsrfField('admin_sermons'); ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo (int) ($s['id'] ?? 0); ?>">
                  <button class="px-3 py-1 text-sm rounded-lg bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100">Delete</button>
                </form>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
          <?php if (empty($sermons)): ?>
          <p class="text-sm text-slate-500">No sermons yet.</p>
          <?php endif; ?>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
