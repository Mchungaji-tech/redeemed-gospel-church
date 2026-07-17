<?php
// Helper function to get YouTube embed URL from various YouTube URL formats
function getYoutubeEmbedUrl($url) {
    if (empty($url)) return '';
    
    $videoId = '';
    
    // Handle different YouTube URL formats - more comprehensive regex
    if (preg_match('/youtube\.com\/watch\?.*[?&]v=([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        $videoId = $matches[1];
    } elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        $videoId = $matches[1];
    } elseif (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        $videoId = $matches[1];
    } elseif (preg_match('/youtube\.com\/v\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        $videoId = $matches[1];
    } elseif (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
        $videoId = $url;
    }
    
    if ($videoId) {
        // Don't use autoplay - let user click to play
        return "https://www.youtube.com/embed/" . $videoId . "?rel=0&modestbranding=1";
    }
    
    return '';
}

// Helper function to get Facebook embed URL
function getFacebookEmbedUrl($url) {
    if (empty($url)) return '';
    
    // If it's already an embed URL, return it as-is
    if (strpos($url, 'facebook.com/plugins/video') !== false) {
        return $url;
    }
    
    // Try to extract video ID from various Facebook URL formats
    if (preg_match('/facebook\.com\/([a-zA-Z0-9.]+)\/videos\/([0-9]+)/', $url, $matches)) {
        return "https://www.facebook.com/plugins/video.php?height=360&href=" . urlencode($url) . "&show_text=false&width=640&t=0";
    }
    
    return $url;
}

// Get video embed URL based on platform
function getVideoEmbedUrl($sermon) {
    // Check for YouTube first - handle both DB and JSON formats
    $ytUrl = $sermon['youtube_url'] ?? '';
    if (!empty($ytUrl)) {
        return getYoutubeEmbedUrl($ytUrl);
    }
    
    // Fall back to Facebook
    $fbUrl = $sermon['facebook_embed'] ?? '';
    if (!empty($fbUrl)) {
        return getFacebookEmbedUrl($fbUrl);
    }
    
    return '';
}

require __DIR__ . '/includes/app.php';
rgcEnforcePublicAccess();

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

// Only use JSON if DB returned nothing
if (empty($sermons)) {
    $sermonsJson = @file_get_contents(__DIR__ . '/data/sermons.json');
    $sermons = $sermonsJson ? json_decode($sermonsJson, true) : [];
    if (!is_array($sermons)) { $sermons = []; }
}

$selectedId = isset($_GET['id']) ? (int)$_GET['id'] : (int)($sermons[0]['id'] ?? 0);
$selected = null;
foreach ($sermons as $s) {
  if ((int)$s['id'] === $selectedId) {
    $selected = $s;
    break;
  }
}
if (!$selected && !empty($sermons)) {
  $selected = $sermons[0];
}

// Load comments for the selected sermon from database (priority) or JSON fallback
$comments = [];
$loadedFromDb = false;
if ($selected && rgcDbAvailable()) {
  try {
    $stmt = rgcDb()->prepare('
      SELECT c.id, c.sermon_id, c.user_id, c.name, c.comment, c.created_at, c.is_blocked,
             c.report_count, c.is_flagged,
             pu.full_name
      FROM comments c
      LEFT JOIN public_users pu ON c.user_id = pu.id
      WHERE c.is_blocked = 0 AND c.sermon_id = :sermon_id
      ORDER BY c.created_at ASC
    ');
    $stmt->execute([':sermon_id' => (int)$selected['id']]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $loadedFromDb = true;
  } catch (Throwable $e) {
    $comments = [];
  }
}

if (!$loadedFromDb && $selected) {
  $commentsJson = @file_get_contents(__DIR__ . '/data/comments.json');
  $allComments = $commentsJson ? json_decode($commentsJson, true) : [];
  if (!is_array($allComments)) { $allComments = []; }

  $needsSave = false;
  $filtered = [];
  foreach ($allComments as $idx => $c) {
    if ((int)($c['sermon_id'] ?? 0) !== (int)$selected['id']) { continue; }

    if (!isset($c['id']) || (int)$c['id'] <= 0) {
      $c['id'] = (int)abs(crc32(($c['sermon_id'] ?? '') . '|' . ($c['created_at'] ?? '') . '|' . ($c['name'] ?? '') . '|' . ($c['comment'] ?? '') . '|' . $idx));
      $needsSave = true;
    }
    if (!isset($c['report_count'])) { $c['report_count'] = 0; $needsSave = true; }
    if (!isset($c['is_flagged'])) { $c['is_flagged'] = 0; $needsSave = true; }
    if (!isset($c['is_blocked'])) { $c['is_blocked'] = 0; $needsSave = true; }
    if ((int)$c['is_blocked'] === 1) { continue; }

    $filtered[] = $c;
  }

  usort($filtered, function ($a, $b) {
    return strtotime($a['created_at'] ?? '') <=> strtotime($b['created_at'] ?? '');
  });

  $comments = $filtered;

  if ($needsSave) {
    foreach ($allComments as $idx => $c) {
      if (!isset($c['id']) || (int)$c['id'] <= 0) {
        $allComments[$idx]['id'] = (int)abs(crc32(($c['sermon_id'] ?? '') . '|' . ($c['created_at'] ?? '') . '|' . ($c['name'] ?? '') . '|' . ($c['comment'] ?? '') . '|' . $idx));
      }
      if (!isset($allComments[$idx]['report_count'])) { $allComments[$idx]['report_count'] = 0; }
      if (!isset($allComments[$idx]['is_flagged'])) { $allComments[$idx]['is_flagged'] = 0; }
      if (!isset($allComments[$idx]['is_blocked'])) { $allComments[$idx]['is_blocked'] = 0; }
    }
    rgcSaveJson('comments.json', $allComments);
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'post_comment';

  if ($action === 'report_comment') {
    rgcRequireCsrf('report_sermon_comment');

    $commentId = (int)($_POST['comment_id'] ?? 0);
    $sermonId = (int)($_POST['sermon_id'] ?? 0);

    if ($commentId > 0 && $sermonId > 0) {
      if (rgcDbAvailable()) {
        try {
          $stmt = rgcDb()->prepare('UPDATE comments SET report_count = report_count + 1, is_flagged = 1 WHERE id = ? AND sermon_id = ?');
          $stmt->execute([$commentId, $sermonId]);
        } catch (Throwable $e) {
        }
      } else {
        $commentsJson = @file_get_contents(__DIR__ . '/data/comments.json');
        $jsonComments = $commentsJson ? json_decode($commentsJson, true) : [];
        if (!is_array($jsonComments)) { $jsonComments = []; }
        $changed = false;
        foreach ($jsonComments as $i => $c) {
          if ((int)($c['id'] ?? 0) === $commentId && (int)($c['sermon_id'] ?? 0) === $sermonId) {
            $jsonComments[$i]['report_count'] = (int)($c['report_count'] ?? 0) + 1;
            $jsonComments[$i]['is_flagged'] = 1;
            $changed = true;
            break;
          }
        }
        if ($changed) {
          rgcSaveJson('comments.json', $jsonComments);
        }
      }
    }

    header('Location: ' . rgcUrl('sermons.php?id=' . $sermonId . '#comments-section'));
    exit;
  }

  rgcRequireCsrf('public_sermon_comment');

  if (!rgcPublicUser()) {
    rgcPublicRequireLogin('sermons.php?id=' . (int) ($_POST['sermon_id'] ?? $selectedId) . '#comments-section');
  }

  $currentUser = rgcPublicUser();
  $comment = trim($_POST['comment'] ?? '');
  $sermonId = (int)($_POST['sermon_id'] ?? 0);

  if ($comment !== '' && $sermonId > 0) {
    $comment = function_exists('mb_substr') ? mb_substr($comment, 0, 2000) : substr($comment, 0, 2000);

    if (rgcDbAvailable()) {
      try {
        $stmt = rgcDb()->prepare('
          INSERT INTO comments (sermon_id, user_id, name, comment, created_at, is_blocked, report_count, is_flagged)
          VALUES (:sermon_id, :user_id, :name, :comment, NOW(), 0, 0, 0)
        ');
        $stmt->execute([
          ':sermon_id' => $sermonId,
          ':user_id' => $currentUser['id'] ?? null,
          ':name' => $currentUser['full_name'] ?? 'User',
          ':comment' => $comment
        ]);
      } catch (Throwable $e) {
        $commentsJson = @file_get_contents(__DIR__ . '/data/comments.json');
        $jsonComments = $commentsJson ? json_decode($commentsJson, true) : [];
        if (!is_array($jsonComments)) { $jsonComments = []; }
        $jsonComments[] = [
          'id' => (int)(microtime(true) * 1000) + random_int(0, 999),
          'sermon_id' => $sermonId,
          'user_id' => $currentUser['id'] ?? null,
          'name' => $currentUser['full_name'] ?? 'User',
          'comment' => $comment,
          'created_at' => date('Y-m-d H:i:s'),
          'is_blocked' => 0,
          'report_count' => 0,
          'is_flagged' => 0
        ];
        rgcSaveJson('comments.json', $jsonComments);
      }
    } else {
      $commentsJson = @file_get_contents(__DIR__ . '/data/comments.json');
      $jsonComments = $commentsJson ? json_decode($commentsJson, true) : [];
      if (!is_array($jsonComments)) { $jsonComments = []; }
      $jsonComments[] = [
        'id' => (int)(microtime(true) * 1000) + random_int(0, 999),
        'sermon_id' => $sermonId,
        'user_id' => $currentUser['id'] ?? null,
        'name' => $currentUser['full_name'] ?? 'User',
        'comment' => $comment,
        'created_at' => date('Y-m-d H:i:s'),
        'is_blocked' => 0,
        'report_count' => 0,
        'is_flagged' => 0
      ];
      rgcSaveJson('comments.json', $jsonComments);
    }

    header('Location: ' . rgcUrl('sermons.php?id=' . $sermonId . '#comments-section'));
    exit;
  }
}

$pageTitle = 'Sermons | Redeemed Gospel Church Eldoret';
require __DIR__ . '/includes/header.php';
?>

<section class="sanctuary-stage" style="--hero-image: url('https://images.unsplash.com/photo-1510511233900-1982d92bd835?q=80&w=2000&auto=format&fit=crop');">
  <div class="max-w-[1280px] mx-auto px-4 pt-10 pb-14 md:pt-16 md:pb-24">
    <div class="sanctuary-stage__grid">
      <div class="sanctuary-copy">
        <span class="sanctuary-kicker">Experience the Word</span>
        <h1 class="sanctuary-copy__title">Voice of Hope. Truth for Life.</h1>
        <p class="sanctuary-copy__lead">Grow your faith through biblically-centered teachings and messages of redemption. Explore our library of life-changing sermons.</p>
        <div class="sanctuary-copy__actions">
          <a href="#browse-sermons" class="btn btn-primary">Start Watching</a>
          <a href="<?php echo rgcUrl('contact.php'); ?>" class="btn btn-outline">Ask for Prayer</a>
        </div>
        <div class="sanctuary-copy__meta grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <span>Latest Message</span>
            <strong><?php echo htmlspecialchars($sermons[0]['title'] ?? 'Word of Life'); ?></strong>
          </div>
          <div>
            <span>Preacher</span>
            <strong><?php echo htmlspecialchars($sermons[0]['speaker'] ?? 'Bishop Omondi'); ?></strong>
          </div>
          <div>
            <span>Global Impact</span>
            <strong>Sharing the Word across nations.</strong>
          </div>
        </div>
      </div>

      <div class="sanctuary-orbit">
        <div class="relative rounded-[2rem] overflow-hidden shadow-2xl border border-white/10 bg-slate-900/40 backdrop-blur-md">
          <div id="sermonSlides" class="flex transition-transform duration-500 ease-out">
            <?php foreach (array_slice($sermons, 0, 3) as $slide): ?>
              <?php 
                $hasYoutube = !empty($slide['youtube_url']);
                $videoId = '';
                if ($hasYoutube && preg_match('/v=([a-zA-Z0-9_-]{11})/', $slide['youtube_url'], $matches)) {
                  $videoId = $matches[1];
                }
                $slideThumb = $videoId ? "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg" : "";
              ?>
              <a href="<?php echo htmlspecialchars(rgcUrl('sermons.php?id=' . (int) $slide['id'])); ?>" class="min-w-full block relative group aspect-video md:aspect-[16/10]">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent z-10"></div>
                <?php if ($slideThumb): ?>
                  <img src="<?php echo htmlspecialchars($slideThumb); ?>" alt="" class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:scale-105 transition-transform duration-700">
                <?php endif; ?>
                
                <div class="absolute inset-0 flex items-center justify-center z-20">
                  <div class="w-16 h-16 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/30 group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                  </div>
                </div>

                <div class="absolute bottom-0 left-0 right-0 p-6 md:p-8 z-30">
                  <div class="flex items-center gap-2 mb-2">
                    <?php if (!empty($slide['is_live'])): ?>
                      <span class="live-badge !bg-rose-600 !border-rose-400">LIVE</span>
                    <?php endif; ?>
                    <span class="text-[0.65rem] uppercase tracking-widest text-brand-200 font-bold">Featured Sermon</span>
                  </div>
                  <h2 class="text-xl md:text-2xl font-bold text-white line-clamp-1"><?php echo htmlspecialchars($slide['title']); ?></h2>
                  <p class="text-slate-300 text-sm mt-1"><?php echo htmlspecialchars($slide['speaker']); ?></p>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
          
          <div class="absolute bottom-4 right-6 z-40 flex gap-2">
            <button id="prevSlide" class="w-8 h-8 rounded-full bg-white/10 border border-white/20 flex items-center justify-center hover:bg-white/20 transition-colors">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button id="nextSlide" class="w-8 h-8 rounded-full bg-white/10 border border-white/20 flex items-center justify-center hover:bg-white/20 transition-colors">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section-padding bg-slate-50 scroll-mt-10" id="browse-sermons">
  <div class="max-w-6xl mx-auto px-4">
    <div class="grid gap-6 md:grid-cols-[minmax(0,1.6fr)_minmax(280px,0.95fr)] md:items-start">
      <div class="space-y-8">
    <!-- Stacking the video and library into separate sections as requested -->
    <div class="flex flex-col gap-16">
      <!-- Featured Session -->
      <div id="featured-player">
        <?php if ($selected): ?>
        <article class="pulse-card !p-0 overflow-hidden rounded-[3rem] shadow-2xl border-none">
          <div class="aspect-video bg-slate-900 relative overflow-hidden">
            <?php 
            $embedUrl = getVideoEmbedUrl($selected);
            $isYoutube = !empty($selected['youtube_url']);
            ?>
            <?php if ($embedUrl): ?>
              <iframe 
                src="<?php echo htmlspecialchars($embedUrl); ?>" 
                class="w-full h-full"
                id="sermonIframe"
                style="border:none;" 
                allowfullscreen="true" 
                allow="autoplay; encrypted-media; picture-in-picture; web-share">
              </iframe>
            <?php else: ?>
              <div class="absolute inset-0 flex flex-col items-center justify-center text-white/50">
                <svg class="w-16 h-16 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <p class="text-lg font-medium">Video Content Unavailable</p>
              </div>
            <?php endif; ?>
          </div>
          
          <div class="p-10 md:p-16 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
              <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded-full bg-brand-100 text-brand-700 text-xs font-bold uppercase tracking-wider">
                  <?php echo $isYoutube ? 'YouTube Session' : 'Direct Link'; ?>
                </span>
                <?php if (!empty($selected['is_live'])): ?>
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 text-rose-600 text-xs font-bold border border-rose-100 animate-pulse">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                    LIVE NOW
                  </span>
                <?php endif; ?>
              </div>
              <div class="flex gap-2">
                <button onclick="window.print()" class="w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                </button>
                <button class="w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                </button>
              </div>
            </div>
            
            <h2 class="font-display text-3xl md:text-4xl font-bold text-slate-900 mb-4"><?php echo htmlspecialchars($selected['title']); ?></h2>
            
            <div class="flex items-center gap-6 py-6 border-y border-slate-100 mb-6">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 font-bold">
                  <?php echo strtoupper(substr($selected['speaker'], 0, 1)); ?>
                </div>
                <div>
                  <p class="text-[0.65rem] uppercase tracking-widest text-slate-400 font-bold">Speaker</p>
                  <p class="font-bold text-slate-800"><?php echo htmlspecialchars($selected['speaker']); ?></p>
                </div>
              </div>
              <div class="h-10 w-px bg-slate-100"></div>
              <div>
                <p class="text-[0.65rem] uppercase tracking-widest text-slate-400 font-bold">Shared On</p>
                <p class="font-medium text-slate-800"><?php echo date('F j, Y', strtotime($selected['scheduled_at'] ?? $selected['created_at'])); ?></p>
              </div>
            </div>

            <div class="prose prose-slate max-w-none text-slate-600 leading-relaxed">
              <?php echo !empty($selected['description']) ? nl2br(htmlspecialchars($selected['description'])) : 'Join us as we explore the deeper truths of God\'s Word. This message was shared to encourage, inspire, and equip you for your spiritual journey.'; ?>
            </div>
          </div>
        </article>

        <section class="pulse-card p-6 md:p-10 mt-6 animate-fade-in-up delay-100" id="comments-section">
          <h3 class="font-display font-bold text-xl mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            Comments
            <span class="text-sm font-normal text-slate-400">(<?php 
              echo count($comments);
            ?>)</span>
          </h3>
          
          <?php if (rgcPublicUser()): ?>
          <form method="post" class="mb-8">
            <?php echo rgcCsrfField('public_sermon_comment'); ?>
            <input type="hidden" name="sermon_id" value="<?php echo (int)$selected['id']; ?>">
            <div class="grid gap-4">
              <textarea name="comment" class="form-input form-textarea" placeholder="Share your thoughts on this sermon..." required></textarea>
              <button type="submit" class="btn btn-primary self-start">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                Post Comment
              </button>
            </div>
          </form>
          <?php else: ?>
          <div class="p-4 mb-8 rounded-xl bg-blue-50 border border-blue-200 flex items-center gap-3">
            <svg class="w-5 h-5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="flex-1">
              <p class="text-blue-800 font-medium">Want to share your thoughts?</p>
              <p class="text-blue-700 text-sm">Please <a href="<?php echo htmlspecialchars(rgcUrl('user/login.php?r=' . urlencode(rgcUrl('sermons.php?id=' . (int) $selected['id'] . '#comments-section')))); ?>" class="font-semibold underline hover:no-underline">log in</a> to post a comment.</p>
            </div>
          </div>
          <?php endif; ?>

          <div class="grid gap-6">
            <?php $hasComments = false; ?>
            <?php foreach ($comments as $c): ?>
              <?php $hasComments = true; ?>
              <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-md transition-all group">
                <div class="flex items-start justify-between gap-3 mb-3">
                  <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="w-8 h-8 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 font-semibold text-sm flex-shrink-0">
                      <?php echo strtoupper(substr($c['full_name'] ?? $c['name'] ?? 'U', 0, 1)); ?>
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="font-semibold text-slate-800 text-sm truncate"><?php echo htmlspecialchars($c['full_name'] ?? $c['name'] ?? 'Anonymous'); ?></p>
                      <p class="text-xs text-slate-400"><?php echo !empty($c['created_at']) ? date('M j, Y \a\t g:ia', strtotime($c['created_at'])) : ''; ?></p>
                    </div>
                  </div>
                  <?php if (!empty($c['id'])): ?>
                    <form method="post" action="<?php echo htmlspecialchars(rgcUrl('sermons.php?id=' . (int) $selected['id'])); ?>" class="inline" style="display: none;" id="report-form-<?php echo (int)$c['id']; ?>">
                      <input type="hidden" name="action" value="report_comment">
                      <input type="hidden" name="sermon_id" value="<?php echo (int)$selected['id']; ?>">
                      <input type="hidden" name="comment_id" value="<?php echo (int)$c['id']; ?>">
                      <?php echo rgcCsrfField('report_sermon_comment'); ?>
                    </form>
                    <button type="button" onclick="if(confirm('Report this comment?')) document.getElementById('report-form-<?php echo (int)$c['id']; ?>').submit();"
                            class="text-slate-400 hover:text-red-600 transition-colors p-1 -m-1"
                            title="Report comment"
                            aria-label="Report this comment">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0-12a9 9 0 110 18 9 9 0 010-18zm0 4a.75.75 0 00-.75.75v2.5a.75.75 0 001.5 0V5.75A.75.75 0 0012 5z"/>
                      </svg>
                    </button>
                  <?php endif; ?>
                </div>
                <p class="text-slate-600 text-sm leading-relaxed break-words"><?php echo htmlspecialchars($c['comment'] ?? ''); ?></p>
                <?php if ((int)($c['report_count'] ?? 0) > 0): ?>
                  <p class="text-xs text-amber-600 mt-2 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    <?php echo (int)$c['report_count']; ?> <?php echo (int)$c['report_count'] === 1 ? 'report' : 'reports'; ?>
                  </p>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
            <?php if (!$hasComments): ?>
              <div class="text-center py-8">
                <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                  <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                  </svg>
                </div>
                <p class="text-slate-500">No comments yet. Be the first to share your thoughts!</p>
              </div>
            <?php endif; ?>
          </div>
        </section>
        <?php endif; ?>
      </div>

      <!-- Sidebar - Sermon List -->
      <aside class="md:sticky md:top-24 space-y-6">
        <div class="pulse-card p-6">
          <h3 class="font-display font-bold text-lg mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            All Sermons
          </h3>
          
          <div class="space-y-3 max-h-[72vh] overflow-y-auto pr-2 custom-scrollbar">
            <?php 
            function getYoutubeThumbnail($url) {
                if (empty($url)) return '';
                if (preg_match('/youtube\.com\/watch\?.*[?&]v=([a-zA-Z0-9_-]{11})/', $url, $matches)) {
                    return 'https://img.youtube.com/vi/' . $matches[1] . '/mqdefault.jpg';
                } elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
                    return 'https://img.youtube.com/vi/' . $matches[1] . '/mqdefault.jpg';
                } elseif (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
                    return 'https://img.youtube.com/vi/' . $matches[1] . '/mqdefault.jpg';
                }
                return '';
            }
            ?>
            <?php foreach ($sermons as $s): ?>
              <?php 
                $hasYoutube = !empty($s['youtube_url']);
                $hasVideo = $hasYoutube || !empty($s['facebook_embed']);
                $thumbUrl = getYoutubeThumbnail($s['youtube_url'] ?? '');
              ?>
              <a href="<?php echo htmlspecialchars(rgcUrl('sermons.php?id=' . (int) $s['id'])); ?>" class="block group">
                <div class="flex gap-4 p-3 rounded-2xl border-2 transition-all <?php echo ($selected && (int)$s['id'] === (int)$selected['id']) ? 'border-brand-400 bg-brand-50/50' : 'border-transparent hover:border-slate-200 hover:bg-slate-50'; ?>">
                  <?php if ($thumbUrl): ?>
                  <div class="w-20 h-14 rounded-lg overflow-hidden shrink-0 relative">
                    <img src="<?php echo htmlspecialchars($thumbUrl); ?>" alt="<?php echo htmlspecialchars($s['title']); ?>" class="w-full h-full object-cover">
                    <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                      <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                    <?php if (!empty($s['is_live'])): ?>
                      <span class="absolute top-1 right-1 bg-red-600 text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold">LIVE</span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>

                <div class="min-w-0 flex-1">
                  <h4 class="font-bold text-base text-slate-900 line-clamp-2 group-hover:text-brand-600 transition-colors"><?php echo htmlspecialchars($s['title']); ?></h4>
                  <p class="text-[10px] text-slate-400 mt-1 font-bold uppercase tracking-widest"><?php echo htmlspecialchars($s['speaker']); ?></p>
                </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
  border-radius: 2px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #d4a843;
  border-radius: 2px;
}
</style>

<script>
// Slider functionality
(function () {
  const track = document.getElementById('sermonSlides');
  const prev = document.getElementById('prevSlide');
  const next = document.getElementById('nextSlide');
  if (!track || track.children.length < 2) return;

  let idx = 0;
  const total = track.children.length;
  let autoPlay = setInterval(() => go(idx + 1), 7000);
  
  const go = (nextIdx) => {
    idx = (nextIdx + total) % total;
    track.style.transform = `translateX(-${idx * 100}%)`;
  };

  if (prev) prev.addEventListener('click', () => { clearInterval(autoPlay); go(idx - 1); });
  if (next) next.addEventListener('click', () => { clearInterval(autoPlay); go(idx + 1); });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
