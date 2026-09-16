<?php

namespace Tests\Feature;

use App\Filament\Resources\DepartmentResource;
use App\Filament\Resources\ServiceCategoryResource;
use App\Filament\Resources\ServiceRequestResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\LatestRequestsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Models\Department;
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

    protected Department $officeADept;

    protected ServiceCategory $facCategory;

    protected ServiceCategory $itCategory;

    protected User $admin;

    protected User $facManager;

    protected User $itManager;

    protected User $facTech;

    protected User $itTech;

    protected User $facEmployee;

    protected User $itEmployee;

    protected User $officeAEmployee;

    protected User $officeAManager;

    protected ServiceRequest $facRequest;

    protected ServiceRequest $itRequest;

    protected ServiceRequest $officeACrossDeptRequest;

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

        $this->officeADept = Department::create([
            'name' => 'Office A',
            'code' => 'OFFA',
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

        $this->officeAEmployee = User::create([
            'name' => 'Office A Employee',
            'email' => 'officeA.emp@test.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'department_id' => $this->officeADept->id,
            'is_active' => true,
        ]);

        $this->officeAManager = User::create([
            'name' => 'Office A Manager',
            'email' => 'officeA.mgr@test.com',
            'password' => bcrypt('password'),
            'role' => 'service_manager',
            'department_id' => $this->officeADept->id,
            'is_active' => true,
        ]);

        $this->facRequest = ServiceRequest::create([
            'ticket_number' => 'SR-FAC-0001',
            'requester_id' => $this->facEmployee->id,
            'department_id' => $this->facDept->id,
            'service_category_id' => $this->facCategory->id,
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
            'title' => 'IT Network Outage',
            'description' => 'Switch is unresponsive',
            'priority' => ServiceRequest::PRIORITY_URGENT,
            'status' => ServiceRequest::STATUS_SUBMITTED,
        ]);

        $this->officeACrossDeptRequest = ServiceRequest::create([
            'ticket_number' => 'SR-OFFA-0001',
            'requester_id' => $this->officeAEmployee->id,
            'department_id' => $this->facDept->id,
            'service_category_id' => $this->facCategory->id,
            'title' => 'Office A AC Repair Request',
            'description' => 'Office A conference room AC whistling',
            'priority' => ServiceRequest::PRIORITY_HIGH,
            'status' => ServiceRequest::STATUS_SUBMITTED,
        ]);
    }

    public function test_service_request_policy_enforces_department_scope_and_cross_department_visibility(): void
    {
        // Requesters can view their own requests and department requests
        $this->assertTrue(Gate::forUser($this->facEmployee)->allows('view', $this->facRequest));
        $this->assertFalse(Gate::forUser($this->facEmployee)->allows('view', $this->itRequest));

        // Office A employee can view their cross-department request handled by Facilities
        $this->assertTrue(Gate::forUser($this->officeAEmployee)->allows('view', $this->officeACrossDeptRequest));
        // Office A manager can view requests from their office
        $this->assertTrue(Gate::forUser($this->officeAManager)->allows('view', $this->officeACrossDeptRequest));
        // IT technician cannot view Office A Facilities request
        $this->assertFalse(Gate::forUser($this->itTech)->allows('view', $this->officeACrossDeptRequest));

        // Servicing department staff can view and action
        $this->assertTrue(Gate::forUser($this->facTech)->allows('view', $this->officeACrossDeptRequest));
        $this->assertTrue(Gate::forUser($this->facTech)->allows('updateStatus', $this->officeACrossDeptRequest));

        // Neither Office A employee nor Office A manager can update status on the Facilities ticket
        $this->assertFalse(Gate::forUser($this->officeAEmployee)->allows('updateStatus', $this->officeACrossDeptRequest));
        $this->assertFalse(Gate::forUser($this->officeAManager)->allows('updateStatus', $this->officeACrossDeptRequest));

        // Super Admin can view and action any ticket
        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $this->officeACrossDeptRequest));
        $this->assertTrue(Gate::forUser($this->admin)->allows('updateStatus', $this->officeACrossDeptRequest));
    }

    public function test_service_request_auto_populates_servicing_department_from_category(): void
    {
        $autoRouted = ServiceRequest::create([
            'requester_id' => $this->officeAEmployee->id,
            'service_category_id' => $this->itCategory->id,
            'title' => 'Office A Laptop Broken',
            'description' => 'Display is cracked',
            'priority' => ServiceRequest::PRIORITY_MEDIUM,
        ]);

        $this->assertEquals($this->itDept->id, $autoRouted->department_id);
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

    public function test_eloquent_global_scope_filters_service_requests_by_department_and_office(): void
    {
        // Facilities employee sees facilities-serviced requests (facRequest + officeACrossDeptRequest)
        $this->actingAs($this->facEmployee);
        $facList = ServiceRequest::all();
        $this->assertCount(2, $facList);
        $this->assertTrue($facList->contains('id', $this->facRequest->id));
        $this->assertTrue($facList->contains('id', $this->officeACrossDeptRequest->id));

        // IT employee sees only IT requests
        $this->actingAs($this->itEmployee);
        $itList = ServiceRequest::all();
        $this->assertCount(1, $itList);
        $this->assertEquals($this->itRequest->id, $itList->first()->id);

        // Office A employee sees requests from Office A (officeACrossDeptRequest)
        $this->actingAs($this->officeAEmployee);
        $officeAList = ServiceRequest::all();
        $this->assertCount(1, $officeAList);
        $this->assertEquals($this->officeACrossDeptRequest->id, $officeAList->first()->id);

        // Admin sees all 3 requests
        $this->actingAs($this->admin);
        $adminList = ServiceRequest::all();
        $this->assertCount(3, $adminList);
    }

    public function test_filament_resource_queries_filter_by_department_for_non_admins(): void
    {
        $this->actingAs($this->facManager);

        $requests = ServiceRequestResource::getEloquentQuery()->pluck('id');
        $this->assertContains($this->facRequest->id, $requests);
        $this->assertContains($this->officeACrossDeptRequest->id, $requests);
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
        $this->assertContains($this->officeACrossDeptRequest->id, $allRequests);
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
            ->assertSee('Office A AC Repair Request')
            ->assertDontSee('IT Network Outage');

        $this->actingAs($this->itManager);

        Livewire::actingAs($this->itManager)
            ->test(LatestRequestsWidget::class)
            ->assertSee('IT Network Outage')
            ->assertDontSee('Facilities AC Issue');
    }
}
