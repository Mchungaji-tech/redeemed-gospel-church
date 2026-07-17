<?php
require __DIR__ . '/../includes/app.php';
unset($_SESSION['public_user']);
$returnTo = rgcSanitizeReturnPath((string) ($_GET['r'] ?? 'index.php'));
header('Location: ' . $returnTo);
exit;
