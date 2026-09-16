-- Vérifier les données des places avec descriptions
SELECT id, name, description, category, type, 
       substring(description, 1, 100) as description_preview
FROM places
LIMIT 10;

-- Compter les places avec descriptions non nulles
SELECT 
    COUNT(*) as total_places,
    COUNT(description) as places_with_description,
    COUNT(images) as places_with_images
FROM places;
