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
        // The table-creation migration for labor_bill_payments is timestamped after this
        // one, so on a fresh install it may not exist yet.
        if (!Schema::hasTable('labor_bill_payments')) {
            Schema::create('labor_bill_payments', function (Blueprint $table) {
                $table->id();

                $table->foreignId('labor_bill_id')->constrained('labor_bills')->cascadeOnDelete();

                $table->decimal('amount', 12, 2);
                $table->date('paid_at');
                $table->enum('payment_method', ['cash', 'transfer', 'promptpay', 'other'])->default('transfer');
                $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
                $table->string('slip_path')->nullable();

                $table->foreignId('wht_certificate_id')->nullable()
                    ->constrained('labor_wht_certificates')->nullOnDelete();

                $table->string('receipt_no', 30)->nullable()->unique();
                $table->timestamp('receipt_generated_at')->nullable();
                $table->string('receipt_pdf_path')->nullable();

                $table->text('notes')->nullable();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['labor_bill_id', 'paid_at']);
            });
        }

        if (!Schema::hasColumn('labor_bill_payments', 'reference_no')) {
            Schema::table('labor_bill_payments', function (Blueprint $table) {
                $table->string('reference_no')->nullable()->after('payment_method');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('labor_bill_payments', function (Blueprint $table) {
            $table->dropColumn('reference_no');
        });
    }
};
