<?php
require __DIR__ . '/includes/app.php';
rgcEnforcePublicAccess();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . rgcUrl('index.php?chat=error'));
  exit;
}

rgcRequireCsrf('public_chat');

if (!rgcDbAvailable()) {
  header('Location: ' . rgcUrl('index.php?chat=offline'));
  exit;
}

$user = rgcPublicUser();
$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($message === '') {
  header('Location: ' . rgcUrl('index.php?chat=error'));
  exit;
}

try {
  rgcEnsurePublicMessagesPrivacyColumns();
  $stmt = rgcDb()->prepare('INSERT INTO public_messages (user_id, guest_token, name, email, type, message, created_at) VALUES (:user_id, :guest_token, :name, :email, :type, :message, NOW())');
  $stmt->execute([
    ':user_id' => $user ? (int) ($user['id'] ?? 0) : null,
    ':guest_token' => $user ? null : rgcPublicGuestToken(),
    ':name' => $user ? (string) ($user['name'] ?? '') : $name,
    ':email' => $user ? (string) ($user['email'] ?? '') : $email,
    ':type' => 'chat',
    ':message' => $message,
  ]);
  header('Location: ' . rgcUrl('index.php?chat=sent'));
  exit;
} catch (Throwable $e) {
  header('Location: ' . rgcUrl('index.php?chat=error'));
}
exit;
