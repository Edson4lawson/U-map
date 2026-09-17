-- Add missing columns to users table based on Laravel migrations
ALTER TABLE users ADD COLUMN IF NOT EXISTS study_location VARCHAR(255);
ALTER TABLE users ADD COLUMN IF NOT EXISTS social_provider VARCHAR(255);
ALTER TABLE users ADD COLUMN IF NOT EXISTS social_id VARCHAR(255);
ALTER TABLE users ALTER COLUMN two_factor_secret TYPE TEXT;
ALTER TABLE users ALTER COLUMN two_factor_recovery_codes TYPE TEXT;
ALTER TABLE users ALTER COLUMN study_status TYPE VARCHAR(255);
