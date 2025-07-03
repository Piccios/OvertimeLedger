-- Database for tracking extra hours
CREATE DATABASE IF NOT EXISTS straordinari;
USE straordinari;

-- Table for companies
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    color VARCHAR(7) NOT NULL DEFAULT '#6c757d',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for extra hours records
CREATE TABLE extra_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    date DATE NOT NULL,
    hours DECIMAL(4,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_company_date (company_id, date)
);

-- Insert the three companies with their colors
INSERT INTO companies (name, color) VALUES 
('Defenda', '#1e3a8a'),
('Euroansa', '#3b82f6'),
('Italian Luxury Villas', '#f59e0b');

-- Create indexes for better performance
CREATE INDEX idx_extra_hours_date ON extra_hours(date);
CREATE INDEX idx_extra_hours_company ON extra_hours(company_id);

-- Migration: Add color column to existing companies table if it doesn't exist
-- (Run this if you already have the database set up)
-- ALTER TABLE companies ADD COLUMN color VARCHAR(7) NOT NULL DEFAULT '#6c757d';
-- UPDATE companies SET color = '#1e3a8a' WHERE name = 'Defenda';
-- UPDATE companies SET color = '#3b82f6' WHERE name = 'Euroansa';
-- UPDATE companies SET color = '#f59e0b' WHERE name = 'Italian Luxury Villas'; 