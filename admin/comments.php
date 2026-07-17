<?php
require __DIR__ . '/includes/auth.php';
requireLogin();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$commentId = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : (int)($_GET['id'] ?? 0);
$sermonFilter = $_GET['sermon'] ?? '';
$statusFilter = $_GET['status'] ?? ''; // 'reported', 'blocked', 'flagged', 'all'

// Handle actions
if ($action && rgcDbAvailable()) {
  rgcRequireCsrf('admin_comment_action');
  
  if ($action === 'delete' && $commentId) {
    try {
      $stmt = rgcDb()->prepare('DELETE FROM comments WHERE id = ?');
      $stmt->execute([$commentId]);
      $message = 'Comment deleted successfully';
      $messageType = 'success';
    } catch (Throwable $e) {
      $message = 'Error deleting comment';
      $messageType = 'error';
    }
  }
  
  elseif ($action === 'block' && $commentId) {
    try {
      $reason = $_POST['block_reason'] ?? 'Blocked by admin';
      $stmt = rgcDb()->prepare('UPDATE comments SET is_blocked = 1, blocked_reason = ?, blocked_at = NOW() WHERE id = ?');
      $stmt->execute([$reason, $commentId]);
      $message = 'Comment blocked successfully';
      $messageType = 'success';
    } catch (Throwable $e) {
      $message = 'Error blocking comment';
      $messageType = 'error';
    }
  }
  
  elseif ($action === 'unblock' && $commentId) {
    try {
      $stmt = rgcDb()->prepare('UPDATE comments SET is_blocked = 0, blocked_reason = NULL, blocked_at = NULL WHERE id = ?');
      $stmt->execute([$commentId]);
      $message = 'Comment unblocked successfully';
      $messageType = 'success';
    } catch (Throwable $e) {
      $message = 'Error unblocking comment';
      $messageType = 'error';
    }
  }
  
  elseif ($action === 'report_comment' && $commentId) {
    // User reported a comment from the public site
    try {
      $stmt = rgcDb()->prepare('UPDATE comments SET report_count = report_count + 1, is_flagged = 1 WHERE id = ?');
      $stmt->execute([$commentId]);
      $message = 'Comment reported successfully';
      $messageType = 'success';
      // Redirect back to sermon page
      header('Location: ' . rgcUrl('sermons.php?id=' . ($_POST['sermon_id'] ?? 0) . '#comments-section'));
      exit;
    } catch (Throwable $e) {
      $message = 'Error reporting comment';
      $messageType = 'error';
    }
  }
  
  elseif ($action === 'flag' && $commentId) {
    try {
      $stmt = rgcDb()->prepare('UPDATE comments SET is_flagged = 1 WHERE id = ?');
      $stmt->execute([$commentId]);
      $message = 'Comment flagged for review';
      $messageType = 'success';
    } catch (Throwable $e) {
      $message = 'Error flagging comment';
      $messageType = 'error';
    }
  }
  
  elseif ($action === 'unflag' && $commentId) {
    try {
      $stmt = rgcDb()->prepare('UPDATE comments SET is_flagged = 0 WHERE id = ?');
      $stmt->execute([$commentId]);
      $message = 'Comment unflagged';
      $messageType = 'success';
    } catch (Throwable $e) {
      $message = 'Error unflagging comment';
      $messageType = 'error';
    }
  }
}

// Build query to fetch comments
$allComments = [];
if (rgcDbAvailable()) {
  try {
    $query = '
      SELECT c.id, c.sermon_id, c.user_id, c.name, c.comment, c.created_at, 
             c.is_blocked, c.is_flagged, c.report_count, c.blocked_reason, c.blocked_at,
             s.title as sermon_title,
             pu.full_name, pu.email
      FROM comments c
      LEFT JOIN sermons s ON c.sermon_id = s.id
      LEFT JOIN public_users pu ON c.user_id = pu.id
    ';
    
    $filters = [];
    if ($sermonFilter) {
      $filters[] = "c.sermon_id = " . (int)$sermonFilter;
    }
    
    if ($statusFilter === 'reported') {
      $filters[] = "c.report_count > 0";
    } elseif ($statusFilter === 'blocked') {
      $filters[] = "c.is_blocked = 1";
    } elseif ($statusFilter === 'flagged') {
      $filters[] = "c.is_flagged = 1";
    }
    
    if (!empty($filters)) {
      $query .= ' WHERE ' . implode(' AND ', $filters);
    }
    
    $query .= ' ORDER BY c.created_at DESC';
    
    $stmt = rgcDb()->query($query);
    $allComments = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    $allComments = [];
  }
}

// Get list of sermons for filter
$sermons = [];
if (rgcDbAvailable()) {
  try {
    $stmt = rgcDb()->query('SELECT id, title FROM sermons ORDER BY title ASC');
    $sermons = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    $sermons = [];
  }
}

// Count stats
$stats = [
  'total' => count($allComments),
  'reported' => 0,
  'blocked' => 0,
  'flagged' => 0
];

foreach ($allComments as $c) {
  if ($c['report_count'] > 0) $stats['reported']++;
  if ($c['is_blocked']) $stats['blocked']++;
  if ($c['is_flagged']) $stats['flagged']++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comments Management | Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .status-badge { @apply inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium; }
    .status-blocked { @apply bg-red-100 text-red-800; }
    .status-flagged { @apply bg-amber-100 text-amber-800; }
    .status-reported { @apply bg-orange-100 text-orange-800; }
  </style>
</head>
<body class="bg-slate-100 min-h-screen">
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
    <!-- Header -->
    <section class="rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-700 text-white p-6 md:p-8 shadow-xl">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Content Moderation</p>
          <h1 class="mt-2 text-3xl font-bold">Comments Management</h1>
        </div>
        <a href="<?php echo rgcUrl('admin/dashboard.php'); ?>" class="px-4 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 transition-colors">← Back</a>
      </div>
    </section>

    <!-- Messages -->
    <?php if (isset($message)): ?>
    <div class="p-4 rounded-xl <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?>">
      <div class="flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="<?php echo $messageType === 'success' ? 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' : 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z'; ?>" clip-rule="evenodd"/>
        </svg>
        <p><?php echo htmlspecialchars($message); ?></p>
      </div>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="grid md:grid-cols-4 gap-4">
      <div class="rounded-xl bg-white p-6 shadow-sm border border-slate-200">
        <p class="text-slate-500 text-sm font-medium">Total Comments</p>
        <p class="mt-2 text-3xl font-bold text-slate-900"><?php echo $stats['total']; ?></p>
      </div>
      <div class="rounded-xl bg-white p-6 shadow-sm border border-slate-200">
        <p class="text-slate-500 text-sm font-medium">Reported</p>
        <p class="mt-2 text-3xl font-bold text-orange-600"><?php echo $stats['reported']; ?></p>
      </div>
      <div class="rounded-xl bg-white p-6 shadow-sm border border-slate-200">
        <p class="text-slate-500 text-sm font-medium">Blocked</p>
        <p class="mt-2 text-3xl font-bold text-red-600"><?php echo $stats['blocked']; ?></p>
      </div>
      <div class="rounded-xl bg-white p-6 shadow-sm border border-slate-200">
        <p class="text-slate-500 text-sm font-medium">Flagged</p>
        <p class="mt-2 text-3xl font-bold text-amber-600"><?php echo $stats['flagged']; ?></p>
      </div>
    </div>

    <!-- Filters -->
    <div class="rounded-xl bg-white p-6 shadow-sm border border-slate-200">
      <form method="get" class="flex flex-wrap gap-4 items-end">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Filter by Sermon</label>
          <select name="sermon" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
            <option value="">All Sermons</option>
            <?php foreach ($sermons as $s): ?>
            <option value="<?php echo (int)$s['id']; ?>" <?php echo $sermonFilter == $s['id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($s['title']); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Filter by Status</label>
          <select name="status" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
            <option value="">All Comments</option>
            <option value="reported" <?php echo $statusFilter === 'reported' ? 'selected' : ''; ?>>Reported</option>
            <option value="blocked" <?php echo $statusFilter === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
            <option value="flagged" <?php echo $statusFilter === 'flagged' ? 'selected' : ''; ?>>Flagged</option>
          </select>
        </div>
        
        <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-sm font-medium transition-colors">
          Apply Filters
        </button>
        
        <?php if ($sermonFilter || $statusFilter): ?>
        <a href="<?php echo rgcUrl('admin/comments.php'); ?>" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-900 rounded-lg text-sm font-medium transition-colors">
          Clear Filters
        </a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Comments Table -->
    <div class="rounded-xl bg-white shadow-sm border border-slate-200 overflow-hidden">
      <?php if (empty($allComments)): ?>
      <div class="p-8 text-center text-slate-500">
        <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
        <p class="font-medium">No comments found</p>
      </div>
      <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Comment</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Author</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Sermon</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Date</th>
              <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <?php foreach ($allComments as $comment): ?>
            <tr class="hover:bg-slate-50 transition-colors <?php echo $comment['is_blocked'] ? 'opacity-60' : ''; ?>">
              <td class="px-6 py-4">
                <div class="max-w-xs">
                  <p class="text-sm text-slate-900 line-clamp-2"><?php echo htmlspecialchars($comment['comment']); ?></p>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm">
                  <p class="font-medium text-slate-900"><?php echo htmlspecialchars($comment['full_name'] ?? $comment['name'] ?? 'Anonymous'); ?></p>
                  <?php if ($comment['email']): ?>
                  <p class="text-xs text-slate-500"><?php echo htmlspecialchars($comment['email']); ?></p>
                  <?php endif; ?>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm">
                  <p class="text-slate-900 font-medium"><?php echo htmlspecialchars($comment['sermon_title'] ?? 'Unknown'); ?></p>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-wrap gap-2">
                  <?php if ($comment['is_blocked']): ?>
                  <span class="status-badge status-blocked">Blocked</span>
                  <?php endif; ?>
                  <?php if ($comment['report_count'] > 0): ?>
                  <span class="status-badge status-reported"><?php echo (int)$comment['report_count']; ?> report<?php echo (int)$comment['report_count'] > 1 ? 's' : ''; ?></span>
                  <?php endif; ?>
                  <?php if ($comment['is_flagged']): ?>
                  <span class="status-badge status-flagged">Flagged</span>
                  <?php endif; ?>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-slate-500">
                <?php echo date('M j, Y', strtotime($comment['created_at'])); ?>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                  <form method="post" style="display: inline;">
                    <?php echo rgcCsrfField('admin_comment_action'); ?>
                    <input type="hidden" name="action" value="<?php echo $comment['is_blocked'] ? 'unblock' : 'block'; ?>">
                    <input type="hidden" name="comment_id" value="<?php echo (int)$comment['id']; ?>">
                    <button type="submit" class="px-3 py-1 text-xs rounded <?php echo $comment['is_blocked'] ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200'; ?> transition-colors">
                      <?php echo $comment['is_blocked'] ? 'Unblock' : 'Block'; ?>
                    </button>
                  </form>
                  
                  <form method="post" onsubmit="return confirm('Delete this comment?');" style="display: inline;">
                    <?php echo rgcCsrfField('admin_comment_action'); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="comment_id" value="<?php echo (int)$comment['id']; ?>">
                    <button type="submit" class="px-3 py-1 text-xs rounded bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                      Delete
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
