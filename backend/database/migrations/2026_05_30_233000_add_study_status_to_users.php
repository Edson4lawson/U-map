<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('study_status')->nullable(); // Ex: "Révise l'Algèbre Linéaire"
            $table->string('study_location')->nullable(); // Ex: "BU Centrale"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['study_status', 'study_location']);
        });
    }
};
