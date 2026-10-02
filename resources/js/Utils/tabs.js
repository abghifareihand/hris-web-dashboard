export const getAttendanceReportTabs = () => [
    { name: 'Log Absensi', href: route('owner.reports.attendances.index'), active: route().current('owner.reports.attendances.index') || route().current('owner.reports.attendances.show') },
    { name: 'Rekap Kehadiran', href: route('owner.reports.attendances.recap.index'), active: route().current('owner.reports.attendances.recap.*') },
    { name: 'Tingkat Kehadiran (%)', href: route('owner.reports.attendances.rate.index'), active: route().current('owner.reports.attendances.rate.*') },
    { name: 'Rekap Lembur', href: route('owner.reports.attendances.overtime-recap.index'), active: route().current('owner.reports.attendances.overtime-recap.*') },
];

export const getPerformanceReportTabs = () => [
    { name: 'Laporan Kerja Harian', href: route('owner.reports.performances.daily.index'), active: route().current('owner.reports.performances.daily.*') },
    { name: 'Performa Karyawan', href: route('owner.reports.performances.employee.index'), active: route().current('owner.reports.performances.employee.*') },
    { name: 'Performa Cabang', href: route('owner.reports.performances.branch.index'), active: route().current('owner.reports.performances.branch.*') },
];

export const getScheduleTabs = () => [
    { name: 'Jadwal Kerja', href: route('employee.schedules.work.index'), active: route().current('employee.schedules.work.*') },
    { name: 'Tukar Jadwal Personal', href: route('employee.schedules.swap-personal.index'), active: route().current('employee.schedules.swap-personal.*') },
    { name: 'Tukar Jadwal Tim (Rekan)', href: route('employee.schedules.swap-team.index'), active: route().current('employee.schedules.swap-team.*') },
];
