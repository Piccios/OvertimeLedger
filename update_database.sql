-- Add color column to companies table
ALTER TABLE companies ADD COLUMN color VARCHAR(7) DEFAULT '#3b82f6' AFTER name;

-- Update existing companies with default colors
UPDATE companies SET color = '#1e3a8a' WHERE name = 'Defenda';
UPDATE companies SET color = '#3b82f6' WHERE name = 'Euroansa';
UPDATE companies SET color = '#f59e0b' WHERE name = 'Italian Luxury Villas'; 