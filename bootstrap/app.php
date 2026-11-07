<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        $middleware->redirectUsersTo(function (Request $request) {
            if (\Illuminate\Support\Facades\Auth::check()) {
                $role = \Illuminate\Support\Facades\Auth::user()->role;
                if ($role === 'admin') {
                    return route('admin.dashboard');
                } elseif ($role === 'owner') {
                    return route('owner.dashboard');
                } elseif ($role === 'employee') {
                    return route('employee.dashboard');
                }
            }
            return '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Standardize API Validation Error (422) -> { status: false, message: ... }
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if ($request->is('api/*') || ($request->expectsJson() && !$request->header('X-Inertia'))) {
                return response()->json([
                    'status' => false,
                    'message' => $e->validator->errors()->first(),
                ], 422);
            }
        });

        // Gracefully handle 404 & ModelNotFound for Inertia requests & APIs
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || ($request->expectsJson() && !$request->header('X-Inertia'))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan.',
                ], 404);
            }

            if ($request->header('X-Inertia')) {
                $prevUrl = url()->previous();
                $currUrl = $request->fullUrl();

                if ($prevUrl && $prevUrl !== $currUrl) {
                    return redirect($prevUrl)->with('error', 'Data yang Anda akses tidak ditemukan atau telah dihapus.');
                }

                $role = $request->user()?->role;
                $fallbackRoute = ($role === 'owner') ? 'owner.dashboard' : (($role === 'admin') ? 'admin.dashboard' : 'employee.dashboard');
                return redirect()->route($fallbackRoute)->with('error', 'Data yang Anda akses tidak ditemukan atau telah dihapus.');
            }
        });
    })->create();
