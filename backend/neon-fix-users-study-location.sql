-- Add missing study_location column to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS study_location VARCHAR(255);
