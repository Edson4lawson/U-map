<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $blueprint) {
            $blueprint->text('two_factor_secret')->nullable();
            $blueprint->text('two_factor_recovery_codes')->nullable();
            $blueprint->timestamp('two_factor_confirmed_at')->nullable();
            $blueprint->string('social_provider')->nullable();
            $blueprint->string('social_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $blueprint) {
            $blueprint->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'social_provider',
                'social_id',
            ]);
        });
    }
};
