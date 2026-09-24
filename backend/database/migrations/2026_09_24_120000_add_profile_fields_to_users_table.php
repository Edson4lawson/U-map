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
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable();
            }
            if (!Schema::hasColumn('users', 'faculty')) {
                $table->string('faculty')->nullable();
            }
            if (!Schema::hasColumn('users', 'study_level')) {
                $table->string('study_level')->nullable();
            }
            if (!Schema::hasColumn('users', 'student_id')) {
                $table->string('student_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'faculty', 'study_level', 'student_id']);
        });
    }
};
