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
        Schema::table('messages', function (Blueprint $table) {
            // Supprimer l'index simple existant
            $table->dropIndex(['created_at']);
            
            // Index composites optimisés pour les requêtes de messagerie
            $table->index(['sender_id', 'receiver_id', 'created_at'], 'idx_sender_receiver_created');
            $table->index(['receiver_id', 'is_read', 'created_at'], 'idx_receiver_read_created');
            $table->index(['created_at'], 'idx_created_at_cleanup');
            
            // Index pour les conversations
            $table->index(['sender_id', 'created_at'], 'idx_sender_created');
            $table->index(['receiver_id', 'created_at'], 'idx_receiver_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('idx_sender_receiver_created');
            $table->dropIndex('idx_receiver_read_created');
            $table->dropIndex('idx_created_at_cleanup');
            $table->dropIndex('idx_sender_created');
            $table->dropIndex('idx_receiver_created');
            
            // Restaurer l'index simple
            $table->index('created_at');
        });
    }
};
