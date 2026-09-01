<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets a Registration-Resolution employee (status stays registration_*,
     * resolution_tab_id stays untouched) also become fully usable inside a
     * matching Renewal tab, without ever overwriting their real Employee
     * row — see EmployeeObserver::syncRenewalStatus() and
     * RenewalController::configureExpiry() for how a row here gets created,
     * and RenewalController's finalizeLink/cancelLink/restoreLink/
     * updateLinkProgress for how it's acted on independently of the
     * employee's real status. Everything else about the employee (photo,
     * passport, appointment, biometrics, insurance...) is intentionally NOT
     * duplicated here — it's read straight off the real Employee row.
     */
    public function up(): void
    {
        Schema::create('employee_renewal_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resolution_tab_id')->constrained('resolution_tabs')->cascadeOnDelete();
            $table->string('status')->default('renewal_pending');
            $table->timestamp('resolution_completed_at')->nullable();
            $table->boolean('resolution_settings_applied')->default(false);
            $table->timestamps();

            $table->unique(['employee_id', 'resolution_tab_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_renewal_links');
    }
};
