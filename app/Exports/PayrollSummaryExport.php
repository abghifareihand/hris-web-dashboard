<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Illuminate\Support\Enumerable;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PayrollSummaryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    protected $payrolls;
    protected $rowNumber = 0;

    public function __construct($payrolls)
    {
        $this->payrolls = $payrolls;
    }

    public function collection(): Enumerable
    {
        return $this->payrolls;
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Penggajian',
            'Periode Awal',
            'Periode Akhir',
            'Cabang',
            'Divisi',
            'Total Karyawan',
            'Total Gaji (Rp)',
            'Status',
            'Tanggal Pembayaran',
        ];
    }

    public function map(mixed $payroll): array
    {
        $this->rowNumber++;

        $statusLabel = match (strtolower((string) $payroll->status)) {
            'paid' => 'Lunas',
            'process' => 'Dalam Proses',
            'cancelled' => 'Dibatalkan',
            default => ucfirst((string) $payroll->status),
        };

        return [
            $this->rowNumber,
            $payroll->code,
            \Carbon\Carbon::parse($payroll->start_date)->format('d/m/Y'),
            \Carbon\Carbon::parse($payroll->end_date)->format('d/m/Y'),
            $payroll->branch->name ?? 'Semua Cabang',
            $payroll->division->name ?? 'Semua Divisi',
            $payroll->total_employees,
            (int) $payroll->total_amount,
            $statusLabel,
            $payroll->paid_at ? \Carbon\Carbon::parse($payroll->paid_at)->format('d/m/Y') : '-',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => '#,##0',
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial');
        $sheet->setShowGridlines(true);
        $lastRow = max(2, $sheet->getHighestRow());

        // Header Styling
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 11,
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'], // Slate 800
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Row heights for data
        for ($r = 2; $r <= $lastRow; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(22);
        }

        // Vertical alignment for all cells
        $sheet->getStyle("A1:J{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Content alignments matching user screenshot
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("C2:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("G2:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("H2:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("I2:I{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("J2:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Status font colors & weights
        $rowIndex = 2;
        foreach ($this->payrolls as $p) {
            $status = strtolower((string) $p->status);
            $color = match ($status) {
                'paid' => '059669',     // Emerald (Lunas)
                'process' => 'D97706',  // Amber (Dalam Proses)
                'cancelled' => 'DC2626',// Merah (Dibatalkan)
                default => '475569',
            };

            $sheet->getStyle("I{$rowIndex}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => $color],
                ],
            ]);
            $rowIndex++;
        }

        $sheet->getStyle("A1:J{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);

        return [];
    }
}
