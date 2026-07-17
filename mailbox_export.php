<?php
require __DIR__ . '/includes/app.php';

$expectedKey = (string) rgcConfig('mailbox.key', '');
$providedKey = (string) ($_GET['key'] ?? '');
$mailMode = strtolower((string) rgcConfig('mail.mode', 'test'));

if ($mailMode !== 'test') {
  http_response_code(403);
  echo 'Mailbox export is only available when RGC_MAIL_MODE=test.';
  exit;
}
if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
  http_response_code(403);
  echo 'Invalid mailbox key.';
  exit;
}
if (!rgcDbAvailable()) {
  http_response_code(503);
  echo 'Database is not available.';
  exit;
}

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$to = trim((string) ($_GET['to'] ?? ''));

$where = [];
$params = [];
if ($q !== '') {
  $where[] = '(subject LIKE :q OR body LIKE :q)';
  $params[':q'] = '%' . $q . '%';
}
if ($status !== '') {
  $where[] = 'status = :status';
  $params[':status'] = $status;
}
if ($to !== '') {
  $where[] = 'mail_to LIKE :to';
  $params[':to'] = '%' . $to . '%';
}
$whereSql = '';
if (!empty($where)) {
  $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$sql = 'SELECT id, mail_to, subject, body, status, error_message, created_at FROM outbound_emails ' . $whereSql . ' ORDER BY id DESC';
$stmt = rgcDb()->prepare($sql);
foreach ($params as $k => $v) {
  $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt->execute();
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="mailbox_export.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['id', 'mail_to', 'subject', 'body', 'status', 'error_message', 'created_at']);
foreach ($rows as $row) {
  fputcsv($out, [
    (int) ($row['id'] ?? 0),
    (string) ($row['mail_to'] ?? ''),
    (string) ($row['subject'] ?? ''),
    (string) ($row['body'] ?? ''),
    (string) ($row['status'] ?? ''),
    (string) ($row['error_message'] ?? ''),
    (string) ($row['created_at'] ?? ''),
  ]);
}
fclose($out);
exit;
