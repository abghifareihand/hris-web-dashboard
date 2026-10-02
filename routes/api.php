<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/register/owner', [AuthController::class, 'registerOwner']);
    Route::post('/register/employee', [AuthController::class, 'registerEmployee']);
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile/owner', [AuthController::class, 'profileOwner']);
        Route::get('/profile/employee', [AuthController::class, 'profileEmployee']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// API Company Public Routes (Without Auth)
Route::get('/companies/search', [\App\Http\Controllers\Api\CompanyController::class, 'searchCompany']);
Route::get('/companies/{id}/structures', [\App\Http\Controllers\Api\CompanyController::class, 'getStructureCompany']);

// API Owner Routes
Route::middleware('auth:sanctum')->prefix('owner')->group(function () {
    // Schedules & Swaps for Owner
    Route::get('/shifts', [\App\Http\Controllers\Api\Owner\ScheduleController::class, 'getShifts']);
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
    
    // Employees & Company Structure for Owner
    Route::get('/employees', [\App\Http\Controllers\Api\Owner\EmployeeController::class, 'getEmployees']);
    Route::get('/company-structure', [\App\Http\Controllers\Api\Owner\EmployeeController::class, 'getCompanyStructure']);

    // Leaves for Owner
    Route::get('/leaves/categories', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'getCategories']);
    Route::post('/leaves/categories', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'storeCategory']);
    Route::put('/leaves/categories/{id}', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'updateCategory']);
    Route::delete('/leaves/categories/{id}', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'deleteCategory']);

    Route::get('/leaves/balances', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'getBalances']);
    Route::put('/leaves/balances/{id}', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'updateBalance']);

    Route::get('/leaves/requests', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'getRequests']);
    Route::post('/leaves/requests/{id}/approve', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'approveRequest']);
    Route::post('/leaves/requests/{id}/reject', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'rejectRequest']);
    Route::delete('/leaves/requests/{id}', [\App\Http\Controllers\Api\Owner\LeaveController::class, 'deleteRequest']);

    // Overtimes for Owner
    Route::get('/overtimes/requests', [\App\Http\Controllers\Api\Owner\OvertimeController::class, 'index']);
    Route::post('/overtimes/requests/{id}/approve', [\App\Http\Controllers\Api\Owner\OvertimeController::class, 'approve']);
    Route::post('/overtimes/requests/{id}/reject', [\App\Http\Controllers\Api\Owner\OvertimeController::class, 'reject']);
    Route::delete('/overtimes/requests/{id}', [\App\Http\Controllers\Api\Owner\OvertimeController::class, 'destroy']);

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

    // Attendance for Employee
    Route::get('/attendance/history', [\App\Http\Controllers\Api\Employee\AttendanceController::class, 'history']);
    Route::get('/attendance/check-radius', [\App\Http\Controllers\Api\Employee\AttendanceController::class, 'checkRadius']);
    Route::get('/attendance/camera', [\App\Http\Controllers\Api\Employee\AttendanceController::class, 'checkCamera']);
    Route::get('/attendance/today', [\App\Http\Controllers\Api\Employee\AttendanceController::class, 'todayStatus']);
    Route::post('/attendance', [\App\Http\Controllers\Api\Employee\AttendanceController::class, 'store']);

    // Payrolls (Slip Gaji) for Employee
    Route::get('/payrolls', [\App\Http\Controllers\Api\Employee\PayrollController::class, 'index']);
    Route::get('/payrolls/{id}/download', [\App\Http\Controllers\Api\Employee\PayrollController::class, 'download'])->name('api.employee.payrolls.download');

    // Loans (Kasbon / Pinjaman) for Employee
    Route::get('/loans', [\App\Http\Controllers\Api\Employee\LoanController::class, 'index']);
    Route::post('/loans', [\App\Http\Controllers\Api\Employee\LoanController::class, 'store']);

    // Reimbursements (Klaim Biaya Operasional) for Employee
    Route::get('/reimbursements', [\App\Http\Controllers\Api\Employee\ReimbursementController::class, 'index']);
    Route::post('/reimbursements', [\App\Http\Controllers\Api\Employee\ReimbursementController::class, 'store']);

    // Announcements (Pengumuman) for Employee
    Route::get('/announcements', [\App\Http\Controllers\Api\Employee\AnnouncementController::class, 'index']);

    // Profile & Security for Employee
    Route::get('/profile', [\App\Http\Controllers\Api\Employee\ProfileController::class, 'show']);
    Route::post('/profile', [\App\Http\Controllers\Api\Employee\ProfileController::class, 'update']);
    Route::post('/change-password', [\App\Http\Controllers\Api\Employee\ProfileController::class, 'changePassword']);

    // THR (Tunjangan Hari Raya) for Employee
    Route::get('/thr', [\App\Http\Controllers\Api\Employee\ThrController::class, 'index']);
    Route::get('/thr/{id}/download', [\App\Http\Controllers\Api\Employee\ThrController::class, 'download'])->name('api.employee.thr.download');
});


