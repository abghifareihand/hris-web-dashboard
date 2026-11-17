/**
 * Konfigurasi Hak Akses Menu Jabatan (RBAC Matrix)
 * Dipisahkan secara tegas antara:
 * 1. 📱 MOBILE APP KARYAWAN (25 Menu):
 *    - Sub-grup 1: Menu Home Karyawan (12 Menu ESS Diri Sendiri)
 *    - Sub-grup 2: Persetujuan & Kelola Karyawan (8 Modul di Home)
 *    - Sub-grup 3: Tab Perusahaan (12 Menu Manajerial company_*)
 * 2. 💻 WEB DASHBOARD KARYAWAN (21 Menu Manajerial Bersih tanpa prefix company_*):
 *    - 12 Menu Master Perusahaan
 *    - 9 Modul Pengawasan Staf & Persetujuan
 * 
 * Struktur penyimpanan backend:
 * {
 *    "mobile": { "schedules": { view, create, edit, delete }, ... },
 *    "web": { "company_profile": { ... }, "employees": { ... }, ... }
 * }
 */

export const CANONICAL_MOBILE_KEYS = [
    // 1. FITUR HOME KARYAWAN (13 Menu Home)
    'schedules',
    'swap_schedules',
    'agendas',
    'attendances',
    'daily_reports',
    'leaves',
    'leave_balances',
    'overtimes',
    'reimbursements',
    'loans',
    'payslips',
    'feedbacks',
    'announcements',

    // 2. FITUR TAB PERUSAHAAN (12 Menu Kantor)
    'company_profile',
    'company_employees',
    'company_branches',
    'company_divisions',
    'company_positions',
    'company_shifts',
    'company_schedules',
    'company_leave_categories',
    'company_holidays',
    'company_payrolls',
    'company_announcements',
    'company_settings',
];

export const CANONICAL_WEB_KEYS = [
    'company_profile',
    'employees',
    'branches',
    'divisions',
    'positions',
    'shifts',
    'schedules',
    'leaves',
    'leave_balances',
    'leave_categories',
    'holidays',
    'payrolls',
    'announcements',
    'settings',
    'swap_schedules',
    'overtimes',
    'reimbursements',
    'loans',
    'attendances',
    'daily_reports',
    'feedbacks',
];

// Alias kanonikal untuk kompatibilitas
export const CANONICAL_KEYS = CANONICAL_MOBILE_KEYS;

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
        desc: 'Menampilkan menu icon "Klaim Biaya" di Grid Home Mobile dan pengajuan reimbursement baru.',
        action: 'view',
        hasCreate: true,
    },
    {
        key: 'loans',
        title: 'Menu Pinjaman (Kasbon)',
        desc: 'Menampilkan menu icon "Pinjaman" di Grid Home Mobile dan pengajuan pinjaman uang kasbon pribadi.',
        action: 'view',
        hasCreate: true,
    },
    {
        key: 'payslips',
        title: 'Menu Slip Gaji',
        desc: 'Menampilkan menu icon "Slip Gaji" di Grid Home Mobile untuk mengunduh rincian slip gaji pribadi.',
        action: 'view',
        hasCreate: false,
    },
    {
        key: 'feedbacks',
        title: 'Menu Kritik & Saran',
        desc: 'Menampilkan menu icon "Kritik & Saran" di Grid Home Mobile dan mengirimkan feedback / aspirasi.',
        action: 'view',
        hasCreate: true,
    },
    {
        key: 'announcements',
        title: 'Menu Pengumuman',
        desc: 'Menampilkan menu icon "Pengumuman" di Grid Home Mobile dan membaca berita resmi kantor.',
        action: 'view',
        hasCreate: false,
    },
];

// Sub-grup 2: Menu Home - Persetujuan & Kelola Karyawan (8 Modul di Home)
export const MOBILE_APPROVAL_MENUS = [
    {
        key: 'swap_schedules',
        title: 'Persetujuan Tukar Jadwal Karyawan',
        desc: 'Menyetujui atau menolak pertukaran shift kerja, serta menghapus pengajuan.',
    },
    {
        key: 'leaves',
        title: 'Persetujuan Cuti Karyawan',
        desc: 'Menyetujui atau menolak permohonan cuti, serta menghapus pengajuan cuti.',
    },
    {
        key: 'overtimes',
        title: 'Persetujuan Lembur Karyawan',
        desc: 'Menyetujui atau menolak permohonan lembur kerja, serta menghapus pengajuan lembur.',
    },
    {
        key: 'reimbursements',
        title: 'Persetujuan Klaim Biaya Karyawan',
        desc: 'Memverifikasi dan menyetujui klaim biaya operasional, serta menghapus pengajuan klaim.',
    },
    {
        key: 'loans',
        title: 'Persetujuan Pinjaman Karyawan',
        desc: 'Memverifikasi dan menyetujui pengajuan pinjaman kasbon, serta menghapus pengajuan pinjaman.',
    },
    {
        key: 'attendances',
        title: 'Kelola Kehadiran Karyawan',
        desc: 'Melihat rekapan kehadiran staf, mengubah (koreksi) jam presensi, serta menghapus data absensi karyawan.',
    },
    {
        key: 'daily_reports',
        title: 'Kelola Laporan Harian Karyawan',
        desc: 'Melihat riwayat progres kerja harian staf, mengoreksi isi laporan, serta menghapus laporan kerja karyawan.',
    },
    {
        key: 'leave_balances',
        title: 'Kelola Sisa Cuti Karyawan',
        desc: 'Melihat daftar serta memperbarui (edit) saldo kuota cuti tahunan maupun cuti khusus seluruh karyawan.',
        disabled: { delete: true },
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
        key: 'company_payrolls',
        title: 'Penggajian',
        desc: 'Kelola dan proses penggajian payroll di Tab Perusahaan Mobile.',
        disabled: { view: false, create: false, edit: true, delete: false },
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
        key: 'employees',
        title: 'Data Karyawan',
        desc: 'Direktori staf dan pengelolaan data karyawan.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'branches',
        title: 'Cabang',
        desc: 'Daftar kantor cabang dan titik radius GPS presensi.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'divisions',
        title: 'Divisi',
        desc: 'Daftar departemen dan divisi kerja.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'positions',
        title: 'Jabatan',
        desc: 'Daftar struktur jabatan kerja.',
        disabled: { view: false, create: true, edit: true, delete: true },
    },
    {
        key: 'shifts',
        title: 'Shift Kerja',
        desc: 'Master jam masuk dan pulang kerja shift.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'schedules',
        title: 'Jadwal Kerja',
        desc: 'Plotting jadwal dan penugasan shift kerja karyawan.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'leaves',
        title: 'Cuti',
        desc: 'Daftar permohonan dan persetujuan cuti karyawan di Web.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'leave_balances',
        title: 'Sisa Cuti',
        desc: 'Rekapitulasi dan penyesuaian kuota saldo cuti tahunan staf.',
        disabled: { view: false, create: true, edit: false, delete: true },
    },
    {
        key: 'leave_categories',
        title: 'Kategori Cuti',
        desc: 'Master jenis cuti tahunan dan cuti khusus.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'holidays',
        title: 'Hari Libur',
        desc: 'Kalender libur nasional dan cuti bersama.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'payrolls',
        title: 'Penggajian',
        desc: 'Hitung dan kelola rekapitulasi payroll gaji karyawan.',
        disabled: { view: false, create: false, edit: true, delete: false },
    },
    {
        key: 'announcements',
        title: 'Pengumuman',
        desc: 'Kelola dan publikasikan pengumuman resmi kantor.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'settings',
        title: 'Pengaturan',
        desc: 'Aturan toleransi keterlambatan, denda, dan kasbon.',
        disabled: { view: false, create: true, edit: false, delete: true },
    },

    // Modul Pengawasan Staf & Persetujuan di Web
    {
        key: 'swap_schedules',
        title: 'Tukar Jadwal Karyawan',
        desc: 'Persetujuan dan pengelolaan tukar jadwal karyawan di Web.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'overtimes',
        title: 'Lembur',
        desc: 'Persetujuan dan verifikasi lembur kerja staf di Web.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'reimbursements',
        title: 'Klaim Biaya',
        desc: 'Verifikasi dan persetujuan klaim biaya / reimbursement staf di Web.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'loans',
        title: 'Pinjaman (Kasbon)',
        desc: 'Verifikasi dan persetujuan pinjaman / kasbon staf di Web.',
        disabled: { view: false, create: false, edit: false, delete: false },
    },
    {
        key: 'attendances',
        title: 'Kehadiran Staf',
        desc: 'Monitoring rekapan kehadiran staf dan riwayat jam kerja di Web.',
        disabled: { view: false, create: false, edit: false, delete: false },
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

/**
 * Membuat objek state permission baru dengan nilai default terpisah untuk mobile dan web
 */
export const createDefaultPermissions = () => {
    const mobile = {};
    for (const key of CANONICAL_MOBILE_KEYS) {
        mobile[key] = {
            view: false,
            create: false,
            edit: false,
            delete: false,
        };
    }

    const web = {};
    for (const key of CANONICAL_WEB_KEYS) {
        web[key] = {
            view: false,
            create: false,
            edit: false,
            delete: false,
        };
    }

    return { mobile, web };
};

/**
 * Normalisasi permission yang disimpan dari backend atau props menjadi format { mobile: {...}, web: {...} }
 */
export const normalizePermissions = (savedPermissions = null) => {
    const defaults = createDefaultPermissions();
    if (!savedPermissions || typeof savedPermissions !== 'object') {
        return defaults;
    }

    // Cek apakah data sudah terpisah mobile dan web
    const hasPlatform = Boolean(savedPermissions.mobile || savedPermissions.web);

    const rawMobile = hasPlatform ? (savedPermissions.mobile || {}) : savedPermissions;
    const rawWeb = hasPlatform ? (savedPermissions.web || {}) : savedPermissions;

    const mobile = {};
    for (const key of CANONICAL_MOBILE_KEYS) {
        const saved = rawMobile[key] || {};
        mobile[key] = {
            view: Boolean(saved.view),
            create: Boolean(saved.create),
            edit: Boolean(saved.edit),
            delete: Boolean(saved.delete),
        };
    }

    const web = {};
    for (const key of CANONICAL_WEB_KEYS) {
        // Cek clean key langsung atau fallback ke company_
        const saved = rawWeb[key] || rawWeb[`company_${key}`] || {};
        web[key] = {
            view: Boolean(saved.view),
            create: Boolean(saved.create),
            edit: Boolean(saved.edit),
            delete: Boolean(saved.delete),
        };
    }

    // Aturan keamanan & batasan Mobile
    if (mobile.company_positions) {
        mobile.company_positions.create = false;
        mobile.company_positions.edit = false;
        mobile.company_positions.delete = false;
    }
    if (mobile.company_profile) {
        mobile.company_profile.create = false;
        mobile.company_profile.delete = false;
    }
    if (mobile.company_settings) {
        mobile.company_settings.create = false;
        mobile.company_settings.delete = false;
    }
    if (mobile.company_payrolls) {
        mobile.company_payrolls.edit = false;
    }
    if (mobile.leave_balances) {
        mobile.leave_balances.delete = false;
    }

    // Aturan keamanan & batasan Web
    if (web.positions) {
        web.positions.create = false;
        web.positions.edit = false;
        web.positions.delete = false;
    }
    if (web.company_profile) {
        web.company_profile.create = false;
        web.company_profile.delete = false;
    }
    if (web.settings) {
        web.settings.create = false;
        web.settings.delete = false;
    }
    if (web.payrolls) {
        web.payrolls.edit = false;
    }
    if (web.leave_balances) {
        web.leave_balances.create = false;
        web.leave_balances.delete = false;
    }

    return { mobile, web };
};
