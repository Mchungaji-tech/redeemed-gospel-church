<?php
require __DIR__ . '/../includes/app.php';
rgcEnforcePublicAccess();
$pageTitle = 'Member Registration';
$error = '';
$ok = false;
$returnTo = rgcSanitizeReturnPath((string) ($_GET['r'] ?? ($_POST['r'] ?? 'index.php')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('public_register');
  $name = (string) ($_POST['name'] ?? '');
  $email = (string) ($_POST['email'] ?? '');
  $password = (string) ($_POST['password'] ?? '');
  $result = rgcPublicRegister($name, $email, $password);
  if (!empty($result['ok'])) {
    $ok = true;
  } else {
    $error = (string) ($result['error'] ?? 'Registration failed');
  }
}
require __DIR__ . '/../includes/header.php';
?>
<section class="section-padding bg-slate-50">
  <div class="max-w-md mx-auto px-4">
    <h1 class="text-2xl font-bold text-slate-900 mb-2">Create Account</h1>
    <p class="text-slate-600 mb-6">Register to comment and send prayer requests.</p>
    <?php if ($error): ?>
    <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($ok): ?>
    <div class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">Registration successful. Please sign in.</div>
    <p class="text-sm"><a class="text-indigo-700" href="<?php echo htmlspecialchars(rgcUrl('user/login.php?r=' . urlencode($returnTo))); ?>">Go to Login</a></p>
    <?php else: ?>
    <form method="post" class="space-y-4">
      <?php echo rgcCsrfField('public_register'); ?>
      <input type="hidden" name="r" value="<?php echo htmlspecialchars($returnTo); ?>">
      <div>
        <label class="form-label">Full Name</label>
        <input name="name" autocomplete="name" class="form-input" required>
      </div>
      <div>
        <label class="form-label">Email</label>
        <input name="email" type="email" autocomplete="email" class="form-input" required>
      </div>
      <div>
        <label class="form-label">Password</label>
        <input name="password" type="password" autocomplete="new-password" class="form-input" required minlength="8">
      </div>
      <button class="btn btn-primary w-full py-3.5">Create Account</button>
    </form>
    <?php endif; ?>
    <p class="text-sm text-slate-600 mt-4">Already have an account? <a href="<?php echo htmlspecialchars(rgcUrl('user/login.php?r=' . urlencode($returnTo))); ?>" class="text-indigo-700">Login</a></p>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
