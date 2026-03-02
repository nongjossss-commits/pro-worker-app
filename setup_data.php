<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employer;
use App\Models\Employee;
use App\Models\RegistrationStep;
use App\Models\ProductionOrder;
use App\Models\ProductionItem;

$employer = Employer::create(['employerId' => 'EMP1', 'employerNameTh' => 'Test Employer 1', 'employerNameEn' => 'Test Employer 1']);

$step1 = RegistrationStep::create(['name' => 'Step 1', 'type' => 'registration', 'order' => 1]);
$step2 = RegistrationStep::create(['name' => 'Step 2', 'type' => 'registration', 'order' => 2]);

$emp1 = Employee::create(['employer_id' => $employer->id, 'employeeNameTh' => 'Somchai', 'employeePassport' => 'P12345', 'status' => 'registration_pending']);
$emp1->registrationSteps()->sync([$step1->id => ['completed_at' => now()], $step2->id => ['completed_at' => now()]]);

$emp2 = Employee::create(['employer_id' => $employer->id, 'employeeNameTh' => 'Somsri', 'employeePassport' => '-', 'status' => 'registration_pending']);
$emp2->registrationSteps()->sync([$step1->id => ['completed_at' => now()]]);

$order = ProductionOrder::create(['employer_id' => $employer->id, 'project_name' => 'Test Registration']);
ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $emp1->id, 'status' => 'pending']);
ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $emp2->id, 'status' => 'pending']);

echo "Data seeded successfully.\n";
