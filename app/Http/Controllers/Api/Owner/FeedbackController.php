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
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Data perusahaan tidak ditemukan atau Anda bukan owner.',
            ], 403);
        }

        $query = Feedback::with(['employee.user', 'employee.position:id,name', 'employee.branch:id,name'])
            ->where('company_id', $company->id);

        // Filter search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('target', 'like', "%{$search}%")
                  ->orWhere('criticism', 'like', "%{$search}%")
                  ->orWhere('suggestion', 'like', "%{$search}%");
            });
        }

        // Filter anonymous
        if ($request->has('is_anonymous') && $request->is_anonymous !== 'all') {
            $isAnonymous = filter_var($request->is_anonymous, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isAnonymous !== null) {
                $query->where('is_anonymous', $isAnonymous);
            }
        }

        // Filter dates
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $query->orderBy('created_at', 'desc');

        if ($request->boolean('all')) {
            $feedbacks = $query->get()->map(function ($item) {
                return $this->formatFeedback($item);
            });

            return response()->json([
                'status' => true,
                'message' => 'Daftar kritik dan saran berhasil diambil.',
                'data' => $feedbacks,
            ]);
        }

        $perPage = $this->getPerPage($request, 10);
        $paginated = $query->paginate($perPage);

        $paginated->getCollection()->transform(function ($item) {
            return $this->formatFeedback($item);
        });

        return $this->paginatedResponse($paginated, 'Daftar kritik dan saran berhasil diambil.');
    }

    /**
     * Get detail of a specific feedback.
     */
    public function show(Request $request, $id)
    {
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Data perusahaan tidak ditemukan atau Anda bukan owner.',
            ], 403);
        }

        $feedback = Feedback::with(['employee.user', 'employee.position:id,name', 'employee.branch:id,name'])
            ->where('company_id', $company->id)
            ->find($id);

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
        $company = $request->user()->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Data perusahaan tidak ditemukan atau Anda bukan owner.',
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
     * Format feedback payload for mobile response.
     */
    private function formatFeedback(Feedback $feedback, bool $isDetail = false): array
    {
        $createdAt = Carbon::parse($feedback->created_at);

        if ($feedback->is_anonymous) {
            $sender = [
                'name' => 'Anonim',
                'avatar_url' => null,
                'position' => 'Dirahasiakan',
                'branch' => 'Dirahasiakan',
            ];
        } else {
            $employee = $feedback->employee;
            $user = $employee ? $employee->user : null;
            $avatarUrl = null;
            if ($user && $user->avatar) {
                $avatarUrl = asset('storage/' . $user->avatar);
            }

            $sender = [
                'id' => $employee ? $employee->id : null,
                'name' => $user ? $user->name : 'Karyawan',
                'avatar_url' => $avatarUrl,
                'position' => $employee && $employee->position ? $employee->position->name : '-',
                'branch' => $employee && $employee->branch ? $employee->branch->name : '-',
            ];
        }

        return [
            'id' => $feedback->id,
            'company_id' => $feedback->company_id,
            'target' => $feedback->target,
            'criticism' => $feedback->criticism,
            'suggestion' => $feedback->suggestion,
            'is_anonymous' => (bool) $feedback->is_anonymous,
            'sender' => $sender,
            'created_at' => $createdAt->format('Y-m-d H:i'),
            'date_formatted' => $isDetail
                ? $createdAt->translatedFormat('l, d F Y \p\u\k\u\l H:i')
                : $createdAt->translatedFormat('d M Y, H:i'),
            'time_ago' => $createdAt->diffForHumans(),
        ];
    }
}
