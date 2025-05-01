-- Dental Clinic System - Complete Database Schema
-- This file contains all the SQL statements to create the database and tables

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS clinicdentalsystem;
USE clinicdentalsystem;

-- Drop existing tables to avoid conflicts (if recreating)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS system_logs;
DROP TABLE IF EXISTS analytics_data;
DROP TABLE IF EXISTS time_off;
DROP TABLE IF EXISTS doctor_schedule;
DROP TABLE IF EXISTS treatment_templates;
DROP TABLE IF EXISTS prescriptions;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS treatments;
DROP TABLE IF EXISTS medical_history;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Create users table - central table for all users
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient', 'doctor', 'admin') NOT NULL DEFAULT 'patient',
    specialization VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    date_of_birth DATE NULL,
    gender ENUM('male', 'female', 'other') NULL,
    profile_picture VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create appointments table
CREATE TABLE appointments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    patient_id INT(11) NOT NULL,
    doctor_id INT(11) NOT NULL,
    appointment_date DATETIME NOT NULL,
    reason TEXT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'no_show') NOT NULL DEFAULT 'pending',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create medical_history table
CREATE TABLE medical_history (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    patient_id INT(11) NOT NULL,
    allergies TEXT NULL,
    medications TEXT NULL,
    medical_conditions TEXT NULL,
    previous_surgeries TEXT NULL,
    blood_type VARCHAR(10) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_patient_id (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create treatments table
CREATE TABLE treatments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    patient_id INT(11) NOT NULL,
    doctor_id INT(11) NOT NULL,
    diagnosis VARCHAR(255) NOT NULL,
    treatment_plan TEXT NOT NULL,
    treatment_type ENUM('diagnostic', 'preventive', 'restorative', 'endodontic', 'periodontal', 'prosthodontic', 'oral_surgery', 'orthodontic', 'cosmetic') NULL,
    teeth_involved VARCHAR(100) NULL,
    notes TEXT NULL,
    status ENUM('planned', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned',
    cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_patient_id (patient_id),
    INDEX idx_doctor_id (doctor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create payments table
CREATE TABLE payments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    treatment_id INT(11) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'gcash', 'bank_transfer', 'insurance') NOT NULL,
    reference_number VARCHAR(50) NULL,
    status ENUM('pending', 'completed', 'rejected', 'refunded') NOT NULL DEFAULT 'pending',
    payment_date DATETIME NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (treatment_id) REFERENCES treatments(id) ON DELETE CASCADE,
    INDEX idx_payment_method (payment_method),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create prescriptions table
CREATE TABLE prescriptions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    patient_id INT(11) NOT NULL,
    doctor_id INT(11) NOT NULL,
    treatment_id INT(11) NULL,
    medication VARCHAR(255) NOT NULL,
    dosage VARCHAR(100) NOT NULL,
    frequency VARCHAR(100) NOT NULL,
    duration VARCHAR(100) NOT NULL,
    instructions TEXT NOT NULL,
    notes TEXT NULL,
    status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (treatment_id) REFERENCES treatments(id) ON DELETE SET NULL,
    INDEX idx_patient_id (patient_id),
    INDEX idx_doctor_id (doctor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create treatment_templates table
CREATE TABLE treatment_templates (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    procedure_steps TEXT NOT NULL,
    estimated_cost DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create doctor_schedule table
CREATE TABLE doctor_schedule (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT(11) NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_day (doctor_id, day_of_week),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_day_of_week (day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create time_off table
CREATE TABLE time_off (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT(11) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_date_range (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create analytics_data table
CREATE TABLE analytics_data (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(50) NOT NULL,
    report_period VARCHAR(20) NOT NULL,
    report_date DATE NOT NULL,
    data_json LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_type (report_type),
    INDEX idx_report_date (report_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create system_logs table
CREATE TABLE system_logs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user
INSERT INTO users (name, email, password, role) 
VALUES ('Admin User', 'admin@gmail.com', '$2y$10$rrWyIQI.6o6M/BwYnBZtYehzWCBbP2Z6XJgFzsdOSVJKLTSaXnmy6', 'admin');
-- Note: The password hash above is for 'admin123'

-- Insert default doctor
INSERT INTO users (name, email, password, role, specialization) 
VALUES ('Dr. John Smith', 'doctor@example.com', '$2y$10$bqjwD9KQw0XZ0cJpSH1cFuLuXuCZq1EcZSXQXeK.DcMm4xhxPWdm2', 'doctor', 'General Dentistry');
-- Note: The password hash above is for 'doctor123'

-- Insert sample doctor schedule
INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time) VALUES
((SELECT id FROM users WHERE email = 'doctor@example.com'), 'monday', '09:00:00', '17:00:00'),
((SELECT id FROM users WHERE email = 'doctor@example.com'), 'tuesday', '09:00:00', '17:00:00'),
((SELECT id FROM users WHERE email = 'doctor@example.com'), 'wednesday', '09:00:00', '17:00:00'),
((SELECT id FROM users WHERE email = 'doctor@example.com'), 'thursday', '09:00:00', '17:00:00'),
((SELECT id FROM users WHERE email = 'doctor@example.com'), 'friday', '09:00:00', '17:00:00'),
((SELECT id FROM users WHERE email = 'doctor@example.com'), 'saturday', '09:00:00', '13:00:00');
