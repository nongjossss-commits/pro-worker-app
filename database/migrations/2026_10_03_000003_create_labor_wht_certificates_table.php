<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ใบหัก ณ ที่จ่าย for the Pro Walker Labor module — mirrors the main app's
 * `wht_certificates` schema (see 2026_06_05_100002_create_wht_certificates_table.php).
 * Usually `type = received`: the team/customer paying Labor withholds tax
 * and gives Labor the certificate. Kept `issued` in the enum for parity in
 * case Labor ever pays a sub-contractor directly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labor_wht_certificates', function (Blueprint $table) {
            $table->id();

            $table->string('cert_no', 40)->unique();
            $table->enum('type', ['issued', 'received'])->default('received');
            $table->enum('wht_type', ['pnd3', 'pnd53']);

            $table->unsignedSmallInteger('tax_period_year');
            $table->unsignedTinyInteger('tax_period_month');

            $table->foreignId('labor_bill_id')->nullable()->constrained('labor_bills')->nullOnDelete();

            $table->string('payer_name');
            $table->string('payer_tax_id', 15)->nullable();
            $table->string('payee_name');
            $table->string('payee_tax_id', 15)->nullable();

            $table->string('income_type', 50)->nullable();

            $table->decimal('amount_paid', 15, 2);
            $table->decimal('wht_rate', 5, 2);
            $table->decimal('wht_amount', 15, 2);
            $table->date('paid_at');

            $table->string('certificate_path')->nullable();

            $table->enum('status', ['draft', 'issued', 'submitted'])->default('draft');

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'tax_period_year', 'tax_period_month'], 'labor_wht_certs_type_period_idx');
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_wht_certificates');
    }
};
