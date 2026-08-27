<?php

namespace App\Services\Geo;

class Haversine
{
    /**
     * Calculate the great-circle distance between two points on Earth
     * using the Haversine formula.
     *
     * @param float $lat1 Latitude of first point in decimal degrees
     * @param float $lon1 Longitude of first point in decimal degrees
     * @param float $lat2 Latitude of second point in decimal degrees
     * @param float $lon2 Longitude of second point in decimal degrees
     * @return float Distance in kilometers
     */
    public static function calculate(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        // Convert degrees to radians
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        // Calculate differences
        $latDiff = $lat2Rad - $lat1Rad;
        $lonDiff = $lon2Rad - $lon1Rad;

        // Haversine formula
        $a = sin($latDiff / 2) ** 2 + cos($lat1Rad) * cos($lat2Rad) * sin($lonDiff / 2) ** 2;
        $c = 2 * asin(sqrt($a));

        return $earthRadius * $c;
    }

    /**
     * Calculate distance in meters
     *
     * @param float $lat1 Latitude of first point in decimal degrees
     * @param float $lon1 Longitude of first point in decimal degrees
     * @param float $lat2 Latitude of second point in decimal degrees
     * @param float $lon2 Longitude of second point in decimal degrees
     * @return float Distance in meters
     */
    public static function calculateInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return self::calculate($lat1, $lon1, $lat2, $lon2) * 1000;
    }

    /**
     * Check if two points are within a certain distance threshold
     *
     * @param float $lat1 Latitude of first point in decimal degrees
     * @param float $lon1 Longitude of first point in decimal degrees
     * @param float $lat2 Latitude of second point in decimal degrees
     * @param float $lon2 Longitude of second point in decimal degrees
     * @param float $thresholdMeters Maximum distance in meters
     * @return bool True if points are within threshold
     */
    public static function withinThreshold(float $lat1, float $lon1, float $lat2, float $lon2, float $thresholdMeters = 100): bool
    {
        $distance = self::calculateInMeters($lat1, $lon1, $lat2, $lon2);
        return $distance <= $thresholdMeters;
    }
}
