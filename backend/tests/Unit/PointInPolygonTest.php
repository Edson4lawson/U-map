<?php

namespace Tests\Unit;

use App\Services\Geo\PointInPolygon;
use Tests\TestCase;

class PointInPolygonTest extends TestCase
{
    /**
     * Test point inside campus polygon
     */
    public function test_point_inside_campus()
    {
        // Point clearly inside the campus (Bibliothèque Centrale de L'UAC)
        $lat = 6.414879;
        $lng = 2.343011;
        
        $result = PointInPolygon::isPointInCampus($lat, $lng);
        
        $this->assertTrue($result, 'Point should be inside campus polygon');
    }

    /**
     * Test point outside campus polygon (Tchinangbégbo)
     */
    public function test_point_outside_campus_tchinangbego()
    {
        // Point clearly outside the campus (Tchinangbégbo area)
        $lat = 6.4000;
        $lng = 2.3200;
        
        $result = PointInPolygon::isPointInCampus($lat, $lng);
        
        $this->assertFalse($result, 'Point should be outside campus polygon');
    }

    /**
     * Test point outside campus polygon (Carrefour Béton Armé)
     */
    public function test_point_outside_campus_carrefour_beton_arme()
    {
        // Point clearly outside the campus (Carrefour Béton Armé area)
        $lat = 6.4300;
        $lng = 2.3600;
        
        $result = PointInPolygon::isPointInCampus($lat, $lng);
        
        $this->assertFalse($result, 'Point should be outside campus polygon');
    }

    /**
     * Test point on campus boundary edge
     */
    public function test_point_on_campus_boundary()
    {
        // Point on the campus boundary (one of the polygon vertices)
        $polygon = PointInPolygon::getCampusPolygon();
        $boundaryPoint = $polygon[0]; // First vertex
        
        $result = PointInPolygon::isPointInCampus($boundaryPoint[0], $boundaryPoint[1]);
        
        // Points on boundary should be considered inside
        $this->assertTrue($result, 'Point on boundary should be considered inside');
    }

    /**
     * Test point near campus center
     */
    public function test_point_near_campus_center()
    {
        // Point near the calculated center of campus
        $lat = 6.41595;
        $lng = 2.341985;
        
        $result = PointInPolygon::isPointInCampus($lat, $lng);
        
        $this->assertTrue($result, 'Point near campus center should be inside');
    }

    /**
     * Test custom polygon with simple square
     */
    public function test_custom_polygon_simple_square()
    {
        // Simple square polygon
        $polygon = [
            [0, 0],
            [0, 10],
            [10, 10],
            [10, 0],
        ];
        
        // Point inside square
        $insideResult = PointInPolygon::isPointInPolygon(5, 5, $polygon);
        $this->assertTrue($insideResult, 'Point (5,5) should be inside square');
        
        // Point outside square
        $outsideResult = PointInPolygon::isPointInPolygon(15, 15, $polygon);
        $this->assertFalse($outsideResult, 'Point (15,15) should be outside square');
    }

    /**
     * Test invalid polygon (less than 3 points)
     */
    public function test_invalid_polygon()
    {
        $invalidPolygon = [
            [0, 0],
            [10, 10],
        ];
        
        $result = PointInPolygon::isPointInPolygon(5, 5, $invalidPolygon);
        
        $this->assertFalse($result, 'Invalid polygon should return false');
    }

    /**
     * Test polygon with complex shape
     */
    public function test_complex_polygon()
    {
        // Complex polygon (L-shape)
        $polygon = [
            [0, 0],
            [0, 10],
            [5, 10],
            [5, 5],
            [10, 5],
            [10, 0],
        ];
        
        // Point inside L-shape
        $insideResult = PointInPolygon::isPointInPolygon(2, 8, $polygon);
        $this->assertTrue($insideResult, 'Point should be inside L-shape');
        
        // Point in the "hole" of L-shape
        $holeResult = PointInPolygon::isPointInPolygon(7, 8, $polygon);
        $this->assertFalse($holeResult, 'Point in L-shape hole should be outside');
    }
}
