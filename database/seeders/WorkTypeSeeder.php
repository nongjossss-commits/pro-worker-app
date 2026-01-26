<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkType;
use App\Models\WorkTypeStep;

class WorkTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [
            [
                'name' => 'แจ้งเข้า / เปลี่ยนนายจ้าง', // Notify In / Change Employer
                'slug' => 'notify_in',
                'is_system' => true,
                'order' => 1,
                'steps' => ['รับเอกสาร', 'ยื่นเรื่อง', 'รออนุมัติ', 'รับเล่มคืน', 'แจ้งผล']
            ],
            [
                'name' => 'แจ้งออก', // Notify Out
                'slug' => 'notify_out',
                'is_system' => true,
                'order' => 2,
                'steps' => ['รับเอกสาร', 'แจ้งออกระบบ', 'คืนนายจ้าง']
            ],
            [
                'name' => 'MOU นำเข้า', // MOU Import
                'slug' => 'mou_import',
                'is_system' => true,
                'order' => 3,
                'steps' => ['Name List', 'Calling Visa', 'Stamp Visa', 'Work Permit', 'Card']
            ]
        ];

        foreach ($types as $typeData) {
            $steps = $typeData['steps'];
            unset($typeData['steps']);

            $workType = WorkType::firstOrCreate(
                ['slug' => $typeData['slug']],
                $typeData
            );

            foreach ($steps as $index => $stepName) {
                WorkTypeStep::firstOrCreate(
                    ['work_type_id' => $workType->id, 'name' => $stepName],
                    ['order' => $index + 1]
                );
            }
        }
    }
}
