<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    /**
     * Get list of feedbacks submitted by the authenticated employee.
     */
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya employee yang bisa mengakses fitur ini.',
            ], 403);
        }

        $query = Feedback::where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('target', 'like', "%{$search}%")
                  ->orWhere('criticism', 'like', "%{$search}%")
                  ->orWhere('suggestion', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('all')) {
            $feedbacks = $query->get()->map(function ($item) {
                return $this->formatFeedback($item);
            });

            return response()->json([
                'status' => true,
                'message' => 'Riwayat kritik dan saran berhasil diambil.',
                'data' => $feedbacks,
            ]);
        }

        $perPage = $this->getPerPage($request, 10);
        $paginated = $query->paginate($perPage);

        $paginated->getCollection()->transform(function ($item) {
            return $this->formatFeedback($item);
        });

        return $this->paginatedResponse($paginated, 'Riwayat kritik dan saran berhasil diambil.');
    }

    /**
     * Submit a new feedback (criticism & suggestion).
     */
    public function store(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya employee yang bisa mengirim kritik dan saran.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'target' => 'required|string|max:255',
            'criticism' => 'required|string',
            'suggestion' => 'required|string',
            'is_anonymous' => 'nullable|boolean',
        ], [
            'target.required' => 'Pihak yang dituju wajib diisi.',
            'criticism.required' => 'Isi kritik wajib diisi.',
            'suggestion.required' => 'Isi saran wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $feedback = Feedback::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'target' => $request->target,
            'criticism' => $request->criticism,
            'suggestion' => $request->suggestion,
            'is_anonymous' => $request->boolean('is_anonymous'),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Kritik dan saran berhasil dikirim.',
            'data' => $this->formatFeedback($feedback, true),
        ], 201);
    }

    /**
     * Show detail of a feedback submitted by employee.
     */
    public function show(Request $request, $id)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya employee yang bisa mengakses fitur ini.',
            ], 403);
        }

        $feedback = Feedback::where('employee_id', $employee->id)->find($id);
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
     * Delete employee's own feedback.
     */
    public function destroy(Request $request, $id)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya employee yang bisa mengakses fitur ini.',
            ], 403);
        }

        $feedback = Feedback::where('employee_id', $employee->id)->find($id);
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
     * Format feedback payload for employee response.
     */
    private function formatFeedback(Feedback $feedback, bool $isDetail = false): array
    {
        $createdAt = Carbon::parse($feedback->created_at);

        return [
            'id' => $feedback->id,
            'company_id' => $feedback->company_id,
            'target' => $feedback->target,
            'criticism' => $feedback->criticism,
            'suggestion' => $feedback->suggestion,
            'is_anonymous' => (bool) $feedback->is_anonymous,
            'created_at' => $createdAt->format('Y-m-d H:i'),
            'date_formatted' => $isDetail
                ? $createdAt->translatedFormat('l, d F Y \p\u\k\u\l H:i')
                : $createdAt->translatedFormat('d M Y, H:i'),
            'time_ago' => $createdAt->diffForHumans(),
        ];
    }
}
