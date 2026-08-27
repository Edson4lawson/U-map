<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration de configuration pour MySQL/PostgreSQL
 * Cette migration prépare la base de données pour la production
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Configuration des engines et charset pour MySQL
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Optimiser les tables existantes pour MySQL
            DB::statement('ALTER TABLE users ENGINE=InnoDB ROW_FORMAT=COMPRESSED');
            DB::statement('ALTER TABLE messages ENGINE=InnoDB ROW_FORMAT=COMPRESSED');
            DB::statement('ALTER TABLE places ENGINE=InnoDB ROW_FORMAT=COMPRESSED');
            DB::statement('ALTER TABLE events ENGINE=InnoDB ROW_FORMAT=COMPRESSED');
            DB::statement('ALTER TABLE reports ENGINE=InnoDB ROW_FORMAT=COMPRESSED');
            
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback des changements si nécessaire
    }
};
