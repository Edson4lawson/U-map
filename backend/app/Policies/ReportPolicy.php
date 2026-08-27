<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any reports.
     */
    public function viewAny(User $user): bool
    {
        // Only moderators and above can view reports
        return $user->can('manage-reports');
    }

    /**
     * Determine whether the user can view the report.
     */
    public function view(User $user, Report $report): bool
    {
        // Reporter can view their own report
        if ($user->id === $report->reported_by) {
            return true;
        }

        // Moderators can view any report
        return $user->can('manage-reports');
    }

    /**
     * Determine whether the user can create reports.
     */
    public function create(User $user): bool
    {
        // All non-restricted users can create reports
        return !$user->is_restricted;
    }

    /**
     * Determine whether the user can update the report.
     */
    public function update(User $user, Report $report): bool
    {
        // Only moderators can update reports
        return $user->can('manage-reports');
    }

    /**
     * Determine whether the user can delete the report.
     */
    public function delete(User $user, Report $report): bool
    {
        // Reporter can delete their own report if not resolved
        if ($user->id === $report->reported_by && $report->status !== 'resolved') {
            return true;
        }

        // Admins can delete any report
        return $user->can('delete-any-content');
    }

    /**
     * Determine whether the user can resolve the report.
     */
    public function resolve(User $user, Report $report): bool
    {
        return $user->can('manage-reports');
    }

    /**
     * Determine whether the user can dismiss the report.
     */
    public function dismiss(User $user, Report $report): bool
    {
        return $user->can('manage-reports');
    }

    /**
     * Determine whether the user can restore the report.
     */
    public function restore(User $user, Report $report): bool
    {
        return $user->can('delete-any-content');
    }

    /**
     * Determine whether the user can permanently delete the report.
     */
    public function forceDelete(User $user, Report $report): bool
    {
        return $user->can('delete-any-content');
    }
}
