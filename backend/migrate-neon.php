<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Running migrations without transactions for Neon...\n";

// Disable schema builder transactions
Schema::disableForeignKeyConstraints();

$migrations = glob(__DIR__.'/database/migrations/*.php');
sort($migrations);

foreach ($migrations as $migrationFile) {
    $migrationName = basename($migrationFile, '.php');
    echo "Processing: $migrationName\n";
    
    require_once $migrationFile;
    
    $className = explode('_', $migrationName);
    $className = array_slice($className, 4);
    $className = implode('_', $className);
    $className = str_replace('_', ' ', $className);
    $className = ucwords($className);
    $className = str_replace(' ', '', $className);
    
    if (class_exists($className)) {
        $migration = new $className();
        try {
            DB::beginTransaction();
            $migration->up();
            DB::commit();
            echo "✓ $migrationName completed\n";
        } catch (\Exception $e) {
            DB::rollBack();
            echo "✗ $migrationName failed: " . $e->getMessage() . "\n";
        }
    }
}

Schema::enableForeignKeyConstraints();

echo "\nAll migrations completed.\n";
