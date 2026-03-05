<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductionOrder;
use App\Models\ProductionItem;
use App\Models\ProductionFinancialGroup;
use App\Models\FinancialTransaction;
use App\Models\Employer;
use App\Models\Employee;

class FinanceStatusSeeder extends Seeder
{
    public function run()
    {
        $employer = Employer::first();
        $employees = $employer->employees()->take(4)->get();

        $order = ProductionOrder::create([
            'employer_id' => $employer->id,
            'status' => 'registration_resolution',
            'type' => 'employer',
            'project_name' => 'Test Registration Resolution',
            'financial_data' => []
        ]);

        $group = ProductionFinancialGroup::create([
            'production_order_id' => $order->id,
            'employer_id' => $employer->id,
            'name' => 'General',
            'financial_data' => [
                'pricing_mode' => 'per_head',
                'pricing_tiers' => [
                    [
                        'name' => 'Default Tier',
                        'price' => 5000,
                        'item_ids' => [strval($employees[0]->id), strval($employees[1]->id), strval($employees[2]->id), strval($employees[3]->id)]
                    ]
                ]
            ]
        ]);

        $items = [];
        foreach ($employees as $emp) {
            $items[] = ProductionItem::create([
                'production_order_id' => $order->id,
                'employee_id' => $emp->id,
                'status' => 'pending'
            ]);
            $emp->status = 'registration_pending';
            $emp->save();
        }

        // Emp 1: Installment Created
        $tx1 = FinancialTransaction::create([
            'production_order_id' => $order->id,
            'production_financial_group_id' => $group->id,
            'type' => 'installment',
            'amount' => 5000,
            'paid_amount' => 0,
            'status' => 'pending'
        ]);
        \DB::table('financial_transaction_items')->insert(['financial_transaction_id' => $tx1->id, 'production_item_id' => $items[1]->id]);

        // Emp 2: Partial
        $tx2 = FinancialTransaction::create([
            'production_order_id' => $order->id,
            'production_financial_group_id' => $group->id,
            'type' => 'installment',
            'amount' => 5000,
            'paid_amount' => 2000,
            'status' => 'partial'
        ]);
        \DB::table('financial_transaction_items')->insert(['financial_transaction_id' => $tx2->id, 'production_item_id' => $items[2]->id]);

        // Emp 3: Paid
        $tx3 = FinancialTransaction::create([
            'production_order_id' => $order->id,
            'production_financial_group_id' => $group->id,
            'type' => 'installment',
            'amount' => 5000,
            'paid_amount' => 5000,
            'status' => 'paid'
        ]);
        \DB::table('financial_transaction_items')->insert(['financial_transaction_id' => $tx3->id, 'production_item_id' => $items[3]->id]);
    }
}
