<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

class BankTransferExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithColumnFormatting, WithCustomValueBinder
{
    protected $payroll;
    protected $periodName;
    protected $rowNumber = 0;

    public function __construct($payroll)
    {
        $this->payroll = $payroll;
        $this->periodName = \Carbon\Carbon::parse($payroll->start_date)->translatedFormat('F Y');
    }

    public function collection()
    {
        return $this->payroll->items()->with('employee')->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Bank',
            'No Rekening',
            'Nama Pemilik Rekening',
            'Jumlah (Rp)',
            'Keterangan',
        ];
    }

    public function map($item): array
    {
        $this->rowNumber++;
        $emp = $item->employee;

        $bankName = !empty($emp->bank_name) ? $emp->bank_name : '-';
        $bankAccountNo = !empty($emp->bank_account_number) ? (string) $emp->bank_account_number : '-';
        $bankAccountName = !empty($emp->bank_account_name) ? $emp->bank_account_name : ($emp->name ?? '-');
        $netSalary = (int) $item->net_salary;
        $remarks = "Gaji " . $this->periodName . " - " . ($emp->name ?? '-');

        return [
            $this->rowNumber,
            $bankName,
            $bankAccountNo,
            $bankAccountName,
            $netSalary,
            $remarks,
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        // Keep Account Numbers strictly as strings so leading zeros are never removed
        if ($cell->getColumn() === 'C' && $cell->getRow() > 1) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return [
            'E' => '#,##0',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial');
        $lastRow = $sheet->getHighestRow();

        // 1. Header Row Styling (Cobalt Blue Background, White Bold Text)
        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 10,
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1D4ED8'], // Cobalt Royal Blue like the user screenshot
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Alignments
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // 2. Data Rows Styling
        if ($lastRow > 1) {
            // General Font
            $sheet->getStyle('A2:F' . $lastRow)->applyFromArray([
                'font' => [
                    'name' => 'Arial',
                    'size' => 10,
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Set Data Row Heights
            for ($row = 2; $row <= $lastRow; $row++) {
                $sheet->getRowDimension($row)->setRowHeight(20);
            }

            // Alignments
            $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('D2:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('E2:E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('F2:F' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Subtle Thin Gridlines for Clean Excel Look
            $sheet->getStyle('A1:F' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFE2E8F0'],
                    ],
                ],
            ]);
        }

        return [];
    }
}
