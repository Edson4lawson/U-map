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
        // 1. Add moderation columns to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'muted_until')) {
                $table->timestamp('muted_until')->nullable()->after('is_restricted');
            }
            if (!Schema::hasColumn('users', 'suspended_until')) {
                $table->timestamp('suspended_until')->nullable()->after('muted_until');
            }
            if (!Schema::hasColumn('users', 'is_banned')) {
                $table->boolean('is_banned')->default(false)->after('suspended_until');
            }
            if (!Schema::hasColumn('users', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('is_banned');
            }
        });

        // 2. Enhance reports table
        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'reportable_type')) {
                $table->string('reportable_type', 50)->default('user')->after('reported_user_id');
            }
            if (!Schema::hasColumn('reports', 'reportable_id')) {
                $table->unsignedBigInteger('reportable_id')->nullable()->after('reportable_type');
            }
            if (!Schema::hasColumn('reports', 'priority')) {
                $table->string('priority', 20)->default('medium')->after('reason');
            }
            if (!Schema::hasColumn('reports', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('reports', 'resolved_by')) {
                $table->unsignedBigInteger('resolved_by')->nullable()->after('admin_notes');
            }
            if (!Schema::hasColumn('reports', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('resolved_by');
            }
        });

        // 3. Create user_sanctions table
        if (!Schema::hasTable('user_sanctions')) {
            Schema::create('user_sanctions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('type', 30); // warning, mute, suspension, ban
                $table->text('reason');
                $table->integer('duration_hours')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['user_id', 'is_active']);
                $table->index('type');
            });
        }

        // 4. Create admin_actions audit log table
        if (!Schema::hasTable('admin_actions')) {
            Schema::create('admin_actions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('action', 50); // warn_user, mute_user, suspend_user, ban_user, delete_message, resolve_report, etc.
                $table->string('target_type', 50)->nullable(); // user, message, report, place
                $table->unsignedBigInteger('target_id')->nullable();
                $table->json('details')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index(['target_type', 'target_id']);
                $table->index('action');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
        Schema::dropIfExists('user_sanctions');

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['reportable_type', 'reportable_id', 'priority', 'admin_notes', 'resolved_by', 'resolved_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['muted_until', 'suspended_until', 'is_banned', 'admin_note']);
        });
    }
};
