<?php
require __DIR__ . '/includes/app.php';
rgcEnforcePublicAccess();
$pageTitle = 'Blog';
$featured = [];
$recent = [];
if (rgcDbAvailable()) {
  try {
    $fs = rgcDb()->query('SELECT id, title, slug, content, banner, created_at FROM blog_posts WHERE featured = 1 ORDER BY created_at DESC LIMIT 3');
    $featured = $fs->fetchAll();
    $rs = rgcDb()->query('SELECT id, title, slug, content, banner, created_at FROM blog_posts WHERE featured = 0 ORDER BY created_at DESC LIMIT 12');
    $recent = $rs->fetchAll();
  } catch (Throwable $e) {
    $featured = [];
    $recent = [];
  }
}
require __DIR__ . '/includes/header.php';
?>
<section class="section-padding bg-slate-50">
  <div class="max-w-6xl mx-auto px-4">
    <h1 class="text-2xl font-bold text-slate-900 mb-2">Blog</h1>
    <p class="text-slate-600 mb-6">News and updates</p>
    <?php if (empty($featured) && empty($recent)): ?>
      <p class="text-slate-500">No posts published yet.</p>
    <?php else: ?>
      <?php if (!empty($featured)): ?>
      <div class="grid md:grid-cols-3 gap-4 mb-8">
        <?php foreach ($featured as $idx => $p): ?>
        <a href="<?php echo htmlspecialchars(rgcUrl('blog-post.php?slug=' . urlencode((string) ($p['slug'] ?? '')))); ?>" class="block">
        <article class="rounded-2xl overflow-hidden border border-slate-200 bg-white shadow group hover:shadow-md transition">
          <div class="h-40 bg-slate-200 <?php echo $idx===0?'md:h-52':''; ?>" style="background-image:url('<?php echo htmlspecialchars((string) ($p['banner'] ?? '')); ?>');background-size:cover;background-position:center;"></div>
          <div class="p-5">
            <span class="inline-block text-xs px-2 py-1 rounded bg-amber-100 text-amber-700 mb-2">Featured</span>
            <h2 class="text-lg font-bold text-slate-900"><?php echo htmlspecialchars((string) $p['title']); ?></h2>
            <p class="text-xs text-slate-500 mt-1"><?php echo htmlspecialchars((string) $p['created_at']); ?></p>
            <div class="mt-2 text-slate-700 text-sm"><?php echo nl2br(htmlspecialchars(substr(trim(strip_tags((string) $p['content'])), 0, 180))); ?>...</div>
          </div>
        </article>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($recent)): ?>
      <div class="grid-auto-fit">
        <?php foreach ($recent as $p): ?>
        <a href="<?php echo htmlspecialchars(rgcUrl('blog-post.php?slug=' . urlencode((string) ($p['slug'] ?? '')))); ?>" class="block">
        <article class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow hover:shadow-md transition">
          <div class="h-36 bg-slate-200" style="background-image:url('<?php echo htmlspecialchars((string) ($p['banner'] ?? '')); ?>');background-size:cover;background-position:center;"></div>
          <div class="p-6">
            <h3 class="text-base font-bold text-slate-900"><?php echo htmlspecialchars((string) $p['title']); ?></h3>
            <p class="text-xs text-slate-500 mt-1"><?php echo htmlspecialchars((string) $p['created_at']); ?></p>
            <div class="mt-2 text-slate-700 text-sm"><?php echo nl2br(htmlspecialchars(substr(trim(strip_tags((string) $p['content'])), 0, 160))); ?>...</div>
          </div>
        </article>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
