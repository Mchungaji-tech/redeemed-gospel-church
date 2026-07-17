<?php
require __DIR__ . '/../includes/app.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Method not allowed']);
  exit;
}

if (!rgcDbAvailable()) {
  http_response_code(503);
  echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'DB unavailable']);
  exit;
}

$expectedKey = (string) rgcConfig('mpesa.webhook_key', '');
$providedKey = (string) ($_GET['key'] ?? '');
if ($expectedKey !== '' && !hash_equals($expectedKey, $providedKey)) {
  http_response_code(403);
  echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Forbidden']);
  exit;
}

$raw = file_get_contents('php://input');
$data = json_decode((string) $raw, true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid payload']);
  exit;
}

$stk = $data['Body']['stkCallback'] ?? [];
$checkoutRequestId = trim((string) ($stk['CheckoutRequestID'] ?? ''));
$resultCode = (int) ($stk['ResultCode'] ?? -1);

if ($checkoutRequestId === '') {
  http_response_code(400);
  echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Missing request id']);
  exit;
}

$status = $resultCode === 0 ? 'received' : 'failed';
$stmt = rgcDb()->prepare('UPDATE donations SET status = :status, updated_at = NOW() WHERE reference = :reference AND method = :method');
$stmt->execute([
  ':status' => $status,
  ':reference' => $checkoutRequestId,
  ':method' => 'mpesa',
]);

echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
