<?php

namespace Tests\Unit;

use App\Models\Position;
use PHPUnit\Framework\TestCase;

class PositionLegacyMigrationTest extends TestCase
{
    public function test_legacy_screenshot_permissions_are_migrated_to_mobile_and_web_structures()
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

        // Result must have both 'mobile' and 'web'
        $this->assertArrayHasKey('mobile', $migrated);
        $this->assertArrayHasKey('web', $migrated);

        $mobile = $migrated['mobile'];
        $web = $migrated['web'];

        // 1. MOBILE: Must have exactly 25 keys
        $this->assertCount(25, $mobile);
        $this->assertSame(Position::CANONICAL_MOBILE_KEYS, array_keys($mobile));

        // 2. WEB: Must have exactly 21 clean keys
        $this->assertCount(21, $web);
        $this->assertSame(Position::CANONICAL_WEB_KEYS, array_keys($web));

        // 3. Mobile Tab Perusahaan keys must be view: true
        $this->assertTrue($mobile['company_profile']['view']);
        $this->assertTrue($mobile['company_employees']['view']);
        $this->assertTrue($mobile['company_branches']['view']);
        $this->assertTrue($mobile['company_divisions']['view']);
        $this->assertTrue($mobile['company_positions']['view']);
        $this->assertTrue($mobile['company_shifts']['view']);
        $this->assertTrue($mobile['company_schedules']['view']);
        $this->assertTrue($mobile['company_leave_categories']['view']);
        $this->assertTrue($mobile['company_holidays']['view']);
        $this->assertTrue($mobile['company_announcements']['view']);
        $this->assertTrue($mobile['company_settings']['view']);

        // 4. Web Clean Keys must be view: true
        $this->assertTrue($web['company_profile']['view']);
        $this->assertTrue($web['employees']['view']);
        $this->assertTrue($web['branches']['view']);
        $this->assertTrue($web['divisions']['view']);
        $this->assertTrue($web['positions']['view']);
        $this->assertTrue($web['shifts']['view']);
        $this->assertTrue($web['schedules']['view']);
        $this->assertTrue($web['leave_categories']['view']);
        $this->assertTrue($web['holidays']['view']);
        $this->assertTrue($web['announcements']['view']);
        $this->assertTrue($web['settings']['view']);

        // 5. Security guards on Mobile & Web
        $this->assertFalse($mobile['company_positions']['create']);
        $this->assertFalse($mobile['company_positions']['edit']);
        $this->assertFalse($mobile['company_positions']['delete']);
        $this->assertFalse($mobile['company_payrolls']['edit']); // Penggajian edit = false
        $this->assertFalse($mobile['leave_balances']['delete']);

        $this->assertFalse($web['positions']['create']);
        $this->assertFalse($web['positions']['edit']);
        $this->assertFalse($web['positions']['delete']);
        $this->assertFalse($web['payrolls']['edit']); // Penggajian edit = false
        $this->assertFalse($web['leave_balances']['create']);
        $this->assertFalse($web['leave_balances']['delete']);
    }
}
