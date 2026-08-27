<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            // Index pour les requêtes de recherche et filtrage
            $table->index(['status'], 'idx_places_status');
            $table->index(['added_by'], 'idx_places_added_by');
            $table->index(['created_at'], 'idx_places_created_at');
            $table->index(['status', 'created_at'], 'idx_places_status_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropIndex('idx_places_status');
            $table->dropIndex('idx_places_added_by');
            $table->dropIndex('idx_places_created_at');
            $table->dropIndex('idx_places_status_created');
        });
    }
};
