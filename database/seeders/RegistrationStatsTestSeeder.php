<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Employer;

class RegistrationStatsTestSeeder extends Seeder
{
    public function run()
    {
        $employer = Employer::create([
            'employerId' => 'EMP-REG-TEST-01',
            'employerNameTh' => 'Test Employer Reg',
            'employerNameEn' => 'Test Employer Reg EN'
        ]);

        // 1. Pending (should count in total and pending)
        Employee::create([
            'employer_id' => $employer->id,
            'employeeNameTh' => 'Reg Pending Employee',
            'employeeNameEn' => 'Reg Pending Employee EN',
            'status' => 'registration_pending',
            'appointment_date' => now()->addDays(2)
        ]);

        // 2. Completed (should count in total and completed)
        Employee::create([
            'employer_id' => $employer->id,
            'employeeNameTh' => 'Reg Completed Employee',
            'employeeNameEn' => 'Reg Completed Employee EN',
            'status' => 'registration_completed',
        ]);

        // 3. Cancelled (should count in total and cancelled)
        Employee::create([
            'employer_id' => $employer->id,
            'employeeNameTh' => 'Reg Cancelled Employee',
            'employeeNameEn' => 'Reg Cancelled Employee EN',
            'status' => 'registration_cancelled',
        ]);

        // 4. Different status (should NOT count in total or anywhere else)
        Employee::create([
            'employer_id' => $employer->id,
            'employeeNameTh' => 'Import Employee',
            'employeeNameEn' => 'Import Employee EN',
            'status' => 'import_pending',
            'appointment_date' => now()->addDays(2)
        ]);

        // 5. Another pending for today's calendar
        Employee::create([
            'employer_id' => $employer->id,
            'employeeNameTh' => 'Reg Calendar Employee',
            'employeeNameEn' => 'Reg Calendar Employee EN',
            'status' => 'registration_pending',
            'appointment_date' => now()
        ]);
    }
}
