<?php
require __DIR__ . '/includes/auth.php';

$base = rgcBaseUrl();
$mailMode = strtolower((string) rgcConfig('mail.mode', 'test'));
$mailKey = (string) rgcConfig('mailbox.key', '');
$dbOk = rgcDbAvailable();
$settings = rgcLoadJson('settings.json', rgcDefaultSettings());
$maintOn = !empty($settings['maintenance_mode']);

echo "Base URL: {$base}\n";
echo "Mail Mode: {$mailMode}\n";
echo "Mailbox Key: " . ($mailKey !== '' ? ('set (' . strlen($mailKey) . ' chars)') : 'missing') . "\n";
echo "DB Available: " . ($dbOk ? 'yes' : 'no') . "\n";
echo "Maintenance: " . ($maintOn ? 'ON' : 'OFF') . "\n";

$tables = ['app_settings','users','outbound_emails','sermons','events','ministries','projects','testimonials','gallery','comments','prayer_requests'];
if ($dbOk) {
  foreach ($tables as $t) {
    try {
      $stmt = rgcDb()->query("SELECT COUNT(*) AS c FROM {$t}");
      $row = $stmt->fetch();
      $count = (int) ($row['c'] ?? 0);
      echo "Table {$t}: OK ({$count} rows)\n";
    } catch (Throwable $e) {
      echo "Table {$t}: MISSING\n";
    }
  }
  try {
    rgcDb()->query("SELECT avatar FROM users LIMIT 1");
    echo "Users.avatar column: OK\n";
  } catch (Throwable $e) {
    echo "Users.avatar column: MISSING\n";
  }
}

$uploadsDir = dirname(__DIR__) . '/assets/uploads/avatars';
if (!is_dir($uploadsDir)) {
  @mkdir($uploadsDir, 0775, true);
}
$writable = is_writable($uploadsDir);
echo "Uploads/avatars dir: " . (is_dir($uploadsDir) ? 'exists' : 'missing') . ", writable: " . ($writable ? 'yes' : 'no') . "\n";

$otpTtl = defined('RGC_OTP_TTL_MINUTES') ? (int) RGC_OTP_TTL_MINUTES : 0;
echo "OTP TTL (minutes): {$otpTtl}\n";

$viewSiteExists = is_file(__DIR__ . '/view_site.php');
echo "Admin view_site route: " . ($viewSiteExists ? 'present' : 'missing') . "\n";

$profileExists = is_file(__DIR__ . '/profile.php');
echo "Admin profile page: " . ($profileExists ? 'present' : 'missing') . "\n";

$mailboxExists = is_file(dirname(__DIR__) . '/mailbox.php');
echo "Mailbox page: " . ($mailboxExists ? 'present' : 'missing') . "\n";
