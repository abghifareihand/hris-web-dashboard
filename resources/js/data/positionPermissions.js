/**
 * Konfigurasi Hak Akses Menu Jabatan (RBAC Matrix)
 * Dikelompokkan per modul kategori agar informatif dan jelas letak lokasinya di Web & Mobile.
 * Menggunakan penamaan dan deskripsi 100% persis sesuai template asli.
 */

export const POSITION_PERMISSION_GROUPS = [
    {
        category: 'Manajemen Perusahaan',
        location: {
            web: 'Sidebar Perusahaan',
            mobile: 'Tab Perusahaan',
        },
        menus: [
            {
                key: 'employee_data',
                title: 'Data Karyawan',
                desc: 'Hak akses untuk menu manajemen data karyawan',
                disabled: { view: false, create: false, edit: false, delete: false },
            },
            {
                key: 'company_profile',
                title: 'Profil Perusahaan',
                desc: 'Hak akses untuk melihat dan mengubah informasi profil perusahaan',
                disabled: { view: false, create: true, edit: false, delete: true },
            },
            {
                key: 'branches',
                title: 'Menu Cabang',
                desc: 'Hak akses untuk manajemen data cabang',
                disabled: { view: false, create: false, edit: false, delete: false },
            },
            {
                key: 'divisions',
                title: 'Menu Divisi',
                desc: 'Hak akses untuk manajemen data divisi',
                disabled: { view: false, create: false, edit: false, delete: false },
            },
            {
                key: 'positions',
                title: 'Menu Jabatan',
                desc: 'Hak akses untuk manajemen data jabatan',
                disabled: { view: false, create: false, edit: false, delete: false },
            },
        ],
    },
    {
        category: 'Penjadwalan & Waktu Kerja',
        location: {
            web: 'Sidebar Penjadwalan',
            mobile: 'Tab Perusahaan & Grid Home',
        },
        menus: [
            {
                key: 'shifts',
                title: 'Menu Shift',
                desc: 'Hak akses untuk manajemen data shift kerja',
                disabled: { view: false, create: false, edit: false, delete: false },
            },
            {
                key: 'employee_schedules',
                title: 'Menu Jadwal Karyawan',
                desc: 'Hak akses untuk manajemen jadwal semua karyawan',
                disabled: { view: false, create: false, edit: false, delete: false },
            },
            {
                key: 'holidays',
                title: 'Menu Hari Libur',
                desc: 'Hak akses untuk manajemen hari libur perusahaan',
                disabled: { view: false, create: false, edit: false, delete: false },
            },
            {
                key: 'swap_personal',
                title: 'Menu Tukar Jadwal Pribadi',
                desc: 'Hak akses untuk manajemen tukar jadwal pribadi karyawan',
                disabled: { view: false, create: false, edit: true, delete: false },
            },
            {
                key: 'swap_team',
                title: 'Menu Tukar Jadwal antar Tim',
                desc: 'Hak akses untuk manajemen tukar jadwal antar Tim (Edit: Persetujuan/Approval)',
                disabled: { view: false, create: false, edit: false, delete: false },
            },
        ],
    },
    {
        category: 'Cuti & Lembur',
        location: {
            web: 'Sidebar Cuti & Lembur',
            mobile: 'Grid Home & Tab Approval Tim',
        },
        menus: [
            {
                key: 'leaves',
                title: 'Menu Cuti',
                desc: 'Hak akses untuk manajemen cuti karyawan (Create: Ajukan, Edit: Persetujuan/Approval Tim)',
                disabled: { view: false, create: false, edit: false, delete: false },
            },
            {
                key: 'leave_balances',
                title: 'Sisa Cuti Semua Karyawan',
                desc: 'Hak akses untuk melihat sisa kuota cuti semua karyawan',
                disabled: { view: false, create: true, edit: false, delete: true },
            },
            {
                key: 'leave_categories',
                title: 'Menu Kategori Cuti',
                desc: 'Hak akses untuk manajemen data kategori cuti karyawan',
                disabled: { view: false, create: false, edit: false, delete: false },
            },
            {
                key: 'overtimes',
                title: 'Menu Lembur',
                desc: 'Hak akses untuk manajemen lembur karyawan (Create: Ajukan, Edit: Persetujuan/Approval Tim)',
                disabled: { view: false, create: false, edit: false, delete: false },
            },
        ],
    },
    {
        category: 'Keuangan & Penggajian',
        location: {
            web: 'Sidebar Keuangan',
            mobile: 'Grid Home & Tab Approval Tim',
        },
        menus: [
            {
                key: 'reimbursements',
                title: 'Menu Klaim Biaya',
                desc: 'Hak akses untuk manajemen klaim biaya (Create: Ajukan, Edit: Persetujuan/Approval Tim)',
                disabled: { view: false, create: false, edit: false, delete: false },
            },
            {
                key: 'loans',
                title: 'Menu Pinjaman Karyawan',
                desc: 'Hak akses untuk manajemen pinjaman karyawan (Create: Ajukan, Edit: Persetujuan/Approval Tim)',
                disabled: { view: false, create: false, edit: false, delete: false },
            },
            {
                key: 'payrolls',
                title: 'Menu Penggajian',
                desc: 'Hak akses untuk manajemen penggajian karyawan',
                disabled: { view: false, create: false, edit: false, delete: false },
            },
        ],
    },
    {
        category: 'Laporan & Monitoring',
        location: {
            web: 'Sidebar Laporan',
            mobile: 'Grid Home',
        },
        menus: [
            {
                key: 'all_attendances',
                title: 'Laporan Kehadiran Semua Karyawan',
                desc: 'Hak akses untuk mengelola laporan kehadiran semua karyawan berdasarkan cabang & divisi yang dapat di akses',
                disabled: { view: false, create: true, edit: true, delete: true },
            },
            {
                key: 'all_daily_reports',
                title: 'Laporan Harian Semua Karyawan',
                desc: 'Hak akses untuk mengelola laporan harian semua karyawan berdasarkan cabang & divisi yang dapat di akses (Edit: Review Laporan)',
                disabled: { view: false, create: true, edit: false, delete: false },
            },
        ],
    },
    {
        category: 'Sistem & Pengaturan',
        location: {
            web: 'Sidebar Sistem',
            mobile: 'Tab Perusahaan',
        },
        menus: [
            {
                key: 'announcements',
                title: 'Menu Pengumuman',
                desc: 'Hak akses untuk manajemen pengumuman karyawan',
                disabled: { view: false, create: false, edit: false, delete: false },
            },
            {
                key: 'system_settings',
                title: 'Pengaturan Sistem',
                desc: 'Hak akses konfigurasi kebijakan absensi, denda, pinjaman, dan penggajian',
                disabled: { view: false, create: true, edit: false, delete: true },
            },
        ],
    },
];

// Flat list dari seluruh menu untuk kebutuhan looping dan normalisasi
export const POSITION_MENU_PERMISSIONS = POSITION_PERMISSION_GROUPS.flatMap(
    (group) => group.menus
);

/**
 * Membuat objek state permission baru dengan nilai default (semua false)
 */
export const createDefaultPermissions = () => {
    const permissions = {};
    for (const item of POSITION_MENU_PERMISSIONS) {
        permissions[item.key] = {
            view: false,
            create: false,
            edit: false,
            delete: false,
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
            view: !item.disabled.view && Boolean(saved.view),
            create: !item.disabled.create && Boolean(saved.create),
            edit: !item.disabled.edit && Boolean(saved.edit),
            delete: !item.disabled.delete && Boolean(saved.delete),
        };
    }
    return permissions;
};
