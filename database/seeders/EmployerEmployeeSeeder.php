<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Employer;
use App\Models\Employee;

class EmployerEmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // สร้างนายจ้างจำนวน 10 ราย
        // สำหรับนายจ้างแต่ละราย ให้สร้างลูกจ้างแบบสุ่มจำนวน 5 ถึง 15 คน
        // และผูกข้อมูลลูกจ้างเข้ากับนายจ้างรายนั้นๆ โดยอัตโนมัติ
        Employer::factory()
            ->has(Employee::factory()->count(rand(5, 15)))
            ->count(10)
            ->create();
    }
}