<?php

namespace App\DTO;

class OverpassPlace
{
    public function __construct(
        public readonly int $osmId,
        public readonly string $name,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly string $type, // 'amenity', 'building', or 'office'
        public readonly ?string $category = null,
        public readonly array $tags = []
    ) {}

    /**
     * Get normalized name for comparison (lowercase, no accents, trimmed)
     */
    public function getNormalizedName(): string
    {
        $normalized = mb_strtolower($this->name);
        $normalized = trim($normalized);
        $normalized = $this->removeAccents($normalized);
        return $normalized;
    }

    /**
     * Remove accents from string
     */
    private function removeAccents(string $string): string
    {
        $accents = [
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ];

        return strtr($string, $accents);
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'osm_id' => $this->osmId,
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'type' => $this->type,
            'category' => $this->category,
            'tags' => $this->tags,
        ];
    }

    /**
     * Create from Overpass API element
     */
    public static function fromOverpassElement(array $element): self
    {
        $type = $element['type'] ?? 'node';
        $tags = $element['tags'] ?? [];
        
        // Determine coordinates
        if ($type === 'node') {
            $lat = $element['lat'] ?? 0;
            $lon = $element['lon'] ?? 0;
        } elseif (isset($element['center'])) {
            $lat = $element['center']['lat'] ?? 0;
            $lon = $element['center']['lon'] ?? 0;
        } else {
            $lat = 0;
            $lon = 0;
        }

        // Determine place type (amenity, building, or office)
        $placeType = 'other';
        if (isset($tags['amenity'])) {
            $placeType = 'amenity';
        } elseif (isset($tags['building'])) {
            $placeType = 'building';
        } elseif (isset($tags['office'])) {
            $placeType = 'office';
        }

        return new self(
            osmId: $element['id'],
            name: $tags['name'] ?? 'Unknown',
            latitude: $lat,
            longitude: $lon,
            type: $placeType,
            category: $tags['amenity'] ?? $tags['building'] ?? $tags['office'] ?? null,
            tags: $tags
        );
    }
}
