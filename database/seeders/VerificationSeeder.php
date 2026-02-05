<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\Employer;
use App\Models\WorkType;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class VerificationSeeder extends Seeder
{
    public function run()
    {
        // Ensure User exists
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('admin_password_1234'),
            ]
        );
        $user = User::first();

        // Ensure WorkType exists
        $wt = WorkType::firstOrCreate(
            ['slug' => 'notify_in'],
            ['name' => 'Notify In', 'order' => 1]
        );

        // Active Employer
        $empActive = Employer::firstOrCreate(
            ['employerNameEn' => 'Active Corp'],
            ['employerNameTh' => 'บริษัท แอคทีฟ จำกัด', 'employerId' => 'EMP-001']
        );

        // Inactive Employer
        $empInactive = Employer::firstOrCreate(
            ['employerNameEn' => 'Inactive Corp'],
            ['employerNameTh' => 'บริษัท อินแอคทีฟ จำกัด', 'employerId' => 'EMP-002']
        );

        // 1. Workflow Orders
        // Active Order
        $wfActive = ProductionOrder::create([
            'employer_id' => $empActive->id,
            'work_type_id' => $wt->id,
            'project_name' => 'Project Active',
            'status' => 'active',
            'created_by' => $user->id
        ]);

        // Add items to Active Order
        ProductionItem::create([
            'production_order_id' => $wfActive->id,
            'status' => 'pending',
            'new_employee_data' => ['name_en' => 'Mr. Active 1', 'name_th' => 'นาย แอคทีฟ 1']
        ]);
        ProductionItem::create([
            'production_order_id' => $wfActive->id,
            'status' => 'completed',
             'new_employee_data' => ['name_en' => 'Mr. Active 2', 'name_th' => 'นาย แอคทีฟ 2']
        ]);

        // Inactive Order (No items or only cancelled/completed)
        $wfInactive = ProductionOrder::create([
            'employer_id' => $empInactive->id,
            'work_type_id' => $wt->id,
            'project_name' => 'Project Inactive',
            'status' => 'active',
            'created_by' => $user->id
        ]);
        // All items completed/cancelled
        ProductionItem::create([
            'production_order_id' => $wfInactive->id,
            'status' => 'completed',
             'new_employee_data' => ['name_en' => 'Mr. Inactive 1', 'name_th' => 'นาย อินแอคทีฟ 1']
        ]);

        // 2. Pre-Production Orders
         // Active Order
        $ppActive = ProductionOrder::create([
            'employer_id' => $empActive->id,
            'work_type_id' => $wt->id,
            'project_name' => 'Prep Active',
            'status' => 'pre_production',
            'created_by' => $user->id
        ]);
        ProductionItem::create([
            'production_order_id' => $ppActive->id,
            'status' => 'pending',
            'new_employee_data' => ['name_en' => 'Mr. Prep 1', 'name_th' => 'นาย เตรียม 1']
        ]);

        // Inactive Order
        $ppInactive = ProductionOrder::create([
            'employer_id' => $empInactive->id,
            'work_type_id' => $wt->id,
            'project_name' => 'Prep Inactive',
            'status' => 'pre_production',
            'created_by' => $user->id
        ]);
         ProductionItem::create([
            'production_order_id' => $ppInactive->id,
            'status' => 'cancelled',
            'new_employee_data' => ['name_en' => 'Mr. Prep Cancel', 'name_th' => 'นาย ยกเลิก']
        ]);
    }
}
