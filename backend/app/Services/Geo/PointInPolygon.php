<?php

namespace App\Services\Geo;

/**
 * Point-in-polygon algorithm using ray-casting method
 * Determines if a point is inside a polygon
 */
class PointInPolygon
{
    /**
     * Check if a point is inside a polygon using ray-casting algorithm
     *
     * @param float $lat Point latitude
     * @param float $lng Point longitude
     * @param array $polygon Array of [lat, lng] coordinates defining the polygon
     * @return bool True if point is inside polygon
     */
    public static function isPointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        if (count($polygon) < 3) {
            return false;
        }

        $inside = false;
        $j = count($polygon) - 1;

        for ($i = 0; $i < count($polygon); $i++) {
            $xi = $polygon[$i][1]; // longitude
            $yi = $polygon[$i][0]; // latitude
            $xj = $polygon[$j][1]; // longitude
            $yj = $polygon[$j][0]; // latitude

            $intersect = (($yi > $lat) != ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }

            $j = $i;
        }

        return $inside;
    }

    /**
     * Get the campus polygon coordinates from config
     *
     * @return array Array of [lat, lng] coordinates
     */
    public static function getCampusPolygon(): array
    {
        // Read polygon from config file
        $polygonCoords = config('campus_polygon.polygon', []);
        
        if (empty($polygonCoords)) {
            // Fallback to hardcoded coordinates if config is empty
            $polygonCoords = [
                [6.4230374, 2.3387855],
                [6.4229378, 2.338885],
                [6.4216166, 2.3424224],
                [6.4203826, 2.3467],
                [6.4206797, 2.3466299],
                [6.4124412, 2.3453902],
                [6.4087872, 2.3435561],
                [6.4087312, 2.3436309],
                [6.4099069, 2.3402295],
                [6.4109479, 2.339071],
                [6.411877, 2.3372926],
                [6.4118353, 2.3372709],
                [6.4231707, 2.3386968],
            ];
        }
        
        return $polygonCoords;
    }

    /**
     * Check if a point is inside the campus polygon
     *
     * @param float $lat Point latitude
     * @param float $lng Point longitude
     * @return bool True if point is inside campus
     */
    public static function isPointInCampus(float $lat, float $lng): bool
    {
        return self::isPointInPolygon($lat, $lng, self::getCampusPolygon());
    }
}
