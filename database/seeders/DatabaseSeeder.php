<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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

        Department::create([
            'name' => 'Security & Access Control',
            'code' => 'SEC',
            'email' => 'security@company.com',
            'description' => 'Access keycards, visitor credentials, and physical office security.',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'System Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'job_title' => 'Chief Operations Officer',
            'phone' => '+1 (555) 010-0001',
            'is_active' => true,
        ]);

        ServiceCategory::create([
            'department_id' => $facilities->id,
            'name' => 'Air Conditioning & Climate',
            'description' => 'Temperature issues, leaks, air flow adjustments.',
            'sla_hours_default' => 12,
        ]);

        ServiceCategory::create([
            'department_id' => $facilities->id,
            'name' => 'Plumbing & Water',
            'description' => 'Leaks, restroom fixtures, water dispensers.',
            'sla_hours_default' => 4,
        ]);

        ServiceCategory::create([
            'department_id' => $facilities->id,
            'name' => 'Electrical & Lighting',
            'description' => 'Flickering lights, blown outlets, circuit breakers.',
            'sla_hours_default' => 8,
        ]);

        ServiceCategory::create([
            'department_id' => $it->id,
            'name' => 'Hardware & Laptop Repairs',
            'description' => 'Battery replacement, monitor repairs, peripherals.',
            'sla_hours_default' => 24,
        ]);

        ServiceCategory::create([
            'department_id' => $it->id,
            'name' => 'Network & Wi-Fi Access',
            'description' => 'Ethernet port issues, VPN configuration, SSID drops.',
            'sla_hours_default' => 4,
        ]);

        ServiceCategory::create([
            'department_id' => $adminServices->id,
            'name' => 'Stationery & Office Supplies',
            'description' => 'Desk supplies, presentation whiteboards, notebooks.',
            'sla_hours_default' => 48,
        ]);

        ServiceCategory::create([
            'department_id' => $facilities->id,
            'name' => 'Ergonomics & Desk Furniture',
            'description' => 'Standing desk motor repair, ergonomic chair adjustments.',
            'sla_hours_default' => 36,
        ]);
    }
}
