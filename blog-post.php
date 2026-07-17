<?php
require __DIR__ . '/includes/app.php';
rgcEnforcePublicAccess();

$slug = trim((string) ($_GET['slug'] ?? ''));
$post = null;

if ($slug !== '' && rgcDbAvailable()) {
  try {
    $stmt = rgcDb()->prepare('SELECT id, title, slug, content, banner, created_at FROM blog_posts WHERE slug = :slug LIMIT 1');
    $stmt->execute([':slug' => $slug]);
    $post = $stmt->fetch();
  } catch (Throwable $e) {
    $post = null;
  }
}

if (!$post) {
  http_response_code(404);
  $pageTitle = 'Post Not Found';
  require __DIR__ . '/includes/header.php';
  ?>
  <section class="section-padding bg-slate-50">
    <div class="max-w-4xl mx-auto px-4">
      <h1 class="text-2xl font-bold text-slate-900">Post not found</h1>
      <p class="text-slate-600 mt-2">The blog post you requested does not exist.</p>
      <a href="<?php echo htmlspecialchars(rgcUrl('blog.php')); ?>" class="inline-block mt-4 px-4 py-2 rounded-lg bg-slate-900 text-white">Back to Blog</a>
    </div>
  </section>
  <?php
  require __DIR__ . '/includes/footer.php';
  exit;
}

$pageTitle = (string) $post['title'];
require __DIR__ . '/includes/header.php';
?>
<section class="section-padding bg-slate-50">
  <div class="max-w-4xl mx-auto px-4">
    <a href="<?php echo htmlspecialchars(rgcUrl('blog.php')); ?>" class="text-sm text-indigo-700 hover:underline">Back to Blog</a>
    <article class="mt-4 bg-white border border-slate-200 rounded-2xl overflow-hidden shadow">
      <?php if (!empty($post['banner'])): ?>
      <div class="h-64 bg-slate-200" style="background-image:url('<?php echo htmlspecialchars((string) $post['banner']); ?>');background-size:cover;background-position:center;"></div>
      <?php endif; ?>
      <div class="p-6 md:p-8">
        <h1 class="text-2xl md:text-3xl font-bold text-slate-900"><?php echo htmlspecialchars((string) $post['title']); ?></h1>
        <p class="text-xs text-slate-500 mt-2"><?php echo htmlspecialchars((string) $post['created_at']); ?></p>
        <div class="prose max-w-none mt-6 text-slate-800">
          <?php echo rgcSanitizeRichHtml((string) $post['content']); ?>
        </div>
      </div>
    </article>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
