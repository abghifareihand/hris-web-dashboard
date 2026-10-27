<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Slip Gaji F4 (10 Grid) - {{ $payroll->code }}</title>
    <style>
        @page {
            size: 215mm 330mm portrait;
            margin: 4.5mm 4.5mm 4.5mm 4.5mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 6.5pt;
            color: #111;
            margin: 0;
            padding: 0;
        }

        .page-break {
            page-break-after: always;
        }

        /* 10-Grid Table: Exactly 2 columns x 5 rows per F4 sheet */
        table.grid-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td.slip-cell {
            width: 50%;
            vertical-align: top;
            padding: 1.2mm;
            box-sizing: border-box;
        }

        /* Dotted/Dashed cutting box for each slip */
        .slip-box {
            border: 1px dashed #64748b;
            padding: 2.5px 3.5px;
            background-color: #ffffff;
            box-sizing: border-box;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 0.8px solid #1e293b;
            padding-bottom: 1px;
            margin-bottom: 1.5px;
        }

        .company-name {
            font-size: 7.5pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            line-height: 1;
        }

        .slip-badge {
            font-size: 7pt;
            font-weight: bold;
            text-align: right;
            color: #0f172a;
            line-height: 1;
        }

        .period-text {
            font-size: 5.5pt;
            color: #475569;
            text-align: right;
            line-height: 1.1;
        }

        .emp-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
            font-size: 6.5pt;
        }

        .emp-info td {
            padding: 0.5px 1px;
            vertical-align: middle;
            line-height: 1.1;
        }

        .emp-name {
            font-weight: bold;
            color: #000;
        }

        /* 2-Column Content (Earnings vs Deductions) */
        table.content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5px;
        }

        table.content-table td {
            vertical-align: top;
            padding: 0;
        }

        table.side-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6pt;
        }

        table.side-table th {
            background-color: #f1f5f9;
            border-top: 0.6px solid #94a3b8;
            border-bottom: 0.6px solid #94a3b8;
            font-weight: bold;
            padding: 1px 1.5px;
            text-align: left;
            font-size: 6pt;
            line-height: 1;
        }

        table.side-table th.text-right {
            text-align: right;
        }

        table.side-table td {
            padding: 0.7px 1.5px;
            border-bottom: 0.4px dotted #cbd5e1;
            line-height: 1.1;
        }

        table.side-table tr.total-row td {
            border-top: 0.8px solid #334155;
            border-bottom: 0.8px solid #334155;
            font-weight: bold;
            background-color: #f8fafc;
            padding: 1px 1.5px;
            color: #0f172a;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .text-muted {
            color: #64748b;
        }

        /* Footer */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5px;
            font-size: 6.5pt;
        }

        .footer-table td {
            padding: 0.5px;
            vertical-align: middle;
        }

        .loan-box {
            font-size: 6pt;
            font-weight: bold;
            color: #b91c1c;
        }

        .thp-amount {
            font-size: 7pt;
            font-weight: bold;
            color: #0f172a;
            margin-left: 2px;
        }
    </style>
</head>

<body>
    @php
        $chunks = $payroll->items->chunk(10);
        $totalChunks = $chunks->count();
        $periodText = Carbon\Carbon::parse($payroll->start_date)->translatedFormat('d M Y') . ' - ' . Carbon\Carbon::parse($payroll->end_date)->translatedFormat('d M Y');
        $companyName = $company->name_company ?? ($company->name ?? 'PERUSAHAAN');
    @endphp

    @foreach ($chunks as $chunkIndex => $chunk)
        @php
            $items = $chunk->values();
        @endphp

        <table class="grid-table">
            @for ($row = 0; $row < 5; $row++)
                <tr>
                    @for ($col = 0; $col < 2; $col++)
                        @php
                            $index = ($row * 2) + $col;
                            $item = $items->get($index);
                        @endphp
                        <td class="slip-cell">
                            @if ($item)
                                @php
                                    $emp = $item->employee;
                                    $cycle = $emp->payroll_cycle ?? ($payroll->payroll_cycle ?? 'monthly');
                                    $cycleLabel = match ($cycle) {
                                        'daily' => 'Gaji Harian',
                                        'weekly' => 'Gaji Mingguan',
                                        default => 'Gaji Bulanan',
                                    };

                                    $bpjsTkTotal = ($item->jht_employee ?? 0) + ($item->jp_employee ?? 0);
                                    $bpjsKesTotal = $item->bpjs_kesehatan_employee ?? 0;

                                    $sisaPinjaman = $remainingLoans[$item->id] ?? 0;
                                    $potonganPinjaman = 0;
                                    if ($item->loanInstallment) {
                                        $potonganPinjaman = $item->loanInstallment->amount;
                                    }
                                    $otherDeductionMinusLoan = max(0, ($item->other_deductions ?? 0) - $potonganPinjaman);
                                @endphp

                                <div class="slip-box">
                                    <!-- Header: Company & Title -->
                                    <table class="header-table">
                                        <tr>
                                            <td style="width: 58%; vertical-align: middle;">
                                                <div class="company-name">{{ Str::limit($companyName, 26) }}</div>
                                            </td>
                                            <td style="width: 42%; vertical-align: middle; text-align: right;">
                                                <div class="slip-badge">SLIP GAJI</div>
                                                <div class="period-text">{{ $periodText }}</div>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Employee Info (No NIK) -->
                                    <table class="emp-info">
                                        <tr>
                                            <td style="width: 13%;" class="text-muted">Nama</td>
                                            <td style="width: 2%;">:</td>
                                            <td style="width: 45%;" class="emp-name">{{ Str::limit($emp->name ?? 'Karyawan', 22) }}</td>
                                            <td style="width: 14%;" class="text-muted">Div/Jab</td>
                                            <td style="width: 2%;">:</td>
                                            <td style="width: 24%;" class="font-bold">{{ Str::limit(($emp->division?->name ?? '-') . '/' . ($emp->position?->name ?? '-'), 16) }}</td>
                                        </tr>
                                    </table>

                                    <!-- Financial Table: 2 Columns (Earnings vs Deductions) -->
                                    <table class="content-table">
                                        <tr>
                                            <!-- PENGHASILAN -->
                                            <td style="width: 50%; padding-right: 2px;">
                                                <table class="side-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Penghasilan</th>
                                                            <th class="text-right">Jumlah</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>{{ $cycleLabel }}</td>
                                                            <td class="text-right">{{ number_format($item->basic_salary, 0, ',', '.') }}</td>
                                                        </tr>
                                                        @if ($item->fixed_allowance > 0)
                                                            <tr>
                                                                <td>Tunjangan Tetap</td>
                                                                <td class="text-right">{{ number_format($item->fixed_allowance, 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endif
                                                        @if ($item->daily_allowance > 0)
                                                            <tr>
                                                                <td>Tunjangan Harian</td>
                                                                <td class="text-right">{{ number_format($item->daily_allowance, 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endif
                                                        @if ($item->other_allowance > 0)
                                                            <tr>
                                                                <td>Tunjangan Lainnya</td>
                                                                <td class="text-right">{{ number_format($item->other_allowance, 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endif
                                                        @if ($item->overtime_amount > 0)
                                                            <tr>
                                                                <td>Upah Lembur</td>
                                                                <td class="text-right">{{ number_format($item->overtime_amount, 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endif
                                                        @if (($item->reimbursement_amount ?? 0) > 0)
                                                            <tr>
                                                                <td>Klaim Biaya</td>
                                                                <td class="text-right">{{ number_format($item->reimbursement_amount, 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endif
                                                        @if ($item->bonus > 0)
                                                            <tr>
                                                                <td>Bonus</td>
                                                                <td class="text-right">{{ number_format($item->bonus, 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endif
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="total-row">
                                                            <td>Total Masuk</td>
                                                            <td class="text-right">Rp {{ number_format($item->total_earnings, 0, ',', '.') }}</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </td>

                                            <!-- POTONGAN -->
                                            <td style="width: 50%; padding-left: 2px;">
                                                <table class="side-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Potongan</th>
                                                            <th class="text-right">Jumlah</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php $hasPotongan = false; @endphp
                                                        @if (($item->late_penalty_amount ?? 0) > 0)
                                                            @php $hasPotongan = true; @endphp
                                                            <tr>
                                                                <td>Denda Terlambat</td>
                                                                <td class="text-right">{{ number_format($item->late_penalty_amount, 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endif
                                                        @if (($item->alpha_penalty_amount ?? 0) > 0)
                                                            @php $hasPotongan = true; @endphp
                                                            <tr>
                                                                <td>Potongan Alfa / Absen</td>
                                                                <td class="text-right">{{ number_format($item->alpha_penalty_amount, 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endif
                                                        @if ($bpjsTkTotal > 0)
                                                            @php $hasPotongan = true; @endphp
                                                            <tr>
                                                                <td>BPJS Ketenagakerjaan</td>
                                                                <td class="text-right">{{ number_format($bpjsTkTotal, 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endif
                                                        @if ($bpjsKesTotal > 0)
                                                            @php $hasPotongan = true; @endphp
                                                            <tr>
                                                                <td>BPJS Kesehatan</td>
                                                                <td class="text-right">{{ number_format($bpjsKesTotal, 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endif
                                                        @if (($item->pph21_amount ?? 0) > 0)
                                                            @php $hasPotongan = true; @endphp
                                                            <tr>
                                                                <td>PPh 21</td>
                                                                <td class="text-right">{{ number_format($item->pph21_amount, 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endif
                                                        @if ($potonganPinjaman > 0)
                                                            @php $hasPotongan = true; @endphp
                                                            <tr>
                                                                <td>Potongan Pinjaman</td>
                                                                <td class="text-right font-bold">{{ number_format($potonganPinjaman, 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endif
                                                        @if ($otherDeductionMinusLoan > 0)
                                                            @php $hasPotongan = true; @endphp
                                                            <tr>
                                                                <td>Potongan Lainnya</td>
                                                                <td class="text-right">{{ number_format($otherDeductionMinusLoan, 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endif
                                                        @if (!$hasPotongan)
                                                            <tr>
                                                                <td class="text-center text-muted" colspan="2" style="font-style: italic;">Nihil</td>
                                                            </tr>
                                                        @endif
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="total-row">
                                                            <td>Total Potong</td>
                                                            <td class="text-right">Rp {{ number_format($item->total_deductions, 0, ',', '.') }}</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Footer: Sisa Pinjaman & Gaji Bersih (No Signature) -->
                                    <table class="footer-table">
                                        <tr>
                                            <td style="width: 44%; white-space: nowrap;">
                                                <div class="loan-box">
                                                    Sisa Pinjaman: Rp {{ number_format($sisaPinjaman, 0, ',', '.') }}
                                                </div>
                                            </td>
                                            <td style="width: 56%; text-align: right; white-space: nowrap;">
                                                <span style="font-size: 6pt; font-weight: bold; color: #334155;">GAJI BERSIH:</span>
                                                <span class="thp-amount">Rp {{ number_format($item->net_salary, 0, ',', '.') }}</span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            @endif
                        </td>
                    @endfor
                </tr>
            @endfor
        </table>

        @if ($chunkIndex < $totalChunks - 1)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>

</html>
