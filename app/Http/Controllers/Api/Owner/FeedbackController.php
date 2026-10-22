<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Get list of feedbacks for owner's company.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang dapat mengakses fitur ini.',
            ], 403);
        }

        $query = Feedback::with([
            'employee.user:id,name,email,avatar',
            'employee.division:id,name',
            'employee.position:id,name',
            'employee.branch:id,name',
        ])->where('company_id', $company->id)
          ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('target', 'like', "%{$search}%")
                  ->orWhere('criticism', 'like', "%{$search}%")
                  ->orWhere('suggestion', 'like', "%{$search}%");
            });
        }

        if ($request->filled('target') && $request->target !== 'all') {
            $query->where('target', $request->target);
        }

        if ($request->filled('is_anonymous')) {
            $query->where('is_anonymous', $request->boolean('is_anonymous'));
        }

        $perPage = $this->getPerPage($request, 15);
        $paginated = $query->paginate($perPage);

        $paginated->getCollection()->transform(function ($item) {
            return $this->formatFeedback($item);
        });

        return $this->paginatedResponse($paginated, 'Daftar kritik dan saran berhasil diambil.');
    }

    /**
     * Get single feedback detail.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang dapat mengakses fitur ini.',
            ], 403);
        }

        $feedback = Feedback::with([
            'employee.user:id,name,email,avatar',
            'employee.division:id,name',
            'employee.position:id,name',
            'employee.branch:id,name',
        ])->where('company_id', $company->id)->find($id);

        if (!$feedback) {
            return response()->json([
                'status' => false,
                'message' => 'Kritik dan saran tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Detail kritik dan saran berhasil diambil.',
            'data' => $this->formatFeedback($feedback, true),
        ]);
    }

    /**
     * Delete a feedback.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang dapat mengakses fitur ini.',
            ], 403);
        }

        $feedback = Feedback::where('company_id', $company->id)->find($id);

        if (!$feedback) {
            return response()->json([
                'status' => false,
                'message' => 'Kritik dan saran tidak ditemukan.',
            ], 404);
        }

        $feedback->delete();

        return response()->json([
            'status' => true,
            'message' => 'Kritik dan saran berhasil dihapus.',
        ]);
    }

    /**
     * Format feedback payload.
     */
    private function formatFeedback(Feedback $feedback, bool $isDetail = false): array
    {
        $createdAt = Carbon::parse($feedback->created_at);

        $employeeData = null;
        if (!$feedback->is_anonymous && $feedback->employee) {
            $employeeData = [
                'id' => $feedback->employee->id,
                'name' => $feedback->employee->user?->name ?? 'Karyawan',
                'email' => $feedback->employee->user?->email,
                'avatar' => $feedback->employee->user?->avatar,
                'division' => $feedback->employee->division?->name,
                'position' => $feedback->employee->position?->name,
                'branch' => $feedback->employee->branch?->name,
            ];
        }

        return [
            'id' => $feedback->id,
            'company_id' => $feedback->company_id,
            'target' => $feedback->target,
            'criticism' => $feedback->criticism,
            'suggestion' => $feedback->suggestion,
            'is_anonymous' => (bool) $feedback->is_anonymous,
            'employee' => $employeeData,
            'created_at' => $createdAt->format('Y-m-d H:i'),
            'date_formatted' => $isDetail
                ? $createdAt->translatedFormat('l, d F Y \p\u\k\u\l H:i')
                : $createdAt->translatedFormat('d M Y, H:i'),
            'time_ago' => $createdAt->diffForHumans(),
        ];
    }
}
