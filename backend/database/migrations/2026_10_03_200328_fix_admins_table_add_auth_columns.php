<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Fix: La table admins a été créée par une autre migration avec un schéma
     * basé sur user_id/role/permissions. La migration create_admins_table n'a
     * jamais ajouté les colonnes username/password car hasTable retournait true.
     * Cette migration ajoute les colonnes d'authentification manquantes.
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (!Schema::hasColumn('admins', 'username')) {
                $table->string('username')->unique()->nullable()->after('id');
            }
            if (!Schema::hasColumn('admins', 'password')) {
                $table->string('password')->nullable()->after('username');
            }
            if (!Schema::hasColumn('admins', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
            if (!Schema::hasColumn('admins', 'last_login_ip')) {
                $table->string('last_login_ip')->nullable();
            }
        });

        // Make user_id nullable — standalone admin auth doesn't require a linked user
        if (Schema::hasColumn('admins', 'user_id')) {
            \Illuminate\Support\Facades\DB::statement(
                'ALTER TABLE admins ALTER COLUMN user_id DROP NOT NULL'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $columns = [];
            foreach (['username', 'password', 'last_login_at', 'last_login_ip'] as $col) {
                if (Schema::hasColumn('admins', $col)) {
                    $columns[] = $col;
                }
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
