<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\Admin\AdminAuthController;
use App\Http\Controllers\Web\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Web\Admin\CompanyController as AdminCompanyController;
use App\Http\Controllers\Web\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Web\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Web\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Web\Owner\Management\Company\ProfileController;
use App\Http\Controllers\Web\Owner\Management\Company\BranchController;
use App\Http\Controllers\Web\Owner\Management\Company\DivisionController;
use App\Http\Controllers\Web\Owner\Management\Company\PositionController;
use App\Http\Controllers\Web\Owner\Management\EmployeeController;
use App\Http\Controllers\Web\Owner\Management\PendingEmployeeController;
use App\Http\Controllers\Web\Owner\Management\AttendanceSettingController;
use App\Http\Controllers\Web\Owner\Management\ScheduleController;
use App\Http\Controllers\Web\Owner\Management\LeaveController;
use App\Http\Controllers\Web\Owner\Management\OvertimeController;
use App\Http\Controllers\Web\Owner\MyProfileController;
use App\Http\Controllers\Web\Owner\NotificationController;
use App\Http\Controllers\Web\Owner\Finance\ReimbursementController;
use App\Http\Controllers\Web\Owner\Finance\LoanController;
use App\Http\Controllers\Web\Owner\Finance\LoanSettingController;
use App\Http\Controllers\Web\Owner\Finance\PayrollController;
use App\Http\Controllers\Web\Owner\Finance\TaxController;
use App\Http\Controllers\Web\Owner\Finance\ThrController;
use App\Http\Controllers\Web\Owner\Finance\ThrSettingController;
use App\Http\Controllers\Web\Owner\Reports\AttendanceController as OwnerAttendanceReportController;
use App\Http\Controllers\Web\Owner\Reports\PerformanceController as OwnerPerformanceReportController;
use App\Http\Controllers\Web\Owner\AnnouncementController as OwnerAnnouncementController;
use App\Http\Controllers\Web\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Web\Employee\AttendanceController as EmployeeAttendanceController;
use App\Http\Controllers\Web\Employee\ScheduleController as EmployeeScheduleController;
use App\Http\Controllers\Web\Employee\LeaveController as EmployeeLeaveController;
use App\Http\Controllers\Web\Employee\OvertimeController as EmployeeOvertimeController;
use App\Http\Controllers\Web\Employee\ReimbursementController as EmployeeReimbursementController;
use App\Http\Controllers\Web\Employee\LoanController as EmployeeLoanController;
use App\Http\Controllers\Web\Employee\PayrollController as EmployeePayrollController;
use App\Http\Controllers\Web\Employee\ProfileController as EmployeeProfileController;
use App\Http\Controllers\Web\Employee\CompanyController as EmployeeCompanyController;

/*
|--------------------------------------------------------------------------
| Web Routes - Frans HRIS SaaS
|--------------------------------------------------------------------------
*/

// Public Landing Page
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Shortcuts
Route::get('/pricing', fn () => redirect('/'))->name('pricing');
Route::get('/privacy', fn () => redirect('/'))->name('privacy');
Route::get('/terms', fn () => redirect('/'))->name('terms');

// General Auth (Tenant: Owner & Employee)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // =========================================================================
    // Owner Routes
    // =========================================================================
    Route::middleware('role:owner')->prefix('owner')->name('owner.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Web\Owner\DashboardController::class, 'index'])->name('dashboard');

        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('/{id}/read', [NotificationController::class, 'read'])->name('read');
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        });

        // My Profile
        Route::prefix('my-profile')->name('my-profile.')->group(function () {
            Route::get('/', [MyProfileController::class, 'index'])->name('index');
            Route::put('/', [MyProfileController::class, 'update'])->name('update');
            Route::put('/password', [MyProfileController::class, 'password'])->name('password');
        });

        // Management (Batch 1: Organization & Employees)
        Route::prefix('management')->name('management.')->group(function () {
            // Pending Employees (defined before employees resource)
            Route::get('/employees/pending', [PendingEmployeeController::class, 'index'])->name('employees.pending.index');
            Route::post('/employees/pending/{id}/approve', [PendingEmployeeController::class, 'approve'])->name('employees.pending.approve');
            Route::post('/employees/pending/{id}/reject', [PendingEmployeeController::class, 'reject'])->name('employees.pending.reject');

            // Employees CRUD
            Route::resource('employees', EmployeeController::class);

            // Company Organization
            Route::prefix('company')->name('company.')->group(function () {
                Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
                Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
                Route::resource('branches', BranchController::class)->except(['show']);
                Route::resource('divisions', DivisionController::class)->except(['show']);
                Route::resource('positions', PositionController::class)->except(['show']);
            });

            // Attendance Settings
            Route::prefix('attendance')->name('attendance.')->group(function () {
                Route::get('/settings', [AttendanceSettingController::class, 'index'])->name('settings.index');
                Route::put('/settings', [AttendanceSettingController::class, 'update'])->name('settings.update');
                Route::post('/settings/status', [AttendanceSettingController::class, 'updateStatus'])->name('settings.update-status');
            });

            // Schedules
            Route::prefix('schedules')->name('schedules.')->group(function () {
                // Shifts
                Route::get('/shifts', [ScheduleController::class, 'shifts'])->name('shifts.index');
                Route::get('/shifts/create', [ScheduleController::class, 'createShifts'])->name('shifts.create');
                Route::post('/shifts', [ScheduleController::class, 'storeShift'])->name('shifts.store');
                Route::get('/shifts/{id}/edit', [ScheduleController::class, 'editShift'])->name('shifts.edit');
                Route::put('/shifts/{id}', [ScheduleController::class, 'updateShift'])->name('shifts.update');
                Route::delete('/shifts/{id}', [ScheduleController::class, 'destroyShift'])->name('shifts.destroy');

                // Holidays
                Route::get('/holidays', [ScheduleController::class, 'holidays'])->name('holidays.index');
                Route::get('/holidays/create', [ScheduleController::class, 'createHolidays'])->name('holidays.create');
                Route::post('/holidays', [ScheduleController::class, 'storeHoliday'])->name('holidays.store');
                Route::get('/holidays/{id}/edit', [ScheduleController::class, 'editHoliday'])->name('holidays.edit');
                Route::put('/holidays/{id}', [ScheduleController::class, 'updateHoliday'])->name('holidays.update');
                Route::delete('/holidays/{id}', [ScheduleController::class, 'destroyHoliday'])->name('holidays.destroy');

                // Work Schedules
                Route::get('/work', [ScheduleController::class, 'work'])->name('work.index');
                Route::get('/work/create', [ScheduleController::class, 'createWork'])->name('work.create');
                Route::post('/work/create', [ScheduleController::class, 'storeWork'])->name('work.store');
                Route::post('/work', [ScheduleController::class, 'storeWork']);
                Route::get('/work/api-employees', [ScheduleController::class, 'apiEmployees'])->name('work.api.employees');
                Route::get('/work/{schedule}/edit', [ScheduleController::class, 'editWork'])->name('work.edit');
                Route::put('/work/{schedule}', [ScheduleController::class, 'updateWork'])->name('work.update');
                Route::delete('/work/{schedule}', [ScheduleController::class, 'destroyWork'])->name('work.destroy');

                // Swap Personal
                Route::get('/swap-personal', [ScheduleController::class, 'swapPersonal'])->name('swap-personal.index');
                Route::post('/swap-personal/{id}/approve', [ScheduleController::class, 'approveSwapPersonal'])->name('swap-personal.approve');
                Route::post('/swap-personal/{id}/reject', [ScheduleController::class, 'rejectSwapPersonal'])->name('swap-personal.reject');

                // Swap Team
                Route::get('/swap-team', [ScheduleController::class, 'swapTeam'])->name('swap-team.index');
                Route::post('/swap-team/{id}/approve', [ScheduleController::class, 'approveSwapTeam'])->name('swap-team.approve');
                Route::post('/swap-team/{id}/reject', [ScheduleController::class, 'rejectSwapTeam'])->name('swap-team.reject');
            });

            // Leaves
            Route::prefix('leaves')->name('leaves.')->group(function () {
                Route::get('/pending', [LeaveController::class, 'pending'])->name('pending.index');
                Route::post('/pending/{id}/approve', [LeaveController::class, 'approve'])->name('pending.approve');
                Route::post('/pending/{id}/reject', [LeaveController::class, 'reject'])->name('pending.reject');

                Route::get('/', [LeaveController::class, 'index'])->name('index');

                Route::get('/balance', [LeaveController::class, 'balance'])->name('balance.index');
                Route::get('/balance/{id}/edit', [LeaveController::class, 'editBalance'])->name('balance.edit');
                Route::put('/balance/{id}', [LeaveController::class, 'updateBalance'])->name('balance.update');

                Route::get('/categories', [LeaveController::class, 'categories'])->name('categories.index');
                Route::get('/categories/create', [LeaveController::class, 'createCategory'])->name('categories.create');
                Route::post('/categories', [LeaveController::class, 'storeCategory'])->name('categories.store');
                Route::get('/categories/{id}/edit', [LeaveController::class, 'editCategory'])->name('categories.edit');
                Route::put('/categories/{id}', [LeaveController::class, 'updateCategory'])->name('categories.update');
                Route::delete('/categories/{id}', [LeaveController::class, 'destroyCategory'])->name('categories.destroy');
            });

            // Overtimes
            Route::prefix('overtimes')->name('overtimes.')->group(function () {
                Route::get('/pending', [OvertimeController::class, 'pending'])->name('pending.index');
                Route::post('/pending/{id}/approve', [OvertimeController::class, 'approve'])->name('pending.approve');
                Route::post('/pending/{id}/reject', [OvertimeController::class, 'reject'])->name('pending.reject');
                Route::get('/', [OvertimeController::class, 'index'])->name('index');
            });
        });

        Route::prefix('finance')->name('finance.')->group(function () {
            // Reimbursements
            Route::prefix('reimbursements')->name('reimbursements.')->group(function () {
                Route::get('/pending', [ReimbursementController::class, 'pending'])->name('pending.index');
                Route::post('/pending/{id}/approve', [ReimbursementController::class, 'approve'])->name('pending.approve');
                Route::post('/pending/{id}/reject', [ReimbursementController::class, 'reject'])->name('pending.reject');
                Route::get('/', [ReimbursementController::class, 'index'])->name('index');
                Route::get('/create', [ReimbursementController::class, 'create'])->name('create');
                Route::post('/', [ReimbursementController::class, 'store'])->name('store');
                Route::delete('/{id}', [ReimbursementController::class, 'destroy'])->name('destroy');
            });

            // Loans
            Route::prefix('loans')->name('loans.')->group(function () {
                Route::get('/settings', [LoanSettingController::class, 'index'])->name('settings.index');
                Route::post('/settings', [LoanSettingController::class, 'store'])->name('settings.store');
                Route::get('/pending', [LoanController::class, 'pending'])->name('pending.index');
                Route::post('/pending/{id}/approve', [LoanController::class, 'approve'])->name('pending.approve');
                Route::post('/pending/{id}/reject', [LoanController::class, 'reject'])->name('pending.reject');
                Route::get('/', [LoanController::class, 'index'])->name('index');
                Route::get('/create', [LoanController::class, 'create'])->name('create');
                Route::post('/', [LoanController::class, 'store'])->name('store');
                Route::get('/{id}', [LoanController::class, 'show'])->name('show');
                Route::delete('/{id}', [LoanController::class, 'destroy'])->name('destroy');
                Route::patch('/{id}/installments/{installmentId}', [LoanController::class, 'updateInstallment'])->name('update-installment');
            });

            // Payrolls
            Route::prefix('payrolls')->name('payrolls.')->group(function () {
                Route::get('/check-employees', [PayrollController::class, 'checkEmployees'])->name('check');
                Route::get('/master', [PayrollController::class, 'master'])->name('master.index');
                Route::get('/', [PayrollController::class, 'index'])->name('index');
                Route::get('/export-excel', [PayrollController::class, 'exportExcel'])->name('export-excel');
                Route::post('/', [PayrollController::class, 'store'])->name('store');
                Route::get('/{id}', [PayrollController::class, 'show'])->name('show');
                Route::get('/{id}/slips', [PayrollController::class, 'printSlips'])->name('slips');
                Route::get('/{id}/slips/{itemId}', [PayrollController::class, 'printSingleSlip'])->name('slip-single');
                Route::get('/{id}/items/{itemId}', [PayrollController::class, 'showItem'])->name('items.show');
                Route::get('/{id}/export-bank-csv', [PayrollController::class, 'exportBankCsv'])->name('export-bank-csv');
                Route::patch('/{id}/status', [PayrollController::class, 'updateStatus'])->name('update-status');
                Route::patch('/{id}/items', [PayrollController::class, 'updateItems'])->name('update-items');
                Route::delete('/{id}', [PayrollController::class, 'destroy'])->name('destroy');
            });

            // Taxes & BPJS
            Route::prefix('taxes')->name('taxes.')->group(function () {
                Route::get('/pph21', [TaxController::class, 'pph21'])->name('pph21');
                Route::get('/bpjs-tk', [TaxController::class, 'bpjsKetenagakerjaan'])->name('bpjs-tk');
                Route::post('/bpjs-tk', [TaxController::class, 'updateBpjsKetenagakerjaan'])->name('bpjs-tk.update');
                Route::get('/bpjs-kes', [TaxController::class, 'bpjsKesehatan'])->name('bpjs-kes');
                Route::post('/bpjs-kes', [TaxController::class, 'updateBpjsKesehatan'])->name('bpjs-kes.update');
            });

            // THR (Tunjangan Hari Raya)
            Route::prefix('thr')->name('thr.')->group(function () {
                Route::get('/settings', [ThrSettingController::class, 'index'])->name('settings.index');
                Route::post('/settings', [ThrSettingController::class, 'store'])->name('settings.store');
                Route::get('/', [ThrController::class, 'index'])->name('index');
                Route::get('/create', [ThrController::class, 'create'])->name('create');
                Route::post('/preview', [ThrController::class, 'create'])->name('preview');
                Route::post('/', [ThrController::class, 'store'])->name('store');
                Route::get('/{id}', [ThrController::class, 'show'])->name('show');
                Route::get('/{id}/slips', [ThrController::class, 'slip'])->name('slips');
                Route::get('/{id}/slips/{itemId}', [ThrController::class, 'slipSingle'])->name('slip-single');
                Route::post('/{id}/pay', [ThrController::class, 'pay'])->name('pay');
                Route::delete('/{id}', [ThrController::class, 'destroy'])->name('destroy');
            });
        });

        // REPORTS
        Route::prefix('reports')->name('reports.')->group(function () {
            // Attendances
            Route::get('/attendances', [OwnerAttendanceReportController::class, 'index'])->name('attendances.index');
            Route::get('/attendances/recap', [OwnerAttendanceReportController::class, 'recap'])->name('attendances.recap.index');
            Route::get('/attendances/recap/export', [OwnerAttendanceReportController::class, 'recapExport'])->name('attendances.recap.export');
            Route::get('/attendances/rate', [OwnerAttendanceReportController::class, 'rate'])->name('attendances.rate.index');
            Route::get('/attendances/rate/export', [OwnerAttendanceReportController::class, 'rateExport'])->name('attendances.rate.export');
            Route::get('/attendances/overtime-recap', [OwnerAttendanceReportController::class, 'overtimeRecap'])->name('attendances.overtime-recap.index');
            Route::get('/attendances/overtime-recap/export', [OwnerAttendanceReportController::class, 'overtimeRecapExport'])->name('attendances.overtime-recap.export');
            Route::get('/attendances/overtime-recap/{employee}', [OwnerAttendanceReportController::class, 'overtimeRecapDetail'])->whereNumber('employee')->name('attendances.overtime-recap.detail');
            Route::get('/attendances/overtime-recap/{employee}/export', [OwnerAttendanceReportController::class, 'overtimeRecapDetailExport'])->whereNumber('employee')->name('attendances.overtime-recap.detail-export');
            Route::get('/attendances/{id}', [OwnerAttendanceReportController::class, 'show'])->whereNumber('id')->name('attendances.show');

            // Performances
            Route::get('/performances/daily', [OwnerPerformanceReportController::class, 'daily'])->name('performances.daily.index');
            Route::get('/performances/daily/export', [OwnerPerformanceReportController::class, 'dailyExport'])->name('performances.daily.export');
            Route::get('/performances/daily/{id}', [OwnerPerformanceReportController::class, 'dailyShow'])->whereNumber('id')->name('performances.daily.show');
            Route::get('/performances/employee', [OwnerPerformanceReportController::class, 'employee'])->name('performances.employee.index');
            Route::get('/performances/employee/export', [OwnerPerformanceReportController::class, 'employeeExport'])->name('performances.employee.export');
            Route::get('/performances/branch', [OwnerPerformanceReportController::class, 'branch'])->name('performances.branch.index');
            Route::get('/performances/branch/export', [OwnerPerformanceReportController::class, 'branchExport'])->name('performances.branch.export');
        });

        // Announcements
        Route::resource('announcements', OwnerAnnouncementController::class);
    });

    // =========================================================================
    // Employee Routes
    // =========================================================================
    Route::middleware('role:employee')->prefix('employee')->name('employee.')->group(function () {
        Route::get('/dashboard', [EmployeeDashboardController::class, 'index'])->name('dashboard');

        // Attendances
        Route::get('/attendances', [EmployeeAttendanceController::class, 'index'])->name('attendances.index');
        Route::post('/attendances/clock-in', [EmployeeAttendanceController::class, 'clockIn'])->name('attendances.clock-in');
        Route::post('/attendances/clock-out', [EmployeeAttendanceController::class, 'clockOut'])->name('attendances.clock-out');

        // Schedules
        Route::prefix('schedules')->name('schedules.')->group(function () {
            Route::get('/work', [EmployeeScheduleController::class, 'work'])->name('work.index');
            Route::get('/swap-personal', [EmployeeScheduleController::class, 'swapPersonal'])->name('swap-personal.index');
            Route::post('/swap-personal', [EmployeeScheduleController::class, 'storePersonalSwap'])->name('swap-personal.store');
            Route::get('/swap-team', [EmployeeScheduleController::class, 'swapTeam'])->name('swap-team.index');
            Route::post('/swap-team', [EmployeeScheduleController::class, 'storeTeamSwap'])->name('swap-team.store');
        });

        // Leaves
        Route::prefix('leaves')->name('leaves.')->group(function () {
            Route::get('/', [EmployeeLeaveController::class, 'index'])->name('index');
            Route::post('/', [EmployeeLeaveController::class, 'store'])->name('store');
            Route::get('/balances', [EmployeeLeaveController::class, 'balances'])->name('balances');
        });

        // Overtimes
        Route::prefix('overtimes')->name('overtimes.')->group(function () {
            Route::get('/', [EmployeeOvertimeController::class, 'index'])->name('index');
            Route::post('/', [EmployeeOvertimeController::class, 'store'])->name('store');
        });

        // Reimbursements
        Route::prefix('reimbursements')->name('reimbursements.')->group(function () {
            Route::get('/', [EmployeeReimbursementController::class, 'index'])->name('index');
            Route::post('/', [EmployeeReimbursementController::class, 'store'])->name('store');
        });

        // Loans
        Route::prefix('loans')->name('loans.')->group(function () {
            Route::get('/', [EmployeeLoanController::class, 'index'])->name('index');
            Route::post('/', [EmployeeLoanController::class, 'store'])->name('store');
        });

        // Payrolls
        Route::get('/payrolls', [EmployeePayrollController::class, 'index'])->name('payrolls.index');

        // Profile & Security
        Route::get('/profile', [EmployeeProfileController::class, 'index'])->name('profile.index');
        Route::put('/profile', [EmployeeProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [EmployeeProfileController::class, 'changePassword'])->name('profile.password');

        // Company
        Route::get('/company', [EmployeeCompanyController::class, 'index'])->name('company.index');
    });
});

// =========================================================================
// Super Admin Routes (Dedicated portal)
// =========================================================================
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login']);
    });

    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('companies', AdminCompanyController::class);
        Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans.index');
        Route::get('/transactions', [AdminTransactionController::class, 'index'])->name('transactions.index');
        Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
    });
});
