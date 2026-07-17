-- Upgrade script to add new columns to existing tables
-- Run this file to add new features without losing existing data

-- Add YouTube URL to sermons table
ALTER TABLE sermons ADD COLUMN youtube_url VARCHAR(500) NULL;

-- Add icon, color, featured to ministries table
ALTER TABLE ministries ADD COLUMN icon VARCHAR(50) NULL;
ALTER TABLE ministries ADD COLUMN color VARCHAR(30) NULL;
ALTER TABLE ministries ADD COLUMN featured TINYINT(1) NOT NULL DEFAULT 0;

-- Add icon, image, status, goal, featured to projects table
ALTER TABLE projects ADD COLUMN icon VARCHAR(50) NULL;
ALTER TABLE projects ADD COLUMN image VARCHAR(500) NULL;
ALTER TABLE projects ADD COLUMN status VARCHAR(30) NULL DEFAULT 'active';
ALTER TABLE projects ADD COLUMN goal VARCHAR(50) NULL;
ALTER TABLE projects ADD COLUMN featured TINYINT(1) NOT NULL DEFAULT 0;

-- If you get "Duplicate column" errors, that's OK - it means the column already exists
