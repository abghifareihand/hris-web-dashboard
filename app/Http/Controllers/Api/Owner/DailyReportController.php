<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\DailyReport;
use App\Models\DailyReportAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DailyReportController extends Controller
{
    /**
     * Get list of daily reports for the authenticated owner
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $employeeUserIds = \App\Models\Employee::where('company_id', $company->id)->pluck('user_id')->toArray();

        $query = DailyReport::with(['attachments', 'user.employee']);

        if ($request->input('type') === 'personal') {
            $query->where('user_id', $user->id);
        } elseif ($request->input('type') === 'employee') {
            $query->whereIn('user_id', $employeeUserIds);
        } else {
            $allowedUserIds = array_merge([$user->id], $employeeUserIds);
            $query->whereIn('user_id', $allowedUserIds);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->filled('employee_id')) {
            $filteredEmployee = \App\Models\Employee::where('company_id', $company->id)
                ->where('id', $request->employee_id)
                ->first();

            if ($filteredEmployee) {
                $query->where('user_id', $filteredEmployee->user_id);
            } else {
                // Prevent returning data if employee_id is invalid for this company
                $query->where('id', -1); 
            }
        }

        $reports = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage($request));

        $reports->getCollection()->transform(function ($report) {
            return [
                'id' => $report->id,
                'title' => $report->title,
                'date' => $report->date->format('Y-m-d'),
                'description' => $report->description,
                'name' => $report->user->employee->name ?? $report->user->name,
                'created_at' => $report->created_at->format('Y-m-d H:i:s'),
                'attachments' => $report->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'file_url' => $attachment->file_url,
                    ];
                }),
            ];
        });

        return $this->paginatedResponse($reports, 'Laporan Harian berhasil diambil.');
    }

    /**
     * Store a new daily report
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $report = DailyReport::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'date' => $request->date,
            'description' => $request->description,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('daily_reports', 'public');
                DailyReportAttachment::create([
                    'daily_report_id' => $report->id,
                    'file_path' => $path
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Laporan Harian berhasil dibuat.'
        ], 201);
    }

    /**
     * Update an existing daily report
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $employeeUserIds = \App\Models\Employee::where('company_id', $company->id)->pluck('user_id')->toArray();
        $allowedUserIds = array_merge([$user->id], $employeeUserIds);

        $report = DailyReport::whereIn('user_id', $allowedUserIds)->find($id);

        if (!$report) {
            return response()->json([
                'status' => false,
                'message' => 'Laporan Harian tidak ditemukan.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'date' => 'sometimes|required|date',
            'description' => 'nullable|string',
            'deleted_attachment_ids' => 'nullable|array',
            'deleted_attachment_ids.*' => 'exists:daily_report_attachments,id',
            'new_attachments' => 'nullable|array',
            'new_attachments.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $report->update($request->only(['title', 'date', 'description']));

        // Handle deleted attachments
        if ($request->filled('deleted_attachment_ids')) {
            $attachmentsToDelete = DailyReportAttachment::where('daily_report_id', $report->id)
                ->whereIn('id', $request->deleted_attachment_ids)
                ->get();

            foreach ($attachmentsToDelete as $attachment) {
                if (Storage::disk('public')->exists($attachment->file_path)) {
                    Storage::disk('public')->delete($attachment->file_path);
                }
                $attachment->delete();
            }
        }

        // Handle new attachments
        if ($request->hasFile('new_attachments')) {
            foreach ($request->file('new_attachments') as $file) {
                $path = $file->store('daily_reports', 'public');
                DailyReportAttachment::create([
                    'daily_report_id' => $report->id,
                    'file_path' => $path
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Laporan Harian berhasil diperbarui.'
        ]);
    }

    /**
     * Delete a daily report
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya owner perusahaan yang bisa mengakses.'
            ], 403);
        }

        $employeeUserIds = \App\Models\Employee::where('company_id', $company->id)->pluck('user_id')->toArray();
        $allowedUserIds = array_merge([$user->id], $employeeUserIds);

        $report = DailyReport::with('attachments')->whereIn('user_id', $allowedUserIds)->find($id);

        if (!$report) {
            return response()->json([
                'status' => false,
                'message' => 'Laporan Harian tidak ditemukan.'
            ], 404);
        }

        foreach ($report->attachments as $attachment) {
            if (Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
        }

        $report->delete();

        return response()->json([
            'status' => true,
            'message' => 'Laporan Harian berhasil dihapus.'
        ]);
    }
}
