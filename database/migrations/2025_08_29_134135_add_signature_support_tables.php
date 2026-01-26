<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update Employers Table
        Schema::table('employers', function (Blueprint $table) {
            $signerNameEnExists = Schema::hasColumn('employers', 'signerNameEn');

            // Track availability to handle both existing columns and columns added in this transaction
            // for the 'after' clause chaining.
            $signer_2_name_th_available = Schema::hasColumn('employers', 'signer_2_name_th');

            if (!$signer_2_name_th_available) {
                if ($signerNameEnExists) {
                    $table->string('signer_2_name_th')->nullable()->after('signerNameEn');
                } else {
                    $table->string('signer_2_name_th')->nullable();
                }
                $signer_2_name_th_available = true;
            }

            $signer_2_name_en_available = Schema::hasColumn('employers', 'signer_2_name_en');

            if (!$signer_2_name_en_available) {
                if ($signer_2_name_th_available) {
                    $table->string('signer_2_name_en')->nullable()->after('signer_2_name_th');
                } else {
                    $table->string('signer_2_name_en')->nullable();
                }
                $signer_2_name_en_available = true;
            }

            $signature_1_path_available = Schema::hasColumn('employers', 'signature_1_path');

            if (!$signature_1_path_available) {
                if ($signer_2_name_en_available) {
                    $table->string('signature_1_path')->nullable()->after('signer_2_name_en');
                } else {
                    $table->string('signature_1_path')->nullable();
                }
                $signature_1_path_available = true;
            }

            if (!Schema::hasColumn('employers', 'signature_2_path')) {
                if ($signature_1_path_available) {
                    $table->string('signature_2_path')->nullable()->after('signature_1_path');
                } else {
                    $table->string('signature_2_path')->nullable();
                }
            }
        });

        // 2. Update Employees Table
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'signature_path')) {
                if (Schema::hasColumn('employees', 'status')) {
                    $table->string('signature_path')->nullable()->after('status');
                } else {
                    $table->string('signature_path')->nullable();
                }
            }
        });

        // 3. Create Global Witnesses Table
        if (!Schema::hasTable('global_witnesses')) {
            Schema::create('global_witnesses', function (Blueprint $table) {
                $table->id();
                $table->string('alias')->unique(); // 'witness_1', 'witness_2', etc.
                $table->string('name_th')->nullable();
                $table->string('name_en')->nullable();
                $table->string('signature_path')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_witnesses');

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'signature_path')) {
                $table->dropColumn(['signature_path']);
            }
        });

        Schema::table('employers', function (Blueprint $table) {
            $columns = [
                'signer_2_name_th',
                'signer_2_name_en',
                'signature_1_path',
                'signature_2_path'
            ];

            $toDrop = [];
            foreach ($columns as $column) {
                if (Schema::hasColumn('employers', $column)) {
                    $toDrop[] = $column;
                }
            }

            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }
};
