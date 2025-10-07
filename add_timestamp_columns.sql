-- SQL script to add missing timestamp columns to tokens table
-- Run this in phpMyAdmin or your MySQL client

-- Add completed_at column if it doesn't exist
ALTER TABLE tokens ADD COLUMN IF NOT EXISTS completed_at DATETIME DEFAULT NULL;

-- Add other timestamp columns that might be missing
ALTER TABLE tokens ADD COLUMN IF NOT EXISTS called_at DATETIME DEFAULT NULL;
ALTER TABLE tokens ADD COLUMN IF NOT EXISTS cancelled_at DATETIME DEFAULT NULL;

-- Verify the changes
DESCRIBE tokens;