<?php
require __DIR__ . '/includes/auth.php';
requireLogin();

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'No image uploaded']);
  exit;
}

$file = $_FILES['image'];
$err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($err !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Upload failed']);
  exit;
}

$tmpPath = (string) ($file['tmp_name'] ?? '');
$size = (int) ($file['size'] ?? 0);
if ($size <= 0 || $size > 5 * 1024 * 1024) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Image too large (max 5MB)']);
  exit;
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
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid image type']);
  exit;
}

$ext = $allowed[$mime];
$dir = dirname(__DIR__) . '/assets/uploads/blog/content';
if (!is_dir($dir)) {
  @mkdir($dir, 0775, true);
}
$fileName = 'content_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$dest = $dir . '/' . $fileName;
if (!@move_uploaded_file($tmpPath, $dest)) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Could not save image']);
  exit;
}

echo json_encode([
  'ok' => true,
  'url' => rgcUrl('assets/uploads/blog/content/' . $fileName),
]);

