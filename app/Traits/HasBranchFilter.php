<?php

namespace App\Traits;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

trait HasBranchFilter
{
    /**
     * Determine if the authenticated user has unrestricted access across all branches.
     */
    protected function canAccessAllBranches(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // Cashiers are strictly branch-scoped
        if ($user->hasRole('cashier')) {
            return false;
        }

        // Admins, owners, managers without employee branch or with admin role can access all branches
        if ($user->hasRole('admin') || $user->hasRole('owner') || $user->hasRole('super-admin')) {
            return true;
        }

        // Non-admins with an assigned employee branch are scoped to their branch
        if ($user->employee?->branch_id) {
            return false;
        }

        return true;
    }

    /**
     * Get the effective branch ID to filter by.
     * Cashier/branch-scoped users CANNOT override their branch.
     *
     * @param  mixed  $requestedBranchId
     * @return int|null null means all branches
     */
    protected function getEffectiveBranchId($requestedBranchId = null): ?int
    {
        $user = Auth::user();

        if (! $this->canAccessAllBranches()) {
            return $user?->employee?->branch_id ? (int) $user->employee->branch_id : null;
        }

        if ($requestedBranchId !== null && $requestedBranchId !== '' && $requestedBranchId !== 'all') {
            return (int) $requestedBranchId;
        }

        return null;
    }

    /**
     * Get the branches available for selection in the UI.
     */
    protected function getAvailableBranches(): Collection
    {
        $user = Auth::user();

        if (! $this->canAccessAllBranches()) {
            $branchId = $user?->employee?->branch_id;
            if ($branchId) {
                return Branch::where('id', $branchId)->get(['id', 'name']);
            }

            return new Collection;
        }

        return Branch::where('status', 'active')->get(['id', 'name']);
    }
}
