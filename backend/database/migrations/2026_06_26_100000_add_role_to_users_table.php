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
            $table->enum('role', ['user', 'moderator', 'admin', 'super_admin'])
                  ->default('user')
                  ->after('is_restricted');
            
            // Index pour les requêtes par rôle
            $table->index('role', 'idx_users_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
            $table->dropColumn('role');
        });
    }
};
