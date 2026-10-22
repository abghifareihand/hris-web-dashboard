<?php

namespace App\Http\Controllers\Web\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\ThrItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ThrController extends Controller
{
    /**
     * Display paginated list of THR slips for authenticated employee.
     */
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403, 'Data karyawan tidak ditemukan.');

        $selectedYear = $request->filled('year') ? (int) $request->input('year') : null;

        $query = ThrItem::with([
            'thr',
            'employee.position',
            'employee.division',
            'employee.branch',
        ])
        ->where('employee_id', $employee->id)
        ->whereHas('thr', function ($q) use ($selectedYear) {
            $q->whereIn('status', ['process', 'paid']);
            if ($selectedYear) {
                $q->where('year', $selectedYear);
            }
        });

        $thrItems = $query->latest('id')->paginate(10)->withQueryString();

        $thrItems->getCollection()->transform(function ($item) {
            $thr = $item->thr;
            return [
                'id' => $item->id,
                'thr_id' => $item->thr_id,
                'thr_code' => $thr ? $thr->code : 'THR-' . $item->id,
                'holiday_name' => $thr ? $thr->holiday_name : 'Tunjangan Hari Raya',
                'year' => $thr ? $thr->year : date('Y'),
                'payment_date' => ($thr && $thr->payment_date) ? Carbon::parse($thr->payment_date)->locale('id')->translatedFormat('d M Y') : '-',
                'status' => $thr ? $thr->status : 'paid',
                'tenure_months' => (int) $item->tenure_months,
                'basis_amount' => (int) $item->basis_amount,
                'prorate_multiplier' => (float) $item->prorate_multiplier,
                'thr_amount' => (int) $item->thr_amount,
                'tax_amount' => (int) $item->tax_amount,
                'net_amount' => (int) $item->net_amount,
                'download_url' => route('employee.thr.download', $item->id),
            ];
        });

        $years = range(date('Y') - 3, date('Y') + 1);

        return Inertia::render('Employee/Thr/Index', [
            'thrItems' => $thrItems,
            'year' => $selectedYear,
            'years' => $years,
            'filters' => [
                'year' => $selectedYear,
            ],
        ]);
    }

    /**
     * Download / Stream PDF slip THR.
     */
    public function downloadPdf($id, Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) abort(403);

        $company = $employee->company;
        if (!$company) abort(403);

        $item = ThrItem::with([
            'thr',
            'employee.division',
            'employee.position',
            'employee.branch',
            'employee.company',
        ])
        ->where('id', $id)
        ->where('employee_id', $employee->id)
        ->firstOrFail();

        $thr = $item->thr;
        $thr->setRelation('items', collect([$item]));

        $pdf = Pdf::loadView('pdf.thr-slip', compact('thr', 'company'));
        $pdf->setPaper('a4', 'portrait');

        $fileName = 'Slip-THR-' . Str::slug($employee->name) . '-' . $thr->code . '.pdf';
        return $pdf->stream($fileName);
    }
}
