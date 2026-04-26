-- Initialize renewal_settings table
USE majayjay_scholars;

-- Create table if it doesn't exist
CREATE TABLE IF NOT EXISTS renewal_settings (
    id INT PRIMARY KEY DEFAULT 1,
    is_open BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default record if it doesn't exist
INSERT IGNORE INTO renewal_settings (id, is_open) VALUES (1, FALSE);

-- Check the current status
SELECT * FROM renewal_settings;
