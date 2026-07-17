<?php
require __DIR__ . '/includes/app.php';
if (!rgcMaintenanceOn()) { header('Location: ' . rgcUrl('index.php')); exit; }
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Maintenance</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-slate-900 text-white min-h-screen flex items-center justify-center p-4"><div class="max-w-lg text-center"><h1 class="text-3xl font-bold mb-3">We\'ll Be Back Soon</h1><p class="text-slate-300"><?php echo htmlspecialchars($rgcSettings['maintenance_message'] ?? 'Site under maintenance.'); ?></p><p class="text-slate-400 text-sm mt-3">Admins can continue signing in to update content while maintenance is active.</p><a class="inline-block mt-6 px-5 py-2 rounded bg-white text-slate-900" href="<?php echo htmlspecialchars(rgcUrl('admin/login.php')); ?>">Admin Login</a></div></body></html>
