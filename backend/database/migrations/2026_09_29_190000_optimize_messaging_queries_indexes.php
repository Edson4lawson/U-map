<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Optimisation profonde des index pour le système de messagerie.
 *
 * Analyse des requêtes chaudes :
 * 1. getMessages()        : WHERE (sender=A AND receiver=B) OR (sender=B AND receiver=A) AND created_at >= ?
 * 2. mark as read         : WHERE sender=X AND receiver=Y AND is_read=false → UPDATE
 * 3. fetchUnreadCounts()  : WHERE receiver=Y AND is_read=false AND created_at >= ? GROUP BY sender
 * 4. fetchLatestMessages(): WHERE created_at >= ? AND (sender=Y AND receiver IN(...)) OR (sender IN(...) AND receiver=Y)
 * 5. getConversations()   : WHERE user_one_id=X OR user_two_id=X ORDER BY last_message_at
 * 6. syncLegacy           : WHERE sender=X OR receiver=X GROUP BY partner
 * 7. getUnreadCount()     : WHERE receiver=X AND is_read=false AND created_at >= ?
 * 8. studyBuddies()       : WHERE study_status IS NOT NULL AND study_status != ''
 * 9. CleanupJob           : WHERE created_at < ? ORDER BY created_at
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── messages ──────────────────────────────────────────────────────
        Schema::table('messages', function (Blueprint $table) {
            // Index couvrant pour la requête conversation inversée (receiver_id, sender_id, created_at)
            // getMessages() utilise un OR entre (sender=A,receiver=B) et (sender=B,receiver=A)
            // L'index existant idx_sender_receiver_created couvre le premier cas
            // Celui-ci couvre le second cas + mark-as-read + fetchUnreadCounts
            try {
                $table->index(
                    ['receiver_id', 'sender_id', 'is_read', 'created_at'],
                    'idx_receiver_sender_read_created'
                );
            } catch (\Throwable $e) {}

            // Index pour le nettoyage (CleanupExpiredMessages) — created_at seul avec id pour chunkById
            // idx_created_at_cleanup et idx_message_expiration existent déjà mais sont simples
            // Pas besoin d'en rajouter ici
        });

        // ── conversations ─────────────────────────────────────────────────
        // L'index composite (user_one_id, user_two_id) existe via la contrainte unique.
        // Mais getConversations() fait WHERE user_one_id=X OR user_two_id=X ORDER BY last_message_at
        // Il faut un index composite sur chaque côté + last_message_at pour le tri.
        // Les idx_user_one_last_msg et idx_user_two_last_msg existent déjà dans la création table.
        // Mais ajoutons updated_at au tri secondaire :
        Schema::table('conversations', function (Blueprint $table) {
            try {
                $table->index(
                    ['user_one_id', 'last_message_at', 'updated_at'],
                    'idx_conv_user_one_sort'
                );
            } catch (\Throwable $e) {}

            try {
                $table->index(
                    ['user_two_id', 'last_message_at', 'updated_at'],
                    'idx_conv_user_two_sort'
                );
            } catch (\Throwable $e) {}
        });

        // ── users ─────────────────────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            // studyBuddies() : WHERE study_status IS NOT NULL AND != ''
            try {
                $table->index('study_status', 'idx_users_study_status');
            } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            try { $table->dropIndex('idx_receiver_sender_read_created'); } catch (\Throwable $e) {}
        });

        Schema::table('conversations', function (Blueprint $table) {
            try { $table->dropIndex('idx_conv_user_one_sort'); } catch (\Throwable $e) {}
            try { $table->dropIndex('idx_conv_user_two_sort'); } catch (\Throwable $e) {}
        });

        Schema::table('users', function (Blueprint $table) {
            try { $table->dropIndex('idx_users_study_status'); } catch (\Throwable $e) {}
        });
    }
};
