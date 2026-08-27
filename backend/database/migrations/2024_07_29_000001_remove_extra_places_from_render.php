<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // No-op: this migration previously attempted to delete places using integer IDs
        // in a UUID column, which is invalid on PostgreSQL. Data cleanup was already
        // performed manually via SQL on Supabase. Nothing to do here.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be easily reversed as we don't have the original data
        // In production, you would restore from backup if needed
        throw new Exception('This migration cannot be reversed. Restore from backup if needed.');
    }
};
