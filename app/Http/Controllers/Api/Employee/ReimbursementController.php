<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Reimbursement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReimbursementController extends Controller
{
    /**
     * Get list of reimbursements for the authenticated employee,
     * including all details (attachment_url, approver, payout_method) directly.
     */
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $query = Reimbursement::with(['approvedBy:id,name'])
            ->where('employee_id', $employee->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $reimbursements = $query->latest('date')->latest('id')->paginate($this->getPerPage($request));

        $reimbursements->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'date' => Carbon::parse($item->date)->format('Y-m-d'),
                'amount' => (int) $item->amount,
                'reason' => $item->reason,
                'attachment' => $item->attachment,
                'attachment_url' => $item->attachment ? asset('storage/' . $item->attachment) : null,
                'status' => $item->status, // 'pending', 'approved', 'paid', 'rejected', 'cancelled'
                'payout_method' => $item->payout_method, // 'payroll', 'direct'
                'reject_reason' => $item->reject_reason,
                'approved_by_name' => $item->approvedBy ? $item->approvedBy->name : null,
                'approved_at' => $item->approved_at ? Carbon::parse($item->approved_at)->format('Y-m-d H:i') : null,
                'created_at' => $item->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return $this->paginatedResponse($reimbursements, 'Daftar klaim biaya berhasil diambil.');
    }

    /**
     * Submit a new reimbursement request from employee.
     */
    public function store(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'amount' => 'required|numeric|min:1000',
            'reason' => 'required|string|max:500',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ], [
            'date.required' => 'Tanggal klaim wajib diisi.',
            'amount.required' => 'Nominal klaim wajib diisi.',
            'amount.min' => 'Nominal klaim minimal Rp 1.000.',
            'reason.required' => 'Keterangan/keperluan klaim wajib diisi.',
            'attachment.max' => 'Ukuran berkas lampiran maksimal 5MB.',
            'attachment.mimes' => 'Format lampiran harus berupa JPG, PNG, atau PDF.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('reimbursements', 'public');
        }

        $reimbursement = Reimbursement::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'date' => $request->date,
            'amount' => $request->amount,
            'reason' => $request->reason,
            'attachment' => $attachmentPath,
            'status' => 'pending',
            'payout_method' => null, // Belum ditentukan, ditentukan oleh Owner saat approval
        ]);



        return response()->json([
            'status' => true,
            'message' => 'Pengajuan klaim biaya berhasil dikirim. Menunggu persetujuan.',
        ], 201);
    }
}

