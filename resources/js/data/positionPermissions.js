/**
 * Konfigurasi Hak Akses Menu Jabatan (RBAC Matrix)
 * Dipisahkan secara tegas antara:
 * 1. 📱 MOBILE APP KARYAWAN:
 *    - Sub-grup 1: Menu Home Karyawan (12 Menu ESS Diri Sendiri)
 *    - Sub-grup 2: Persetujuan Tim (6 Modul Approval)
 *    - Sub-grup 3: Tab Perusahaan (12 Menu Manajerial company_*)
 * 2. 💻 WEB DASHBOARD KARYAWAN (21 Menu Manajerial Kantor & Pengawasan Staf)
 * 
 * Menggunakan arsitektur Flat Permissions (25 Canonical Keys).
 */

export const CANONICAL_KEYS = [
    // 1. FITUR HOME KARYAWAN (12 ESS)
    'schedules',
    'agendas',
    'swap_schedules',
    'attendances',
    'leaves',
    'daily_reports',
    'overtimes',
    'reimbursements',
    'loans',
    'payslips',
    'feedbacks',
    'announcements',

    // 2. FITUR MANAJERIAL PERUSAHAAN (12 COMPANY)
    'company_profile',
    'company_employees',
    'company_branches',
    'company_divisions',
    'company_positions',
    'company_shifts',
    'company_schedules',
    'company_leave_categories',
    'company_holidays',
    'company_announcements',
    'company_settings',
    'company_payrolls',
];

// =========================================================================
// 📱 TAB 1: MOBILE APP KARYAWAN
// =========================================================================

// Sub-grup 1: Menu Home - Fitur Diri Sendiri (ESS - 12 Menu)
export const MOBILE_HOME_MENUS = [
    {
        key: 'schedules',
        title: 'Menu Jadwal',
        desc: 'Menampilkan menu icon "Jadwal" di Grid Home Mobile untuk melihat kalender jadwal shift kerja pribadi.',
        action: 'view',
        hasCreate: false,
    },
    {
        key: 'agendas',
        title: 'Menu Agenda',
        desc: 'Menampilkan menu icon "Agenda" di Grid Home Mobile untuk melihat dan mencatat agenda kegiatan pribadi.',
        action: 'view',
        hasCreate: true,
    },
    {
        key: 'swap_schedules',
        title: 'Menu Tukar Jadwal',
        desc: 'Menampilkan menu icon "Tukar Jadwal" di Grid Home Mobile dan izin mengajukan penukaran shift kerja.',
        action: 'view',
        hasCreate: true,
    },
    {
        key: 'attendances',
        title: 'Menu Kehadiran',
        desc: 'Menampilkan menu icon "Kehadiran" di Grid Home Mobile untuk melihat riwayat absensi dan rekap jam kerja pribadi.',
        action: 'view',
        hasCreate: false,
    },
    {
        key: 'leaves',
        title: 'Menu Cuti',
        desc: 'Menampilkan menu icon "Cuti", sisa kuota cuti, dan pengajuan cuti baru.',
        action: 'view',
        hasCreate: true,
    },
    {
        key: 'daily_reports',
        title: 'Menu Laporan Harian',
        desc: 'Menampilkan menu icon "Laporan Harian" di Grid Home Mobile dan pengisian laporan kerja harian.',
        action: 'view',
        hasCreate: true,
    },
    {
        key: 'overtimes',
        title: 'Menu Lembur',
        desc: 'Menampilkan menu icon "Lembur" di Grid Home Mobile dan pengajuan lembur kerja pribadi.',
        action: 'view',
        hasCreate: true,
    },
    {
        key: 'reimbursements',
        title: 'Menu Klaim Biaya',
        desc: 'Menampilkan menu icon "Klaim Biaya" di Grid Home Mobile dan pengajuan klaim biaya operasional.',
        action: 'view',
        hasCreate: true,
    },
    {
        key: 'loans',
        title: 'Menu Pinjaman',
        desc: 'Menampilkan menu icon "Pinjaman" di Grid Home Mobile dan pengajuan pinjaman kasbon pribadi.',
        action: 'view',
        hasCreate: true,
    },
    {
        key: 'payslips',
        title: 'Menu Slip Gaji',
        desc: 'Menampilkan menu icon "Slip Gaji" di Grid Home Mobile untuk melihat rincian gaji dan mengunduh slip gaji pribadi.',
        action: 'view',
        hasCreate: false,
    },
    {
        key: 'feedbacks',
        title: 'Menu Kritik & Saran',
        desc: 'Menampilkan menu icon "Kritik & Saran" di Grid Home Mobile dan pengiriman masukan ke manajemen.',
        action: 'view',
        hasCreate: true,
    },
    {
        key: 'announcements',
        title: 'Menu Pengumuman',
        desc: 'Menampilkan banner siaran informasi & pengumuman kantor di halaman utama Mobile Home.',
        action: 'view',
        hasCreate: false,
    },
];

// Sub-grup 2: Menu Home - Persetujuan Pengajuan (Approval - 6 Modul)
export const MOBILE_APPROVAL_MENUS = [
    {
        key: 'swap_schedules',
        title: 'Persetujuan Tukar Jadwal',
        desc: 'Menyetujui atau menolak pertukaran shift kerja, serta menghapus pengajuan.',
    },
    {
        key: 'leaves',
        title: 'Persetujuan Cuti',
        desc: 'Menyetujui atau menolak permohonan cuti, serta menghapus pengajuan cuti.',
    },
    {
        key: 'overtimes',
        title: 'Persetujuan Lembur',
        desc: 'Menyetujui atau menolak permohonan lembur kerja, serta menghapus pengajuan lembur.',
    },
    {
        key: 'reimbursements',
        title: 'Persetujuan Klaim Biaya',
        desc: 'Memverifikasi dan menyetujui klaim biaya operasional, serta menghapus pengajuan klaim.',
    },
    {
        key: 'loans',
        title: 'Persetujuan Pinjaman',
        desc: 'Memverifikasi dan menyetujui pengajuan pinjaman kasbon, serta menghapus pengajuan pinjaman.',
    },
    {
        key: 'daily_reports',
        title: 'Review Laporan Harian',
        desc: 'Memeriksa dan memvalidasi laporan harian kerja, serta menghapus laporan.',
    },
];

// Sub-grup 3: Tab Perusahaan (12 Menu Kantor di Mobile)
export const MOBILE_COMPANY_MENUS = [
    {
        key: 'company_profile',
        title: 'Data Perusahaan',
        desc: 'Informasi profil dan legalitas perusahaan di Tab Perusahaan Mobile.',
        disabled: { view: false, create: true, edit: false, delete: true },
    },
    {
        key: 'company_employees',
        title: 'Data Karyawan',
        desc: 'Direktori dan manajemen staf di Tab Perusahaan Mobile.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_branches',
        title: 'Cabang',
        desc: 'Daftar kantor cabang dan titik radius GPS presensi di Tab Perusahaan Mobile.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_divisions',
        title: 'Divisi',
        desc: 'Daftar departemen dan divisi kerja di Tab Perusahaan Mobile.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_positions',
        title: 'Jabatan',
        desc: 'Daftar struktur jabatan kerja di Tab Perusahaan Mobile.',
        disabled: { view: false, create: true, edit: true, delete: true },
    },
    {
        key: 'company_shifts',
        title: 'Shift Kerja',
        desc: 'Master jam masuk dan pulang kerja shift di Tab Perusahaan Mobile.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_schedules',
        title: 'Jadwal Kerja',
        desc: 'Plotting jadwal dan penugasan shift kerja karyawan di Tab Perusahaan Mobile.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_leave_categories',
        title: 'Kategori Cuti',
        desc: 'Master jenis cuti tahunan dan cuti khusus di Tab Perusahaan Mobile.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_holidays',
        title: 'Hari Libur',
        desc: 'Kalender libur nasional dan cuti bersama di Tab Perusahaan Mobile.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_announcements',
        title: 'Pengumuman',
        desc: 'Kelola siaran pengumuman kantor di Tab Perusahaan Mobile.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_settings',
        title: 'Pengaturan',
        desc: 'Aturan toleransi keterlambatan, denda, dan kasbon di Tab Perusahaan Mobile.',
        disabled: { view: false, create: true, edit: false, delete: true },
    },
    {
        key: 'company_payrolls',
        title: 'Penggajian',
        desc: 'Kelola dan proses penggajian payroll di Tab Perusahaan Mobile.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
];

// =========================================================================
// 💻 TAB 2: WEB DASHBOARD KARYAWAN (Khusus Manajerial Kantor & Pengawasan Staf)
// =========================================================================

export const WEB_MANAGERIAL_MENUS = [
    // 12 Menu Perusahaan
    {
        key: 'company_profile',
        title: 'Data Perusahaan',
        desc: 'Informasi profil dan legalitas perusahaan.',
        disabled: { view: false, create: true, edit: false, delete: true },
    },
    {
        key: 'company_employees',
        title: 'Data Karyawan',
        desc: 'Direktori staf dan pengelolaan data karyawan.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_branches',
        title: 'Cabang',
        desc: 'Daftar kantor cabang dan titik radius GPS presensi.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_divisions',
        title: 'Divisi',
        desc: 'Daftar departemen dan divisi kerja.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_positions',
        title: 'Jabatan',
        desc: 'Daftar struktur jabatan kerja.',
        disabled: { view: false, create: true, edit: true, delete: true },
    },
    {
        key: 'company_shifts',
        title: 'Shift Kerja',
        desc: 'Master jam masuk dan pulang kerja shift.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_schedules',
        title: 'Jadwal Kerja',
        desc: 'Plotting jadwal dan penugasan shift kerja karyawan.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_leave_categories',
        title: 'Kategori Cuti',
        desc: 'Master jenis cuti tahunan dan cuti khusus.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_holidays',
        title: 'Hari Libur',
        desc: 'Kalender libur nasional dan cuti bersama.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_payrolls',
        title: 'Penggajian',
        desc: 'Hitung dan kelola rekapitulasi payroll gaji karyawan.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_announcements',
        title: 'Pengumuman',
        desc: 'Kelola dan publikasikan pengumuman resmi kantor.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'company_settings',
        title: 'Pengaturan',
        desc: 'Aturan toleransi keterlambatan, denda, dan kasbon.',
        disabled: { view: false, create: true, edit: false, delete: true },
    },

    // 9 Modul Pengawasan Staf & Persetujuan di Web
    {
        key: 'swap_schedules',
        title: 'Tukar Jadwal Tim',
        desc: 'Persetujuan dan pengelolaan tukar jadwal tim di Web.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'leaves',
        title: 'Cuti',
        desc: 'Daftar permohonan cuti, sisa cuti, dan persetujuan cuti tim.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'overtimes',
        title: 'Lembur',
        desc: 'Daftar permohonan lembur dan persetujuan lembur tim.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'reimbursements',
        title: 'Klaim Biaya',
        desc: 'Daftar pengajuan klaim nota dan persetujuan pencairan.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'loans',
        title: 'Pinjaman',
        desc: 'Daftar pengajuan pinjaman kasbon dan persetujuannya.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'attendances',
        title: 'Laporan Kehadiran',
        desc: 'Rekapitulasi data absensi dan jam kerja seluruh karyawan.',
        disabled: { view: false, create: true, edit: true, delete: true },
    },
    {
        key: 'daily_reports',
        title: 'Laporan Harian',
        desc: 'Monitoring dan review laporan kerja harian staf.',
        disabled: { view: false, create: true, edit: false, delete: false },
    },
    {
        key: 'feedbacks',
        title: 'Kritik & Saran',
        desc: 'Kotak masuk aspirasi dan masukan dari karyawan.',
        disabled: { view: false, create: true, edit: true, delete: false },
    },
];

// Alias untuk kompatibilitas jika masih ada komponen yang merujuk WEB_MANAGERIAL_GROUPS
export const WEB_MANAGERIAL_GROUPS = [
    {
        category: 'Menu Manajerial Web',
        desc: 'Daftar hak akses manajerial kantor.',
        menus: WEB_MANAGERIAL_MENUS,
    },
];

// Flat list dari seluruh menu untuk kebutuhan normalisasi
export const POSITION_MENU_PERMISSIONS = CANONICAL_KEYS.map((key) => ({ key }));

/**
 * Membuat objek state permission baru dengan nilai default (semua false)
 */
export const createDefaultPermissions = () => {
    const permissions = {};
    for (const key of CANONICAL_KEYS) {
        permissions[key] = {
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
    for (const key of CANONICAL_KEYS) {
        const saved = savedPermissions[key] || {};
        permissions[key] = {
            view: Boolean(saved.view),
            create: Boolean(saved.create),
            edit: Boolean(saved.edit),
            delete: Boolean(saved.delete),
        };
    }

    // Aturan keamanan bawaan: create/edit/delete positions selalu false untuk non-owner
    if (permissions.company_positions) {
        permissions.company_positions.create = false;
        permissions.company_positions.edit = false;
        permissions.company_positions.delete = false;
    }
    if (permissions.positions) {
        permissions.positions.create = false;
        permissions.positions.edit = false;
        permissions.positions.delete = false;
    }

    return permissions;
};
