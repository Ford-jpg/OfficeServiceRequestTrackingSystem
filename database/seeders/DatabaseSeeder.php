<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Location;
use App\Models\RequestAuditLog;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Departments
        $facilities = Department::create([
            'name' => 'Facilities & Maintenance',
            'code' => 'FAC',
            'email' => 'facilities@company.com',
            'description' => 'Building infrastructure, HVAC, plumbing, electrical, and furniture maintenance.',
            'is_active' => true,
        ]);

        $it = Department::create([
            'name' => 'Information Technology',
            'code' => 'IT',
            'email' => 'it-helpdesk@company.com',
            'description' => 'Workstation hardware, software licensing, printers, and network infrastructure.',
            'is_active' => true,
        ]);

        $adminServices = Department::create([
            'name' => 'Office Administration & Supplies',
            'code' => 'ADMIN',
            'email' => 'admin-services@company.com',
            'description' => 'Office stationery, pantry supplies, ergonomics, and meeting room logistics.',
            'is_active' => true,
        ]);

        $security = Department::create([
            'name' => 'Security & Access Control',
            'code' => 'SEC',
            'email' => 'security@company.com',
            'description' => 'Access keycards, visitor credentials, and physical office security.',
            'is_active' => true,
        ]);

        // 2. Seed Users
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'job_title' => 'Chief Operations Officer',
            'phone' => '+1 (555) 010-0001',
            'is_active' => true,
        ]);

        $manager = User::create([
            'name' => 'Marcus Vance',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'role' => 'service_manager',
            'department_id' => $facilities->id,
            'job_title' => 'Service Operations Manager',
            'phone' => '+1 (555) 010-0002',
            'is_active' => true,
        ]);

        $techFac = User::create([
            'name' => 'Carlos Reyes',
            'email' => 'tech.facilities@example.com',
            'password' => Hash::make('password'),
            'role' => 'technician',
            'department_id' => $facilities->id,
            'job_title' => 'Senior HVAC & Electrical Technician',
            'phone' => '+1 (555) 010-0003',
            'is_active' => true,
        ]);

        $techIT = User::create([
            'name' => 'Elena Rostova',
            'email' => 'tech.it@example.com',
            'password' => Hash::make('password'),
            'role' => 'technician',
            'department_id' => $it->id,
            'job_title' => 'Lead Desktop Support Specialist',
            'phone' => '+1 (555) 010-0004',
            'is_active' => true,
        ]);

        $employee1 = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'job_title' => 'Senior Product Designer',
            'phone' => '+1 (555) 010-0005',
            'is_active' => true,
        ]);

        $employee2 = User::create([
            'name' => 'John Smith',
            'email' => 'john.smith@example.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'job_title' => 'Financial Analyst',
            'phone' => '+1 (555) 010-0006',
            'is_active' => true,
        ]);

        $employee3 = User::create([
            'name' => 'Sarah Lee',
            'email' => 'sarah.lee@example.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'job_title' => 'Talent Acquisition Partner',
            'phone' => '+1 (555) 010-0007',
            'is_active' => true,
        ]);

        // 3. Seed Locations
        $loc1 = Location::create(['building' => 'Main Tower', 'floor' => 'Ground Floor', 'room_or_area' => 'Reception & Visitor Lounge']);
        $loc2 = Location::create(['building' => 'Main Tower', 'floor' => '2nd Floor', 'room_or_area' => 'Finance & Accounting Hub']);
        $loc3 = Location::create(['building' => 'Main Tower', 'floor' => '3rd Floor', 'room_or_area' => 'Executive Boardroom A']);
        $loc4 = Location::create(['building' => 'Main Tower', 'floor' => '4th Floor', 'room_or_area' => 'Design & Marketing Pod (Desks 12-25)']);
        $loc5 = Location::create(['building' => 'Innovation Wing', 'floor' => '1st Floor', 'room_or_area' => 'Engineering Lab & Server Room']);
        $loc6 = Location::create(['building' => 'Innovation Wing', 'floor' => '2nd Floor', 'room_or_area' => 'Staff Pantry & Coffee Bar']);

        // 4. Seed Service Categories
        $catHvac = ServiceCategory::create([
            'department_id' => $facilities->id,
            'name' => 'Air Conditioning & Climate',
            'description' => 'Temperature issues, leaks, air flow adjustments.',
            'sla_hours_default' => 12,
        ]);

        $catPlumbing = ServiceCategory::create([
            'department_id' => $facilities->id,
            'name' => 'Plumbing & Water',
            'description' => 'Leaks, restroom fixtures, water dispensers.',
            'sla_hours_default' => 4,
        ]);

        $catElectrical = ServiceCategory::create([
            'department_id' => $facilities->id,
            'name' => 'Electrical & Lighting',
            'description' => 'Flickering lights, blown outlets, circuit breakers.',
            'sla_hours_default' => 8,
        ]);

        $catHardware = ServiceCategory::create([
            'department_id' => $it->id,
            'name' => 'Hardware & Laptop Repairs',
            'description' => 'Battery replacement, monitor repairs, peripherals.',
            'sla_hours_default' => 24,
        ]);

        $catNetwork = ServiceCategory::create([
            'department_id' => $it->id,
            'name' => 'Network & Wi-Fi Access',
            'description' => 'Ethernet port issues, VPN configuration, SSID drops.',
            'sla_hours_default' => 4,
        ]);

        $catSupplies = ServiceCategory::create([
            'department_id' => $adminServices->id,
            'name' => 'Stationery & Office Supplies',
            'description' => 'Desk supplies, presentation whiteboards, notebooks.',
            'sla_hours_default' => 48,
        ]);

        $catFurniture = ServiceCategory::create([
            'department_id' => $facilities->id,
            'name' => 'Ergonomics & Desk Furniture',
            'description' => 'Standing desk motor repair, ergonomic chair adjustments.',
            'sla_hours_default' => 36,
        ]);

        // 5. Seed Requests representing each status in the workflow:
        // Submitted -> Under Review -> In Progress -> Completed / Rejected

        // A. Submitted (Newly created)
        $req1 = ServiceRequest::create([
            'ticket_number' => 'SR-' . date('Ym') . '-0001',
            'requester_id' => $employee1->id,
            'department_id' => $facilities->id,
            'service_category_id' => $catHvac->id,
            'location_id' => $loc3->id,
            'title' => 'AC unit whistling and blowing warm air in Boardroom A',
            'description' => 'During our 10 AM client meeting, the central air unit started making an intermittent high-pitched sound and room temperature reached 26C.',
            'priority' => ServiceRequest::PRIORITY_HIGH,
            'status' => ServiceRequest::STATUS_SUBMITTED,
            'due_date' => Carbon::now()->addHours(12),
            'created_at' => Carbon::now()->subHours(2),
        ]);

        $req2 = ServiceRequest::create([
            'ticket_number' => 'SR-' . date('Ym') . '-0002',
            'requester_id' => $employee2->id,
            'department_id' => $it->id,
            'service_category_id' => $catHardware->id,
            'location_id' => $loc2->id,
            'title' => 'Dual monitor setup displays flickering lines on secondary screen',
            'description' => 'HDMI connection appears loose or cable has degraded. Swapping ports did not solve the artifacting.',
            'priority' => ServiceRequest::PRIORITY_MEDIUM,
            'status' => ServiceRequest::STATUS_SUBMITTED,
            'due_date' => Carbon::now()->addHours(24),
            'created_at' => Carbon::now()->subHours(5),
        ]);

        // B. Under Review
        $req3 = ServiceRequest::create([
            'ticket_number' => 'SR-' . date('Ym') . '-0003',
            'requester_id' => $employee3->id,
            'department_id' => $facilities->id,
            'service_category_id' => $catPlumbing->id,
            'location_id' => $loc6->id,
            'assigned_to_user_id' => $techFac->id,
            'title' => 'Water filter dispenser leaking onto kitchen linoleum',
            'description' => 'Slow drip beneath the cabinet has accumulated a puddle near the refrigerator. Slip hazard notice placed.',
            'priority' => ServiceRequest::PRIORITY_URGENT,
            'status' => ServiceRequest::STATUS_UNDER_REVIEW,
            'due_date' => Carbon::now()->addHours(3),
            'created_at' => Carbon::now()->subHours(4),
        ]);

        RequestAuditLog::create([
            'service_request_id' => $req3->id,
            'user_id' => $manager->id,
            'action' => 'status_change',
            'from_status' => ServiceRequest::STATUS_SUBMITTED,
            'to_status' => ServiceRequest::STATUS_UNDER_REVIEW,
            'notes' => 'Acknowledged urgent slip hazard. Dispatching facilities technician immediately.',
            'created_at' => Carbon::now()->subHours(3),
        ]);

        // C. In Progress
        $req4 = ServiceRequest::create([
            'ticket_number' => 'SR-' . date('Ym') . '-0004',
            'requester_id' => $employee1->id,
            'department_id' => $it->id,
            'service_category_id' => $catNetwork->id,
            'location_id' => $loc4->id,
            'assigned_to_user_id' => $techIT->id,
            'title' => 'Subnet IP conflict on Design floor wireless access point',
            'description' => 'Three designer laptops unable to connect to internal staging environment. Error code 0x800704cf.',
            'priority' => ServiceRequest::PRIORITY_HIGH,
            'status' => ServiceRequest::STATUS_IN_PROGRESS,
            'due_date' => Carbon::now()->addHours(2),
            'created_at' => Carbon::now()->subHours(6),
        ]);

        RequestAuditLog::create([
            'service_request_id' => $req4->id,
            'user_id' => $manager->id,
            'action' => 'status_change',
            'from_status' => ServiceRequest::STATUS_SUBMITTED,
            'to_status' => ServiceRequest::STATUS_UNDER_REVIEW,
            'notes' => 'Reviewed and routed to IT infrastructure group.',
            'created_at' => Carbon::now()->subHours(5),
        ]);

        RequestAuditLog::create([
            'service_request_id' => $req4->id,
            'user_id' => $techIT->id,
            'action' => 'status_change',
            'from_status' => ServiceRequest::STATUS_UNDER_REVIEW,
            'to_status' => ServiceRequest::STATUS_IN_PROGRESS,
            'notes' => 'On site at Design pod. Accessing AP configuration and flushing DHCP lease cache.',
            'created_at' => Carbon::now()->subHours(1),
        ]);

        // D. Completed (Successfully resolved)
        $req5 = ServiceRequest::create([
            'ticket_number' => 'SR-' . date('Ym') . '-0005',
            'requester_id' => $employee2->id,
            'department_id' => $facilities->id,
            'service_category_id' => $catElectrical->id,
            'location_id' => $loc2->id,
            'assigned_to_user_id' => $techFac->id,
            'title' => 'Overhead LED troffer light buzzing above workstation',
            'description' => 'Ballast is vibrating loudly creating a distraction for adjacent analysts.',
            'priority' => ServiceRequest::PRIORITY_LOW,
            'status' => ServiceRequest::STATUS_COMPLETED,
            'resolution_notes' => 'Replaced faulty ballast driver with 40W electronic LED driver. Tested illuminance and confirmed noise-free operation.',
            'due_date' => Carbon::now()->subHours(2),
            'resolved_at' => Carbon::now()->subHours(3),
            'satisfaction_rating' => 5,
            'satisfaction_feedback' => 'Very fast resolution! Thanks Carlos.',
            'created_at' => Carbon::now()->subDays(1),
        ]);

        RequestAuditLog::create([
            'service_request_id' => $req5->id,
            'user_id' => $manager->id,
            'action' => 'status_change',
            'from_status' => ServiceRequest::STATUS_SUBMITTED,
            'to_status' => ServiceRequest::STATUS_UNDER_REVIEW,
            'notes' => 'Approved and assigned to Carlos.',
            'created_at' => Carbon::now()->subDays(1)->addHours(1),
        ]);

        RequestAuditLog::create([
            'service_request_id' => $req5->id,
            'user_id' => $techFac->id,
            'action' => 'status_change',
            'from_status' => ServiceRequest::STATUS_UNDER_REVIEW,
            'to_status' => ServiceRequest::STATUS_IN_PROGRESS,
            'notes' => 'Grabbed replacement driver from basement inventory; working on ceiling fixture.',
            'created_at' => Carbon::now()->subHours(5),
        ]);

        RequestAuditLog::create([
            'service_request_id' => $req5->id,
            'user_id' => $techFac->id,
            'action' => 'status_change',
            'from_status' => ServiceRequest::STATUS_IN_PROGRESS,
            'to_status' => ServiceRequest::STATUS_COMPLETED,
            'notes' => 'Replaced faulty ballast driver with 40W electronic LED driver. Tested illuminance and confirmed noise-free operation.',
            'created_at' => Carbon::now()->subHours(3),
        ]);

        // E. Rejected (Declined with mandatory reason)
        $req6 = ServiceRequest::create([
            'ticket_number' => 'SR-' . date('Ym') . '-0006',
            'requester_id' => $employee3->id,
            'department_id' => $facilities->id,
            'service_category_id' => $catFurniture->id,
            'location_id' => $loc1->id,
            'assigned_to_user_id' => $manager->id,
            'title' => 'Request for personal gaming chair in reception area',
            'description' => 'Employee requested custom high-back bucket seat for personal comfort.',
            'priority' => ServiceRequest::PRIORITY_LOW,
            'status' => ServiceRequest::STATUS_REJECTED,
            'rejection_reason' => 'Non-standard ergonomic equipment request. Company policy requires HR occupational health approval for non-standard seating.',
            'created_at' => Carbon::now()->subDays(2),
        ]);

        RequestAuditLog::create([
            'service_request_id' => $req6->id,
            'user_id' => $manager->id,
            'action' => 'status_change',
            'from_status' => ServiceRequest::STATUS_SUBMITTED,
            'to_status' => ServiceRequest::STATUS_UNDER_REVIEW,
            'notes' => 'Reviewing equipment procurement policy.',
            'created_at' => Carbon::now()->subDays(2)->addHours(2),
        ]);

        RequestAuditLog::create([
            'service_request_id' => $req6->id,
            'user_id' => $manager->id,
            'action' => 'status_change',
            'from_status' => ServiceRequest::STATUS_UNDER_REVIEW,
            'to_status' => ServiceRequest::STATUS_REJECTED,
            'notes' => 'Non-standard ergonomic equipment request. Company policy requires HR occupational health approval for non-standard seating.',
            'created_at' => Carbon::now()->subDays(1),
        ]);

        // F. Additional sample requests for richer dashboard metrics
        ServiceRequest::create([
            'ticket_number' => 'SR-' . date('Ym') . '-0007',
            'requester_id' => $employee1->id,
            'department_id' => $adminServices->id,
            'service_category_id' => $catSupplies->id,
            'location_id' => $loc4->id,
            'title' => 'Restock Post-it notes and dry-erase markers for Design Studio',
            'description' => 'Sprint planning begins Monday and dry erase pens have dried out.',
            'priority' => ServiceRequest::PRIORITY_MEDIUM,
            'status' => ServiceRequest::STATUS_SUBMITTED,
            'due_date' => Carbon::now()->addHours(36),
            'created_at' => Carbon::now()->subHours(1),
        ]);

        ServiceRequest::create([
            'ticket_number' => 'SR-' . date('Ym') . '-0008',
            'requester_id' => $employee2->id,
            'department_id' => $facilities->id,
            'service_category_id' => $catHvac->id,
            'location_id' => $loc2->id,
            'assigned_to_user_id' => $techFac->id,
            'title' => 'Thermostat calibration needed in Finance department',
            'description' => 'Temperature fluctuating between 19C and 25C within two hours.',
            'priority' => ServiceRequest::PRIORITY_MEDIUM,
            'status' => ServiceRequest::STATUS_IN_PROGRESS,
            'due_date' => Carbon::now()->addHours(8),
            'created_at' => Carbon::now()->subHours(8),
        ]);
    }
}
