<?php
require __DIR__ . '/includes/auth.php';

$error = '';
$success = '';
$activationLink = '';

rgcRequireDbForAuth();
$isBootstrap = rgcCountUsers() === 0;
$bootstrapKey = (string) rgcEnv('RGC_BOOTSTRAP_REG_KEY', (string) rgcEnv('RGC_SUPER_ADMIN_REG_KEY', ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('admin_register');

  $name = trim((string) ($_POST['name'] ?? ''));
  $username = trim((string) ($_POST['username'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $role = (($_POST['role'] ?? 'admin') === 'super_admin') ? 'super_admin' : 'admin';
  $password = (string) ($_POST['password'] ?? '');
  $confirm = (string) ($_POST['confirm_password'] ?? '');
  $registrationKey = (string) ($_POST['registration_key'] ?? '');

  if ($password !== $confirm) {
    $error = 'Password confirmation does not match.';
  } else {
    $allowed = false;
    if ($isBootstrap) {
      $role = 'super_admin';
      if ($bootstrapKey !== '' && hash_equals($bootstrapKey, $registrationKey)) {
        $allowed = true;
      } else {
        $error = 'Bootstrap registration requires a valid bootstrap key.';
      }
    } else {
      $expected = $role === 'super_admin'
        ? (string) rgcEnv('RGC_SUPER_ADMIN_REG_KEY', '')
        : (string) rgcEnv('RGC_ADMIN_REG_KEY', '');
      if ($expected !== '' && hash_equals($expected, $registrationKey)) {
        $allowed = true;
      }
    }

    if (!$allowed && $error === '') {
      $error = 'Invalid registration key for selected role.';
    } else {
      $createdBy = rgcCurrentUserId();
      $result = rgcCreateUser($name, $username, $email, $role, $password, $createdBy);
      if (!($result['ok'] ?? false)) {
        $error = (string) ($result['error'] ?? 'Registration failed.');
      } else {
        $success = 'Account created. Activate using the link below, then log in.';
        $activationLink = (string) ($result['activation_link'] ?? '');
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register Admin Account</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
  <div class="w-full max-w-2xl bg-white border border-slate-200 shadow-xl rounded-2xl p-8">
    <h1 class="text-2xl font-bold text-slate-900">Admin Registration</h1>
    <p class="text-sm text-slate-500 mt-1">Create an admin or super admin account, then activate it before first login.</p>

    <?php if ($isBootstrap): ?>
    <div class="mt-4 p-3 rounded-lg text-sm bg-amber-50 text-amber-800 border border-amber-200">Bootstrap mode: no users found. Set `RGC_BOOTSTRAP_REG_KEY` or `RGC_SUPER_ADMIN_REG_KEY` and use it to create the first super admin.</div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="mt-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="mt-4 p-3 rounded-lg text-sm bg-emerald-50 text-emerald-700 border border-emerald-200"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($activationLink): ?>
    <div class="mt-3 p-3 rounded-lg text-sm bg-indigo-50 text-indigo-700 border border-indigo-200 break-all">
      Activation link: <a class="font-semibold underline" href="<?php echo htmlspecialchars($activationLink); ?>"><?php echo htmlspecialchars($activationLink); ?></a>
    </div>
    <?php endif; ?>

    <form method="post" class="mt-5 space-y-4">
      <?php echo rgcCsrfField('admin_register'); ?>
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium text-slate-700">Full Name</label>
          <input name="name" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" required>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Username</label>
          <input name="username" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" required>
        </div>
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700">Email</label>
        <input name="email" type="email" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" required>
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700">Role</label>
        <select name="role" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
          <?php if ($isBootstrap): ?>
          <option value="super_admin">Super Admin</option>
          <?php else: ?>
          <option value="admin">Admin</option>
          <option value="super_admin">Super Admin</option>
          <?php endif; ?>
        </select>
      </div>
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium text-slate-700">Password</label>
          <input name="password" type="password" minlength="10" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" required>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Confirm Password</label>
          <input name="confirm_password" type="password" minlength="10" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" required>
        </div>
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700"><?php echo $isBootstrap ? 'Bootstrap Key' : 'Registration Key'; ?></label>
        <input name="registration_key" type="password" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" required>
      </div>
      <button class="w-full bg-slate-900 text-white py-2.5 rounded-lg hover:bg-slate-800 font-medium">Create Account</button>
    </form>

    <p class="text-xs text-slate-500 mt-5">
      Already activated? <a class="text-indigo-700 hover:underline" href="<?php echo rgcUrl('admin/login.php'); ?>">Go to login</a>
    </p>
  </div>
</body>
</html>
