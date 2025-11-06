-- ============================================================================
-- Token Gate Application - Add Timer Feature Migration
-- ============================================================================
-- This script adds timer functionality to the existing token system.
-- Run this script to add countdown timer features to your database.
--
-- MIGRATION INSTRUCTIONS:
-- 1. Backup your current database
-- 2. Run this script to add timer columns
-- 3. Update your application to use timer features
-- ============================================================================

USE token_gate_db;

-- Add timer-related columns to active_token table
ALTER TABLE active_token
ADD COLUMN token_expiry_minutes INT DEFAULT 60 COMMENT 'Minutes until token expires/changes',
ADD COLUMN token_rotation_interval INT DEFAULT 60 COMMENT 'Automatic rotation interval in minutes',
ADD COLUMN last_rotation_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Last time token was rotated',
ADD COLUMN next_rotation_time TIMESTAMP NULL COMMENT 'Scheduled time for next rotation',
ADD COLUMN auto_rotation_enabled BOOLEAN DEFAULT TRUE COMMENT 'Enable/disable automatic rotation';

-- Create token_history table for tracking rotation history
CREATE TABLE IF NOT EXISTS token_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    old_token VARCHAR(50) NOT NULL COMMENT 'Previous token value',
    new_token VARCHAR(50) NOT NULL COMMENT 'New token value',
    rotation_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When rotation occurred',
    rotation_type ENUM('manual', 'auto') DEFAULT 'manual' COMMENT 'How rotation was triggered',
    rotated_by VARCHAR(50) COMMENT 'Username who initiated rotation (manual only)',
    ip_address VARCHAR(45) COMMENT 'IP address of rotation initiator'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update existing record with default timer values
UPDATE active_token SET
    token_expiry_minutes = 60,
    token_rotation_interval = 60,
    auto_rotation_enabled = TRUE,
    next_rotation_time = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 60 MINUTE)
WHERE id = 1;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_token_history_time ON token_history(rotation_time);
CREATE INDEX IF NOT EXISTS idx_token_history_type ON token_history(rotation_type);

-- Verify the migration
SELECT 'Timer migration completed successfully!' AS status;
SELECT
    id,
    current_token,
    token_expiry_minutes,
    token_rotation_interval,
    auto_rotation_enabled,
    last_rotation_time,
    next_rotation_time,
    updated_at
FROM active_token WHERE id = 1;

SELECT 'token_history table created' AS table_status;
DESCRIBE token_history;