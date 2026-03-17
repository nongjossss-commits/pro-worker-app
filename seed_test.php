<?php
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$employer = new \App\Models\Employer();
$employer->employerNameTh = 'Test Employer Company';
$employer->employerId = 'EMP-999';
$employer->save();

$employee = new \App\Models\Employee();
$employee->employer_id = $employer->id;
$employee->employeeNameTh = 'Test Employee Name';
$employee->status = 'registration_pending';
$employee->save();

echo "Seeded!";
