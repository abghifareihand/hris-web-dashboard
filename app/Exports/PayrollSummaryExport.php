<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
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

    public function collection()
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

    public function map($payroll): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $payroll->code,
            \Carbon\Carbon::parse($payroll->start_date)->format('d/m/Y'),
            \Carbon\Carbon::parse($payroll->end_date)->format('d/m/Y'),
            $payroll->branch->name ?? 'Semua Cabang',
            $payroll->division->name ?? 'Semua Divisi',
            $payroll->total_employees,
            (int) $payroll->total_amount,
            strtoupper($payroll->status),
            $payroll->paid_at ? $payroll->paid_at->format('d/m/Y H:i') : '-',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => '#,##0',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial');
        $lastRow = max(2, $sheet->getHighestRow());

        // Header Styling
        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 10,
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E40AF'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Content alignments & borders
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B2:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("G2:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("H2:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("I2:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("A1:J{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ]);

        return [];
    }
}
