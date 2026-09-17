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
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->index('name', 'idx_users_name');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('places', function (Blueprint $table) {
                $table->index('uuid', 'idx_places_uuid');
                $table->index('slug', 'idx_places_slug');
            });
        } catch (\Throwable $e) {}

        if (Schema::hasTable('conversations')) {
            try {
                Schema::table('conversations', function (Blueprint $table) {
                    $table->index('user_one_id', 'idx_conversations_user_one');
                    $table->index('user_two_id', 'idx_conversations_user_two');
                    $table->index('last_message_at', 'idx_conversations_last_message');
                });
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_name');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('places', function (Blueprint $table) {
                $table->dropIndex('idx_places_uuid');
                $table->dropIndex('idx_places_slug');
            });
        } catch (\Throwable $e) {}

        if (Schema::hasTable('conversations')) {
            try {
                Schema::table('conversations', function (Blueprint $table) {
                    $table->dropIndex('idx_conversations_user_one');
                    $table->dropIndex('idx_conversations_user_two');
                    $table->dropIndex('idx_conversations_last_message');
                });
            } catch (\Throwable $e) {}
        }
    }
};
