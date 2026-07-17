<?php
require __DIR__ . '/includes/app.php';
rgcEnforcePublicAccess();
$pageTitle = 'Events | Redeemed Gospel Church Eldoret';
$events = rgcLoadJson('events.json', []);
require __DIR__ . '/includes/header.php';
?>

<section class="hero-pattern relative overflow-hidden bg-gradient-to-br from-brand-900 via-brand-800 to-brand-900 text-white">
  <div class="absolute inset-0 overflow-hidden">
    <div class="absolute top-20 right-20 w-64 h-64 bg-brand-500/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-20 left-20 w-64 h-64 bg-brand-600/10 rounded-full blur-3xl"></div>
  </div>

  <div class="max-w-5xl mx-auto px-4 py-16 relative z-10 text-center">
    <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-brand-500/25 border border-brand-300/60 text-white text-sm font-semibold mb-6">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      Events Calendar
    </span>
    <h1 class="font-display text-3xl md:text-5xl font-bold mb-4 text-white drop-shadow-[0_3px_12px_rgba(0,0,0,0.45)]">Posters, Dates, and Live Countdown</h1>
    <p class="text-white text-lg font-semibold max-w-2xl mx-auto bg-black/40 px-4 py-2 rounded-xl drop-shadow-[0_2px_10px_rgba(0,0,0,0.45)]">Track every church event with exact date/time and live countdown.</p>
  </div>
</section>

<section class="section-padding bg-slate-50">
  <div class="max-w-6xl mx-auto px-4">
    <div class="space-y-6">
      <?php foreach ($events as $index => $e): ?>
      <?php $timestamp = strtotime((string) ($e['event_at'] ?? '')); ?>
      <article class="event-card card p-4 md:p-6 animate-fade-in-up" style="animation-delay: <?php echo $index * 90; ?>ms">
        <div class="grid lg:grid-cols-[220px_1fr_auto] gap-5 items-start">
          <div class="rounded-xl overflow-hidden border bg-slate-100">
            <img
              src="<?php echo htmlspecialchars(!empty($e['poster']) ? $e['poster'] : 'https://images.unsplash.com/photo-1515162305285-0293e4767cc2?q=80&w=800&auto=format&fit=crop'); ?>"
              alt="<?php echo htmlspecialchars($e['title'] ?? 'Event poster'); ?>"
              class="w-full h-44 object-cover"
            >
          </div>

          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-50 border border-brand-200 text-brand-700 text-xs font-semibold uppercase">
              <?php echo $timestamp ? htmlspecialchars(date('l, F j, Y', $timestamp)) : 'Date Pending'; ?>
            </div>
            <h2 class="font-display text-2xl font-bold text-slate-800 mt-3"><?php echo htmlspecialchars($e['title'] ?? 'Untitled Event'); ?></h2>
            <p class="text-slate-500 mt-2 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              </svg>
              <?php echo htmlspecialchars($e['location'] ?? 'Location TBA'); ?>
            </p>
            <p class="text-slate-500 mt-1">
              <?php echo $timestamp ? htmlspecialchars(date('g:i A', $timestamp)) : 'Time TBA'; ?>
            </p>
            <?php if (!empty($e['description'])): ?>
            <p class="text-slate-700 mt-3"><?php echo htmlspecialchars($e['description']); ?></p>
            <?php endif; ?>
          </div>

          <div class="w-full lg:w-[230px] px-4 py-3 rounded-xl bg-brand-50 border border-brand-200">
            <p class="text-xs text-brand-700 uppercase tracking-wider font-semibold">Actual Countdown</p>
            <p class="countdown-display mt-2 text-lg font-bold text-brand-900" data-event-at="<?php echo htmlspecialchars((string) ($e['event_at'] ?? '')); ?>">Loading...</p>
          </div>
        </div>
      </article>
      <?php endforeach; ?>

      <?php if (empty($events)): ?>
      <div class="text-center py-12">
        <div class="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
          <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
        <p class="text-slate-500">No upcoming events at this time.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
function formatCountdown(diff) {
  if (diff <= 0) return 'Event Started';
  const d = Math.floor(diff / 86400);
  const h = Math.floor((diff % 86400) / 3600);
  const m = Math.floor((diff % 3600) / 60);
  const s = diff % 60;
  return `${d}d ${String(h).padStart(2, '0')}h ${String(m).padStart(2, '0')}m ${String(s).padStart(2, '0')}s`;
}

function tickCountdowns() {
  const now = Date.now();
  document.querySelectorAll('.countdown-display[data-event-at]').forEach((el) => {
    const raw = el.dataset.eventAt || '';
    if (!raw) {
      el.textContent = 'Date Pending';
      return;
    }
    const target = new Date(raw.replace(' ', 'T')).getTime();
    if (Number.isNaN(target)) {
      el.textContent = 'Invalid Date';
      return;
    }
    const diff = Math.floor((target - now) / 1000);
    el.textContent = formatCountdown(diff);
  });
}

setInterval(tickCountdowns, 1000);
tickCountdowns();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
