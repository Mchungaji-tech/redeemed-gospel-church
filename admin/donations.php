<?php
require __DIR__ . '/includes/auth.php';
requireLogin();

$rows = [];
$error = '';
$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$method = trim((string) ($_GET['method'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$per = (int) ($_GET['per'] ?? 25);
if ($per < 10) { $per = 10; }
if ($per > 100) { $per = 100; }
$offset = ($page - 1) * $per;
$total = 0;

$countReceived = 0;
$countPending = 0;
$countFailed = 0;
$sumReceivedCents = 0;

if (rgcDbAvailable()) {
  try {
    $w = [];
    $params = [];
    if ($q !== '') {
      $w[] = '(full_name LIKE :q OR email LIKE :q)';
      $params[':q'] = '%' . $q . '%';
    }
    if (in_array($status, ['pending', 'received', 'failed'], true)) {
      $w[] = 'status = :status';
      $params[':status'] = $status;
    }
    if (in_array($method, ['mpesa', 'bank', 'paypal', 'stripe', 'cash'], true)) {
      $w[] = 'method = :method';
      $params[':method'] = $method;
    }
    if ($dateFrom !== '') {
      $w[] = 'DATE(created_at) >= :date_from';
      $params[':date_from'] = $dateFrom;
    }
    if ($dateTo !== '') {
      $w[] = 'DATE(created_at) <= :date_to';
      $params[':date_to'] = $dateTo;
    }
    $whereSql = empty($w) ? '' : (' WHERE ' . implode(' AND ', $w));

    $cstmt = rgcDb()->prepare('SELECT COUNT(*) AS c FROM donations' . $whereSql);
    foreach ($params as $k => $v) {
      $cstmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $cstmt->execute();
    $total = (int) ($cstmt->fetch()['c'] ?? 0);

    $sql = 'SELECT id, user_id, full_name, email, amount_cents, currency, note, status, method, reference, phone, created_at, updated_at
            FROM donations' . $whereSql . ' ORDER BY id DESC LIMIT :lim OFFSET :off';
    $stmt = rgcDb()->prepare($sql);
    foreach ($params as $k => $v) {
      $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(':lim', $per, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $sum = rgcDb()->query(
      "SELECT
        SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) AS received_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_count,
        SUM(CASE WHEN status = 'received' THEN amount_cents ELSE 0 END) AS received_cents
      FROM donations"
    )->fetch();
    $countReceived = (int) ($sum['received_count'] ?? 0);
    $countPending = (int) ($sum['pending_count'] ?? 0);
    $countFailed = (int) ($sum['failed_count'] ?? 0);
    $sumReceivedCents = (int) ($sum['received_cents'] ?? 0);
  } catch (Throwable $e) {
    $rows = [];
    $error = 'Unable to load donation records.';
  }
} else {
  $error = 'Database unavailable.';
}

function rgcFormatAmount(int $cents, string $currency): string {
  $currency = strtoupper(trim($currency));
  if ($currency === '') {
    $currency = 'KES';
  }
  return $currency . ' ' . number_format($cents / 100, 2);
}

$pages = $per > 0 ? max(1, (int) ceil($total / $per)) : 1;
$baseQs = $_GET;
unset($baseQs['page']);
$baseUrl = rgcUrl('admin/donations.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Donations</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <section class="rounded-2xl bg-gradient-to-r from-cyan-700 via-teal-600 to-sky-600 text-white p-6 shadow-lg">
      <h1 class="text-2xl font-bold">Donations</h1>
      <p class="mt-2 text-cyan-100">Read-only donation records with filters and pagination.</p>
    </section>

    <section class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <article class="bg-white rounded-xl p-5 shadow border border-slate-200">
        <p class="text-sm font-medium text-slate-500">Total Records</p>
        <p class="text-3xl font-bold mt-2 text-slate-900"><?php echo (int) $total; ?></p>
      </article>
      <article class="bg-white rounded-xl p-5 shadow border border-slate-200">
        <p class="text-sm font-medium text-slate-500">Received</p>
        <p class="text-3xl font-bold mt-2 text-emerald-700"><?php echo (int) $countReceived; ?></p>
      </article>
      <article class="bg-white rounded-xl p-5 shadow border border-slate-200">
        <p class="text-sm font-medium text-slate-500">Pending</p>
        <p class="text-3xl font-bold mt-2 text-amber-700"><?php echo (int) $countPending; ?></p>
      </article>
      <article class="bg-white rounded-xl p-5 shadow border border-slate-200">
        <p class="text-sm font-medium text-slate-500">Received Value</p>
        <p class="text-2xl font-bold mt-2 text-slate-900"><?php echo htmlspecialchars(rgcFormatAmount($sumReceivedCents, 'KES')); ?></p>
      </article>
    </section>

    <section class="bg-white rounded-xl shadow border border-slate-200">
      <div class="p-4 border-b border-slate-200">
        <form method="get" class="grid md:grid-cols-6 gap-2">
          <input name="q" value="<?php echo htmlspecialchars($q); ?>" class="md:col-span-2 border border-slate-300 rounded-lg px-3 py-2" placeholder="Search name or email">
          <select name="status" class="border border-slate-300 rounded-lg px-3 py-2">
            <option value="">All status</option>
            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>pending</option>
            <option value="received" <?php echo $status === 'received' ? 'selected' : ''; ?>>received</option>
            <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>failed</option>
          </select>
          <select name="method" class="border border-slate-300 rounded-lg px-3 py-2">
            <option value="">All methods</option>
            <option value="mpesa" <?php echo $method === 'mpesa' ? 'selected' : ''; ?>>mpesa</option>
            <option value="paypal" <?php echo $method === 'paypal' ? 'selected' : ''; ?>>paypal</option>
            <option value="bank" <?php echo $method === 'bank' ? 'selected' : ''; ?>>bank</option>
            <option value="cash" <?php echo $method === 'cash' ? 'selected' : ''; ?>>cash</option>
            <option value="stripe" <?php echo $method === 'stripe' ? 'selected' : ''; ?>>stripe</option>
          </select>
          <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" class="border border-slate-300 rounded-lg px-3 py-2">
          <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" class="border border-slate-300 rounded-lg px-3 py-2">
          <input type="hidden" name="per" value="<?php echo (int) $per; ?>">
          <button class="md:col-span-1 px-4 py-2 rounded-lg bg-slate-900 text-white">Filter</button>
        </form>
      </div>

      <?php if ($error !== ''): ?>
      <p class="m-4 text-sm rounded bg-rose-50 text-rose-700 px-3 py-2"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="text-left px-4 py-3">Name</th>
              <th class="text-left px-4 py-3">Email</th>
              <th class="text-left px-4 py-3">Amount</th>
              <th class="text-left px-4 py-3">Method</th>
              <th class="text-left px-4 py-3">Status</th>
              <th class="text-left px-4 py-3">Phone / Ref</th>
              <th class="text-left px-4 py-3">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($rows as $r): ?>
            <tr>
              <td class="px-4 py-3 text-slate-900"><?php echo htmlspecialchars((string) ($r['full_name'] ?? '')); ?></td>
              <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string) ($r['email'] ?? '')); ?></td>
              <td class="px-4 py-3 text-slate-900 font-medium"><?php echo htmlspecialchars(rgcFormatAmount((int) ($r['amount_cents'] ?? 0), (string) ($r['currency'] ?? 'KES'))); ?></td>
              <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string) ($r['method'] ?? '')); ?></td>
              <td class="px-4 py-3">
                <?php $st = (string) ($r['status'] ?? 'pending'); ?>
                <span class="text-xs px-2 py-1 rounded <?php echo $st === 'received' ? 'bg-emerald-100 text-emerald-700' : ($st === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700'); ?>">
                  <?php echo htmlspecialchars($st); ?>
                </span>
              </td>
              <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string) (($r['phone'] ?? '') !== '' ? $r['phone'] : ($r['reference'] ?? ''))); ?></td>
              <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars((string) ($r['created_at'] ?? '')); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No donation records found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($pages > 1): ?>
      <div class="flex items-center justify-between px-4 py-3 border-t border-slate-200 text-sm">
        <a class="px-3 py-1.5 rounded border border-slate-300 bg-white <?php echo $page <= 1 ? 'opacity-50 pointer-events-none' : ''; ?>" href="<?php $p = $baseQs; $p['page'] = max(1, $page - 1); echo htmlspecialchars($baseUrl . '?' . http_build_query($p)); ?>">Prev</a>
        <span>Page <?php echo (int) $page; ?> of <?php echo (int) $pages; ?></span>
        <a class="px-3 py-1.5 rounded border border-slate-300 bg-white <?php echo $page >= $pages ? 'opacity-50 pointer-events-none' : ''; ?>" href="<?php $p = $baseQs; $p['page'] = min($pages, $page + 1); echo htmlspecialchars($baseUrl . '?' . http_build_query($p)); ?>">Next</a>
      </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
