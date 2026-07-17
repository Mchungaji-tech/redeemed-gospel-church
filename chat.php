<?php
require __DIR__ . '/includes/app.php';
rgcEnforcePublicAccess();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && rgcDbAvailable()) {
  rgcRequireCsrf('public_chat');
  $user = rgcPublicUser();
  $name = trim((string) ($_POST['name'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $message = trim((string) ($_POST['message'] ?? ''));
  if ($message !== '') {
    $stmt = rgcDb()->prepare('INSERT INTO public_messages (user_id, name, email, type, message, created_at) VALUES (:user_id, :name, :email, :type, :message, NOW())');
    $stmt->execute([
      ':user_id' => $user ? (int) ($user['id'] ?? 0) : null,
      ':name' => $user ? (string) ($user['name'] ?? '') : $name,
      ':email' => $user ? (string) ($user['email'] ?? '') : $email,
      ':type' => 'chat',
      ':message' => $message,
    ]);
    header('Location: /rgc/index.php?chat=sent');
    exit;
  }
}
header('Location: /rgc/index.php?chat=error');
exit;
