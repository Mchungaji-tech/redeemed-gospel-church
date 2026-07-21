
CREATE TABLE IF NOT EXISTS app_settings (
  id TINYINT PRIMARY KEY,
  maintenance_mode TINYINT(1) NOT NULL DEFAULT 0,
  maintenance_message VARCHAR(255) NOT NULL DEFAULT 'We are performing maintenance. Please check back shortly.',
  broadcast_enabled TINYINT(1) NOT NULL DEFAULT 1,
  broadcast_message VARCHAR(255) NOT NULL DEFAULT 'Welcome to Redeemed Gospel Church Eldoret. Sunday Service: 9:00 AM.',
  force_logout_at INT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
  full_name VARCHAR(100) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  activation_token_hash CHAR(64) NULL,
  activation_expires_at DATETIME NULL,
  otp_code_hash VARCHAR(255) NULL,
  otp_expires_at DATETIME NULL,
  failed_login_attempts INT NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  last_login_at DATETIME NULL,
  last_login_ip VARCHAR(64) NULL,
  created_by INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role (role),
  INDEX idx_users_active (is_active)
);

CREATE TABLE IF NOT EXISTS security_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  username VARCHAR(50) NULL,
  event_type VARCHAR(80) NOT NULL,
  event_message VARCHAR(255) NOT NULL,
  ip_address VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_security_logs_created_at (created_at),
  INDEX idx_security_logs_user_id (user_id),
  INDEX idx_security_logs_event_type (event_type)
);

CREATE TABLE IF NOT EXISTS outbound_emails (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  mail_to VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'queued_test',
  error_message VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_outbound_emails_created_at (created_at),
  INDEX idx_outbound_emails_mail_to (mail_to)
);

CREATE TABLE IF NOT EXISTS sermons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  speaker VARCHAR(100) NULL,
  youtube_url VARCHAR(500) NULL,
  facebook_embed TEXT NULL,
  scheduled_at DATETIME NULL,
  is_live TINYINT(1) NOT NULL DEFAULT 0,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sermons_scheduled_at (scheduled_at),
  INDEX idx_sermons_featured (featured),
  INDEX idx_sermons_is_live (is_live)
);

CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  location VARCHAR(200) NULL,
  poster TEXT NULL,
  event_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_events_event_at (event_at)
);

CREATE TABLE IF NOT EXISTS ministries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  icon VARCHAR(50) NULL,
  color VARCHAR(30) NULL,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  icon VARCHAR(50) NULL,
  image VARCHAR(500) NULL,
  status VARCHAR(30) NULL DEFAULT 'active',
  goal VARCHAR(50) NULL,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS testimonials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS gallery (
  id INT AUTO_INCREMENT PRIMARY KEY,
  image TEXT NOT NULL,
  caption VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sermon_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  comment TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_comments_sermon_id (sermon_id),
  INDEX idx_comments_created_at (created_at)
);

CREATE TABLE IF NOT EXISTS prayer_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(180) NULL,
  request TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_prayer_requests_created_at (created_at)
);

-- Ensure compatibility if older schema existed already.
ALTER TABLE sermons
  ADD COLUMN IF NOT EXISTS youtube_url VARCHAR(500) NULL,
  ADD COLUMN IF NOT EXISTS facebook_embed TEXT NULL,
  ADD COLUMN IF NOT EXISTS scheduled_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS is_live TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS featured TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE events
  ADD COLUMN IF NOT EXISTS description TEXT NULL,
  ADD COLUMN IF NOT EXISTS location VARCHAR(200) NULL,
  ADD COLUMN IF NOT EXISTS poster TEXT NULL,
  ADD COLUMN IF NOT EXISTS event_at DATETIME NULL;

ALTER TABLE ministries
  ADD COLUMN IF NOT EXISTS icon VARCHAR(50) NULL,
  ADD COLUMN IF NOT EXISTS color VARCHAR(30) NULL,
  ADD COLUMN IF NOT EXISTS featured TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE projects
  ADD COLUMN IF NOT EXISTS icon VARCHAR(50) NULL,
  ADD COLUMN IF NOT EXISTS image VARCHAR(500) NULL,
  ADD COLUMN IF NOT EXISTS status VARCHAR(30) NULL DEFAULT 'active',
  ADD COLUMN IF NOT EXISTS goal VARCHAR(50) NULL,
  ADD COLUMN IF NOT EXISTS featured TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE app_settings
  ADD COLUMN IF NOT EXISTS maintenance_bypass_minutes INT NOT NULL DEFAULT 30;

-- Profile support
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS avatar TEXT NULL;

-- Public users
CREATE TABLE IF NOT EXISTS public_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(180) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  last_active_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_public_users_verified (is_verified)
);

-- Upgrade: ensure activity columns exist on older public_users
ALTER TABLE public_users
  ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS last_active_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS country VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS registration_ip VARCHAR(64) NULL;

-- Donations
CREATE TABLE IF NOT EXISTS donations (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  full_name VARCHAR(120) NULL,
  email VARCHAR(180) NULL,
  amount_cents INT NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'KES',
  giving_type ENUM('offering','tithe','donation') NOT NULL DEFAULT 'donation',
  note VARCHAR(255) NULL,
  status ENUM('pending','received','failed') NOT NULL DEFAULT 'pending',
  method ENUM('paybill','paystack','bank','cash','mpesa','paypal','stripe') NOT NULL DEFAULT 'paybill',
  reference VARCHAR(120) NULL,
  phone VARCHAR(20) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_donations_status (status),
  INDEX idx_donations_user_id (user_id)
);

-- Upgrade: ensure new donation columns exist on older donations
ALTER TABLE donations
  ADD COLUMN IF NOT EXISTS giving_type ENUM('offering','tithe','donation') NOT NULL DEFAULT 'donation',
  ADD COLUMN IF NOT EXISTS method ENUM('paybill','paystack','bank','cash','mpesa','paypal','stripe') NOT NULL DEFAULT 'paybill',
  ADD COLUMN IF NOT EXISTS reference VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL;

ALTER TABLE donations
  MODIFY COLUMN method ENUM('paybill','paystack','bank','cash','mpesa','paypal','stripe') NOT NULL DEFAULT 'paybill';

CREATE TABLE IF NOT EXISTS public_messages (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  guest_token CHAR(64) NULL,
  name VARCHAR(120) NULL,
  email VARCHAR(180) NULL,
  type ENUM('chat','reply','broadcast') NOT NULL DEFAULT 'chat',
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  admin_seen_at DATETIME NULL,
  INDEX idx_public_messages_type (type),
  INDEX idx_public_messages_user_id (user_id),
  INDEX idx_public_messages_guest_token (guest_token),
  INDEX idx_public_messages_admin_seen_at (admin_seen_at)
);

ALTER TABLE public_messages
  ADD COLUMN IF NOT EXISTS guest_token CHAR(64) NULL AFTER user_id;

ALTER TABLE public_messages
  ADD COLUMN IF NOT EXISTS admin_seen_at DATETIME NULL AFTER created_at;

CREATE TABLE IF NOT EXISTS blog_posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  content LONGTEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_blog_posts_created_at (created_at)
);

ALTER TABLE blog_posts
  ADD COLUMN IF NOT EXISTS featured TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE blog_posts
  ADD COLUMN IF NOT EXISTS banner TEXT NULL;

-- Comment Management: Add new columns for user tracking, blocking, and reporting
ALTER TABLE comments
  ADD COLUMN IF NOT EXISTS user_id INT NULL,
  ADD COLUMN IF NOT EXISTS is_blocked TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS report_count INT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS is_flagged TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS blocked_reason VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS blocked_at DATETIME NULL;

SET @idx_comments_user_id_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'comments'
    AND index_name = 'idx_comments_user_id'
);
SET @idx_comments_user_id_sql = IF(
  @idx_comments_user_id_exists = 0,
  'ALTER TABLE comments ADD INDEX idx_comments_user_id (user_id)',
  'SELECT 1'
);
PREPARE stmt FROM @idx_comments_user_id_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_comments_is_blocked_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'comments'
    AND index_name = 'idx_comments_is_blocked'
);
SET @idx_comments_is_blocked_sql = IF(
  @idx_comments_is_blocked_exists = 0,
  'ALTER TABLE comments ADD INDEX idx_comments_is_blocked (is_blocked)',
  'SELECT 1'
);
PREPARE stmt FROM @idx_comments_is_blocked_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_comments_is_flagged_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'comments'
    AND index_name = 'idx_comments_is_flagged'
);
SET @idx_comments_is_flagged_sql = IF(
  @idx_comments_is_flagged_exists = 0,
  'ALTER TABLE comments ADD INDEX idx_comments_is_flagged (is_flagged)',
  'SELECT 1'
);
PREPARE stmt FROM @idx_comments_is_flagged_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_comments_user_id_exists = (
  SELECT COUNT(*)
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE table_schema = DATABASE()
    AND table_name = 'comments'
    AND column_name = 'user_id'
    AND referenced_table_name = 'public_users'
);
SET @fk_comments_user_id_sql = IF(
  @fk_comments_user_id_exists = 0,
  'ALTER TABLE comments ADD CONSTRAINT fk_comments_user_id FOREIGN KEY (user_id) REFERENCES public_users(id) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @fk_comments_user_id_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- No default users are inserted here.
-- Register admin/super-admin from /admin/register.php.
 
