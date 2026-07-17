﻿<?php
require __DIR__ . '/includes/app.php';
rgcEnforcePublicAccess();
$pageTitle = 'Ministries | Redeemed Gospel Church Eldoret';
$ministries = rgcLoadJson('ministries.json', []);
$projects = rgcLoadJson('projects.json', []);
$testimonials = rgcLoadJson('testimonials.json', []);
require __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-pattern relative overflow-hidden bg-gradient-to-br from-brand-900 via-brand-800 to-brand-900 text-white">
  <div class="absolute inset-0 overflow-hidden">
    <div class="absolute top-20 right-20 w-64 h-64 bg-brand-500/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-20 left-20 w-64 h-64 bg-brand-600/10 rounded-full blur-3xl"></div>
  </div>
  
  <div class="max-w-4xl mx-auto px-4 py-16 relative z-10 text-center">
    <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-brand-500/25 border border-brand-300/60 text-white text-sm font-semibold mb-6">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
      </svg>
      Serving
    </span>
    
    <h1 class="font-display text-3xl md:text-5xl font-bold mb-4 text-white drop-shadow-[0_3px_12px_rgba(0,0,0,0.45)]">Our Ministries</h1>
    <p class="text-white text-lg font-semibold max-w-2xl mx-auto bg-black/40 px-4 py-2 rounded-xl drop-shadow-[0_2px_10px_rgba(0,0,0,0.45)]">Equipping believers and reaching the lost through various outreach programs.</p>
  </div>
</section>

<!-- Ministries -->
<section class="section-padding bg-white">
  <div class="max-w-6xl mx-auto px-4">
    <div class="text-center max-w-3xl mx-auto mb-20">
      <span class="story-card__eyebrow">Our Calling</span>
      <h2 class="text-4xl md:text-6xl font-display font-bold mt-6 leading-tight">Spaces to Grow.</h2>
      <p class="text-xl text-slate-500 mt-6">Discover where you can connect, serve, and grow in your faith journey.</p>
    </div>

    <div class="space-y-16">
      <?php foreach ($ministries as $index => $m): ?>
      <article class="ministry-section animate-fade-in-up" style="animation-delay: <?php echo $index * 100; ?>ms">
        <div class="grid md:grid-cols-[1fr_2fr] gap-8 items-center">
          <div class="text-center md:text-left">
            <div class="w-24 h-24 rounded-3xl bg-brand-100 flex items-center justify-center mx-auto md:mx-0 mb-6 text-brand-600">
              <?php echo getMinistryIconSvg($m['icon'] ?? 'default'); ?>
            </div>
            <h3 class="font-display text-3xl font-bold text-slate-900"><?php echo htmlspecialchars($m['name']); ?></h3>
          </div>
          <div>
            <p class="text-lg text-slate-600 leading-relaxed"><?php echo htmlspecialchars($m['description']); ?></p>
            <div class="mt-8">
              <a href="<?php echo rgcUrl('contact.php'); ?>" class="btn btn-primary">Join This Ministry</a>
            </div>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Projects -->
<section class="section-padding bg-slate-50">
  <div class="max-w-5xl mx-auto px-4">
    <div class="text-center mb-10">
      <h2 class="section-heading inline-block text-2xl md:text-3xl font-display font-bold">Outreach Projects</h2>
      <p class="text-slate-500 mt-3">Making a difference in our community</p>
    </div>
    
    <div class="grid md:grid-cols-3 gap-5">
      <?php foreach ($projects as $index => $p): ?>
      <div class="card p-6 animate-fade-in-up" style="animation-delay: <?php echo $index * 100; ?>ms">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-brand-400 to-brand-500 flex items-center justify-center mb-4 shadow-md">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
          </svg>
        </div>
        <h3 class="font-semibold text-lg text-slate-800 mb-2"><?php echo htmlspecialchars($p['title']); ?></h3>
        <p class="text-slate-600 text-sm leading-relaxed"><?php echo htmlspecialchars($p['description']); ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Testimonies -->
<section class="section-padding bg-white">
  <div class="max-w-4xl mx-auto px-4">
    <div class="text-center mb-10">
      <h2 class="section-heading inline-block text-2xl md:text-3xl font-display font-bold">Testimonies</h2>
      <p class="text-slate-500 mt-3">What God is doing in lives</p>
    </div>
    
    <div class="grid md:grid-cols-2 gap-6">
      <?php foreach ($testimonials as $index => $t): ?>
      <blockquote class="testimonial-card animate-fade-in-up" style="animation-delay: <?php echo $index * 100; ?>ms">
        <p class="text-slate-700 relative z-10 leading-relaxed">"<?php echo htmlspecialchars($t['message']); ?>"</p>
        <cite class="block mt-5 font-semibold text-brand-800 not-italic">
          — <?php echo htmlspecialchars($t['name']); ?>
        </cite>
      </blockquote>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="section-padding bg-brand-900 text-white">
  <div class="max-w-2xl mx-auto px-4 text-center">
    <h2 class="font-display text-2xl md:text-3xl font-bold mb-4">Get Involved</h2>
    <p class="text-white/70 mb-6">Join any of our ministries and be part of what God is doing in Eldoret.</p>
    <a href="/rgc/contact.php" class="btn btn-primary">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
      </svg>
      Contact Us
    </a>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
