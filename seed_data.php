<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$employer = new \App\Models\Employer();
$employer->employerNameTh = 'Test Employer Company';
$employer->employerId = 'EMP-999';
$employer->employerTaxId = '1234567890123';
$employer->save();

$employee = new \App\Models\Employee();
$employee->employer_id = $employer->id;
$employee->employeeNameTh = 'Test Employee Name';
$employee->status = 'registration_pending';
$employee->save();

$order = new \App\Models\ProductionOrder();
$order->employer_id = $employer->id;
$order->title = 'Test Registration Order';
$order->type = 'registration';
$order->status = 'pending';
$order->save();

$item = new \App\Models\ProductionItem();
$item->production_order_id = $order->id;
$item->employee_id = $employee->id;
$item->status = 'pending';
$item->save();

echo "Seeded employee and order.\n";
