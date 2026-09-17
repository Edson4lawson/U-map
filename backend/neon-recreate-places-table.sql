-- Drop and recreate places table with correct added_by type
DROP TABLE IF EXISTS places CASCADE;

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
