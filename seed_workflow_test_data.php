<?php
use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\WorkType;
use App\Models\Employer;
use App\Models\Employee;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$workType = WorkType::firstOrCreate(['slug' => 'renewal_mou'], ['name' => 'ต่ออายุ MOU', 'order' => 1]);

$employer = Employer::firstOrCreate(
    ['employerId' => 'EMP-TEST-01'],
    ['employerNameTh' => 'Test Employer']
);

$order = ProductionOrder::create([
    'production_order_number' => 'ORD-TEST-001',
    'work_type_id' => $workType->id,
    'employer_id' => $employer->id,
    'status' => 'pending',
    'active_items_count' => 3
]);

$emp1 = Employee::create(['employer_id' => $employer->id, 'employeeNameTh' => 'Test Employee 1']);
$emp2 = Employee::create(['employer_id' => $employer->id, 'employeeNameTh' => 'Test Employee 2']);
$emp3 = Employee::create(['employer_id' => $employer->id, 'employeeNameTh' => 'Test Employee 3']);

ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $emp1->id, 'status' => 'pending']);
ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $emp2->id, 'status' => 'cancelled']);
ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $emp3->id, 'status' => 'pending']);

echo "Test data seeded!\n";
