<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add resolution_tab_id to employees
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('resolution_tab_id')->nullable()->after('employer_id')
                  ->constrained('resolution_tabs')->nullOnDelete();
        });

        // 2. Add resolution_tab_id to registration_steps
        Schema::table('registration_steps', function (Blueprint $table) {
            $table->foreignId('resolution_tab_id')->nullable()->after('id')
                  ->constrained('resolution_tabs')->nullOnDelete();
        });

        // 3. Add resolution_tab_id to system_settings
        Schema::table('system_settings', function (Blueprint $table) {
            $table->foreignId('resolution_tab_id')->nullable()->after('id')
                  ->constrained('resolution_tabs')->nullOnDelete();
        });

        // 4. Add resolution_tab_id to notification_settings
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->foreignId('resolution_tab_id')->nullable()->after('id')
                  ->constrained('resolution_tabs')->nullOnDelete();
        });

        // 5. Create employer_resolution_tab pivot table
        Schema::create('employer_resolution_tab', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->onDelete('cascade');
            $table->foreignId('resolution_tab_id')->constrained()->onDelete('cascade');
            $table->string('resolution_status')->default('preparing');
            $table->text('resolution_note')->nullable();
            $table->timestamps();
            $table->unique(['employer_id', 'resolution_tab_id']);
        });

        // 6. Add resolution_tab_id to production_orders
        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('resolution_tab_id')->nullable()->after('employer_id')
                  ->constrained('resolution_tabs')->nullOnDelete();
        });

        // --- Backfill existing data ---

        // Get default tab IDs
        $regTabId = DB::table('resolution_tabs')->where('slug', 'default-registration')->value('id');
        $renTabId = DB::table('resolution_tabs')->where('slug', 'default-renewal')->value('id');

        // Backfill employees
        if ($regTabId) {
            DB::table('employees')
                ->whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled'])
                ->whereNull('resolution_tab_id')
                ->update(['resolution_tab_id' => $regTabId]);
        }

        if ($renTabId) {
            DB::table('employees')
                ->whereIn('status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled'])
                ->whereNull('resolution_tab_id')
                ->update(['resolution_tab_id' => $renTabId]);
        }

        // Backfill registration_steps
        if ($regTabId) {
            DB::table('registration_steps')
                ->where('type', 'registration')
                ->whereNull('resolution_tab_id')
                ->update(['resolution_tab_id' => $regTabId]);
        }

        if ($renTabId) {
            DB::table('registration_steps')
                ->where('type', 'renewal')
                ->whereNull('resolution_tab_id')
                ->update(['resolution_tab_id' => $renTabId]);
        }

        // Backfill system_settings
        if ($regTabId) {
            DB::table('system_settings')
                ->where('group', 'registration')
                ->whereNull('resolution_tab_id')
                ->update(['resolution_tab_id' => $regTabId]);
        }

        if ($renTabId) {
            DB::table('system_settings')
                ->where('group', 'renewal')
                ->whereNull('resolution_tab_id')
                ->update(['resolution_tab_id' => $renTabId]);
        }

        // Backfill notification_settings
        if ($regTabId) {
            DB::table('notification_settings')
                ->where('notification_type', 'like', 'registration%')
                ->whereNull('resolution_tab_id')
                ->update(['resolution_tab_id' => $regTabId]);
        }

        if ($renTabId) {
            DB::table('notification_settings')
                ->where('notification_type', 'like', 'renewal%')
                ->whereNull('resolution_tab_id')
                ->update(['resolution_tab_id' => $renTabId]);
        }

        // Backfill production_orders
        if ($regTabId) {
            DB::table('production_orders')
                ->whereIn('status', ['registration_resolution', 'registration_resolution_cancelled'])
                ->whereNull('resolution_tab_id')
                ->update(['resolution_tab_id' => $regTabId]);
        }

        if ($renTabId) {
            DB::table('production_orders')
                ->whereIn('status', ['renewal_resolution', 'renewal_resolution_cancelled'])
                ->whereNull('resolution_tab_id')
                ->update(['resolution_tab_id' => $renTabId]);
        }

        // Backfill employer_resolution_tab pivot from existing employer columns
        $employers = DB::table('employers')
            ->whereNotNull('registration_resolution_status')
            ->get(['id', 'registration_resolution_status', 'registration_resolution_note']);

        if ($regTabId) {
            foreach ($employers as $emp) {
                DB::table('employer_resolution_tab')->insertOrIgnore([
                    'employer_id' => $emp->id,
                    'resolution_tab_id' => $regTabId,
                    'resolution_status' => $emp->registration_resolution_status ?? 'preparing',
                    'resolution_note' => $emp->registration_resolution_note ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Also backfill renewal employer data
        if ($renTabId) {
            $renewalEmployers = DB::table('employers')
                ->join('employees', 'employers.id', '=', 'employees.employer_id')
                ->whereIn('employees.status', ['renewal_pending', 'renewal_completed', 'renewal_cancelled'])
                ->distinct()
                ->select('employers.id', 'employers.renewal_resolution_note')
                ->get();

            foreach ($renewalEmployers as $emp) {
                DB::table('employer_resolution_tab')->insertOrIgnore([
                    'employer_id' => $emp->id,
                    'resolution_tab_id' => $renTabId,
                    'resolution_status' => 'preparing',
                    'resolution_note' => $emp->renewal_resolution_note ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_resolution_tab');

        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolution_tab_id');
        });

        Schema::table('notification_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolution_tab_id');
        });

        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolution_tab_id');
        });

        Schema::table('registration_steps', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolution_tab_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolution_tab_id');
        });
    }
};
