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
        // 1. Ensure `users` table has all required columns
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('student');
            }
            if (!Schema::hasColumn('users', 'is_restricted')) {
                $table->boolean('is_restricted')->default(false);
            }
            if (!Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable();
            }
            if (!Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable();
            }
            if (!Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'social_provider')) {
                $table->string('social_provider')->nullable();
            }
            if (!Schema::hasColumn('users', 'social_id')) {
                $table->string('social_id')->nullable();
            }
            if (!Schema::hasColumn('users', 'study_status')) {
                $table->string('study_status')->nullable();
            }
            if (!Schema::hasColumn('users', 'study_location')) {
                $table->string('study_location')->nullable();
            }
        });

        // 2. Ensure `messages` table has encrypted_content and is_encrypted
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'encrypted_content')) {
                $table->text('encrypted_content')->nullable();
            }
            if (!Schema::hasColumn('messages', 'is_encrypted')) {
                $table->boolean('is_encrypted')->default(true);
            }
        });

        // 3. Fix `conversations` table columns if it was created with old schema
        if (Schema::hasTable('conversations')) {
            if (!Schema::hasColumn('conversations', 'user_one_id')) {
                Schema::table('conversations', function (Blueprint $table) {
                    $table->foreignId('user_one_id')->nullable()->constrained('users')->onDelete('cascade');
                    $table->foreignId('user_two_id')->nullable()->constrained('users')->onDelete('cascade');
                    $table->timestamp('last_message_at')->nullable();
                });
            }
        } else {
            Schema::create('conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_one_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('user_two_id')->constrained('users')->onDelete('cascade');
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();

                $table->unique(['user_one_id', 'user_two_id'], 'unique_user_conversation');
                $table->index(['user_one_id', 'last_message_at'], 'idx_user_one_last_msg');
                $table->index(['user_two_id', 'last_message_at'], 'idx_user_two_last_msg');
            });
        }

        // 4. Ensure `reports` table exists
        if (!Schema::hasTable('reports')) {
            Schema::create('reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('reported_user_id')->constrained('users')->onDelete('cascade');
                $table->text('reason');
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // 5. Ensure `audit_logs` table exists
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('action');
                $table->string('resource_type')->nullable();
                $table->unsignedBigInteger('resource_id')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->json('payload')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('status')->default('success');
                $table->text('error_message')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
