-- Upgrade script to add country and registration_ip columns to public_users table
-- Run this on existing databases

ALTER TABLE public_users
  ADD COLUMN IF NOT EXISTS country VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS registration_ip VARCHAR(64) NULL;

-- Update existing users with their country based on last_login_ip if available
-- This is optional - only if you have IP addresses stored
-- UPDATE public_users SET country = '' WHERE country IS NULL;
