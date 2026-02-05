<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Models\Employer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmployeeImportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_import_employee_with_new_columns()
    {
        // Setup permissions
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'create-employees']);
        $user->givePermissionTo($permission);
        $this->actingAs($user);

        // Setup Employer
        $employer = Employer::create([
            'employerNameTh' => 'Test Employer',
            'employerNameEn' => 'Test Employer EN',
            'employerId' => '1234567890123',
            'employerTaxId' => '1234567890123',
        ]);

        // Create Excel File
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Row 12 Headers (Mock)
        $sheet->setCellValue('A12', 'Photo');
        // ... set other headers if strict checking existed, but it doesn't.

        // Row 13 Data
        // A=1, B=2 ... P=16, Q=17, R=18, S=19, T=20
        $sheet->setCellValue('B13', 'Mr.');       // Title EN
        $sheet->setCellValue('C13', 'John');      // Name EN
        $sheet->setCellValue('D13', 'นาย');      // Title TH
        $sheet->setCellValue('E13', 'สมชาย');    // Name TH
        $sheet->setCellValue('F13', '01/01/1990');// DOB
        $sheet->setCellValue('G13', 'ลาว');       // Nationality
        $sheet->setCellValue('H13', 'PP123456');  // Passport
        $sheet->setCellValue('I13', '01/01/2030');// PP Expiry
        $sheet->setCellValue('J13', 'WP987654');  // WP No
        $sheet->setCellValue('K13', '01/01/2025');// WP Expiry
        $sheet->setCellValue('L13', 'MOU');       // WP Type
        $sheet->setCellValue('M13', 'PINK001');   // Pink Card
        $sheet->setCellValue('N13', 'CI');        // Book Type
        $sheet->setCellValue('O13', '01/01/2026');// Visa Expiry
        $sheet->setCellValue('P13', '01/06/2024');// 90 Day

        // NEW COLUMNS
        $sheet->setCellValue('Q13', 'EMP_REF_001'); // Employer's Employee ID
        $sheet->setCellValue('R13', '15/05/2024');  // Start Date
        $sheet->setCellValue('S13', 'ID_NUM_999');  // Employee ID Number
        $sheet->setCellValue('T13', '20/05/2020');  // Passport Issue Date

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'import_test');
        $writer->save($tempFile);

        $file = new UploadedFile($tempFile, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        // Perform Request
        $response = $this->post(route('employees.import'), [
            'employer_id' => $employer->id,
            'file' => $file,
        ]);

        // Assertions
        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'employer_id' => $employer->id,
            'employeeNameEn' => 'John',
            'employer_employee_id' => 'EMP_REF_001',
            'startDate' => '2024-05-15', // formatted
            'employee_id_number' => 'ID_NUM_999',
            'passport_issue_date' => '2020-05-20', // formatted
        ]);

        unlink($tempFile);
    }

    public function test_can_download_template()
    {
        $user = User::factory()->create();
        // Permission check might be needed if the controller enforces it, but the method itself seems public/protected by auth middleware usually.
        // Assuming route is protected by auth.
        $this->actingAs($user);

        $response = $this->get(route('employees.template'));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=employee_import_template.xlsx');
    }
}
