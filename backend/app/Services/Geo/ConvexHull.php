<?php

namespace App\Services\Geo;

/**
 * Convex Hull algorithm (Graham scan) for finding the smallest convex polygon
 * that contains all points
 */
class ConvexHull
{
    /**
     * Calculate convex hull of points using Graham scan algorithm
     *
     * @param array $points Array of [lat, lng] coordinates
     * @return array Array of [lat, lng] coordinates forming the convex hull
     */
    public static function calculate(array $points): array
    {
        if (count($points) < 3) {
            return $points;
        }

        // Remove duplicate points
        $uniquePoints = array_unique($points, SORT_REGULAR);
        $points = array_values($uniquePoints);

        if (count($points) < 3) {
            return $points;
        }

        // Sort points by latitude (y-coordinate)
        usort($points, function ($a, $b) {
            return $a[0] <=> $b[0];
        });

        // Build lower hull
        $lower = [];
        foreach ($points as $point) {
            while (count($lower) >= 2 && self::crossProduct(
                $lower[count($lower) - 2],
                $lower[count($lower) - 1],
                $point
            ) <= 0) {
                array_pop($lower);
            }
            $lower[] = $point;
        }

        // Build upper hull
        $upper = [];
        foreach (array_reverse($points) as $point) {
            while (count($upper) >= 2 && self::crossProduct(
                $upper[count($upper) - 2],
                $upper[count($upper) - 1],
                $point
            ) <= 0) {
                array_pop($upper);
            }
            $upper[] = $point;
        }

        // Remove last point of each half because it's repeated
        array_pop($lower);
        array_pop($upper);

        // Concatenate hulls
        $hull = array_merge($lower, $upper);

        return $hull;
    }

    /**
     * Calculate cross product of vectors OA and OB
     * Returns positive if O->A->B is counter-clockwise, negative if clockwise
     *
     * @param array $o Origin point [lat, lng]
     * @param array $a Point A [lat, lng]
     * @param array $b Point B [lat, lng]
     * @return float Cross product
     */
    protected static function crossProduct(array $o, array $a, array $b): float
    {
        return ($a[1] - $o[1]) * ($b[0] - $o[0]) - ($a[0] - $o[0]) * ($b[1] - $o[1]);
    }

    /**
     * Add margin buffer around convex hull
     *
     * @param array $hull Convex hull points
     * @param float $marginMeters Margin in meters
     * @return array Expanded hull points
     */
    public static function addMargin(array $hull, float $marginMeters = 150): array
    {
        if (count($hull) < 3) {
            return $hull;
        }

        // Calculate centroid
        $centroid = self::calculateCentroid($hull);

        // Expand each point away from centroid
        $expanded = [];
        foreach ($hull as $point) {
            $expanded[] = self::expandPoint($centroid, $point, $marginMeters);
        }

        return $expanded;
    }

    /**
     * Calculate centroid of points
     *
     * @param array $points Array of [lat, lng] coordinates
     * @return array [lat, lng] centroid
     */
    public static function calculateCentroid(array $points): array
    {
        if (empty($points)) {
            return [0, 0];
        }

        $sumLat = 0;
        $sumLng = 0;
        foreach ($points as $point) {
            $sumLat += $point[0];
            $sumLng += $point[1];
        }

        return [
            $sumLat / count($points),
            $sumLng / count($points),
        ];
    }

    /**
     * Expand a point away from a reference point by a given distance in meters
     *
     * @param array $reference Reference point [lat, lng]
     * @param array $point Point to expand [lat, lng]
     * @param float $distanceMeters Distance to expand in meters
     * @return array Expanded point [lat, lng]
     */
    protected static function expandPoint(array $reference, array $point, float $distanceMeters): array
    {
        // Calculate direction vector
        $dLat = $point[0] - $reference[0];
        $dLng = $point[1] - $reference[1];

        // Calculate current distance
        $currentDistance = Haversine::calculateInMeters(
            $reference[0],
            $reference[1],
            $point[0],
            $point[1]
        );

        if ($currentDistance == 0) {
            return $point;
        }

        // Calculate expansion factor
        $expansionFactor = ($currentDistance + $distanceMeters) / $currentDistance;

        // Expand point
        return [
            $reference[0] + $dLat * $expansionFactor,
            $reference[1] + $dLng * $expansionFactor,
        ];
    }
}
