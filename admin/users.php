<?php
require __DIR__ . '/includes/auth.php';
requireSuperAdmin();

$error = '';
$activationLink = '';
$users = rgcListUsers();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('admin_users');
  $username = trim((string) ($_POST['username'] ?? ''));
  $name = trim((string) ($_POST['name'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $role = (($_POST['role'] ?? 'admin') === 'super_admin') ? 'super_admin' : 'admin';
  $password = (string) ($_POST['password'] ?? '');

  $result = rgcCreateUser($name, $username, $email, $role, $password, rgcCurrentUserId());
  if (!($result['ok'] ?? false)) {
    $error = (string) ($result['error'] ?? 'Unable to create user.');
  } else {
    $activationLink = (string) ($result['activation_link'] ?? '');
    $users = rgcListUsers();
    header('Location: ' . rgcUrl('admin/users.php?saved=1&activation=' . urlencode($activationLink)));
    exit;
  }
}

if (isset($_GET['activation'])) {
  $activationLink = (string) $_GET['activation'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <section class="rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-700 text-white p-6 shadow-lg">
      <h1 class="text-2xl font-bold">Admin User Management</h1>
      <p class="text-slate-200 mt-2">Create and manage platform operators with activation and verification controls.</p>
    </section>

    <section class="grid xl:grid-cols-5 gap-6">
      <article class="bg-white rounded-xl shadow border border-slate-200 p-6 xl:col-span-2">
        <h2 class="text-lg font-bold text-slate-900">Create New User</h2>
        <p class="text-sm text-slate-500 mt-1 mb-4">New users are inactive until they open their activation link.</p>

        <?php if (isset($_GET['saved'])): ?>
        <p class="mb-3 text-sm rounded bg-emerald-50 text-emerald-700 px-3 py-2">User created successfully.</p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
        <p class="mb-3 text-sm rounded bg-red-50 text-red-700 px-3 py-2"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($activationLink !== ''): ?>
        <p class="mb-3 text-sm rounded bg-indigo-50 text-indigo-700 px-3 py-2 break-all">
          Activation link: <a class="underline font-semibold" href="<?php echo htmlspecialchars($activationLink); ?>"><?php echo htmlspecialchars($activationLink); ?></a>
        </p>
        <?php endif; ?>

        <form method="post" class="space-y-3">
          <?php echo rgcCsrfField('admin_users'); ?>
          <div>
            <label class="text-sm font-medium text-slate-700">Full Name</label>
            <input name="name" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="Full name" required>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Username</label>
            <input name="username" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="Username" required>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Email</label>
            <input name="email" type="email" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="name@email.com" required>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Password</label>
            <input name="password" type="password" minlength="10" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" placeholder="At least 10 characters" required>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Role</label>
            <select name="role" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2">
              <option value="admin">Admin</option>
              <option value="super_admin">Super Admin</option>
            </select>
          </div>
          <button class="px-5 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Create User</button>
        </form>
      </article>

      <article class="bg-white rounded-xl shadow border border-slate-200 p-6 xl:col-span-3">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-bold text-slate-900">Existing Users</h2>
          <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600"><?php echo count($users); ?> users</span>
        </div>

        <div class="space-y-3">
          <?php foreach ($users as $u): ?>
          <article class="rounded-xl border border-slate-200 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div>
                <p class="font-semibold text-slate-900"><?php echo htmlspecialchars((string) ($u['full_name'] ?? '')); ?></p>
                <p class="text-sm text-slate-600">@<?php echo htmlspecialchars((string) ($u['username'] ?? '')); ?> | <?php echo htmlspecialchars((string) ($u['email'] ?? '')); ?></p>
                <p class="text-xs text-slate-500 mt-1">Last login: <?php echo htmlspecialchars((string) ($u['last_login_at'] ?? 'Never')); ?></p>
              </div>
              <div class="flex flex-col items-end gap-2">
                <span class="text-xs px-2 py-1 rounded-full <?php echo ($u['role'] ?? '') === 'super_admin' ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700'; ?>">
                  <?php echo htmlspecialchars((string) ($u['role'] ?? 'admin')); ?>
                </span>
                <span class="text-xs px-2 py-1 rounded-full <?php echo !empty($u['is_active']) ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?>">
                  <?php echo !empty($u['is_active']) ? 'Active' : 'Pending Activation'; ?>
                </span>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
