<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckWebPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $menuKey
     * @param  string|null  $action
     */
    public function handle(Request $request, Closure $next, string $menuKey, ?string $action = null): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Owner & Super Admin have full access
        if ($user->isOwner() || $user->isAdmin()) {
            return $next($request);
        }

        // If not employee or employee profile missing
        if (!$user->isEmployee() || !$user->employee) {
            abort(403, 'Akses ditolak. Profil karyawan tidak ditemukan.');
        }

        // Determine action if not explicitly supplied
        if (!$action) {
            $routeName = $request->route() ? $request->route()->getName() : '';
            $method = strtoupper($request->method());

            if (str_contains($routeName, 'approve') || str_contains($routeName, 'reject') || str_contains($routeName, 'status')) {
                $action = 'edit';
            } elseif (str_ends_with($routeName, '.create') || str_ends_with($routeName, '.store') || $method === 'POST') {
                $action = 'create';
            } elseif (str_ends_with($routeName, '.edit') || str_ends_with($routeName, '.update') || in_array($method, ['PUT', 'PATCH'])) {
                $action = 'edit';
            } elseif ($method === 'DELETE' || str_ends_with($routeName, '.destroy')) {
                $action = 'delete';
            } else {
                $action = 'view';
            }
        }

        if (!$user->hasPermission($menuKey, $action, 'web')) {
            if ($request->expectsJson() || $request->header('X-Inertia')) {
                abort(403, "Anda tidak memiliki izin ({$action}) untuk menu ini.");
            }

            return redirect()->route('owner.dashboard')->with('error', 'Anda tidak memiliki hak akses untuk membuka menu tersebut.');
        }

        return $next($request);
    }
}
