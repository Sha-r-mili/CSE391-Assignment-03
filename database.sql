-- ============================================
-- Car Workshop Appointment System
-- Database Setup Script
-- ============================================
-- Student Name: [YOUR NAME HERE]
-- Student ID: [YOUR ID HERE]
-- Course: CSE 391 - Programming for the Internet
-- Assignment: 3 - PHP and MySQL Project
-- ============================================

-- Create database (for local XAMPP only - skip for InfinityFree)
CREATE DATABASE IF NOT EXISTS workshop_appointment;
USE workshop_appointment;

-- ============================================
-- Table: mechanics
-- Stores information about workshop mechanics
-- ============================================
CREATE TABLE IF NOT EXISTS mechanics (
    mechanic_id INT PRIMARY KEY AUTO_INCREMENT,
    mechanic_name VARCHAR(100) NOT NULL,
    max_appointments INT DEFAULT 4 COMMENT 'Maximum appointments per day',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: appointments
-- Stores client appointment information
-- ============================================
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT PRIMARY KEY AUTO_INCREMENT,
    client_name VARCHAR(100) NOT NULL,
    client_address VARCHAR(255) NOT NULL,
    client_phone VARCHAR(20) NOT NULL,
    car_license VARCHAR(50) NOT NULL,
    car_engine VARCHAR(100) NOT NULL,
    mechanic_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mechanic_id) REFERENCES mechanics(mechanic_id) ON DELETE CASCADE,
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_mechanic_date (mechanic_id, appointment_date),
    INDEX idx_client_phone (client_phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: admin_users
-- Stores admin login credentials
-- ============================================
CREATE TABLE IF NOT EXISTS admin_users (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Insert Sample Data
-- ============================================

-- Insert 5 mechanics
INSERT INTO mechanics (mechanic_name, max_appointments) VALUES
('John Smith', 4),
('Michael Johnson', 4),
('David Williams', 4),
('Robert Brown', 4),
('James Davis', 4);

-- Insert default admin user
-- Username: admin
-- Password: admin123
INSERT INTO admin_users (username, password) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ============================================
-- Sample Appointment Data (Optional - for testing)
-- ============================================
/*
INSERT INTO appointments (client_name, client_address, client_phone, car_license, car_engine, mechanic_id, appointment_date, status) VALUES
('Alice Johnson', '123 Main Street, Dhaka', '01712345678', 'DHA-1234', 'ENG12345', 1, CURDATE() + INTERVAL 1 DAY, 'confirmed'),
('Bob Smith', '456 Park Avenue, Dhaka', '01798765432', 'DHA-5678', 'ENG67890', 2, CURDATE() + INTERVAL 2 DAY, 'confirmed'),
('Charlie Brown', '789 Lake Road, Dhaka', '01611223344', 'DHA-9012', 'ENG11223', 3, CURDATE() + INTERVAL 3 DAY, 'confirmed');
*/

-- ============================================
-- Verify Installation
-- ============================================
SELECT 'Database setup completed successfully!' AS Status;
SELECT COUNT(*) AS 'Total Mechanics' FROM mechanics;
SELECT COUNT(*) AS 'Total Admin Users' FROM admin_users;

-- ============================================
-- End of Database Setup
-- ============================================