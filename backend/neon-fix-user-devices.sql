-- Ajouter la colonne manquante updated_at à user_devices
ALTER TABLE user_devices ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
