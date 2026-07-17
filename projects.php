<?php
require __DIR__ . '/includes/app.php';
rgcEnforcePublicAccess();

$pageTitle = 'Projects | Redeemed Gospel Church Eldoret';
$projects = rgcLoadJson('projects.json', []);

require __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-pattern relative overflow-hidden bg-gradient-to-br from-brand-900 via-brand-800 to-brand-900 text-white">
  <div class="absolute inset-0 overflow-hidden">
    <div class="absolute top-0 right-0 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-96 h-96 bg-brand-600/10 rounded-full blur-3xl"></div>
  </div>
  
  <div class="max-w-4xl mx-auto px-4 py-16 relative z-10 text-center">
    <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-brand-500/25 border border-brand-300/60 text-white text-sm font-semibold mb-6">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
      </svg>
      Our Projects
    </span>
    
    <h1 class="font-display text-3xl md:text-5xl font-bold mb-4 text-white drop-shadow-[0_3px_12px_rgba(0,0,0,0.45)]">Building for God</h1>
    <p class="text-white text-lg font-semibold max-w-2xl mx-auto bg-black/40 px-4 py-2 rounded-xl drop-shadow-[0_2px_10px_rgba(0,0,0,0.45)]">Transforming lives through community development and ministry expansion.</p>
  </div>
</section>

<!-- Projects Grid -->
<section class="section-padding bg-slate-50">
  <div class="max-w-6xl mx-auto px-4">
    
    <?php if (empty($projects)): ?>
    <div class="text-center py-12">
      <div class="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
      </div>
      <p class="text-slate-500">No projects to display yet.</p>
    </div>
    <?php else: ?>
    <div class="space-y-16">
      <?php foreach ($projects as $index => $project): 
        // Get icon SVG based on icon name
        $iconName = $project['icon'] ?? 'heart';
        $iconSvg = getProjectIcon($iconName);
        
        // Get status badge
        $status = $project['status'] ?? 'active';
        $statusLabel = $status === 'ongoing' ? 'In Progress' : 'Active';
        $statusClass = $status === 'ongoing' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700';
        
        // Alternate layout direction
        $isReversed = $index % 2 === 1;
      ?>
      <article class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="grid md:grid-cols-2 gap-0">
          <!-- Images Section -->
          <div class="<?php echo $isReversed ? 'md:order-2' : ''; ?> p-6 md:p-8 bg-slate-50">
            <div class="grid grid-cols-2 gap-4 h-full">
              <!-- First Image -->
              <div class="relative rounded-xl overflow-hidden bg-slate-200 min-h-[200px]">
                <?php if (!empty($project['image'])): ?>
                  <img src="<?php echo htmlspecialchars($project['image']); ?>" 
                       alt="<?php echo htmlspecialchars($project['title']); ?> - Image 1"
                       class="absolute inset-0 w-full h-full object-cover">
                <?php else: ?>
                  <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-16 h-16 rounded-2xl bg-brand-100 flex items-center justify-center">
                      <?php echo $iconSvg; ?>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
              <!-- Second Image -->
              <div class="relative rounded-xl overflow-hidden bg-slate-200 min-h-[200px]">
                <?php if (!empty($project['image2'])): ?>
                  <img src="<?php echo htmlspecialchars($project['image2']); ?>" 
                       alt="<?php echo htmlspecialchars($project['title']); ?> - Image 2"
                       class="absolute inset-0 w-full h-full object-cover">
                <?php else: ?>
                  <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-16 h-16 rounded-2xl bg-brand-100 flex items-center justify-center">
                      <?php echo $iconSvg; ?>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          
          <!-- Content Section -->
          <div class="<?php echo $isReversed ? 'md:order-1' : ''; ?> p-6 md:p-8">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-12 h-12 rounded-xl bg-brand-100 flex items-center justify-center">
                <?php echo $iconSvg; ?>
              </div>
              <div>
                <h3 class="font-display text-xl md:text-2xl font-bold text-slate-800"><?php echo htmlspecialchars($project['title']); ?></h3>
                <span class="inline-block mt-1 px-3 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                  <?php echo $statusLabel; ?>
                </span>
              </div>
            </div>
            
            <p class="text-slate-600 leading-relaxed mb-6">
              <?php echo htmlspecialchars($project['description']); ?>
            </p>
            
            <?php if (!empty($project['goal'])): ?>
            <div class="bg-slate-50 rounded-xl p-4 mb-4">
              <div class="flex items-center justify-between text-sm mb-2">
                <span class="text-slate-600">Fundraising Goal</span>
                <span class="font-semibold text-brand-600">KES <?php echo number_format((int)str_replace(',', '', $project['goal'])); ?></span>
              </div>
              <?php if ($status === 'ongoing'): ?>
              <div class="h-3 bg-slate-200 rounded-full overflow-hidden">
                <div class="h-full w-[35%] bg-gradient-to-r from-brand-500 to-brand-400 rounded-full"></div>
              </div>
              <p class="text-xs text-slate-500 mt-2">35% funded - Your support makes a difference!</p>
              <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="flex flex-wrap gap-3">
              <a href="<?php echo htmlspecialchars(rgcUrl('contact.php')); ?>" class="btn btn-primary text-sm">Get Involved</a>
              <a href="<?php echo htmlspecialchars(rgcUrl('donate.php')); ?>" class="btn btn-outline text-sm">Donate</a>
            </div>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- CTA Section -->
<section class="section-padding bg-white">
  <div class="max-w-2xl mx-auto px-4 text-center">
    <h2 class="font-display text-2xl md:text-3xl font-bold text-slate-800 mb-4">Partner With Our Projects</h2>
    <p class="text-slate-600 mb-6">Your support helps us continue our mission to serve the community and spread the Gospel.</p>
    <div class="flex flex-wrap justify-center gap-4">
      <a href="<?php echo htmlspecialchars(rgcUrl('contact.php')); ?>" class="btn btn-primary">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        </svg>
        Get Involved
      </a>
      <a href="<?php echo htmlspecialchars(rgcUrl('donate.php')); ?>" class="btn btn-outline">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Donate
      </a>
    </div>
  </div>
</section>

require __DIR__ . '/includes/footer.php'; 
?>
