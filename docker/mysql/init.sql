-- =============================================================================
-- NexCreate - MySQL Initialization Script
-- =============================================================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS nexcreate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant privileges
GRANT ALL PRIVILEGES ON nexcreate.* TO 'nexcreate_user'@'%';
FLUSH PRIVILEGES;

-- Use the database
USE nexcreate;

-- Log successful initialization
SELECT 'NexCreate database initialized successfully!' AS status;
