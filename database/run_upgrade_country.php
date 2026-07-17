<?php
/**
 * Script to add country and registration_ip columns to public_users table
 */

require_once __DIR__ . '/../includes/app.php';

if (!rgcDbAvailable()) {
    echo "Error: Database is not available.\n";
    exit(1);
}

$sql = "ALTER TABLE public_users
  ADD COLUMN IF NOT EXISTS country VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS registration_ip VARCHAR(64) NULL;";

try {
    rgcDb()->exec($sql);
    echo "Success: Added country and registration_ip columns to public_users table.\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
