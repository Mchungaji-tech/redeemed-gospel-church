<?php
require __DIR__ . '/includes/auth.php';

$u = rgcCurrentUser();
if ($u) {
  rgcSecurityLog('logout', 'User logged out.', (int) ($u['id'] ?? 0), (string) ($u['username'] ?? null));
}
session_destroy();
header('Location: ' . rgcUrl('admin/login.php'));
exit;
