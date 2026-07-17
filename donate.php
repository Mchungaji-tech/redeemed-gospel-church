<?php
require __DIR__ . '/includes/app.php';
rgcEnforcePublicAccess();
$pageTitle = 'Donate';
$error = '';
$ok = false;
$statusMsg = '';

if (($_GET['status'] ?? '') === 'success') {
  $statusMsg = 'Thank you. Your payment was successful.';
} elseif (($_GET['status'] ?? '') === 'cancel') {
  $statusMsg = 'Payment was cancelled. You can try again anytime.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('public_donate');
  $amountInput = trim((string) ($_POST['amount'] ?? '0'));
  $amountFloat = (float) $amountInput;
  $amount = (int) round($amountFloat * 100);
  $currency = strtoupper(trim((string) ($_POST['currency'] ?? 'KES')));
  $note = trim((string) ($_POST['note'] ?? ''));
  $name = trim((string) ($_POST['name'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $method = trim((string) ($_POST['method'] ?? 'mpesa'));
  $phone = trim((string) ($_POST['phone'] ?? ''));

  if (!in_array($method, ['mpesa', 'paypal', 'stripe', 'bank', 'cash'], true)) {
    $error = 'Select a valid payment method.';
  } elseif ($currency !== 'KES') {
    $error = 'Unsupported currency.';
  } elseif ($method === 'mpesa' && $phone === '') {
    $error = 'Enter your M‑Pesa phone number.';
  } elseif ($method === 'stripe' && trim((string) rgcConfig('donate.stripe.link', '')) === '') {
    $error = 'Card payments are not configured.';
  } elseif ($method === 'paypal' && (trim((string) rgcConfig('paypal.client_id', '')) === '' && trim((string) rgcConfig('paypal.sandbox_client_id', '')) === '' && trim((string) rgcConfig('paypal.live_client_id', '')) === '')) {
    $error = 'PayPal is not configured.';
  } elseif ($amount <= 0 || $amount > 100000000) {
    $error = 'Enter a valid amount.';
  } else {
    if (rgcDbAvailable()) {
      $user = rgcPublicUser();
      $stmt = rgcDb()->prepare('INSERT INTO donations (user_id, full_name, email, amount_cents, currency, note, status, method, phone, created_at, updated_at) VALUES (:user_id, :full_name, :email, :amount_cents, :currency, :note, :status, :method, :phone, NOW(), NOW())');
      $stmt->execute([
        ':user_id' => $user ? (int) ($user['id'] ?? 0) : null,
        ':full_name' => $name,
        ':email' => $email,
        ':amount_cents' => $amount,
        ':currency' => $currency,
        ':note' => $note,
        ':status' => 'pending',
        ':method' => $method,
        ':phone' => $phone,
      ]);
      $_SESSION['donation_last_id'] = (int) rgcDb()->lastInsertId();
      $ok = true;
    } else {
      $error = 'Service temporarily unavailable.';
    }
  }
}
require __DIR__ . '/includes/header.php';
?>
<section class="section-padding bg-slate-50">
  <div class="max-w-md mx-auto px-4">
    <h1 class="text-2xl font-bold text-slate-900 mb-2">Make a Donation</h1>
    <p class="text-slate-600 mb-6">Your support helps our ministry reach more people.</p>
    <?php if ($statusMsg !== ''): ?>
    <div class="mb-4 p-3 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200"><?php echo htmlspecialchars($statusMsg); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($ok): ?>
    <div class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">Thank you! Your donation intent has been recorded.</div>
    <?php
      $paybill = (string) rgcConfig('donate.mpesa.paybill', '');
      $account = (string) rgcConfig('donate.mpesa.account', '');
      $bankName = (string) rgcConfig('donate.bank.name', '');
      $bankAcc = (string) rgcConfig('donate.bank.account', '');
      $paypal = (string) rgcConfig('donate.paypal.url', '');
      $stripeLink = (string) rgcConfig('donate.stripe.link', '');
      $amountDisp = number_format(($amount ?? 0) / 100, 2);
    ?>
    <div class="space-y-4">
      <?php if (($method ?? '') === 'mpesa'): ?>
      <div class="p-4 rounded-lg border border-slate-200 bg-white">
        <p class="font-semibold text-slate-900">M‑Pesa (Lipa na M‑Pesa)</p>
        <p class="text-slate-700 text-sm mt-2">Paybill: <strong><?php echo htmlspecialchars($paybill); ?></strong> | Account: <strong><?php echo htmlspecialchars($account); ?></strong> | Amount: <strong><?php echo htmlspecialchars($amountDisp); ?> <?php echo htmlspecialchars($currency ?? 'KES'); ?></strong></p>
        <div class="mt-3 flex items-center gap-2">
          <input id="mpesaPhone" autocomplete="tel" class="form-input" placeholder="Enter M‑Pesa phone e.g. 2547XXXXXXXX" value="<?php echo htmlspecialchars((string) ($phone ?? '')); ?>">
          <button id="mpesaStkBtn" class="px-4 py-2 rounded-lg bg-emerald-600 text-white">Send STK Push</button>
        </div>
        <p id="mpesaStatus" class="text-xs text-slate-600 mt-2"></p>
      </div>
      <?php endif; ?>
      <?php if (($method ?? '') === 'bank'): ?>
      <div class="p-4 rounded-lg border border-slate-200 bg-white">
        <p class="font-semibold text-slate-900">Bank Transfer</p>
        <p class="text-slate-700 text-sm mt-2">Bank: <strong><?php echo htmlspecialchars($bankName); ?></strong> | Account: <strong><?php echo htmlspecialchars($bankAcc); ?></strong> | Amount: <strong><?php echo htmlspecialchars($amountDisp); ?> <?php echo htmlspecialchars($currency ?? 'KES'); ?></strong></p>
      </div>
      <?php endif; ?>
      <?php if (($method ?? '') === 'paypal'): ?>
      <div id="paypalButtons" class="mt-2"></div>
      <?php endif; ?>
      <?php if (($method ?? '') === 'stripe' && $stripeLink !== ''): ?>
      <a href="<?php echo htmlspecialchars($stripeLink); ?>" class="inline-flex px-4 py-2 rounded-lg bg-indigo-600 text-white" target="_blank">Pay by Card (Stripe)</a>
      <?php endif; ?>
      <?php if (($method ?? '') === 'cash'): ?>
      <div class="p-4 rounded-lg border border-slate-200 bg-white">
        <p class="font-semibold text-slate-900">Cash / In‑Person</p>
        <p class="text-slate-700 text-sm mt-2">Please visit the church office or speak to an usher to complete your donation. Your intent has been recorded.</p>
      </div>
      <?php endif; ?>
      <p class="text-xs text-slate-600">We never store sensitive payment details on this site.</p>
    </div>
    <?php else: ?>
    <form method="post" class="space-y-4" id="donateForm">
      <?php echo rgcCsrfField('public_donate'); ?>
      <div>
        <label class="form-label">Amount (KES)</label>
        <input name="amount" type="number" min="10" step="1" class="form-input" required>
      </div>
      <div>
        <label class="form-label">Payment Method</label>
        <div class="grid grid-cols-3 gap-2" id="methodTiles">
          <label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 bg-white cursor-pointer select-none" data-method="mpesa">
            <input type="radio" name="method" value="mpesa" class="hidden" checked>
            <span class="inline-flex items-center gap-2">
              <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M6 2h9a2 2 0 012 2v4H6m0 0H4a2 2 0 00-2 2v7a3 3 0 003 3h9a3 3 0 003-3V8M6 6v6m4-3h4"/></svg>
              M‑Pesa
            </span>
          </label>
          <label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 bg-white cursor-pointer select-none" data-method="paypal">
            <input type="radio" name="method" value="paypal" class="hidden">
            <span class="inline-flex items-center gap-2">
              <svg class="w-5 h-5 text-sky-600" viewBox="0 0 24 24"><path fill="currentColor" d="M7 21a1 1 0 01-.99-1.141l1.2-8A2 2 0 09 9 10h5.5c2.2 0 3.9 1.1 3.9 3.2 0 3-2.4 4.8-5.6 4.8H11l-.3 2.1A1 1 0 019.7 21H7zM10 9H6.6l.3-2.2A2 2 0 019 5h6.2c1.8 0 3.2.9 3.2 2.6 0 1.7-1.3 2.4-3 2.4H10z"/></svg>
              PayPal
            </span>
          </label>
          <label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 bg-white cursor-pointer select-none" data-method="stripe">
            <input type="radio" name="method" value="stripe" class="hidden">
            <span class="inline-flex items-center gap-2">
              <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h10M6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
              Card
            </span>
          </label>
          <label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 bg-white cursor-pointer select-none" data-method="bank">
            <input type="radio" name="method" value="bank" class="hidden">
            <span class="inline-flex items-center gap-2">
              <svg class="w-5 h-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-6 9 6M4 10h16v9H4zM2 19h20"/></svg>
              Bank
            </span>
          </label>
          <label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 bg-white cursor-pointer select-none" data-method="cash">
            <input type="radio" name="method" value="cash" class="hidden">
            <span class="inline-flex items-center gap-2">
              <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 7h18v10H3zM7 7v10M17 7v10M3 11h18"/></svg>
              Cash
            </span>
          </label>
        </div>
      </div>
      <div id="mpesaPhoneField" style="display:block;">
        <label class="form-label">M‑Pesa Phone</label>
        <input name="phone" class="form-input" placeholder="2547XXXXXXXX">
      </div>
      <div>
        <label class="form-label">Your Name</label>
        <input name="name" class="form-input">
      </div>
      <div>
        <label class="form-label">Email</label>
        <input name="email" type="email" class="form-input">
      </div>
      <div>
        <label class="form-label">Note (optional)</label>
        <input name="note" class="form-input">
      </div>
      <input type="hidden" name="currency" value="KES">
      <button class="btn btn-primary w-full py-3.5">Submit</button>
    </form>
    <?php
      $paybill = (string) rgcConfig('donate.mpesa.paybill', '');
      $account = (string) rgcConfig('donate.mpesa.account', '');
      $bankName = (string) rgcConfig('donate.bank.name', '');
      $bankAcc = (string) rgcConfig('donate.bank.account', '');
      $paypal = (string) rgcConfig('donate.paypal.url', '');
      $stripeLink = (string) rgcConfig('donate.stripe.link', '');
    ?>
    <div class="mt-4 grid gap-3 text-sm">
      <?php if ($paybill !== '' && $account !== ''): ?>
      <div class="p-3 rounded-lg border border-slate-200 bg-white">
        <p class="text-slate-700">M‑Pesa: Paybill <strong><?php echo htmlspecialchars($paybill); ?></strong>, Account <strong><?php echo htmlspecialchars($account); ?></strong></p>
      </div>
      <?php endif; ?>
      <?php if ($bankName !== '' && $bankAcc !== ''): ?>
      <div class="p-3 rounded-lg border border-slate-200 bg-white">
        <p class="text-slate-700">Bank: <strong><?php echo htmlspecialchars($bankName); ?></strong>, Account <strong><?php echo htmlspecialchars($bankAcc); ?></strong></p>
      </div>
      <?php endif; ?>
      <?php if ($paypal !== ''): ?>
      <div class="p-3 rounded-lg border border-slate-200 bg-white">
        <a href="<?php echo htmlspecialchars($paypal); ?>" target="_blank" class="text-indigo-700">Open PayPal</a>
      </div>
      <?php endif; ?>
      <?php if ($stripeLink !== ''): ?>
      <div class="p-3 rounded-lg border border-slate-200 bg-white">
        <a href="<?php echo htmlspecialchars($stripeLink); ?>" target="_blank" class="text-indigo-700">Pay by Card (Stripe)</a>
      </div>
      <?php endif; ?>
      <p class="text-xs text-slate-500">Security: CSRF, server validation, and no storage of sensitive card data.</p>
    </div>
    <?php endif; ?>
  </div>
</section>
<script>
(function(){
  const form = document.getElementById('donateForm');
  if (form) {
    const methodRadios = form.querySelectorAll('input[name="method"]');
    const mpesaField = document.getElementById('mpesaPhoneField');
    function updateFields() {
      let val = 'mpesa';
      methodRadios.forEach(r => { if (r.checked) val = r.value; });
      mpesaField.style.display = val === 'mpesa' ? 'block' : 'none';
    }
    methodRadios.forEach(r => r.addEventListener('change', updateFields));
    updateFields();
  }
})();
</script>
<script>
(function(){
  const tiles = document.querySelectorAll('#methodTiles label[data-method]');
  const inputs = document.querySelectorAll('#methodTiles input[type="radio"]');
  function paint() {
    tiles.forEach(t => t.classList.remove('border-indigo-500','bg-indigo-50'));
    const checked = Array.from(inputs).find(i => i.checked);
    if (!checked) return;
    const sel = checked.closest('label');
    if (sel) sel.classList.add('border-indigo-500','bg-indigo-50');
  }
  tiles.forEach(t => t.addEventListener('click', paint));
  inputs.forEach(i => i.addEventListener('change', paint));
  paint();
})();
</script>
<?php if (!empty($ok) && ($method ?? '') === 'paypal'): ?>
<?php
  $paypalClient = (string) rgcConfig('paypal.client_id', '');
  if ($paypalClient === '') {
    $mode = (string) rgcConfig('paypal.mode', 'sandbox');
    $paypalClient = (string) ($mode === 'live' ? rgcConfig('paypal.live_client_id', '') : rgcConfig('paypal.sandbox_client_id', ''));
  }
?>
<?php if ($paypalClient !== ''): ?>
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo urlencode($paypalClient); ?>&currency=<?php echo htmlspecialchars($currency ?? 'KES'); ?>"></script>
<script>
(function(){
  const buttonsContainer = document.getElementById('paypalButtons');
  if (!buttonsContainer || typeof paypal === 'undefined') return;
  const amount = <?php echo json_encode((float) ($amount ?? 0) / 100); ?>;
  const currency = <?php echo json_encode($currency ?? 'KES'); ?>;
  const donationId = <?php echo json_encode((int) ($_SESSION['donation_last_id'] ?? 0)); ?>;
  const csrf = <?php echo json_encode(rgcCsrfToken('public_donate')); ?>;
  paypal.Buttons({
    createOrder: function(data, actions) {
      return actions.order.create({
        purchase_units: [{ amount: { value: amount.toFixed(2), currency_code: currency } }]
      });
    },
    onApprove: function(data, actions) {
      return actions.order.capture().then(function(details) {
        if (!donationId) {
          buttonsContainer.innerHTML = '<div class="p-3 rounded-lg bg-rose-50 text-rose-700 border border-rose-200">Donation session missing. Reload and try again.</div>';
          return;
        }
        fetch('<?php echo rgcUrl('api/paypal_record.php'); ?>', {
          method: 'POST',
          headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
          body: JSON.stringify({donation_id: donationId, order_id: data.orderID})
        }).then(r=>r.json()).then(j=>{
          if (j && j.ok) {
            buttonsContainer.innerHTML = '<div class="p-3 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">Payment received. Thank you.</div>';
          } else {
            buttonsContainer.innerHTML = '<div class="p-3 rounded-lg bg-rose-50 text-rose-700 border border-rose-200">' + (j.error || 'Unable to verify payment') + '</div>';
          }
        }).catch(()=>{
          buttonsContainer.innerHTML = '<div class="p-3 rounded-lg bg-rose-50 text-rose-700 border border-rose-200">Network error while verifying payment.</div>';
        });
      });
    }
  }).render('#paypalButtons');
})();
</script>
<?php endif; ?>
<?php endif; ?>
<?php if (!empty($ok) && ($method ?? '') === 'mpesa'): ?>
<script>
(function(){
  const btn = document.getElementById('mpesaStkBtn');
  const phoneEl = document.getElementById('mpesaPhone');
  const statusEl = document.getElementById('mpesaStatus');
  const donationId = <?php echo json_encode((int) ($_SESSION['donation_last_id'] ?? 0)); ?>;
  const csrf = <?php echo json_encode(rgcCsrfToken('public_donate')); ?>;
  if (!btn) return;
  btn.addEventListener('click', function(e){
    e.preventDefault();
    if (!donationId) { statusEl.textContent = 'Donation session missing. Reload and try again.'; return; }
    const phone = (phoneEl.value || '').trim();
    if (!phone) { statusEl.textContent = 'Enter phone.'; return; }
    statusEl.textContent = 'Sending push...';
    fetch('<?php echo rgcUrl('api/mpesa_stk.php'); ?>', {
      method: 'POST',
      headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
      body: JSON.stringify({donation_id: donationId, phone})
    }).then(r=>r.json()).then(j=>{
      if (j.ok) statusEl.textContent = 'Check your phone to complete payment.';
      else statusEl.textContent = j.error || 'Failed to send STK push.';
    }).catch(()=>{ statusEl.textContent = 'Network error.'; });
  });
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
