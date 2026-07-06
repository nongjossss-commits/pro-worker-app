<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track the real physical dimensions (in mm) of the uploaded company
 * stamp so the PDF renderer can place the stamp at its true size,
 * centered inside the template area, instead of stretching to fill.
 *
 * A round company seal that's 3cm wide should stay 30mm × 30mm on
 * paper regardless of how big the template box is.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            $table->decimal('employer_stamp_width_mm', 6, 2)->nullable()->after('employer_stamp_path');
            $table->decimal('employer_stamp_height_mm', 6, 2)->nullable()->after('employer_stamp_width_mm');
        });

        if (Schema::hasTable('importers')) {
            Schema::table('importers', function (Blueprint $table) {
                if (Schema::hasColumn('importers', 'importer_stamp_path')) {
                    $table->decimal('importer_stamp_width_mm', 6, 2)->nullable()->after('importer_stamp_path');
                    $table->decimal('importer_stamp_height_mm', 6, 2)->nullable()->after('importer_stamp_width_mm');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            $table->dropColumn(['employer_stamp_width_mm', 'employer_stamp_height_mm']);
        });

        if (Schema::hasTable('importers')) {
            Schema::table('importers', function (Blueprint $table) {
                if (Schema::hasColumn('importers', 'importer_stamp_width_mm')) {
                    $table->dropColumn(['importer_stamp_width_mm', 'importer_stamp_height_mm']);
                }
            });
        }
    }
};
