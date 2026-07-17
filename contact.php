<?php
require __DIR__ . '/includes/app.php';
rgcEnforcePublicAccess();
$pageTitle = 'Prayer Request | Redeemed Gospel Church Eldoret';
$success = '';
$error = '';
$publicUser = rgcPublicUser();
$isPublicLoggedIn = $publicUser !== null;
$name = trim((string) ($publicUser['name'] ?? ''));
$email = trim((string) ($publicUser['email'] ?? ''));
$request = '';

// Load footer data for consistent contact info
$footerData = rgcLoadJson('footer.json', []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  rgcRequireCsrf('public_prayer');
  $name = trim((string) ($_POST['name'] ?? $name));
  $email = trim((string) ($_POST['email'] ?? $email));
  $request = trim((string) ($_POST['request'] ?? ''));
  
  if ($name !== '' && $request !== '') {
    $rows = rgcLoadJson('prayer_requests.json', []);
    $rows[] = [
      'id' => rgcNextId($rows),
      'name' => $name,
      'email' => $email,
      'request' => $request,
      'created_at' => date('Y-m-d H:i:s')
    ];
    rgcSaveJson('prayer_requests.json', $rows);
    $success = 'Your prayer request has been submitted successfully. Our prayer team will stand with you in faith.';
    $request = '';
  } else {
    $error = 'Please provide your name and prayer request.';
  }
}

require __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-pattern relative overflow-hidden bg-gradient-to-br from-brand-900 via-brand-800 to-brand-900 text-white">
  <div class="absolute inset-0 overflow-hidden">
    <div class="absolute top-0 right-0 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-96 h-96 bg-brand-600/10 rounded-full blur-3xl"></div>
  </div>
  
  <div class="max-w-4xl mx-auto px-4 py-16 relative z-10 text-center">
    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-brand-500/25 border border-brand-300/60 text-white text-sm font-semibold mb-6">
      <span class="w-2 h-2 rounded-full bg-brand-400 animate-pulse"></span>
      We're here to pray
    </div>
    
    <h1 class="font-display text-3xl md:text-5xl font-bold mb-4 text-white drop-shadow-[0_3px_12px_rgba(0,0,0,0.45)]">Prayer Requests</h1>
    <p class="text-white text-lg font-semibold max-w-2xl mx-auto bg-black/40 px-4 py-2 rounded-xl drop-shadow-[0_2px_10px_rgba(0,0,0,0.45)]">We believe in the power of prayer. Share your prayer needs with us and our team will stand with you in faith.</p>
  </div>
</section>

<!-- Contact Section -->
<section class="section-padding bg-slate-50">
  <div class="max-w-5xl mx-auto px-4">
    <div class="grid md:grid-cols-2 gap-8">
      <!-- Contact Info -->
      <div class="card p-8 animate-fade-in-up">
        <h2 class="font-display text-2xl font-bold mb-6">Get In Touch</h2>
        
        <div class="space-y-6">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-brand-100 flex items-center justify-center shrink-0">
              <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
            </div>
            <div>
              <h3 class="font-semibold text-slate-800">Location</h3>
              <p class="text-slate-600 mt-1"><?php echo htmlspecialchars($footerData['contact']['address'] ?? 'Eldoret, Kenya'); ?></p>
            </div>
          </div>
          
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-brand-100 flex items-center justify-center shrink-0">
              <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
              </svg>
            </div>
            <div>
              <h3 class="font-semibold text-slate-800">Phone</h3>
              <p class="text-slate-600 mt-1"><?php echo htmlspecialchars($footerData['contact']['phone'] ?? '+254 700 000 000'); ?></p>
            </div>
          </div>

          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center shrink-0">
              <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
              </svg>
            </div>
            <div>
              <h3 class="font-semibold text-slate-800">WhatsApp</h3>
              <a href="https://wa.me/254<?php echo preg_replace('/^0/', '', $footerData['contact']['whatsapp'] ?? '729222999'); ?>" target="_blank" class="text-green-600 hover:text-green-700 mt-1 inline-block">Chat on WhatsApp</a>
            </div>
          </div>
          
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-brand-100 flex items-center justify-center shrink-0">
              <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
            </div>
            <div>
              <h3 class="font-semibold text-slate-800">Email</h3>
              <p class="text-slate-600 mt-1"><?php echo htmlspecialchars($footerData['contact']['email'] ?? 'redeemedgospelchurch.eldoret@gmail.com'); ?></p>
            </div>
          </div>
        </div>
        
        <div class="mt-8 pt-6 border-t border-slate-100">
          <h3 class="font-semibold text-slate-800 mb-3">Service Times</h3>
          <ul class="space-y-2 text-slate-600">
            <?php foreach ($footerData['service_times'] ?? [] as $service): ?>
            <li class="flex items-center gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-brand-500"></span>
              <?php echo htmlspecialchars($service['day'] ?? ''); ?>: <?php echo htmlspecialchars($service['time'] ?? ''); ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <!-- Prayer Request Form -->
      <div class="card p-8 animate-fade-in-up delay-100">
        <h2 class="font-display text-2xl font-bold mb-2">Send Prayer Request</h2>
        <p class="text-slate-500 mb-6">We would love to pray for you.</p>
        
        <?php if ($success): ?>
        <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200">
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
            <div>
              <p class="font-medium text-green-800">Thank you!</p>
              <p class="text-sm text-green-700 mt-1"><?php echo htmlspecialchars($success); ?></p>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200">
          <p class="text-sm text-rose-700"><?php echo htmlspecialchars($error); ?></p>
        </div>
        <?php endif; ?>
        
        <form method="post" class="space-y-5">
          <?php echo rgcCsrfField('public_prayer'); ?>
          <div>
            <label for="name" class="form-label">Your Name *</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" class="form-input" placeholder="Enter your name" required>
          </div>
          
          <div>
            <label for="email" class="form-label">Email Address</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="form-input" placeholder="your@email.com (optional)">
          </div>
          
          <div>
            <label for="request" class="form-label">Prayer Request *</label>
            <textarea id="request" name="request" class="form-input form-textarea" placeholder="Share your prayer request with us..." required><?php echo htmlspecialchars($request); ?></textarea>
          </div>
          
          <button type="submit" class="btn btn-primary w-full py-3.5 text-base">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
            Submit Prayer Request
          </button>
        </form>
        <?php if ($success && !$isPublicLoggedIn): ?>
        <div class="mt-4 p-4 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-between gap-3">
          <p class="text-sm text-amber-900">Create a free account to track requests and stay connected.</p>
          <a href="/rgc/user/register.php" class="px-3 py-2 rounded-lg bg-slate-900 text-white text-sm whitespace-nowrap">Create Account</a>
        </div>
        <?php endif; ?>
        
        <p class="text-xs text-slate-400 mt-4 text-center">
          Your prayer request is kept confidential and will be prayed over by our team.
        </p>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
