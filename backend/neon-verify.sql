-- ============================================
-- Script de vérification de la base Neon
-- ============================================

-- Lister toutes les tables
SELECT table_name 
FROM information_schema.tables 
WHERE table_schema = 'public' 
ORDER BY table_name;

-- Compter le nombre de tables
SELECT COUNT(*) as total_tables 
FROM information_schema.tables 
WHERE table_schema = 'public';

-- Vérifier la structure de la table users
SELECT column_name, data_type, is_nullable, column_default
FROM information_schema.columns
WHERE table_name = 'users' AND table_schema = 'public'
ORDER BY ordinal_position;

-- Vérifier la structure de la table messages
SELECT column_name, data_type, is_nullable, column_default
FROM information_schema.columns
WHERE table_name = 'messages' AND table_schema = 'public'
ORDER BY ordinal_position;

-- Vérifier la structure de la table personal_access_tokens
SELECT column_name, data_type, is_nullable, column_default
FROM information_schema.columns
WHERE table_name = 'personal_access_tokens' AND table_schema = 'public'
ORDER BY ordinal_position;

-- Compter les enregistrements dans chaque table
SELECT 
    'users' as table_name, COUNT(*) as row_count FROM users
UNION ALL
SELECT 'messages', COUNT(*) FROM messages
UNION ALL
SELECT 'places', COUNT(*) FROM places
UNION ALL
SELECT 'conversations', COUNT(*) FROM conversations
UNION ALL
SELECT 'personal_access_tokens', COUNT(*) FROM personal_access_tokens
UNION ALL
SELECT 'sessions', COUNT(*) FROM sessions
UNION ALL
SELECT 'cache', COUNT(*) FROM cache
ORDER BY table_name;

-- Vérifier les index
SELECT 
    tablename, 
    indexname, 
    indexdef
FROM pg_indexes
WHERE schemaname = 'public'
ORDER BY tablename, indexname;
