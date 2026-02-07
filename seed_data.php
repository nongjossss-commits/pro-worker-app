<?php

use App\Models\WorkType;
use App\Models\Employer;
use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

// Ensure admin user exists
$user = User::firstOrCreate(
    ['email' => 'test@example.com'],
    [
        'name' => 'Test User',
        'password' => Hash::make('password')
    ]
);
// Ensure admin role exists
$role = Role::firstOrCreate(['name' => 'admin']);
$user->assignRole($role);


// Create WorkType
$wt = WorkType::firstOrCreate(['slug' => 'notify_in'], ['name' => 'Notify In', 'order' => 1]);

// Create Employer
$employer = Employer::factory()->create(['employerNameTh' => 'Test Employer']);

// Create Order
$order = ProductionOrder::create([
    'employer_id' => $employer->id,
    'work_type_id' => $wt->id,
    'status' => 'active',
    'project_name' => 'Test Project'
]);

// 1. Cancelled Item
ProductionItem::create([
    'production_order_id' => $order->id,
    'status' => 'cancelled',
    'employee_id' => Employee::factory()->create(['employer_id' => $employer->id])->id
]);

// 2. Active Item (Pending)
ProductionItem::create([
    'production_order_id' => $order->id,
    'status' => 'pending',
    'employee_id' => Employee::factory()->create(['employer_id' => $employer->id])->id
]);

// 3. Completed Item (Recent)
ProductionItem::create([
    'production_order_id' => $order->id,
    'status' => 'completed',
    'completed_at' => now()->subMinutes(10),
    'employee_id' => Employee::factory()->create(['employer_id' => $employer->id])->id
]);

// 4. History Item (Old)
ProductionItem::create([
    'production_order_id' => $order->id,
    'status' => 'completed',
    'completed_at' => now()->subHours(25),
    'employee_id' => Employee::factory()->create(['employer_id' => $employer->id])->id
]);

echo "Seeded Data for Order ID: " . $order->id . "\n";
