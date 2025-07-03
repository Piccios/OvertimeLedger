-- Migration script to add color column to existing companies table
-- Run this if you already have the database set up

USE straordinari;

-- Add color column to companies table
ALTER TABLE companies ADD COLUMN color VARCHAR(7) NOT NULL DEFAULT '#6c757d';

-- Update existing companies with their default colors
UPDATE companies SET color = '#1e3a8a' WHERE name = 'Defenda';
UPDATE companies SET color = '#3b82f6' WHERE name = 'Euroansa';
UPDATE companies SET color = '#f59e0b' WHERE name = 'Italian Luxury Villas'; 