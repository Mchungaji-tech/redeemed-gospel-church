<?php
require __DIR__ . '/includes/app.php';
rgcEnforcePublicAccess();
$pageTitle = 'About Us | Redeemed Gospel Church Eldoret';
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
      Since 1996
    </span>
    <h1 class="font-display text-3xl md:text-5xl font-bold mb-4 text-white drop-shadow-[0_3px_12px_rgba(0,0,0,0.45)]">About Redeemed Gospel Church</h1>
    <p class="text-white text-lg font-semibold max-w-2xl mx-auto bg-black/40 px-4 py-2 rounded-xl drop-shadow-[0_2px_10px_rgba(0,0,0,0.45)]">A beacon of spiritual influence in Eldoret, Kenya - growing through evangelistic crusades and the power of the Gospel.</p>
  </div>
</section>

<!-- Main Content -->
<section class="section-padding bg-white">
  <div class="max-w-4xl mx-auto px-4">
    <div class="space-y-8">
      <!-- Our History -->
      <article class="card p-8 animate-fade-in-up">
        <div class="flex items-start gap-4">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-500 flex items-center justify-center shrink-0 shadow-lg">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div>
            <h2 class="font-display text-2xl font-bold mb-3 text-slate-900">Our History</h2>
            <p class="text-slate-800 leading-relaxed">
              Founded in January 1995 by Pastor Morris Omondi and Grace Olweny, Redeemed Gospel Church Eldoret grew through evangelistic crusades, becoming a beacon of spiritual influence in the region. What started as a small gathering has grown into a thriving ministry impacting lives throughout Eldoret and beyond.
            </p>
          </div>
        </div>
      </article>

      <!-- Our Ministry -->
      <article class="card p-8 animate-fade-in-up delay-100">
        <div class="flex items-start gap-4">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-500 flex items-center justify-center shrink-0 shadow-lg">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
            </svg>
          </div>
          <div>
            <h2 class="font-display text-2xl font-bold mb-3 text-slate-900">Our Ministry</h2>
            <p class="text-slate-800 leading-relaxed">
              We have planted churches across various locations and authored books on spiritual growth. Our upcoming book, "Kingdom Living," aims to inspire and equip believers for greater things in their Christian walk. Through various programs and outreach initiatives, we continue to spread the message of hope and redemption.
            </p>
          </div>
        </div>
      </article>

      <!-- Leadership Journey -->
      <article class="card p-8 animate-fade-in-up delay-200">
        <div class="flex items-start gap-4">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-500 flex items-center justify-center shrink-0 shadow-lg">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
            </svg>
          </div>
          <div>
            <h2 class="font-display text-2xl font-bold mb-3 text-slate-900">Leadership Journey</h2>
            <p class="text-slate-800 leading-relaxed">
              Pastor Morris Omondi was ordained as a pastor in 2000, an overseer in 2002, and a bishop in 2012. Since then, he has been overseeing churches throughout the Western Region, providing spiritual leadership and guidance to multiple congregations.
            </p>
          </div>
        </div>
      </article>

      <!-- Our Family -->
      <article class="card p-8 animate-fade-in-up delay-300">
        <div class="flex items-start gap-4">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-500 flex items-center justify-center shrink-0 shadow-lg">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
          </div>
          <div>
            <h2 class="font-display text-2xl font-bold mb-3 text-slate-900">Our Family</h2>
            <p class="text-slate-800 leading-relaxed">
              Bishop Morris and Rev. Grace Omondi have been blessed with three children: Joan, Eunice, and Pastor Timothy Omondi, all of whom are actively involved in ministry. This family commitment to serving God has been an inspiration to many in our congregation and beyond.
            </p>
          </div>
        </div>
      </article>
    </div>
  </div>
</section>

<!-- Stats Section -->
<section class="section-padding bg-brand-900 text-white">
  <div class="max-w-4xl mx-auto px-4">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
      <div class="animate-fade-in-up">
        <div class="font-display text-4xl md:text-5xl font-bold text-gradient">25+</div>
        <div class="text-white text-sm mt-2 font-medium">Years of Ministry</div>
      </div>
      <div class="animate-fade-in-up delay-100">
        <div class="font-display text-4xl md:text-5xl font-bold text-gradient">3</div>
        <div class="text-white text-sm mt-2 font-medium">Church Plants</div>
      </div>
      <div class="animate-fade-in-up delay-200">
        <div class="font-display text-4xl md:text-5xl font-bold text-gradient">2</div>
        <div class="text-white text-sm mt-2 font-medium">Published Books</div>
      </div>
      <div class="animate-fade-in-up delay-300">
        <div class="font-display text-4xl md:text-5xl font-bold text-gradient">1</div>
        <div class="text-white text-sm mt-2 font-medium">Region Oversight</div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="section-padding bg-slate-50">
  <div class="max-w-2xl mx-auto px-4 text-center">
    <h2 class="font-display text-2xl md:text-3xl font-bold text-slate-800 mb-4">Join Our Community</h2>
    <p class="text-slate-800 mb-6">We welcome you to be part of our growing family in Christ.</p>
    <div class="flex flex-wrap justify-center gap-4">
      <a href="/rgc/contact.php" class="btn btn-primary">Send Prayer Request</a>
      <a href="/rgc/sermons.php" class="btn btn-outline">Watch Sermons</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
