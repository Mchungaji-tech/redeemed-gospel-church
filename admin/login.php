<?php
require __DIR__ . '/includes/auth.php';
if (isLoggedIn()) { header('Location: ' . rgcUrl('admin/dashboard.php')); exit; }

$error = '';
$info = '';
$showOtp = hasPendingOtp();
$otpExpiresIn = $showOtp ? rgcOtpExpiresInSeconds() : 0;
$otpResendIn = $showOtp ? rgcOtpResendCooldownRemainingSeconds() : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('admin_login');
  $step = $_POST['step'] ?? 'credentials';

  if ($step === 'verify') {
    $result = attemptVerifyOtp((string) ($_POST['verification_code'] ?? ''));
    if (($result['status'] ?? '') === 'ok') {
      header('Location: ' . rgcUrl('admin/dashboard.php'));
      exit;
    }
    $showOtp = true;
    $error = (string) ($result['message'] ?? 'Verification failed.');
  } elseif ($step === 'resend') {
    $result = attemptResendOtp();
    $showOtp = true;
    if (($result['status'] ?? '') === 'otp_required') {
      $info = (string) ($result['message'] ?? 'Verification code resent.');
    } else {
      $error = (string) ($result['message'] ?? 'Unable to resend verification code.');
    }
  } else {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = trim((string) ($_POST['password'] ?? ''));
    $result = attemptLoginStepOne($username, $password);
    $status = (string) ($result['status'] ?? '');
    if ($status === 'otp_required') {
      $showOtp = true;
      $info = (string) ($result['message'] ?? 'Verification code sent.');
    } else {
      $error = (string) ($result['message'] ?? 'Invalid login credentials.');
    }
  }
  $otpExpiresIn = $showOtp ? rgcOtpExpiresInSeconds() : 0;
  $otpResendIn = $showOtp ? rgcOtpResendCooldownRemainingSeconds() : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-900 flex items-center justify-center p-4">
  <div class="w-full max-w-4xl grid md:grid-cols-2 rounded-2xl overflow-hidden shadow-2xl border border-white/10">
    <div class="bg-slate-900/70 text-white p-8 md:p-10">
      <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Redeemed Gospel Church</p>
      <h1 class="text-3xl font-bold mt-2">Admin Control Center</h1>
      <p class="text-slate-300 mt-3">Secure workspace for sermons, events, content, and platform settings.</p>
      <div class="mt-6 space-y-2 text-sm text-slate-300">
        <p class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> Role-based access control</p>
        <p class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> Two-step verification code login</p>
        <p class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> Brute-force lockout + security logging</p>
      </div>
    </div>

    <div class="bg-white p-8 md:p-10">
      <h2 class="text-2xl font-bold text-slate-900"><?php echo $showOtp ? 'Verify Sign In' : 'Sign In'; ?></h2>
      <p class="text-sm text-slate-500 mt-1 mb-5"><?php echo $showOtp ? 'Enter your 6-digit verification code to complete login.' : 'Use your activated admin account to continue.'; ?></p>

      <?php if (isset($_GET['maintenance'])): ?>
      <div class="mb-3 p-3 rounded-lg text-sm bg-amber-50 text-amber-700 border border-amber-200">Maintenance mode is active for public visitors.</div>
      <?php endif; ?>
      <?php if (isset($_GET['expired'])): ?>
      <div class="mb-3 p-3 rounded-lg text-sm bg-amber-50 text-amber-700 border border-amber-200">You were logged out by system maintenance control.</div>
      <?php endif; ?>
      <?php if (isset($_GET['activated'])): ?>
      <div class="mb-3 p-3 rounded-lg text-sm bg-emerald-50 text-emerald-700 border border-emerald-200">Account activated. Continue with login.</div>
      <?php endif; ?>
      <?php if ($info): ?>
      <div class="mb-3 p-3 rounded-lg text-sm bg-indigo-50 text-indigo-700 border border-indigo-200"><?php echo htmlspecialchars($info); ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="mb-3 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if (!empty($_SESSION['debug_otp'])): ?>
      <div class="mb-3 p-3 rounded-lg text-sm bg-amber-50 text-amber-700 border border-amber-200">Debug OTP: <strong><?php echo htmlspecialchars((string) $_SESSION['debug_otp']); ?></strong></div>
      <?php endif; ?>

      <?php if ($showOtp): ?>
      <form method="post" class="space-y-4">
        <?php echo rgcCsrfField('admin_login'); ?>
        <input type="hidden" name="step" value="verify">
        <div>
          <label class="text-sm font-medium text-slate-700">Verification Code</label>
          <input name="verification_code" pattern="\d{6}" maxlength="6" minlength="6" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2 tracking-[0.3em] text-center text-lg" required>
        </div>
        <button class="w-full bg-slate-900 text-white py-2.5 rounded-lg hover:bg-slate-800 font-medium">Verify and Sign In</button>
      </form>
      <div class="mt-3 text-xs text-slate-600 space-y-1">
        <p>Code expires in <strong id="otp-expiry-seconds" data-seconds="<?php echo (int) $otpExpiresIn; ?>"><?php echo (int) $otpExpiresIn; ?></strong> seconds.</p>
        <p>You can resend after <strong id="otp-resend-seconds" data-seconds="<?php echo (int) $otpResendIn; ?>"><?php echo (int) $otpResendIn; ?></strong> seconds.</p>
      </div>
      <form method="post" class="mt-3">
        <?php echo rgcCsrfField('admin_login'); ?>
        <input type="hidden" name="step" value="resend">
        <button id="resend-btn" class="w-full bg-white border border-slate-300 text-slate-700 py-2.5 rounded-lg hover:bg-slate-50 font-medium disabled:opacity-50 disabled:cursor-not-allowed" <?php echo $otpResendIn > 0 ? 'disabled' : ''; ?>>Resend Verification Code</button>
      </form>
      <?php else: ?>
      <form method="post" class="space-y-4">
        <?php echo rgcCsrfField('admin_login'); ?>
        <input type="hidden" name="step" value="credentials">
        <div>
          <label class="text-sm font-medium text-slate-700">Username</label>
          <input name="username" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" required>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Password</label>
          <input name="password" type="password" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2" required>
        </div>
        <button class="w-full bg-slate-900 text-white py-2.5 rounded-lg hover:bg-slate-800 font-medium">Continue</button>
      </form>
      <?php endif; ?>

      <div class="mt-6 text-xs text-slate-500 border-t pt-4 space-y-1">
        <p>Need an account? <a href="<?php echo rgcUrl('admin/register.php'); ?>" class="text-indigo-700 hover:underline">Register here</a>.</p>
        <p>Account must be activated before login.</p>
      </div>
    </div>
  </div>
  <script>
  (function() {
    const expiryEl = document.getElementById('otp-expiry-seconds');
    const resendEl = document.getElementById('otp-resend-seconds');
    const resendBtn = document.getElementById('resend-btn');
    if (!expiryEl || !resendEl || !resendBtn) return;

    let expiry = parseInt(expiryEl.dataset.seconds || '0', 10);
    let resend = parseInt(resendEl.dataset.seconds || '0', 10);

    function tick() {
      if (expiry > 0) expiry -= 1;
      if (resend > 0) resend -= 1;

      expiryEl.textContent = String(expiry);
      resendEl.textContent = String(resend);

      if (resend <= 0) {
        resendBtn.removeAttribute('disabled');
      } else {
        resendBtn.setAttribute('disabled', 'disabled');
      }
    }

    setInterval(tick, 1000);
  })();
  </script>
</body>
</html>
