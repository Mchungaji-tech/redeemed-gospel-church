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

function paypalFetchOrder(string $baseUrl, string $accessToken, string $orderId): ?array {
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => rtrim($baseUrl, '/') . '/v2/checkout/orders/' . rawurlencode($orderId),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
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
$donationId = (int) ($data['donation_id'] ?? 0);
$orderId = trim((string) ($data['order_id'] ?? ''));
if ($donationId <= 0 || $orderId === '') {
  paypalFail('Missing payment data');
}

$stmt = rgcDb()->prepare('SELECT id, user_id, amount_cents, currency, status, method FROM donations WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $donationId]);
$donation = $stmt->fetch();
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

$mode = (string) rgcConfig('paypal.mode', 'sandbox');
$baseUrl = (string) rgcConfig('paypal.base_url', '');
$clientId = (string) rgcConfig('paypal.client_id', '');
$clientSecret = (string) rgcConfig('paypal.client_secret', '');
if ($clientId === '' || $clientSecret === '') {
  $clientId = (string) ($mode === 'live' ? rgcConfig('paypal.live_client_id', '') : rgcConfig('paypal.sandbox_client_id', ''));
  $clientSecret = (string) ($mode === 'live' ? rgcConfig('paypal.live_secret', '') : rgcConfig('paypal.sandbox_secret', ''));
}
if ($baseUrl === '') {
  $baseUrl = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
}
if ($clientId === '' || $clientSecret === '') {
  paypalFail('PayPal server credentials not configured', 500);
}

$token = paypalGetAccessToken($baseUrl, $clientId, $clientSecret);
if ($token === null || $token === '') {
  paypalFail('Unable to authenticate PayPal request', 502);
}

$order = paypalFetchOrder($baseUrl, $token, $orderId);
if (!$order) {
  paypalFail('Unable to verify PayPal order', 502);
}

$status = strtoupper((string) ($order['status'] ?? ''));
if (!in_array($status, ['COMPLETED', 'APPROVED'], true)) {
  paypalFail('Order is not completed');
}

$amountValue = (string) ($order['purchase_units'][0]['amount']['value'] ?? '');
$amountCurrency = strtoupper((string) ($order['purchase_units'][0]['amount']['currency_code'] ?? ''));
if (!preg_match('/^\d+(\.\d{1,2})?$/', $amountValue)) {
  paypalFail('Invalid order amount');
}
$orderCents = (int) round(((float) $amountValue) * 100);
$donationCents = (int) ($donation['amount_cents'] ?? 0);
$donationCurrency = strtoupper((string) ($donation['currency'] ?? ''));
if ($orderCents !== $donationCents || $amountCurrency !== $donationCurrency) {
  paypalFail('Amount mismatch', 409);
}

$update = rgcDb()->prepare('UPDATE donations SET status = :status, reference = :reference, updated_at = NOW() WHERE id = :id AND status <> :received');
$update->execute([
  ':status' => 'received',
  ':reference' => $orderId,
  ':id' => $donationId,
  ':received' => 'received',
]);

echo json_encode(['ok' => true, 'status' => 'received']);
