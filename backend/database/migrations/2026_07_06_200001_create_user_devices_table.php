<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('user_id')->constrained()->cascadeOnDelete();
            $blueprint->string('ip_address', 45)->nullable();
            $blueprint->text('user_agent')->nullable();
            $blueprint->string('device_name')->nullable();
            $blueprint->timestamp('last_active_at')->useCurrent();
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
