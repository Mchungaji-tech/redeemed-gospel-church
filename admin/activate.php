<?php
require __DIR__ . '/includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $username = trim((string) ($_GET['user'] ?? ''));
  $token = trim((string) ($_GET['token'] ?? ''));
  if ($username === '' || $token === '') {
    $error = 'Invalid activation link.';
  } else {
    $result = rgcActivateUser($username, $token);
    if (!($result['ok'] ?? false)) {
      $error = (string) ($result['error'] ?? 'Activation failed.');
    } else {
      $success = 'Account activated successfully.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Activate Account</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
  <div class="w-full max-w-lg bg-white border border-slate-200 shadow-xl rounded-2xl p-8 text-center">
    <h1 class="text-2xl font-bold text-slate-900">Account Activation</h1>
    <?php if ($error): ?>
    <p class="mt-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
    <p class="mt-4 p-3 rounded-lg text-sm bg-emerald-50 text-emerald-700 border border-emerald-200"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <a href="<?php echo rgcUrl('admin/login.php?activated=1'); ?>" class="inline-flex mt-5 px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Proceed to Login</a>
  </div>
</body>
</html>
