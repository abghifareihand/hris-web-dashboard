<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $totalCompanies = Company::count();
        $totalEmployees = Employee::count();
        $totalOwners = User::where('role', 'owner')->count();
        $activeEmployees = Employee::where('is_active', true)->count();

        // Recent companies
        $recentCompanies = Company::with('owner:id,name,email')
            ->latest()
            ->take(5)
            ->get();

        $stats = [
            'total_companies' => $totalCompanies,
            'total_employees' => $totalEmployees,
            'total_owners' => $totalOwners,
            'active_employees' => $activeEmployees,
            'active_subscriptions' => $totalCompanies, // All registered companies currently on active plan
            'monthly_revenue' => $totalCompanies * 499000, // Estimated aggregate ARR/MRR in IDR
        ];

        return Inertia::render('Admin/Dashboard/Index', [
            'stats' => $stats,
            'recentCompanies' => $recentCompanies,
        ]);
    }
}
