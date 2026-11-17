<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Employee;
use App\Models\LeaveCategory;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebRbacTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerUser;
    protected Company $company;
    protected Branch $branch;
    protected Division $division;
    protected Position $position;
    protected User $employeeUser;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Owner & Company setup
        $this->ownerUser = User::factory()->create(['role' => 'owner']);
        $this->company = Company::create([
            'user_id' => $this->ownerUser->id,
            'name_company' => 'PT Frans Maju Bersama',
            'email' => 'company@example.com',
            'phone' => '02112345678',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'business_type' => 'Teknologi Informasi',
        ]);
        $this->ownerUser->update(['company_id' => $this->company->id]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Kantor Pusat Jakarta',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'radius' => 100,
        ]);

        $this->division = Division::create([
            'company_id' => $this->company->id,
            'name' => 'Human Resources',
        ]);

        // 2. Position with isolated Mobile & Web permissions
        // Web: leave_balances is OFF, employees is ON
        // Mobile: leave_balances is ON
        $permissions = [
            'mobile' => [
                'leave_balances' => ['view' => true, 'create' => false, 'edit' => true, 'delete' => false],
            ],
            'web' => [
                'leave_balances' => ['view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                'employees' => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            ],
        ];

        $this->position = Position::create([
            'company_id' => $this->company->id,
            'name' => 'HR Staff',
            'permissions' => $permissions,
        ]);
        $this->position->branches()->sync([$this->branch->id]);
        $this->position->divisions()->sync([$this->division->id]);

        // 3. Employee User
        $this->employeeUser = User::factory()->create(['role' => 'employee']);
        $this->employee = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $this->employeeUser->id,
            'branch_id' => $this->branch->id,
            'division_id' => $this->division->id,
            'position_id' => $this->position->id,
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'is_active' => true,
        ]);
    }

    public function test_mobile_and_web_permissions_are_completely_isolated()
    {
        // Mobile platform check: leave_balances is true
        $this->assertTrue(
            $this->employeeUser->hasPermission('leave_balances', 'view', 'mobile'),
            'Mobile must have leave_balances view permission.'
        );

        // Web platform check: leave_balances is false
        $this->assertFalse(
            $this->employeeUser->hasPermission('leave_balances', 'view', 'web'),
            'Web must NOT have leave_balances view permission when toggled off.'
        );

        // Web platform check: employees is true
        $this->assertTrue(
            $this->employeeUser->hasPermission('employees', 'view', 'web'),
            'Web must have employees view permission.'
        );

        // Both getCompany() and ->company attribute should resolve the employee's company
        $this->assertNotNull($this->employeeUser->getCompany());
        $this->assertEquals($this->company->id, $this->employeeUser->getCompany()->id);
        $this->assertNotNull($this->employeeUser->company);
        $this->assertEquals($this->company->id, $this->employeeUser->company->id);

        // Mobile effective permissions dictionary has 25 keys
        $mobileEffective = $this->employeeUser->getEffectivePermissions('mobile');
        $this->assertCount(25, $mobileEffective);
        $this->assertTrue($mobileEffective['leave_balances']['view']);

        // Web effective permissions dictionary has 21 clean keys
        $webEffective = $this->employeeUser->getEffectivePermissions('web');
        $this->assertCount(21, $webEffective);
        $this->assertFalse($webEffective['leave_balances']['view']);
        $this->assertTrue($webEffective['employees']['view']);
    }

    public function test_web_leave_balances_route_is_denied_when_turned_off_in_web()
    {
        $this->actingAs($this->employeeUser);

        // 1. Direct browser request redirects to dashboard with error flash
        $response = $this->get(route('owner.management.leaves.balance.index'));
        $response->assertRedirect(route('owner.dashboard'));
        $response->assertSessionHas('error');

        // 2. JSON / API client request returns 403 Forbidden
        $jsonResponse = $this->getJson(route('owner.management.leaves.balance.index'));
        $jsonResponse->assertStatus(403);
    }

    public function test_web_leave_balances_route_succeeds_when_turned_on_in_web()
    {
        // Turn ON leave_balances in Web
        $currentPerms = $this->position->permissions;
        $currentPerms['web']['leave_balances'] = ['view' => true, 'create' => false, 'edit' => true, 'delete' => false];
        $this->position->update(['permissions' => $currentPerms]);
        $this->employeeUser->unsetRelation('employee');

        // Create a leave category so balance query works
        LeaveCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Cuti Tahunan',
            'default_quota' => 12,
        ]);

        $this->actingAs($this->employeeUser);

        // Employee should now access web leave balance index without 403
        $response = $this->get(route('owner.management.leaves.balance.index'));
        $response->assertStatus(200);
    }
}
