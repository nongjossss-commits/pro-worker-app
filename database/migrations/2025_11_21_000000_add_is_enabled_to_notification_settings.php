<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->boolean('is_enabled')->default(true);
        });

        // Seed initial data for new types
        $newTypes = [
            'pink_card_missing' => 30, // Default days, though ignored for this type
            'residence_permit_missing' => 30, // Default days, though ignored for this type
        ];

        foreach ($newTypes as $type => $days) {
            if (DB::table('notification_settings')->where('notification_type', $type)->doesntExist()) {
                DB::table('notification_settings')->insert([
                    'notification_type' => $type,
                    'days_before_expiry' => $days,
                    'is_enabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Ensure existing types are also present if table was empty
        $existingTypes = [
            'ninety_day_report',
            'passport_expiry',
            'work_permit_mou',
            'visa_expiry',
            'ci_renewal',
            'resolution_renewal',
            'new_registration_renewal',
            'employer_document_expiry',
            'employee_insurance_expiry',
        ];

        foreach ($existingTypes as $type) {
            if (DB::table('notification_settings')->where('notification_type', $type)->doesntExist()) {
                DB::table('notification_settings')->insert([
                    'notification_type' => $type,
                    'days_before_expiry' => 30,
                    'is_enabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->dropColumn('is_enabled');
        });
    }
};
