<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait ScopesDataByPermission
{
    /**
     * Get allowed branch IDs for the authenticated user (null means all / unrestricted).
     */
    protected function getAllowedBranchIds(): ?array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        return $user->getAllowedBranchIds();
    }

    /**
     * Get allowed division IDs for the authenticated user (null means all / unrestricted).
     */
    protected function getAllowedDivisionIds(): ?array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        return $user->getAllowedDivisionIds();
    }

    /**
     * Scope a query directly on models having branch_id and division_id (e.g. Employee, PendingEmployee).
     */
    protected function scopeBranchDivision(Builder $query, string $branchCol = 'branch_id', string $divisionCol = 'division_id'): Builder
    {
        $user = Auth::user();
        if (!$user || $user->isOwner() || $user->isAdmin()) {
            return $query;
        }

        $branchIds = $user->getAllowedBranchIds();
        if ($branchIds !== null) {
            $query->whereIn($branchCol, $branchIds);
        }

        $divisionIds = $user->getAllowedDivisionIds();
        if ($divisionIds !== null) {
            $query->whereIn($divisionCol, $divisionIds);
        }

        return $query;
    }

    /**
     * Scope a query for models that belong to an Employee (e.g. LeaveRequest, Overtime, Attendance, Loan, Reimbursement).
     */
    protected function scopeByEmployeeRelation(Builder $query, string $relation = 'employee'): Builder
    {
        $user = Auth::user();
        if (!$user || $user->isOwner() || $user->isAdmin()) {
            return $query;
        }

        $branchIds = $user->getAllowedBranchIds();
        $divisionIds = $user->getAllowedDivisionIds();

        if ($branchIds === null && $divisionIds === null) {
            return $query;
        }

        return $query->whereHas($relation, function ($q) use ($branchIds, $divisionIds) {
            if ($branchIds !== null) {
                $q->whereIn('branch_id', $branchIds);
            }
            if ($divisionIds !== null) {
                $q->whereIn('division_id', $divisionIds);
            }
        });
    }

    /**
     * Check if the authenticated user has access to a specific employee based on branch & division scoping.
     */
    protected function canAccessEmployee(?Employee $employee): bool
    {
        if (!$employee) {
            return false;
        }

        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Multi-tenant isolation: Must belong to user's company
        $companyId = $user->getCompany()?->id ?? null;
        if (!$companyId || $employee->company_id !== $companyId) {
            return false;
        }

        if ($user->isOwner() || $user->isAdmin()) {
            return true;
        }

        $allowedBranches = $user->getAllowedBranchIds();
        if ($allowedBranches !== null) {
            if (empty($employee->branch_id) || !in_array((int)$employee->branch_id, array_map('intval', $allowedBranches))) {
                return false;
            }
        }

        $allowedDivisions = $user->getAllowedDivisionIds();
        if ($allowedDivisions !== null) {
            if (empty($employee->division_id) || !in_array((int)$employee->division_id, array_map('intval', $allowedDivisions))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the authenticated user has access to a specific branch.
     */
    protected function canAccessBranch($branchId): bool
    {
        $user = Auth::user();
        if (!$user || $user->isOwner() || $user->isAdmin()) {
            return true;
        }

        $allowed = $user->getAllowedBranchIds();
        if ($allowed === null) {
            return true;
        }

        return !empty($branchId) && in_array((int)$branchId, array_map('intval', $allowed));
    }

    /**
     * Check if the authenticated user has access to a specific division.
     */
    protected function canAccessDivision($divisionId): bool
    {
        $user = Auth::user();
        if (!$user || $user->isOwner() || $user->isAdmin()) {
            return true;
        }

        $allowed = $user->getAllowedDivisionIds();
        if ($allowed === null) {
            return true;
        }

        return !empty($divisionId) && in_array((int)$divisionId, array_map('intval', $allowed));
    }

    /**
     * Get allowed branches list for dropdowns/filters.
     */
    protected function getAllowedBranches(?int $companyId = null)
    {
        $user = Auth::user();
        $cid = $companyId ?? ($user?->getCompany()?->id ?? null);
        $query = Branch::where('company_id', $cid)->orderBy('name');

        if ($user && !$user->isOwner() && !$user->isAdmin()) {
            $allowed = $user->getAllowedBranchIds();
            if ($allowed !== null) {
                $query->whereIn('id', $allowed);
            }
        }

        return $query->get(['id', 'name']);
    }

    /**
     * Get allowed divisions list for dropdowns/filters.
     */
    protected function getAllowedDivisions(?int $companyId = null)
    {
        $user = Auth::user();
        $cid = $companyId ?? ($user?->getCompany()?->id ?? null);
        $query = Division::where('company_id', $cid)->orderBy('name');

        if ($user && !$user->isOwner() && !$user->isAdmin()) {
            $allowed = $user->getAllowedDivisionIds();
            if ($allowed !== null) {
                $query->whereIn('id', $allowed);
            }
        }

        return $query->get(['id', 'name']);
    }
}
