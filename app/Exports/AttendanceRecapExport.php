<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AttendanceRecapExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $data;
    protected $statusLabels;
    protected $rowNumber = 0;

    public function __construct($data, $statusLabels = [])
    {
        $this->data = $data;
        $this->statusLabels = array_merge([
            'very_good' => 'Sangat Baik',
            'good' => 'Baik',
            'fair' => 'Cukup',
            'poor' => 'Kurang',
        ], $statusLabels);
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Karyawan',
            'Email',
            'Cabang',
            'Divisi',
            'Jabatan',
            'Total Jadwal Kerja',
            'Total Kehadiran',
            'Total Alpha',
            'Total Cuti',
            'Total Terlambat',
            'Total Pulang Cepat',
            'Total Pelanggaran',
            'Total Lembur',
            'Total Durasi Lembur',
            $this->statusLabels['very_good'],
            $this->statusLabels['good'],
            $this->statusLabels['fair'],
            $this->statusLabels['poor'],
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $row['name'],
            $row['email'],
            $row['branch'],
            $row['division'],
            $row['position'],
            $row['total_schedule'],
            $row['total_present'],
            $row['total_alpha'],
            $row['total_leave'],
            $row['total_late'],
            $row['total_early_leaving'],
            $row['total_violation'],
            $row['total_overtime_count'],
            $row['total_overtime_duration'],
            $row['status_very_good'],
            $row['status_good'],
            $row['status_fair'],
            $row['status_poor'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();

        // Style header
        $sheet->getStyle("A1:{$highestCol}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'], // Slate 800
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Center align numbers & metrics
        $sheet->getStyle("A2:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("G2:{$highestCol}{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Border for whole data
        $sheet->getStyle("A1:{$highestCol}{$highestRow}")->applyFromArray([
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
