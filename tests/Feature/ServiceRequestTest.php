<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employee;

    private Department $department;

    private ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create([
            'name' => 'IT Department',
            'code' => 'IT',
        ]);

        $this->category = ServiceCategory::create([
            'department_id' => $this->department->id,
            'name' => 'Hardware Repair',
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->employee = User::create([
            'name' => 'Employee User',
            'email' => 'employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
        ]);
    }

    public function test_can_register_service_request_with_required_fields(): void
    {
        $request = ServiceRequest::create([
            'user_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'service_category_id' => $this->category->id,
            'description' => 'Laptop screen is flickering continuously.',
            'priority' => ServiceRequest::PRIORITY_HIGH,
        ]);

        $this->assertNotNull($request->reference_number);
        $this->assertStringStartsWith('REF-', $request->reference_number);
        $this->assertEquals(ServiceRequest::STATUS_SUBMITTED, $request->status);
        $this->assertEquals('Laptop screen is flickering continuously.', $request->description);

        $this->assertDatabaseHas('request_audit_logs', [
            'service_request_id' => $request->id,
            'user_id' => $this->employee->id,
            'to_status' => ServiceRequest::STATUS_SUBMITTED,
            'action' => 'created',
        ]);
    }

    public function test_authorized_personnel_can_update_status_following_workflow(): void
    {
        $request = ServiceRequest::create([
            'user_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'service_category_id' => $this->category->id,
            'description' => 'Printer out of toner.',
            'priority' => ServiceRequest::PRIORITY_MEDIUM,
        ]);

        // 1. Submitted -> Under Review
        $request->transitionTo(ServiceRequest::STATUS_UNDER_REVIEW, $this->admin, 'Reviewing request');
        $this->assertEquals(ServiceRequest::STATUS_UNDER_REVIEW, $request->fresh()->status);

        // 2. Under Review -> In Progress
        $request->transitionTo(ServiceRequest::STATUS_IN_PROGRESS, $this->admin, 'Dispatched tech');
        $this->assertEquals(ServiceRequest::STATUS_IN_PROGRESS, $request->fresh()->status);

        // 3. In Progress -> Completed
        $request->transitionTo(ServiceRequest::STATUS_COMPLETED, $this->admin, 'Toner cartridge replaced');
        $this->assertEquals(ServiceRequest::STATUS_COMPLETED, $request->fresh()->status);

        $this->assertDatabaseHas('request_audit_logs', [
            'service_request_id' => $request->id,
            'user_id' => $this->admin->id,
            'from_status' => ServiceRequest::STATUS_IN_PROGRESS,
            'to_status' => ServiceRequest::STATUS_COMPLETED,
            'notes' => 'Toner cartridge replaced',
        ]);
    }

    public function test_workflow_prevents_skipping_steps(): void
    {
        $request = ServiceRequest::create([
            'user_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'service_category_id' => $this->category->id,
            'description' => 'Office door handle loose.',
            'priority' => ServiceRequest::PRIORITY_LOW,
        ]);

        $this->expectException(ValidationException::class);
        // Direct transition from Submitted to Completed is forbidden
        $request->transitionTo(ServiceRequest::STATUS_COMPLETED, $this->admin);
    }

    public function test_unauthorized_employee_cannot_update_status(): void
    {
        $request = ServiceRequest::create([
            'user_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'service_category_id' => $this->category->id,
            'description' => 'AC unit blowing hot air.',
            'priority' => ServiceRequest::PRIORITY_URGENT,
        ]);

        $this->expectException(AuthorizationException::class);
        $request->transitionTo(ServiceRequest::STATUS_UNDER_REVIEW, $this->employee);
    }

    public function test_rejection_requires_reason(): void
    {
        $request = ServiceRequest::create([
            'user_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'service_category_id' => $this->category->id,
            'description' => 'Personal gaming console setup request.',
            'priority' => ServiceRequest::PRIORITY_LOW,
        ]);

        $this->expectException(ValidationException::class);
        // Rejection without notes must fail
        $request->transitionTo(ServiceRequest::STATUS_REJECTED, $this->admin, '');
    }

    public function test_rejection_succeeds_with_reason(): void
    {
        $request = ServiceRequest::create([
            'user_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'service_category_id' => $this->category->id,
            'description' => 'Personal gaming console setup request.',
            'priority' => ServiceRequest::PRIORITY_LOW,
        ]);

        $request->transitionTo(ServiceRequest::STATUS_REJECTED, $this->admin, 'Not an approved office equipment request.');

        $this->assertEquals(ServiceRequest::STATUS_REJECTED, $request->fresh()->status);
        $this->assertDatabaseHas('request_audit_logs', [
            'service_request_id' => $request->id,
            'user_id' => $this->admin->id,
            'from_status' => ServiceRequest::STATUS_SUBMITTED,
            'to_status' => ServiceRequest::STATUS_REJECTED,
            'notes' => 'Not an approved office equipment request.',
        ]);
    }
}
