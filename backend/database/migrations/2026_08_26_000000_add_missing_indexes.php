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
            // Index pour login par username et tri par name
            $table->index('name', 'idx_users_name');
        });

        Schema::table('places', function (Blueprint $table) {
            // Index pour recherche par uuid et slug
            $table->index('uuid', 'idx_places_uuid');
            $table->index('slug', 'idx_places_slug');
        });

        Schema::table('conversations', function (Blueprint $table) {
            // Index pour récupérer les conversations d'un utilisateur
            $table->index('user_one_id', 'idx_conversations_user_one');
            $table->index('user_two_id', 'idx_conversations_user_two');
            // Index pour tri par dernier message
            $table->index('last_message_at', 'idx_conversations_last_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_name');
        });

        Schema::table('places', function (Blueprint $table) {
            $table->dropIndex('idx_places_uuid');
            $table->dropIndex('idx_places_slug');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('idx_conversations_user_one');
            $table->dropIndex('idx_conversations_user_two');
            $table->dropIndex('idx_conversations_last_message');
        });
    }
};
