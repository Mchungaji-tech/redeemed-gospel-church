<?php
require_once dirname(__DIR__, 2) . '/includes/app.php';

const RGC_MAX_FAILED_LOGINS = 5;
const RGC_LOCKOUT_MINUTES = 15;
const RGC_OTP_TTL_MINUTES = 10;
const RGC_OTP_MAX_ATTEMPTS = 5;
const RGC_OTP_RESEND_COOLDOWN_SECONDS = 60;
const RGC_IP_FAIL_WINDOW_MINUTES = 15;
const RGC_IP_FAIL_LIMIT = 10;

function rgcClientIp(): string {
  $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
  foreach ($keys as $key) {
    $value = trim((string) ($_SERVER[$key] ?? ''));
    if ($value === '') {
      continue;
    }
    if ($key === 'HTTP_X_FORWARDED_FOR') {
      $parts = explode(',', $value);
      return trim($parts[0]);
    }
    return $value;
  }
  return 'unknown';
}

function rgcUserAgent(): string {
  return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 255);
}

function rgcSecurityLog(string $eventType, string $message, ?int $userId = null, ?string $username = null): void {
  if (!rgcDbAvailable()) {
    return;
  }

  $payload = [
    'event_type' => $eventType,
    'event_message' => $message,
    'user_id' => $userId,
    'username' => $username,
    'ip_address' => rgcClientIp(),
    'user_agent' => rgcUserAgent(),
    'created_at' => date('Y-m-d H:i:s'),
  ];

  $sql = 'INSERT INTO security_logs (user_id, username, event_type, event_message, ip_address, user_agent, created_at)
          VALUES (:user_id, :username, :event_type, :event_message, :ip_address, :user_agent, NOW())';
  $stmt = rgcDb()->prepare($sql);
  $stmt->execute([
    ':user_id' => $payload['user_id'],
    ':username' => $payload['username'],
    ':event_type' => $payload['event_type'],
    ':event_message' => $payload['event_message'],
    ':ip_address' => $payload['ip_address'],
    ':user_agent' => $payload['user_agent'],
  ]);
}

function isLoggedIn(): bool {
  return isset($_SESSION['user']);
}

function rgcCurrentUserId(): ?int {
  $id = (int) ($_SESSION['user']['id'] ?? 0);
  return $id > 0 ? $id : null;
}

function rgcRequireDbForAuth(): void {
  if (!rgcDbEnabled()) {
    http_response_code(503);
    echo 'Database auth is disabled. Set RGC_USE_DB_AUTH=1.';
    exit;
  }
  if (!rgcDbAvailable()) {
    http_response_code(503);
    echo 'Database connection unavailable for admin authentication.';
    exit;
  }
}

function rgcFindUserByUsername(string $username): ?array {
  rgcRequireDbForAuth();
  $stmt = rgcDb()->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
  $stmt->execute([':username' => $username]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function rgcFindUserById(int $id): ?array {
  rgcRequireDbForAuth();
  $stmt = rgcDb()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
  $stmt->execute([':id' => $id]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function rgcListUsers(): array {
  rgcRequireDbForAuth();
  $stmt = rgcDb()->query('SELECT id, username, email, full_name, role, is_active, created_at, last_login_at FROM users ORDER BY created_at DESC');
  return $stmt->fetchAll();
}

function rgcCountUsers(): int {
  if (!rgcDbAvailable()) {
    return 0;
  }
  return (int) rgcDb()->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
}

function rgcCountPendingActivations(): int {
  if (!rgcDbAvailable()) {
    return 0;
  }
  return (int) rgcDb()->query('SELECT COUNT(*) AS c FROM users WHERE is_active = 0')->fetch()['c'];
}

function rgcCountRecentSecurityEvents(int $hours = 24): int {
  if (!rgcDbAvailable()) {
    return 0;
  }
  $stmt = rgcDb()->prepare('SELECT COUNT(*) AS c FROM security_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)');
  $stmt->bindValue(':hours', $hours, PDO::PARAM_INT);
  $stmt->execute();
  return (int) $stmt->fetch()['c'];
}

function rgcCountUnreadPublicChatThreads(): int {
  if (!rgcDbAvailable()) {
    return 0;
  }

  rgcEnsurePublicMessagesPrivacyColumns();

  $sql = "SELECT COUNT(*) AS c
          FROM (
            SELECT
              CASE
                WHEN user_id IS NOT NULL THEN CONCAT('user:', user_id)
                WHEN guest_token IS NOT NULL AND guest_token <> '' THEN CONCAT('guest:', guest_token)
                WHEN email IS NOT NULL AND email <> '' THEN CONCAT('email:', LOWER(email))
                ELSE CONCAT('message:', id)
              END AS thread_key
            FROM public_messages
            WHERE type = 'chat'
              AND admin_seen_at IS NULL
            GROUP BY thread_key
          ) unread_threads";

  return (int) (rgcDb()->query($sql)->fetch()['c'] ?? 0);
}

function rgcFormatBytes(int $bytes): string {
  $units = ['B', 'KB', 'MB', 'GB', 'TB'];
  $size = (float) $bytes;
  $i = 0;
  while ($size >= 1024 && $i < count($units) - 1) {
    $size /= 1024;
    $i++;
  }
  return number_format($size, $i === 0 ? 0 : 2) . ' ' . $units[$i];
}

function rgcDirSize(string $dir): int {
  if (!is_dir($dir)) {
    return 0;
  }
  $size = 0;
  $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
  foreach ($iterator as $item) {
    if ($item->isFile()) {
      $size += $item->getSize();
    }
  }
  return $size;
}

function rgcIsSuspiciousClient(?string $agent = null): bool {
  $ua = strtolower($agent ?? rgcUserAgent());
  $markers = ['hydra', 'sqlmap', 'python', 'requests', 'wfuzz', 'nikto', 'nmap', 'curl', 'burp'];
  foreach ($markers as $marker) {
    if (str_contains($ua, $marker)) {
      return true;
    }
  }
  return false;
}

function rgcTooManyIpFailures(string $ip): bool {
  if (!rgcDbAvailable()) {
    return false;
  }
  $sql = "SELECT COUNT(*) AS c
          FROM security_logs
          WHERE ip_address = :ip
            AND event_type IN ('login_failed_password', 'login_failed_username', 'login_blocked_client', 'login_blocked_rate')
            AND created_at >= DATE_SUB(NOW(), INTERVAL :mins MINUTE)";
  $stmt = rgcDb()->prepare($sql);
  $stmt->bindValue(':ip', $ip);
  $stmt->bindValue(':mins', RGC_IP_FAIL_WINDOW_MINUTES, PDO::PARAM_INT);
  $stmt->execute();
  return ((int) $stmt->fetch()['c']) >= RGC_IP_FAIL_LIMIT;
}

function rgcCreateActivationToken(int $userId): string {
  $token = bin2hex(random_bytes(32));
  $hash = hash('sha256', $token);
  $stmt = rgcDb()->prepare('UPDATE users SET activation_token_hash = :token_hash, activation_expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR), is_active = 0 WHERE id = :id');
  $stmt->execute([':token_hash' => $hash, ':id' => $userId]);
  return $token;
}

function rgcCreateUser(string $name, string $username, string $email, string $role, string $password, ?int $createdBy = null): array {
  rgcRequireDbForAuth();
  $name = trim($name);
  $username = trim($username);
  $email = trim($email);
  $role = $role === 'super_admin' ? 'super_admin' : 'admin';

  if ($name === '' || $username === '' || $email === '' || $password === '') {
    return ['ok' => false, 'error' => 'Name, username, email, and password are required.'];
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return ['ok' => false, 'error' => 'Provide a valid email address.'];
  }
  if (strlen($password) < 10) {
    return ['ok' => false, 'error' => 'Password must be at least 10 characters long.'];
  }

  $existsStmt = rgcDb()->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
  $existsStmt->execute([':username' => $username, ':email' => $email]);
  if ($existsStmt->fetch()) {
    return ['ok' => false, 'error' => 'Username or email already exists.'];
  }

  $stmt = rgcDb()->prepare(
    'INSERT INTO users (username, email, password_hash, role, full_name, is_active, created_by, created_at, updated_at)
     VALUES (:username, :email, :password_hash, :role, :full_name, 0, :created_by, NOW(), NOW())'
  );
  $stmt->execute([
    ':username' => $username,
    ':email' => $email,
    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ':role' => $role,
    ':full_name' => $name,
    ':created_by' => $createdBy,
  ]);
  $userId = (int) rgcDb()->lastInsertId();
  $token = rgcCreateActivationToken($userId);
  $link = rgcBaseUrl() . '/admin/activate.php?user=' . urlencode($username) . '&token=' . urlencode($token);
  rgcSecurityLog('account_created', 'Account created and activation required.', $userId, $username);
  return ['ok' => true, 'activation_link' => $link, 'user_id' => $userId];
}

function rgcActivateUser(string $username, string $token): array {
  rgcRequireDbForAuth();
  $user = rgcFindUserByUsername($username);
  if (!$user) {
    return ['ok' => false, 'error' => 'Invalid activation request.'];
  }
  $expectedHash = (string) ($user['activation_token_hash'] ?? '');
  $expires = strtotime((string) ($user['activation_expires_at'] ?? ''));
  if ($expectedHash === '' || !$expires || $expires < time()) {
    return ['ok' => false, 'error' => 'Activation link expired. Request a new activation link.'];
  }
  if (!hash_equals($expectedHash, hash('sha256', $token))) {
    rgcSecurityLog('activation_failed', 'Invalid activation token.', (int) $user['id'], (string) $user['username']);
    return ['ok' => false, 'error' => 'Invalid activation token.'];
  }

  $stmt = rgcDb()->prepare('UPDATE users SET is_active = 1, activation_token_hash = NULL, activation_expires_at = NULL, updated_at = NOW() WHERE id = :id');
  $stmt->execute([':id' => (int) $user['id']]);
  rgcSecurityLog('activation_success', 'Account activated successfully.', (int) $user['id'], (string) $user['username']);
  return ['ok' => true];
}

function rgcCreateAndStoreOtp(int $userId): string {
  $code = (string) random_int(100000, 999999);
  $stmt = rgcDb()->prepare('UPDATE users SET otp_code_hash = :hash, otp_expires_at = DATE_ADD(NOW(), INTERVAL :mins MINUTE), updated_at = NOW() WHERE id = :id');
  $stmt->bindValue(':hash', password_hash($code, PASSWORD_DEFAULT));
  $stmt->bindValue(':mins', RGC_OTP_TTL_MINUTES, PDO::PARAM_INT);
  $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
  $stmt->execute();
  return $code;
}

function rgcPendingMfaUser(): ?array {
  $userId = (int) ($_SESSION['mfa_user_id'] ?? 0);
  if ($userId <= 0) {
    return null;
  }
  return rgcFindUserById($userId);
}

function rgcOtpExpiresInSeconds(): int {
  $userId = (int) ($_SESSION['mfa_user_id'] ?? 0);
  if ($userId <= 0 || !rgcDbAvailable()) {
    return 0;
  }
  $stmt = rgcDb()->prepare('SELECT GREATEST(TIMESTAMPDIFF(SECOND, NOW(), otp_expires_at), 0) AS s FROM users WHERE id = :id');
  $stmt->execute([':id' => $userId]);
  $row = $stmt->fetch();
  return (int) ($row['s'] ?? 0);
}

function rgcOtpResendCooldownRemainingSeconds(): int {
  $lastSentAt = (int) ($_SESSION['mfa_last_sent_at'] ?? 0);
  if ($lastSentAt <= 0) {
    return 0;
  }
  $next = $lastSentAt + RGC_OTP_RESEND_COOLDOWN_SECONDS;
  return max(0, $next - time());
}

function rgcIssueOtpForUser(array $user, bool $isResend = false): array {
  $userId = (int) ($user['id'] ?? 0);
  if ($userId <= 0) {
    return ['status' => 'error', 'message' => 'Unable to generate verification code.'];
  }
  $code = rgcCreateAndStoreOtp($userId);
  $_SESSION['mfa_user_id'] = $userId;
  if (!isset($_SESSION['mfa_attempts'])) {
    $_SESSION['mfa_attempts'] = 0;
  }
  $_SESSION['mfa_last_sent_at'] = time();
  $deliveryMessage = rgcOtpDeliveryMessage((string) $user['email'], $code);
  rgcSecurityLog($isResend ? 'otp_resent' : 'otp_issued', $isResend ? 'Login OTP re-issued.' : 'Login OTP issued.', $userId, (string) $user['username']);
  return ['status' => 'otp_required', 'message' => $deliveryMessage];
}

function attemptResendOtp(): array {
  rgcRequireDbForAuth();
  $user = rgcPendingMfaUser();
  if (!$user) {
    return ['status' => 'error', 'message' => 'Login session expired. Sign in again.'];
  }
  $remaining = rgcOtpResendCooldownRemainingSeconds();
  if ($remaining > 0) {
    return ['status' => 'error', 'message' => 'Please wait ' . $remaining . ' seconds before resending.'];
  }
  if (!(int) ($user['is_active'] ?? 0)) {
    return ['status' => 'error', 'message' => 'Account is not active.'];
  }
  return rgcIssueOtpForUser($user, true);
}

function rgcHandleSuccessfulSession(array $user): void {
  session_regenerate_id(true);
  $_SESSION['user'] = [
    'id' => (int) $user['id'],
    'username' => (string) $user['username'],
    'name' => (string) $user['full_name'],
    'role' => (string) $user['role'],
    'email' => (string) $user['email'],
    'avatar' => (string) ($user['avatar'] ?? ''),
  ];
  $_SESSION['login_at'] = time();
  unset($_SESSION['mfa_user_id'], $_SESSION['mfa_attempts'], $_SESSION['mfa_last_sent_at'], $_SESSION['debug_otp']);
}

function rgcOtpDeliveryMessage(string $email, string $code): string {
  $subject = 'Redeemed Gospel Admin Verification Code';
  $body = "Your verification code is: {$code}\nThis code expires in " . RGC_OTP_TTL_MINUTES . " minutes.";
  $sent = rgcSendMail($email, $subject, $body);
  if ($sent) {
    $mode = strtolower((string) rgcConfig('mail.mode', 'test'));
    if ($mode === 'test') {
      return 'Verification code sent to test mailbox. Open ' . rgcUrl('mailbox.php') . ' with your mailbox key.';
    }
    return 'Verification code sent to your email.';
  }
  if (rgcFlag('RGC_DEBUG_SHOW_OTP', false)) {
    $_SESSION['debug_otp'] = $code;
    return 'Mail not configured. Debug verification code is displayed below.';
  }
  return 'Verification code generated. Configure SMTP/mail on the server for delivery.';
}

function attemptLoginStepOne(string $username, string $password): array {
  rgcRequireDbForAuth();
  $username = trim($username);
  $password = trim($password);
  $ip = rgcClientIp();
  $ua = rgcUserAgent();

  if ($username === '' || $password === '') {
    return ['status' => 'error', 'message' => 'Username and password are required.'];
  }
  if (rgcIsSuspiciousClient($ua)) {
    rgcSecurityLog('login_blocked_client', 'Suspicious client blocked before auth.', null, $username);
    return ['status' => 'blocked', 'message' => 'Access denied for this client.'];
  }
  if (rgcTooManyIpFailures($ip)) {
    rgcSecurityLog('login_blocked_rate', 'IP rate limit exceeded.', null, $username);
    return ['status' => 'blocked', 'message' => 'Too many attempts. Try again later.'];
  }

  $user = rgcFindUserByUsername($username);
  if (!$user) {
    rgcSecurityLog('login_failed_username', 'Unknown username login attempt.', null, $username);
    return ['status' => 'error', 'message' => 'Invalid login credentials.'];
  }

  $lockedUntil = strtotime((string) ($user['locked_until'] ?? ''));
  if ($lockedUntil && $lockedUntil > time()) {
    rgcSecurityLog('login_locked', 'Login blocked because account is locked.', (int) $user['id'], $username);
    return ['status' => 'locked', 'message' => 'Account locked due to failed attempts. Try again later.'];
  }

  if (!password_verify($password, (string) ($user['password_hash'] ?? ''))) {
    $failed = ((int) ($user['failed_login_attempts'] ?? 0)) + 1;
    $newLock = null;
    if ($failed >= RGC_MAX_FAILED_LOGINS) {
      $newLock = date('Y-m-d H:i:s', time() + (RGC_LOCKOUT_MINUTES * 60));
      $failed = 0;
    }
    $stmt = rgcDb()->prepare('UPDATE users SET failed_login_attempts = :failed, locked_until = :locked_until, updated_at = NOW() WHERE id = :id');
    $stmt->execute([
      ':failed' => $failed,
      ':locked_until' => $newLock,
      ':id' => (int) $user['id'],
    ]);
    rgcSecurityLog('login_failed_password', 'Incorrect password provided.', (int) $user['id'], $username);
    if ($newLock !== null) {
      rgcSecurityLog('account_locked', 'Account locked after too many failed logins.', (int) $user['id'], $username);
      return ['status' => 'locked', 'message' => 'Too many failed attempts. Account temporarily locked.'];
    }
    return ['status' => 'error', 'message' => 'Invalid login credentials.'];
  }

  if (!(int) ($user['is_active'] ?? 0)) {
    rgcSecurityLog('login_blocked_inactive', 'Login blocked for inactive account.', (int) $user['id'], $username);
    return ['status' => 'inactive', 'message' => 'Activate your account before signing in.'];
  }

  $_SESSION['mfa_user_id'] = (int) $user['id'];
  $_SESSION['mfa_attempts'] = 0;
  return rgcIssueOtpForUser($user, false);
}

function attemptVerifyOtp(string $otpCode): array {
  rgcRequireDbForAuth();
  $userId = (int) ($_SESSION['mfa_user_id'] ?? 0);
  if ($userId <= 0) {
    return ['status' => 'error', 'message' => 'Login session expired. Sign in again.'];
  }
  $otpCode = trim($otpCode);
  if (!preg_match('/^\d{6}$/', $otpCode)) {
    return ['status' => 'error', 'message' => 'Enter the 6-digit verification code.'];
  }

  $stmt = rgcDb()->prepare('SELECT username, otp_code_hash, otp_expires_at > NOW() AS valid, is_active FROM users WHERE id = :id LIMIT 1');
  $stmt->execute([':id' => $userId]);
  $user = $stmt->fetch();
  if (!$user) {
    return ['status' => 'error', 'message' => 'Login session expired. Sign in again.'];
  }

  $attempts = ((int) ($_SESSION['mfa_attempts'] ?? 0)) + 1;
  $_SESSION['mfa_attempts'] = $attempts;
  if ($attempts > RGC_OTP_MAX_ATTEMPTS) {
    $stmt = rgcDb()->prepare('UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL :mins MINUTE), otp_code_hash = NULL, otp_expires_at = NULL, updated_at = NOW() WHERE id = :id');
    $stmt->bindValue(':mins', RGC_LOCKOUT_MINUTES, PDO::PARAM_INT);
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    rgcSecurityLog('otp_failed_lockout', 'OTP attempts exceeded limit; account locked.', $userId, (string) ($user['username'] ?? ''));
    unset($_SESSION['mfa_user_id'], $_SESSION['mfa_attempts']);
    return ['status' => 'locked', 'message' => 'Too many invalid codes. Account locked temporarily.'];
  }

  $otpHash = (string) ($user['otp_code_hash'] ?? '');
  $validNow = !empty($user['valid']);
  if ($otpHash === '' || !$validNow) {
    rgcSecurityLog('otp_expired', 'OTP verification failed because code expired.', $userId, (string) ($user['username'] ?? ''));
    return ['status' => 'error', 'message' => 'Verification code expired. Sign in again.'];
  }
  if (!password_verify($otpCode, $otpHash)) {
    rgcSecurityLog('otp_failed', 'Incorrect OTP supplied.', $userId, (string) ($user['username'] ?? ''));
    return ['status' => 'error', 'message' => 'Invalid verification code.'];
  }

  $stmt = rgcDb()->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL, otp_code_hash = NULL, otp_expires_at = NULL, last_login_at = NOW(), last_login_ip = :ip, updated_at = NOW() WHERE id = :id');
  $stmt->execute([':ip' => rgcClientIp(), ':id' => $userId]);
  $userFull = rgcFindUserById($userId);
  if ($userFull) {
    rgcHandleSuccessfulSession($userFull);
    rgcSecurityLog('login_success', 'User login completed with OTP.', $userId, (string) ($userFull['username'] ?? ''));
  } else {
    rgcHandleSuccessfulSession(['id' => $userId, 'username' => (string) ($user['username'] ?? ''), 'full_name' => '', 'role' => '', 'email' => '']);
    rgcSecurityLog('login_success', 'User login completed with OTP.', $userId, (string) ($user['username'] ?? ''));
  }
  return ['status' => 'ok'];
}

function hasPendingOtp(): bool {
  return (int) ($_SESSION['mfa_user_id'] ?? 0) > 0;
}

function enforceForcedLogout() {
  global $rgcSettings;
  if (!isLoggedIn()) {
    return;
  }
  $forceAt = (int) ($rgcSettings['force_logout_at'] ?? 0);
  $loginAt = (int) ($_SESSION['login_at'] ?? time());
  if ($forceAt > 0 && $loginAt < $forceAt) {
    $u = rgcCurrentUser();
    rgcSecurityLog('forced_logout', 'Session ended by force logout setting.', (int) ($u['id'] ?? 0), (string) ($u['username'] ?? null));
    session_destroy();
    header('Location: ' . rgcUrl('admin/login.php?expired=1'));
    exit;
  }
}

function requireLogin() {
  enforceForcedLogout();
  if (!isLoggedIn()) {
    header('Location: ' . rgcUrl('admin/login.php'));
    exit;
  }
}

function requireSuperAdmin() {
  requireLogin();
  if (!rgcIsSuperAdmin()) {
    http_response_code(403);
    echo 'Access denied. Super admin only.';
    exit;
  }
}

function attemptLogin($username, $password) {
  $result = attemptLoginStepOne((string) $username, (string) $password);
  return ($result['status'] ?? '') === 'ok';
}
