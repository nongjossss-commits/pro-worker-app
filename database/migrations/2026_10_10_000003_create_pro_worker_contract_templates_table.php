<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Templates for the Pro Worker <-> Employer service contract — deliberately
 * a SEPARATE table from pdf_templates (see app/Models/PdfTemplate.php).
 * That table's field_mapping binds fields to real Employee/Employer data
 * (via PdfGeneratorService::resolveValue()); here every field (except the
 * fixed address pair) is a free-form label the admin invents when building
 * the template, filled in ad-hoc by whoever issues a contract — a
 * different enough shape to warrant its own table rather than overloading
 * pdf_templates.type with a third value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pro_worker_contract_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_path');
            $table->json('field_mapping')->nullable();
            $table->json('meta_data')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pro_worker_contract_templates');
    }
};
