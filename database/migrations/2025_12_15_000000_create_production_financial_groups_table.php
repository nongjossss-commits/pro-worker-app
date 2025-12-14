<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ProductionOrder;
use App\Models\ProductionFinancialGroup;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the Groups Table
        Schema::create('production_financial_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->onDelete('cascade');
            $table->string('name')->default('General');
            $table->json('financial_data')->nullable(); // Pricing tiers, tax settings, etc.
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Add Group ID to Transactions
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->foreignId('production_financial_group_id')->nullable()->after('production_order_id')->constrained('production_financial_groups')->onDelete('cascade');
        });

        // 3. Migrate Data
        // We use raw DB queries or models if safe. Using DB to avoid model caching issues during migration.
        $orders = DB::table('production_orders')->get();

        foreach ($orders as $order) {
            // Create Default Group
            $groupId = DB::table('production_financial_groups')->insertGetId([
                'production_order_id' => $order->id,
                'name' => 'General', // Default name
                'financial_data' => $order->financial_data,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update associated transactions
            DB::table('financial_transactions')
                ->where('production_order_id', $order->id)
                ->update(['production_financial_group_id' => $groupId]);
        }
    }

    public function down(): void
    {
        // Reverse migration is tricky because we lose the separation, but we can try to restore the first group's data to the order.
        // For simplicity in this dev environment, we will just drop the columns/tables. Data loss on rollback is expected here if multiple tabs were created.

        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropForeign(['production_financial_group_id']);
            $table->dropColumn('production_financial_group_id');
        });

        Schema::dropIfExists('production_financial_groups');
    }
};
