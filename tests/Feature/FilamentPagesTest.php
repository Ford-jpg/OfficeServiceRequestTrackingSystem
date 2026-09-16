<?php

namespace Tests\Feature;

use App\Filament\Widgets\StatsOverviewWidget;
use App\Models\Department;
use App\Models\Location;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentPagesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected ServiceRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::create([
            'name' => 'Facilities',
            'code' => 'FAC',
            'is_active' => true,
        ]);

        $location = Location::create([
            'building' => 'Main Tower',
            'floor' => '2nd Floor',
            'room_or_area' => 'Room 201',
            'is_active' => true,
        ]);

        $category = ServiceCategory::create([
            'department_id' => $department->id,
            'name' => 'Air Conditioning',
            'sla_hours_default' => 24,
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.test@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->request = ServiceRequest::create([
            'requester_id' => $this->admin->id,
            'department_id' => $department->id,
            'service_category_id' => $category->id,
            'location_id' => $location->id,
            'title' => 'Sample AC Repair',
            'description' => 'Test description for AC issue',
            'priority' => ServiceRequest::PRIORITY_HIGH,
            'status' => ServiceRequest::STATUS_SUBMITTED,
        ]);
    }

    public function test_admin_can_access_filament_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertSuccessful();
        $response->assertSee('Office Service Tracker');

        Livewire::actingAs($this->admin)
            ->test(StatsOverviewWidget::class)
            ->assertSee('Submitted')
            ->assertSee('Under Review')
            ->assertSee('In Progress')
            ->assertSee('Completed')
            ->assertSee('Rejected');
    }

    public function test_admin_can_access_service_requests_table(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/service-requests');

        $response->assertSuccessful();
        $response->assertSee('Service Requests');
        $response->assertSee('Sample AC Repair');
        $response->assertSee('All Requests');
        $response->assertSee('Submitted');
    }

    public function test_admin_can_access_service_request_view_page_and_see_audit_trail(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/service-requests/{$this->request->id}");

        $response->assertSuccessful();
        $response->assertSee('Request Overview');
        $response->assertSee('Audit Trail & Status History');
        $response->assertSee('Sample AC Repair');
        $response->assertSee('Request submitted by requester');
    }

    public function test_admin_can_access_department_and_user_management(): void
    {
        $this->actingAs($this->admin)->get('/admin/departments')->assertSuccessful();
        $this->actingAs($this->admin)->get('/admin/locations')->assertSuccessful();
        $this->actingAs($this->admin)->get('/admin/service-categories')->assertSuccessful();
        $this->actingAs($this->admin)->get('/admin/users')->assertSuccessful();
    }
}
