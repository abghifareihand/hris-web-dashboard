<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Slip THR - {{ $thr->code }}</title>
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

    @foreach ($thr->items as $index => $item)
        @php
            $emp = $item->employee;
        @endphp
        <div class="{{ !$loop->last ? 'page-break' : '' }}">

            <!-- Header -->
            <div class="header text-center mb-4">
                <h1 class="uppercase font-bold" style="font-size: 15px;">
                    {{ strtoupper($company->name_company ?? 'PT Goys Media') }}</h1>
                <p style="color: #000; font-weight: bold; font-size: 11px;">Slip THR - {{ $thr->holiday_name }} {{ $thr->year }}</p>
                <p style="color: #666; font-size: 10px;">Tanggal Pembayaran: {{ \Carbon\Carbon::parse($thr->payment_date)->format('d M Y') }}</p>
            </div>

            <!-- Employee Info -->
            <table class="info-table">
                <tr>
                    <td style="width: 55%;">
                        <table>
                            <tr>
                                <td class="info-label">Nama</td>
                                <td class="info-colon">:</td>
                                <td>{{ $emp->name }}</td>
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
                                <td>{{ $emp->bank_name ?? 'BCA' }} | <span class="font-bold">No. Rek:
                                        {{ $emp->bank_account_number ?? '-' }}</span></td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 45%;">
                        <table>
                            <tr>
                                <td class="info-label">Jabatan</td>
                                <td class="info-colon">:</td>
                                <td>{{ $emp->position->name ?? 'Staff' }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Divisi</td>
                                <td class="info-colon">:</td>
                                <td>{{ $emp->division->name ?? 'Marketing' }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Masa Kerja</td>
                                <td class="info-colon">:</td>
                                <td>{{ $item->tenure_months }} bulan</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Table 1: Rincian THR -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Rincian Perhitungan THR</th>
                        <th style="width: 35%;">Nilai (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Gaji Pokok</td>
                        <td class="text-right">{{ number_format($emp->basic_salary, 0, ',', '.') }}</td>
                    </tr>
                    @if ($item->basis_amount > $emp->basic_salary)
                        <tr>
                            <td>Tunjangan Tetap</td>
                            <td class="text-right">{{ number_format($item->basis_amount - $emp->basic_salary, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="font-bold">Dasar Perhitungan (Upah 1 Bulan)</td>
                        <td class="text-right font-bold">{{ number_format($item->basis_amount, 0, ',', '.') }}</td>
                    </tr>
                    @if ($item->prorate_multiplier < 1.0)
                        <tr>
                            <td>Pengali Prorata ({{ $item->tenure_months }}/12)</td>
                            <td class="text-right">x {{ str_replace('.', ',', (string) ((float) $item->prorate_multiplier)) }}</td>
                        </tr>
                    @else
                        <tr>
                            <td>Pengali Penuh</td>
                            <td class="text-right">x 1</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td class="font-bold">Total THR (Bruto)</td>
                        <td class="text-right font-bold">Rp {{ number_format($item->thr_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>

            @if($item->tax_amount > 0)
            <!-- Table 2: Potongan (Jika ada pajak) -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Potongan Pajak</th>
                        <th style="width: 35%;">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>PPh 21 atas THR</td>
                        <td class="text-right">- {{ number_format($item->tax_amount, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
            @endif

            <!-- Total Diterima Banner -->
            <table class="total-banner" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td class="uppercase">TOTAL THR DITERIMA</td>
                    <td class="text-right" style="width: 35%; font-size: 12px;">Rp
                        {{ number_format($item->net_amount, 0, ',', '.') }}</td>
                </tr>
            </table>

            <!-- Signature Section -->
            <table class="sig-table">
                <tr>
                    <td style="text-align: left;">
                        Diterima oleh,<br><br><br><br><br><br>
                        (___________________)
                    </td>
                    <td style="text-align: right;">
                        {{ ucwords(strtolower($company->city ?? 'Bandung')) }},
                        {{ \Carbon\Carbon::parse($thr->payment_date)->translatedFormat('d F Y') }}<br>
                        HRD Manager<br><br><br><br><br><br>
                        ( ___________________ )
                    </td>
                </tr>
            </table>

            <!-- Footer Disclaimer -->
            <div class="footer">
                <p>Slip THR ini dicetak secara elektronik dan tidak memerlukan tanda tangan basah</p>
                <p>Tanggal cetak: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</p>
            </div>

        </div>
    @endforeach

</body>

</html>
