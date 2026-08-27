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
        Schema::table('users', function (Blueprint $table) {
            // Index pour les requêtes de recherche et authentification
            $table->index(['email'], 'idx_users_email');
            $table->index(['is_restricted'], 'idx_users_restricted');
            $table->index(['study_status'], 'idx_users_study_status');
            $table->index(['created_at'], 'idx_users_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email');
            $table->dropIndex('idx_users_restricted');
            $table->dropIndex('idx_users_study_status');
            $table->dropIndex('idx_users_created_at');
        });
    }
};
