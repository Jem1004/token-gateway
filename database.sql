-- ============================================================================
-- Token Gate Application - Database Initialization Script
-- ============================================================================
-- This script creates the database and table structure for the Token Gate
-- application. It should be run once during initial setup.
--
-- SETUP INSTRUCTIONS:
-- 1. Ensure MySQL server is running
-- 2. Run this script as a MySQL user with CREATE DATABASE privileges:
--    mysql -u root -p < database.sql
-- 3. Update config.php with your database credentials
-- 4. Verify the initial token is created successfully
--
-- REQUIREMENTS ADDRESSED:
-- - 5.1: Create active_token table with single row design
-- - 5.2: Define id column as Primary Key (INT type)
-- - 5.3: Define current_token column (VARCHAR type)
-- ============================================================================

-- Create the database
CREATE DATABASE IF NOT EXISTS token_gate_db
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

-- Use the newly created database
USE token_gate_db;

-- Create the active_token table
-- This table stores only ONE active token at any time (single row design)
CREATE TABLE IF NOT EXISTS active_token (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT 'Primary key, always value 1',
    current_token VARCHAR(50) NOT NULL COMMENT 'Currently valid access token',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last token update timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert the initial token
-- This creates the single row that will be updated by token rotation
INSERT INTO active_token (id, current_token) VALUES (1, 'INITIAL00')
ON DUPLICATE KEY UPDATE current_token = current_token;

-- Verify the setup
SELECT 'Database setup completed successfully!' AS status;
SELECT * FROM active_token;
