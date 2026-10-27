<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/check-email', [AuthController::class, 'checkEmail']);
    Route::post('/register-owner-account', [AuthController::class, 'registerOwnerAccount']);
    Route::post('/register/owner', [AuthController::class, 'registerOwner']);
    Route::post('/register/employee', [AuthController::class, 'registerEmployee']);
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile/owner', [AuthController::class, 'profileOwner']);
        Route::get('/profile/employee', [AuthController::class, 'profileEmployee']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/fcm-token', [AuthController::class, 'updateFcmToken']);
    });
});

Route::middleware('auth:sanctum')->post('/fcm-token', [AuthController::class, 'updateFcmToken']);

// API Company Public Routes (Without Auth)
Route::get('/companies/search', [\App\Http\Controllers\Api\CompanyController::class, 'searchCompany']);
Route::get('/companies/{id}/structures', [\App\Http\Controllers\Api\CompanyController::class, 'getStructureCompany']);

// API Owner Routes
Route::middleware('auth:sanctum')->prefix('owner')->group(function () {
    // Shifts for Owner
    Route::get('/shifts', [\App\Http\Controllers\Api\Owner\ShiftController::class, 'index']);
    Route::post('/shifts', [\App\Http\Controllers\Api\Owner\ShiftController::class, 'store']);
    Route::get('/shifts/{id}', [\App\Http\Controllers\Api\Owner\ShiftController::class, 'show']);
    Route::put('/shifts/{id}', [\App\Http\Controllers\Api\Owner\ShiftController::class, 'update']);
    Route::delete('/shifts/{id}', [\App\Http\Controllers\Api\Owner\ShiftController::class, 'destroy']);

    // Schedules for Owner
    Route::get('/schedules', [\App\Http\Controllers\Api\Owner\ScheduleController::class, 'getSchedules']);
    Route::post('/schedules/bulk-store', [\App\Http\Controllers\Api\Owner\ScheduleController::class, 'bulkStore']);
    Route::put('/schedules/{id}', [\App\Http\Controllers\Api\Owner\ScheduleController::class, 'update']);
    Route::delete('/schedules/{id}', [\App\Http\Controllers\Api\Owner\ScheduleController::class, 'destroy']);
    
    Route::get('/schedule-swaps/personal', [\App\Http\Controllers\Api\Owner\ScheduleController::class, 'getPersonalSwaps']);
    Route::get('/schedule-swaps/personal/{id}', [\App\Http\Controllers\Api\Owner\ScheduleController::class, 'getPersonalSwapDetail']);
    Route::post('/schedule-swaps/personal/{id}/approve', [\App\Http\Controllers\Api\Owner\ScheduleController::class, 'approvePersonalSwap']);
    Route::post('/schedule-swaps/personal/{id}/reject', [\App\Http\Controllers\Api\Owner\ScheduleController::class, 'rejectPersonalSwap']);
    
    Route::get('/schedule-swaps/team', [\App\Http\Controllers\Api\Owner\ScheduleController::class, 'getTeamSwaps']);
    Route::get('/schedule-swaps/team/{id}', [\App\Http\Controllers\Api\Owner\ScheduleController::class, 'getTeamSwapDetail']);
    Route::post('/schedule-swaps/team/{id}/approve', [\App\Http\Controllers\Api\Owner\ScheduleController::class, 'approveTeamSwap']);
    Route::post('/schedule-swaps/team/{id}/reject', [\App\Http\Controllers\Api\Owner\ScheduleController::class, 'rejectTeamSwap']);
    
    // Company Profile for Owner
    Route::get('/company', [\App\Http\Controllers\Api\Owner\CompanyController::class, 'show']);
    Route::post('/company', [\App\Http\Controllers\Api\Owner\CompanyController::class, 'update']);
    Route::post('/company/setup', [\App\Http\Controllers\Api\Owner\CompanyController::class, 'setup']);

    // Employees & Company Structure for Owner
    Route::get('/employees', [\App\Http\Controllers\Api\Owner\EmployeeController::class, 'getEmployees']);
    Route::post('/employees', [\App\Http\Controllers\Api\Owner\EmployeeController::class, 'store']);
    Route::get('/employees/{id}', [\App\Http\Controllers\Api\Owner\EmployeeController::class, 'show']);
    Route::post('/employees/{id}', [\App\Http\Controllers\Api\Owner\EmployeeController::class, 'update']);
    Route::post('/employees/{id}/toggle-status', [\App\Http\Controllers\Api\Owner\EmployeeController::class, 'toggleStatus']);
    Route::delete('/employees/{id}', [\App\Http\Controllers\Api\Owner\EmployeeController::class, 'destroy']);
    Route::get('/company-structure', [\App\Http\Controllers\Api\Owner\EmployeeController::class, 'getCompanyStructure']);

    // Pending Employees (Approval Registrasi Calon Karyawan) for Owner
    Route::get('/pending-employees', [\App\Http\Controllers\Api\Owner\PendingEmployeeController::class, 'index']);
    Route::get('/pending-employees/{id}', [\App\Http\Controllers\Api\Owner\PendingEmployeeController::class, 'show']);
    Route::post('/pending-employees/{id}/approve', [\App\Http\Controllers\Api\Owner\PendingEmployeeController::class, 'approve']);
    Route::post('/pending-employees/{id}/reject', [\App\Http\Controllers\Api\Owner\PendingEmployeeController::class, 'reject']);

    // Alias routes for /employees/pending
    Route::get('/employees/pending', [\App\Http\Controllers\Api\Owner\PendingEmployeeController::class, 'index']);
    Route::get('/employees/pending/{id}', [\App\Http\Controllers\Api\Owner\PendingEmployeeController::class, 'show']);
    Route::post('/employees/pending/{id}/approve', [\App\Http\Controllers\Api\Owner\PendingEmployeeController::class, 'approve']);
    Route::post('/employees/pending/{id}/reject', [\App\Http\Controllers\Api\Owner\PendingEmployeeController::class, 'reject']);

    // Branches for Owner
    Route::get('/branches', [\App\Http\Controllers\Api\Owner\BranchController::class, 'index']);
    Route::post('/branches', [\App\Http\Controllers\Api\Owner\BranchController::class, 'store']);
    Route::get('/branches/{id}', [\App\Http\Controllers\Api\Owner\BranchController::class, 'show']);
    Route::put('/branches/{id}', [\App\Http\Controllers\Api\Owner\BranchController::class, 'update']);
    Route::delete('/branches/{id}', [\App\Http\Controllers\Api\Owner\BranchController::class, 'destroy']);

    // Positions for Owner
    Route::get('/positions', [\App\Http\Controllers\Api\Owner\PositionController::class, 'index']);
    Route::post('/positions', [\App\Http\Controllers\Api\Owner\PositionController::class, 'store']);
    Route::get('/positions/{id}', [\App\Http\Controllers\Api\Owner\PositionController::class, 'show']);
    Route::put('/positions/{id}', [\App\Http\Controllers\Api\Owner\PositionController::class, 'update']);
    Route::delete('/positions/{id}', [\App\Http\Controllers\Api\Owner\PositionController::class, 'destroy']);

    // Divisions for Owner
    Route::get('/divisions', [\App\Http\Controllers\Api\Owner\DivisionController::class, 'index']);
    Route::post('/divisions', [\App\Http\Controllers\Api\Owner\DivisionController::class, 'store']);
    Route::get('/divisions/{id}', [\App\Http\Controllers\Api\Owner\DivisionController::class, 'show']);
    Route::put('/divisions/{id}', [\App\Http\Controllers\Api\Owner\DivisionController::class, 'update']);
    Route::delete('/divisions/{id}', [\App\Http\Controllers\Api\Owner\DivisionController::class, 'destroy']);

    // Announcements for Owner
    Route::get('/announcements', [\App\Http\Controllers\Api\Owner\AnnouncementController::class, 'index']);
    Route::post('/announcements', [\App\Http\Controllers\Api\Owner\AnnouncementController::class, 'store']);
    Route::get('/announcements/{id}', [\App\Http\Controllers\Api\Owner\AnnouncementController::class, 'show']);
    Route::post('/announcements/{id}', [\App\Http\Controllers\Api\Owner\AnnouncementController::class, 'update']);
    Route::delete('/announcements/{id}', [\App\Http\Controllers\Api\Owner\AnnouncementController::class, 'destroy']);

    // Feedbacks (Kritik & Saran) for Owner
    Route::get('/feedbacks', [\App\Http\Controllers\Api\Owner\FeedbackController::class, 'index']);
    Route::get('/feedbacks/{id}', [\App\Http\Controllers\Api\Owner\FeedbackController::class, 'show']);
    Route::delete('/feedbacks/{id}', [\App\Http\Controllers\Api\Owner\FeedbackController::class, 'destroy']);

    // Holidays for Owner
    Route::get('/holidays', [\App\Http\Controllers\Api\Owner\HolidayController::class, 'index']);
    Route::post('/holidays', [\App\Http\Controllers\Api\Owner\HolidayController::class, 'store']);
    Route::get('/holidays/{id}', [\App\Http\Controllers\Api\Owner\HolidayController::class, 'show']);
    Route::put('/holidays/{id}', [\App\Http\Controllers\Api\Owner\HolidayController::class, 'update']);
    Route::delete('/holidays/{id}', [\App\Http\Controllers\Api\Owner\HolidayController::class, 'destroy']);

    // Leaves for Owner
    Route::get('/leaves/categories', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'getCategories']);
    Route::post('/leaves/categories', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'storeCategory']);
    Route::get('/leaves/categories/{id}', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'showCategory']);
    Route::put('/leaves/categories/{id}', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'updateCategory']);
    Route::delete('/leaves/categories/{id}', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'deleteCategory']);

    Route::get('/leaves/balances', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'getBalances']);
    Route::put('/leaves/balances/{id}', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'updateBalance']);

    Route::get('/leaves', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'getRequests']);
    Route::post('/leaves/{id}/approve', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'approveRequest']);
    Route::post('/leaves/{id}/reject', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'rejectRequest']);
    Route::delete('/leaves/{id}', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'deleteRequest']);

    // Overtimes for Owner
    Route::get('/overtimes', [\App\Http\Controllers\Api\Owner\OvertimeController::class, 'index']);
    Route::post('/overtimes/{id}/approve', [\App\Http\Controllers\Api\Owner\OvertimeController::class, 'approve']);
    Route::post('/overtimes/{id}/reject', [\App\Http\Controllers\Api\Owner\OvertimeController::class, 'reject']);
    Route::delete('/overtimes/{id}', [\App\Http\Controllers\Api\Owner\OvertimeController::class, 'destroy']);

    // Agendas for Owner
    Route::get('/agendas', [\App\Http\Controllers\Api\Owner\AgendaController::class, 'index']);
    Route::post('/agendas', [\App\Http\Controllers\Api\Owner\AgendaController::class, 'store']);
    Route::put('/agendas/{id}', [\App\Http\Controllers\Api\Owner\AgendaController::class, 'update']);
    Route::delete('/agendas/{id}', [\App\Http\Controllers\Api\Owner\AgendaController::class, 'destroy']);

    // Daily Reports for Owner
    Route::get('/daily-reports', [\App\Http\Controllers\Api\Owner\DailyReportController::class, 'index']);
    Route::post('/daily-reports', [\App\Http\Controllers\Api\Owner\DailyReportController::class, 'store']);
    Route::post('/daily-reports/{id}', [\App\Http\Controllers\Api\Owner\DailyReportController::class, 'update']);
    Route::delete('/daily-reports/{id}', [\App\Http\Controllers\Api\Owner\DailyReportController::class, 'destroy']);

    // Attendances for Owner
    Route::get('/attendances', [\App\Http\Controllers\Api\Owner\AttendanceController::class, 'index']);

    // Reimbursements for Owner
    Route::get('/reimbursements', [\App\Http\Controllers\Api\Owner\ReimbursementController::class, 'index']);
    Route::post('/reimbursements/{id}/approve', [\App\Http\Controllers\Api\Owner\ReimbursementController::class, 'approve']);
    Route::post('/reimbursements/{id}/reject', [\App\Http\Controllers\Api\Owner\ReimbursementController::class, 'reject']);
    Route::delete('/reimbursements/{id}', [\App\Http\Controllers\Api\Owner\ReimbursementController::class, 'destroy']);

    // Loans for Owner
    Route::get('/loans', [\App\Http\Controllers\Api\Owner\LoanController::class, 'index']);
    Route::post('/loans', [\App\Http\Controllers\Api\Owner\LoanController::class, 'store']);
    Route::post('/loans/{id}/approve', [\App\Http\Controllers\Api\Owner\LoanController::class, 'approve']);
    Route::post('/loans/{id}/reject', [\App\Http\Controllers\Api\Owner\LoanController::class, 'reject']);
    Route::delete('/loans/{id}', [\App\Http\Controllers\Api\Owner\LoanController::class, 'destroy']);
    Route::post('/loans/{id}/installments/{installmentId}/pay', [\App\Http\Controllers\Api\Owner\LoanController::class, 'updateInstallment']);
    Route::post('/loans/{id}/installments/{installmentId}/update', [\App\Http\Controllers\Api\Owner\LoanController::class, 'updateInstallment']);

    // Payrolls for Owner
    Route::get('/payrolls', [\App\Http\Controllers\Api\Owner\PayrollController::class, 'index']);
    Route::get('/payrolls/{id}/items', [\App\Http\Controllers\Api\Owner\PayrollController::class, 'items']);
    Route::get('/payrolls/{id}/items/{itemId}', [\App\Http\Controllers\Api\Owner\PayrollController::class, 'showItem']);
    Route::get('/payrolls/{id}/items/{itemId}/download', [\App\Http\Controllers\Api\Owner\PayrollController::class, 'downloadItem'])->name('api.owner.payrolls.download-item');
    Route::post('/payrolls/{id}/pay', [\App\Http\Controllers\Api\Owner\PayrollController::class, 'markAsPaid']);
    Route::delete('/payrolls/{id}', [\App\Http\Controllers\Api\Owner\PayrollController::class, 'destroy']);

    // System Settings for Owner
    Route::get('/settings', [\App\Http\Controllers\Api\Owner\SettingController::class, 'index']);
    Route::get('/settings/attendance', [\App\Http\Controllers\Api\Owner\SettingController::class, 'getAttendance']);
    Route::put('/settings/attendance', [\App\Http\Controllers\Api\Owner\SettingController::class, 'updateAttendance']);
    Route::get('/settings/loan', [\App\Http\Controllers\Api\Owner\SettingController::class, 'getLoan']);
    Route::put('/settings/loan', [\App\Http\Controllers\Api\Owner\SettingController::class, 'updateLoan']);
    Route::get('/settings/payroll', [\App\Http\Controllers\Api\Owner\SettingController::class, 'getPayroll']);
    Route::put('/settings/payroll', [\App\Http\Controllers\Api\Owner\SettingController::class, 'updatePayroll']);

    // Feedbacks (Kritik & Saran) for Owner
    Route::get('/feedbacks', [\App\Http\Controllers\Api\Owner\FeedbackController::class, 'index']);
    Route::get('/feedbacks/{id}', [\App\Http\Controllers\Api\Owner\FeedbackController::class, 'show']);
    Route::delete('/feedbacks/{id}', [\App\Http\Controllers\Api\Owner\FeedbackController::class, 'destroy']);

    // Profile & Security for Owner
    Route::get('/profile', [\App\Http\Controllers\Api\Owner\ProfileController::class, 'show']);
    Route::post('/profile', [\App\Http\Controllers\Api\Owner\ProfileController::class, 'update']);
    Route::post('/change-password', [\App\Http\Controllers\Api\Owner\ProfileController::class, 'changePassword']);

    // Notifications for Owner
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Owner\NotificationController::class, 'index']);
        Route::get('/unread-count', [\App\Http\Controllers\Api\Owner\NotificationController::class, 'unreadCount']);
        Route::post('/{id}/read', [\App\Http\Controllers\Api\Owner\NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [\App\Http\Controllers\Api\Owner\NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\Owner\NotificationController::class, 'destroy']);
    });
});

// API Employee Routes
Route::middleware('auth:sanctum')->prefix('employee')->group(function () {
    // Schedules & Swaps for Employee
    Route::get('/schedules', [\App\Http\Controllers\Api\Employee\ScheduleController::class, 'getSchedules']);
    Route::get('/colleagues', [\App\Http\Controllers\Api\Employee\ScheduleController::class, 'getColleagues']);

    Route::get('/schedule-swaps/personal', [\App\Http\Controllers\Api\Employee\ScheduleController::class, 'getPersonalSwaps']);
    Route::get('/schedule-swaps/personal/{id}', [\App\Http\Controllers\Api\Employee\ScheduleController::class, 'getPersonalSwapDetail']);
    Route::post('/schedule-swaps/personal', [\App\Http\Controllers\Api\Employee\ScheduleController::class, 'storePersonalSwap']);

    Route::get('/schedule-swaps/team', [\App\Http\Controllers\Api\Employee\ScheduleController::class, 'getTeamSwaps']);
    Route::get('/schedule-swaps/team/{id}', [\App\Http\Controllers\Api\Employee\ScheduleController::class, 'getTeamSwapDetail']);
    Route::post('/schedule-swaps/team', [\App\Http\Controllers\Api\Employee\ScheduleController::class, 'storeTeamSwap']);

    // Leaves for Employee
    Route::get('/leaves/balances', [\App\Http\Controllers\Api\Employee\LeaveController::class, 'getBalances']);
    Route::get('/leaves', [\App\Http\Controllers\Api\Employee\LeaveController::class, 'getRequests']);
    Route::post('/leaves', [\App\Http\Controllers\Api\Employee\LeaveController::class, 'storeRequest']);
    Route::post('/leaves/{id}/cancel', [\App\Http\Controllers\Api\Employee\LeaveController::class, 'cancelRequest']);

    // Overtimes for Employee
    Route::get('/overtimes', [\App\Http\Controllers\Api\Employee\OvertimeController::class, 'index']);
    Route::post('/overtimes', [\App\Http\Controllers\Api\Employee\OvertimeController::class, 'store']);
    Route::post('/overtimes/{id}/cancel', [\App\Http\Controllers\Api\Employee\OvertimeController::class, 'cancel']);

    // Agendas for Employee
    Route::get('/agendas', [\App\Http\Controllers\Api\Employee\AgendaController::class, 'index']);
    Route::post('/agendas', [\App\Http\Controllers\Api\Employee\AgendaController::class, 'store']);
    Route::put('/agendas/{id}', [\App\Http\Controllers\Api\Employee\AgendaController::class, 'update']);
    Route::delete('/agendas/{id}', [\App\Http\Controllers\Api\Employee\AgendaController::class, 'destroy']);

    // Daily Reports for Employee
    Route::get('/daily-reports', [\App\Http\Controllers\Api\Employee\DailyReportController::class, 'index']);
    Route::post('/daily-reports', [\App\Http\Controllers\Api\Employee\DailyReportController::class, 'store']);
    Route::post('/daily-reports/{id}', [\App\Http\Controllers\Api\Employee\DailyReportController::class, 'update']);
    Route::delete('/daily-reports/{id}', [\App\Http\Controllers\Api\Employee\DailyReportController::class, 'destroy']);

    // Attendances (Riwayat Kehadiran)
    Route::get('/attendances', [\App\Http\Controllers\Api\Employee\AttendanceController::class, 'index']);

    // Presence (Aksi & Validasi Presensi Realtime)
    Route::get('/presence/today', [\App\Http\Controllers\Api\Employee\PresenceController::class, 'todayStatus']);
    Route::get('/presence/check-radius', [\App\Http\Controllers\Api\Employee\PresenceController::class, 'checkRadius']);
    Route::get('/presence/camera', [\App\Http\Controllers\Api\Employee\PresenceController::class, 'checkCamera']);
    Route::post('/presence', [\App\Http\Controllers\Api\Employee\PresenceController::class, 'store']);

    // Payrolls (Slip Gaji) for Employee
    Route::get('/payrolls', [\App\Http\Controllers\Api\Employee\PayrollController::class, 'index']);
    Route::get('/payrolls/{id}/download', [\App\Http\Controllers\Api\Employee\PayrollController::class, 'download'])->name('api.employee.payrolls.download');

    // Loans (Kasbon / Pinjaman) for Employee
    Route::get('/loans', [\App\Http\Controllers\Api\Employee\LoanController::class, 'index']);
    Route::get('/loans/settings', [\App\Http\Controllers\Api\Employee\LoanController::class, 'settings']);
    Route::post('/loans', [\App\Http\Controllers\Api\Employee\LoanController::class, 'store']);

    // Reimbursements (Klaim Biaya Operasional) for Employee
    Route::get('/reimbursements', [\App\Http\Controllers\Api\Employee\ReimbursementController::class, 'index']);
    Route::post('/reimbursements', [\App\Http\Controllers\Api\Employee\ReimbursementController::class, 'store']);

    // Announcements (Pengumuman) for Employee
    Route::get('/announcements', [\App\Http\Controllers\Api\Employee\AnnouncementController::class, 'index']);

    // Feedbacks (Kritik & Saran) for Employee
    Route::get('/feedbacks', [\App\Http\Controllers\Api\Employee\FeedbackController::class, 'index']);
    Route::post('/feedbacks', [\App\Http\Controllers\Api\Employee\FeedbackController::class, 'store']);
    Route::get('/feedbacks/{id}', [\App\Http\Controllers\Api\Employee\FeedbackController::class, 'show']);
    Route::delete('/feedbacks/{id}', [\App\Http\Controllers\Api\Employee\FeedbackController::class, 'destroy']);

    // Profile & Security for Employee
    Route::get('/profile', [\App\Http\Controllers\Api\Employee\ProfileController::class, 'show']);
    Route::post('/profile', [\App\Http\Controllers\Api\Employee\ProfileController::class, 'update']);
    Route::post('/change-password', [\App\Http\Controllers\Api\Employee\ProfileController::class, 'changePassword']);

    // THR (Tunjangan Hari Raya) for Employee
    Route::get('/thr', [\App\Http\Controllers\Api\Employee\ThrController::class, 'index']);
    Route::get('/thr/{id}/download', [\App\Http\Controllers\Api\Employee\ThrController::class, 'download'])->name('api.employee.thr.download');

    // Notifications for Employee
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Employee\NotificationController::class, 'index']);
        Route::get('/unread-count', [\App\Http\Controllers\Api\Employee\NotificationController::class, 'unreadCount']);
        Route::post('/{id}/read', [\App\Http\Controllers\Api\Employee\NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [\App\Http\Controllers\Api\Employee\NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\Employee\NotificationController::class, 'destroy']);
    });
});




