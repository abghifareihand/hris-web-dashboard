<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Employee;
use App\Models\LeaveCategory;
use App\Models\LeaveRequest;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiRbacTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branchA;
    protected Branch $branchB;
    protected Division $divisionA;
    protected Position $positionStaff;
    protected Position $positionManager;
    protected User $ownerUser;
    protected User $staffUser;
    protected Employee $staffEmp;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Company & Owner
        $this->ownerUser = User::factory()->create([
            'role' => 'owner',
        ]);
        $this->company = Company::create([
            'user_id' => $this->ownerUser->id,
            'name_company' => 'PT Test RBAC',
            'email' => 'company@example.com',
            'phone' => '02112345678',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'business_type' => 'Teknologi Informasi',
        ]);
        $this->ownerUser->update(['company_id' => $this->company->id]);

        // 2. Setup Branches & Division
        $this->branchA = Branch::create(['company_id' => $this->company->id, 'name' => 'Cabang Jakarta']);
        $this->branchB = Branch::create(['company_id' => $this->company->id, 'name' => 'Cabang Bandung']);
        $this->divisionA = Division::create(['company_id' => $this->company->id, 'name' => 'Divisi HR']);

        // 3. Setup Position Manager with leaves view + edit only
        $this->positionManager = Position::create([
            'company_id' => $this->company->id,
            'name' => 'HR Manager',
            'permissions' => [
                'leaves' => ['view' => true, 'create' => false, 'edit' => true, 'delete' => false],
                'employee_data' => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            ],
        ]);
        $this->positionManager->branches()->attach($this->branchA->id);
        $this->positionManager->divisions()->attach($this->divisionA->id);

        // 4. Setup Manager User & Employee
        $managerUser = User::factory()->create(['role' => 'employee']);
        $this->staffEmp = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $managerUser->id,
            'branch_id' => $this->branchA->id,
            'division_id' => $this->divisionA->id,
            'position_id' => $this->positionManager->id,
            'name' => 'Manager HR',
            'email' => 'manager.hr@example.com',
            'phone' => '081234567890',
            'is_active' => true,
        ]);
        $this->staffUser = $managerUser;
    }

    public function test_unauthenticated_request_is_rejected()
    {
        $response = $this->getJson('/api/owner/payrolls');
        $response->assertStatus(401);
    }

    public function test_employee_without_permission_is_forbidden()
    {
        Sanctum::actingAs($this->staffUser);

        // Staff does NOT have payrolls permission
        $response = $this->getJson('/api/owner/payrolls');
        $response->assertStatus(403);
        $response->assertJson([
            'status' => false,
            'message' => 'Anda tidak memiliki izin melihat data untuk menu ini.',
        ]);
    }

    public function test_employee_with_permission_can_access()
    {
        Sanctum::actingAs($this->staffUser);

        // Staff HAS leaves view permission
        $response = $this->getJson('/api/owner/leaves');
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }

    public function test_employee_cannot_perform_unpermitted_action()
    {
        Sanctum::actingAs($this->staffUser);

        // Staff does NOT have leaves delete permission
        $response = $this->deleteJson('/api/owner/leaves/999');
        $response->assertStatus(403);
        $response->assertJson([
            'status' => false,
            'message' => 'Anda tidak memiliki izin menghapus data untuk menu ini.',
        ]);
    }

    public function test_post_on_resource_id_is_detected_as_edit_not_create()
    {
        Sanctum::actingAs($this->staffUser); // Staff only has employee_data: view=true, create=false, edit=false

        // POST /api/owner/employees/{id} is an UPDATE/EDIT action, not create
        $response = $this->postJson("/api/owner/employees/{$this->staffEmp->id}", [
            'name' => 'Updated Name',
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'status' => false,
            'message' => 'Anda tidak memiliki izin mengubah data untuk menu ini.',
        ]);
    }

    public function test_branch_data_scoping_on_mobile_api()
    {
        // Setup employee in Branch B (Bandung)
        $empBUser = User::factory()->create(['role' => 'employee']);
        $empB = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $empBUser->id,
            'branch_id' => $this->branchB->id,
            'division_id' => $this->divisionA->id,
            'name' => 'Karyawan Bandung',
            'email' => 'bandung@example.com',
            'phone' => '08987654321',
            'is_active' => true,
        ]);

        // Setup employee in Branch A (Jakarta)
        $empAUser = User::factory()->create(['role' => 'employee']);
        $empA = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $empAUser->id,
            'branch_id' => $this->branchA->id,
            'division_id' => $this->divisionA->id,
            'name' => 'Karyawan Jakarta',
            'email' => 'jakarta@example.com',
            'phone' => '08987654322',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->staffUser); // Manager is only allowed Branch A

        // Call employees list API
        $response = $this->getJson('/api/owner/employees');
        $response->assertStatus(200);
        $response->assertStatus(200);

        $employeeNames = collect($response->json('data'))->pluck('name')->toArray();

        // Must contain Karyawan Jakarta and Manager HR
        $this->assertContains('Karyawan Jakarta', $employeeNames);
        // Must NOT contain Karyawan Bandung (filtered out by Branch Scoping)
        $this->assertNotContains('Karyawan Bandung', $employeeNames);

        // Direct show on Bandung employee must return 403 Forbidden
        $showResponse = $this->getJson("/api/owner/employees/{$empB->id}");
        $showResponse->assertStatus(403);
        $showResponse->assertJson([
            'status' => false,
            'message' => 'Anda tidak memiliki akses ke cabang/divisi karyawan ini.',
        ]);
    }

    public function test_auth_me_returns_permissions_and_scoping()
    {
        Sanctum::actingAs($this->staffUser);

        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'user' => [
                'id',
                'role',
                'permissions',
                'allowed_branches',
                'allowed_divisions',
            ]
        ]);

        $userData = $response->json('user');
        $this->assertEquals([$this->branchA->id], $userData['allowed_branches']);
        $this->assertEquals([$this->divisionA->id], $userData['allowed_divisions']);
        $this->assertTrue($userData['permissions']['leaves']['view']);
        $this->assertTrue($userData['permissions']['leaves']['edit']);
        $this->assertFalse($userData['permissions']['leaves']['delete']);
    }

    public function test_leave_approval_respects_branch_scoping()
    {
        // Setup employee in Branch B (Bandung)
        $empBUser = User::factory()->create(['role' => 'employee']);
        $empB = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $empBUser->id,
            'branch_id' => $this->branchB->id,
            'division_id' => $this->divisionA->id,
            'name' => 'Karyawan Bandung',
            'email' => 'bandung2@example.com',
            'phone' => '08987654323',
            'is_active' => true,
        ]);

        $category = LeaveCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Cuti Tahunan',
            'default_days' => 12,
            'is_paid' => true,
        ]);

        $leaveB = LeaveRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $empB->id,
            'leave_category_id' => $category->id,
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(3),
            'days_count' => 2,
            'reason' => 'Urusan keluarga',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->staffUser); // Manager HR only has Branch A

        // Attempting to approve leave of employee in Branch B must return 403
        $response = $this->postJson("/api/owner/leaves/{$leaveB->id}/approve");
        $response->assertStatus(403);
        $response->assertJson([
            'status' => false,
            'message' => 'Anda tidak memiliki hak akses untuk karyawan ini.',
        ]);
    }
}
