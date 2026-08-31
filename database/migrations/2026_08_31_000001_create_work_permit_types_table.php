<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * "ประเภทใบอนุญาตทำงาน" (Work Permit / MOU Group type) — previously a fixed
 * list of 5 hardcoded <option> values duplicated across ~20 files. This
 * table lets Super Admin add/rename/delete types going forward, WITHOUT
 * touching how the value itself is stored — employees.workPermitMOUGroup
 * (and sales_lead_employees.workPermitMOUGroup) stay plain string columns,
 * completely unchanged. Renaming a type here cascades to every employee
 * currently using the old name (see WorkPermitTypeController::update()),
 * so exports always show the corrected name, per the user's requirement.
 *
 * `slug` is a stable identifier set once at creation and never changed by
 * a rename — CheckExpiries.php classifies employees into notification
 * categories by slug (not by the editable display name), so a Super Admin
 * renaming "MOU" to something else doesn't silently break that
 * classification.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_permit_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // Seed the 4 values that were previously hardcoded, with stable
        // slugs matching what CheckExpiries.php's classification expects.
        // "อื่นๆ" (Other) is intentionally NOT seeded here — it stays a
        // permanent, non-editable fallback baked into the forms alongside
        // workPermitMOUGroupOther's free-text field, structurally
        // different from a normal named type.
        $now = now();
        DB::table('work_permit_types')->insert([
            ['name' => 'MOU', 'slug' => 'mou', 'sort_order' => 1, 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'MOU 2 ปีหลัง', 'slug' => 'mou-2-years-later', 'sort_order' => 2, 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'มติต่ออายุในประเทศ', 'slug' => 'resolution-renewal-domestic', 'sort_order' => 3, 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'มติขึ้นทะเบียน', 'slug' => 'resolution-registration', 'sort_order' => 4, 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('work_permit_types');
    }
};
