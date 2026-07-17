<?php
require __DIR__ . '/includes/auth.php';
requireLogin();

function rgcSlugify(string $value): string {
  $value = strtolower(trim($value));
  $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
  $value = trim($value, '-');
  return $value === '' ? 'post-' . time() : $value;
}

function rgcHandleBlogBannerUpload(string $fieldName): array {
  if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
    return ['', ''];
  }
  $file = $_FILES[$fieldName];
  $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
  if ($err === UPLOAD_ERR_NO_FILE) {
    return ['', ''];
  }
  if ($err !== UPLOAD_ERR_OK) {
    return ['', 'Image upload failed. Try a smaller file.'];
  }

  $tmpPath = (string) ($file['tmp_name'] ?? '');
  $size = (int) ($file['size'] ?? 0);
  if ($size <= 0 || $size > 5 * 1024 * 1024) {
    return ['', 'Image too large. Max 5MB.'];
  }

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = $finfo ? finfo_file($finfo, $tmpPath) : null;
  if ($finfo) { finfo_close($finfo); }
  $allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
  ];
  if (!isset($allowed[$mime ?? ''])) {
    return ['', 'Invalid image type. Use JPG, PNG, WEBP, or GIF.'];
  }

  $ext = $allowed[$mime];
  $dir = dirname(__DIR__) . '/assets/uploads/blog';
  if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
  }
  $fileName = 'blog_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
  $dest = $dir . '/' . $fileName;
  if (!@move_uploaded_file($tmpPath, $dest)) {
    return ['', 'Failed to save image to uploads.'];
  }

  return [rgcUrl('assets/uploads/blog/' . $fileName), ''];
}

$title = '';
$slug = '';
$banner = '';
$content = '';
$featured = false;
$error = '';
$info = '';

$q = trim((string) ($_GET['q'] ?? ''));
$featuredFilter = trim((string) ($_GET['featured'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$per = 10;
$offset = ($page - 1) * $per;
$rows = [];
$total = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('admin_blog');
  $action = (string) ($_POST['action'] ?? 'create');

  if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0 && rgcDbAvailable()) {
      try {
        $stmt = rgcDb()->prepare('DELETE FROM blog_posts WHERE id = :id');
        $stmt->execute([':id' => $id]);
        header('Location: ' . rgcUrl('admin/blog.php?saved=1'));
        exit;
      } catch (Throwable $e) {
        $error = 'Unable to delete blog post.';
      }
    }
  } else {
    $title = trim((string) ($_POST['title'] ?? ''));
    $slug = trim((string) ($_POST['slug'] ?? ''));
    $banner = '';
    $content = rgcSanitizeRichHtml((string) ($_POST['content'] ?? ''));
    $featured = isset($_POST['featured']);
    [$uploadedBanner, $uploadError] = rgcHandleBlogBannerUpload('banner_file');
    if ($uploadError !== '') {
      $error = $uploadError;
    } elseif ($uploadedBanner !== '') {
      $banner = $uploadedBanner;
    }
    if ($slug === '') {
      $slug = rgcSlugify($title);
    } else {
      $slug = rgcSlugify($slug);
    }

    if ($error === '' && ($title === '' || $content === '')) {
      $error = 'Title and content are required.';
    } elseif ($error === '' && !rgcDbAvailable()) {
      $error = 'Database unavailable.';
    } elseif ($error === '') {
      try {
        $stmt = rgcDb()->prepare(
          'INSERT INTO blog_posts (title, slug, content, banner, featured, created_at)
           VALUES (:title, :slug, :content, :banner, :featured, NOW())'
        );
        $stmt->execute([
          ':title' => $title,
          ':slug' => $slug,
          ':content' => $content,
          ':banner' => $banner !== '' ? $banner : null,
          ':featured' => $featured ? 1 : 0,
        ]);
        header('Location: ' . rgcUrl('admin/blog.php?saved=1'));
        exit;
      } catch (PDOException $e) {
        if ((string) $e->getCode() === '23000') {
          $error = 'A post with this slug already exists - please edit the slug field.';
        } else {
          $error = 'Unable to save blog post.';
        }
      } catch (Throwable $e) {
        $error = 'Unable to save blog post.';
      }
    }
  }
}

if (isset($_GET['saved'])) {
  $info = 'Blog changes saved.';
}

if (rgcDbAvailable()) {
  try {
    $where = [];
    $params = [];
    if ($q !== '') {
      $where[] = '(title LIKE :q OR slug LIKE :q)';
      $params[':q'] = '%' . $q . '%';
    }
    if ($featuredFilter === '1' || $featuredFilter === '0') {
      $where[] = 'featured = :featured';
      $params[':featured'] = (int) $featuredFilter;
    }
    $whereSql = empty($where) ? '' : (' WHERE ' . implode(' AND ', $where));

    $cstmt = rgcDb()->prepare('SELECT COUNT(*) AS c FROM blog_posts' . $whereSql);
    foreach ($params as $k => $v) {
      $cstmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $cstmt->execute();
    $total = (int) ($cstmt->fetch()['c'] ?? 0);

    $stmt = rgcDb()->prepare(
      'SELECT id, title, slug, featured, created_at FROM blog_posts' . $whereSql . ' ORDER BY id DESC LIMIT :lim OFFSET :off'
    );
    foreach ($params as $k => $v) {
      $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':lim', $per, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
  } catch (Throwable $e) {
    $rows = [];
    $error = $error === '' ? 'Failed to load blog posts.' : $error;
  }
}

$pages = $per > 0 ? max(1, (int) ceil($total / $per)) : 1;
$baseQs = $_GET;
unset($baseQs['page']);
$baseUrl = rgcUrl('admin/blog.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blog Manager</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <section class="rounded-2xl bg-gradient-to-r from-indigo-700 via-indigo-600 to-sky-600 text-white p-6 shadow-lg">
      <h1 class="text-2xl font-bold">Blog Manager</h1>
      <p class="mt-2 text-indigo-100">Create, edit, and publish posts with rich text formatting.</p>
    </section>

    <section class="grid xl:grid-cols-5 gap-6">
      <article class="bg-white rounded-xl shadow border border-slate-200 p-6 xl:col-span-2">
        <h2 class="text-lg font-bold text-slate-900">Create Post</h2>
        <p class="text-sm text-slate-500 mt-1 mb-4">Slug auto-generates from title. You can override it.</p>

        <?php if ($info !== ''): ?>
        <p class="mb-3 text-sm rounded bg-emerald-50 text-emerald-700 px-3 py-2"><?php echo htmlspecialchars($info); ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
        <p class="mb-3 text-sm rounded bg-rose-50 text-rose-700 px-3 py-2"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="space-y-3" id="blogCreateForm">
          <?php echo rgcCsrfField('admin_blog'); ?>
          <input type="hidden" name="action" value="create">
          <div>
            <label class="text-sm font-medium text-slate-700">Title</label>
            <input id="blogTitle" name="title" value="<?php echo htmlspecialchars($title); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" required>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Slug</label>
            <input id="blogSlug" name="slug" value="<?php echo htmlspecialchars($slug); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="auto-generated">
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Banner Image</label>
            <input type="file" name="banner_file" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-1 block w-full cursor-pointer rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-slate-400 hover:bg-white hover:file:bg-slate-800">
            <p class="text-xs text-slate-500 mt-1">Optional. JPG, PNG, WEBP, or GIF up to 5MB.</p>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Content</label>
            <div id="editor" class="mt-1 bg-white border border-slate-300 rounded-lg min-h-[220px]"></div>
            <textarea id="contentField" name="content" class="hidden"><?php echo htmlspecialchars($content); ?></textarea>
          </div>
          <label class="text-sm rounded-lg border border-slate-200 px-3 py-2 bg-slate-50 block">
            <input type="checkbox" name="featured" class="mr-2" <?php echo $featured ? 'checked' : ''; ?>> Mark as featured
          </label>
          <button class="px-5 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Publish Post</button>
        </form>
      </article>

      <article class="bg-white rounded-xl shadow border border-slate-200 p-6 xl:col-span-3">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-bold text-slate-900">All Posts</h2>
          <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600"><?php echo (int) $total; ?> total</span>
        </div>

        <form method="get" class="mb-4 grid sm:grid-cols-4 gap-2">
          <input name="q" value="<?php echo htmlspecialchars($q); ?>" class="sm:col-span-2 border border-slate-300 rounded-lg px-3 py-2" placeholder="Search title or slug">
          <select name="featured" class="border border-slate-300 rounded-lg px-3 py-2">
            <option value="">All</option>
            <option value="1" <?php echo $featuredFilter === '1' ? 'selected' : ''; ?>>Featured</option>
            <option value="0" <?php echo $featuredFilter === '0' ? 'selected' : ''; ?>>Standard</option>
          </select>
          <button class="px-4 py-2 rounded-lg bg-slate-900 text-white">Filter</button>
        </form>

        <div class="space-y-3">
          <?php foreach ($rows as $row): ?>
          <article class="border border-slate-200 rounded-xl p-4">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <p class="font-semibold text-slate-900"><?php echo htmlspecialchars((string) $row['title']); ?></p>
                <p class="text-xs text-slate-500 mt-1"><?php echo htmlspecialchars((string) $row['slug']); ?></p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                  <span class="px-2 py-0.5 rounded-full <?php echo !empty($row['featured']) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'; ?>">
                    <?php echo !empty($row['featured']) ? 'Featured' : 'Standard'; ?>
                  </span>
                  <span class="text-slate-500"><?php echo htmlspecialchars((string) $row['created_at']); ?></span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <a href="<?php echo rgcUrl('admin/edit-blog.php?id=' . (int) $row['id']); ?>" class="px-3 py-1 text-sm rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100">Edit</a>
                <form method="post" onsubmit="return confirm('Delete this blog post?');">
                  <?php echo rgcCsrfField('admin_blog'); ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                  <button class="px-3 py-1 text-sm rounded-lg bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100">Delete</button>
                </form>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
          <?php if (empty($rows)): ?>
          <p class="text-sm text-slate-500">No blog posts yet.</p>
          <?php endif; ?>
        </div>

        <?php if ($pages > 1): ?>
        <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-200 text-sm">
          <a class="px-3 py-1.5 rounded border border-slate-300 bg-white <?php echo $page <= 1 ? 'opacity-50 pointer-events-none' : ''; ?>" href="<?php $p = $baseQs; $p['page'] = max(1, $page - 1); echo htmlspecialchars($baseUrl . '?' . http_build_query($p)); ?>">Prev</a>
          <span>Page <?php echo (int) $page; ?> of <?php echo (int) $pages; ?></span>
          <a class="px-3 py-1.5 rounded border border-slate-300 bg-white <?php echo $page >= $pages ? 'opacity-50 pointer-events-none' : ''; ?>" href="<?php $p = $baseQs; $p['page'] = min($pages, $page + 1); echo htmlspecialchars($baseUrl . '?' . http_build_query($p)); ?>">Next</a>
        </div>
        <?php endif; ?>
      </article>
    </section>
  </main>

  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
  <script>
    (function () {
      const title = document.getElementById('blogTitle');
      const slug = document.getElementById('blogSlug');
      const form = document.getElementById('blogCreateForm');
      const contentField = document.getElementById('contentField');
      const quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
          toolbar: {
            container: [
              [{ header: [1, 2, 3, false] }],
              ['bold', 'italic', 'underline', 'strike'],
              [{ list: 'ordered' }, { list: 'bullet' }],
              ['link', 'image', 'blockquote', 'code-block'],
              ['clean']
            ],
            handlers: {
              image: function () {
                const picker = document.createElement('input');
                picker.type = 'file';
                picker.accept = 'image/jpeg,image/png,image/webp,image/gif';
                picker.click();
                picker.addEventListener('change', async function () {
                  const file = picker.files && picker.files[0] ? picker.files[0] : null;
                  if (!file) return;
                  const body = new FormData();
                  body.append('image', file);
                  try {
                    const res = await fetch('<?php echo rgcUrl('admin/upload-blog-image.php'); ?>', {
                      method: 'POST',
                      body
                    });
                    const data = await res.json();
                    if (!res.ok || !data || !data.ok || !data.url) {
                      throw new Error((data && data.error) ? data.error : 'Image upload failed');
                    }
                    const range = quill.getSelection(true) || { index: quill.getLength(), length: 0 };
                    quill.insertEmbed(range.index, 'image', data.url, 'user');
                    quill.setSelection(range.index + 1, 0);
                  } catch (err) {
                    alert((err && err.message) ? err.message : 'Image upload failed');
                  }
                });
              }
            }
          }
        }
      });
      const initial = contentField.value || '';
      if (initial.trim() !== '') {
        quill.clipboard.dangerouslyPasteHTML(initial);
      }

      function slugify(value) {
        return (value || '')
          .toLowerCase()
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/^-+|-+$/g, '');
      }

      title.addEventListener('input', function () {
        if (slug.dataset.manual === '1') return;
        slug.value = slugify(title.value);
      });
      slug.addEventListener('input', function () {
        slug.dataset.manual = slug.value.trim() !== '' ? '1' : '0';
      });
      form.addEventListener('submit', function () {
        contentField.value = quill.root.innerHTML;
      });
    })();
  </script>
</body>
</html>
