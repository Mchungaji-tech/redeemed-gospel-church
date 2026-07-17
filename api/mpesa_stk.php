<?php
require __DIR__ . '/../includes/app.php';

header('Content-Type: application/json');

function mpesaFail(string $error, int $code = 400): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $error]);
  exit;
}

function mpesaReadJson(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode((string) $raw, true);
  return is_array($data) ? $data : [];
}

function mpesaVerifyCsrfFromHeader(string $scope): bool {
  $header = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
  $expected = (string) ($_SESSION['csrf_tokens'][$scope] ?? '');
  return $header !== '' && $expected !== '' && hash_equals($expected, $header);
}

function mpesaNormalizePhone(string $phone): ?string {
  $digits = preg_replace('/\D+/', '', $phone);
  if ($digits === null) {
    return null;
  }
  if (str_starts_with($digits, '0')) {
    $digits = '254' . substr($digits, 1);
  }
  if (str_starts_with($digits, '7') && strlen($digits) === 9) {
    $digits = '254' . $digits;
  }
  if (!preg_match('/^2547\d{8}$/', $digits)) {
    return null;
  }
  return $digits;
}

function mpesaDonationOwnedByCurrentUser(array $donation): bool {
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  mpesaFail('Invalid method', 405);
}
if (!rgcDbAvailable()) {
  mpesaFail('Service unavailable', 503);
}
if (!mpesaVerifyCsrfFromHeader('public_donate')) {
  mpesaFail('CSRF validation failed', 419);
}

$data = mpesaReadJson();
$donationId = (int) ($data['donation_id'] ?? 0);
$phoneInput = trim((string) ($data['phone'] ?? ''));
$phone = mpesaNormalizePhone($phoneInput);

if ($donationId <= 0 || $phone === null) {
  mpesaFail('Invalid donation id or phone');
}

$stmt = rgcDb()->prepare('SELECT id, user_id, amount_cents, currency, status, method FROM donations WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $donationId]);
$donation = $stmt->fetch();
if (!$donation) {
  mpesaFail('Donation not found', 404);
}
if (!mpesaDonationOwnedByCurrentUser($donation)) {
  mpesaFail('Forbidden', 403);
}
if ((string) ($donation['method'] ?? '') !== 'mpesa') {
  mpesaFail('Donation method mismatch');
}
if ((string) ($donation['status'] ?? '') !== 'pending') {
  mpesaFail('Donation is not pending');
}

$amountCents = (int) ($donation['amount_cents'] ?? 0);
$amount = (int) max(1, round($amountCents / 100));

$base = (string) rgcConfig('mpesa.base_url', 'https://sandbox.safaricom.co.ke');
$key = (string) rgcConfig('mpesa.consumer_key', '');
$secret = (string) rgcConfig('mpesa.consumer_secret', '');
$short = (string) rgcConfig('mpesa.shortcode', '');
$pass = (string) rgcConfig('mpesa.passkey', '');
$callback = (string) rgcConfig('mpesa.callback_url', '');
$webhookKey = (string) rgcConfig('mpesa.webhook_key', '');
if ($callback === '') {
  $callback = rgcBaseUrl() . '/api/mpesa_webhook.php';
  if ($webhookKey !== '') {
    $callback .= '?key=' . urlencode($webhookKey);
  }
}

if ($key === '' || $secret === '' || $short === '' || $pass === '') {
  mpesaFail('MPesa not configured', 500);
}

$ch = curl_init();
curl_setopt_array($ch, [
  CURLOPT_URL => rtrim($base, '/') . '/oauth/v1/generate?grant_type=client_credentials',
  CURLOPT_HTTPHEADER => ['Authorization: Basic ' . base64_encode($key . ':' . $secret)],
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_CONNECTTIMEOUT => 10,
  CURLOPT_TIMEOUT => 20,
]);
$authRes = curl_exec($ch);
if ($authRes === false) {
  curl_close($ch);
  mpesaFail('MPesa auth request failed', 502);
}
$authStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($authStatus < 200 || $authStatus >= 300) {
  curl_close($ch);
  mpesaFail('MPesa auth rejected', 502);
}
$token = (string) (json_decode($authRes, true)['access_token'] ?? '');
if ($token === '') {
  curl_close($ch);
  mpesaFail('MPesa token missing', 502);
}

$timestamp = date('YmdHis');
$password = base64_encode($short . $pass . $timestamp);
$payload = [
  'BusinessShortCode' => $short,
  'Password' => $password,
  'Timestamp' => $timestamp,
  'TransactionType' => 'CustomerPayBillOnline',
  'Amount' => $amount,
  'PartyA' => $phone,
  'PartyB' => $short,
  'PhoneNumber' => $phone,
  'CallBackURL' => $callback,
  'AccountReference' => 'DONATION-' . $donationId,
  'TransactionDesc' => 'RGC Donation',
];

curl_setopt_array($ch, [
  CURLOPT_URL => rtrim($base, '/') . '/mpesa/stkpush/v1/processrequest',
  CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_CONNECTTIMEOUT => 10,
  CURLOPT_TIMEOUT => 20,
]);
$stkRes = curl_exec($ch);
if ($stkRes === false) {
  curl_close($ch);
  mpesaFail('STK request failed', 502);
}
$stkStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($stkStatus < 200 || $stkStatus >= 300) {
  mpesaFail('STK request rejected', 502);
}

$j = json_decode($stkRes, true);
$reqId = (string) ($j['CheckoutRequestID'] ?? '');
if ($reqId === '') {
  mpesaFail((string) ($j['errorMessage'] ?? 'Failed to initiate STK push'), 502);
}

$up = rgcDb()->prepare('UPDATE donations SET reference = :ref, phone = :phone, updated_at = NOW() WHERE id = :id AND status = :status');
$up->execute([
  ':ref' => $reqId,
  ':phone' => $phone,
  ':id' => $donationId,
  ':status' => 'pending',
]);

echo json_encode(['ok' => true, 'request_id' => $reqId]);
