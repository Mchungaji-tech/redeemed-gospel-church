<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/**
 * Polyfills for PHP < 8.0 compatibility
 */
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle !== '' && substr($haystack, -strlen($needle)) === (string)$needle;
    }
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/data.php';

// IP Geolocation function to detect country from IP
function rgcGetCountryFromIP(string $ip = ''): string {
  if (empty($ip)) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
  }
  
  // Skip private/local IPs - common in cPanel hosting
  if (empty($ip) || in_array($ip, ['127.0.0.1', '::1', 'localhost', '::ffff:127.0.0.1']) || 
      strpos($ip, '192.168.') === 0 || 
      strpos($ip, '10.') === 0 ||
      strpos($ip, '172.16.') === 0 ||
      strpos($ip, '172.17.') === 0 ||
      strpos($ip, '172.18.') === 0 ||
      strpos($ip, '172.19.') === 0 ||
      strpos($ip, '172.2') === 0 ||
      strpos($ip, '172.30.') === 0 ||
      strpos($ip, '172.31.') === 0) {
    return '';
  }
  
  // Use ip-api.com free service (40 requests/minute, 1000/day)
  $url = 'http://ip-api.com/json/' . urlencode($ip) . '?fields=country';
  $response = false;
  
  // Try file_get_contents first
  if (ini_get('allow_url_fopen')) {
    $response = @file_get_contents($url);
  }
  
  // Fallback to cURL if available
  if (!$response && function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    curl_close($ch);
  }
  
  if ($response) {
    $data = json_decode($response, true);
    if (!empty($data['country'])) {
      return (string) $data['country'];
    }
  }
  
  return '';
}

// Get country code for flag emoji
function rgcGetCountryCode(string $countryName = ''): string {
  $countryCodes = [
    'Kenya' => 'KE', 'United States' => 'US', 'United Kingdom' => 'GB',
    'Nigeria' => 'NG', 'Uganda' => 'UG', 'Tanzania' => 'TZ',
    'South Africa' => 'ZA', 'Ghana' => 'GH', 'Rwanda' => 'RW',
    'Ethiopia' => 'ET', 'Sudan' => 'SD', 'Egypt' => 'EG',
    'India' => 'IN', 'China' => 'CN', 'Germany' => 'DE',
    'France' => 'FR', 'Italy' => 'IT', 'Spain' => 'ES',
    'Netherlands' => 'NL', 'Belgium' => 'BE', 'Canada' => 'CA',
    'Australia' => 'AU', 'Japan' => 'JP', 'Brazil' => 'BR',
    'Mexico' => 'MX', 'Argentina' => 'AR', 'Chile' => 'CL',
    'Colombia' => 'CO', 'Peru' => 'PE', 'Venezuela' => 'VE',
    'Portugal' => 'PT', 'Greece' => 'GR', 'Poland' => 'PL',
    'Russia' => 'RU', 'Ukraine' => 'UA', 'Turkey' => 'TR',
    'Saudi Arabia' => 'SA', 'UAE' => 'AE', 'Israel' => 'IL',
    'South Korea' => 'KR', 'Thailand' => 'TH', 'Indonesia' => 'ID',
    'Malaysia' => 'MY', 'Philippines' => 'PH', 'Singapore' => 'SG',
    'New Zealand' => 'NZ', 'Ireland' => 'IE', 'Sweden' => 'SE',
    'Norway' => 'NO', 'Denmark' => 'DK', 'Finland' => 'FI',
    'Switzerland' => 'CH', 'Austria' => 'AT', 'Czech Republic' => 'CZ',
  ];
  return $countryCodes[$countryName] ?? '';
}

// Convert country code to flag emoji
function rgcCountryToFlag(string $countryCode): string {
  if (strlen($countryCode) !== 2) {
    return '';
  }
  $offset = ord(strtoupper($countryCode[0])) - ord('A');
  $flag = '';
  if ($offset >= 0 && $offset <= 25) {
    $flag .= chr($offset + 0x1F1E6);
  }
  $offset = ord(strtoupper($countryCode[1])) - ord('A');
  if ($offset >= 0 && $offset <= 25) {
    $flag .= chr($offset + 0x1F1E6);
  }
  return $flag;
}

function getMinistryIconSvg(string $iconName): string {
    $icons = [
        'music' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>',
        'users' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
        'share' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'user' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
        'heart' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
        'home' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
        'food' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
        'building' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
        'graduation' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.4-3.2M12 14l-6.4-3.2M12 14v7"/></svg>',
        'default' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>'
    ];
    return $icons[$iconName] ?? $icons['default'];
}

function getProjectIcon(string $iconName): string {
    $icons = [
        'food' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
        'building' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
        'graduation' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.4-3.2M12 14l-6.4-3.2M12 14v7"/></svg>',
        'users' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
        'heart' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
        'home' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
        'default' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>'
    ];
    return $icons[$iconName] ?? $icons['default'];
}

function rgcJsonTableMap(): array {
  static $map = null;
  if ($map !== null) {
    return $map;
  }

  $map = [
    'sermons.json' => [
      'table' => 'sermons',
      'columns' => ['id', 'title', 'speaker', 'youtube_url', 'facebook_embed', 'scheduled_at', 'is_live', 'featured', 'created_at'],
      'bool_columns' => ['is_live', 'featured'],
    ],
    'events.json' => [
      'table' => 'events',
      'columns' => ['id', 'title', 'description', 'location', 'poster', 'event_at', 'created_at'],
      'bool_columns' => [],
    ],
    'ministries.json' => [
      'table' => 'ministries',
      'columns' => ['id', 'name', 'description', 'created_at'],
      'bool_columns' => [],
    ],
    'projects.json' => [
      'table' => 'projects',
      'columns' => ['id', 'title', 'description', 'created_at'],
      'bool_columns' => [],
    ],
    'testimonials.json' => [
      'table' => 'testimonials',
      'columns' => ['id', 'name', 'message', 'created_at'],
      'bool_columns' => [],
    ],
    'gallery.json' => [
      'table' => 'gallery',
      'columns' => ['id', 'image', 'caption', 'created_at'],
      'bool_columns' => [],
    ],
    'comments.json' => [
      'table' => 'comments',
      'columns' => ['id', 'sermon_id', 'name', 'comment', 'created_at'],
      'bool_columns' => [],
    ],
    'prayer_requests.json' => [
      'table' => 'prayer_requests',
      'columns' => ['id', 'name', 'email', 'request', 'created_at'],
      'bool_columns' => [],
    ],
  ];

  return $map;
}

function rgcDefaultSettings(): array {
  return [
    'maintenance_mode' => false,
    'maintenance_message' => 'We are performing maintenance. Please check back shortly.',
    'broadcast_enabled' => true,
    'broadcast_message' => 'Welcome to Redeemed Gospel Church Eldoret. Sunday Service: 9:00 AM.',
    'force_logout_at' => 0,
    'maintenance_bypass_minutes' => 30,
  ];
}

function rgcLoadSettingsFromDb(array $default): array {
  if (!rgcDbAvailable()) {
    return $default;
  }
  try {
    $stmt = rgcDb()->query('SELECT maintenance_mode, maintenance_message, broadcast_enabled, broadcast_message, force_logout_at, maintenance_bypass_minutes FROM app_settings WHERE id = 1 LIMIT 1');
    $row = $stmt->fetch();
  } catch (Throwable $e) {
    return $default;
  }
  if (!$row) {
    return $default;
  }
  return [
    'maintenance_mode' => !empty($row['maintenance_mode']),
    'maintenance_message' => (string) ($row['maintenance_message'] ?? $default['maintenance_message']),
    'broadcast_enabled' => !empty($row['broadcast_enabled']),
    'broadcast_message' => (string) ($row['broadcast_message'] ?? $default['broadcast_message']),
    'force_logout_at' => (int) ($row['force_logout_at'] ?? 0),
    'maintenance_bypass_minutes' => (int) ($row['maintenance_bypass_minutes'] ?? $default['maintenance_bypass_minutes']),
  ];
}

function rgcSaveSettingsToDb(array $data): void {
  if (!rgcDbAvailable()) {
    return;
  }
  $defaults = rgcDefaultSettings();
  $payload = [
    'maintenance_mode' => !empty($data['maintenance_mode']) ? 1 : 0,
    'maintenance_message' => (string) ($data['maintenance_message'] ?? $defaults['maintenance_message']),
    'broadcast_enabled' => !empty($data['broadcast_enabled']) ? 1 : 0,
    'broadcast_message' => (string) ($data['broadcast_message'] ?? $defaults['broadcast_message']),
    'force_logout_at' => (int) ($data['force_logout_at'] ?? 0),
    'maintenance_bypass_minutes' => (int) ($data['maintenance_bypass_minutes'] ?? $defaults['maintenance_bypass_minutes']),
  ];
  $sql = 'INSERT INTO app_settings (id, maintenance_mode, maintenance_message, broadcast_enabled, broadcast_message, force_logout_at, maintenance_bypass_minutes, updated_at)
          VALUES (1, :maintenance_mode, :maintenance_message, :broadcast_enabled, :broadcast_message, :force_logout_at, :maintenance_bypass_minutes, NOW())
          ON DUPLICATE KEY UPDATE
            maintenance_mode = VALUES(maintenance_mode),
            maintenance_message = VALUES(maintenance_message),
            broadcast_enabled = VALUES(broadcast_enabled),
            broadcast_message = VALUES(broadcast_message),
            force_logout_at = VALUES(force_logout_at),
            maintenance_bypass_minutes = :maintenance_bypass_minutes,
            updated_at = NOW()';
  try {
    $stmt = rgcDb()->prepare($sql);
    $stmt->execute([
      ':maintenance_mode' => $payload['maintenance_mode'],
      ':maintenance_message' => $payload['maintenance_message'],
      ':broadcast_enabled' => $payload['broadcast_enabled'],
      ':broadcast_message' => $payload['broadcast_message'],
      ':force_logout_at' => $payload['force_logout_at'],
      ':maintenance_bypass_minutes' => $payload['maintenance_bypass_minutes'],
    ]);
  } catch (Throwable $e) {
    return;
  }
}

/**
 * Simple file-based cache for database queries.
 */
function rgcCacheGet(string $key) {
    $cacheFile = __DIR__ . '/../data/cache/' . md5($key) . '.cache';
    if (file_exists($cacheFile)) {
        $data = unserialize(file_get_contents($cacheFile));
        if ($data['expires'] > time()) {
            return $data['content'];
        }
        unlink($cacheFile);
    }
    return null;
}

function rgcCacheSet(string $key, $content, int $ttl = 3600): void {
    $cacheDir = __DIR__ . '/../data/cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    $cacheFile = $cacheDir . '/' . md5($key) . '.cache';
    $data = [
        'expires' => time() + $ttl,
        'content' => $content
    ];
    file_put_contents($cacheFile, serialize($data));
}

function rgcCacheClear(): void {
    $cacheDir = __DIR__ . '/../data/cache';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}

function rgcLoadRowsFromDb(string $file, array $default): array {
  if (!rgcDbAvailable()) {
    return $default;
  }
  
  $cacheKey = "db_rows_$file";
  $cached = rgcCacheGet($cacheKey);
  if ($cached !== null) {
    return $cached;
  }

  $map = rgcJsonTableMap()[$file] ?? null;
  if ($map === null) {
    return $default;
  }
  $columns = implode(', ', $map['columns']);
  $table = $map['table'];
  try {
    $stmt = rgcDb()->query("SELECT {$columns} FROM {$table} ORDER BY id ASC");
    $rows = $stmt->fetchAll();
  } catch (Throwable $e) {
    return $default;
  }
  foreach ($rows as &$row) {
    foreach ($map['bool_columns'] as $boolCol) {
      if (array_key_exists($boolCol, $row)) {
        $row[$boolCol] = !empty($row[$boolCol]);
      }
    }
  }
  unset($row);
  
  rgcCacheSet($cacheKey, $rows);
  return $rows;
}

function rgcSaveRowsToDb(string $file, array $data): void {
  rgcCacheClear();
  if (!rgcDbAvailable()) {
    return;
  }
  $map = rgcJsonTableMap()[$file] ?? null;
  if ($map === null) {
    return;
  }

  $table = $map['table'];
  $columns = $map['columns'];
  $boolColumns = $map['bool_columns'];

  try {
    rgcDb()->beginTransaction();
  } catch (Throwable $e) {
    return;
  }
  try {
    rgcDb()->exec("DELETE FROM {$table}");

    foreach ($data as $row) {
      if (!is_array($row)) {
        continue;
      }
      $insertCols = [];
      $params = [];

      foreach ($columns as $column) {
        if ($column === 'id') {
          $idValue = (int) ($row['id'] ?? 0);
          if ($idValue > 0) {
            $insertCols[] = 'id';
            $params[':id'] = $idValue;
          }
          continue;
        }
        if ($column === 'created_at') {
          $createdAt = trim((string) ($row['created_at'] ?? ''));
          if ($createdAt !== '') {
            $insertCols[] = 'created_at';
            $params[':created_at'] = $createdAt;
          }
          continue;
        }
        if (in_array($column, $boolColumns, true)) {
          $insertCols[] = $column;
          $params[':' . $column] = !empty($row[$column]) ? 1 : 0;
          continue;
        }
        $insertCols[] = $column;
        $params[':' . $column] = isset($row[$column]) ? (string) $row[$column] : '';
      }

      if (empty($insertCols)) {
        continue;
      }

      $placeholders = array_map(static fn($col) => ':' . $col, $insertCols);
      $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $table,
        implode(', ', $insertCols),
        implode(', ', $placeholders)
      );
      $stmt = rgcDb()->prepare($sql);
      $stmt->execute($params);
    }

    rgcDb()->commit();
  } catch (Throwable $e) {
    if (rgcDb()->inTransaction()) {
      rgcDb()->rollBack();
    }
    return;
  }
}

function rgcLoadJson($file, $default) {
  if ($file === 'settings.json') {
    return rgcLoadSettingsFromDb(is_array($default) ? $default : rgcDefaultSettings());
  }
  if ($file === 'footer.json') {
    return rgcLoadFooterFromDb(is_array($default) ? $default : []);
  }
  if (isset(rgcJsonTableMap()[$file])) {
    $dbRows = rgcLoadRowsFromDb($file, is_array($default) ? $default : []);
    if (!empty($dbRows)) {
      return $dbRows;
    }
  }
  
  // Special case for non-table JSON files
  $filePath = RGC_DATA_DIR . '/' . $file;
  if (file_exists($filePath)) {
    $content = file_get_contents($filePath);
    $data = json_decode($content, true);
    if (json_last_error() === JSON_ERROR_NONE) {
      return $data;
    }
  }
  
  return is_array($default) ? $default : [];
}

function rgcSaveJson($file, $data) {
  if ($file === 'settings.json') {
    rgcSaveSettingsToDb(is_array($data) ? $data : []);
    return;
  }
  if ($file === 'footer.json') {
    rgcSaveFooterToDb(is_array($data) ? $data : []);
    return;
  }
  if (isset(rgcJsonTableMap()[$file])) {
    rgcSaveRowsToDb($file, is_array($data) ? $data : []);
    return;
  }

  // Special case for non-table JSON files
  $filePath = RGC_DATA_DIR . '/' . $file;
  file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
}

function rgcLoadFooterFromDb(array $default): array {
    if (!rgcDbAvailable()) {
        return $default;
    }
    try {
        $stmt = rgcDb()->query('SELECT * FROM site_footer WHERE id = 1 LIMIT 1');
        $row = $stmt->fetch();
        if ($row) {
            return [
                'church_name' => $row['church_name'],
                'tagline' => $row['tagline'],
                'service_times' => json_decode($row['service_times'], true) ?: [],
                'quick_links' => json_decode($row['quick_links'], true) ?: [],
                'contact' => [
                    'address' => $row['address'],
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'whatsapp' => $row['whatsapp']
                ],
                'designer_credit' => $row['designer_credit'],
                'copyright_year' => $row['copyright_year']
            ];
        }
    } catch (Throwable $e) {
        return $default;
    }
    return $default;
}

function rgcSaveFooterToDb(array $data): void {
    if (!rgcDbAvailable()) {
        return;
    }
    try {
        $stmt = rgcDb()->prepare("
            INSERT INTO site_footer (id, church_name, tagline, service_times, quick_links, address, email, phone, whatsapp, designer_credit, copyright_year)
            VALUES (1, :church_name, :tagline, :service_times, :quick_links, :address, :email, :phone, :whatsapp, :designer_credit, :copyright_year)
            ON DUPLICATE KEY UPDATE
                church_name = VALUES(church_name),
                tagline = VALUES(tagline),
                service_times = VALUES(service_times),
                quick_links = VALUES(quick_links),
                address = VALUES(address),
                email = VALUES(email),
                phone = VALUES(phone),
                whatsapp = VALUES(whatsapp),
                designer_credit = VALUES(designer_credit),
                copyright_year = VALUES(copyright_year)
        ");
        $stmt->execute([
            ':church_name' => $data['church_name'] ?? '',
            ':tagline' => $data['tagline'] ?? '',
            ':service_times' => json_encode($data['service_times'] ?? []),
            ':quick_links' => json_encode($data['quick_links'] ?? []),
            ':address' => $data['contact']['address'] ?? '',
            ':email' => $data['contact']['email'] ?? '',
            ':phone' => $data['contact']['phone'] ?? '',
            ':whatsapp' => $data['contact']['whatsapp'] ?? '',
            ':designer_credit' => $data['designer_credit'] ?? '',
            ':copyright_year' => $data['copyright_year'] ?? date('Y')
        ]);
    } catch (Throwable $e) {
        return;
    }
}

function rgcRecordOutboundEmail(string $mailTo, string $subject, string $body, string $status, ?string $error = null): void {
  if (!rgcDbAvailable()) {
    return;
  }
  try {
    $stmt = rgcDb()->prepare(
      'INSERT INTO outbound_emails (mail_to, subject, body, status, error_message, created_at)
       VALUES (:mail_to, :subject, :body, :status, :error_message, NOW())'
    );
    $stmt->execute([
      ':mail_to' => $mailTo,
      ':subject' => $subject,
      ':body' => $body,
      ':status' => $status,
      ':error_message' => $error,
    ]);
  } catch (Throwable $e) {
    return;
  }
}

function rgcMailEncodeHeader(string $value): string {
  $value = trim($value);
  if ($value === '') {
    return '';
  }
  if (function_exists('mb_encode_mimeheader')) {
    return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
  }
  return $value;
}

function rgcMailFormatAddress(string $email, string $name = ''): string {
  $email = trim($email);
  $name = trim($name);
  if ($name === '') {
    return $email;
  }
  return rgcMailEncodeHeader($name) . ' <' . $email . '>';
}

function rgcSmtpReadResponse($socket): array {
  $response = '';
  $code = 0;
  while (!feof($socket)) {
    $line = fgets($socket, 515);
    if ($line === false) {
      break;
    }
    $response .= $line;
    if (preg_match('/^(\d{3})([\s-])/', $line, $matches)) {
      $code = (int) $matches[1];
      if ($matches[2] === ' ') {
        break;
      }
    } else {
      break;
    }
  }
  return [$code, trim($response)];
}

function rgcSmtpExpect($socket, array $expectedCodes, string $action): void {
  [$code, $response] = rgcSmtpReadResponse($socket);
  if (!in_array($code, $expectedCodes, true)) {
    throw new RuntimeException($action . ' failed: ' . ($response !== '' ? $response : 'No SMTP response'));
  }
}

function rgcSmtpCommand($socket, string $command, array $expectedCodes, string $action): void {
  $bytes = fwrite($socket, $command . "\r\n");
  if ($bytes === false) {
    throw new RuntimeException($action . ' failed: could not write to SMTP socket');
  }
  rgcSmtpExpect($socket, $expectedCodes, $action);
}

function rgcSmtpNormalizeBody(string $body): string {
  $body = str_replace(["\r\n", "\r"], "\n", $body);
  $lines = explode("\n", $body);
  foreach ($lines as &$line) {
    if (str_starts_with($line, '.')) {
      $line = '.' . $line;
    }
  }
  unset($line);
  return implode("\r\n", $lines);
}

function rgcSmtpSendMail(string $mailTo, string $subject, string $body): void {
  $host = trim((string) rgcConfig('smtp.host', ''));
  $port = (int) rgcConfig('smtp.port', 587);
  $username = trim((string) rgcConfig('smtp.username', ''));
  $password = (string) rgcConfig('smtp.password', '');
  $encryption = strtolower(trim((string) rgcConfig('smtp.encryption', 'tls')));
  $timeout = max(5, (int) rgcConfig('smtp.timeout', 15));
  $from = trim((string) rgcConfig('mail.from', ''));
  $fromName = trim((string) rgcConfig('mail.from_name', 'Redeemed Gospel Church'));

  if ($host === '') {
    throw new RuntimeException('SMTP host is missing. Set RGC_SMTP_HOST.');
  }
  if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
    throw new RuntimeException('Mail from address is invalid. Set RGC_MAIL_FROM.');
  }
  if (!filter_var($mailTo, FILTER_VALIDATE_EMAIL)) {
    throw new RuntimeException('Recipient email address is invalid.');
  }

  $transport = $host;
  if ($encryption === 'ssl') {
    $transport = 'ssl://' . $host;
  } elseif (!in_array($encryption, ['', 'none', 'tls'], true)) {
    throw new RuntimeException('Unsupported SMTP encryption. Use tls, ssl, or none.');
  }

  $context = stream_context_create([
    'ssl' => [
      'verify_peer' => true,
      'verify_peer_name' => true,
      'allow_self_signed' => false,
      'SNI_enabled' => true,
      'peer_name' => $host,
    ],
  ]);

  $socket = @stream_socket_client(
    $transport . ':' . $port,
    $errno,
    $errstr,
    $timeout,
    STREAM_CLIENT_CONNECT,
    $context
  );

  if (!is_resource($socket)) {
    throw new RuntimeException('SMTP connection failed: ' . $errstr . ' (' . $errno . ')');
  }

  try {
    stream_set_timeout($socket, $timeout);
    rgcSmtpExpect($socket, [220], 'SMTP connect');

    $helloDomain = trim((string) ($_SERVER['SERVER_NAME'] ?? 'localhost'));
    if ($helloDomain === '' || !preg_match('/^[a-z0-9.-]+$/i', $helloDomain)) {
      $helloDomain = 'localhost';
    }

    rgcSmtpCommand($socket, 'EHLO ' . $helloDomain, [250], 'SMTP EHLO');

    if ($encryption === 'tls') {
      rgcSmtpCommand($socket, 'STARTTLS', [220], 'SMTP STARTTLS');
      $cryptoEnabled = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
      if ($cryptoEnabled !== true) {
        throw new RuntimeException('SMTP STARTTLS negotiation failed.');
      }
      rgcSmtpCommand($socket, 'EHLO ' . $helloDomain, [250], 'SMTP EHLO after STARTTLS');
    }

    if ($username !== '') {
      rgcSmtpCommand($socket, 'AUTH LOGIN', [334], 'SMTP AUTH LOGIN');
      rgcSmtpCommand($socket, base64_encode($username), [334], 'SMTP AUTH username');
      rgcSmtpCommand($socket, base64_encode($password), [235], 'SMTP AUTH password');
    }

    rgcSmtpCommand($socket, 'MAIL FROM:<' . $from . '>', [250], 'SMTP MAIL FROM');
    rgcSmtpCommand($socket, 'RCPT TO:<' . $mailTo . '>', [250, 251], 'SMTP RCPT TO');
    rgcSmtpCommand($socket, 'DATA', [354], 'SMTP DATA');

    $headers = [
      'Date: ' . date(DATE_RFC2822),
      'From: ' . rgcMailFormatAddress($from, $fromName),
      'To: ' . $mailTo,
      'Subject: ' . rgcMailEncodeHeader($subject),
      'MIME-Version: 1.0',
      'Content-Type: text/plain; charset=UTF-8',
      'Content-Transfer-Encoding: 8bit',
    ];

    $message = implode("\r\n", $headers) . "\r\n\r\n" . rgcSmtpNormalizeBody($body) . "\r\n.\r\n";
    $bytes = fwrite($socket, $message);
    if ($bytes === false) {
      throw new RuntimeException('SMTP DATA send failed.');
    }
    rgcSmtpExpect($socket, [250], 'SMTP message delivery');
    rgcSmtpCommand($socket, 'QUIT', [221], 'SMTP QUIT');
  } finally {
    fclose($socket);
  }
}

function rgcSendMail(string $mailTo, string $subject, string $body): bool {
  $mode = strtolower((string) rgcConfig('mail.mode', 'test'));
  $from = (string) rgcConfig('mail.from', 'no-reply@redeemed.local');

  if ($mode === 'smtp') {
    try {
      rgcSmtpSendMail($mailTo, $subject, $body);
      rgcRecordOutboundEmail($mailTo, $subject, $body, 'sent', null);
      return true;
    } catch (Throwable $e) {
      rgcRecordOutboundEmail($mailTo, $subject, $body, 'failed', $e->getMessage());
      return false;
    }
  }

  if ($mode === 'phpmail') {
    $headers = 'From: ' . $from;
    $sent = @mail($mailTo, $subject, $body, $headers);
    rgcRecordOutboundEmail($mailTo, $subject, $body, $sent ? 'sent' : 'failed', $sent ? null : 'mail() returned false');
    return $sent;
  }

  rgcRecordOutboundEmail($mailTo, $subject, $body, 'queued_test', null);
  return true;
}

function rgcCsrfToken(string $scope = 'default'): string {
  if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
    $_SESSION['csrf_tokens'] = [];
  }
  if (empty($_SESSION['csrf_tokens'][$scope])) {
    $_SESSION['csrf_tokens'][$scope] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_tokens'][$scope];
}

function rgcCsrfField(string $scope = 'default'): string {
  $token = rgcCsrfToken($scope);
  return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function rgcVerifyCsrf(string $scope = 'default'): bool {
  $token = (string) ($_POST['_csrf'] ?? '');
  $expected = $_SESSION['csrf_tokens'][$scope] ?? '';
  return $token !== '' && $expected !== '' && hash_equals($expected, $token);
}

function rgcRequireCsrf(string $scope = 'default'): void {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
  }
  if (rgcVerifyCsrf($scope)) {
    return;
  }
  http_response_code(419);
  echo 'CSRF token mismatch. Refresh and try again.';
  exit;
}

$rgcSettings = rgcLoadJson('settings.json', rgcDefaultSettings());

$rgcBaseUrl = rgcConfig('app.base_url', '');
if ($rgcBaseUrl === '') {
    // Attempt to detect base folder if not set in .env
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $phpSelf = $_SERVER['PHP_SELF'] ?? '';
    
    // Use SCRIPT_NAME as it's more reliable for the actual file being executed
    $detectPath = $scriptName ?: $phpSelf;
    $rgcBaseUrl = str_replace('\\', '/', dirname($detectPath));
    
    // If we are in a subdirectory like /admin/ or /api/, go up one level
    // This is a simple way to get the root folder
    if (str_ends_with($rgcBaseUrl, '/admin') || str_ends_with($rgcBaseUrl, '/api') || str_ends_with($rgcBaseUrl, '/user')) {
        $rgcBaseUrl = dirname($rgcBaseUrl);
    }
}
$rgcBaseUrl = rtrim(str_replace('\\', '/', $rgcBaseUrl), '/');

/**
 * Returns the path to an asset, preferring the minified version if it exists.
 */
function rgcAsset(string $path): string {
    $minPath = str_replace(['.css', '.js'], ['.min.css', '.min.js'], $path);
    if (file_exists(RGC_ROOT_DIR . '/' . ltrim($minPath, '/'))) {
        return rgcUrl($minPath);
    }
    return rgcUrl($path);
}

function rgcUrl(string $path): string {
    global $rgcBaseUrl;
    $path = ltrim($path, '/');
    
    // If rgcBaseUrl is just '/', we don't want to double slash
    if ($rgcBaseUrl === '' || $rgcBaseUrl === '/') {
        return '/' . $path;
    }
    
    // Remove the base URL prefix if it's already in the path to prevent double prefixing
    $baseUrlPath = ltrim(parse_url($rgcBaseUrl, PHP_URL_PATH) ?? '', '/');
    if ($baseUrlPath !== '' && str_starts_with($path, $baseUrlPath . '/')) {
        $path = substr($path, strlen($baseUrlPath) + 1);
    }
    
    return $rgcBaseUrl . '/' . $path;
}

/**
 * Optimizes an uploaded image: compresses it and converts to WebP if possible.
 * Returns the relative path to the optimized image.
 */
function rgcOptimizeImage(string $sourcePath, string $targetDir, string $prefix = 'img'): string {
    if (!file_exists($sourcePath)) return '';
    
    $info = getimagesize($sourcePath);
    if (!$info) return '';
    
    $mime = $info['mime'];
    $width = $info[0];
    $height = $info[1];
    
    // Create image resource
    switch ($mime) {
        case 'image/jpeg': $src = imagecreatefromjpeg($sourcePath); break;
        case 'image/png':  $src = imagecreatefrompng($sourcePath);  break;
        case 'image/webp': $src = imagecreatefromwebp($sourcePath); break;
        default: return ''; // Unsupported
    }
    
    if (!$src) return '';

    // Max dimensions (e.g., 1920px)
    $maxDim = 1920;
    if ($width > $maxDim || $height > $maxDim) {
        $ratio = $width / $height;
        if ($ratio > 1) {
            $newWidth = $maxDim;
            $newHeight = $maxDim / $ratio;
        } else {
            $newHeight = $maxDim;
            $newWidth = $maxDim * $ratio;
        }
        $dst = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG
        if ($mime === 'image/png') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($src);
        $src = $dst;
    }

    // Generate filename
    $filename = $prefix . '_' . bin2hex(random_bytes(4)) . '.webp';
    $relativePath = 'assets/uploads/' . $filename;
    $fullPath = rtrim($targetDir, '/') . '/' . $filename;

    // Save as WebP (quality 80)
    if (function_exists('imagewebp')) {
        imagewebp($src, $fullPath, 80);
    } else {
        // Fallback to JPEG if WebP not supported
        $filename = str_replace('.webp', '.jpg', $filename);
        $relativePath = 'assets/uploads/' . $filename;
        $fullPath = rtrim($targetDir, '/') . '/' . $filename;
        imagejpeg($src, $fullPath, 80);
    }

    imagedestroy($src);
    return $relativePath;
}

function rgcMaintenanceOn() {
  global $rgcSettings;
  return !empty($rgcSettings['maintenance_mode']);
}

function rgcBroadcastMessage() {
  global $rgcSettings;
  if (!empty($rgcSettings['broadcast_enabled']) && !empty($rgcSettings['broadcast_message'])) {
    return (string) $rgcSettings['broadcast_message'];
  }
  return '';
}

function rgcNextId($rows) {
  $max = 0;
  foreach ($rows as $row) {
    $id = (int) ($row['id'] ?? 0);
    if ($id > $max) {
      $max = $id;
    }
  }
  return $max + 1;
}

function rgcCurrentUser() {
  return $_SESSION['user'] ?? null;
}

function rgcIsSuperAdmin() {
  $user = rgcCurrentUser();
  return $user && ($user['role'] ?? '') === 'super_admin';
}

function rgcIsAdminUser() {
  $user = rgcCurrentUser();
  return $user && in_array(($user['role'] ?? ''), ['super_admin', 'admin'], true);
}

function rgcEnforcePublicAccess() {
  if (!rgcMaintenanceOn()) {
    return;
  }
  $isAdmin = rgcIsAdminUser();
  $bypassUntil = (int) ($_SESSION['maintenance_bypass_until'] ?? 0);
  $hasBypass = $isAdmin && $bypassUntil > time();
  if (!$hasBypass) {
    header('Location: ' . rgcUrl('maintenance.php'));
    exit;
  }
}

function rgcPublicUser() {
  return $_SESSION['public_user'] ?? null;
}

function rgcPublicRequireLogin(string $returnTo = 'index.php') {
  if (isset($_SESSION['public_user'])) {
    return;
  }
  $r = urlencode(rgcSanitizeReturnPath($returnTo));
  header('Location: ' . rgcUrl('user/login.php?r=' . $r));
  exit;
}

function rgcSanitizeReturnPath(string $returnTo, string $default = 'index.php'): string {
  $returnTo = trim(html_entity_decode($returnTo, ENT_QUOTES, 'UTF-8'));
  if ($returnTo === '') {
    return rgcUrl($default);
  }

  $parts = parse_url($returnTo);
  if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
    return rgcUrl($default);
  }

  $path = (string) ($parts['path'] ?? '');
  if ($path === '') {
    $path = '/' . ltrim($default, '/');
  }
  if (str_starts_with($path, '//')) {
    return rgcUrl($default);
  }

  $normalizedPath = '/' . ltrim($path, '/');
  $query = isset($parts['query']) && $parts['query'] !== '' ? ('?' . $parts['query']) : '';
  $fragment = isset($parts['fragment']) && $parts['fragment'] !== '' ? ('#' . $parts['fragment']) : '';

  return rgcUrl($normalizedPath) . $query . $fragment;
}

function rgcSanitizeRichHtml(string $html): string {
  $html = str_replace("\0", '', trim($html));
  if ($html === '') {
    return '';
  }

  $html = preg_replace('~<\s*(script|style|iframe|object|embed|form|input|button|textarea|select|meta|link)[^>]*>.*?<\s*/\s*\1\s*>~is', '', $html) ?? $html;
  $html = preg_replace('~<\s*(script|style|iframe|object|embed|form|input|button|textarea|select|meta|link)\b[^>]*?/?>~is', '', $html) ?? $html;
  $html = strip_tags($html, '<p><br><strong><em><b><i><u><blockquote><ul><ol><li><h1><h2><h3><h4><h5><h6><a><img>');
  $html = preg_replace('/\s+on[a-z]+\s*=\s*("|\').*?\1/si', '', $html) ?? $html;
  $html = preg_replace('/\s+on[a-z]+\s*=\s*[^\s>]+/si', '', $html) ?? $html;
  $html = preg_replace('/\sstyle\s*=\s*("|\').*?\1/si', '', $html) ?? $html;
  $html = preg_replace('/\s(href|src)\s*=\s*("|\')\s*(javascript:|data:).*?\2/si', '', $html) ?? $html;
  $html = preg_replace('/\s(href|src)\s*=\s*(javascript:|data:)[^\s>]*/si', '', $html) ?? $html;

  return $html;
}

function rgcPublicRegister(string $name, string $email, string $password): array {
  if (!rgcDbAvailable()) {
    return ['ok' => false, 'error' => 'Database unavailable'];
  }
  $name = trim($name);
  $email = strtolower(trim($email));
  $password = trim($password);
  if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    return ['ok' => false, 'error' => 'Provide name, valid email, and 8+ char password'];
  }
  $stmt = rgcDb()->prepare('SELECT id FROM public_users WHERE email = :email LIMIT 1');
  $stmt->execute([':email' => $email]);
  if ($stmt->fetch()) {
    return ['ok' => false, 'error' => 'Email already registered'];
  }
  // Get IP and country for registration
  $ip = $_SERVER['REMOTE_ADDR'] ?? '';
  $country = rgcGetCountryFromIP($ip);
  $stmt = rgcDb()->prepare('INSERT INTO public_users (email, password_hash, full_name, is_verified, country, registration_ip, created_at, updated_at) VALUES (:email, :hash, :name, 1, :country, :ip, NOW(), NOW())');
  $stmt->execute([':email' => $email, ':hash' => password_hash($password, PASSWORD_DEFAULT), ':name' => $name, ':country' => $country, ':ip' => $ip]);
  return ['ok' => true];
}

function rgcPublicLogin(string $email, string $password): array {
  if (!rgcDbAvailable()) {
    return ['ok' => false, 'error' => 'Database unavailable'];
  }
  $email = strtolower(trim($email));
  $password = trim($password);
  $stmt = rgcDb()->prepare('SELECT id, email, full_name, password_hash FROM public_users WHERE email = :email LIMIT 1');
  $stmt->execute([':email' => $email]);
  $row = $stmt->fetch();
  if (!$row || !password_verify($password, (string) ($row['password_hash'] ?? ''))) {
    return ['ok' => false, 'error' => 'Invalid credentials'];
  }
  try {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $country = rgcGetCountryFromIP($ip);
    $up = rgcDb()->prepare('UPDATE public_users SET last_login_at = NOW(), last_active_at = NOW(), country = COALESCE(NULLIF(country, \'\'), :country), updated_at = NOW() WHERE id = :id');
    $up->execute([':id' => (int) $row['id'], ':country' => $country]);
  } catch (Throwable $e) {}
  $_SESSION['public_user'] = [
    'id' => (int) $row['id'],
    'email' => (string) $row['email'],
    'name' => (string) $row['full_name'],
  ];
  return ['ok' => true];
}

function rgcTouchPublicActivity(): void {
  if (!rgcDbAvailable()) {
    return;
  }
  if (empty($_SESSION['public_user']['id'])) {
    return;
  }
  $last = (int) ($_SESSION['public_last_touch'] ?? 0);
  if (time() - $last < 60) {
    return;
  }
  $_SESSION['public_last_touch'] = time();
  try {
    $stmt = rgcDb()->prepare('UPDATE public_users SET last_active_at = NOW(), updated_at = NOW() WHERE id = :id');
    $stmt->execute([':id' => (int) $_SESSION['public_user']['id']]);
  } catch (Throwable $e) {}
}

if (!empty($_SESSION['public_user']['id'])) {
  rgcTouchPublicActivity();
}
