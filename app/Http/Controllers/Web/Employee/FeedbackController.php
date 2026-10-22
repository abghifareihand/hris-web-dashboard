<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Feedback;

class FeedbackController extends Controller
{
    /**
     * Display list of employee submitted feedback and suggestions.
     */
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $query = Feedback::where('employee_id', $employee->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('target', 'like', "%{$search}%")
                  ->orWhere('criticism', 'like', "%{$search}%")
                  ->orWhere('suggestion', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_anonymous')) {
            if ($request->is_anonymous === '1') {
                $query->where('is_anonymous', true);
            } elseif ($request->is_anonymous === '0') {
                $query->where('is_anonymous', false);
            }
        }

        $feedbacks = $query->latest('id')->paginate(10)->withQueryString();

        $allFeedbacks = Feedback::where('employee_id', $employee->id)->get();
        $totalCount = $allFeedbacks->count();
        $anonymousCount = $allFeedbacks->where('is_anonymous', true)->count();
        $publicCount = $totalCount - $anonymousCount;

        return Inertia::render('Employee/Feedbacks/Index', [
            'feedbacks' => $feedbacks,
            'totalCount' => $totalCount,
            'anonymousCount' => $anonymousCount,
            'publicCount' => $publicCount,
            'filters' => [
                'search' => $request->input('search', ''),
                'is_anonymous' => $request->input('is_anonymous', ''),
            ],
        ]);
    }

    /**
     * Store a new employee feedback.
     */
    public function store(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $request->validate([
            'target' => 'required|string|max:100',
            'criticism' => 'required|string|max:2000',
            'suggestion' => 'required|string|max:2000',
            'is_anonymous' => 'nullable|boolean',
        ], [
            'target.required' => 'Pihak yang dituju wajib diisi.',
            'criticism.required' => 'Poin kritik atau masalah yang dirasakan wajib diisi.',
            'suggestion.required' => 'Usulan saran perbaikan wajib diisi.',
        ]);

        Feedback::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'target' => $request->target,
            'criticism' => $request->criticism,
            'suggestion' => $request->suggestion,
            'is_anonymous' => $request->boolean('is_anonymous'),
        ]);

        return back()->with('success', 'Kritik & saran Anda berhasil dikirim ke pimpinan perusahaan. Terima kasih atas aspirasi Anda!');
    }

    /**
     * Delete employee's own feedback.
     */
    public function destroy(Request $request, $id)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $feedback = Feedback::where('employee_id', $employee->id)->findOrFail($id);
        $feedback->delete();

        return back()->with('success', 'Kritik & saran berhasil dihapus.');
    }
}
