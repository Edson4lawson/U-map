<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Force change added_by from bigint to varchar if it exists
        if (Schema::hasColumn('places', 'added_by')) {
            DB::statement('ALTER TABLE places ALTER COLUMN added_by TYPE VARCHAR(255)');
        } else {
            Schema::table('places', function (Blueprint $table) {
                $table->string('added_by')->nullable()->after('longitude');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            //
        });
    }
};
