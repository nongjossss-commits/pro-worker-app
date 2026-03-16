<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Employer;
use App\Models\Employee;

$employer = Employer::create([
    'employerId' => 'EMP-REG-01',
    'employerNameTh' => 'Test Employer',
]);

Employee::create([
    'employer_id' => $employer->id,
    'employeeNameEn' => 'Test Employee',
    'status' => 'renewal_pending',
    'employeePassport' => 'P-123456',
    'request_number' => 'REQ-001',
    'renewal_request_number' => 'REN-002'
]);

Employee::create([
    'employer_id' => $employer->id,
    'employeeNameEn' => 'Test Employee Reg',
    'status' => 'registration_pending',
    'employeePassport' => 'P-654321',
    'request_number' => 'REQ-101',
    'registration_request_number' => 'REG-102'
]);
echo "Seeded!";
