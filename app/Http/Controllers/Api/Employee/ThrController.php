<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\ThrItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ThrController extends Controller
{
    /**
     * Get list of THR items (slips) for the authenticated employee,
     * including full calculation breakdown directly.
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

        $query = ThrItem::with([
            'thr',
            'employee.division',
            'employee.position',
        ])
        ->where('employee_id', $employee->id)
        ->whereHas('thr', function ($q) {
            $q->whereIn('status', ['process', 'paid']);
        });

        if ($request->filled('year')) {
            $query->whereHas('thr', function ($q) use ($request) {
                $q->where('year', $request->year);
            });
        }

        $thrItems = $query->latest('id')->paginate($this->getPerPage($request));

        $thrItems->getCollection()->transform(function ($item) {
            $thr = $item->thr;

            return [
                'id' => $item->id,
                'thr_id' => $item->thr_id,
                'thr_code' => $thr ? $thr->code : null,
                'year' => $thr ? $thr->year : null,
                'holiday_name' => $thr ? $thr->holiday_name : null,
                'payment_date' => $thr ? Carbon::parse($thr->payment_date)->format('Y-m-d') : null,
                'status' => $thr ? $thr->status : 'process',
                
                // Detailed Calculation Breakdown
                'tenure_months' => (int) $item->tenure_months,
                'basis_amount' => (int) $item->basis_amount,
                'prorate_multiplier' => (float) $item->prorate_multiplier,
                'thr_amount' => (int) $item->thr_amount,
                'tax_amount' => (int) $item->tax_amount,
                'net_amount' => (int) $item->net_amount,

                'download_url' => route('api.employee.thr.download', $item->id),
                'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return $this->paginatedResponse($thrItems, 'Daftar slip THR berhasil diambil.');
    }

    /**
     * Download or stream PDF slip THR for the authenticated employee.
     */
    public function download(Request $request, $id)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan.'
            ], 404);
        }

        $item = ThrItem::with([
            'thr',
            'employee.division',
            'employee.position',
            'employee.company',
        ])
        ->where('id', $id)
        ->where('employee_id', $employee->id)
        ->firstOrFail();

        $company = $employee->company;
        $thr = $item->thr;

        // Set relation to single item so template renders this slip
        $thr->setRelation('items', collect([$item]));

        $pdf = Pdf::loadView('pdf.thr-slip', compact('thr', 'company'));
        $pdf->setPaper('a4', 'portrait');

        $fileName = 'Slip-THR-' . Str::slug($employee->name) . '-' . $thr->code . '.pdf';
        return $pdf->stream($fileName);
    }
}
