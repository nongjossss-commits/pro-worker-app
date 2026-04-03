<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmployeeGroup;
use App\Models\EmployeeTeam;
use App\Models\Employer;
use App\Models\Employee;

class DummyGroupSeeder extends Seeder
{
    public function run()
    {
        // 1. สร้าง Employer 2 แห่ง
        $emp1 = Employer::create([
            'employerId' => 'EMP_DUMMY_001',
            'employerNameTh' => 'บริษัท ทดสอบ 1 จำกัด',
            'employerNameEn' => 'Test Co., Ltd. 1',
            'employerTaxId' => '1234567890123'
        ]);
        $emp2 = Employer::create([
            'employerId' => 'EMP_DUMMY_002',
            'employerNameTh' => 'บริษัท ทดสอบ 2 จำกัด',
            'employerNameEn' => 'Test Co., Ltd. 2',
            'employerTaxId' => '9876543210987'
        ]);

        // 2. สร้าง Employee ให้แต่ละ Employer
        $employees = [];
        for ($i = 1; $i <= 3; $i++) {
            $employees[] = Employee::create([
                'employer_id' => $emp1->id,
                'employeeNameTh' => "ลูกจ้าง บ.1 คนที่ $i",
                'employeeNameEn' => "Emp Co1 No$i",
                'passport' => "PP100$i",
                'status' => 'active'
            ]);
        }
        for ($i = 1; $i <= 2; $i++) {
            $employees[] = Employee::create([
                'employer_id' => $emp2->id,
                'employeeNameTh' => "ลูกจ้าง บ.2 คนที่ $i",
                'employeeNameEn' => "Emp Co2 No$i",
                'passport' => "PP200$i",
                'status' => 'active'
            ]);
        }

        // 3. สร้าง Group
        $group = EmployeeGroup::create([
            'name' => 'กลุ่มงานต่อวีซ่า',
            'type' => 'independent'
        ]);

        // 4. สร้าง Team ใน Group
        $team = EmployeeTeam::create([
            'employee_group_id' => $group->id,
            'name' => 'ทีม A'
        ]);

        // 5. เอาพนักงานเข้า Team (ผ่าน relationship team_members)
        foreach($employees as $employee) {
            \DB::table('employee_team_members')->insert([
                'employee_team_id' => $team->id,
                'employee_id' => $employee->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
