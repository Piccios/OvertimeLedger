-- Database structure for Overtime Hours Manager
-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS straordinari;
USE straordinari;

-- Companies table
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#3b82f6',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Extra hours table
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

-- Indexes for performance
CREATE INDEX idx_extra_hours_date ON extra_hours(date);
CREATE INDEX idx_extra_hours_company ON extra_hours(company_id);

-- Insert some sample companies
INSERT INTO companies (name, color) VALUES 
('Defenda', '#1e3a8a'),
('Euroansa', '#3b82f6'),
('Italian Luxury Villas', '#f59e0b'); 