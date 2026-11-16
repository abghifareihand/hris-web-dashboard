<?php

namespace Tests\Unit;

use App\Models\Position;
use PHPUnit\Framework\TestCase;

class PositionLegacyMigrationTest extends TestCase
{
    public function test_legacy_screenshot_permissions_are_migrated_to_25_canonical_keys()
    {
        // Exact raw permissions from user's screenshot
        $legacyRaw = [
            "agendas" => ["view" => false, "create" => false, "edit" => false, "delete" => false],
            "leaves" => ["view" => false, "create" => false, "edit" => false, "delete" => false],
            "overtimes" => ["view" => false, "create" => false, "edit" => false, "delete" => false],
            "reimbursements" => ["view" => false, "create" => true, "edit" => false, "delete" => false],
            "loans" => ["view" => false, "create" => false, "edit" => false, "delete" => false],
            "payslips" => ["view" => false, "create" => false, "edit" => false, "delete" => false],
            "feedbacks" => ["view" => false, "create" => false, "edit" => false, "delete" => false],
            "announcements" => ["view" => true, "create" => false, "edit" => false, "delete" => false],
            "company_profile" => ["view" => true, "create" => false, "edit" => false, "delete" => false],
            "leave_balances" => ["view" => false, "create" => false, "edit" => false, "delete" => false],
            "shifts" => ["view" => true, "create" => false, "edit" => false, "delete" => false],
            "branches" => ["view" => true, "create" => false, "edit" => false, "delete" => false],
            "holidays" => ["view" => true, "create" => false, "edit" => false, "delete" => false],
            "payrolls" => ["view" => false, "create" => false, "edit" => false, "delete" => false],
            "divisions" => ["view" => true, "create" => false, "edit" => false, "delete" => false],
            "positions" => ["view" => true, "create" => false, "edit" => false, "delete" => false],
            "swap_team" => ["view" => false, "create" => false, "edit" => false, "delete" => false],
            "employee_data" => ["view" => true, "create" => false, "edit" => false, "delete" => false],
            "swap_personal" => ["view" => false, "create" => false, "edit" => false, "delete" => false],
            "all_attendances" => ["view" => false, "create" => false, "edit" => false, "delete" => false],
            "system_settings" => ["view" => true, "create" => false, "edit" => false, "delete" => false],
            "leave_categories" => ["view" => true, "create" => false, "edit" => false, "delete" => false],
            "all_daily_reports" => ["view" => false, "create" => false, "edit" => false, "delete" => false],
            "employee_schedules" => ["view" => true, "create" => false, "edit" => false, "delete" => false],
        ];

        $migrated = Position::formatPermissions($legacyRaw);

        // 1. Must have exactly 24 keys
        $this->assertCount(24, $migrated);
        $this->assertSame(Position::CANONICAL_MENU_KEYS, array_keys($migrated));

        // 2. No old keys
        $this->assertArrayNotHasKey('leave_balances', $migrated);
        $this->assertArrayNotHasKey('employee_data', $migrated);
        $this->assertArrayNotHasKey('shifts', $migrated);
        $this->assertArrayNotHasKey('branches', $migrated);
        $this->assertArrayNotHasKey('divisions', $migrated);
        $this->assertArrayNotHasKey('positions', $migrated);
        $this->assertArrayNotHasKey('holidays', $migrated);
        $this->assertArrayNotHasKey('employee_schedules', $migrated);
        $this->assertArrayNotHasKey('leave_categories', $migrated);
        $this->assertArrayNotHasKey('system_settings', $migrated);
        $this->assertArrayNotHasKey('all_attendances', $migrated);
        $this->assertArrayNotHasKey('all_daily_reports', $migrated);
        $this->assertArrayNotHasKey('swap_personal', $migrated);
        $this->assertArrayNotHasKey('swap_team', $migrated);

        // 3. Tab Perusahaan keys must be view: true
        $this->assertTrue($migrated['company_profile']['view']);
        $this->assertTrue($migrated['company_employees']['view']);
        $this->assertTrue($migrated['company_branches']['view']);
        $this->assertTrue($migrated['company_divisions']['view']);
        $this->assertTrue($migrated['company_positions']['view']);
        $this->assertTrue($migrated['company_shifts']['view']);
        $this->assertTrue($migrated['company_schedules']['view']);
        $this->assertTrue($migrated['company_leave_categories']['view']);
        $this->assertTrue($migrated['company_holidays']['view']);
        $this->assertTrue($migrated['company_announcements']['view']);
        $this->assertTrue($migrated['company_settings']['view']);

        // 4. Home ESS announcements must NOT be true (it was migrated to company_announcements)
        $this->assertFalse($migrated['announcements']['view']);

        // 5. Home ESS reimbursements create action must be preserved
        $this->assertTrue($migrated['reimbursements']['create']);
        $this->assertFalse($migrated['reimbursements']['view']);

        // 6. Security guard on positions
        $this->assertFalse($migrated['company_positions']['create']);
        $this->assertFalse($migrated['company_positions']['edit']);
        $this->assertFalse($migrated['company_positions']['delete']);
    }
}
