<?php
require __DIR__ . '/includes/app.php';
rgcEnforcePublicAccess();
$pageTitle = 'Missions | Redeemed Gospel Church Eldoret';
require __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-pattern relative overflow-hidden bg-gradient-to-br from-brand-900 via-brand-800 to-brand-900 text-white">
  <div class="absolute inset-0 overflow-hidden">
    <div class="absolute top-0 right-0 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-96 h-96 bg-brand-600/10 rounded-full blur-3xl"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-brand-500/5 rounded-full blur-3xl"></div>
  </div>
  
  <div class="max-w-4xl mx-auto px-4 py-16 relative z-10 text-center">
    <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-brand-500/25 border border-brand-300/60 text-white text-sm font-semibold mb-6">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      Global Mission
    </span>
    
    <h1 class="font-display text-3xl md:text-5xl font-bold mb-4 text-white drop-shadow-[0_3px_12px_rgba(0,0,0,0.45)]">Missions & Outreach</h1>
    <p class="text-white text-lg font-semibold max-w-2xl mx-auto bg-black/40 px-4 py-2 rounded-xl drop-shadow-[0_2px_10px_rgba(0,0,0,0.45)]">Taking the Gospel to the ends of the earth - locally, regionally, and globally.</p>
  </div>
</section>

<!-- Mission Intro -->
<section class="section-padding bg-white">
  <div class="max-w-3xl mx-auto px-4 text-center">
    <p class="text-lg text-slate-600 leading-relaxed">
      Bishop Omondi has shared the Gospel across Africa, Europe, and North America. Our mission is to raise disciples and plant churches that impact communities for Christ. We believe in the Great Commission and are committed to making disciples of all nations.
    </p>
  </div>
</section>

<!-- Mission Areas -->
<section class="section-padding bg-slate-50">
  <div class="max-w-5xl mx-auto px-4">
    <div class="grid md:grid-cols-3 gap-6">
      <!-- Local Outreach -->
      <div class="card p-8 text-center animate-fade-in-up">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-500 flex items-center justify-center mx-auto mb-5 shadow-lg">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
        </div>
        <h3 class="font-display text-xl font-bold text-slate-800 mb-3">Local Outreach</h3>
        <p class="text-slate-600">Evangelistic crusades, discipleship groups, and prayer gatherings reaching our immediate community in Eldoret.</p>
      </div>

      <!-- Regional Support -->
      <div class="card p-8 text-center animate-fade-in-up delay-100">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-500 flex items-center justify-center mx-auto mb-5 shadow-lg">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
        </div>
        <h3 class="font-display text-xl font-bold text-slate-800 mb-3">Regional Church Support</h3>
        <p class="text-slate-600">Leadership training and church planting initiatives throughout the Western Region of Kenya.</p>
      </div>

      <!-- Global Ministry -->
      <div class="card p-8 text-center animate-fade-in-up delay-200">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-500 flex items-center justify-center mx-auto mb-5 shadow-lg">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <h3 class="font-display text-xl font-bold text-slate-800 mb-3">Global Ministry</h3>
        <p class="text-slate-600">Preaching, encouragement, and resource sharing internationally across Africa, Europe, and North America.</p>
      </div>
    </div>
  </div>
</section>

<!-- Impact Stats -->
<section class="section-padding bg-brand-900 text-white">
  <div class="max-w-4xl mx-auto px-4">
    <div class="text-center mb-10">
      <h2 class="section-heading inline-block text-2xl md:text-3xl font-display font-bold after:bg-brand-400">Our Impact</h2>
    </div>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
      <div class="animate-fade-in-up">
        <div class="font-display text-3xl md:text-4xl font-bold text-gradient">3+</div>
        <div class="text-white/60 text-sm mt-2">Churches Planted</div>
      </div>
      <div class="animate-fade-in-up delay-100">
        <div class="font-display text-3xl md:text-4xl font-bold text-gradient">10+</div>
        <div class="text-white/60 text-sm mt-2">Countries Reached</div>
      </div>
      <div class="animate-fade-in-up delay-200">
        <div class="font-display text-3xl md:text-4xl font-bold text-gradient">1000+</div>
        <div class="text-white/60 text-sm mt-2">Lives Impacted</div>
      </div>
      <div class="animate-fade-in-up delay-300">
        <div class="font-display text-3xl md:text-4xl font-bold text-gradient">2</div>
        <div class="text-white/60 text-sm mt-2">Books Published</div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="section-padding bg-slate-50">
  <div class="max-w-2xl mx-auto px-4 text-center">
    <h2 class="font-display text-2xl md:text-3xl font-bold text-slate-800 mb-4">Partner With Us</h2>
    <p class="text-slate-600 mb-6">Support our mission to spread the Gospel locally and globally.</p>
    <div class="flex flex-wrap justify-center gap-4">
      <a href="/rgc/contact.php" class="btn btn-primary">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        </svg>
        Prayer Support
      </a>
      <a href="/rgc/sermons.php" class="btn btn-outline">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
        </svg>
        Watch Teachings
      </a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
