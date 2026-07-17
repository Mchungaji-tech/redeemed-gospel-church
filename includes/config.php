<?php

if (!defined('RGC_ROOT_DIR')) {
  define('RGC_ROOT_DIR', dirname(__DIR__));
}
if (!defined('RGC_DATA_DIR')) {
  define('RGC_DATA_DIR', RGC_ROOT_DIR . '/data');
}

// Load .env file FIRST before any config is read
rgcLoadDotEnv(RGC_ROOT_DIR . '/.env');

function rgcLoadDotEnv(string $path): void {
  if (!is_file($path)) {
    return;
  }

  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  if ($lines === false) {
    return;
  }

  foreach ($lines as $line) {
    $trimmed = trim($line);
    if ($trimmed === '' || str_starts_with($trimmed, '#')) {
      continue;
    }
    $parts = explode('=', $trimmed, 2);
    if (count($parts) !== 2) {
      continue;
    }
    $key = trim($parts[0]);
    $value = trim($parts[1]);
    if ($key === '') {
      continue;
    }
    $value = trim($value, "\"'");
    if (getenv($key) === false) {
      putenv($key . '=' . $value);
      $_ENV[$key] = $value;
      $_SERVER[$key] = $value;
    }
  }
}

function rgcEnv(string $key, ?string $default = null): ?string {
  $value = getenv($key);
  if ($value === false || $value === null || $value === '') {
    return $default;
  }
  return (string) $value;
}

function rgcFlag(string $key, bool $default = false): bool {
  $raw = strtolower((string) rgcEnv($key, $default ? '1' : '0'));
  return in_array($raw, ['1', 'true', 'yes', 'on'], true);
}

function rgcConfig(string $key, mixed $default = null): mixed {
  static $config = null;
  if ($config === null) {
    $config = [
      'app.base_url' => rgcEnv('RGC_BASE_URL', null),
      'auth.use_db' => rgcFlag('RGC_USE_DB_AUTH', true),
      'db.host' => rgcEnv('RGC_DB_HOST', '127.0.0.1'),
      'db.port' => (int) rgcEnv('RGC_DB_PORT', '3306'),
      'db.name' => rgcEnv('RGC_DB_NAME', 'redeemed_church'),
      'db.user' => rgcEnv('RGC_DB_USER', 'root'),
      'db.pass' => rgcEnv('RGC_DB_PASS', ''),
      'db.charset' => rgcEnv('RGC_DB_CHARSET', 'utf8mb4'),
      'db.ssl_ca' => rgcEnv('RGC_DB_SSL_CA', null),
      'debug.show_otp' => rgcFlag('RGC_DEBUG_SHOW_OTP', false),
      'mail.mode' => rgcEnv('RGC_MAIL_MODE', 'test'),
      'mail.from' => rgcEnv('RGC_MAIL_FROM', 'no-reply@redeemed.local'),
      'mailbox.key' => rgcEnv('RGC_MAILBOX_KEY', ''),
      'donate.mpesa.paybill' => rgcEnv('RGC_DONATE_MPESA_PAYBILL', ''),
      'donate.mpesa.account' => rgcEnv('RGC_DONATE_MPESA_ACCOUNT', ''),
      'donate.paybill.number' => rgcEnv('RGC_DONATE_PAYBILL_NUMBER', '122766'),
      'donate.paybill.offering_account' => rgcEnv('RGC_DONATE_PAYBILL_OFFERING_ACCOUNT', 'Offering'),
      'donate.paybill.tithe_account' => rgcEnv('RGC_DONATE_PAYBILL_TITHE_ACCOUNT', 'Tithe'),
      'donate.paybill.donation_account' => rgcEnv('RGC_DONATE_PAYBILL_DONATION_ACCOUNT', 'Donation'),
      'donate.bank.name' => rgcEnv('RGC_DONATE_BANK_NAME', ''),
      'donate.bank.account' => rgcEnv('RGC_DONATE_BANK_ACCOUNT', ''),
      'donate.paystack.url' => rgcEnv('RGC_DONATE_PAYSTACK_URL', ''),
      'donate.contact.whatsapp' => rgcEnv('RGC_DONATE_CONTACT_WHATSAPP', '0722551152'),
      'donate.contact.email' => rgcEnv('RGC_DONATE_CONTACT_EMAIL', 'redeemedgospelchurch.eldoret@gmail.com'),
      'donate.paypal.url' => rgcEnv('RGC_DONATE_PAYPAL_URL', ''),
      'donate.stripe.link' => rgcEnv('RGC_DONATE_STRIPE_LINK', ''),
      'paypal.mode' => rgcEnv('RGC_PAYPAL_MODE', 'sandbox'),
      'paypal.sandbox_client_id' => rgcEnv('RGC_PAYPAL_SANDBOX_CLIENT_ID', ''),
      'paypal.sandbox_secret' => rgcEnv('RGC_PAYPAL_SANDBOX_SECRET', ''),
      'paypal.live_client_id' => rgcEnv('RGC_PAYPAL_LIVE_CLIENT_ID', ''),
      'paypal.live_secret' => rgcEnv('RGC_PAYPAL_LIVE_SECRET', ''),
      'mpesa.base_url' => rgcEnv('RGC_MPESA_BASE_URL', 'https://sandbox.safaricom.co.ke'),
      'mpesa.consumer_key' => rgcEnv('RGC_MPESA_CONSUMER_KEY', ''),
      'mpesa.consumer_secret' => rgcEnv('RGC_MPESA_CONSUMER_SECRET', ''),
      'mpesa.shortcode' => rgcEnv('RGC_MPESA_SHORTCODE', ''),
      'mpesa.passkey' => rgcEnv('RGC_MPESA_PASSKEY', ''),
      'mpesa.callback_url' => rgcEnv('RGC_MPESA_CALLBACK_URL', ''),
      'mpesa.webhook_key' => rgcEnv('RGC_MPESA_WEBHOOK_KEY', ''),
      'security.admin_reg_key' => rgcEnv('RGC_ADMIN_REG_KEY', ''),
      'security.super_admin_reg_key' => rgcEnv('RGC_SUPER_ADMIN_REG_KEY', ''),
    ];
  }
  return $config[$key] ?? $default;
}

function rgcDbEnabled(): bool {
  return (bool) rgcConfig('auth.use_db', true);
}

function rgcDb(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) {
    return $pdo;
  }

  $dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    (string) rgcConfig('db.host'),
    (int) rgcConfig('db.port'),
    (string) rgcConfig('db.name'),
    (string) rgcConfig('db.charset')
  );

  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 8,
  ];

  $sslCa = rgcConfig('db.ssl_ca');
  if (!empty($sslCa)) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
  }

  $pdo = new PDO(
    $dsn,
    (string) rgcConfig('db.user'),
    (string) rgcConfig('db.pass'),
    $options
  );
  return $pdo;
}

function rgcDbAvailable(): bool {
  if (!rgcDbEnabled()) {
    return false;
  }
  try {
    rgcDb()->query('SELECT 1');
    return true;
  } catch (Throwable $e) {
    return false;
  }
}

function rgcBaseUrl(): string {
  $configured = rgcConfig('app.base_url');
  if (!empty($configured)) {
    return rtrim((string) $configured, '/');
  }

  $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
  $basePath = dirname($scriptName);
  if (in_array(basename($basePath), ['admin', 'api', 'user'], true)) {
    $basePath = dirname($basePath);
  }
  $basePath = rtrim(str_replace('\\', '/', $basePath), '/');

  $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);
  $scheme = $isHttps ? 'https' : 'http';
  $host = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
  if (!preg_match('/^[a-z0-9.-]+(?::\d+)?$/i', $host)) {
    $host = 'localhost';
  }

  return $scheme . '://' . $host . ($basePath === '' ? '' : $basePath);
}
