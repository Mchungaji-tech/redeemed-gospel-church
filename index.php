<?php
require __DIR__ . '/includes/app.php';
rgcEnforcePublicAccess();
$pageTitle = 'Home | Redeemed Gospel Church Eldoret';

// YouTube embed helper
function getHomeYoutubeEmbedUrl($url) {
    if (empty($url)) return '';
    $videoId = '';
    if (preg_match('/youtube\.com\/watch\?.*[?&]v=([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        $videoId = $matches[1];
    } elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        $videoId = $matches[1];
    } elseif (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        $videoId = $matches[1];
    }
    if ($videoId) {
        return "https://www.youtube.com/embed/" . $videoId . "?rel=0&modestbranding=1";
    }
    return '';
}

function getHomeYoutubeThumbnail(?string $url): string {
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    $videoId = '';
    if (preg_match('/youtube\.com\/watch\?.*[?&]v=([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        $videoId = $matches[1];
    } elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        $videoId = $matches[1];
    } elseif (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        $videoId = $matches[1];
    }
    return $videoId !== '' ? 'https://img.youtube.com/vi/' . $videoId . '/hqdefault.jpg' : '';
}

// Fetch sermons with priority: DB > JSON placeholders
$sermons = [];
if (rgcDbAvailable()) {
    try {
        $stmt = rgcDb()->query('SELECT id, title, speaker, youtube_url, facebook_embed, scheduled_at, is_live, featured, created_at FROM sermons ORDER BY COALESCE(scheduled_at, created_at) DESC');
        $sermons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $sermons = [];
    }
}

// Only use JSON if DB returned nothing or if you want to merge specific placeholders
// In this case, the user wants DB to reflect correctly.
if (empty($sermons)) {
    $sermons = rgcLoadJson('sermons.json', []);
}

$events = rgcLoadJson('events.json', []);
$ministries = rgcLoadJson('ministries.json', []);
$projects = rgcLoadJson('projects.json', []);
$testimonials = rgcLoadJson('testimonials.json', []);
$gallery = rgcLoadJson('gallery.json', []);
$slider = rgcLoadJson('slider.json', []);

// Sort slider by order
usort($slider, function($a, $b) {
    return ($a['order'] ?? 1) - ($b['order'] ?? 1);
});

$bishopImageMarker = '__BISHOP_IMAGE__';
$bishopQuoteMarker = '__BISHOP_QUOTE__|';
$bishopImage = rgcUrl('assets/uploads/gallery_1772215472_118d5f.jpg');
$bishopQuote = 'Prayer is not our last option. It is our first response and our greatest strength.';
$bishopAuthor = 'Bishop';
$publicGallery = [];
$publicTestimonials = [];
$footerData = rgcLoadJson('footer.json', []);

foreach ($gallery as $item) {
  if (($item['caption'] ?? '') === $bishopImageMarker) {
    if (!empty($item['image'])) {
      $bishopImage = rgcUrl($item['image']);
    }
    continue;
  }
  // Ensure image path is correct
  if (!empty($item['image']) && !str_starts_with($item['image'], 'http')) {
    $item['image'] = rgcUrl($item['image']);
  }
  $publicGallery[] = $item;
}
foreach ($testimonials as $item) {
  $nameValue = (string)($item['name'] ?? '');
  if (str_starts_with($nameValue, $bishopQuoteMarker)) {
    $bishopQuote = trim((string)($item['message'] ?? $bishopQuote));
    $storedAuthor = trim(substr($nameValue, strlen($bishopQuoteMarker)));
    if ($storedAuthor !== '') {
      $bishopAuthor = $storedAuthor;
    }
    continue;
  }
  $publicTestimonials[] = $item;
}

$upcomingEvents = array_values(array_filter($events, static function ($event) {
  return !empty($event['event_at']) && strtotime((string) $event['event_at']) > time();
}));
usort($upcomingEvents, static function ($a, $b) {
  return strtotime((string) ($a['event_at'] ?? '')) <=> strtotime((string) ($b['event_at'] ?? ''));
});
$displayEvents = array_slice($upcomingEvents, 0, 3);

$featuredSermons = array_values(array_filter($sermons, static function ($sermon) {
  return !empty($sermon['featured']);
}));
if (empty($featuredSermons)) {
  $featuredSermons = $sermons;
}
$featuredSermons = array_slice($featuredSermons, 0, 3);

$mainVideoSermon = null;
$mainVideoUrl = '';
foreach ($sermons as $sermon) {
  if (!empty($sermon['featured']) && !empty($sermon['youtube_url'])) {
    $mainVideoSermon = $sermon;
    $mainVideoUrl = getHomeYoutubeEmbedUrl((string) $sermon['youtube_url']);
    break;
  }
}
if (!$mainVideoSermon) {
  foreach ($sermons as $sermon) {
    if (!empty($sermon['youtube_url'])) {
      $mainVideoSermon = $sermon;
      $mainVideoUrl = getHomeYoutubeEmbedUrl((string) $sermon['youtube_url']);
      break;
    }
  }
}

$homeMinistries = array_slice($ministries, 0, 3);
$homeProjects = array_slice($projects, 0, 3);
$homeTestimonials = array_slice($publicTestimonials, 0, 2);
$homeGallery = array_slice($publicGallery, 0, 4);
$heroBackground = $homeGallery[0]['image'] ?? $bishopImage;
$nextEvent = $displayEvents[0] ?? null;
$galleryPool = !empty($publicGallery) ? array_values($publicGallery) : [['image' => $bishopImage, 'caption' => 'Redeemed Gospel Church']];

if (empty($slider)) {
  $slider = [[
    'id' => 1,
    'title' => 'A Church Family For Worship, Prayer, And Growth',
    'subtitle' => 'Welcome Home',
    'description' => 'Join a Christ-centered community where faith is formed, hope is renewed, and love is put into action.',
    'image' => $heroBackground,
    'button1_text' => 'Plan Your Visit',
    'button1_link' => rgcUrl('contact.php'),
    'button2_text' => 'Watch Sermons',
    'button2_link' => rgcUrl('sermons.php'),
    'background_style' => 'brand',
    'order' => 1,
  ]];
}

$leadSermon = $featuredSermons[0] ?? null;
$secondarySermons = array_slice($featuredSermons, 1, 2);

require __DIR__ . '/includes/header.php';
?>

<div id="chatDock" class="chat-dock">
  <button id="chatOpen" type="button" class="chat-trigger" aria-controls="chatPanel" aria-expanded="false">
    <svg class="chat-trigger__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
    <span>Chat</span>
  </button>
  <form id="chatPanel" method="post" action="<?php echo rgcUrl('chat.php'); ?>" class="chat-panel" hidden>
    <?php echo rgcCsrfField('public_chat'); ?>
    <div class="chat-panel__header">
      <p class="chat-panel__title">Chat with us</p>
      <button id="chatClose" type="button" class="chat-close" aria-label="Close chat">&times;</button>
    </div>
    <?php if (!rgcPublicUser()): ?>
      <input name="name" autocomplete="name" class="form-input mb-2" placeholder="Your name">
      <input name="email" type="email" autocomplete="email" autocomplete="email" class="form-input mb-2" placeholder="Email (optional)">
    <?php endif; ?>
    <textarea name="message" class="form-input form-textarea mb-2" placeholder="Type your message..." required></textarea>
    <div class="chat-panel__actions">
      <button class="px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">Send</button>
      <a
        id="chatWaLink"
        class="chat-link"
        target="_blank"
        href="https://wa.me/254<?php echo preg_replace('/^0/', '', $footerData['contact']['whatsapp'] ?? '729222999'); ?>"
        data-whatsapp-number="254<?php echo preg_replace('/^0/', '', $footerData['contact']['whatsapp'] ?? '729222999'); ?>"
      >WhatsApp</a>
    </div>
  </form>
</div>

<section class="sanctuary-stage" style="--hero-image: url('<?php echo htmlspecialchars($heroBackground); ?>');">
  <div class="max-w-[1280px] mx-auto px-4 pt-10 pb-14 md:pt-16 md:pb-24">
    <div class="sanctuary-stage__grid">
      <div class="sanctuary-copy text-center md:text-left">
        <span class="sanctuary-kicker">Not Just A Church Service</span>
        <h1 class="sanctuary-copy__title sanctuary-copy__title--desktop text-5xl md:text-7xl lg:text-8xl font-bold leading-tight">Step into a living story of worship, healing, and belonging.</h1>
        <h1 class="sanctuary-copy__title sanctuary-copy__title--mobile text-5xl md:text-7xl lg:text-8xl font-bold leading-tight">Worship, prayer, and belonging.</h1>
        <p class="sanctuary-copy__lead sanctuary-copy__lead--desktop text-lg md:text-xl max-w-3xl mx-auto md:mx-0"><?php echo htmlspecialchars($footerData['church']['mission'] ?? 'Join our community of faith as we seek to worship God, grow in His grace, and serve the people of Eldoret and beyond.'); ?></p>
        <p class="sanctuary-copy__lead sanctuary-copy__lead--mobile text-lg md:text-xl max-w-3xl mx-auto md:mx-0">Find a Christ-centered church home in Eldoret with clear teaching, warm worship, and real community.</p>
        <div class="sanctuary-copy__actions justify-center md:justify-start">
          <a href="<?php echo rgcUrl('contact.php'); ?>" class="btn btn-primary">Plan Your Visit</a>
          <a href="<?php echo rgcUrl('sermons.php'); ?>" class="btn btn-outline homepage-mobile-secondary">Experience A Message</a>
        </div>
        <div class="sanctuary-copy__meta grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-8">
          <div>
            <span>Sunday Gathering</span>
            <strong><?php echo htmlspecialchars(($footerData['service_times'][0]['day'] ?? 'Sunday Service') . ' · ' . ($footerData['service_times'][0]['time'] ?? '9:00 AM')); ?></strong>
          </div>
          <div>
            <span>Location</span>
            <strong><?php echo htmlspecialchars($footerData['contact']['address'] ?? 'Eldoret, Kenya'); ?></strong>
          </div>
          <div class="homepage-mobile-secondary">
            <span>Atmosphere</span>
            <strong>Warm worship. Real prayer.</strong>
          </div>
          <?php if ($nextEvent): ?>
          <div class="homepage-mobile-secondary">
            <span>Next Gathering</span>
            <strong><?php echo htmlspecialchars($nextEvent['title'] ?? 'Upcoming Event'); ?></strong>
            <p class="text-[0.7rem] mt-1 text-brand-200 font-mono" data-event-at="<?php echo htmlspecialchars((string) ($nextEvent['event_at'] ?? '')); ?>"></p>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="sanctuary-orbit homepage-mobile-secondary">
        <div class="sanctuary-feature relative overflow-hidden h-full group">
          <div class="hero-slider-progress" aria-hidden="true">
            <span id="heroSliderProgressBar" class="hero-slider-progress__bar"></span>
          </div>
          <div id="heroSlider" class="relative w-full aspect-video md:aspect-[16/10]">
            <?php foreach ($slider as $idx => $slide): ?>
              <?php 
                $slidePoolItem = $galleryPool[$idx % count($galleryPool)] ?? ['image' => $bishopImage];
                $slideImg = !empty($slide['image']) ? $slide['image'] : ($slidePoolItem['image'] ?? $bishopImage);
                $slideLinkOne = trim((string) ($slide['button1_link'] ?? ''));
                $slideLinkTwo = trim((string) ($slide['button2_link'] ?? ''));
                $slideTone = trim((string) ($slide['background_style'] ?? 'brand'));
              ?>
              <div class="hero-slide-item absolute inset-0 opacity-0 transition-opacity duration-1000 ease-in-out <?php echo $idx === 0 ? 'active opacity-100' : ''; ?>" data-slide-index="<?php echo $idx; ?>">
                <div class="absolute inset-0 z-10 hero-slide-item__overlay hero-slide-item__overlay--<?php echo htmlspecialchars(in_array($slideTone, ['brand', 'slate', 'gradient'], true) ? $slideTone : 'brand'); ?>"></div>
                <img src="<?php echo htmlspecialchars($slideImg); ?>" 
                     alt="<?php echo htmlspecialchars($slide['title']); ?>" 
                     class="w-full h-full object-cover">
                
                <div class="absolute inset-0 z-20 flex flex-col justify-end p-6 md:p-8">
                  <div class="hero-slide-item__content">
                    <span class="sanctuary-feature__tag !text-brand-400"><?php echo htmlspecialchars($slide['subtitle'] ?? 'Our Vision'); ?></span>
                    <strong class="text-white text-xl md:text-2xl lg:text-3xl block mb-2 leading-tight"><?php echo htmlspecialchars($slide['title']); ?></strong>
                    <p class="text-slate-200 text-sm md:text-base max-w-xl"><?php echo htmlspecialchars($slide['description'] ?? ''); ?></p>
                    <?php if ($slideLinkOne !== '' || $slideLinkTwo !== ''): ?>
                    <div class="hero-slide-item__actions">
                      <?php if ($slideLinkOne !== ''): ?>
                      <a href="<?php echo htmlspecialchars($slideLinkOne); ?>" class="hero-slide-item__action hero-slide-item__action--primary">
                        <?php echo htmlspecialchars($slide['button1_text'] ?? 'Learn More'); ?>
                      </a>
                      <?php endif; ?>
                      <?php if ($slideLinkTwo !== ''): ?>
                      <a href="<?php echo htmlspecialchars($slideLinkTwo); ?>" class="hero-slide-item__action hero-slide-item__action--secondary">
                        <?php echo htmlspecialchars($slide['button2_text'] ?? 'Explore'); ?>
                      </a>
                      <?php endif; ?>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          
          <!-- Slider Navigation Controls -->
          <div class="absolute bottom-4 right-4 z-30 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            <button id="heroPrev" type="button" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 flex items-center justify-center text-white transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button id="heroNext" type="button" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 flex items-center justify-center text-white transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
          </div>
          <!-- Progress Dots -->
          <div class="absolute bottom-4 left-6 z-30 flex gap-1.5">
            <?php foreach ($slider as $idx => $slide): ?>
              <div class="hero-dot w-1.5 h-1.5 rounded-full bg-white/30 transition-all duration-300 <?php echo $idx === 0 ? 'active !w-6 !bg-brand-400' : ''; ?>" data-slide-to="<?php echo $idx; ?>"></div>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if (count($slider) > 1): ?>
        <div class="hero-slider-tabs" role="tablist" aria-label="Homepage hero slides">
          <?php foreach ($slider as $idx => $slide): ?>
          <button
            type="button"
            class="hero-tab <?php echo $idx === 0 ? 'active' : ''; ?>"
            data-slide-to="<?php echo $idx; ?>"
            role="tab"
            aria-selected="<?php echo $idx === 0 ? 'true' : 'false'; ?>">
            <span class="hero-tab__eyebrow"><?php echo htmlspecialchars($slide['subtitle'] ?? 'Our Vision'); ?></span>
            <strong><?php echo htmlspecialchars($slide['title'] ?? 'Slide'); ?></strong>
          </button>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="sanctuary-path homepage-mobile-secondary grid-cols-1 md:grid-cols-3 gap-4 mt-12">
      <a href="<?php echo rgcUrl('about.php'); ?>" class="sanctuary-path__item">
        <span>01</span>
        <strong>Meet The Heart</strong>
        <p>Discover the vision, values, and leadership behind the ministry.</p>
      </a>
      <a href="<?php echo rgcUrl('events.php'); ?>" class="sanctuary-path__item">
        <span>02</span>
        <strong>Follow The Rhythm</strong>
        <p>See the gatherings, moments, and people shaping church life this week.</p>
      </a>
      <a href="<?php echo rgcUrl('contact.php'); ?>" class="sanctuary-path__item">
        <span>03</span>
        <strong>Take A Next Step</strong>
        <p>Ask for prayer, plan your visit, or start a conversation with the team.</p>
      </a>
    </div>
  </div>
</section>

<section class="mobile-home-shortcuts">
  <div class="max-w-[1280px] mx-auto px-4">
    <div class="mobile-home-shortcuts__grid">
      <a href="<?php echo rgcUrl('contact.php'); ?>" class="mobile-home-shortcuts__card">
        <span>Visit</span>
        <strong>Plan Your Visit</strong>
      </a>
      <a href="<?php echo rgcUrl('sermons.php'); ?>" class="mobile-home-shortcuts__card">
        <span>Watch</span>
        <strong>Latest Sermon</strong>
      </a>
      <a href="<?php echo rgcUrl('contact.php'); ?>" class="mobile-home-shortcuts__card">
        <span>Prayer</span>
        <strong>Send Request</strong>
      </a>
    </div>
  </div>
</section>

<section class="section-padding pt-0 homepage-mobile-secondary">
  <div class="max-w-[1280px] mx-auto px-4">
    <article class="story-card story-card--arrival p-12 md:p-20 rounded-[3rem] bg-white shadow-2xl border border-slate-100 text-center max-w-5xl mx-auto">
      <span class="story-card__eyebrow">The Experience</span>
      <h2 class="text-4xl md:text-6xl font-display font-bold mt-6 leading-tight">Church should feel like an exhale, not pressure.</h2>
      <p class="text-xl text-slate-500 mt-6 leading-relaxed max-w-3xl mx-auto">We designed this journey for people who want beauty, truth, prayer, and a place to belong without pretense.</p>
      <div class="grid md:grid-cols-3 gap-8 mt-16 text-left">
        <div class="p-8 rounded-2xl bg-slate-50 border border-slate-100">
          <strong class="text-xl block mb-2 text-slate-900">Come Curious</strong>
          <p class="text-slate-500">There is room for questions, fresh starts, and honest faith.</p>
        </div>
        <div class="p-8 rounded-2xl bg-slate-50 border border-slate-100">
          <strong class="text-xl block mb-2 text-slate-900">Bring Family</strong>
          <p class="text-slate-500">Every generation matters and has a place in the room.</p>
        </div>
        <div class="p-8 rounded-2xl bg-slate-50 border border-slate-100">
          <strong class="text-xl block mb-2 text-slate-900">Leave Renewed</strong>
          <p class="text-slate-500">Our goal is practical hope, not religious performance.</p>
        </div>
      </div>
    </article>
  </div>
</section>

<section class="section-padding bg-slate-50 homepage-mobile-secondary">
  <div class="max-w-[1280px] mx-auto px-4">
    <article class="p-12 md:p-20 rounded-[3rem] bg-white shadow-xl border border-slate-200/60">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-16">
        <div class="max-w-2xl">
          <span class="story-card__eyebrow">The Calendar</span>
          <h2 class="text-4xl md:text-6xl font-display font-bold mt-4 leading-tight">Gathering together.</h2>
          <p class="text-lg text-slate-500 mt-4">Join us for upcoming moments of worship, fellowship, and growth.</p>
        </div>
        <a href="<?php echo rgcUrl('events.php'); ?>" class="btn btn-outline h-fit">Explore Full Calendar</a>
      </div>
      <div class="grid lg:grid-cols-3 gap-6">
        <?php foreach ($displayEvents as $event): ?>
        <a href="<?php echo htmlspecialchars(rgcUrl('events.php')); ?>" class="group block p-8 rounded-3xl bg-slate-50 border border-slate-200/50 hover:bg-white hover:shadow-xl transition-all">
          <div class="flex items-center gap-4 mb-6">
            <div class="w-16 h-16 rounded-2xl bg-brand-500 flex flex-col items-center justify-center text-white">
              <span class="text-[0.6rem] font-bold uppercase tracking-widest opacity-80"><?php echo htmlspecialchars(date('M', strtotime((string) $event['event_at']))); ?></span>
              <span class="text-2xl font-bold leading-none"><?php echo htmlspecialchars(date('d', strtotime((string) $event['event_at']))); ?></span>
            </div>
            <div class="h-10 w-px bg-slate-200"></div>
            <span class="text-sm font-bold text-brand-600 uppercase tracking-widest">Upcoming</span>
          </div>
          <h3 class="text-2xl font-bold text-slate-900 group-hover:text-brand-600 transition-colors"><?php echo htmlspecialchars($event['title'] ?? 'Upcoming Event'); ?></h3>
          <p class="text-slate-500 mt-4"><?php echo htmlspecialchars(date('g:i A', strtotime((string) $event['event_at'])) . ' · ' . ($event['location'] ?? 'Sanctuary')); ?></p>
        </a>
        <?php endforeach; ?>
      </div>
    </article>
  </div>
</section>

<section class="section-padding bg-brand-900 overflow-hidden homepage-mobile-secondary">
  <div class="max-w-[1280px] mx-auto px-4 relative">
    <div class="absolute top-0 right-0 w-96 h-96 bg-brand-500/10 rounded-full blur-[120px] -mr-48 -mt-48"></div>
    <article class="max-w-5xl">
      <span class="story-card__eyebrow !text-brand-300">The Heart</span>
      <blockquote class="text-4xl md:text-7xl font-display font-bold text-white mt-8 leading-tight">
        "<?php echo htmlspecialchars($bishopQuote); ?>"
      </blockquote>
      <div class="mt-12 flex items-center gap-6">
        <div class="w-20 h-20 rounded-full border-2 border-brand-400 p-1">
          <img src="<?php echo htmlspecialchars($bishopImage); ?>" class="w-full h-full object-cover rounded-full" alt="">
        </div>
        <div>
          <p class="text-xl font-bold text-white"><?php echo htmlspecialchars($bishopAuthor); ?></p>
          <p class="text-brand-300 uppercase tracking-widest text-xs font-bold mt-1">Lead Bishop</p>
        </div>
      </div>
    </article>
  </div>
</section>

<section class="section-padding bg-white">
  <div class="max-w-[1280px] mx-auto px-4">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-16">
      <div class="max-w-2xl">
        <span class="story-card__eyebrow">The Messages</span>
        <h2 class="text-4xl md:text-6xl font-display font-bold mt-4 leading-tight">Truth for Monday.</h2>
        <p class="text-lg text-slate-500 mt-4">Experience teachings that bridge the gap between biblical truth and daily life.</p>
      </div>
      <a href="<?php echo rgcUrl('sermons.php'); ?>" class="btn btn-primary h-fit homepage-mobile-secondary">Watch All Sermons</a>
    </div>

    <?php if ($leadSermon): ?>
    <?php
      $leadSermonDate = (string) ($leadSermon['scheduled_at'] ?? $leadSermon['created_at'] ?? '');
      $leadSermonThumb = getHomeYoutubeThumbnail((string) ($leadSermon['youtube_url'] ?? ''));
      if ($leadSermonThumb === '') {
        $leadSermonThumb = $bishopImage;
      }
    ?>
    <div class="sermon-showcase">
      <a href="<?php echo htmlspecialchars(rgcUrl('sermons.php?id=' . (int) ($leadSermon['id'] ?? 0))); ?>" class="sermon-showcase__lead group">
        <div class="sermon-showcase__lead-media">
          <img src="<?php echo htmlspecialchars($leadSermonThumb); ?>" alt="<?php echo htmlspecialchars($leadSermon['title'] ?? 'Sermon'); ?>" class="sermon-showcase__lead-image">
          <div class="sermon-showcase__lead-overlay"></div>
          <div class="sermon-showcase__play">
            <svg class="w-6 h-6 ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
          </div>
        </div>
        <div class="sermon-showcase__lead-body">
          <div class="sermon-showcase__meta">
            <span class="sermon-showcase__badge">Featured Message</span>
            <?php if ($leadSermonDate !== ''): ?>
            <span class="sermon-showcase__date"><?php echo htmlspecialchars(date('M d, Y', strtotime($leadSermonDate))); ?></span>
            <?php endif; ?>
          </div>
          <h3><?php echo htmlspecialchars($leadSermon['title'] ?? 'Sermon'); ?></h3>
          <p class="sermon-showcase__speaker"><?php echo htmlspecialchars($leadSermon['speaker'] ?? 'Redeemed Gospel Church'); ?></p>
          <p class="sermon-showcase__summary">Watch a practical, hope-filled message that connects biblical truth to daily life, worship, and spiritual growth.</p>
          <div class="sermon-showcase__actions">
            <span class="sermon-showcase__pill">Watch Now</span>
            <span class="sermon-showcase__pill sermon-showcase__pill--muted">Sunday Teaching</span>
          </div>
        </div>
      </a>

      <div class="sermon-showcase__stack homepage-mobile-secondary">
        <?php foreach ($secondarySermons as $sermon): ?>
        <?php
          $sermonDate = (string) ($sermon['scheduled_at'] ?? $sermon['created_at'] ?? '');
          $sermonThumb = getHomeYoutubeThumbnail((string) ($sermon['youtube_url'] ?? ''));
          if ($sermonThumb === '') {
            $sermonThumb = $heroBackground;
          }
        ?>
        <a href="<?php echo htmlspecialchars(rgcUrl('sermons.php?id=' . (int) ($sermon['id'] ?? 0))); ?>" class="sermon-stack-card group">
          <div class="sermon-stack-card__thumb">
            <img src="<?php echo htmlspecialchars($sermonThumb); ?>" alt="<?php echo htmlspecialchars($sermon['title'] ?? 'Sermon'); ?>" class="sermon-stack-card__image">
          </div>
          <div class="sermon-stack-card__body">
            <span class="sermon-stack-card__label">Watch Message</span>
            <h4><?php echo htmlspecialchars($sermon['title'] ?? 'Sermon'); ?></h4>
            <p class="sermon-stack-card__speaker"><?php echo htmlspecialchars($sermon['speaker'] ?? 'Redeemed Gospel Church'); ?></p>
            <?php if ($sermonDate !== ''): ?>
            <p class="sermon-stack-card__date"><?php echo htmlspecialchars(date('M d, Y', strtotime($sermonDate))); ?></p>
            <?php endif; ?>
            <div class="sermon-stack-card__cta">Open message</div>
          </div>
        </a>
        <?php endforeach; ?>

        <?php if (empty($secondarySermons)): ?>
        <div class="sermon-stack-card sermon-stack-card--empty">
          <div class="sermon-stack-card__body">
            <span class="sermon-stack-card__label">More Messages</span>
            <h4>More teachings will appear here.</h4>
            <p class="sermon-stack-card__speaker">Add more featured sermons to build out the desktop showcase.</p>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<div id="galleryLightbox" class="gallery-lightbox" aria-hidden="true" role="dialog" aria-label="Gallery image viewer">
  <div class="gallery-lightbox__backdrop" data-close-lightbox="true"></div>
  <div class="gallery-lightbox__content" role="document">
    <div class="gallery-lightbox__rings" aria-hidden="true"></div>
    <button class="gallery-lightbox__close" type="button" aria-label="Close gallery viewer">
      <span aria-hidden="true">&times;</span>
    </button>
    <div id="galleryLightboxOrbit" class="gallery-lightbox__orbit" aria-hidden="true"></div>
    <figure class="gallery-lightbox__figure">
      <img id="galleryLightboxImage" src="" alt="">
      <figcaption id="galleryLightboxCaption"></figcaption>
    </figure>
    <div id="galleryLightboxThumbs" class="gallery-lightbox__thumbs" aria-label="Gallery thumbnails"></div>
    <div class="gallery-lightbox__controls">
      <button class="gallery-lightbox__nav gallery-lightbox__nav--prev" type="button" aria-label="Previous image">
        <span aria-hidden="true">&#10094;</span>
      </button>
      <button class="gallery-lightbox__nav gallery-lightbox__nav--next" type="button" aria-label="Next image">
        <span aria-hidden="true">&#10095;</span>
      </button>
    </div>
  </div>
</div>

<section class="section-padding pt-0">
  <div class="max-w-[1280px] mx-auto px-4">
    <div class="mosaic-wall grid-cols-1 md:grid-cols-2 gap-6">
      <article class="mosaic-wall__intro homepage-mobile-secondary p-8 rounded-3xl bg-white shadow-lg border border-slate-100">
        <span class="story-card__eyebrow">Church Pulse</span>
        <h2 class="text-3xl md:text-4xl font-display font-bold mt-3 leading-tight">Belong in community. Serve with purpose. Carry hope into the city.</h2>
        <p class="text-lg text-slate-600 mt-4 leading-relaxed">Instead of treating church like a weekly appointment, we see it as a living rhythm of worship, formation, outreach, and practical love.</p>
      </article>

      <div class="mosaic-wall__columns">
        <article class="mosaic-panel">
          <div class="pulse-card__header">
            <div>
              <span class="story-card__eyebrow">Ministries</span>
              <h3 class="text-2xl font-display font-bold mt-3 leading-tight">Spaces to grow.</h3>
            </div>
            <a href="<?php echo rgcUrl('ministries.php'); ?>" class="home-panel__link homepage-mobile-secondary">Explore</a>
          </div>
          <div class="mosaic-mini-list">
            <?php foreach ($homeMinistries as $index => $ministry): ?>
            <article class="mosaic-mini-list__item <?php echo $index > 1 ? 'homepage-mobile-secondary' : ''; ?>">
              <strong><?php echo htmlspecialchars($ministry['name'] ?? 'Ministry'); ?></strong>
              <p><?php echo htmlspecialchars($ministry['description'] ?? ''); ?></p>
            </article>
            <?php endforeach; ?>
          </div>
        </article>

        <article class="mosaic-panel mosaic-panel--accent homepage-mobile-secondary">
          <div class="pulse-card__header">
            <div>
              <span class="story-card__eyebrow">Impact</span>
              <h3 class="text-2xl font-display font-bold mt-3 leading-tight">Love made visible.</h3>
            </div>
            <a href="<?php echo rgcUrl('projects.php'); ?>" class="home-panel__link">See projects</a>
          </div>
          <div class="mosaic-mini-list">
            <?php foreach ($homeProjects as $project): ?>
            <article class="mosaic-mini-list__item home-project-card">
              <span class="home-project-card__status"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($project['status'] ?? 'community outreach')))); ?></span>
              <strong><?php echo htmlspecialchars($project['title'] ?? 'Project'); ?></strong>
              <p><?php echo htmlspecialchars($project['description'] ?? ''); ?></p>
            </article>
            <?php endforeach; ?>
          </div>
        </article>
      </div>
    </div>
  </div>
</section>

<?php if (!empty($homeTestimonials)): ?>
<section class="section-padding pt-0 homepage-mobile-secondary">
  <div class="max-w-[1280px] mx-auto px-4">
    <div class="echo-strip grid-cols-1 md:grid-cols-2 gap-6">
      <div class="echo-strip__intro p-8 rounded-3xl bg-white shadow-lg border border-slate-100">
        <span class="story-card__eyebrow">Voices Of Grace</span>
        <h2 class="text-3xl md:text-4xl font-display font-bold mt-3 leading-tight">Stories that sound like freedom.</h2>
      </div> 
      <div class="echo-strip__grid">
        <?php foreach ($homeTestimonials as $testimonial): ?>
        <blockquote class="echo-card">
          <p>"<?php echo htmlspecialchars($testimonial['message'] ?? ''); ?>"</p>
          <cite>— <?php echo htmlspecialchars($testimonial['name'] ?? 'Church Member'); ?></cite>
        </blockquote>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section-padding pt-0 homepage-mobile-secondary">
  <div class="max-w-[1120px] mx-auto px-4">
    <div class="summit-cta p-10 rounded-3xl bg-white shadow-lg border border-slate-100 text-center">
      <span class="story-card__eyebrow">Begin Here</span>
      <h2 class="text-4xl md:text-5xl font-display font-bold mt-3 leading-tight">You do not have to figure faith out alone.</h2>
      <p>Come for worship, ask for prayer, bring your questions, and let this be the start of something deeply life-giving.</p>
      <div class="summit-cta__actions">
        <a href="<?php echo rgcUrl('contact.php'); ?>" class="btn btn-primary">Send Prayer Request</a>
        <a href="<?php echo rgcUrl('about.php'); ?>" class="btn btn-outline">Learn More About Us</a>
      </div>
    </div>
  </div>
</section>

<script>
// Hero Slider
(function() {
  const sliderRoot = document.getElementById('heroSlider');
  if (!sliderRoot) return;

  const slides = sliderRoot.querySelectorAll('.hero-slide-item');
  const dots = document.querySelectorAll('.hero-dot');
  const tabs = document.querySelectorAll('.hero-tab');
  const prevBtn = document.getElementById('heroPrev');
  const nextBtn = document.getElementById('heroNext');
  const progressBar = document.getElementById('heroSliderProgressBar');
  
  if (slides.length === 0) return;
  
  let current = 0;
  const total = slides.length;
  let autoAdvance = null;
  
  function showSlide(index) {
    slides.forEach((slide, i) => {
      if (i === index) {
        slide.style.opacity = '1';
        slide.style.visibility = 'visible';
        slide.classList.add('active');
      } else {
        slide.style.opacity = '0';
        slide.style.visibility = 'hidden';
        slide.classList.remove('active');
      }
      slide.style.transition = 'opacity 0.5s ease';
    });
    
    dots.forEach((dot, i) => {
      if (i === index) {
        dot.classList.add('active');
        dot.classList.remove('bg-white/70');
        dot.classList.add('bg-brand-400');
      } else {
        dot.classList.remove('active');
        dot.classList.remove('bg-brand-400');
        dot.classList.add('bg-white/70');
      }
    });

    tabs.forEach((tab, i) => {
      const active = i === index;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });
  }

  function restartTimer() {
    if (autoAdvance !== null) {
      clearInterval(autoAdvance);
    }
    if (progressBar) {
      progressBar.style.transition = 'none';
      progressBar.style.width = '0%';
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          progressBar.style.transition = 'width 6s linear';
          progressBar.style.width = total > 1 ? '100%' : '0%';
        });
      });
    }
    if (total > 1) {
      autoAdvance = setInterval(nextSlide, 6000);
    }
  }
  
  function nextSlide() {
    current = (current + 1) % total;
    showSlide(current);
    restartTimer();
  }
  
  function prevSlide() {
    current = (current - 1 + total) % total;
    showSlide(current);
    restartTimer();
  }
  
  if (prevBtn) prevBtn.addEventListener('click', prevSlide);
  if (nextBtn) nextBtn.addEventListener('click', nextSlide);
  
  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => {
      current = i;
      showSlide(current);
      restartTimer();
    });
  });

  tabs.forEach((tab, i) => {
    tab.addEventListener('click', () => {
      current = i;
      showSlide(current);
      restartTimer();
    });
  });
  
  // Initialize first slide
  showSlide(0);
  restartTimer();
})();

// Countdown
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
  document.querySelectorAll('[data-event-at]').forEach((el) => {
    const target = new Date(el.dataset.eventAt.replace(' ', 'T')).getTime();
    const diff = Math.floor((target - now) / 1000);
    el.textContent = formatCountdown(diff);
  });
}

setInterval(tickCountdowns, 1000);
tickCountdowns();

// Scroll reveal animations
(function() {
  const revealItems = document.querySelectorAll('.scroll-reveal');
  if (!revealItems.length) return;

  revealItems.forEach((item) => item.classList.add('reveal-ready'));

  if (!('IntersectionObserver' in window)) {
    revealItems.forEach((item) => item.classList.add('is-visible'));
    return;
  }

  const observer = new IntersectionObserver((entries, obs) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-visible');
      obs.unobserve(entry.target);
    });
  }, {
    threshold: 0.15,
    rootMargin: '0px 0px -60px 0px'
  });

  revealItems.forEach((item) => observer.observe(item));
})();

// Bishop image parallax
(function() {
  const image = document.querySelector('.bishop-parallax');
  if (!image) return;

  function updateParallax() {
    const rect = image.getBoundingClientRect();
    const vh = window.innerHeight || document.documentElement.clientHeight;
    const progress = (vh - rect.top) / (vh + rect.height);
    const y = (progress - 0.5) * 34;
    image.style.transform = `scale(1.08) translateY(${y.toFixed(2)}px)`;
  }

  window.addEventListener('scroll', updateParallax, { passive: true });
  window.addEventListener('resize', updateParallax);
  updateParallax();
})();

// Gallery river parallax
(function() {
  const cards = Array.from(document.querySelectorAll('.gallery-river__card'));
  if (!cards.length) return;

  let ticking = false;
  function paint() {
    const vh = window.innerHeight || document.documentElement.clientHeight;
    cards.forEach((card) => {
      const media = card.querySelector('.gallery-river__media');
      if (!media) return;
      const rect = card.getBoundingClientRect();
      const depth = Number(card.style.getPropertyValue('--depth')) || 1;
      const center = rect.top + rect.height / 2;
      const delta = (center - vh / 2) / vh;
      const y = delta * (8 + depth * 5);
      media.style.transform = `translateY(${y.toFixed(2)}px)`;
    });
    ticking = false;
  }

  function onScroll() {
    if (ticking) return;
    ticking = true;
    window.requestAnimationFrame(paint);
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll);
  onScroll();

  // 3D tilt on pointer move inside a card
  cards.forEach((card) => {
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left - rect.width / 2;
      const y = e.clientY - rect.top - rect.height / 2;
      const rotateX = (y / rect.height) * 12;
      const rotateY = (x / rect.width) * -12;
      card.style.transform = `translateZ(0) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
    });

    card.addEventListener('mouseleave', () => {
      // reset transform - keep parallax depth if active
      card.style.transform = '';
    });
  });
})();

// Gallery Lightbox
(function() {
  const triggers = Array.from(document.querySelectorAll('.gallery-trigger'));
  triggers.forEach((t) => {
    if (!t.dataset.gallerySrc) {
      t.dataset.gallerySrc = t.src || '';
    }
  });
  const lightbox = document.getElementById('galleryLightbox');
  const lightboxImage = document.getElementById('galleryLightboxImage');
  const lightboxCaption = document.getElementById('galleryLightboxCaption');
  const lightboxOrbit = document.getElementById('galleryLightboxOrbit');
  const lightboxThumbs = document.getElementById('galleryLightboxThumbs');
  const closeBtn = lightbox ? lightbox.querySelector('.gallery-lightbox__close') : null;
  const prevBtn = lightbox ? lightbox.querySelector('.gallery-lightbox__nav--prev') : null;
  const nextBtn = lightbox ? lightbox.querySelector('.gallery-lightbox__nav--next') : null;
  const closeBackdrop = lightbox ? lightbox.querySelector('[data-close-lightbox="true"]') : null;
  let currentIndex = 0;

  if (!lightbox || !lightboxImage || !lightboxCaption || !lightboxOrbit || !lightboxThumbs || !triggers.length) return;

  const orbitSlots = [
    { x: -170, y: -150, size: 'lg' },
    { x: 165, y: -145, size: 'md' },
    { x: -225, y: -10, size: 'sm' },
    { x: 230, y: 20, size: 'lg' },
    { x: -155, y: 160, size: 'md' },
    { x: 170, y: 165, size: 'sm' }
  ];

  function renderImage(index) {
    const item = triggers[index];
    if (!item) {
      lightboxCaption.textContent = 'Image unavailable';
      lightboxImage.src = '';
      lightboxImage.alt = '';
      lightboxImage.classList.remove('is-loading');
      return;
    }
    lightboxImage.onload = null;
    lightboxImage.onerror = null;
    lightboxImage.classList.add('is-loading');
    lightboxImage.src = item.dataset.gallerySrc || item.src || '';
    lightboxImage.alt = item.alt || 'Gallery image';
    lightboxCaption.textContent = item.dataset.galleryCaption || '';

    if (lightboxImage.complete) {
      lightboxImage.classList.remove('is-loading');
    } else {
      lightboxImage.onload = () => lightboxImage.classList.remove('is-loading');
      lightboxImage.onerror = () => lightboxImage.classList.remove('is-loading');
    }
  }

  function renderThumbs(index) {
    lightboxThumbs.innerHTML = '';
    triggers.forEach((item, itemIndex) => {
      const thumb = document.createElement('button');
      thumb.type = 'button';
      thumb.className = 'gallery-thumb';
      if (itemIndex === index) thumb.classList.add('is-active');
      thumb.setAttribute('aria-label', item.dataset.galleryCaption || 'Open image');

      const thumbImg = document.createElement('img');
      thumbImg.src = item.dataset.gallerySrc || '';
      thumbImg.alt = item.alt || 'Gallery thumbnail';
      thumb.appendChild(thumbImg);

      thumb.addEventListener('click', () => {
        currentIndex = itemIndex;
        renderImage(currentIndex);
        renderOrbit(currentIndex);
        renderThumbs(currentIndex);
      });

      lightboxThumbs.appendChild(thumb);
    });
  }

  function renderOrbit(index) {
    let sources = triggers
      .map((item, itemIndex) => ({ item, itemIndex }))
      .filter(({ itemIndex }) => itemIndex !== index);
    if (sources.length === 0) {
      sources = Array.from({ length: orbitSlots.length }, () => ({ item: triggers[index], itemIndex: index }));
    }

    lightboxOrbit.innerHTML = '';

    orbitSlots.forEach((slot, slotIndex) => {
      const source = sources[slotIndex % sources.length];
      if (!source) return;

      const orb = document.createElement('button');
      orb.type = 'button';
      orb.className = `gallery-orb gallery-orb--${slot.size}`;
      orb.style.setProperty('--x', `${slot.x}px`);
      orb.style.setProperty('--y', `${slot.y}px`);
      orb.style.setProperty('--spin-delay', `${slotIndex * -1.1}s`);
      orb.style.backgroundImage = `url("${source.item.dataset.gallerySrc || ''}")`;
      orb.setAttribute('aria-label', source.item.dataset.galleryCaption || 'Open gallery image');

      orb.addEventListener('click', () => {
        currentIndex = source.itemIndex;
        renderImage(currentIndex);
        renderOrbit(currentIndex);
      });

      lightboxOrbit.appendChild(orb);
    });
  }

  function openLightbox(index) {
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileMenu && mobileMenu.classList.contains('active')) {
      if (typeof window.toggleMobileMenu === 'function') {
        window.toggleMobileMenu(false);
      } else {
        mobileMenu.classList.remove('active');
        document.body.classList.remove('mobile-menu-open');
      }
    }

    currentIndex = index;
    renderImage(currentIndex);
    renderOrbit(currentIndex);
    renderThumbs(currentIndex);
    lightbox.style.display = 'grid';
    lightbox.classList.add('is-open');
    lightbox.setAttribute('aria-hidden', 'false');
    document.body.classList.add('lightbox-open');
  }

  function closeLightbox() {
    lightbox.classList.remove('is-open');
    lightbox.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('lightbox-open');
    window.setTimeout(() => {
      if (!lightbox.classList.contains('is-open')) lightbox.style.display = '';
    }, 350);
  }

  function nextImage() {
    currentIndex = (currentIndex + 1) % triggers.length;
    renderImage(currentIndex);
    renderOrbit(currentIndex);
    renderThumbs(currentIndex);
  }

  function prevImage() {
    currentIndex = (currentIndex - 1 + triggers.length) % triggers.length;
    renderImage(currentIndex);
    renderOrbit(currentIndex);
    renderThumbs(currentIndex);
  }

  triggers.forEach((item, index) => {
    item.addEventListener('click', (event) => {
      event.stopPropagation();
      openLightbox(index);
    });
    item.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        openLightbox(index);
      }
    });
  });

  document.querySelectorAll('.gallery-item').forEach((card) => {
    card.addEventListener('click', (event) => {
      if (event.target.closest('button, a, .gallery-trigger')) return;
      const image = card.querySelector('.gallery-trigger');
      if (!image) return;
      const index = triggers.indexOf(image);
      if (index >= 0) openLightbox(index);
    });
  });

  if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
  if (closeBackdrop) closeBackdrop.addEventListener('click', closeLightbox);
  if (nextBtn) nextBtn.addEventListener('click', nextImage);
  if (prevBtn) prevBtn.addEventListener('click', prevImage);

  document.addEventListener('keydown', (event) => {
    if (!lightbox.classList.contains('is-open')) return;

    if (event.key === 'Escape') closeLightbox();
    if (event.key === 'ArrowRight') nextImage();
    if (event.key === 'ArrowLeft') prevImage();
  });
})();
</script>

<script>
(function(){
  const openBtn = document.getElementById('chatOpen');
  const closeBtn = document.getElementById('chatClose');
  const panel = document.getElementById('chatPanel');
  const msg = panel ? panel.querySelector('textarea[name="message"]') : null;
  const wa = document.getElementById('chatWaLink');
  function showPanel(show) {
    if (!panel || !openBtn) return;
    panel.hidden = !show;
    openBtn.setAttribute('aria-expanded', show ? 'true' : 'false');
    if (show && msg) msg.focus();
  }
  if (openBtn && panel) openBtn.addEventListener('click', () => showPanel(panel.hidden));
  if (closeBtn && panel) closeBtn.addEventListener('click', () => showPanel(false));
  function updateWa() {
    if (!wa) return;
    const number = wa.dataset.whatsappNumber || '';
    const text = encodeURIComponent(msg ? (msg.value || '') : '');
    wa.href = number !== '' ? ('https://wa.me/' + number + '?text=' + text) : ('https://wa.me/?text=' + text);
  }
  if (msg && wa) { msg.addEventListener('input', updateWa); updateWa(); }
  const params = new URLSearchParams(window.location.search);
  if (params.get('chat') === 'offline') {
    const toast = document.createElement('div');
    toast.style.position='fixed';toast.style.bottom='24px';toast.style.left='50%';toast.style.transform='translateX(-50%)';
    toast.style.background='#b91c1c';toast.style.color='#fff';toast.style.padding='8px 16px';toast.style.borderRadius='10px';toast.style.boxShadow='0 8px 24px rgba(0,0,0,.25)';
    toast.textContent='Chat is temporarily unavailable. Please use WhatsApp.';document.body.appendChild(toast);setTimeout(()=>toast.remove(),3500);
  }
  if (params.get('chat') === 'sent') {
    const toast = document.createElement('div');
    toast.style.position='fixed';toast.style.bottom='24px';toast.style.left='50%';toast.style.transform='translateX(-50%)';
    toast.style.background='#059669';toast.style.color='#fff';toast.style.padding='8px 16px';toast.style.borderRadius='10px';toast.style.boxShadow='0 8px 24px rgba(0,0,0,.25)';
    toast.textContent='Message sent. Thank you!';document.body.appendChild(toast);setTimeout(()=>toast.remove(),3000);
  }
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
<section class="section-padding bg-slate-50 homepage-mobile-secondary">
  <div class="max-w-[1280px] mx-auto px-4">
    <div class="text-center max-w-3xl mx-auto mb-20">
      <span class="story-card__eyebrow">Our Atmosphere</span>
      <h2 class="text-4xl md:text-6xl font-display font-bold mt-6 leading-tight">Life in the House.</h2>
      <p class="text-xl text-slate-500 mt-6">A visual journey through our worship, community, and service.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
      <?php foreach ($homeGallery as $index => $item): ?>
      <figure class="group">
        <div class="aspect-[4/5] rounded-[2.5rem] overflow-hidden bg-slate-200 shadow-lg border-8 border-white group-hover:shadow-2xl group-hover:-translate-y-2 transition-all duration-500">
          <img
            src="<?php echo htmlspecialchars($item['image']); ?>"
            alt="<?php echo htmlspecialchars($item['caption']); ?>"
            class="gallery-trigger w-full h-full object-cover cursor-pointer"
            data-gallery-index="<?php echo $index; ?>"
            data-gallery-src="<?php echo htmlspecialchars($item['image']); ?>"
          >
        </div>
        <figcaption class="mt-6 text-center">
          <span class="text-slate-400 text-xs font-bold uppercase tracking-[0.2em]"><?php echo htmlspecialchars($item['caption']); ?></span>
        </figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
