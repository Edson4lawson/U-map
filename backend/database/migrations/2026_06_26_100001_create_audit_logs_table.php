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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action'); // create, update, delete, view, login, logout, etc.
            $table->string('resource_type')->nullable(); // Message, User, Place, etc.
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('payload')->nullable(); // Sanitized request data
            $table->json('old_values')->nullable(); // For updates
            $table->json('new_values')->nullable(); // For updates
            $table->string('status')->default('success'); // success, failure, blocked
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes for performance
            $table->index(['user_id', 'created_at'], 'idx_user_created');
            $table->index(['resource_type', 'resource_id'], 'idx_resource');
            $table->index(['action', 'created_at'], 'idx_action_created');
            $table->index(['ip_address'], 'idx_ip_address');
            $table->index(['created_at'], 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
