<?php

namespace App\Http\Controllers\Web\Owner;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeedbackController extends Controller
{
    /**
     * Display a listing of employee feedbacks.
     */
    public function index(Request $request)
    {
        $company = $request->user()?->company ?? auth()->user()?->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $query = Feedback::with([
            'employee.user:id,name,email,avatar',
            'employee.division:id,name',
            'employee.position:id,name',
            'employee.branch:id,name',
        ])->where('company_id', $company->id);

        // Search filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('target', 'like', "%{$search}%")
                  ->orWhere('criticism', 'like', "%{$search}%")
                  ->orWhere('suggestion', 'like', "%{$search}%")
                  ->orWhereHas('employee.user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Target filter
        if ($request->filled('target') && $request->target !== 'all') {
            $query->where('target', $request->target);
        }

        // Anonymity filter
        if ($request->filled('type') && $request->type !== 'all') {
            if ($request->type === 'anonymous') {
                $query->where('is_anonymous', true);
            } elseif ($request->type === 'identified') {
                $query->where('is_anonymous', false);
            }
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Stats calculation
        $baseQuery = Feedback::where('company_id', $company->id);
        $totalCount = (clone $baseQuery)->count();
        $anonymousCount = (clone $baseQuery)->where('is_anonymous', true)->count();
        $identifiedCount = (clone $baseQuery)->where('is_anonymous', false)->count();

        // Distinct available targets for filter dropdown
        $availableTargets = Feedback::where('company_id', $company->id)
            ->whereNotNull('target')
            ->where('target', '!=', '')
            ->distinct()
            ->pluck('target')
            ->values();

        // Pagination
        $feedbacks = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($item) {
                $createdAt = Carbon::parse($item->created_at);

                $employeeData = null;
                if (!$item->is_anonymous && $item->employee) {
                    $employeeData = [
                        'id' => $item->employee->id,
                        'name' => $item->employee->user?->name ?? 'Karyawan',
                        'email' => $item->employee->user?->email,
                        'avatar' => $item->employee->user?->avatar,
                        'division' => $item->employee->division?->name ?? '-',
                        'position' => $item->employee->position?->name ?? '-',
                        'branch' => $item->employee->branch?->name ?? '-',
                    ];
                }

                return [
                    'id' => $item->id,
                    'target' => $item->target,
                    'criticism' => $item->criticism,
                    'suggestion' => $item->suggestion,
                    'is_anonymous' => (bool) $item->is_anonymous,
                    'employee' => $employeeData,
                    'created_at' => $createdAt->toISOString(),
                    'date_formatted' => $createdAt->locale('id')->translatedFormat('l, d F Y, H:i') . ' WIB',
                    'time_ago' => $createdAt->diffForHumans(),
                ];
            });

        return Inertia::render('Owner/System/Feedbacks/Index', [
            'feedbacks' => $feedbacks,
            'filters' => [
                'search' => $request->input('search', ''),
                'target' => $request->filled('target') && $request->target !== 'all' ? $request->target : '',
                'type' => $request->filled('type') && $request->type !== 'all' ? $request->type : '',
                'start_date' => $request->input('start_date', ''),
                'end_date' => $request->input('end_date', ''),
            ],
            'stats' => [
                'total' => $totalCount,
                'anonymous' => $anonymousCount,
                'identified' => $identifiedCount,
            ],
            'availableTargets' => $availableTargets,
        ]);
    }

    /**
     * Display the specified feedback.
     */
    public function show(Request $request, $id)
    {
        $company = $request->user()?->company ?? auth()->user()?->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $feedback = Feedback::with([
            'employee.user:id,name,email,avatar',
            'employee.division:id,name',
            'employee.position:id,name',
            'employee.branch:id,name',
        ])->where('company_id', $company->id)->findOrFail($id);

        $createdAt = Carbon::parse($feedback->created_at);
        $employeeData = null;
        if (!$feedback->is_anonymous && $feedback->employee) {
            $employeeData = [
                'id' => $feedback->employee->id,
                'name' => $feedback->employee->user?->name ?? 'Karyawan',
                'email' => $feedback->employee->user?->email,
                'avatar' => $feedback->employee->user?->avatar,
                'division' => $feedback->employee->division?->name ?? '-',
                'position' => $feedback->employee->position?->name ?? '-',
                'branch' => $feedback->employee->branch?->name ?? '-',
            ];
        }

        return Inertia::render('Owner/System/Feedbacks/Show', [
            'feedback' => [
                'id' => $feedback->id,
                'target' => $feedback->target,
                'criticism' => $feedback->criticism,
                'suggestion' => $feedback->suggestion,
                'is_anonymous' => (bool) $feedback->is_anonymous,
                'employee' => $employeeData,
                'created_at' => $createdAt->toISOString(),
                'date_formatted' => $createdAt->locale('id')->translatedFormat('l, d F Y, H:i') . ' WIB',
                'time_ago' => $createdAt->diffForHumans(),
            ],
        ]);
    }

    /**
     * Delete a feedback.
     */
    public function destroy(Request $request, $id)
    {
        $company = $request->user()?->company ?? auth()->user()?->company;
        if (!$company) {
            abort(403, 'Akses ditolak.');
        }

        $feedback = Feedback::where('company_id', $company->id)->findOrFail($id);
        $feedback->delete();

        return redirect()->route('owner.feedbacks.index')->with('success', 'Kritik & saran berhasil dihapus.');
    }
}
