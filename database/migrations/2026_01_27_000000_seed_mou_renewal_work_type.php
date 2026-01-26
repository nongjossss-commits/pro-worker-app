<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\WorkType;
use App\Models\WorkTypeStep;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create the new Work Type
        $workType = WorkType::create([
            'name' => 'งานต่ออายุ MOU', // MOU Renewal
            'slug' => 'mou_renewal',
            'is_system' => true,
            'order' => 4, // After mou_import
        ]);

        // 2. Define Default Steps
        $steps = [
            'รับเอกสาร',      // Receive Documents
            'ยื่นเรื่อง',        // Submission
            'รออนุมัติ',       // Waiting for Approval
            'รับเล่มคืน',       // Receive Book Back
            'แจ้งผล',          // Notify Result
        ];

        // 3. Create Steps
        foreach ($steps as $index => $stepName) {
            WorkTypeStep::create([
                'work_type_id' => $workType->id,
                'name' => $stepName,
                'order' => $index + 1
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $workType = WorkType::where('slug', 'mou_renewal')->first();
        if ($workType) {
            $workType->steps()->delete();
            $workType->delete();
        }
    }
};
