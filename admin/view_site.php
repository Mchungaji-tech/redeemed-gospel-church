<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
$settings = rgcLoadJson('settings.json', rgcDefaultSettings());
$minutes = (int) ($settings['maintenance_bypass_minutes'] ?? 30);
if ($minutes < 5) { $minutes = 5; }
if ($minutes > 180) { $minutes = 180; }
$_SESSION['maintenance_bypass_until'] = time() + ($minutes * 60);
header('Location: ' . rgcUrl('index.php'));
exit;
