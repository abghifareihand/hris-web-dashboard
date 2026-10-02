<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $payroll->code }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 0;
            padding: 0;
        }

        .page-break {
            page-break-after: always;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .mb-2 {
            margin-bottom: 8px;
        }

        .mb-4 {
            margin-bottom: 16px;
        }

        .mt-4 {
            margin-top: 16px;
        }

        .header h1 {
            font-size: 18px;
            margin: 0 0 2px 0;
            /* Reduced margin */
            letter-spacing: 0.5px;
        }

        .header p {
            margin: 0;
            font-size: 11px;
            color: #444;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Employee Info */
        .info-table {
            margin-bottom: 15px;
        }

        .info-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .info-label {
            width: 70px;
            font-weight: bold;
        }

        .info-colon {
            width: 10px;
            font-weight: bold;
        }

        /* Data Tables - Grid Style */
        .data-table {
            margin-bottom: 12px;
            border: 1px solid #ccc;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #ccc;
            /* Grid lines */
            padding: 5px 10px;
        }

        .data-table th {
            background-color: #fafafa;
            text-align: center;
        }

        .data-table tfoot td {
            font-weight: bold;
            background-color: #fafafa;
        }

        /* Banner Table */
        .total-banner {
            margin-bottom: 12px;
            border: 1px solid #ccc;
        }

        .total-banner td {
            padding: 7px 10px;
            font-weight: bold;
            border: 1px solid #ccc;
        }

        .total-banner td:first-child {
            text-align: center;
            background-color: #fafafa;
        }

        /* Signatures */
        .sig-table {
            margin-top: 40px;
            width: 100%;
        }

        .sig-table td {
            width: 50%;
            vertical-align: bottom;
            height: 60px;
        }

        .footer {
            margin-top: 25px;
            padding-top: 8px;
            text-align: center;
            font-size: 9px;
            color: #777;
        }

        .footer p {
            margin: 2px 0;
        }
    </style>
</head>

<body>

    @foreach ($payroll->items as $index => $item)
        @php
            $emp = $item->employee;
        @endphp
        <div class="{{ !$loop->last ? 'page-break' : '' }}">

            <!-- Header -->
            <div class="header text-center mb-4">
                <h1 class="uppercase font-bold" style="font-size: 15px;">
                    {{ strtoupper($company->name_company ?? ($company->name ?? 'PERUSAHAAN')) }}</h1>
                <p style="color: #000; font-weight: bold; font-size: 11px;">Slip Gaji Periode
                    {{ \Carbon\Carbon::parse($payroll->start_date)->format('d M Y') }} -
                    {{ \Carbon\Carbon::parse($payroll->end_date)->format('d M Y') }}</p>
            </div>

            <!-- Employee Info -->
            <table class="info-table">
                <tr>
                    <td style="width: 55%;">
                        <table>
                            <tr>
                                <td class="info-label">Nama</td>
                                <td class="info-colon">:</td>
                                <td>{{ $emp->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">NIK</td>
                                <td class="info-colon">:</td>
                                <td>{{ $emp->nik ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">NPWP</td>
                                <td class="info-colon">:</td>
                                <td>{{ $emp->npwp ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Bank</td>
                                <td class="info-colon">:</td>
                                <td>{{ $emp->bank_name ?? '-' }} | <span class="font-bold">No. Rek:
                                        {{ $emp->bank_account_number ?? '-' }}</span></td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 45%;">
                        <table>
                            <tr>
                                <td class="info-label">Jabatan</td>
                                <td class="info-colon">:</td>
                                <td>{{ $emp?->position?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Divisi</td>
                                <td class="info-colon">:</td>
                                <td>{{ $emp?->division?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">PTKP</td>
                                <td class="info-colon">:</td>
                                <td>{{ $item->ptkp_status ?? 'TK/0' }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Table 1: Komponen Penghasilan -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Komponen Penghasilan</th>
                        <th style="width: 35%;">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Gaji Pokok</td>
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
                            <td>Reimbursement / Klaim Biaya</td>
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
                    <tr>
                        <td class="font-bold">Total Penghasilan</td>
                        <td class="text-right font-bold">Rp {{ number_format($item->total_earnings, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>

            <!-- Table 2: Komponen Potongan -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Komponen Potongan</th>
                        <th style="width: 35%;">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @php $hasPotongan = false; @endphp

                    {{-- Denda & Potongan Lainnya --}}
                    @if ($item->late_penalty_amount > 0)
                        @php $hasPotongan = true; @endphp
                        <tr>
                            <td>Pinalty</td>
                            <td class="text-right">- {{ number_format($item->late_penalty_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if ($item->alpha_penalty_amount > 0)
                        @php $hasPotongan = true; @endphp
                        <tr>
                            <td>Alpha</td>
                            <td class="text-right">- {{ number_format($item->alpha_penalty_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if ($item->other_deductions > 0)
                        @php
                            $hasPotongan = true;
                            $deductionLabel = 'Potongan Lainnya';
                            $remarksLower = strtolower($item->remarks ?? '');
                            $hasLoan = !empty($item->loan_installment_id) || str_contains($remarksLower, 'kasbon') || str_contains($remarksLower, 'pinjaman');
                            $hasProrata = str_contains($remarksLower, 'prorata');

                            if ($hasLoan && $hasProrata) {
                                $deductionLabel = 'Potongan Lainnya (Gaji Prorata & Pinjaman)';
                            } elseif ($hasLoan) {
                                $deductionLabel = 'Potongan Lainnya (Pinjaman)';
                            } elseif ($hasProrata) {
                                $deductionLabel = 'Potongan Lainnya (Gaji Prorata)';
                            } elseif (!empty($item->remarks)) {
                                $deductionLabel = 'Potongan Lainnya (' . $item->remarks . ')';
                            }
                        @endphp
                        <tr>
                            <td>{{ $deductionLabel }}</td>
                            <td class="text-right">- {{ number_format($item->other_deductions, 0, ',', '.') }}</td>
                        </tr>
                    @endif

                    {{-- BPJS & Pajak --}}
                    @if ($item->bpjs_kesehatan_employee > 0)
                        @php $hasPotongan = true; @endphp
                        <tr>
                            <td>BPJS Kesehatan ({{ (float) ($bpjsKes->employee_percent ?? 1) }}%)</td>
                            <td class="text-right">- {{ number_format($item->bpjs_kesehatan_employee, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                    @if ($item->jht_employee > 0)
                        @php $hasPotongan = true; @endphp
                        <tr>
                            <td>BPJS Ketenagakerjaan (JHT {{ (float) ($bpjsTk->jht_employee_percent ?? 2) }}%)</td>
                            <td class="text-right">- {{ number_format($item->jht_employee, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if ($item->jp_employee > 0)
                        @php $hasPotongan = true; @endphp
                        <tr>
                            <td>BPJS Ketenagakerjaan (JP {{ (float) ($bpjsTk->jp_employee_percent ?? 1) }}%)</td>
                            <td class="text-right">- {{ number_format($item->jp_employee, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if ($item->pph21_amount > 0)
                        @php $hasPotongan = true; @endphp
                        <tr>
                            <td>PPH 21</td>
                            <td class="text-right">- {{ number_format($item->pph21_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif

                    @if (!$hasPotongan)
                        <tr>
                            <td class="text-center" style="color: #888; font-style: italic;" colspan="2">Nihil</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td class="font-bold">Total Potongan</td>
                        <td class="text-right font-bold">- Rp {{ number_format($item->total_deductions, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>

            <!-- Total Gaji Diterima Banner -->
            <table class="total-banner" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td class="uppercase">TOTAL GAJI DITERIMA</td>
                    <td class="text-right" style="width: 35%; font-size: 12px;">Rp
                        {{ number_format($item->net_salary, 0, ',', '.') }}</td>
                </tr>
            </table>

            <!-- Table 3: Manfaat / Benefit -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Manfaat/Benefit dari perusahaan</th>
                        <th style="width: 35%;">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($item->bpjs_kesehatan_company > 0)
                        <tr>
                            <td>BPJS Kesehatan ({{ (float) ($bpjsKes->company_percent ?? 4) }}%)</td>
                            <td class="text-right">{{ number_format($item->bpjs_kesehatan_company, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if ($item->jht_company > 0)
                        <tr>
                            <td>BPJS Ketenagakerjaan (JHT {{ (float) ($bpjsTk->jht_company_percent ?? 3.7) }}%)</td>
                            <td class="text-right">{{ number_format($item->jht_company, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if ($item->jp_company > 0)
                        <tr>
                            <td>BPJS Ketenagakerjaan (JP {{ (float) ($bpjsTk->jp_company_percent ?? 2) }}%)</td>
                            <td class="text-right">{{ number_format($item->jp_company, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if ($item->jkk_company > 0)
                        <tr>
                            <td>BPJS Ketenagakerjaan (JKK {{ (float) ($bpjsTk->jkk_percent ?? 0.24) }}%)</td>
                            <td class="text-right">{{ number_format($item->jkk_company, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if ($item->jkm_company > 0)
                        <tr>
                            <td>BPJS Ketenagakerjaan (JKM {{ (float) ($bpjsTk->jkm_percent ?? 0.3) }}%)</td>
                            <td class="text-right">{{ number_format($item->jkm_company, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td class="font-bold">Total Manfaat</td>
                        <td class="text-right font-bold">Rp {{ number_format($item->total_benefits, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>

            <!-- Signature Section -->
            <table class="sig-table">
                <tr>
                    <td style="text-align: left;">
                        Diterima oleh,<br><br><br><br><br>
                        ( <strong>{{ $emp->name ?? 'Karyawan' }}</strong> )
                    </td>
                    <td style="text-align: right;">
                        {{ ucwords(strtolower($company->city ?? ($company->province ?? 'Indonesia'))) }},
                        {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                        Dibuat / Disetujui oleh,<br><br><br><br><br>
                        ( ___________________ )
                    </td>
                </tr>
            </table>

            <!-- Footer Disclaimer -->
            <div class="footer">
                <p>Slip gaji ini dicetak secara elektronik dan tidak memerlukan tanda tangan basah</p>
                <p>Tanggal cetak: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</p>
            </div>

        </div>
    @endforeach

</body>

</html>
