<?php

// Générer le SQL pour importer les lieux dans Supabase
$json = file_get_contents(__DIR__ . '/../database/data/campus.json');
$data = json_decode($json, true);

$sql = "-- Import des lieux dans Supabase\n";
$sql .= "INSERT INTO places (uuid, name, slug, description, latitude, longitude, category, type, status, added_by, opening_hours, images, tags, created_at, updated_at) VALUES\n";

$values = [];
foreach ($data['features'] as $index => $feature) {
    $props = $feature['properties'];
    $coords = $feature['geometry']['coordinates'];
    
    // Générer un UUID valide
    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    $name = str_replace("'", "''", $props['name']);
    // Générer un slug correct en gérant les caractères accentués
    $slug = strtolower($props['name']);
    // Remplacer les caractères accentués par leurs équivalents non accentués
    $accents = ['à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
                'æ' => 'ae', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
                'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'd', 'ñ' => 'n',
                'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
                'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'þ' => 'th',
                'ÿ' => 'y', 'œ' => 'oe',
                // Majuscules accentuées
                'À' => 'a', 'Á' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Ä' => 'a', 'Å' => 'a',
                'Æ' => 'ae', 'Ç' => 'c', 'È' => 'e', 'É' => 'e', 'Ê' => 'e', 'Ë' => 'e',
                'Ì' => 'i', 'Í' => 'i', 'Î' => 'i', 'Ï' => 'i', 'Ð' => 'd', 'Ñ' => 'n',
                'Ò' => 'o', 'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o', 'Ø' => 'o',
                'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ü' => 'u', 'Ý' => 'y', 'Þ' => 'th',
                'Ÿ' => 'y', 'Œ' => 'oe'];
    $slug = strtr($slug, $accents);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug); // Remplacer les caractères non alphanumériques
    $slug = trim($slug, '-'); // Enlever les tirets au début et à la fin
    $description = str_replace("'", "''", $props['description'] ?? '');
    $latitude = $coords[1];
    $longitude = $coords[0];
    $category = str_replace("'", "''", $props['category'] ?? '');
    $type = str_replace("'", "''", $props['type'] ?? '');
    $addedBy = 'system'; // Valeur par défaut pour les lieux importés
    
    // Convertir opening_hours en JSON valide
    $openingHoursValue = $props['openingHours'] ?? '';
    if (!empty($openingHoursValue)) {
        $openingHours = json_encode(['hours' => $openingHoursValue]);
    } else {
        $openingHours = 'null';
    }
    $openingHours = str_replace("'", "''", $openingHours);
    
    $images = str_replace("'", "''", json_encode($props['images'] ?? []));
    $tags = str_replace("'", "''", json_encode($props['tags'] ?? []));
    
    $values[] = "('$uuid', '$name', '$slug', '$description', $latitude, $longitude, '$category', '$type', 'approved', '$addedBy', '$openingHours'::jsonb, '$images'::jsonb, '$tags'::jsonb, NOW(), NOW())";
}

$sql .= implode(",\n", $values) . ";\n";

file_put_contents(__DIR__ . '/import_places.sql', $sql);
echo "SQL généré: " . __DIR__ . '/import_places.sql' . "\n";
echo "Nombre de lieux: " . count($values) . "\n";
