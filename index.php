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
$homepageSpotlight = array_merge([
  'eyebrow' => 'Bishop Spotlight',
  'quote' => '',
  'author' => '',
  'role' => 'Lead Bishop',
  'cta_text' => 'Meet Our Leadership',
  'cta_link' => 'about.php',
], rgcLoadJson('homepage_spotlight.json', []));
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
$homeGallery = array_slice($publicGallery, 0, 6);
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
$bishopQuote = trim((string) ($homepageSpotlight['quote'] ?? '')) !== '' ? trim((string) $homepageSpotlight['quote']) : $bishopQuote;
$bishopAuthor = trim((string) ($homepageSpotlight['author'] ?? '')) !== '' ? trim((string) $homepageSpotlight['author']) : $bishopAuthor;
$bishopRole = trim((string) ($homepageSpotlight['role'] ?? 'Lead Bishop')) ?: 'Lead Bishop';
$bishopEyebrow = trim((string) ($homepageSpotlight['eyebrow'] ?? 'Bishop Spotlight')) ?: 'Bishop Spotlight';
$bishopCtaText = trim((string) ($homepageSpotlight['cta_text'] ?? 'Meet Our Leadership')) ?: 'Meet Our Leadership';
$bishopCtaLinkRaw = trim((string) ($homepageSpotlight['cta_link'] ?? 'about.php'));
$bishopCtaLink = $bishopCtaLinkRaw !== '' && preg_match('#^https?://#i', $bishopCtaLinkRaw)
  ? $bishopCtaLinkRaw
  : rgcUrl($bishopCtaLinkRaw !== '' ? $bishopCtaLinkRaw : 'about.php');
$experienceImage = $galleryPool[1]['image'] ?? $heroBackground;
$eventBackdrop = $galleryPool[2]['image'] ?? $heroBackground;
$chatMessages = rgcFetchCurrentVisitorMessages(6);

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
    <?php if (!empty($chatMessages)): ?>
      <div class="chat-panel__history" aria-label="Your recent messages">
        <p class="chat-panel__eyebrow">Your recent messages</p>
        <div class="chat-panel__history-list">
          <?php foreach ($chatMessages as $chatMessage): ?>
            <article class="chat-bubble chat-bubble--<?php echo htmlspecialchars((string) ($chatMessage['type'] ?? 'chat')); ?>">
              <p><?php echo nl2br(htmlspecialchars((string) ($chatMessage['message'] ?? ''))); ?></p>
              <span><?php echo htmlspecialchars(date('M j, g:i a', strtotime((string) ($chatMessage['created_at'] ?? 'now')))); ?></span>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
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

<section class="sanctuary-stage sanctuary-stage--immersive scroll-reveal" style="--hero-image: url('<?php echo htmlspecialchars($heroBackground); ?>'); --reveal-delay: 0.02s;">
  <div class="hero-slider-progress" aria-hidden="true">
    <span id="heroSliderProgressBar" class="hero-slider-progress__bar"></span>
  </div>
  <div id="heroSlider" class="sanctuary-stage__slider">
    <?php foreach ($slider as $idx => $slide): ?>
      <?php
        $slidePoolItem = $galleryPool[$idx % count($galleryPool)] ?? ['image' => $bishopImage];
        $slideImg = !empty($slide['image']) ? $slide['image'] : ($slidePoolItem['image'] ?? $bishopImage);
        $slideLinkOne = trim((string) ($slide['button1_link'] ?? ''));
        $slideLinkTwo = trim((string) ($slide['button2_link'] ?? ''));
        $slideTone = trim((string) ($slide['background_style'] ?? 'brand'));
      ?>
      <article class="hero-slide-item <?php echo $idx === 0 ? 'active' : ''; ?>" data-slide-index="<?php echo $idx; ?>">
        <div class="hero-slide-item__visual">
          <img
            src="<?php echo htmlspecialchars($slideImg); ?>"
            alt="<?php echo htmlspecialchars($slide['title']); ?>"
            class="hero-slide-item__image parallax-layer"
            data-parallax-speed="0.16"
            style="--parallax-scale: 1.12;">
          <div class="hero-slide-item__overlay hero-slide-item__overlay--<?php echo htmlspecialchars(in_array($slideTone, ['brand', 'slate', 'gradient'], true) ? $slideTone : 'brand'); ?>"></div>
        </div>
        <div class="hero-slide-item__shell">
          <div class="max-w-[1280px] mx-auto px-4 hero-slide-item__layout">
            <div class="hero-slide-item__copy">
              <span class="sanctuary-kicker"><?php echo htmlspecialchars($slide['subtitle'] ?? 'Welcome Home'); ?></span>
              <h1 class="hero-slide-item__title"><?php echo htmlspecialchars($slide['title'] ?? 'Redeemed Gospel Church Eldoret'); ?></h1>
              <p class="hero-slide-item__lead"><?php echo htmlspecialchars($slide['description'] ?? ($footerData['church']['mission'] ?? 'Join our community of faith as we seek to worship God, grow in His grace, and serve the people of Eldoret and beyond.')); ?></p>
              <div class="hero-slide-item__actions">
                <?php if ($slideLinkOne !== ''): ?>
                <a href="<?php echo htmlspecialchars($slideLinkOne); ?>" class="hero-slide-item__action hero-slide-item__action--primary">
                  <?php echo htmlspecialchars($slide['button1_text'] ?? 'Plan Your Visit'); ?>
                </a>
                <?php endif; ?>
                <?php if ($slideLinkTwo !== ''): ?>
                <a href="<?php echo htmlspecialchars($slideLinkTwo); ?>" class="hero-slide-item__action hero-slide-item__action--secondary">
                  <?php echo htmlspecialchars($slide['button2_text'] ?? 'Watch Sermons'); ?>
                </a>
                <?php endif; ?>
              </div>
              <div class="hero-slide-item__info">
                <span><?php echo htmlspecialchars(($footerData['service_times'][0]['day'] ?? 'Sunday Service') . ' · ' . ($footerData['service_times'][0]['time'] ?? '9:00 AM')); ?></span>
                <span><?php echo htmlspecialchars($footerData['contact']['address'] ?? 'Eldoret, Kenya'); ?></span>
                <?php if ($nextEvent): ?>
                <span><?php echo htmlspecialchars($nextEvent['title'] ?? 'Upcoming Event'); ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <div class="hero-slider-controls">
    <div class="hero-slider-controls__arrows">
      <button id="heroPrev" type="button" class="hero-slider-arrow" aria-label="Previous slide">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </button>
      <button id="heroNext" type="button" class="hero-slider-arrow" aria-label="Next slide">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </button>
    </div>
  </div>
</section>

<section class="mobile-home-shortcuts scroll-reveal" style="--reveal-delay: 0.08s;">
  <div class="max-w-[1280px] mx-auto px-4">
    <div class="mobile-home-shortcuts__grid">
      <a href="<?php echo rgcUrl('contact.php'); ?>" class="mobile-home-shortcuts__card scroll-reveal scroll-reveal--text" style="--reveal-delay: 0.06s;">
        <span>Visit</span>
        <strong>Plan Your Visit</strong>
      </a>
      <a href="<?php echo rgcUrl('sermons.php'); ?>" class="mobile-home-shortcuts__card scroll-reveal scroll-reveal--text" style="--reveal-delay: 0.12s;">
        <span>Watch</span>
        <strong>Latest Sermon</strong>
      </a>
      <a href="<?php echo rgcUrl('contact.php'); ?>" class="mobile-home-shortcuts__card scroll-reveal scroll-reveal--text" style="--reveal-delay: 0.18s;">
        <span>Prayer</span>
        <strong>Send Request</strong>
      </a>
      <a href="<?php echo rgcUrl('donate.php'); ?>" class="mobile-home-shortcuts__card scroll-reveal scroll-reveal--text" style="--reveal-delay: 0.24s;">
        <span>Support</span>
        <strong>Support the Mission</strong>
      </a>
    </div>
  </div>
</section>

<?php if ($nextEvent): ?>
<section class="mobile-home-event scroll-reveal" style="--reveal-delay: 0.12s;">
  <div class="max-w-[1280px] mx-auto px-4">
    <a href="<?php echo rgcUrl('events.php'); ?>" class="mobile-home-event__card scroll-reveal scroll-reveal--media" style="--reveal-delay: 0.14s;">
      <span class="mobile-home-event__eyebrow">Featured Event</span>
      <strong><?php echo htmlspecialchars($nextEvent['title'] ?? 'Upcoming Event'); ?></strong>
      <p><?php echo htmlspecialchars(date('M d, Y · g:i A', strtotime((string) ($nextEvent['event_at'] ?? 'now'))) . ' · ' . ($nextEvent['location'] ?? 'Sanctuary')); ?></p>
    </a>
  </div>
</section>
<?php endif; ?>

<section class="homepage-mobile-secondary">
  <div class="immersion-split">
    <div class="immersion-split__media parallax-scene scroll-reveal scroll-reveal--media">
      <div class="immersion-split__media-bg parallax-layer" data-parallax-speed="0.12" style="--immersive-image: url('<?php echo htmlspecialchars($experienceImage); ?>'); --parallax-scale: 1.12;"></div>
      <div class="immersion-split__media-copy">
        <span class="story-card__eyebrow">The Experience</span>
        <strong>A calm, prayerful atmosphere where people meet Jesus and feel at home.</strong>
      </div>
    </div>
    <div class="immersion-split__panel scroll-reveal scroll-reveal--text" style="--reveal-delay: 0.12s;">
      <span class="story-card__eyebrow">What To Expect</span>
      <h2>Let the image hit first, then the invitation.</h2>
      <p>From the first look to the final section, the homepage now tells a more visual story. Worship spaces, people, and church life lead the experience before the copy fills in the meaning.</p>
      <div class="immersion-split__points">
        <article>
          <strong>Come Curious</strong>
          <p>Questions, first visits, and fresh starts all belong here.</p>
        </article>
        <article>
          <strong>Bring Family</strong>
          <p>Adults, youth, and children all find a place to settle in.</p>
        </article>
        <article>
          <strong>Leave Renewed</strong>
          <p>The goal is practical hope, not religious pressure.</p>
        </article>
      </div>
    </div>
  </div>
</section>

<?php if ($nextEvent): ?>
<section class="featured-event-cover homepage-mobile-secondary parallax-scene scroll-reveal scroll-reveal--media">
  <div class="featured-event-cover__bg parallax-layer" data-parallax-speed="0.1" style="--cover-image: url('<?php echo htmlspecialchars($eventBackdrop); ?>'); --parallax-scale: 1.1;"></div>
  <div class="max-w-[1280px] mx-auto px-4 featured-event-cover__shell">
    <div class="featured-event-cover__panel scroll-reveal scroll-reveal--text" style="--reveal-delay: 0.14s;">
      <span class="story-card__eyebrow !text-brand-200">Featured Event</span>
      <h2><?php echo htmlspecialchars($nextEvent['title'] ?? 'Upcoming Event'); ?></h2>
      <p><?php echo htmlspecialchars(date('l, M d, Y', strtotime((string) ($nextEvent['event_at'] ?? 'now')))); ?> · <?php echo htmlspecialchars(date('g:i A', strtotime((string) ($nextEvent['event_at'] ?? 'now')))); ?></p>
      <p><?php echo htmlspecialchars($nextEvent['location'] ?? 'Redeemed Gospel Church Eldoret'); ?></p>
      <div class="featured-event-cover__actions">
        <a href="<?php echo rgcUrl('events.php'); ?>" class="btn btn-primary">View All Events</a>
        <a href="<?php echo rgcUrl('contact.php'); ?>" class="btn btn-outline">Ask About This Event</a>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="bishop-band scroll-reveal">
  <div class="bishop-band__left scroll-reveal scroll-reveal--text" style="--reveal-delay: 0.12s;">
    <span class="bishop-kicker"><?php echo htmlspecialchars($bishopEyebrow); ?></span>
    <blockquote class="bishop-feature__text">"<?php echo htmlspecialchars($bishopQuote); ?>"</blockquote>
    <p class="bishop-feature__author"><?php echo htmlspecialchars($bishopAuthor); ?></p>
    <p class="bishop-feature__role"><?php echo htmlspecialchars($bishopRole); ?></p>
    <div class="bishop-band__actions">
      <a href="<?php echo htmlspecialchars($bishopCtaLink); ?>" class="btn btn-primary"><?php echo htmlspecialchars($bishopCtaText); ?></a>
      <a href="<?php echo rgcUrl('contact.php'); ?>" class="btn btn-outline">Request Prayer</a>
    </div>
  </div>
  <div class="bishop-band__right scroll-reveal scroll-reveal--media">
    <img src="<?php echo htmlspecialchars($bishopImage); ?>" class="bishop-feature__photo parallax-layer" data-parallax-speed="0.15" style="--parallax-scale: 1.08;" alt="<?php echo htmlspecialchars($bishopAuthor); ?>">
  </div>
</section>

<section class="section-padding bg-white scroll-reveal" style="--reveal-delay: 0.08s;">
  <div class="max-w-[1280px] mx-auto px-4">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-16">
      <div class="max-w-2xl scroll-reveal scroll-reveal--text" style="--reveal-delay: 0.08s;">
        <span class="story-card__eyebrow">The Messages</span>
        <h2 class="text-4xl md:text-6xl font-display font-bold mt-4 leading-tight">Truth for Monday.</h2>
        <p class="text-lg text-slate-500 mt-4">Experience teachings that bridge the gap between biblical truth and daily life.</p>
      </div>
      <a href="<?php echo rgcUrl('sermons.php'); ?>" class="btn btn-primary h-fit homepage-mobile-secondary scroll-reveal scroll-reveal--text" style="--reveal-delay: 0.14s;">Watch All Sermons</a>
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
      <a href="<?php echo htmlspecialchars(rgcUrl('sermons.php?id=' . (int) ($leadSermon['id'] ?? 0))); ?>" class="sermon-showcase__lead group scroll-reveal scroll-reveal--media" style="--reveal-delay: 0.12s;">
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
        <?php foreach ($secondarySermons as $secondaryIndex => $sermon): ?>
        <?php
          $sermonDate = (string) ($sermon['scheduled_at'] ?? $sermon['created_at'] ?? '');
          $sermonThumb = getHomeYoutubeThumbnail((string) ($sermon['youtube_url'] ?? ''));
          if ($sermonThumb === '') {
            $sermonThumb = $heroBackground;
          }
        ?>
        <a href="<?php echo htmlspecialchars(rgcUrl('sermons.php?id=' . (int) ($sermon['id'] ?? 0))); ?>" class="sermon-stack-card group scroll-reveal scroll-reveal--text" style="--reveal-delay: <?php echo htmlspecialchars(number_format(0.18 + $secondaryIndex * 0.08, 2)); ?>s;">
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

<section class="section-padding pt-0 scroll-reveal" style="--reveal-delay: 0.08s;">
  <div class="max-w-[1280px] mx-auto px-4">
    <div class="mosaic-wall grid-cols-1 md:grid-cols-2 gap-6">
      <article class="mosaic-wall__intro homepage-mobile-secondary p-8 rounded-3xl bg-white shadow-lg border border-slate-100 scroll-reveal scroll-reveal--text" style="--reveal-delay: 0.08s;">
        <span class="story-card__eyebrow">Church Pulse</span>
        <h2 class="text-3xl md:text-4xl font-display font-bold mt-3 leading-tight">Belong in community. Serve with purpose. Carry hope into the city.</h2>
        <p class="text-lg text-slate-600 mt-4 leading-relaxed">Instead of treating church like a weekly appointment, we see it as a living rhythm of worship, formation, outreach, and practical love.</p>
      </article>

      <div class="mosaic-wall__columns">
        <article class="mosaic-panel scroll-reveal scroll-reveal--text" style="--reveal-delay: 0.14s;">
          <div class="pulse-card__header">
            <div>
              <span class="story-card__eyebrow">Ministries</span>
              <h3 class="text-2xl font-display font-bold mt-3 leading-tight">Spaces to grow.</h3>
            </div>
            <a href="<?php echo rgcUrl('ministries.php'); ?>" class="home-panel__link homepage-mobile-secondary">Explore</a>
          </div>
          <div class="mosaic-mini-list">
            <?php foreach ($homeMinistries as $index => $ministry): ?>
            <article class="mosaic-mini-list__item <?php echo $index > 1 ? 'homepage-mobile-secondary' : ''; ?> scroll-reveal scroll-reveal--text" style="--reveal-delay: <?php echo htmlspecialchars(number_format(0.18 + $index * 0.06, 2)); ?>s;">
              <strong><?php echo htmlspecialchars($ministry['name'] ?? 'Ministry'); ?></strong>
              <p><?php echo htmlspecialchars($ministry['description'] ?? ''); ?></p>
            </article>
            <?php endforeach; ?>
          </div>
        </article>

        <article class="mosaic-panel mosaic-panel--accent homepage-mobile-secondary scroll-reveal scroll-reveal--text" style="--reveal-delay: 0.2s;">
          <div class="pulse-card__header">
            <div>
              <span class="story-card__eyebrow">Impact</span>
              <h3 class="text-2xl font-display font-bold mt-3 leading-tight">Love made visible.</h3>
            </div>
            <a href="<?php echo rgcUrl('projects.php'); ?>" class="home-panel__link">See projects</a>
          </div>
          <div class="mosaic-mini-list">
            <?php foreach ($homeProjects as $projectIndex => $project): ?>
            <article class="mosaic-mini-list__item home-project-card scroll-reveal scroll-reveal--text" style="--reveal-delay: <?php echo htmlspecialchars(number_format(0.22 + $projectIndex * 0.06, 2)); ?>s;">
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
<section class="section-padding pt-0 homepage-mobile-secondary scroll-reveal" style="--reveal-delay: 0.08s;">
  <div class="max-w-[1280px] mx-auto px-4">
    <div class="echo-strip grid-cols-1 md:grid-cols-2 gap-6">
      <div class="echo-strip__intro p-8 rounded-3xl bg-white shadow-lg border border-slate-100 scroll-reveal scroll-reveal--text" style="--reveal-delay: 0.08s;">
        <span class="story-card__eyebrow">Voices Of Grace</span>
        <h2 class="text-3xl md:text-4xl font-display font-bold mt-3 leading-tight">Stories that sound like freedom.</h2>
      </div> 
      <div class="echo-strip__grid">
        <?php foreach ($homeTestimonials as $testimonialIndex => $testimonial): ?>
        <blockquote class="echo-card scroll-reveal scroll-reveal--text" style="--reveal-delay: <?php echo htmlspecialchars(number_format(0.16 + $testimonialIndex * 0.08, 2)); ?>s;">
          <p>"<?php echo htmlspecialchars($testimonial['message'] ?? ''); ?>"</p>
          <cite>— <?php echo htmlspecialchars($testimonial['name'] ?? 'Church Member'); ?></cite>
        </blockquote>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section-padding pt-0 homepage-mobile-secondary scroll-reveal" style="--reveal-delay: 0.08s;">
  <div class="max-w-[1120px] mx-auto px-4">
    <div class="summit-cta p-10 rounded-3xl bg-white shadow-lg border border-slate-100 text-center scroll-reveal scroll-reveal--text" style="--reveal-delay: 0.1s;">
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

  const slides = Array.from(sliderRoot.querySelectorAll('.hero-slide-item'));
  const prevBtn = document.getElementById('heroPrev');
  const nextBtn = document.getElementById('heroNext');
  const progressBar = document.getElementById('heroSliderProgressBar');

  if (slides.length === 0) return;

  let current = 0;
  const total = slides.length;
  let autoAdvance = null;
  let motionToken = 0;

  function finishState(index) {
    slides.forEach((slide, i) => {
      slide.classList.toggle('active', i === index);
    });
  }

  function animateVisual(node, frames, duration) {
    if (!node || typeof node.animate !== 'function') return null;
    return node.animate(frames, {
      duration,
      easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
      fill: 'forwards',
    });
  }

  function showSlide(index, direction = 'next', immediate = false) {
    if (index === current && !immediate) return;

    const previous = slides[current];
    const next = slides[index];
    const goingForward = direction === 'next';

    motionToken += 1;
    const token = motionToken;

    if (immediate || !previous || previous === next) {
      finishState(index);
      current = index;
      return;
    }

    slides.forEach((slide) => {
      if (slide !== previous && slide !== next) {
        slide.classList.remove('active');
      }
      if (typeof slide.getAnimations === 'function') {
        slide.getAnimations().forEach((animation) => animation.cancel());
      }
    });

    previous.classList.add('active');
    next.classList.add('active');

    const previousVisual = previous.querySelector('.hero-slide-item__visual');
    const nextVisual = next.querySelector('.hero-slide-item__visual');
    const previousCopy = previous.querySelector('.hero-slide-item__copy');
    const nextCopy = next.querySelector('.hero-slide-item__copy');

    const enterFrom = goingForward ? ['100%', '0'] : ['0', '100%'];
    const leaveTo = goingForward ? ['0', '100%'] : ['100%', '0'];

    animateVisual(nextVisual, [
      { clipPath: `inset(0 ${enterFrom[0]} 0 ${enterFrom[1]})`, transform: 'scale(1.08)' },
      { clipPath: 'inset(0 0 0 0)', transform: 'scale(1)' }
    ], 900);

    animateVisual(previousVisual, [
      { clipPath: 'inset(0 0 0 0)', transform: 'scale(1)' },
      { clipPath: `inset(0 ${leaveTo[0]} 0 ${leaveTo[1]})`, transform: 'scale(1.04)' }
    ], 820);

    animateVisual(nextCopy, [
      { opacity: 0, transform: `translate3d(${goingForward ? '38px' : '-38px'}, 34px, 0)` },
      { opacity: 1, transform: 'translate3d(0, 0, 0)' }
    ], 760);

    animateVisual(previousCopy, [
      { opacity: 1, transform: 'translate3d(0, 0, 0)' },
      { opacity: 0, transform: `translate3d(${goingForward ? '-26px' : '26px'}, -14px, 0)` }
    ], 520);

    window.setTimeout(() => {
      if (token !== motionToken) return;
      current = index;
      finishState(index);
    }, 920);
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
    showSlide((current + 1) % total, 'next');
    restartTimer();
  }

  function prevSlide() {
    showSlide((current - 1 + total) % total, 'prev');
    restartTimer();
  }

  if (prevBtn) prevBtn.addEventListener('click', prevSlide);
  if (nextBtn) nextBtn.addEventListener('click', nextSlide);

  showSlide(0, 'next', true);
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
  const allowReverseMotion = !(window.matchMedia && window.matchMedia('(hover: none), (pointer: coarse)').matches);

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

<?php if (!empty($homeGallery)): ?>
<section class="gallery-canvas section-padding homepage-mobile-secondary">
  <div class="max-w-[1280px] mx-auto px-4">
    <div class="text-center max-w-3xl mx-auto mb-16 scroll-reveal scroll-reveal--text">
      <span class="story-card__eyebrow !text-brand-200">Life In The House</span>
      <h2 class="text-4xl md:text-6xl font-display font-bold mt-6 leading-tight text-white">A gallery that moves with the story.</h2>
      <p class="text-lg text-slate-200 mt-6">As people scroll, the images rise into view first, then the captions complete the moment. It feels more like walking through church life than reading another grid of cards.</p>
    </div>
    <div class="gallery-river">
      <?php foreach ($homeGallery as $index => $item): ?>
      <article class="gallery-river__card gallery-item scroll-reveal scroll-reveal--media" style="--depth: <?php echo (string) (($index % 3) + 1); ?>; --reveal-delay: <?php echo htmlspecialchars(number_format($index * 0.08, 2)); ?>s;">
        <div class="gallery-river__media parallax-scene">
          <img
            src="<?php echo htmlspecialchars($item['image']); ?>"
            alt="<?php echo htmlspecialchars($item['caption']); ?>"
            class="gallery-river__img gallery-trigger parallax-layer"
            data-parallax-speed="<?php echo htmlspecialchars(number_format(0.1 + (($index % 3) * 0.03), 2, '.', '')); ?>"
            style="--parallax-scale: 1.12;"
            tabindex="0"
            role="button"
            data-gallery-index="<?php echo $index; ?>"
            data-gallery-caption="<?php echo htmlspecialchars($item['caption']); ?>"
            data-gallery-src="<?php echo htmlspecialchars($item['image']); ?>"
          >
        </div>
        <div class="gallery-river__meta"><?php echo htmlspecialchars($item['caption'] ?: 'Redeemed Gospel Church'); ?></div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
