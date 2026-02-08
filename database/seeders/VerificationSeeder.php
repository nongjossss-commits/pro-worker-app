<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employer;
use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\WorkType;
use App\Models\Employee;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VerificationSeeder extends Seeder
{
    public function run()
    {
        // Ensure user exists
        $user = User::firstOrCreate([
            'email' => 'admin@example.com'
        ], [
            'name' => 'Admin User',
            'password' => Hash::make('password')
        ]);

        // Ensure role
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole($role);

        // Ensure WorkType
        $workType = WorkType::firstOrCreate(
            ['slug' => 'registration'],
            ['name' => 'Registration']
        );

        // Create Employer
        $employer = Employer::create([
            'employerNameTh' => 'Verification Company',
            'employerNameEn' => 'Verification Company EN',
            'employerId' => 'EMP-' . Str::random(5)
        ]);

        // Create Employee
        $employee = Employee::create([
            'employer_id' => $employer->id,
            'employeeNameTh' => 'Somchai Jaidee',
            'employeeNameEn' => 'Somchai Jaidee',
            'employeeTitleEn' => 'Mr.',
            'employeeNationality' => 'Laos',
            'status' => 'registration_pending' // Changed from active
        ]);

        // Create Order
        $order = ProductionOrder::create([
            'employer_id' => $employer->id,
            'work_type_id' => $workType->id,
            'status' => 'active',
            'created_by' => $user->id,
            'project_name' => 'Verification Project'
        ]);

        // Create Item
        $item = ProductionItem::create([
            'production_order_id' => $order->id,
            'employee_id' => $employee->id,
            'status' => 'pending'
        ]);

        $this->command->info("Verification Seeder Complete. Use Order ID: {$order->id}");
    }
}
