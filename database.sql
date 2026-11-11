-- Create the support ticket database
CREATE DATABASE IF NOT EXISTS lab06_support
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE lab06_support;

-- Create the support tickets table
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    issue_category VARCHAR(100) NOT NULL,
    priority VARCHAR(20) NOT NULL,
    subject VARCHAR(120) NOT NULL,
    issue_description TEXT NOT NULL,
    screenshot VARCHAR(255) DEFAULT NULL,
    status ENUM('Pending', 'In Progress', 'Resolved')
        DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);