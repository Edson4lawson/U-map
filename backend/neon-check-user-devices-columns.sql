-- Vérifier les colonnes de user_devices
SELECT column_name, data_type
FROM information_schema.columns
WHERE table_name = 'user_devices' AND table_schema = 'public'
ORDER BY ordinal_position;
