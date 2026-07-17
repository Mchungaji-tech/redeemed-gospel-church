<?php
require __DIR__ . '/../includes/app.php';

header('Content-Type: application/json');

function paypalFail(string $error, int $code = 400): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $error]);
  exit;
}

function paypalReadJson(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode((string) $raw, true);
  return is_array($data) ? $data : [];
}

function paypalVerifyCsrfFromHeader(string $scope): bool {
  $header = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
  $expected = (string) ($_SESSION['csrf_tokens'][$scope] ?? '');
  return $header !== '' && $expected !== '' && hash_equals($expected, $header);
}

function paypalDonationOwnedByCurrentUser(array $donation): bool {
  $publicUser = rgcPublicUser();
  $sessionDonationId = (int) ($_SESSION['donation_last_id'] ?? 0);
  $donationId = (int) ($donation['id'] ?? 0);
  if ($sessionDonationId > 0 && $donationId === $sessionDonationId) {
    return true;
  }
  if ($publicUser && (int) ($donation['user_id'] ?? 0) === (int) ($publicUser['id'] ?? 0)) {
    return true;
  }
  return false;
}

function paypalGetAccessToken(string $baseUrl, string $clientId, string $clientSecret): ?string {
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => rtrim($baseUrl, '/') . '/v1/oauth2/token',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
    CURLOPT_USERPWD => $clientId . ':' . $clientSecret,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
  ]);
  $res = curl_exec($ch);
  if ($res === false) {
    curl_close($ch);
    return null;
  }
  $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($status < 200 || $status >= 300) {
    return null;
  }
  $json = json_decode($res, true);
  return is_array($json) ? (string) ($json['access_token'] ?? '') : '';
}

function paypalCaptureOrder(string $baseUrl, string $accessToken, string $orderId): ?array {
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => rtrim($baseUrl, '/') . '/v2/checkout/orders/' . rawurlencode($orderId) . '/capture',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer ' . $accessToken,
      'Content-Type: application/json'
    ],
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
  ]);
  $res = curl_exec($ch);
  if ($res === false) {
    curl_close($ch);
    return null;
  }
  $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($status < 200 || $status >= 300) {
    return null;
  }
  $json = json_decode($res, true);
  return is_array($json) ? $json : null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  paypalFail('Invalid method', 405);
}
if (!rgcDbAvailable()) {
  paypalFail('Service unavailable', 503);
}
if (!paypalVerifyCsrfFromHeader('public_donate')) {
  paypalFail('CSRF validation failed', 419);
}

$data = paypalReadJson();
$orderId = trim((string) ($data['order_id'] ?? ''));
$donationId = (int) ($data['donation_id'] ?? 0);

if ($orderId === '' || $donationId <= 0) {
  paypalFail('Invalid order or donation ID');
}

$stmt = rgcDb()->prepare('SELECT id, user_id, status, method FROM donations WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $donationId]);
$donation = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$donation) {
  paypalFail('Donation not found', 404);
}
if (!paypalDonationOwnedByCurrentUser($donation)) {
  paypalFail('Forbidden', 403);
}
if ((string) ($donation['method'] ?? '') !== 'paypal') {
  paypalFail('Donation method mismatch');
}
if ((string) ($donation['status'] ?? '') === 'received') {
  echo json_encode(['ok' => true, 'status' => 'already_received']);
  exit;
}
if ((string) ($donation['status'] ?? '') !== 'pending') {
  paypalFail('Donation is not pending');
}

// Get PayPal Config
$mode = rgcConfig('paypal.mode', 'sandbox');
$baseUrl = ($mode === 'live') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
$clientId = ($mode === 'live') ? rgcConfig('paypal.live_client_id') : rgcConfig('paypal.sandbox_client_id');
$clientSecret = ($mode === 'live') ? rgcConfig('paypal.live_secret') : rgcConfig('paypal.sandbox_secret');

if (empty($clientId) || empty($clientSecret)) {
  paypalFail('PayPal configuration missing', 500);
}

// Get Access Token
$accessToken = paypalGetAccessToken($baseUrl, $clientId, $clientSecret);
if (!$accessToken) {
  paypalFail('Failed to authenticate with PayPal', 500);
}

// Capture Order
$capture = paypalCaptureOrder($baseUrl, $accessToken, $orderId);
if (!$capture || ($capture['status'] ?? '') !== 'COMPLETED') {
  paypalFail('Failed to capture PayPal payment', 500);
}

// Update donation in DB
$stmt = rgcDb()->prepare('UPDATE donations SET status = :status, reference = :reference, updated_at = NOW() WHERE id = :id AND status <> :received');
$stmt->execute([
  ':status' => 'received',
  ':reference' => $orderId,
  ':id' => $donationId,
  ':received' => 'received',
]);

echo json_encode(['ok' => true]);
