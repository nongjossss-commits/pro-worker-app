<?php

namespace Database\Seeders;

use App\Models\WorkflowBarrier;
use Illuminate\Database\Seeder;

class WorkflowBarrierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define default barriers
        $barriers = [
            [
                'name' => 'Pending (รอดำเนินการ)',
                'color' => 'primary',
                'sequence' => 1,
            ],
            [
                'name' => 'Pending Inspection (รอตรวจสอบ)',
                'color' => 'warning',
                'sequence' => 2,
            ],
            [
                'name' => 'Document Correction (แก้ไขเอกสาร)',
                'color' => 'danger',
                'sequence' => 3,
            ],
            [
                'name' => 'Completed (เสร็จสิ้น)',
                'color' => 'success',
                'sequence' => 4,
            ],
            [
                'name' => 'Cannot Proceed (ไม่สามารถดำเนินการต่อได้)',
                'color' => 'dark',
                'sequence' => 5,
            ],
        ];

        foreach ($barriers as $barrier) {
            WorkflowBarrier::firstOrCreate(
                ['name' => $barrier['name']], // Check by name
                $barrier // Data to insert if not found
            );
        }
    }
}
