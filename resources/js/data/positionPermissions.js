/**
 * Konfigurasi Hak Akses Menu Jabatan (RBAC Matrix)
 * Menentukan daftar menu valid sesuai fitur Web Owner & Mobile Karyawan,
 * badge platform (WEB & MOBILE / WEB / MOBILE),
 * batasan aksi (disabled), serta nilai default saat membuat jabatan baru.
 */

export const POSITION_MENU_PERMISSIONS = [
    // -------------------------------------------------------------------------
    // 1. MANAJEMEN KARYAWAN & PERUSAHAAN
    // -------------------------------------------------------------------------
    {
        key: 'employee_data',
        title: 'Data Karyawan',
        platform: 'WEB & MOBILE',
        desc: 'Hak akses untuk melihat, menambah, mengubah, dan menghapus data profil karyawan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_profile',
        title: 'Profil Perusahaan',
        platform: 'WEB',
        desc: 'Hak akses untuk melihat dan memperbarui informasi identitas & profil perusahaan',
        disabled: { view: false, create: true, edit: false, delete: true },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'branches',
        title: 'Cabang Perusahaan',
        platform: 'WEB & MOBILE',
        desc: 'Hak akses untuk manajemen kantor cabang perusahaan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'divisions',
        title: 'Divisi Kerja',
        platform: 'WEB & MOBILE',
        desc: 'Hak akses untuk manajemen departemen dan divisi kerja',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'positions',
        title: 'Jabatan & Hak Akses',
        platform: 'WEB & MOBILE',
        desc: 'Hak akses untuk manajemen jabatan dan pengaturan matriks hak akses (RBAC)',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },

    // -------------------------------------------------------------------------
    // 2. PENJADWALAN & WAKTU KERJA
    // -------------------------------------------------------------------------
    {
        key: 'shifts',
        title: 'Shift Kerja',
        platform: 'WEB & MOBILE',
        desc: 'Hak akses untuk manajemen jam kerja dan master shift karyawan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'holidays',
        title: 'Hari Libur',
        platform: 'WEB & MOBILE',
        desc: 'Hak akses untuk manajemen kalender libur nasional dan hari libur perusahaan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'employee_schedules',
        title: 'Jadwal Kerja Karyawan',
        platform: 'WEB & MOBILE',
        desc: 'Hak akses untuk mengatur penempatan jadwal dan rotasi shift harian karyawan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'swap_personal',
        title: 'Tukar Shift Pribadi',
        platform: 'WEB & MOBILE',
        desc: 'Pengajuan dan persetujuan penukaran jadwal shift antar perorangan',
        disabled: { view: false, create: false, edit: true, delete: false },
        defaults: { view: true, create: true, edit: false, delete: true },
    },
    {
        key: 'swap_team',
        title: 'Tukar Shift Antar Tim',
        platform: 'WEB & MOBILE',
        desc: 'Pengajuan dan persetujuan penukaran jadwal shift rombongan/antar regu kerja',
        disabled: { view: false, create: false, edit: true, delete: false },
        defaults: { view: true, create: true, edit: false, delete: true },
    },

    // -------------------------------------------------------------------------
    // 3. CUTI & LEMBUR
    // -------------------------------------------------------------------------
    {
        key: 'leaves',
        title: 'Cuti & Izin',
        platform: 'WEB & MOBILE',
        desc: 'Pengajuan cuti pribadi serta persetujuan dan pembatalan cuti bawahan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: true, create: true, edit: false, delete: true },
    },
    {
        key: 'leave_balances',
        title: 'Sisa Cuti Karyawan',
        platform: 'WEB',
        desc: 'Melihat rekapitulasi dan mengelola penyesuaian kuota sisa cuti tahunan karyawan',
        disabled: { view: false, create: true, edit: false, delete: true },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'leave_categories',
        title: 'Kategori Cuti',
        platform: 'WEB & MOBILE',
        desc: 'Manajemen jenis cuti, alokasi hari kuota, dan syarat dokumen lampiran',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'overtimes',
        title: 'Lembur',
        platform: 'WEB & MOBILE',
        desc: 'Pengajuan lembur kerja pribadi dan persetujuan pengajuan lembur tim',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: true, create: true, edit: false, delete: true },
    },

    // -------------------------------------------------------------------------
    // 4. KEUANGAN (FINANCE)
    // -------------------------------------------------------------------------
    {
        key: 'reimbursements',
        title: 'Klaim Biaya (Reimbursement)',
        platform: 'WEB & MOBILE',
        desc: 'Pengajuan klaim penggantian biaya operasional dan persetujuan pencairan dana',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: true, create: true, edit: false, delete: true },
    },
    {
        key: 'loans',
        title: 'Pinjaman Karyawan (Kasbon)',
        platform: 'WEB & MOBILE',
        desc: 'Pengajuan kasbon darurat dan persetujuan/verifikasi pinjaman karyawan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: true, create: true, edit: false, delete: true },
    },
    {
        key: 'payrolls',
        title: 'Penggajian (Payroll)',
        platform: 'WEB & MOBILE',
        desc: 'Manajemen kalkulasi penggajian bulanan/mingguan dan master komponen upah',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'taxes',
        title: 'Pajak & BPJS Perusahaan',
        platform: 'WEB',
        desc: 'Rekapitulasi tarif dan laporan iuran PPh 21, BPJS Ketenagakerjaan, dan BPJS Kesehatan',
        disabled: { view: false, create: true, edit: false, delete: true },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'thr',
        title: 'Tunjangan Hari Raya (THR)',
        platform: 'WEB',
        desc: 'Kalkulasi otomatis, penyesuaian masa kerja, dan pencairan THR keagamaan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },

    // -------------------------------------------------------------------------
    // 5. LAPORAN (REPORTS)
    // -------------------------------------------------------------------------
    {
        key: 'all_attendances',
        title: 'Laporan Kehadiran',
        platform: 'WEB & MOBILE',
        desc: 'Rekapitulasi kehadiran, keterlambatan, rekap lembur, dan tingkat kepatuhan kerja',
        disabled: { view: false, create: true, edit: false, delete: true },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'all_daily_reports',
        title: 'Laporan Kinerja Karyawan',
        platform: 'WEB & MOBILE',
        desc: 'Rekapitulasi performa cabang, produktivitas, dan review laporan harian tim',
        disabled: { view: false, create: true, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },

    // -------------------------------------------------------------------------
    // 6. SISTEM & PENGUMUMAN
    // -------------------------------------------------------------------------
    {
        key: 'announcements',
        title: 'Pengumuman Perusahaan',
        platform: 'WEB & MOBILE',
        desc: 'Membuat siaran berita, memo resmi, dan informasi internal perusahaan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'system_settings',
        title: 'Pengaturan Sistem',
        platform: 'WEB',
        desc: 'Konfigurasi aturan absensi, toleransi denda keterlambatan, limit kasbon, dan payroll',
        disabled: { view: false, create: true, edit: false, delete: true },
        defaults: { view: false, create: false, edit: false, delete: false },
    },

    // -------------------------------------------------------------------------
    // 7. FITUR KHUSUS MOBILE KARYAWAN
    // -------------------------------------------------------------------------
    {
        key: 'agenda',
        title: 'Agenda Ku',
        platform: 'MOBILE',
        desc: 'Catatan agenda kerja harian pribadi pemegang jabatan di aplikasi mobile',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: true, create: true, edit: true, delete: true },
    },
    {
        key: 'daily_reports',
        title: 'Laporan Harian Pribadi',
        platform: 'MOBILE',
        desc: 'Pengisian dan pelaporan aktivitas pekerjaan harian pribadi lewat mobile',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: true, create: true, edit: true, delete: true },
    },
    {
        key: 'payslips',
        title: 'Slip Gaji Karyawan',
        platform: 'MOBILE',
        desc: 'Akses dan unduh e-slip gaji bulanan/mingguan pribadi di mobile',
        disabled: { view: false, create: true, edit: true, delete: true },
        defaults: { view: true, create: false, edit: false, delete: false },
    },
    {
        key: 'feedbacks',
        title: 'Kritik & Saran',
        platform: 'MOBILE',
        desc: 'Penyampaian aspirasi, saran, dan masukan internal karyawan lewat mobile',
        disabled: { view: false, create: false, edit: true, delete: true },
        defaults: { view: true, create: true, edit: false, delete: false },
    },
];

/**
 * Membuat objek state permission baru dengan nilai default karyawan
 */
export const createDefaultPermissions = () => {
    const permissions = {};
    for (const item of POSITION_MENU_PERMISSIONS) {
        permissions[item.key] = {
            view: !item.disabled.view && Boolean(item.defaults.view),
            create: !item.disabled.create && Boolean(item.defaults.create),
            edit: !item.disabled.edit && Boolean(item.defaults.edit),
            delete: !item.disabled.delete && Boolean(item.defaults.delete),
        };
    }
    return permissions;
};

/**
 * Normalisasi permission yang disimpan dari backend atau props
 */
export const normalizePermissions = (savedPermissions = null) => {
    const defaults = createDefaultPermissions();
    if (!savedPermissions || typeof savedPermissions !== 'object') {
        return defaults;
    }

    const permissions = {};
    for (const item of POSITION_MENU_PERMISSIONS) {
        const saved = savedPermissions[item.key] || {};
        permissions[item.key] = {
            view: !item.disabled.view && Boolean(saved.view ?? item.defaults.view),
            create: !item.disabled.create && Boolean(saved.create ?? item.defaults.create),
            edit: !item.disabled.edit && Boolean(saved.edit ?? item.defaults.edit),
            delete: !item.disabled.delete && Boolean(saved.delete ?? item.defaults.delete),
        };
    }
    return permissions;
};
