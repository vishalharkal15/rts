-- RTS Ticket System Database Setup
-- Run this with: mysql -u root -p < setup_database.sql

CREATE DATABASE IF NOT EXISTS rts_ticket_system;
USE rts_ticket_system;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student', 'trainer', 'intern', 'management') DEFAULT 'student',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tickets table
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_token VARCHAR(16) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    requester_id INT NOT NULL,
    assigned_to INT NULL,
    status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_token (ticket_token),
    INDEX idx_status (status),
    INDEX idx_requester (requester_id),
    INDEX idx_assigned (assigned_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chat requests table
CREATE TABLE IF NOT EXISTS chat_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_sender (sender_id),
    INDEX idx_receiver (receiver_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chat messages table
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chat_id) REFERENCES chat_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_chat (chat_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user
-- Password: admin123 (change this in production!)
INSERT INTO users (name, email, password, role, status) 
VALUES ('Vikram', 'admin@rts.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved')
ON DUPLICATE KEY UPDATE id=id;

-- Insert some test users
INSERT INTO users (name, email, password, role, status) 
VALUES 
    ('John Student', 'john@rts.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'approved'),
    ('Sarah Trainer', 'sarah@rts.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer', 'approved'),
    ('Mike Intern', 'mike@rts.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'intern', 'approved')
ON DUPLICATE KEY UPDATE id=id;

SELECT 'Database setup complete!' AS status;
SELECT 'Default admin: admin@rts.com / admin123' AS credentials;
