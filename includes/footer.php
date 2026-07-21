<?php
// Load footer data
$footerData = rgcLoadJson('footer.json', [
    'church_name' => 'RGC Eldoret',
    'tagline' => "A sanctuary built for Christ's glory and the salvation of souls. Join us in worship and fellowship.",
    'service_times' => [
        ['day' => 'Sunday Service', 'time' => '9:00 AM'],
        ['day' => 'Wednesday Bible Study', 'time' => '6:00 PM'],
        ['day' => 'Friday Prayer', 'time' => '7:00 PM']
    ],
    'quick_links' => [
        ['text' => 'About Us', 'url' => 'about.php'],
        ['text' => 'Ministries', 'url' => 'ministries.php'],
        ['text' => 'Events', 'url' => 'events.php'],
        ['text' => 'Sermons', 'url' => 'sermons.php'],
        ['text' => 'Prayer Request', 'url' => 'contact.php']
    ],
    'contact' => [
        'address' => 'Eldoret, Kenya',
        'email' => 'redeemedgospelchurch.eldoret@gmail.com',
        'phone' => '0722551152',
        'whatsapp' => '0722551152'
    ],
    'designer_credit' => 'Designed by TekTrend'
]);
?>

</main>

<footer class="footer text-white mt-20">
  <div class="max-w-[1280px] mx-auto px-4 py-14 md:py-16">
    <div class="grid gap-8 lg:grid-cols-[1.25fr_0.75fr] lg:items-start">
      <section class="footer-showcase rounded-[2rem] border border-white/10 p-7 md:p-10 shadow-2xl">
        <div class="flex items-center gap-3 mb-5">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-300 flex items-center justify-center shadow-lg shadow-brand-900/20">
            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
          </div>
          <div>
            <p class="text-xs uppercase tracking-[0.28em] text-brand-200/80">Redeemed Gospel Church</p>
            <h2 class="font-display text-2xl font-semibold"><?php echo htmlspecialchars($footerData['church_name'] ?? 'RGC Eldoret'); ?></h2>
          </div>
        </div>

        <p class="max-w-2xl text-white/72 text-base leading-8">
          <?php echo htmlspecialchars($footerData['tagline'] ?? ''); ?>
        </p>

        <div class="grid gap-4 md:grid-cols-3 mt-8">
          <div class="footer-showcase__card rounded-2xl p-5">
            <p class="text-xs uppercase tracking-[0.24em] text-brand-200/75 mb-2">Visit</p>
            <p class="text-sm text-white/80"><?php echo htmlspecialchars($footerData['contact']['address'] ?? 'Eldoret, Kenya'); ?></p>
          </div>
          <div class="footer-showcase__card rounded-2xl p-5">
            <p class="text-xs uppercase tracking-[0.24em] text-brand-200/75 mb-2">Service</p>
            <p class="text-sm text-white/80"><?php echo htmlspecialchars(($footerData['service_times'][0]['day'] ?? 'Sunday Service') . ': ' . ($footerData['service_times'][0]['time'] ?? '9:00 AM')); ?></p>
          </div>
          <div class="footer-showcase__card rounded-2xl p-5">
            <p class="text-xs uppercase tracking-[0.24em] text-brand-200/75 mb-2">Next Step</p>
            <a href="<?php echo rgcUrl('contact.php'); ?>" class="inline-flex items-center gap-2 text-sm text-white hover:text-brand-200 transition-colors">Plan a visit or send prayer</a>
          </div>
        </div>
      </section>

      <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-1">
        <section class="footer-panel rounded-[1.75rem] border border-white/10 p-6">
          <h3 class="font-display font-semibold text-xl mb-4">Quick Links</h3>
          <ul class="space-y-3 text-sm text-white/74">
            <?php foreach ($footerData['quick_links'] ?? [] as $link): ?>
            <li><a href="<?php echo rgcUrl($link['url'] ?? '#'); ?>" class="footer-link inline-flex items-center gap-2"><?php echo htmlspecialchars($link['text'] ?? ''); ?></a></li>
            <?php endforeach; ?>
          </ul>
        </section>

        <section class="footer-panel rounded-[1.75rem] border border-white/10 p-6">
          <h3 class="font-display font-semibold text-xl mb-4">Contact</h3>
          <ul class="space-y-3 text-sm text-white/74">
            <?php $footerEmail = trim((string) ($footerData['contact']['email'] ?? '')); ?>
            <?php $footerPhone = trim((string) ($footerData['contact']['phone'] ?? '')); ?>
            <li><?php echo htmlspecialchars($footerData['contact']['address'] ?? ''); ?></li>
            <?php if ($footerEmail !== ''): ?>
            <li><a href="mailto:<?php echo htmlspecialchars($footerEmail); ?>" class="footer-link"><?php echo htmlspecialchars($footerEmail); ?></a></li>
            <?php endif; ?>
            <?php if ($footerPhone !== ''): ?>
            <li><a href="tel:<?php echo htmlspecialchars(preg_replace('/[^\d+]/', '', $footerPhone) ?? $footerPhone); ?>" class="footer-link"><?php echo htmlspecialchars($footerPhone); ?></a></li>
            <?php endif; ?>
            <li><a href="https://wa.me/254<?php echo preg_replace('/^0/', '', $footerData['contact']['whatsapp'] ?? '729222999'); ?>" target="_blank" class="footer-link">WhatsApp</a></li>
          </ul>
        </section>
      </div>
    </div>

    <div class="divider-gold my-8"></div>

    <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-white/60">
      <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($footerData['church_name'] ?? 'Redeemed Gospel Church Eldoret'); ?>. All rights reserved.</p>
      <p class="text-xs uppercase tracking-[0.22em]"><?php echo htmlspecialchars($footerData['designer_credit'] ?? ''); ?></p>
    </div>
  </div>
</footer>

<script>
(function () {
  const body = document.body;
  if (!body) return;

  const page = body.dataset.page || '';
  const isTouch = window.matchMedia && window.matchMedia('(hover: none), (pointer: coarse)').matches;
  const allowReverseMotion = !isTouch;
  if (isTouch) {
    body.classList.add('touch-device');
  }

  const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const parallaxLayers = Array.from(document.querySelectorAll('.parallax-layer[data-parallax-speed]'));
  if (!prefersReducedMotion && parallaxLayers.length) {
    const parallaxIntensity = isTouch ? 0.42 : 1;
    let parallaxTicking = false;

    function clamp(value, min, max) {
      return Math.min(Math.max(value, min), max);
    }

    function paintParallax() {
      const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 1;
      parallaxLayers.forEach((layer) => {
        const anchor = layer.closest('.parallax-scene') || layer;
        const rect = anchor.getBoundingClientRect();
        const center = rect.top + rect.height / 2;
        const delta = (center - viewportHeight / 2) / viewportHeight;
        const speed = Number(layer.dataset.parallaxSpeed || 0);
        const y = clamp(delta * viewportHeight * speed * parallaxIntensity, -72, 72);
        layer.style.setProperty('--parallax-y', `${y.toFixed(2)}px`);
      });
      parallaxTicking = false;
    }

    function queueParallax() {
      if (parallaxTicking) return;
      parallaxTicking = true;
      window.requestAnimationFrame(paintParallax);
    }

    window.addEventListener('scroll', queueParallax, { passive: true });
    window.addEventListener('resize', queueParallax);
    queueParallax();
  }

  if (!isTouch) {
    const galleryTiltCards = Array.from(document.querySelectorAll('.gallery-river__card'));
    galleryTiltCards.forEach((card) => {
      card.addEventListener('mousemove', (event) => {
        const rect = card.getBoundingClientRect();
        const x = event.clientX - rect.left - rect.width / 2;
        const y = event.clientY - rect.top - rect.height / 2;
        const rotateX = (y / rect.height) * 12;
        const rotateY = (x / rect.width) * -12;
        card.style.transform = `translateZ(0) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg)`;
      });

      card.addEventListener('mouseleave', () => {
        card.style.transform = '';
      });
    });
  }

  const skipMotionPages = new Set(['index.php', 'blog.php', 'blog-post.php']);
  if (skipMotionPages.has(page)) {
    return;
  }

  const main = document.querySelector('main');
  if (!main) return;

  function setRevealDelay(node, value) {
    if (node.style.getPropertyValue('--reveal-delay') !== '') return;
    node.style.setProperty('--reveal-delay', value);
  }

  function markUnique(nodes, classes, baseDelay, step) {
    nodes.forEach((node, index) => {
      if (!(node instanceof HTMLElement) || node.classList.contains('scroll-reveal')) return;
      node.classList.add('scroll-reveal', ...classes.filter((name) => name !== 'scroll-reveal'));
      setRevealDelay(node, `${Math.min(baseDelay + index * step, 0.36).toFixed(2)}s`);
    });
  }

  const sections = Array.from(main.querySelectorAll(':scope > section'));
  markUnique(sections, ['scroll-reveal', 'scroll-reveal--section'], 0.04, 0.04);

  const cardNodes = Array.from(main.querySelectorAll('.card, .pulse-card, .event-card, .testimonial-card, .ministry-section, .footer-showcase__card, .footer-panel, .next-step-card, .vision-media-card'));
  markUnique(cardNodes, ['scroll-reveal', 'scroll-reveal--text'], 0.08, 0.03);

  const iconNodes = Array.from(main.querySelectorAll('.w-10.h-10, .w-12.h-12, .w-14.h-14, .w-16.h-16, .w-20.h-20, .w-24.h-24'))
    .filter((node) => node.querySelector('svg'));
  markUnique(iconNodes, ['scroll-reveal', 'scroll-reveal--icon'], 0.06, 0.025);

  const headingNodes = Array.from(main.querySelectorAll('section h1, section h2, section h3'));
  headingNodes.forEach((heading, index) => {
    if (!(heading instanceof HTMLElement)) return;
    if (heading.closest('.scroll-reveal--word')) return;
    if (heading.children.length > 0) {
      if (!heading.classList.contains('scroll-reveal')) {
        heading.classList.add('scroll-reveal', 'scroll-reveal--text');
        setRevealDelay(heading, `${Math.min(0.08 + index * 0.02, 0.28).toFixed(2)}s`);
      }
      return;
    }

    const text = heading.textContent.trim();
    const words = text === '' ? [] : text.split(/\s+/);
    if (isTouch || words.length === 0 || words.length > 12) {
      if (!heading.classList.contains('scroll-reveal')) {
        heading.classList.add('scroll-reveal', 'scroll-reveal--text');
        setRevealDelay(heading, `${Math.min(0.08 + index * 0.02, 0.28).toFixed(2)}s`);
      }
      return;
    }

    heading.dataset.motionSplit = 'true';
    heading.classList.add('scroll-reveal', 'scroll-reveal--word');
    setRevealDelay(heading, `${Math.min(0.08 + index * 0.02, 0.28).toFixed(2)}s`);
    heading.textContent = '';

    words.forEach((word, wordIndex) => {
      const outer = document.createElement('span');
      outer.className = 'word-reveal__mask';
      const inner = document.createElement('span');
      inner.className = 'word-reveal__word';
      inner.style.setProperty('--word-delay', `${Math.min(wordIndex * 0.05, 0.4).toFixed(2)}s`);
      inner.textContent = word;
      outer.appendChild(inner);
      heading.appendChild(outer);
      if (wordIndex < words.length - 1) {
        heading.appendChild(document.createTextNode(' '));
      }
    });
  });

  const textBlocks = Array.from(main.querySelectorAll('section p, section li, section .btn'))
    .filter((node) => node instanceof HTMLElement && !node.classList.contains('scroll-reveal') && !node.closest('.scroll-reveal--word'));
  markUnique(textBlocks.slice(0, 60), ['scroll-reveal', 'scroll-reveal--text'], 0.1, 0.015);

  const revealItems = Array.from(document.querySelectorAll('.scroll-reveal'));
  if (!revealItems.length) return;

  revealItems.forEach((item) => item.classList.add('reveal-ready'));

  if (!('IntersectionObserver' in window)) {
    revealItems.forEach((item) => item.classList.add('is-visible'));
    return;
  }

  let lastScrollY = window.pageYOffset || window.scrollY || 0;
  let scrollDirection = 'down';

  window.addEventListener('scroll', () => {
    const currentY = window.pageYOffset || window.scrollY || 0;
    scrollDirection = currentY > lastScrollY ? 'down' : 'up';
    lastScrollY = currentY;
  }, { passive: true });

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      entry.target.classList.toggle('scroll-reveal--reverse', allowReverseMotion && scrollDirection === 'up');
      entry.target.classList.toggle('is-visible', entry.isIntersecting);
    });
  }, {
    threshold: 0.12,
    rootMargin: '0px 0px -40px 0px'
  });

  revealItems.forEach((item) => observer.observe(item));
})();
</script>

</body>
</html>
