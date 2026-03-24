<?php

namespace Database\Migrations;

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
        Schema::create('sales_lead_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_lead_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id')->nullable()->comment('Linked to real employee if selected from search');

            // Replicating Employee fields, but mostly nullable
            $table->string('employeeNameEn'); // Only required field
            $table->string('employeeNameTh')->nullable();
            $table->string('employeeGender')->nullable();
            $table->date('employeeDob')->nullable();
            $table->string('employeePassport')->nullable();
            $table->string('employeeWorkPermit')->nullable();
            $table->string('insurance_type')->nullable(); // 'ประกันเอกชน', 'ประกันโรงพยาบาล', 'ประกันสังคม'
            $table->string('pinkCardNo')->nullable();

            // We won't mirror all fields for the temporary state to keep it light,
            // just the essentials needed for quotation and identification.
            // When transitioned, if it's an existing employee, we use the real one.
            // If it's a new employee, they'll fill out the rest in the real module or here if they choose.
            // Wait, the user said: "แนบรูปภาพได้ด้วยแนบไฟล์ได้ด้วยทุกอย่างได้เหมือนกันหมดเลยแต่ก็บังคับให้กรอกแค่ชื่อภาษาอังกฤษอย่างเดียวเท่านั้น"
            // So we need more fields to match the real employee creation if they want to fill it out completely here.
            $table->string('employeeNationality')->nullable();
            $table->string('workPermitMOUGroup')->nullable();
            $table->date('passportExpiryDate')->nullable();
            $table->date('workPermitExpiryDate')->nullable();
            $table->string('system_employee_id')->nullable();
            $table->string('employer_employee_id')->nullable();
            $table->string('name_list_number')->nullable();
            $table->text('employeeAddress')->nullable();
            $table->string('employeePhone')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_lead_employees');
    }
};
