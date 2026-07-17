<?php
require __DIR__ . '/includes/auth.php';
requireLogin();

$u = rgcCurrentUser();
$userId = (int) ($u['id'] ?? 0);
$dbUser = rgcFindUserById($userId);
$name = (string) ($dbUser['full_name'] ?? ($u['name'] ?? ''));
$avatar = (string) ($dbUser['avatar'] ?? '');
$error = '';
$info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('admin_profile');
  $newName = trim((string) ($_POST['name'] ?? ''));
  if ($newName === '') {
    $error = 'Name cannot be empty.';
  } else {
    try {
      $stmt = rgcDb()->prepare('UPDATE users SET full_name = :name, updated_at = NOW() WHERE id = :id');
      $stmt->execute([':name' => $newName, ':id' => $userId]);
      $name = $newName;
      $_SESSION['user']['name'] = $newName;
      $info = 'Profile updated.';
    } catch (Throwable $e) {
      $error = 'Unable to update profile name.';
    }
  }

  if (isset($_FILES['avatar']) && is_array($_FILES['avatar']) && (int) ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['avatar'];
    if ((int) ($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
      $error = 'Upload failed. Try a smaller image.';
    } else {
      $tmpPath = (string) ($file['tmp_name'] ?? '');
      $size = (int) ($file['size'] ?? 0);
      if ($size <= 0 || $size > 2 * 1024 * 1024) {
        $error = 'Image too large. Max 2MB.';
      } else {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $tmpPath) : null;
        if ($finfo) { finfo_close($finfo); }
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($allowed[$mime ?? ''])) {
          $error = 'Only JPG or PNG images are allowed.';
        } else {
          $ext = $allowed[$mime];
          $dir = dirname(__DIR__) . '/assets/uploads/avatars';
          if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
          }
          $fileName = 'u' . $userId . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
          $dest = $dir . '/' . $fileName;
          if (@move_uploaded_file($tmpPath, $dest)) {
            $doResize = function_exists('imagecreatetruecolor');
            if ($doResize) {
              $img = null;
              if ($mime === 'image/jpeg') { $img = @imagecreatefromjpeg($dest); }
              elseif ($mime === 'image/png') { $img = @imagecreatefrompng($dest); }
              if ($img) {
                $w = imagesx($img);
                $h = imagesy($img);
                $side = min($w, $h);
                $srcX = (int) max(0, ($w - $side) / 2);
                $srcY = (int) max(0, ($h - $side) / 2);
                $targetSize = 256;
                $dst = imagecreatetruecolor($targetSize, $targetSize);
                imagecopyresampled($dst, $img, 0, 0, $srcX, $srcY, $targetSize, $targetSize, $side, $side);
                if ($mime === 'image/jpeg') { @imagejpeg($dst, $dest, 85); }
                else { @imagepng($dst, $dest); }
                imagedestroy($dst);
                imagedestroy($img);
              }
            }
            $publicPath = rgcUrl('assets/uploads/avatars/' . $fileName);
            try {
              $stmt = rgcDb()->prepare('UPDATE users SET avatar = :avatar, updated_at = NOW() WHERE id = :id');
              $stmt->execute([':avatar' => $publicPath, ':id' => $userId]);
              $avatar = $publicPath;
              $_SESSION['user']['avatar'] = $publicPath;
              $info = $info ? $info . ' Avatar updated.' : 'Avatar updated.';
            } catch (Throwable $e) {
              $error = 'Saved image but failed to update profile.';
            }
          } else {
            $error = 'Failed to save image to uploads.';
          }
        }
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-4xl mx-auto px-4 py-8 space-y-6">
    <section class="rounded-2xl bg-gradient-to-r from-indigo-700 via-indigo-600 to-sky-600 text-white p-6 shadow-lg">
      <h1 class="text-2xl font-bold">My Profile</h1>
      <p class="mt-2 text-indigo-100">Update your display name and profile picture.</p>
    </section>

    <section class="grid md:grid-cols-3 gap-6">
      <article class="bg-white rounded-xl shadow border border-slate-200 p-6">
        <div class="flex flex-col items-center">
          <div class="w-32 h-32 rounded-full overflow-hidden border border-slate-200 bg-slate-50">
            <?php if ($avatar): ?>
              <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" class="w-full h-full object-cover">
            <?php else: ?>
              <div class="w-full h-full flex items-center justify-center text-slate-400">No Photo</div>
            <?php endif; ?>
          </div>
          <p class="mt-3 font-semibold text-slate-900"><?php echo htmlspecialchars($name); ?></p>
          <p class="text-xs text-slate-500 mt-1"><?php echo htmlspecialchars($u['username'] ?? ''); ?> · <?php echo htmlspecialchars($u['role'] ?? ''); ?></p>
        </div>
      </article>

      <article class="md:col-span-2 bg-white rounded-xl shadow border border-slate-200 p-6">
        <?php if ($info): ?>
        <div class="mb-3 p-3 rounded-lg text-sm bg-emerald-50 text-emerald-700 border border-emerald-200"><?php echo htmlspecialchars($info); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="mb-3 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="space-y-4">
          <?php echo rgcCsrfField('admin_profile'); ?>
          <div>
            <label class="text-sm font-medium text-slate-700">Display Name</label>
            <input name="name" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" value="<?php echo htmlspecialchars($name); ?>" required>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Profile Photo (JPG/PNG, ≤2MB)</label>
            <input type="file" name="avatar" accept="image/jpeg,image/png" class="mt-1 block w-full cursor-pointer rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-slate-400 hover:bg-white hover:file:bg-slate-800">
          </div>
          <div class="flex gap-2">
            <button class="px-5 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Save Changes</button>
            <a href="<?php echo rgcUrl('admin/dashboard.php'); ?>" class="px-5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700">Back to Dashboard</a>
          </div>
        </form>
      </article>
    </section>
  </main>
</body>
</html>
