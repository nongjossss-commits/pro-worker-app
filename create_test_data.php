<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$employer = App\Models\Employer::first();
$workType = App\Models\WorkType::first();

$order = new App\Models\ProductionOrder();
$order->employer_id = $employer->id;
$order->work_type_id = $workType->id;
$order->type = 'employer';
$order->project_name = 'Test Project';
$order->status = 'active';
$order->financial_data = [
    'vat_enabled' => false,
    'vat_included' => false,
    'vat_rate' => 7,
    'total_amount' => 1000,
    'pricing_mode' => 'fixed',
    'fixed_base_amount' => 1000
];
$order->save();

$group = new App\Models\ProductionFinancialGroup();
$group->production_order_id = $order->id;
$group->name = 'Group 1';
$group->type = 'default';
$group->financial_data = [
    'vat_enabled' => false,
    'vat_included' => false,
    'vat_rate' => 7,
    'total_amount' => 1000,
    'pricing_mode' => 'fixed',
    'fixed_base_amount' => 1000
];
$group->save();

$employee = new App\Models\Employee();
$employee->employer_id = $employer->id;
$employee->employeeNameEn = 'Test Employee';
$employee->employee_reference_id = 'EMP-001';
$employee->status = 'registration_step_1';
$employee->save();

$item = new App\Models\ProductionItem();
$item->production_order_id = $order->id;
$item->financial_group_id = $group->id;
$item->employee_id = $employee->id;
$item->status = 'registration_step_1';
$item->order = 1;
$item->save();

echo "OK";
