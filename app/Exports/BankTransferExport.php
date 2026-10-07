<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Illuminate\Support\Enumerable;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use Carbon\Carbon;

class BankTransferExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithColumnFormatting, WithCustomValueBinder
{
    protected $payroll;
    protected $periodName;
    protected $rowNumber = 0;

    public function __construct($payroll)
    {
        $this->payroll = $payroll;
        $this->periodName = $payroll->start_date 
            ? Carbon::parse($payroll->start_date)->locale('id')->translatedFormat('F Y') 
            : Carbon::now()->locale('id')->translatedFormat('F Y');
    }

    public function collection(): Enumerable
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

    public function bindValue(Cell $cell, mixed $value): bool
    {
        // Pastikan nomor rekening tetap terbaca sebagai teks murni agar 0 di depan tidak terpotong
        if ($cell->getColumn() === 'C' && $cell->getRow() > 1) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return [
            'C' => '@',
            'E' => '#,##0',
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial');
        $sheet->setShowGridlines(true);

        $highestRow = max(1, $sheet->getHighestRow());
        $highestCol = $sheet->getHighestColumn();

        // 1. Style Header (Slate 800 '1E293B', Font Putih Tebal, Row Height 30 - Seragam Export Rekap)
        $sheet->getStyle("A1:{$highestCol}1")->applyFromArray([
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
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Header Alignments
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // 2. Data Rows Styling
        if ($highestRow > 1) {
            $sheet->getStyle("A2:{$highestCol}{$highestRow}")->applyFromArray([
                'font' => [
                    'name' => 'Arial',
                    'size' => 10,
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            for ($row = 2; $row <= $highestRow; $row++) {
                $sheet->getRowDimension($row)->setRowHeight(22);
            }

            // Alignments Data
            $sheet->getStyle("A2:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B2:B{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C2:C{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("D2:D{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("E2:E{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("F2:F{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Thin Border untuk seluruh data (Slate 300 'CBD5E1' - Seragam Export Rekap)
            $sheet->getStyle("A1:{$highestCol}{$highestRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1'],
                    ],
                ],
            ]);
        }

        return [];
    }
}
