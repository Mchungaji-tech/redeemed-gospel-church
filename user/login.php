<?php
require __DIR__ . '/../includes/app.php';
rgcEnforcePublicAccess();
$pageTitle = 'Member Login';
$error = '';
$returnTo = rgcSanitizeReturnPath((string) ($_GET['r'] ?? ($_POST['r'] ?? 'index.php')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('public_login');
  $email = (string) ($_POST['email'] ?? '');
  $password = (string) ($_POST['password'] ?? '');
  $result = rgcPublicLogin($email, $password);
  if (!empty($result['ok'])) {
    header('Location: ' . $returnTo);
    exit;
  }
  $error = (string) ($result['error'] ?? 'Login failed');
}
require __DIR__ . '/../includes/header.php';
?>
<section class="section-padding bg-slate-50">
  <div class="max-w-md mx-auto px-4">
    <h1 class="text-2xl font-bold text-slate-900 mb-2">Member Login</h1>
    <p class="text-slate-600 mb-6">Sign in to comment and send prayer requests.</p>
    <?php if ($error): ?>
    <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" class="space-y-4">
      <?php echo rgcCsrfField('public_login'); ?>
      <input type="hidden" name="r" value="<?php echo htmlspecialchars($returnTo); ?>">
      <div>
        <label class="form-label">Email</label>
        <input name="email" type="email" autocomplete="email" class="form-input" required>
      </div>
      <div>
        <label class="form-label">Password</label>
        <input name="password" type="password" autocomplete="current-password" class="form-input" required minlength="8">
      </div>
      <button class="btn btn-primary w-full py-3.5">Sign In</button>
    </form>
    <p class="text-sm text-slate-600 mt-4">No account? <a href="<?php echo htmlspecialchars(rgcUrl('user/register.php?r=' . urlencode($returnTo))); ?>" class="text-indigo-700">Register</a></p>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
