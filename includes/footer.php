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

</body>
</html>
