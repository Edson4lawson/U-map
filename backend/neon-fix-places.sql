-- Ajouter les colonnes manquantes à places pour le seeder
ALTER TABLE places ADD COLUMN IF NOT EXISTS uuid VARCHAR(255) UNIQUE;
ALTER TABLE places ADD COLUMN IF NOT EXISTS type VARCHAR(100);
ALTER TABLE places ADD COLUMN IF NOT EXISTS opening_hours TEXT;
ALTER TABLE places ADD COLUMN IF NOT EXISTS images JSONB;
ALTER TABLE places ADD COLUMN IF NOT EXISTS tags JSONB;

-- Supprimer et recréer la table places avec le bon schéma si nécessaire
DROP TABLE IF EXISTS places CASCADE;

CREATE TABLE places (
    id BIGSERIAL PRIMARY KEY,
    uuid VARCHAR(255) UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    type VARCHAR(100),
    opening_hours TEXT,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    image_url VARCHAR(500),
    images JSONB,
    tags JSONB,
    added_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    status VARCHAR(50) DEFAULT 'approved',
    slug VARCHAR(255) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Recréer les index
CREATE INDEX IF NOT EXISTS idx_places_category ON places(category);
CREATE INDEX IF NOT EXISTS idx_places_status ON places(status);
CREATE INDEX IF NOT EXISTS idx_places_slug ON places(slug);
CREATE INDEX IF NOT EXISTS idx_places_uuid ON places(uuid);
