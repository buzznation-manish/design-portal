-- Design Portal Database Schema
-- Run this script to set up the database

CREATE DATABASE IF NOT EXISTS design_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE design_portal;

-- Admin users table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Projects table
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(100) NOT NULL,
    event_name VARCHAR(150) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    assigned_by VARCHAR(100) NOT NULL,
    status ENUM('Pending', 'Ongoing', 'Completed') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Project designers (many-to-many: one project can have multiple designers)
CREATE TABLE IF NOT EXISTS project_designers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    designer_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert a default admin user (password: Admin@1234)
-- Password hash generated with password_hash('Admin@1234', PASSWORD_BCRYPT)
INSERT INTO admins (username, password, full_name, email) VALUES
('admin', '$2y$12$YKI4c81r5BFXDlp0PkZTheF4WjBkJn2GEd2kH/.J69r1kxJKzrLVm', 'Design Team Admin', 'admin@designportal.com');

-- Sample projects for demonstration
INSERT INTO projects (client_name, event_name, start_date, end_date, assigned_by, status) VALUES
('ABC Corporation', 'Annual Company Gala', '2026-03-01', '2026-04-15', 'Sarah Johnson', 'Ongoing'),
('XYZ Events', 'Product Launch Event', '2026-03-10', '2026-04-10', 'Michael Brown', 'Ongoing'),
('Tech Innovators', 'Tech Summit 2026', '2026-01-15', '2026-02-28', 'Sarah Johnson', 'Completed'),
('Green Earth NGO', 'Charity Fundraiser', '2026-04-01', '2026-04-30', 'Emily Davis', 'Pending'),
('Metro Real Estate', 'Property Expo', '2026-03-20', '2026-04-20', 'Michael Brown', 'Ongoing');

-- Sample designers for projects
INSERT INTO project_designers (project_id, designer_name) VALUES
(1, 'Alice Wong'),
(1, 'Bob Smith'),
(2, 'Charlie Lee'),
(3, 'Alice Wong'),
(3, 'Diana Prince'),
(4, 'Bob Smith'),
(5, 'Charlie Lee'),
(5, 'Eve Taylor'),
(5, 'Frank Miller');

CREATE TABLE IF NOT EXISTS designers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO designers (name) VALUES ('Alice Wong'),('Bob Smith'),('Charlie Lee'),('Diana Prince'),('Eve Taylor'),('Frank Miller');
INSERT IGNORE INTO sales_users (name) VALUES ('Sarah Johnson'),('Michael Brown'),('Emily Davis');
