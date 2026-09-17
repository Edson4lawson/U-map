-- Check if users table exists and create it if not
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'users') THEN
        CREATE TABLE users (
            id BIGSERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            email_verified_at TIMESTAMP NULL,
            password VARCHAR(255) NOT NULL,
            remember_token VARCHAR(100),
            is_restricted BOOLEAN DEFAULT FALSE,
            role VARCHAR(50) DEFAULT 'user',
            two_factor_secret TEXT,
            two_factor_confirmed_at TIMESTAMP,
            two_factor_recovery_codes TEXT,
            social_provider VARCHAR(255),
            social_id VARCHAR(255),
            google_id VARCHAR(255),
            google_token TEXT,
            google_refresh_token TEXT,
            study_status VARCHAR(255),
            study_location VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        RAISE NOTICE 'Table users created successfully';
    ELSE
        RAISE NOTICE 'Table users already exists';
    END IF;
END $$;
