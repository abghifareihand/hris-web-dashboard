/**
 * Konfigurasi Hak Akses Menu Jabatan (RBAC Matrix)
 * Menentukan daftar menu, aksi yang diizinkan (disabled jika tidak relevan),
 * dan nilai default saat membuat jabatan baru.
 */

export const POSITION_MENU_PERMISSIONS = [
    {
        key: 'employee_data',
        title: 'Data Karyawan',
        desc: 'Hak akses untuk menu manajemen data karyawan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'employee_detail',
        title: 'Detail Informasi Karyawan',
        desc: 'Hak akses untuk melihat halaman informasi data karyawan',
        disabled: { view: false, create: true, edit: true, delete: true },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'salary_info',
        title: 'Informasi Gaji',
        desc: 'Hak akses untuk melihat dan mengubah informasi penggajian karyawan.',
        disabled: { view: false, create: true, edit: false, delete: true },
        defaults: { view: true, create: false, edit: false, delete: false },
    },
    {
        key: 'tax_bpjs_info',
        title: 'Informasi Pajak & BPJS',
        desc: 'Hak akses untuk melihat dan mengubah informasi Pajak & kepesertaan BPJS karyawan.',
        disabled: { view: false, create: true, edit: false, delete: true },
        defaults: { view: true, create: false, edit: false, delete: false },
    },
    {
        key: 'bank_account_info',
        title: 'Informasi Rekening Bank',
        desc: 'Hak akses untuk melihat dan mengubah informasi rekening bank karyawan.',
        disabled: { view: false, create: true, edit: false, delete: true },
        defaults: { view: true, create: false, edit: false, delete: false },
    },
    {
        key: 'branches',
        title: 'Menu Cabang',
        desc: 'Hak akses untuk manajemen data cabang',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'divisions',
        title: 'Menu Divisi',
        desc: 'Hak akses untuk manajemen data divisi',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'positions',
        title: 'Menu Jabatan',
        desc: 'Hak akses untuk manajemen data jabatan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'shifts',
        title: 'Menu Shift',
        desc: 'Hak akses untuk manajemen data shift kerja',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'employee_schedules',
        title: 'Menu Jadwal Karyawan',
        desc: 'Hak akses untuk manajemen jadwal semua karyawan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'swap_personal',
        title: 'Menu Tukar Jadwal Pribadi',
        desc: 'Hak akses untuk manajemen tukar jadwal pribadi karyawan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: true, create: true, edit: false, delete: true },
    },
    {
        key: 'swap_team',
        title: 'Menu Tukar Jadwal antar Tim',
        desc: 'Hak akses untuk manajemen tukar jadwal antar Tim',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: true, create: true, edit: false, delete: true },
    },
    {
        key: 'holidays',
        title: 'Menu Hari Libur',
        desc: 'Hak akses untuk manajemen hari libur perusahaan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'leaves',
        title: 'Menu Cuti',
        desc: 'Hak akses untuk manajemen cuti karyawan, view (tampil menu), create (pengajuan), edit (persetujuan), delete (batalkan pengajuan)',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: true, create: true, edit: false, delete: true },
    },
    {
        key: 'leave_balances',
        title: 'Sisa Cuti Semua Karyawan',
        desc: 'Hak akses untuk melihat sisa kuota cuti semua karyawan',
        disabled: { view: false, create: true, edit: false, delete: true },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'leave_categories',
        title: 'Menu Kategori Cuti',
        desc: 'Hak akses untuk manajemen data kategori cuti karyawan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'agenda',
        title: 'Menu Agenda Ku',
        desc: 'Hak akses untuk menu catatan agenda masing-masing karyawan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: true, create: true, edit: true, delete: true },
    },
    {
        key: 'overtimes',
        title: 'Menu Lembur',
        desc: 'Hak akses untuk manajemen lembur karyawan, view (tampil menu), create (pengajuan), edit (persetujuan), delete (batalkan)',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: true, create: true, edit: false, delete: true },
    },
    {
        key: 'reimbursements',
        title: 'Menu Klaim Biaya',
        desc: 'Hak akses untuk manajemen klaim biaya, view (tampil menu), create (pengajuan), edit (persetujuan), delete (batalkan)',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: true, create: true, edit: false, delete: true },
    },
    {
        key: 'loans',
        title: 'Menu Pinjaman Karyawan',
        desc: 'Hak akses untuk manajemen pinjaman karyawan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: true, create: true, edit: false, delete: true },
    },
    {
        key: 'loan_settings',
        title: 'Pengaturan Pinjaman',
        desc: 'Hak akses untuk pengaturan pinjaman karyawan',
        disabled: { view: true, create: true, edit: false, delete: true },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'payrolls',
        title: 'Menu Penggajian',
        desc: 'Hak akses untuk manajemen penggajian karyawan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'payslips',
        title: 'Menu Slip Gaji',
        desc: 'Hak akses untuk menu slip gaji karyawan',
        disabled: { view: false, create: true, edit: true, delete: true },
        defaults: { view: true, create: false, edit: false, delete: false },
    },
    {
        key: 'announcements',
        title: 'Menu Pengumuman',
        desc: 'Hak akses untuk manajemen pengumuman karyawan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'feedbacks',
        title: 'Menu Kritik & Saran',
        desc: 'Hak akses untuk menu kritik & saran',
        disabled: { view: false, create: false, edit: true, delete: true },
        defaults: { view: true, create: true, edit: false, delete: false },
    },
    {
        key: 'daily_reports',
        title: 'Laporan Harian',
        desc: 'Hak akses untuk menu laporan harian karyawan',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: true, create: true, edit: true, delete: true },
    },
    {
        key: 'all_daily_reports',
        title: 'Laporan Harian Semua Karyawan',
        desc: 'Hak akses untuk mengelola laporan harian semua karyawan berdasarkan cabang & divisi yang dapat diakses',
        disabled: { view: false, create: true, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'all_attendances',
        title: 'Laporan Kehadiran Semua Karyawan',
        desc: 'Hak akses untuk mengelola laporan kehadiran semua karyawan berdasarkan cabang & divisi yang dapat diakses',
        disabled: { view: false, create: false, edit: false, delete: false },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'export_reports',
        title: 'Download & Share Laporan',
        desc: 'Hak akses untuk download atau share excel pada setiap laporan',
        disabled: { view: false, create: true, edit: true, delete: true },
        defaults: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'subscription_payment',
        title: 'Menu Pembayaran Aplikasi',
        desc: 'Hak akses untuk menu pembayaran langganan aplikasi',
        disabled: { view: false, create: true, edit: true, delete: true },
        defaults: { view: false, create: false, edit: false, delete: false },
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
