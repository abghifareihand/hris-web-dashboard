<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\DailyReport;
use App\Models\DailyReportAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DailyReportController extends Controller
{
    /**
     * Get list of daily reports for the authenticated employee
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = DailyReport::with('attachments')->where('user_id', $user->id);

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
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
            'attachments.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120' // max 5MB per image
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
        $report = DailyReport::where('user_id', $request->user()->id)->find($id);

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
        $report = DailyReport::with('attachments')->where('user_id', $request->user()->id)->find($id);

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
