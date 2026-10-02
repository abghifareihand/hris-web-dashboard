<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Shift;
use App\Models\SwapPersonal;
use App\Models\SwapTeam;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ScheduleManagementSeeder extends Seeder
{
    /**
     * Run database seeds for schedule management modules:
     * - 15 Shifts
     * - 15 Holidays
     * - 15 Swap Personal records
     * - 15 Swap Team records
     */
    public function run(): void
    {
        $company = Company::where('name_company', 'like', '%Goys%')->first();
        if (!$company) {
            $company = Company::first();
        }
        if (!$company) {
            return;
        }

        $owner = User::find($company->user_id) ?? User::where('role', 'owner')->first() ?? User::where('role', 'admin')->first();
        $ownerId = $owner ? $owner->id : null;

        $branches = Branch::where('company_id', $company->id)->get();
        $divisions = Division::where('company_id', $company->id)->get();
        $employees = Employee::where('company_id', $company->id)->where('is_active', true)->get();

        if ($employees->count() < 2) {
            $employees = Employee::where('company_id', $company->id)->get();
        }

        $branchBandung = $branches->firstWhere('code', 'CBG-BDO') ?? $branches->first();
        $branchJakarta = $branches->firstWhere('code', 'CBG-JKT') ?? ($branches->count() > 1 ? $branches->skip(1)->first() : null);
        $divIt = $divisions->firstWhere('name', 'IT') ?? $divisions->first();

        // =========================================================================
        // 1. SEED 15 SHIFTS
        // =========================================================================
        $shiftsList = [
            ['name' => 'Shift Reguler', 'clock_in' => '08:00:00', 'clock_out' => '17:00:00'],
            ['name' => 'Shift Reguler IT', 'clock_in' => '08:00:00', 'clock_out' => '17:00:00'],
            ['name' => 'Shift Pagi Operasional', 'clock_in' => '07:00:00', 'clock_out' => '15:30:00'],
            ['name' => 'Shift Siang Operasional', 'clock_in' => '15:00:00', 'clock_out' => '23:30:00'],
            ['name' => 'Shift Malam / NOC 24 Jam', 'clock_in' => '23:00:00', 'clock_out' => '07:30:00'],
            ['name' => 'Shift Customer Support Pagi', 'clock_in' => '06:00:00', 'clock_out' => '14:30:00'],
            ['name' => 'Shift Customer Support Sore', 'clock_in' => '14:00:00', 'clock_out' => '22:30:00'],
            ['name' => 'Shift Customer Support Malam', 'clock_in' => '22:00:00', 'clock_out' => '06:30:00'],
            ['name' => 'Shift Middle Fleksibel', 'clock_in' => '09:00:00', 'clock_out' => '18:00:00'],
            ['name' => 'Shift Eksekutif / Head Office', 'clock_in' => '08:30:00', 'clock_out' => '17:30:00'],
            ['name' => 'Shift Logistik & Gudang Pagi', 'clock_in' => '06:30:00', 'clock_out' => '15:00:00'],
            ['name' => 'Shift Logistik & Gudang Siang', 'clock_in' => '14:30:00', 'clock_out' => '23:00:00'],
            ['name' => 'Shift Weekend Standby', 'clock_in' => '08:00:00', 'clock_out' => '16:00:00'],
            ['name' => 'Shift Part Time Sesi Pagi', 'clock_in' => '08:00:00', 'clock_out' => '12:00:00'],
            ['name' => 'Shift Maintenance Server Subuh', 'clock_in' => '00:00:00', 'clock_out' => '06:00:00'],
        ];

        // Keep or create exactly 15 shifts
        $createdShifts = [];
        foreach ($shiftsList as $sData) {
            $createdShifts[] = Shift::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => $sData['name'],
                ],
                [
                    'clock_in' => $sData['clock_in'],
                    'clock_out' => $sData['clock_out'],
                ]
            );
        }
        Shift::where('company_id', $company->id)->whereNotIn('name', array_column($shiftsList, 'name'))->delete();

        // =========================================================================
        // 2. SEED 15 HARI LIBUR (HOLIDAYS)
        // =========================================================================
        Holiday::where('company_id', $company->id)->delete();

        $holidaysList = [
            [
                'name' => 'Tahun Baru 2026 Masehi',
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-01',
                'branch_id' => null,
                'division_id' => null,
            ],
            [
                'name' => 'Isra Mi\'raj Nabi Muhammad SAW 1447 H',
                'start_date' => '2026-01-16',
                'end_date' => '2026-01-16',
                'branch_id' => null,
                'division_id' => null,
            ],
            [
                'name' => 'Tahun Baru Imlek 2577 Kongzili',
                'start_date' => '2026-02-17',
                'end_date' => '2026-02-17',
                'branch_id' => null,
                'division_id' => null,
            ],
            [
                'name' => 'Hari Suci Nyepi Tahun Baru Saka 1948',
                'start_date' => '2026-03-19',
                'end_date' => '2026-03-19',
                'branch_id' => null,
                'division_id' => null,
            ],
            [
                'name' => 'Hari Raya Idul Fitri 1447 H',
                'start_date' => '2026-03-20',
                'end_date' => '2026-03-21',
                'branch_id' => null,
                'division_id' => null,
            ],
            [
                'name' => 'Cuti Bersama Hari Raya Idul Fitri 1447 H',
                'start_date' => '2026-03-23',
                'end_date' => '2026-03-25',
                'branch_id' => null,
                'division_id' => null,
            ],
            [
                'name' => 'Wafat Yesus Kristus',
                'start_date' => '2026-04-03',
                'end_date' => '2026-04-03',
                'branch_id' => null,
                'division_id' => null,
            ],
            [
                'name' => 'Hari Paskah Kebangkitan Yesus Kristus',
                'start_date' => '2026-04-05',
                'end_date' => '2026-04-05',
                'branch_id' => null,
                'division_id' => null,
            ],
            [
                'name' => 'Hari Buruh Internasional (May Day)',
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-01',
                'branch_id' => null,
                'division_id' => null,
            ],
            [
                'name' => 'Kenaikan Yesus Kristus',
                'start_date' => '2026-05-14',
                'end_date' => '2026-05-14',
                'branch_id' => null,
                'division_id' => null,
            ],
            [
                'name' => 'Hari Raya Idul Adha 1447 H',
                'start_date' => '2026-05-27',
                'end_date' => '2026-05-27',
                'branch_id' => null,
                'division_id' => null,
            ],
            [
                'name' => 'Hari Raya Waisak 2570 BE',
                'start_date' => '2026-05-31',
                'end_date' => '2026-05-31',
                'branch_id' => null,
                'division_id' => null,
            ],
            [
                'name' => 'Hari Lahir Pancasila',
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-01',
                'branch_id' => null,
                'division_id' => null,
            ],
            [
                'name' => 'Hari Kemerdekaan Republik Indonesia Ke-81',
                'start_date' => '2026-08-17',
                'end_date' => '2026-08-17',
                'branch_id' => null,
                'division_id' => null,
            ],
            [
                'name' => 'Hari Raya Natal & Cuti Bersama Akhir Tahun',
                'start_date' => '2026-12-25',
                'end_date' => '2026-12-26',
                'branch_id' => null,
                'division_id' => null,
            ],
        ];

        // Specific regional branch/division holidays for rich UI testing
        if ($branchBandung) {
            $holidaysList[11]['name'] = 'HUT Kota Bandung Ke-216';
            $holidaysList[11]['start_date'] = '2026-09-25';
            $holidaysList[11]['end_date'] = '2026-09-25';
            $holidaysList[11]['branch_id'] = $branchBandung->id;
        }
        if ($branchJakarta) {
            $holidaysList[12]['name'] = 'HUT DKI Jakarta Ke-499';
            $holidaysList[12]['start_date'] = '2026-06-22';
            $holidaysList[12]['end_date'] = '2026-06-22';
            $holidaysList[12]['branch_id'] = $branchJakarta->id;
        }

        foreach ($holidaysList as $h) {
            Holiday::create([
                'company_id' => $company->id,
                'branch_id' => $h['branch_id'],
                'division_id' => $h['division_id'],
                'name' => $h['name'],
                'start_date' => $h['start_date'],
                'end_date' => $h['end_date'],
            ]);
        }

        // =========================================================================
        // 3. SEED 15 TUKAR JADWAL PRIBADI (SWAP PERSONAL)
        // =========================================================================
        SwapPersonal::where('company_id', $company->id)->delete();

        $personalReasons = [
            'Keperluan keluarga mendesak dan menghadiri akad nikah saudara kandung di luar kota',
            'Jadwal kontrol kesehatan rutin dan pemeriksaan berkala di rumah sakit rujukan',
            'Perpanjangan SIM, STNK, dan pengurusan berkas administrasi kependudukan di Samsat',
            'Menggantikan giliran piket dengan hari kerja pengganti di akhir pekan berjalan',
            'Menghadiri wisuda kelulusan sarjana adik kandung di universitas',
            'Renovasi instalasi listrik dan atap rumah membutuhkan pengawasan teknis langsung',
            'Pendampingan orang tua yang sedang menjalani rawat inap di rumah sakit',
            'Menghadiri upacara adat dan keagamaan tahunan di lingkungan tempat tinggal',
            'Panggilan verifikasi dokumen sertifikat tanah di kantor pertanahan nasional',
            'Menemani balita vaksinasi dan imunisasi terjadwal di puskesmas setempat',
            'Proses persiapan pindahan rumah kontrakan baru bersama keluarga',
            'Kendaraan operasional pribadi mengalami kendala transmisi dan masuk bengkel servis',
            'Mengikuti ujian sertifikasi keahlian profesional Cloud Architect secara online',
            'Kunjungan silaturahmi keluarga besar dari luar provinsi',
            'Tukar shift dinas untuk mendampingi pasangan melahirkan di klinik bersalin',
        ];

        // 6 Approved, 5 Pending, 4 Rejected = 15 total
        $personalStatuses = [
            'approved', 'approved', 'approved', 'approved', 'approved', 'approved',
            'pending', 'pending', 'pending', 'pending', 'pending',
            'rejected', 'rejected', 'rejected', 'rejected',
        ];

        $empCount = $employees->count();
        if ($empCount > 0) {
            for ($i = 0; $i < 15; $i++) {
                $emp = $employees[$i % $empCount];
                $status = $personalStatuses[$i];

                $dayOffset = ($i * 2) + 1;
                $origDate = Carbon::create(2026, 9, 1)->addDays($dayOffset);
                $targetDate = (clone $origDate)->addDays(($i % 2 === 0) ? 2 : 3);

                // Ensure schedules exist on both dates for smooth testing
                $primaryShift = $createdShifts[$i % count($createdShifts)];
                $secondaryShift = $createdShifts[($i + 1) % count($createdShifts)];

                WorkSchedule::updateOrCreate(
                    ['employee_id' => $emp->id, 'date' => $origDate->format('Y-m-d')],
                    [
                        'company_id' => $company->id,
                        'shift_id' => $primaryShift->id,
                        'is_day_off' => false,
                        'attendance_type' => 'all',
                    ]
                );

                WorkSchedule::updateOrCreate(
                    ['employee_id' => $emp->id, 'date' => $targetDate->format('Y-m-d')],
                    [
                        'company_id' => $company->id,
                        'shift_id' => $secondaryShift->id,
                        'is_day_off' => false,
                        'attendance_type' => 'all',
                    ]
                );

                SwapPersonal::create([
                    'company_id' => $company->id,
                    'employee_id' => $emp->id,
                    'original_work_date' => $origDate->format('Y-m-d'),
                    'target_work_date' => $targetDate->format('Y-m-d'),
                    'reason' => $personalReasons[$i],
                    'status' => $status,
                    'approved_by' => ($status === 'pending') ? null : $ownerId,
                    'created_at' => (clone $origDate)->subDays(3)->format('Y-m-d H:i:s'),
                    'updated_at' => (clone $origDate)->subDays(1)->format('Y-m-d H:i:s'),
                ]);
            }
        }

        // =========================================================================
        // 4. SEED 15 TUKAR JADWAL TIM (SWAP TEAM)
        // =========================================================================
        SwapTeam::where('company_id', $company->id)->delete();

        $teamReasons = [
            'Saling tukar shift dengan rekan satu divisi karena ingin menghadiri acara keluarga',
            'Rekan sepakat bertukar shift kerja untuk backup coverage proses deployment sistem',
            'Tukar jadwal kerja dinas agar bisa menyelesaikan target sprint kuartal bersama',
            'Kesepakatan saling ganti shift: pemohon mengambil shift malam, rekan mengambil shift pagi',
            'Tukar shift hari kerja reguler karena rekan berhalangan hadir pada sesi pagi',
            'Penyesuaian jadwal standby monitoring server: pertukaran telah disepakati kedua belah pihak',
            'Tukar shift akhir pekan agar rekan mendapat hari libur berurutan untuk hajatan nikah',
            'Rekan meminta bantuan bertukar hari kerja untuk keperluan mendesak keluarga',
            'Saling menggantikan shift operasional cabang demi kesinambungan layanan pelanggan',
            'Pertukaran jadwal shift tim IT Support untuk monitoring live rilis modul aplikasi',
            'Saling tukar shift agar rekan kerja bisa menghadiri seminar keahlian teknologi tahunan',
            'Tukar jadwal piket kantor karena rekan harus mendampingi orang tua periksa laboratorium',
            'Pertukaran giliran shift dinas malam yang telah disetujui internal team supervisor',
            'Tukar shift dinas lapangan untuk menyesuaikan jadwal inspeksi perangkat jaringan cabang',
            'Saling bertukar jadwal kerja akhir pekan untuk persiapan audit kepatuhan ISO internal',
        ];

        // 6 Approved, 5 Pending, 4 Rejected = 15 total
        $teamStatuses = [
            'approved', 'approved', 'approved', 'approved', 'approved', 'approved',
            'pending', 'pending', 'pending', 'pending', 'pending',
            'rejected', 'rejected', 'rejected', 'rejected',
        ];

        if ($empCount >= 2) {
            for ($i = 0; $i < 15; $i++) {
                $reqIndex = $i % $empCount;
                $tarIndex = ($i + 1) % $empCount;
                if ($reqIndex === $tarIndex) {
                    $tarIndex = ($tarIndex + 1) % $empCount;
                }

                $requestor = $employees[$reqIndex];
                $targetEmployee = $employees[$tarIndex];
                $status = $teamStatuses[$i];

                $dayOffset = ($i * 2) + 2;
                $reqDate = Carbon::create(2026, 9, 1)->addDays($dayOffset);
                $tarDate = (clone $reqDate)->addDays(($i % 2 === 0) ? 1 : 2);

                $reqShift = $createdShifts[$i % count($createdShifts)];
                $tarShift = $createdShifts[($i + 2) % count($createdShifts)];

                // Ensure schedules exist on both dates for both employees
                WorkSchedule::updateOrCreate(
                    ['employee_id' => $requestor->id, 'date' => $reqDate->format('Y-m-d')],
                    [
                        'company_id' => $company->id,
                        'shift_id' => $reqShift->id,
                        'is_day_off' => false,
                        'attendance_type' => 'all',
                    ]
                );

                WorkSchedule::updateOrCreate(
                    ['employee_id' => $targetEmployee->id, 'date' => $tarDate->format('Y-m-d')],
                    [
                        'company_id' => $company->id,
                        'shift_id' => $tarShift->id,
                        'is_day_off' => false,
                        'attendance_type' => 'all',
                    ]
                );

                SwapTeam::create([
                    'company_id' => $company->id,
                    'requestor_id' => $requestor->id,
                    'target_employee_id' => $targetEmployee->id,
                    'requestor_work_date' => $reqDate->format('Y-m-d'),
                    'target_work_date' => $tarDate->format('Y-m-d'),
                    'reason' => $teamReasons[$i],
                    'status' => $status,
                    'approved_by' => ($status === 'pending') ? null : $ownerId,
                    'created_at' => (clone $reqDate)->subDays(3)->format('Y-m-d H:i:s'),
                    'updated_at' => (clone $reqDate)->subDays(1)->format('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
