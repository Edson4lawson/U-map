-- Create all missing tables for U-Map application

-- Users table
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
    END IF;
END $$;

-- Places table
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'places') THEN
        CREATE TABLE places (
            id BIGSERIAL PRIMARY KEY,
            uuid VARCHAR(36) UNIQUE,
            slug VARCHAR(255) UNIQUE,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(255),
            category VARCHAR(255),
            description TEXT,
            opening_hours TEXT,
            latitude DECIMAL(10, 8),
            longitude DECIMAL(11, 8),
            added_by VARCHAR(255),
            status VARCHAR(20) DEFAULT 'pending',
            images JSONB,
            tags JSONB,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        RAISE NOTICE 'Table places created successfully';
    END IF;
END $$;

-- Messages table
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'messages') THEN
        CREATE TABLE messages (
            id BIGSERIAL PRIMARY KEY,
            sender_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            receiver_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            encrypted_content TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        RAISE NOTICE 'Table messages created successfully';
    END IF;
END $$;

-- Events table
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'events') THEN
        CREATE TABLE events (
            id BIGSERIAL PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            start_time TIMESTAMP NOT NULL,
            end_time TIMESTAMP,
            location VARCHAR(255),
            organizer_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        RAISE NOTICE 'Table events created successfully';
    END IF;
END $$;

-- Reports table
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'reports') THEN
        CREATE TABLE reports (
            id BIGSERIAL PRIMARY KEY,
            reporter_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            reported_user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            reason TEXT NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        RAISE NOTICE 'Table reports created successfully';
    END IF;
END $$;

-- User devices table
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'user_devices') THEN
        CREATE TABLE user_devices (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            device_id VARCHAR(255) NOT NULL,
            device_name VARCHAR(255),
            platform VARCHAR(50),
            ip_address VARCHAR(45),
            last_active_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        RAISE NOTICE 'Table user_devices created successfully';
    END IF;
END $$;
