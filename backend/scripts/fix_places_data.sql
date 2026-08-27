-- Correction 1 : Mettre à jour la catégorie de Zone Master vers la nouvelle taxonomie
UPDATE places 
SET category = 'academic_area' 
WHERE slug = 'zone-master';

-- Correction 2 : Ajouter l'image Unsplash aux 5 lieux de restauration sans images
UPDATE places 
SET images = '["https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=1000&auto=format&fit=crop"]'::jsonb
WHERE slug IN ('resteau-bar-uac', 'centre-commercial-epac', 'restaurant-universitaire-1', 'restaurant-universitaire-2', 'restaurant-universitaire-4');
