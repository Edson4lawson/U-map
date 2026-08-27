<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Enums\UserRole;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        'App\Models\Message' => 'App\Policies\MessagePolicy',
        'App\Models\Place' => 'App\Policies\PlacePolicy',
        'App\Models\User' => 'App\Policies\UserPolicy',
        'App\Models\Report' => 'App\Policies\ReportPolicy',
        'App\Models\Event' => 'App\Policies\EventPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Gate: Is Admin
        Gate::define('is-admin', function ($user) {
            return $user->role === UserRole::ADMIN->value || $user->role === UserRole::SUPER_ADMIN->value;
        });

        // Gate: Is Moderator
        Gate::define('is-moderator', function ($user) {
            return in_array($user->role, [
                UserRole::MODERATOR->value,
                UserRole::ADMIN->value,
                UserRole::SUPER_ADMIN->value
            ]);
        });

        // Gate: Is Super Admin
        Gate::define('is-super-admin', function ($user) {
            return $user->role === UserRole::SUPER_ADMIN->value;
        });

        // Gate: Manage Users
        Gate::define('manage-users', function ($user) {
            return in_array($user->role, [
                UserRole::ADMIN->value,
                UserRole::SUPER_ADMIN->value
            ]);
        });

        // Gate: Manage Places
        Gate::define('manage-places', function ($user) {
            return in_array($user->role, [
                UserRole::MODERATOR->value,
                UserRole::ADMIN->value,
                UserRole::SUPER_ADMIN->value
            ]);
        });

        // Gate: Manage Reports
        Gate::define('manage-reports', function ($user) {
            return in_array($user->role, [
                UserRole::MODERATOR->value,
                UserRole::ADMIN->value,
                UserRole::SUPER_ADMIN->value
            ]);
        });

        // Gate: View Audit Logs
        Gate::define('view-audit-logs', function ($user) {
            return in_array($user->role, [
                UserRole::ADMIN->value,
                UserRole::SUPER_ADMIN->value
            ]);
        });

        // Gate: Delete Any Content
        Gate::define('delete-any-content', function ($user) {
            return in_array($user->role, [
                UserRole::ADMIN->value,
                UserRole::SUPER_ADMIN->value
            ]);
        });

        // Gate: Impersonate Users (Super Admin only)
        Gate::define('impersonate-users', function ($user) {
            return $user->role === UserRole::SUPER_ADMIN->value;
        });

        // Gate: Manage System Settings
        Gate::define('manage-system', function ($user) {
            return $user->role === UserRole::SUPER_ADMIN->value;
        });
    }
}
