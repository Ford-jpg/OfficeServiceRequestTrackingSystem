<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\RequestAuditLog;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $officeA = Department::create([
            'name' => 'Office A',
            'code' => 'OFA',
            'description' => 'Office A operations and facility services.',
            'email' => 'office-a@company.com',
            'is_active' => true,
        ]);

        $officeB = Department::create([
            'name' => 'Office B',
            'code' => 'OFB',
            'description' => 'Office B workstations and technical infrastructure support.',
            'email' => 'office-b@company.com',
            'is_active' => true,
        ]);

        $officeC = Department::create([
            'name' => 'Office C',
            'code' => 'OFC',
            'description' => 'Office C administration and office supply logistics.',
            'email' => 'office-c@company.com',
            'is_active' => true,
        ]);

        $officeD = Department::create([
            'name' => 'Office D',
            'code' => 'OFD',
            'description' => 'Office D security and access control services.',
            'email' => 'office-d@company.com',
            'is_active' => true,
        ]);

        $catHardware = ServiceCategory::create([
            'department_id' => $officeB->id,
            'name' => 'Computer & Hardware Support',
            'description' => 'Laptops, monitors, keyboards, and peripheral repairs.',
        ]);

        $catNetwork = ServiceCategory::create([
            'department_id' => $officeB->id,
            'name' => 'Network & Internet Access',
            'description' => 'Wi-Fi connection drops, VPN access, ethernet wall ports.',
        ]);

        $catHvac = ServiceCategory::create([
            'department_id' => $officeA->id,
            'name' => 'Air Conditioning & HVAC',
            'description' => 'Temperature control, thermostat adjustments, AC leaks.',
        ]);

        $catElectrical = ServiceCategory::create([
            'department_id' => $officeA->id,
            'name' => 'Electrical & Lighting',
            'description' => 'Flickering ceiling fixtures, burnt power strips, circuit breakers.',
        ]);

        $catSupplies = ServiceCategory::create([
            'department_id' => $officeC->id,
            'name' => 'Office Furniture & Supplies',
            'description' => 'Ergonomic chairs, standing desks, whiteboard markers.',
        ]);

        $catSecurity = ServiceCategory::create([
            'department_id' => $officeD->id,
            'name' => 'Security & Access Badges',
            'description' => 'Badge replacement, visitor credentials, door access.',
        ]);

        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'department_id' => $officeB->id,
        ]);

        $employee1 = User::create([
            'name' => 'John Doe',
            'email' => 'employee@example.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'department_id' => $officeD->id,
        ]);

        $employee2 = User::create([
            'name' => 'Sarah Connor',
            'email' => 'sarah@example.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'department_id' => $officeA->id,
        ]);

        $req1 = ServiceRequest::create([
            'reference_number' => 'REF-202609-0001',
            'user_id' => $employee1->id,
            'department_id' => $officeB->id,
            'service_category_id' => $catHardware->id,
            'description' => 'External monitor power supply is faulty and fails to turn on after power surge.',
            'priority' => 'high',
            'status' => ServiceRequest::STATUS_SUBMITTED,
            'created_at' => now()->subDays(2),
        ]);

        $req2 = ServiceRequest::create([
            'reference_number' => 'REF-202609-0002',
            'user_id' => $employee2->id,
            'department_id' => $officeA->id,
            'service_category_id' => $catHvac->id,
            'description' => 'Meeting Room B air conditioning is making a grinding noise and blowing warm air.',
            'priority' => 'urgent',
            'status' => ServiceRequest::STATUS_UNDER_REVIEW,
            'created_at' => now()->subDays(3),
        ]);

        RequestAuditLog::create([
            'service_request_id' => $req2->id,
            'user_id' => $admin->id,
            'action' => 'status_change',
            'from_status' => ServiceRequest::STATUS_SUBMITTED,
            'to_status' => ServiceRequest::STATUS_UNDER_REVIEW,
            'notes' => 'Reviewed by facilities desk; dispatched inspection team.',
            'created_at' => now()->subDays(2),
        ]);

        $req3 = ServiceRequest::create([
            'reference_number' => 'REF-202609-0003',
            'user_id' => $employee1->id,
            'department_id' => $officeA->id,
            'service_category_id' => $catElectrical->id,
            'description' => 'Overhead LED strip in conference cubicle is flickering intermittently.',
            'priority' => 'medium',
            'status' => ServiceRequest::STATUS_IN_PROGRESS,
            'created_at' => now()->subDays(4),
        ]);

        RequestAuditLog::create([
            'service_request_id' => $req3->id,
            'user_id' => $admin->id,
            'action' => 'status_change',
            'from_status' => ServiceRequest::STATUS_SUBMITTED,
            'to_status' => ServiceRequest::STATUS_UNDER_REVIEW,
            'notes' => 'Electrician assigned for fixture replacement.',
            'created_at' => now()->subDays(3),
        ]);

        RequestAuditLog::create([
            'service_request_id' => $req3->id,
            'user_id' => $admin->id,
            'action' => 'status_change',
            'from_status' => ServiceRequest::STATUS_UNDER_REVIEW,
            'to_status' => ServiceRequest::STATUS_IN_PROGRESS,
            'notes' => 'Electrician on-site with replacement ballast.',
            'created_at' => now()->subDays(1),
        ]);

        $req4 = ServiceRequest::create([
            'reference_number' => 'REF-202609-0004',
            'user_id' => $employee2->id,
            'department_id' => $officeB->id,
            'service_category_id' => $catNetwork->id,
            'description' => 'Need static IP and VLAN configuration for executive conference room videoconferencing unit.',
            'priority' => 'high',
            'status' => ServiceRequest::STATUS_COMPLETED,
            'created_at' => now()->subDays(5),
        ]);

        RequestAuditLog::create([
            'service_request_id' => $req4->id,
            'user_id' => $admin->id,
            'action' => 'status_change',
            'from_status' => ServiceRequest::STATUS_SUBMITTED,
            'to_status' => ServiceRequest::STATUS_UNDER_REVIEW,
            'notes' => 'Evaluating subnet allocation.',
            'created_at' => now()->subDays(4),
        ]);

        RequestAuditLog::create([
            'service_request_id' => $req4->id,
            'user_id' => $admin->id,
            'action' => 'status_change',
            'from_status' => ServiceRequest::STATUS_UNDER_REVIEW,
            'to_status' => ServiceRequest::STATUS_IN_PROGRESS,
            'notes' => 'Switch port tagged and IP provisioned.',
            'created_at' => now()->subDays(2),
        ]);

        RequestAuditLog::create([
            'service_request_id' => $req4->id,
            'user_id' => $admin->id,
            'action' => 'status_change',
            'from_status' => ServiceRequest::STATUS_IN_PROGRESS,
            'to_status' => ServiceRequest::STATUS_COMPLETED,
            'notes' => 'Configuration verified and videoconferencing tested successfully.',
            'created_at' => now()->subHours(6),
        ]);

        $req5 = ServiceRequest::create([
            'reference_number' => 'REF-202609-0005',
            'user_id' => $employee1->id,
            'department_id' => $officeC->id,
            'service_category_id' => $catSupplies->id,
            'description' => 'Request for personal espresso machine and custom leather recliner.',
            'priority' => 'low',
            'status' => ServiceRequest::STATUS_REJECTED,
            'created_at' => now()->subDays(6),
        ]);

        RequestAuditLog::create([
            'service_request_id' => $req5->id,
            'user_id' => $admin->id,
            'action' => 'status_change',
            'from_status' => ServiceRequest::STATUS_SUBMITTED,
            'to_status' => ServiceRequest::STATUS_REJECTED,
            'notes' => 'Item is not approved under standard office equipment policy.',
            'created_at' => now()->subDays(5),
        ]);
    }
}
