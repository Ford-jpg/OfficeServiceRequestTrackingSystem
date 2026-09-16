<?php

namespace Tests\Feature;

use App\Filament\Resources\DepartmentResource;
use App\Filament\Resources\ServiceCategoryResource;
use App\Filament\Resources\ServiceRequestResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\LatestRequestsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Models\Department;
use App\Models\Location;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class DepartmentScopeAndRolePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected Department $facDept;

    protected Department $itDept;

    protected Location $location;

    protected ServiceCategory $facCategory;

    protected ServiceCategory $itCategory;

    protected User $admin;

    protected User $facManager;

    protected User $itManager;

    protected User $facTech;

    protected User $itTech;

    protected User $facEmployee;

    protected User $itEmployee;

    protected ServiceRequest $facRequest;

    protected ServiceRequest $itRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facDept = Department::create([
            'name' => 'Facilities',
            'code' => 'FAC',
            'is_active' => true,
        ]);

        $this->itDept = Department::create([
            'name' => 'Information Technology',
            'code' => 'IT',
            'is_active' => true,
        ]);

        $this->location = Location::create([
            'building' => 'Main Tower',
            'floor' => '1st Floor',
            'room_or_area' => 'Room 101',
            'is_active' => true,
        ]);

        $this->facCategory = ServiceCategory::create([
            'department_id' => $this->facDept->id,
            'name' => 'HVAC Maintenance',
            'sla_hours_default' => 24,
            'is_active' => true,
        ]);

        $this->itCategory = ServiceCategory::create([
            'department_id' => $this->itDept->id,
            'name' => 'Network Hardware',
            'sla_hours_default' => 12,
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->facManager = User::create([
            'name' => 'Facilities Manager',
            'email' => 'fac.mgr@test.com',
            'password' => bcrypt('password'),
            'role' => 'service_manager',
            'department_id' => $this->facDept->id,
            'is_active' => true,
        ]);

        $this->itManager = User::create([
            'name' => 'IT Manager',
            'email' => 'it.mgr@test.com',
            'password' => bcrypt('password'),
            'role' => 'service_manager',
            'department_id' => $this->itDept->id,
            'is_active' => true,
        ]);

        $this->facTech = User::create([
            'name' => 'Facilities Technician',
            'email' => 'fac.tech@test.com',
            'password' => bcrypt('password'),
            'role' => 'technician',
            'department_id' => $this->facDept->id,
            'is_active' => true,
        ]);

        $this->itTech = User::create([
            'name' => 'IT Technician',
            'email' => 'it.tech@test.com',
            'password' => bcrypt('password'),
            'role' => 'technician',
            'department_id' => $this->itDept->id,
            'is_active' => true,
        ]);

        $this->facEmployee = User::create([
            'name' => 'Facilities Employee',
            'email' => 'fac.emp@test.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'department_id' => $this->facDept->id,
            'is_active' => true,
        ]);

        $this->itEmployee = User::create([
            'name' => 'IT Employee',
            'email' => 'it.emp@test.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'department_id' => $this->itDept->id,
            'is_active' => true,
        ]);

        $this->facRequest = ServiceRequest::create([
            'ticket_number' => 'SR-FAC-0001',
            'requester_id' => $this->facEmployee->id,
            'department_id' => $this->facDept->id,
            'service_category_id' => $this->facCategory->id,
            'location_id' => $this->location->id,
            'title' => 'Facilities AC Issue',
            'description' => 'AC stopped cooling',
            'priority' => ServiceRequest::PRIORITY_HIGH,
            'status' => ServiceRequest::STATUS_SUBMITTED,
        ]);

        $this->itRequest = ServiceRequest::create([
            'ticket_number' => 'SR-IT-0001',
            'requester_id' => $this->itEmployee->id,
            'department_id' => $this->itDept->id,
            'service_category_id' => $this->itCategory->id,
            'location_id' => $this->location->id,
            'title' => 'IT Network Outage',
            'description' => 'Switch is unresponsive',
            'priority' => ServiceRequest::PRIORITY_URGENT,
            'status' => ServiceRequest::STATUS_SUBMITTED,
        ]);
    }

    public function test_service_request_policy_enforces_department_scope(): void
    {
        $this->assertTrue(Gate::forUser($this->facEmployee)->allows('view', $this->facRequest));
        $this->assertFalse(Gate::forUser($this->facEmployee)->allows('view', $this->itRequest));

        $this->assertTrue(Gate::forUser($this->itEmployee)->allows('view', $this->itRequest));
        $this->assertFalse(Gate::forUser($this->itEmployee)->allows('view', $this->facRequest));

        $this->assertTrue(Gate::forUser($this->facTech)->allows('updateStatus', $this->facRequest));
        $this->assertFalse(Gate::forUser($this->facTech)->allows('updateStatus', $this->itRequest));

        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $this->facRequest));
        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $this->itRequest));
        $this->assertTrue(Gate::forUser($this->admin)->allows('updateStatus', $this->facRequest));
        $this->assertTrue(Gate::forUser($this->admin)->allows('updateStatus', $this->itRequest));
    }

    public function test_department_policy_enforces_department_scope(): void
    {
        $this->assertTrue(Gate::forUser($this->facManager)->allows('view', $this->facDept));
        $this->assertFalse(Gate::forUser($this->facManager)->allows('view', $this->itDept));

        $this->assertFalse(Gate::forUser($this->facManager)->allows('create', Department::class));
        $this->assertFalse(Gate::forUser($this->facManager)->allows('delete', $this->facDept));

        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $this->facDept));
        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $this->itDept));
        $this->assertTrue(Gate::forUser($this->admin)->allows('create', Department::class));
        $this->assertTrue(Gate::forUser($this->admin)->allows('delete', $this->facDept));
    }

    public function test_service_category_policy_enforces_department_scope(): void
    {
        $this->assertTrue(Gate::forUser($this->facTech)->allows('view', $this->facCategory));
        $this->assertFalse(Gate::forUser($this->facTech)->allows('view', $this->itCategory));

        $this->assertTrue(Gate::forUser($this->facManager)->allows('update', $this->facCategory));
        $this->assertFalse(Gate::forUser($this->facManager)->allows('update', $this->itCategory));

        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $this->facCategory));
        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $this->itCategory));
        $this->assertTrue(Gate::forUser($this->admin)->allows('update', $this->facCategory));
        $this->assertTrue(Gate::forUser($this->admin)->allows('update', $this->itCategory));
    }

    public function test_user_policy_enforces_department_scope(): void
    {
        $this->assertTrue(Gate::forUser($this->facManager)->allows('view', $this->facTech));
        $this->assertTrue(Gate::forUser($this->facManager)->allows('view', $this->facEmployee));
        $this->assertFalse(Gate::forUser($this->facManager)->allows('view', $this->itTech));
        $this->assertFalse(Gate::forUser($this->facManager)->allows('view', $this->itEmployee));

        $this->assertTrue(Gate::forUser($this->facEmployee)->allows('view', $this->facEmployee));
        $this->assertFalse(Gate::forUser($this->facEmployee)->allows('view', $this->facTech));

        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $this->facTech));
        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $this->itTech));
    }

    public function test_eloquent_global_scope_filters_service_requests_by_department(): void
    {
        $this->actingAs($this->facEmployee);
        $facList = ServiceRequest::all();
        $this->assertCount(1, $facList);
        $this->assertEquals($this->facRequest->id, $facList->first()->id);

        $this->actingAs($this->itEmployee);
        $itList = ServiceRequest::all();
        $this->assertCount(1, $itList);
        $this->assertEquals($this->itRequest->id, $itList->first()->id);

        $this->actingAs($this->admin);
        $adminList = ServiceRequest::all();
        $this->assertCount(2, $adminList);
    }

    public function test_filament_resource_queries_filter_by_department_for_non_admins(): void
    {
        $this->actingAs($this->facManager);

        $requests = ServiceRequestResource::getEloquentQuery()->pluck('id');
        $this->assertContains($this->facRequest->id, $requests);
        $this->assertNotContains($this->itRequest->id, $requests);

        $departments = DepartmentResource::getEloquentQuery()->pluck('id');
        $this->assertContains($this->facDept->id, $departments);
        $this->assertNotContains($this->itDept->id, $departments);

        $categories = ServiceCategoryResource::getEloquentQuery()->pluck('id');
        $this->assertContains($this->facCategory->id, $categories);
        $this->assertNotContains($this->itCategory->id, $categories);

        $users = UserResource::getEloquentQuery()->pluck('id');
        $this->assertContains($this->facTech->id, $users);
        $this->assertNotContains($this->itTech->id, $users);

        $this->actingAs($this->admin);
        $allRequests = ServiceRequestResource::getEloquentQuery()->pluck('id');
        $this->assertContains($this->facRequest->id, $allRequests);
        $this->assertContains($this->itRequest->id, $allRequests);
    }

    public function test_dashboard_widgets_reflect_department_scope(): void
    {
        $this->actingAs($this->facManager);

        Livewire::actingAs($this->facManager)
            ->test(StatsOverviewWidget::class)
            ->assertSee('Submitted');

        Livewire::actingAs($this->facManager)
            ->test(LatestRequestsWidget::class)
            ->assertSee('Facilities AC Issue')
            ->assertDontSee('IT Network Outage');

        $this->actingAs($this->itManager);

        Livewire::actingAs($this->itManager)
            ->test(LatestRequestsWidget::class)
            ->assertSee('IT Network Outage')
            ->assertDontSee('Facilities AC Issue');
    }
}
