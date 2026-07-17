<?php
require __DIR__ . '/includes/app.php';
rgcEnforcePublicAccess();
$pageTitle = 'Donate';
$error = '';
$ok = false;
$statusMsg = '';
$amount = 0;
$currency = 'KES';
$method = 'paybill';
$givingType = 'donation';
$note = '';
$name = '';
$email = '';
$phone = '';

$paybillNumber = trim((string) rgcConfig('donate.paybill.number', '122766'));
$accountLabels = [
  'offering' => trim((string) rgcConfig('donate.paybill.offering_account', 'Offering')),
  'tithe' => trim((string) rgcConfig('donate.paybill.tithe_account', 'Tithe')),
  'donation' => trim((string) rgcConfig('donate.paybill.donation_account', 'Donation')),
];
$paystackUrl = trim((string) rgcConfig('donate.paystack.url', ''));
$bankName = trim((string) rgcConfig('donate.bank.name', ''));
$bankAcc = trim((string) rgcConfig('donate.bank.account', ''));
$supportWhatsapp = trim((string) rgcConfig('donate.contact.whatsapp', '0722551152'));
$supportEmail = trim((string) rgcConfig('donate.contact.email', 'redeemedgospelchurch.eldoret@gmail.com'));

if (($_GET['status'] ?? '') === 'success') {
  $statusMsg = 'Thank you. Your donation was received successfully.';
} elseif (($_GET['status'] ?? '') === 'cancel') {
  $statusMsg = 'Payment was cancelled. You can try again anytime.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('public_donate');
  $amountInput = trim((string) ($_POST['amount'] ?? '0'));
  $amount = (int) round(((float) $amountInput) * 100);
  $currency = strtoupper(trim((string) ($_POST['currency'] ?? 'KES')));
  $note = trim((string) ($_POST['note'] ?? ''));
  $name = trim((string) ($_POST['name'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $phone = trim((string) ($_POST['phone'] ?? ''));
  $method = trim((string) ($_POST['method'] ?? 'paybill'));
  $givingType = trim((string) ($_POST['giving_type'] ?? 'donation'));

  if (!in_array($method, ['paybill', 'paystack', 'bank', 'cash'], true)) {
    $error = 'Select a valid payment method.';
  } elseif (!in_array($givingType, ['offering', 'tithe', 'donation'], true)) {
    $error = 'Select a valid giving type.';
  } elseif ($currency !== 'KES') {
    $error = 'Unsupported currency.';
  } elseif ($method === 'paystack' && $paystackUrl === '') {
    $error = 'Paystack is not configured yet. Add your Paystack payment link first.';
  } elseif ($amount <= 0 || $amount > 100000000) {
    $error = 'Enter a valid amount.';
  } elseif (!rgcDbAvailable()) {
    $error = 'Service temporarily unavailable.';
  } else {
    $user = rgcPublicUser();
    $stmt = rgcDb()->prepare('INSERT INTO donations (user_id, full_name, email, amount_cents, currency, giving_type, note, status, method, phone, created_at, updated_at) VALUES (:user_id, :full_name, :email, :amount_cents, :currency, :giving_type, :note, :status, :method, :phone, NOW(), NOW())');
    $stmt->execute([
      ':user_id' => $user ? (int) ($user['id'] ?? 0) : null,
      ':full_name' => $name,
      ':email' => $email,
      ':amount_cents' => $amount,
      ':currency' => $currency,
      ':giving_type' => $givingType,
      ':note' => $note,
      ':status' => 'pending',
      ':method' => $method,
      ':phone' => $phone,
    ]);
    $_SESSION['donation_last_id'] = (int) rgcDb()->lastInsertId();
    $ok = true;
  }
}

$selectedAccount = $accountLabels[$givingType] ?? $accountLabels['donation'];
$amountDisplay = number_format($amount / 100, 2);
$whatsAppHref = 'https://wa.me/254' . preg_replace('/^0/', '', $supportWhatsapp);

require __DIR__ . '/includes/header.php';
?>
<section class="section-padding bg-slate-50">
  <div class="max-w-3xl mx-auto px-4">
    <h1 class="text-3xl font-bold text-slate-900 mb-2">Give to Redeemed Gospel Church</h1>
    <p class="text-slate-600 mb-6">Support the ministry through church paybill, Paystack, bank request, or in-person giving.</p>

    <?php if ($statusMsg !== ''): ?>
    <div class="mb-4 p-3 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200"><?php echo htmlspecialchars($statusMsg); ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
    <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($ok): ?>
    <div class="mb-4 p-4 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200">
      Thank you. Your giving intent has been recorded.
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <?php if ($method === 'paybill'): ?>
      <div class="p-5 rounded-xl border border-slate-200 bg-white">
        <p class="font-semibold text-slate-900">Church Paybill</p>
        <p class="text-sm text-slate-700 mt-3">Paybill Number: <strong><?php echo htmlspecialchars($paybillNumber); ?></strong></p>
        <p class="text-sm text-slate-700 mt-2">Account Number: <strong><?php echo htmlspecialchars($selectedAccount); ?></strong></p>
        <p class="text-sm text-slate-700 mt-2">Amount: <strong><?php echo htmlspecialchars($amountDisplay); ?> <?php echo htmlspecialchars($currency); ?></strong></p>
        <p class="text-xs text-slate-500 mt-3">Use the selected giving type as the account number when paying.</p>
      </div>
      <?php endif; ?>

      <?php if ($method === 'paystack'): ?>
      <div class="p-5 rounded-xl border border-slate-200 bg-white">
        <p class="font-semibold text-slate-900">Paystack</p>
        <p class="text-sm text-slate-700 mt-3">Giving Type: <strong><?php echo htmlspecialchars(ucfirst($givingType)); ?></strong></p>
        <p class="text-sm text-slate-700 mt-2">Amount: <strong><?php echo htmlspecialchars($amountDisplay); ?> <?php echo htmlspecialchars($currency); ?></strong></p>
        <a href="<?php echo htmlspecialchars($paystackUrl); ?>" target="_blank" rel="noopener" class="inline-flex mt-4 px-4 py-2 rounded-lg bg-indigo-600 text-white">Open Paystack</a>
      </div>
      <?php endif; ?>

      <?php if ($method === 'bank'): ?>
      <div class="p-5 rounded-xl border border-slate-200 bg-white">
        <p class="font-semibold text-slate-900">Bank Giving</p>
        <?php if ($bankName !== '' && $bankAcc !== ''): ?>
        <p class="text-sm text-slate-700 mt-3">Bank: <strong><?php echo htmlspecialchars($bankName); ?></strong></p>
        <p class="text-sm text-slate-700 mt-2">Account Number: <strong><?php echo htmlspecialchars($bankAcc); ?></strong></p>
        <?php else: ?>
        <p class="text-sm text-slate-700 mt-3">Bank details are shared on request.</p>
        <p class="text-sm text-slate-700 mt-2">WhatsApp: <strong><?php echo htmlspecialchars($supportWhatsapp); ?></strong></p>
        <p class="text-sm text-slate-700 mt-2">Email: <strong><?php echo htmlspecialchars($supportEmail); ?></strong></p>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($method === 'cash'): ?>
      <div class="p-5 rounded-xl border border-slate-200 bg-white">
        <p class="font-semibold text-slate-900">Cash / In-Person</p>
        <p class="text-sm text-slate-700 mt-3">Please visit the church office or speak to an usher to complete your giving.</p>
      </div>
      <?php endif; ?>

      <div class="p-5 rounded-xl border border-slate-200 bg-white">
        <p class="font-semibold text-slate-900">Need Help?</p>
        <p class="text-sm text-slate-700 mt-3">WhatsApp: <a href="<?php echo htmlspecialchars($whatsAppHref); ?>" target="_blank" rel="noopener" class="text-green-700"><?php echo htmlspecialchars($supportWhatsapp); ?></a></p>
        <p class="text-sm text-slate-700 mt-2">Email: <a href="mailto:<?php echo htmlspecialchars($supportEmail); ?>" class="text-indigo-700"><?php echo htmlspecialchars($supportEmail); ?></a></p>
      </div>
    </div>
    <?php else: ?>
    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
      <form method="post" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" id="donateForm">
        <?php echo rgcCsrfField('public_donate'); ?>

        <div>
          <label class="form-label">Amount (KES)</label>
          <input name="amount" type="number" min="10" step="1" class="form-input" required value="<?php echo $amount > 0 ? htmlspecialchars((string) ($amount / 100)) : ''; ?>">
        </div>

        <div>
          <label class="form-label">Giving Type</label>
          <select name="giving_type" class="form-input" required>
            <option value="offering" <?php echo $givingType === 'offering' ? 'selected' : ''; ?>>Offering</option>
            <option value="tithe" <?php echo $givingType === 'tithe' ? 'selected' : ''; ?>>Tithe</option>
            <option value="donation" <?php echo $givingType === 'donation' ? 'selected' : ''; ?>>Donation</option>
          </select>
        </div>

        <div>
          <label class="form-label">Payment Method</label>
          <div class="grid sm:grid-cols-2 gap-3" id="methodTiles">
            <label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 bg-white cursor-pointer select-none" data-method="paybill">
              <input type="radio" name="method" value="paybill" class="hidden" <?php echo $method === 'paybill' ? 'checked' : ''; ?>>
              <span class="font-medium text-slate-800">Church Paybill</span>
            </label>
            <label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 bg-white cursor-pointer select-none" data-method="paystack">
              <input type="radio" name="method" value="paystack" class="hidden" <?php echo $method === 'paystack' ? 'checked' : ''; ?>>
              <span class="font-medium text-slate-800">Paystack</span>
            </label>
            <label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 bg-white cursor-pointer select-none" data-method="bank">
              <input type="radio" name="method" value="bank" class="hidden" <?php echo $method === 'bank' ? 'checked' : ''; ?>>
              <span class="font-medium text-slate-800">Bank Request</span>
            </label>
            <label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 bg-white cursor-pointer select-none" data-method="cash">
              <input type="radio" name="method" value="cash" class="hidden" <?php echo $method === 'cash' ? 'checked' : ''; ?>>
              <span class="font-medium text-slate-800">Cash / In-Person</span>
            </label>
          </div>
        </div>

        <div>
          <label class="form-label">Your Name</label>
          <input name="name" class="form-input" value="<?php echo htmlspecialchars($name); ?>">
        </div>

        <div>
          <label class="form-label">Email</label>
          <input name="email" type="email" class="form-input" value="<?php echo htmlspecialchars($email); ?>">
        </div>

        <div>
          <label class="form-label">Phone / WhatsApp</label>
          <input name="phone" class="form-input" value="<?php echo htmlspecialchars($phone); ?>" placeholder="0722551152">
        </div>

        <div>
          <label class="form-label">Note (optional)</label>
          <input name="note" class="form-input" value="<?php echo htmlspecialchars($note); ?>" placeholder="Thanksgiving, building fund, special seed">
        </div>

        <input type="hidden" name="currency" value="KES">
        <button class="btn btn-primary w-full py-3.5">Continue Giving</button>
      </form>

      <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="font-semibold text-slate-900">Church Paybill</p>
          <p class="text-sm text-slate-700 mt-3">Paybill Number: <strong><?php echo htmlspecialchars($paybillNumber); ?></strong></p>
          <p class="text-sm text-slate-700 mt-2">Account Numbers: <strong><?php echo htmlspecialchars($accountLabels['offering']); ?></strong>, <strong><?php echo htmlspecialchars($accountLabels['tithe']); ?></strong>, <strong><?php echo htmlspecialchars($accountLabels['donation']); ?></strong></p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="font-semibold text-slate-900">Paystack</p>
          <?php if ($paystackUrl !== ''): ?>
          <a href="<?php echo htmlspecialchars($paystackUrl); ?>" target="_blank" rel="noopener" class="inline-flex mt-3 px-4 py-2 rounded-lg bg-indigo-600 text-white">Open Paystack</a>
          <?php else: ?>
          <p class="text-sm text-slate-700 mt-3">Add your Paystack payment link in `.env` to enable this option.</p>
          <?php endif; ?>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="font-semibold text-slate-900">Bank Account Request</p>
          <?php if ($bankName !== '' && $bankAcc !== ''): ?>
          <p class="text-sm text-slate-700 mt-3">Bank: <strong><?php echo htmlspecialchars($bankName); ?></strong></p>
          <p class="text-sm text-slate-700 mt-2">Account Number: <strong><?php echo htmlspecialchars($bankAcc); ?></strong></p>
          <?php else: ?>
          <p class="text-sm text-slate-700 mt-3">Request bank details on WhatsApp or email.</p>
          <p class="text-sm text-slate-700 mt-2">WhatsApp: <strong><?php echo htmlspecialchars($supportWhatsapp); ?></strong></p>
          <p class="text-sm text-slate-700 mt-2">Email: <strong><?php echo htmlspecialchars($supportEmail); ?></strong></p>
          <?php endif; ?>
        </div>

        <p class="text-xs text-slate-500">Security: the site records giving intent and donor details, but does not store card or mobile money credentials.</p>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
<script>
(function () {
  const tiles = document.querySelectorAll('#methodTiles label[data-method]');
  const inputs = document.querySelectorAll('#methodTiles input[type="radio"]');
  function paint() {
    tiles.forEach((tile) => tile.classList.remove('border-indigo-500', 'bg-indigo-50'));
    const checked = Array.from(inputs).find((input) => input.checked);
    if (!checked) return;
    const selectedTile = checked.closest('label');
    if (selectedTile) {
      selectedTile.classList.add('border-indigo-500', 'bg-indigo-50');
    }
  }
  tiles.forEach((tile) => tile.addEventListener('click', paint));
  inputs.forEach((input) => input.addEventListener('change', paint));
  paint();
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
