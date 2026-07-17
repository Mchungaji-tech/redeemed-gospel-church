<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/app.php';

if (PHP_SAPI !== 'cli') {
  http_response_code(403);
  echo "Run this script from CLI only.\n";
  exit(1);
}

if (!rgcDbAvailable()) {
  fwrite(STDERR, "Database is not available. Check .env settings.\n");
  exit(1);
}

$map = [
  'settings.json' => rgcDefaultSettings(),
  'sermons.json' => [],
  'events.json' => [],
  'ministries.json' => [],
  'projects.json' => [],
  'testimonials.json' => [],
  'gallery.json' => [],
  'comments.json' => [],
  'prayer_requests.json' => [],
];

foreach ($map as $file => $fallback) {
  $path = RGC_ROOT_DIR . '/data/' . $file;
  if (!is_file($path)) {
    echo "[skip] {$file} not found.\n";
    continue;
  }
  $raw = file_get_contents($path);
  $decoded = json_decode((string) $raw, true);
  if (!is_array($decoded)) {
    echo "[skip] {$file} invalid JSON.\n";
    continue;
  }
  rgcSaveJson($file, $decoded);
  echo "[ok] Migrated {$file}\n";
}

echo "Migration complete.\n";
