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
            // Index composite pour les conversations (sender_id, receiver_id, created_at)
            $table->index(['sender_id', 'receiver_id', 'created_at'], 'idx_conversation_timestamp');
            
            // Index pour la lecture des messages non lus
            $table->index(['receiver_id', 'is_read', 'created_at'], 'idx_unread_messages');
            
            // Index pour l'expiration des messages (cleanup)
            $table->index(['created_at'], 'idx_message_expiration');
            
            // Index composite pour sender_id + created_at
            $table->index(['sender_id', 'created_at'], 'idx_sender_timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('idx_conversation_timestamp');
            $table->dropIndex('idx_unread_messages');
            $table->dropIndex('idx_message_expiration');
            $table->dropIndex('idx_sender_timestamp');
        });
    }
};
