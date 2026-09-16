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
        $officeA = Department::create([
            'name' => 'Office A',
            'code' => 'OFA',
            'email' => 'office-a@company.com',
            'description' => 'Office A operations and facility services.',
            'is_active' => true,
        ]);

        $officeB = Department::create([
            'name' => 'Office B',
            'code' => 'OFB',
            'email' => 'office-b@company.com',
            'description' => 'Office B workstations and technical infrastructure support.',
            'is_active' => true,
        ]);

        $officeC = Department::create([
            'name' => 'Office C',
            'code' => 'OFC',
            'email' => 'office-c@company.com',
            'description' => 'Office C administration and office supply logistics.',
            'is_active' => true,
        ]);

        $officeD = Department::create([
            'name' => 'Office D',
            'code' => 'OFD',
            'email' => 'office-d@company.com',
            'description' => 'Office D security and access control services.',
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
            'department_id' => $officeA->id,
            'name' => 'Air Conditioning & Climate',
            'description' => 'Temperature issues, leaks, air flow adjustments.',
            'sla_hours_default' => 12,
        ]);

        ServiceCategory::create([
            'department_id' => $officeA->id,
            'name' => 'Plumbing & Water',
            'description' => 'Leaks, restroom fixtures, water dispensers.',
            'sla_hours_default' => 4,
        ]);

        ServiceCategory::create([
            'department_id' => $officeA->id,
            'name' => 'Electrical & Lighting',
            'description' => 'Flickering lights, blown outlets, circuit breakers.',
            'sla_hours_default' => 8,
        ]);

        ServiceCategory::create([
            'department_id' => $officeB->id,
            'name' => 'Hardware & Laptop Repairs',
            'description' => 'Battery replacement, monitor repairs, peripherals.',
            'sla_hours_default' => 24,
        ]);

        ServiceCategory::create([
            'department_id' => $officeB->id,
            'name' => 'Network & Wi-Fi Access',
            'description' => 'Ethernet port issues, VPN configuration, SSID drops.',
            'sla_hours_default' => 4,
        ]);

        ServiceCategory::create([
            'department_id' => $officeC->id,
            'name' => 'Stationery & Office Supplies',
            'description' => 'Desk supplies, presentation whiteboards, notebooks.',
            'sla_hours_default' => 48,
        ]);

        ServiceCategory::create([
            'department_id' => $officeD->id,
            'name' => 'Security & Access Badges',
            'description' => 'Badge replacement, visitor credentials, door access.',
            'sla_hours_default' => 8,
        ]);

        ServiceCategory::create([
            'department_id' => $officeA->id,
            'name' => 'Ergonomics & Desk Furniture',
            'description' => 'Standing desk motor repair, ergonomic chair adjustments.',
            'sla_hours_default' => 36,
        ]);
    }
}
