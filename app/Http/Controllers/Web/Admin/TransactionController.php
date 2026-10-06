<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Company;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $companies = Company::select('id', 'name_company', 'email')->get();

        // Sample real SaaS invoices derived from active tenants
        $transactions = [];
        $i = 1;
        foreach ($companies as $comp) {
            $transactions[] = [
                'id' => 'INV-2026' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'company_name' => $comp->name,
                'company_email' => $comp->email,
                'plan_name' => $i % 2 === 0 ? 'Professional Growth' : 'Starter Tier',
                'amount' => $i % 2 === 0 ? 599000 : 299000,
                'payment_method' => $i % 3 === 0 ? 'BCA Virtual Account' : ($i % 3 === 1 ? 'Mandiri Bill' : 'Credit Card'),
                'status' => $i === count($companies) ? 'pending' : 'paid',
                'paid_at' => now()->subDays($i * 3)->format('Y-m-d H:i'),
                'due_date' => now()->addDays(20 - $i)->format('Y-m-d'),
            ];
            $i++;
        }

        return Inertia::render('Admin/Transactions/Index', [
            'transactions' => $transactions,
        ]);
    }
}
