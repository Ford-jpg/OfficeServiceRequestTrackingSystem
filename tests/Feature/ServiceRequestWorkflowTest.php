<?php

namespace Tests\Feature;

use App\Filament\Widgets\StatsOverviewWidget;
use App\Models\Department;
use App\Models\Location;
use App\Models\RequestAuditLog;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ServiceRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected User $technician;
    protected User $employee;
    protected Department $department;
    protected Location $location;
    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create([
            'name' => 'Facilities',
            'code' => 'FAC',
            'is_active' => true,
        ]);

        $this->location = Location::create([
            'building' => 'Main Tower',
            'floor' => '2nd Floor',
            'room_or_area' => 'Room 201',
            'is_active' => true,
        ]);

        $this->category = ServiceCategory::create([
            'department_id' => $this->department->id,
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

        $this->manager = User::create([
            'name' => 'Service Manager',
            'email' => 'manager.test@example.com',
            'password' => bcrypt('password'),
            'role' => 'service_manager',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        $this->technician = User::create([
            'name' => 'Technician Staff',
            'email' => 'tech.test@example.com',
            'password' => bcrypt('password'),
            'role' => 'technician',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        $this->employee = User::create([
            'name' => 'Regular Employee',
            'email' => 'employee.test@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'is_active' => true,
        ]);
    }

    private function createSampleRequest(): ServiceRequest
    {
        return ServiceRequest::create([
            'requester_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'service_category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'title' => 'Test Service Request',
            'description' => 'Detailed issue description for testing',
            'priority' => ServiceRequest::PRIORITY_HIGH,
        ]);
    }

    /**
     * Requirement 3: Default status is Submitted upon creation.
     * Requirement 6: Audit trail logs creation.
     */
    public function test_request_initializes_with_submitted_status_and_creation_audit_trail(): void
    {
        $request = $this->createSampleRequest();

        $this->assertEquals(ServiceRequest::STATUS_SUBMITTED, $request->status);
        $this->assertNotEmpty($request->ticket_number);
        $this->assertStringStartsWith('SR-', $request->ticket_number);

        $auditLog = RequestAuditLog::where('service_request_id', $request->id)->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals('created', $auditLog->action);
        $this->assertEquals(ServiceRequest::STATUS_SUBMITTED, $auditLog->to_status);
    }

    /**
     * Requirement 3: Workflow: Submitted -> Under Review -> In Progress -> Completed.
     * Requirement 6: Audit trail recorded at each step.
     */
    public function test_workflow_progression_from_submitted_to_completed(): void
    {
        $request = $this->createSampleRequest();

        // 1. Submitted -> Under Review (Manager reviews)
        $request->transitionTo(ServiceRequest::STATUS_UNDER_REVIEW, $this->manager, 'Acknowledged by facilities manager.');
        $request->refresh();
        $this->assertEquals(ServiceRequest::STATUS_UNDER_REVIEW, $request->status);

        // 2. Under Review -> In Progress (Technician starts work)
        $request->transitionTo(ServiceRequest::STATUS_IN_PROGRESS, $this->technician, 'Technician dispatched to Room 201.');
        $request->refresh();
        $this->assertEquals(ServiceRequest::STATUS_IN_PROGRESS, $request->status);

        // 3. In Progress -> Completed (Technician completes with resolution notes)
        $request->transitionTo(ServiceRequest::STATUS_COMPLETED, $this->technician, 'Replaced filter and tested airflow.');
        $request->refresh();
        $this->assertEquals(ServiceRequest::STATUS_COMPLETED, $request->status);
        $this->assertNotNull($request->resolved_at);
        $this->assertEquals('Replaced filter and tested airflow.', $request->resolution_notes);

        // Verify full audit trail has 4 entries (creation + 3 transitions)
        $logs = RequestAuditLog::where('service_request_id', $request->id)->orderBy('id')->get();
        $this->assertCount(4, $logs);

        // Check transition 1
        $this->assertEquals(ServiceRequest::STATUS_SUBMITTED, $logs[1]->from_status);
        $this->assertEquals(ServiceRequest::STATUS_UNDER_REVIEW, $logs[1]->to_status);
        $this->assertEquals($this->manager->id, $logs[1]->user_id);

        // Check transition 2
        $this->assertEquals(ServiceRequest::STATUS_UNDER_REVIEW, $logs[2]->from_status);
        $this->assertEquals(ServiceRequest::STATUS_IN_PROGRESS, $logs[2]->to_status);
        $this->assertEquals($this->technician->id, $logs[2]->user_id);

        // Check transition 3
        $this->assertEquals(ServiceRequest::STATUS_IN_PROGRESS, $logs[3]->from_status);
        $this->assertEquals(ServiceRequest::STATUS_COMPLETED, $logs[3]->to_status);
        $this->assertEquals($this->technician->id, $logs[3]->user_id);
    }

    /**
     * Requirement 3: Workflow rejection: Submitted -> Rejected.
     * Requirement 7: Validation requires rejection reason.
     */
    public function test_rejection_workflow_requires_reason(): void
    {
        $request = $this->createSampleRequest();

        // Attempting rejection without notes must fail validation
        $this->expectException(ValidationException::class);
        $request->transitionTo(ServiceRequest::STATUS_REJECTED, $this->manager, '');
    }

    public function test_rejection_workflow_with_reason_succeeds_and_logs_audit(): void
    {
        $request = $this->createSampleRequest();

        $request->transitionTo(ServiceRequest::STATUS_REJECTED, $this->manager, 'Duplicate request filed earlier.');
        $request->refresh();

        $this->assertEquals(ServiceRequest::STATUS_REJECTED, $request->status);
        $this->assertEquals('Duplicate request filed earlier.', $request->rejection_reason);

        $latestLog = RequestAuditLog::where('service_request_id', $request->id)->latest('id')->first();
        $this->assertEquals(ServiceRequest::STATUS_SUBMITTED, $latestLog->from_status);
        $this->assertEquals(ServiceRequest::STATUS_REJECTED, $latestLog->to_status);
        $this->assertEquals($this->manager->id, $latestLog->user_id);
        $this->assertEquals('Duplicate request filed earlier.', $latestLog->notes);
    }

    /**
     * Requirement 7: Completion requires resolution notes.
     */
    public function test_completion_requires_resolution_notes(): void
    {
        $request = $this->createSampleRequest();
        $request->transitionTo(ServiceRequest::STATUS_UNDER_REVIEW, $this->manager, 'Reviewing');
        $request->transitionTo(ServiceRequest::STATUS_IN_PROGRESS, $this->technician, 'Working');

        // Attempting to complete without notes must fail
        $this->expectException(ValidationException::class);
        $request->transitionTo(ServiceRequest::STATUS_COMPLETED, $this->technician, '   ');
    }

    /**
     * Requirement 3: Enforce sequential workflow (cannot skip states).
     */
    public function test_invalid_workflow_transition_is_blocked(): void
    {
        $request = $this->createSampleRequest(); // Currently Submitted

        // Technician cannot jump directly from Submitted to Completed
        $this->expectException(ValidationException::class);
        $request->transitionTo(ServiceRequest::STATUS_COMPLETED, $this->technician, 'Direct complete attempt');
    }

    /**
     * Requirement 4: Restrict status updates to authorized personnel.
     * Regular employees must NOT be able to change request status.
     */
    public function test_unauthorized_employee_cannot_update_status(): void
    {
        $request = $this->createSampleRequest();

        $this->expectException(AuthorizationException::class);
        $request->transitionTo(ServiceRequest::STATUS_UNDER_REVIEW, $this->employee, 'Employee trying to approve own request');
    }

    /**
     * Requirement 5: Dashboard statistics widget counts.
     */
    public function test_dashboard_status_counts(): void
    {
        // 1. Submitted
        $r1 = $this->createSampleRequest();

        // 2. Under Review
        $r2 = $this->createSampleRequest();
        $r2->transitionTo(ServiceRequest::STATUS_UNDER_REVIEW, $this->manager, 'Reviewing');

        // 3. In Progress
        $r3 = $this->createSampleRequest();
        $r3->transitionTo(ServiceRequest::STATUS_UNDER_REVIEW, $this->manager, 'Reviewing');
        $r3->transitionTo(ServiceRequest::STATUS_IN_PROGRESS, $this->technician, 'Starting work');

        // 4. Completed
        $r4 = $this->createSampleRequest();
        $r4->transitionTo(ServiceRequest::STATUS_UNDER_REVIEW, $this->manager, 'Reviewing');
        $r4->transitionTo(ServiceRequest::STATUS_IN_PROGRESS, $this->technician, 'Working');
        $r4->transitionTo(ServiceRequest::STATUS_COMPLETED, $this->technician, 'Fixed issue');

        // 5. Rejected
        $r5 = $this->createSampleRequest();
        $r5->transitionTo(ServiceRequest::STATUS_REJECTED, $this->manager, 'Out of scope');

        $this->assertEquals(1, ServiceRequest::where('status', ServiceRequest::STATUS_SUBMITTED)->count());
        $this->assertEquals(1, ServiceRequest::where('status', ServiceRequest::STATUS_UNDER_REVIEW)->count());
        $this->assertEquals(1, ServiceRequest::where('status', ServiceRequest::STATUS_IN_PROGRESS)->count());
        $this->assertEquals(1, ServiceRequest::where('status', ServiceRequest::STATUS_COMPLETED)->count());
        $this->assertEquals(1, ServiceRequest::where('status', ServiceRequest::STATUS_REJECTED)->count());
        $this->assertEquals(5, ServiceRequest::count());
    }
}
