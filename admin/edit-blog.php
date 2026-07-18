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

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
  http_response_code(404);
  echo 'Post not found.';
  exit;
}

$post = null;
$error = '';
$info = '';

if (!rgcDbAvailable()) {
  http_response_code(503);
  echo 'Database unavailable.';
  exit;
}

try {
  $stmt = rgcDb()->prepare('SELECT id, title, slug, content, banner, featured FROM blog_posts WHERE id = :id LIMIT 1');
  $stmt->execute([':id' => $id]);
  $post = $stmt->fetch();
} catch (Throwable $e) {
  $post = null;
}

if (!$post) {
  http_response_code(404);
  echo 'Post not found.';
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('admin_edit_blog');
  $title = trim((string) ($_POST['title'] ?? ''));
  $slugRaw = trim((string) ($_POST['slug'] ?? ''));
  $banner = (string) ($post['banner'] ?? '');
  $content = rgcSanitizeRichHtml((string) ($_POST['content'] ?? ''));
  $featured = isset($_POST['featured']) ? 1 : 0;
  $slug = rgcSlugify($slugRaw === '' ? $title : $slugRaw);
  [$uploadedBanner, $uploadError] = rgcHandleBlogBannerUpload('banner_file');
  if ($uploadError !== '') {
    $error = $uploadError;
  } elseif ($uploadedBanner !== '') {
    $banner = $uploadedBanner;
  }

  if ($error === '' && ($title === '' || $content === '')) {
    $error = 'Title and content are required.';
  } elseif ($error === '') {
    try {
      $up = rgcDb()->prepare(
        'UPDATE blog_posts
         SET title = :title, slug = :slug, content = :content, banner = :banner, featured = :featured
         WHERE id = :id'
      );
      $up->execute([
        ':title' => $title,
        ':slug' => $slug,
        ':content' => $content,
        ':banner' => $banner !== '' ? $banner : null,
        ':featured' => $featured,
        ':id' => $id,
      ]);
      header('Location: ' . rgcUrl('admin/blog.php?saved=1'));
      exit;
    } catch (PDOException $e) {
      if ((string) $e->getCode() === '23000') {
        $error = 'A post with this slug already exists - please edit the slug field.';
      } else {
        $error = 'Unable to save blog post changes.';
      }
    } catch (Throwable $e) {
      $error = 'Unable to save blog post changes.';
    }
  }

  $post['title'] = $title;
  $post['slug'] = $slug;
  $post['banner'] = $banner;
  $post['content'] = $content;
  $post['featured'] = $featured;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Blog Post</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-4xl mx-auto px-4 py-8 space-y-6">
    <section class="rounded-2xl bg-gradient-to-r from-indigo-700 via-indigo-600 to-sky-600 text-white p-6 shadow-lg">
      <h1 class="text-2xl font-bold">Edit Blog Post</h1>
      <p class="mt-2 text-indigo-100">Update title, slug, media, and content.</p>
    </section>

    <article class="bg-white rounded-xl shadow border border-slate-200 p-6">
      <?php if ($info !== ''): ?>
      <p class="mb-3 text-sm rounded bg-emerald-50 text-emerald-700 px-3 py-2"><?php echo htmlspecialchars($info); ?></p>
      <?php endif; ?>
      <?php if ($error !== ''): ?>
      <p class="mb-3 text-sm rounded bg-rose-50 text-rose-700 px-3 py-2"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="space-y-3" id="blogEditForm">
        <?php echo rgcCsrfField('admin_edit_blog'); ?>
        <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
        <div>
          <label class="text-sm font-medium text-slate-700">Title</label>
          <input id="blogTitle" name="title" value="<?php echo htmlspecialchars((string) $post['title']); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" required>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Slug</label>
          <input id="blogSlug" name="slug" value="<?php echo htmlspecialchars((string) $post['slug']); ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" required>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Banner Image</label>
          <input type="file" name="banner_file" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-1 block w-full cursor-pointer rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-slate-400 hover:bg-white hover:file:bg-slate-800">
          <p class="text-xs text-slate-500 mt-1">Optional. Uploading replaces the current banner.</p>
          <?php if (!empty($post['banner'])): ?>
          <p class="text-xs text-slate-500 mt-1">A banner image is currently set.</p>
          <?php endif; ?>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Content</label>
          <div id="editor" class="mt-1 bg-white border border-slate-300 rounded-lg min-h-[300px]"></div>
          <textarea id="contentField" name="content" class="hidden"><?php echo htmlspecialchars((string) $post['content']); ?></textarea>
        </div>
        <label class="text-sm rounded-lg border border-slate-200 px-3 py-2 bg-slate-50 block">
          <input type="checkbox" name="featured" class="mr-2" <?php echo !empty($post['featured']) ? 'checked' : ''; ?>> Mark as featured
        </label>
        <div class="flex items-center gap-2">
          <button class="px-5 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Save Changes</button>
          <a href="<?php echo rgcUrl('admin/blog.php'); ?>" class="px-5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700">Back</a>
        </div>
      </form>
    </article>
  </main>

  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
  <script>
    (function () {
      const form = document.getElementById('blogEditForm');
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

      form.addEventListener('submit', function () {
        contentField.value = quill.root.innerHTML;
      });
    })();
  </script>
</body>
</html>
