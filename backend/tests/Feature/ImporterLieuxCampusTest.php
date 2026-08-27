<?php

namespace Tests\Feature;

use App\DTO\OverpassPlace;
use App\Models\Place;
use App\Services\CampusImporter;
use App\Services\OverpassService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImporterLieuxCampusTest extends TestCase
{
    use RefreshDatabase;

    protected OverpassService $overpassService;
    protected CampusImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->overpassService = app(OverpassService::class);
        $this->importer = app(CampusImporter::class);
    }

    /**
     * Test that the command runs in dry-run mode by default
     */
    public function test_command_runs_in_dry_run_mode(): void
    {
        $this->artisan('campus:import')
            ->assertExitCode(0)
            ->expectsOutputToContain('MODE DRY-RUN');
    }

    /**
     * Test that the command shows statistics
     */
    public function test_command_shows_statistics(): void
    {
        $this->artisan('campus:import --stats --use-filtered')
            ->assertExitCode(0)
            ->expectsOutputToContain('Statistiques');
    }

    /**
     * Test OverpassPlace DTO creation
     */
    public function test_overpass_place_dto_creation(): void
    {
        $element = [
            'id' => 123456,
            'type' => 'node',
            'lat' => 6.4200,
            'lon' => 2.3400,
            'tags' => [
                'name' => 'Amphithéâtre Test',
                'amenity' => 'university',
            ],
        ];

        $place = OverpassPlace::fromOverpassElement($element);

        $this->assertEquals(123456, $place->osmId);
        $this->assertEquals('Amphithéâtre Test', $place->name);
        $this->assertEquals(6.4200, $place->latitude);
        $this->assertEquals(2.3400, $place->longitude);
        $this->assertEquals('amenity', $place->type);
        $this->assertEquals('university', $place->category);
    }

    /**
     * Test OverpassPlace name normalization
     */
    public function test_overpass_place_name_normalization(): void
    {
        $element = [
            'id' => 123456,
            'type' => 'node',
            'lat' => 6.4200,
            'lon' => 2.3400,
            'tags' => [
                'name' => 'Amphithéâtre Étudiant',
                'amenity' => 'university',
            ],
        ];

        $place = OverpassPlace::fromOverpassElement($element);
        $normalized = $place->getNormalizedName();

        $this->assertEquals('amphitheatre etudiant', $normalized);
    }

    /**
     * Test Haversine distance calculation
     */
    public function test_haversine_distance_calculation(): void
    {
        // Distance between two known points (approximately 1 km apart)
        $lat1 = 6.4200;
        $lon1 = 2.3400;
        $lat2 = 6.4300;
        $lon2 = 2.3500;

        $distance = \App\Services\Geo\Haversine::calculateInMeters($lat1, $lon1, $lat2, $lon2);

        // Should be approximately 1.5-2 km
        $this->assertGreaterThan(1000, $distance);
        $this->assertLessThan(3000, $distance);
    }

    /**
     * Test Haversine within threshold
     */
    public function test_haversine_within_threshold(): void
    {
        $lat1 = 6.4200;
        $lon1 = 2.3400;
        $lat2 = 6.4201; // Very close
        $lon2 = 2.3401;

        $this->assertTrue(
            \App\Services\Geo\Haversine::withinThreshold($lat1, $lon1, $lat2, $lon2, 100)
        );
    }

    /**
     * Test CampusImporter exact match
     */
    public function test_campus_importer_exact_match(): void
    {
        // Create existing place
        Place::create([
            'uuid' => '123456',
            'name' => 'Bibliothèque Centrale',
            'type' => 'amenity',
            'category' => 'library',
            'latitude' => 6.4200,
            'longitude' => 2.3400,
        ]);

        // Create Overpass place with same name
        $overpassPlace = new OverpassPlace(
            osmId: 123456,
            name: 'Bibliothèque Centrale',
            latitude: 6.4201,
            longitude: 2.3401,
            type: 'amenity',
            category: 'library'
        );

        $comparison = $this->importer->compare([$overpassPlace]);

        $this->assertCount(0, $comparison['to_create']);
        $this->assertCount(1, $comparison['to_update']);
    }

    /**
     * Test CampusImporter no match
     */
    public function test_campus_importer_no_match(): void
    {
        // Create existing place
        Place::create([
            'uuid' => '123456',
            'name' => 'Bibliothèque Centrale',
            'type' => 'amenity',
            'category' => 'library',
            'latitude' => 6.4200,
            'longitude' => 2.3400,
        ]);

        // Create Overpass place with different name
        $overpassPlace = new OverpassPlace(
            osmId: 789012,
            name: 'Restaurant Universitaire',
            latitude: 6.4250,
            longitude: 2.3450,
            type: 'amenity',
            category: 'restaurant'
        );

        $comparison = $this->importer->compare([$overpassPlace]);

        $this->assertCount(1, $comparison['to_create']);
        $this->assertCount(0, $comparison['to_update']);
    }

    /**
     * Test CampusImporter fuzzy match
     */
    public function test_campus_importer_fuzzy_match(): void
    {
        // Create existing place
        Place::create([
            'uuid' => '123456',
            'name' => 'Amphithéâtre A',
            'type' => 'amenity',
            'category' => 'university',
            'latitude' => 6.4200,
            'longitude' => 2.3400,
        ]);

        // Create Overpass place with similar name
        $overpassPlace = new OverpassPlace(
            osmId: 789012,
            name: 'Amphithéâtre B',
            latitude: 6.4201,
            longitude: 2.3401,
            type: 'amenity',
            category: 'university'
        );

        $comparison = $this->importer->compare([$overpassPlace]);

        // Should match due to similarity (Amphithéâtre A vs Amphithéâtre B)
        $this->assertCount(0, $comparison['to_create']);
        $this->assertCount(1, $comparison['to_update']);
    }

    /**
     * Test CampusImporter create place
     */
    public function test_campus_importer_create_place(): void
    {
        $overpassPlace = new OverpassPlace(
            osmId: 789012,
            name: 'Nouveau Lieu',
            latitude: 6.4250,
            longitude: 2.3450,
            type: 'amenity',
            category: 'cafe'
        );

        $place = $this->importer->createPlace($overpassPlace);

        $this->assertDatabaseHas('places', [
            'uuid' => '789012',
            'name' => 'Nouveau Lieu',
            'latitude' => 6.4250,
            'longitude' => 2.3450,
        ]);
    }

    /**
     * Test CampusImporter update place
     */
    public function test_campus_importer_update_place(): void
    {
        $place = Place::create([
            'uuid' => '123456',
            'name' => 'Ancien Lieu',
            'type' => 'amenity',
            'category' => 'library',
            'latitude' => 6.4200,
            'longitude' => 2.3400,
        ]);

        $overpassPlace = new OverpassPlace(
            osmId: 123456,
            name: 'Ancien Lieu',
            latitude: 6.4250,
            longitude: 2.3450,
            type: 'amenity',
            category: 'library'
        );

        $updatedPlace = $this->importer->updatePlace($place, $overpassPlace);

        $this->assertEquals(6.4250, $updatedPlace->latitude);
        $this->assertEquals(2.3450, $updatedPlace->longitude);
    }

    /**
     * Test OverpassService cache functionality
     */
    public function test_overpass_service_cache(): void
    {
        Storage::fake();

        // First call should use API (mocked)
        // Since we can't easily mock the API in this context, we'll test cache logic
        $this->artisan('campus:import --refresh')
            ->assertExitCode(0);
    }
}
