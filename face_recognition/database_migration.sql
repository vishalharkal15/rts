-- SQL script to add facial recognition support
-- Run this to add the necessary tables

-- Create admin login logs table
CREATE TABLE IF NOT EXISTS admin_login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    login_type ENUM('password', 'face_recognition') DEFAULT 'password',
    confidence DECIMAL(5,2) NULL COMMENT 'Face recognition confidence score',
    login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_login_at (login_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add face_registered flag to users table
ALTER TABLE users 
ADD COLUMN face_registered TINYINT(1) DEFAULT 0 COMMENT 'Whether user has registered face for login',
ADD COLUMN face_registered_at TIMESTAMP NULL COMMENT 'When face was registered';

-- Create face authentication attempts log
CREATE TABLE IF NOT EXISTS face_auth_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    success TINYINT(1) DEFAULT 0,
    confidence DECIMAL(5,2) NULL,
    failure_reason VARCHAR(255) NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_attempted_at (attempted_at),
    INDEX idx_success (success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin if not exists (for testing)
INSERT IGNORE INTO users (id, name, email, password, role, status, created_at) 
VALUES (1, 'System Admin', 'admin@rts.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved', NOW());
-- Password is 'password' (for testing only - change in production!)

SELECT 'Database migration completed successfully!' AS Message;
