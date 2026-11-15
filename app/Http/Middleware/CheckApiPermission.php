<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckApiPermission
{
    /**
     * Handle an incoming API request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $menuKey
     * @param  string|null  $action
     */
    public function handle(Request $request, Closure $next, string $menuKey, ?string $action = null): Response
    {
        $user = $request->user() ?: Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Owner & Super Admin have unrestricted access
        if ($user->isOwner() || $user->isAdmin()) {
            return $next($request);
        }

        // If not employee or employee profile missing
        if (!$user->isEmployee() || !$user->employee) {
            return response()->json([
                'status' => false,
                'message' => 'Akses ditolak. Profil karyawan tidak ditemukan.',
            ], 403);
        }

        // Determine action if not explicitly supplied
        if (!$action) {
            $actionMethod = $request->route() ? $request->route()->getActionMethod() : '';
            $routeName = $request->route() ? ($request->route()->getName() ?: '') : '';
            $uri = $request->path();
            $method = strtoupper($request->method());
            $uriSegments = explode('/', trim($uri, '/'));
            $lastSegment = end($uriSegments);

            // 1. EDIT / APPROVAL Actions
            if (
                in_array($actionMethod, ['update', 'edit', 'toggleStatus', 'markAsPaid', 'updateInstallment', 'updateBalance', 'updateCategory', 'updateAttendance', 'updateLoan', 'updatePayroll']) ||
                str_contains($actionMethod, 'approve') ||
                str_contains($actionMethod, 'reject') ||
                str_contains($actionMethod, 'toggle') ||
                str_contains($routeName, 'approve') ||
                str_contains($routeName, 'reject') ||
                str_contains($routeName, 'status') ||
                in_array($lastSegment, ['approve', 'reject', 'status', 'toggle-status', 'pay']) ||
                str_contains($uri, '/approve/') ||
                str_contains($uri, '/reject/') ||
                str_contains($uri, '/status/') ||
                str_contains($uri, '/pay/') ||
                in_array($method, ['PUT', 'PATCH']) ||
                ($method === 'POST' && is_numeric($lastSegment))
            ) {
                $action = 'edit';
            }
            // 2. CREATE Actions
            elseif (
                in_array($actionMethod, ['store', 'create', 'bulkStore', 'setup', 'storeCategory']) ||
                str_ends_with($routeName, '.create') ||
                str_ends_with($routeName, '.store') ||
                in_array($lastSegment, ['setup', 'bulk-store']) ||
                ($method === 'POST' && !is_numeric($lastSegment))
            ) {
                $action = 'create';
            }
            // 3. DELETE Actions
            elseif (
                $method === 'DELETE' ||
                in_array($actionMethod, ['destroy', 'delete', 'deleteRequest', 'deleteCategory']) ||
                str_contains($actionMethod, 'destroy') ||
                str_contains($actionMethod, 'delete') ||
                str_ends_with($routeName, '.destroy')
            ) {
                $action = 'delete';
            }
            // 4. Default: VIEW Actions
            else {
                $action = 'view';
            }
        }

        if (!$user->hasPermission($menuKey, $action)) {
            $actionLabels = [
                'view' => 'melihat',
                'create' => 'menambah',
                'edit' => 'mengubah',
                'delete' => 'menghapus',
            ];
            $label = $actionLabels[$action] ?? $action;

            return response()->json([
                'status' => false,
                'message' => "Anda tidak memiliki izin {$label} data untuk menu ini.",
            ], 403);
        }

        return $next($request);
    }
}
