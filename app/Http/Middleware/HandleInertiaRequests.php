<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user) {
            $user->loadMissing(['company', 'employee.company', 'employee.position']);
        }

        $company = $user ? ($user->company ?? $user->employee?->company) : null;
        $companyName = $company ? ($company->name_company ?? $company->name ?? null) : null;

        return [
            ...parent::share($request),
            'appName' => config('app.name', 'Frans HRIS'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar' => $user->avatar ?? $user->employee?->avatar,
                    'company_id' => $user->company_id ?? $company?->id,
                    'company' => $company ? [
                        'id' => $company->id,
                        'name' => $companyName,
                        'name_company' => $companyName,
                        'logo' => $company->logo ?? null,
                    ] : null,
                    'employee' => $user->employee ? [
                        'id' => $user->employee->id,
                        'nik' => $user->employee->nik ?? null,
                        'position' => $user->employee->position->name ?? null,
                        'department' => $user->employee->department ?? null,
                        'avatar' => $user->employee->avatar ?? null,
                    ] : null,
                ] : null,
                'unreadNotificationsCount' => $user ? $user->unreadNotifications()->count() : 0,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
