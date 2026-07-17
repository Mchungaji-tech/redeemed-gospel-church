<?php
require __DIR__ . '/includes/auth.php';
requireSuperAdmin();
$pageTitle = 'Public Users';
$rows = [];
$total = 0;
$q = '';
$page = 1;
$per = 50;
$error = '';
$dbAvailable = rgcDbAvailable();

try {
  $q = trim((string) ($_GET['q'] ?? ''));
  $page = max(1, (int) ($_GET['page'] ?? 1));
  $per = (int) ($_GET['per'] ?? 50);
  if ($per < 10) { $per = 10; }
  if ($per > 200) { $per = 200; }
  $offset = ($page - 1) * $per;
  
  if ($dbAvailable) {
    // Check if public_users table exists
    $tableExists = false;
    try {
      $stmt = rgcDb()->query('SELECT 1 FROM public_users LIMIT 1');
      $tableExists = true;
    } catch (Throwable $e) {
      $tableExists = false;
    }
    
    if (!$tableExists) {
      $error = 'The public_users table does not exist. Please run the database schema to create it.';
    } else {
      $where = '';
      $params = [];
      if ($q !== '') {
        $where = 'WHERE email LIKE :q OR full_name LIKE :q OR id = :idq';
        $params[':q'] = '%' . $q . '%';
        $params[':idq'] = ctype_digit($q) ? (int) $q : -1;
      }
      $countSql = 'SELECT COUNT(*) AS c FROM public_users ' . $where;
      $cstmt = rgcDb()->prepare($countSql);
      foreach ($params as $k => $v) { $cstmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR); }
      $cstmt->execute();
      $total = (int) ($cstmt->fetch()['c'] ?? 0);
      $sql = 'SELECT id, full_name, email, is_verified, country, last_login_at, last_active_at, created_at FROM public_users ' . $where . ' ORDER BY COALESCE(last_active_at, \'1970-01-01\') DESC, id DESC LIMIT :lim OFFSET :off';
      $stmt = rgcDb()->prepare($sql);
      foreach ($params as $k => $v) { $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR); }
      $stmt->bindValue(':lim', $per, PDO::PARAM_INT);
      $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
      $stmt->execute();
      $rows = $stmt->fetchAll();
    }
  } else {
    $error = 'Database is not available. Please configure database settings in .env file.';
  }
} catch (Throwable $e) {
  $error = 'Error: ' . $e->getMessage();
  $rows = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Public Users</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>
  <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <section class="rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-700 text-white p-6 md:p-8 shadow-xl">
      <div class="flex flex-wrap items-start justify-between gap-5">
        <div>
          <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Directory</p>
          <h2 class="text-2xl md:text-3xl font-bold mt-1">Public Users</h2>
          <p class="text-slate-200 mt-2 max-w-2xl">Search and view members. Online means active in the last 10 minutes.</p>
        </div>
        <form class="flex items-center gap-2" method="get">
          <input name="q" value="<?php echo htmlspecialchars($q); ?>" class="px-3 py-2 rounded-lg border border-white/20 bg-white/10 text-white placeholder-white/70" placeholder="Search name or email">
          <select name="per" class="px-3 py-2 rounded-lg border border-white/20 bg-white/10 text-white">
            <?php foreach ([25,50,100,200] as $opt): ?>
            <option value="<?php echo $opt; ?>" <?php echo (int)$per===$opt?'selected':''; ?>><?php echo $opt; ?>/page</option>
            <?php endforeach; ?>
          </select>
          <button class="px-4 py-2 rounded-lg bg-white text-slate-900 font-semibold">Search</button>
        </form>
      </div>
    </section>
    <section class="bg-white rounded-xl border border-slate-200 shadow">
      <?php if ($error): ?>
      <div class="m-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <div class="px-4 py-2 text-sm text-slate-600">Total: <?php echo (int) $total; ?> · Page <?php echo (int) $page; ?></div>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="text-left px-4 py-2">Name</th>
              <th class="text-left px-4 py-2">Email</th>
              <th class="text-left px-4 py-2">Country</th>
              <th class="text-left px-4 py-2">Verified</th>
              <th class="text-left px-4 py-2">Status</th>
              <th class="text-left px-4 py-2">Last Login</th>
              <th class="text-left px-4 py-2">Last Active</th>
              <th class="text-left px-4 py-2">Joined</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($rows as $r): ?>
            <?php
              $active = (string) ($r['last_active_at'] ?? '');
              $online = false;
              if ($active !== '') {
                $online = (strtotime($active) >= time() - 600);
              }
            ?>
            <tr>
              <td class="px-4 py-2 font-medium text-slate-900"><?php echo htmlspecialchars((string) $r['full_name']); ?></td>
              <td class="px-4 py-2 text-slate-700"><?php echo htmlspecialchars((string) $r['email']); ?></td>
              <td class="px-4 py-2"><?php $c = (string) ($r['country'] ?? ''); if ($c) { $cc = rgcGetCountryCode($c); $flag = rgcCountryToFlag($cc); echo $flag ? $flag . ' ' . htmlspecialchars($c) : htmlspecialchars($c); } else { echo '<span class="text-slate-400">-</span>'; } ?></td>
              <td class="px-4 py-2"><?php echo !empty($r['is_verified']) ? '<span class="px-2 py-1 rounded bg-emerald-100 text-emerald-700 text-xs">yes</span>' : '<span class="px-2 py-1 rounded bg-amber-100 text-amber-700 text-xs">no</span>'; ?></td>
              <td class="px-4 py-2"><?php echo $online ? '<span class="px-2 py-1 rounded bg-emerald-100 text-emerald-700 text-xs">online</span>' : '<span class="px-2 py-1 rounded bg-slate-100 text-slate-700 text-xs">offline</span>'; ?></td>
              <td class="px-4 py-2 text-slate-600"><?php echo htmlspecialchars((string) ($r['last_login_at'] ?? '')); ?></td>
              <td class="px-4 py-2 text-slate-600"><?php echo htmlspecialchars((string) ($r['last_active_at'] ?? '')); ?></td>
              <td class="px-4 py-2 text-slate-600"><?php echo htmlspecialchars((string) ($r['created_at'] ?? '')); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No users found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php
        $baseQs = $_GET;
        unset($baseQs['page']);
        $base = strtok($_SERVER['REQUEST_URI'], '?');
        $pages = $per > 0 ? max(1, (int) ceil($total / $per)) : 1;
      ?>
      <?php if ($pages > 1): ?>
      <div class="flex items-center justify-between px-4 py-3 border-t border-slate-200 text-sm">
        <a class="px-3 py-1.5 rounded border border-slate-300 bg-white <?php echo $page<=1?'opacity-50 pointer-events-none':''; ?>" href="<?php $p=$baseQs; $p['page']=max(1,$page-1); echo htmlspecialchars($base.'?'.http_build_query($p)); ?>">Prev</a>
        <span>Page <?php echo (int)$page; ?> of <?php echo (int)$pages; ?></span>
        <a class="px-3 py-1.5 rounded border border-slate-300 bg-white <?php echo $page>=$pages?'opacity-50 pointer-events-none':''; ?>" href="<?php $p=$baseQs; $p['page']=min($pages,$page+1); echo htmlspecialchars($base.'?'.http_build_query($p)); ?>">Next</a>
      </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
