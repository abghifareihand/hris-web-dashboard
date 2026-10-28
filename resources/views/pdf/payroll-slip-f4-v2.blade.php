<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Slip Gaji F4 (8 Grid) - {{ $payroll->code }}</title>
    <style>
        @page {
            size: 215mm 330mm portrait;
            margin: 4mm 4mm 4mm 4mm;
        }

        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif !important;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 6.2pt;
            color: #000000;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        .page-break {
            page-break-after: always;
        }

        /* 8-Grid Table: Exactly 2 columns x 4 rows per F4 sheet */
        table.grid-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        td.slip-cell {
            width: 50%;
            vertical-align: top;
            padding: 1.5mm;
            box-sizing: border-box;
            background-color: #ffffff;
        }

        /* Uniform outer border for each slip box */
        .slip-box {
            width: 100%;
            border: 0.5pt solid #000000;
            background-color: #ffffff;
            box-sizing: border-box;
        }

        /* Header Title */
        .slip-title {
            text-align: center;
            font-weight: bold;
            font-size: 7.2pt;
            text-transform: uppercase;
            padding: 2.5px 4px;
            border-bottom: 0.5pt solid #000000;
            letter-spacing: 0.2px;
            background-color: #ffffff;
            color: #000000;
            line-height: 1.15;
        }

        /* Meta Rows: Nama, Tanggal, Div/Jab, Telat, Periode, Absen Kerja */
        table.meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.2pt;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        table.meta-table td {
            padding: 1.8px 4px;
            vertical-align: middle;
            border-bottom: 0.5pt solid #000000;
            line-height: 1.15;
            background-color: #ffffff;
            color: #000000;
        }

        /* Unified 4-Column Financial Table:
           Col 1: Penghasilan Label (29%)
           Col 2: Penghasilan Jumlah (21%) with border-right
           Col 3: Potongan Label (32%)
           Col 4: Potongan Jumlah (18%)
        */
        table.slip-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6pt;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        table.slip-table th {
            background-color: #ffffff;
            color: #000000;
            border-bottom: 0.5pt solid #000000;
            font-weight: bold;
            padding: 1.6px 3px;
            line-height: 1.15;
            text-align: left;
            font-size: 6pt;
        }

        table.slip-table th.th-inc-val {
            text-align: right;
            border-right: 0.5pt solid #000000;
        }

        table.slip-table th.th-ded-val {
            text-align: right;
        }

        table.slip-table td {
            padding: 0.9px 3px;
            line-height: 1.15;
            background-color: #ffffff;
            color: #000000;
            vertical-align: middle;
        }

        table.slip-table td.td-inc-val {
            text-align: right;
            border-right: 0.5pt solid #000000;
        }

        table.slip-table td.td-ded-val {
            text-align: right;
        }

        /* Total Row: perfectly aligned across both columns */
        table.slip-table tr.total-row td {
            border-top: 0.5pt solid #000000;
            border-bottom: 0.5pt solid #000000;
            font-weight: bold;
            padding: 1.6px 3px;
            background-color: #ffffff;
            color: #000000;
            line-height: 1.15;
        }

        table.slip-table tr.total-row td.td-inc-total-val {
            text-align: right;
            border-right: 0.5pt solid #000000;
        }

        table.slip-table tr.total-row td.td-ded-total-val {
            text-align: right;
        }

        /* Summary Footer (Gaji Yang Diterima & Sisa Pinjaman Pokok) */
        table.summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.2pt;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        table.summary-table td {
            padding: 1.8px 4px;
            vertical-align: middle;
            line-height: 1.15;
            background-color: #ffffff;
            color: #000000;
        }

        .sum-label {
            width: 44%;
            white-space: nowrap;
        }

        .sum-sep {
            width: 8%;
            white-space: nowrap;
        }

        .sum-val {
            width: 48%;
            text-align: right;
            white-space: nowrap;
            font-family: Arial, Helvetica, sans-serif !important;
        }

        .font-bold {
            font-weight: bold;
        }
    </style>
</head>

<body>
    @php
        $chunks = $payroll->items->chunk(8);
        $totalChunks = $chunks->count();
        $dateFormatted = Carbon\Carbon::parse($payroll->end_date)->translatedFormat('l, d/m/Y');
        $periodText = Carbon\Carbon::parse($payroll->start_date)->translatedFormat('d M Y') . ' - ' . Carbon\Carbon::parse($payroll->end_date)->translatedFormat('d M Y');
        $companyName = strtoupper($company->name_company ?? ($company->name ?? 'CV MEDIA NUGROHO MANDIRI'));
    @endphp

    @foreach ($chunks as $chunkIndex => $chunk)
        @php
            $items = $chunk->values();
        @endphp

        <table class="grid-table">
            @for ($row = 0; $row < 4; $row++)
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

                                    $att = $attendanceSummaries[$item->employee_id] ?? null;
                                    $presentDays = $att['present'] ?? ($emp->payroll_cycle === 'weekly' ? 6 : 25);
                                    $lateTimes = ($att['late'] ?? 0) > 0 ? ($att['late'] . ' KALI') : '-';
                                    $divJab = ($emp->division?->name ?? '-') . ' / ' . ($emp->position?->name ?? '-');

                                    // Collect Earnings (> 0 only, except basic salary)
                                    $earnings = [];
                                    $earnings[] = ['label' => $cycleLabel, 'amount' => $item->basic_salary];
                                    if ($item->fixed_allowance > 0) {
                                        $earnings[] = ['label' => 'Tunjangan Tetap', 'amount' => $item->fixed_allowance];
                                    }
                                    if ($item->daily_allowance > 0) {
                                        $earnings[] = ['label' => 'Tunjangan Harian', 'amount' => $item->daily_allowance];
                                    }
                                    if ($item->other_allowance > 0) {
                                        $earnings[] = ['label' => 'Tunjangan Lainnya', 'amount' => $item->other_allowance];
                                    }
                                    if ($item->overtime_amount > 0) {
                                        $earnings[] = ['label' => 'Upah Lembur', 'amount' => $item->overtime_amount];
                                    }
                                    if (($item->reimbursement_amount ?? 0) > 0) {
                                        $earnings[] = ['label' => 'Klaim Biaya', 'amount' => $item->reimbursement_amount];
                                    }
                                    if ($item->bonus > 0) {
                                        $earnings[] = ['label' => 'Bonus', 'amount' => $item->bonus];
                                    }

                                    // Collect Deductions (> 0 only)
                                    $deductions = [];
                                    if (($item->late_penalty_amount ?? 0) > 0) {
                                        $deductions[] = ['label' => 'Denda Terlambat', 'amount' => $item->late_penalty_amount];
                                    }
                                    if (($item->alpha_penalty_amount ?? 0) > 0) {
                                        $deductions[] = ['label' => 'Potongan Ketidakhadiran (Alpha)', 'amount' => $item->alpha_penalty_amount];
                                    }
                                    if ($bpjsTkTotal > 0) {
                                        $deductions[] = ['label' => 'BPJS Ketenagakerjaan', 'amount' => $bpjsTkTotal];
                                    }
                                    if ($bpjsKesTotal > 0) {
                                        $deductions[] = ['label' => 'BPJS Kesehatan', 'amount' => $bpjsKesTotal];
                                    }
                                    if (($item->pph21_amount ?? 0) > 0) {
                                        $deductions[] = ['label' => 'PPh 21', 'amount' => $item->pph21_amount];
                                    }
                                    if ($potonganPinjaman > 0) {
                                        $deductions[] = ['label' => 'Potongan Pinjaman', 'amount' => $potonganPinjaman];
                                    }
                                    if ($otherDeductionMinusLoan > 0) {
                                        $deductions[] = ['label' => 'Potongan Lainnya', 'amount' => $otherDeductionMinusLoan];
                                    }

                                    $maxRows = max(count($earnings), count($deductions));
                                    if ($maxRows === 0) {
                                        $maxRows = 1;
                                    }
                                @endphp

                                <div class="slip-box">
                                    <!-- Title Box -->
                                    <div class="slip-title">
                                        {{ $companyName }} / SLIP GAJI KARYAWAN
                                    </div>

                                    <!-- Meta Rows: Nama, Div/Jab, Hari/Tanggal, Telat, Periode, Absen Kerja -->
                                    <table class="meta-table">
                                        <tr>
                                            <td style="width: 52%; font-weight: bold;">
                                                NAMA : {{ strtoupper($emp->name ?? '-') }}
                                            </td>
                                            <td style="width: 48%; text-align: right;">
                                                HARI / TANGGAL : {{ $dateFormatted }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 52%;">
                                                DIV/JAB : {{ strtoupper($divJab) }}
                                            </td>
                                            <td style="width: 48%; text-align: right;">
                                                TELAT : {{ $lateTimes }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 52%;">
                                                PERIODE : {{ $periodText }}
                                            </td>
                                            <td style="width: 48%; text-align: right;">
                                                ABSEN KERJA : {{ $presentDays }} HARI
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Unified 4-Column Financial Table: Penghasilan (Kiri) vs Potongan (Kanan) -->
                                    <table class="slip-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 29%;">Penghasilan</th>
                                                <th class="th-inc-val" style="width: 21%;">Jumlah</th>
                                                <th style="width: 32%;">Potongan</th>
                                                <th class="th-ded-val" style="width: 18%;">Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @for ($i = 0; $i < $maxRows; $i++)
                                                @php
                                                    $inc = $earnings[$i] ?? null;
                                                    $ded = $deductions[$i] ?? null;
                                                @endphp
                                                <tr>
                                                    <td style="width: 29%;">{{ $inc ? $inc['label'] : '' }}</td>
                                                    <td class="td-inc-val" style="width: 21%;">{{ $inc ? number_format($inc['amount'], 0, ',', '.') : '' }}</td>
                                                    @if (empty($deductions) && $i === 0)
                                                        <td style="width: 32%; font-style: italic;">Nihil</td>
                                                        <td class="td-ded-val" style="width: 18%;">-</td>
                                                    @else
                                                        <td style="width: 32%;">{{ $ded ? $ded['label'] : '' }}</td>
                                                        <td class="td-ded-val" style="width: 18%;">{{ $ded ? number_format($ded['amount'], 0, ',', '.') : '' }}</td>
                                                    @endif
                                                </tr>
                                            @endfor

                                            <!-- Total Row: Always aligned at same vertical position -->
                                            <tr class="total-row">
                                                <td style="width: 29%;">Total Masuk</td>
                                                <td class="td-inc-total-val" style="width: 21%;">Rp {{ number_format($item->total_earnings, 0, ',', '.') }}</td>
                                                <td style="width: 32%;">Total Potong</td>
                                                <td class="td-ded-total-val" style="width: 18%;">Rp {{ number_format($item->total_deductions, 0, ',', '.') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <!-- Summary Footer: GAJI YANG DI TERIMA & SISA PINJAMAN POKOK -->
                                    <table class="summary-table">
                                        <tr>
                                            <td class="sum-label font-bold">GAJI YANG DI TERIMA</td>
                                            <td class="sum-sep font-bold">: Rp</td>
                                            <td class="sum-val font-bold">{{ number_format($item->net_salary, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="sum-label font-bold">SISA PINJAMAN POKOK</td>
                                            <td class="sum-sep font-bold">: Rp</td>
                                            <td class="sum-val font-bold">{{ $sisaPinjaman > 0 ? number_format($sisaPinjaman, 0, ',', '.') : '-' }}</td>
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
