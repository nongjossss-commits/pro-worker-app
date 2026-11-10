<?php

namespace Tests\Feature\Production;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\FinancialTransaction;
use App\Models\ProductionFinancialGroup;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-11-10: an employee put on a per-head bill keeps the price of that bill
 * even when the pricing tier is later changed or deleted; only taking them
 * off the bill (or deleting the bill) brings back the current tier price.
 * A fully paid bill cannot be deleted — corrections go through a credit note.
 */
class BilledPriceSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private ProductionOrder $order;
    private ProductionFinancialGroup $group;
    /** @var int[] */
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $this->actingAs($user);
        $this->withoutMiddleware(\App\Http\Middleware\CheckMenuAccess::class);

        $employer = Employer::factory()->create();
        $this->order = ProductionOrder::create([
            'employer_id' => $employer->id,
            'work_type_id' => WorkType::create(['name' => 'Job', 'slug' => 'job-' . uniqid()])->id,
            'status' => 'active',
        ]);
        foreach (range(1, 3) as $i) {
            $this->ids[] = ProductionItem::create([
                'production_order_id' => $this->order->id,
                'employee_id' => Employee::factory()->create(['employer_id' => $employer->id, 'employeeNameEn' => "PERSON {$i}"])->id,
                'status' => 'pending',
            ])->id;
        }
        $this->group = $this->order->financialGroups()->create(['name' => 'G', 'financial_data' => [
            'pricing_mode' => 'per_head',
            'pricing_tiers' => [['price' => 500, 'count' => 3, 'item_ids' => $this->ids]],
        ]]);
    }

    private function setTierPrice(float $price, ?array $ids = null): void
    {
        $data = $this->group->fresh()->financial_data;
        $data['pricing_tiers'] = [['price' => $price, 'count' => count($ids ?? $this->ids), 'item_ids' => $ids ?? $this->ids]];
        $this->group->update(['financial_data' => $data]);
    }

    private function createBill(array $itemIds, float $amount): FinancialTransaction
    {
        $id = $this->postJson("/production/{$this->order->id}/transactions", [
            'amount' => $amount, 'type' => 'installment', 'financial_group_id' => $this->group->id, 'item_ids' => $itemIds,
        ])->assertOk()->json('transaction.id');

        return FinancialTransaction::findOrFail($id);
    }

    private function billedPrices(FinancialTransaction $bill): array
    {
        return $bill->fresh()->items->mapWithKeys(fn ($i) => [$i->id => $i->pivot->unit_price === null ? null : (float) $i->pivot->unit_price])->all();
    }

    public function test_billed_employees_keep_their_price_when_the_tier_changes_or_is_deleted(): void
    {
        $bill = $this->createBill([$this->ids[0], $this->ids[1]], 1000);
        $this->assertSame([$this->ids[0] => 500.0, $this->ids[1] => 500.0], $this->billedPrices($bill));

        // tier price raised, then the tier deleted — the bill's prices stay
        $this->setTierPrice(800);
        $this->assertSame([$this->ids[0] => 500.0, $this->ids[1] => 500.0], $this->billedPrices($bill));
        $this->setTierPrice(800, [$this->ids[1], $this->ids[2]]);

        // editing the bill and adding a 3rd person: the new one takes today's price, the others keep theirs
        $this->putJson("/production/transactions/{$bill->id}", ['amount' => 1800, 'item_ids' => [$this->ids[0], $this->ids[1], $this->ids[2]]])->assertOk();
        $this->assertSame([$this->ids[0] => 500.0, $this->ids[1] => 500.0, $this->ids[2] => 800.0], $this->billedPrices($bill));

        // taking someone off the bill drops their snapshot; putting them back = today's price
        $this->putJson("/production/transactions/{$bill->id}", ['item_ids' => [$this->ids[0], $this->ids[2]]])->assertOk();
        $this->putJson("/production/transactions/{$bill->id}", ['item_ids' => [$this->ids[0], $this->ids[1], $this->ids[2]]])->assertOk();
        $this->assertSame(800.0, $this->billedPrices($bill)[$this->ids[1]]);
    }

    public function test_documents_print_the_billed_price_not_the_current_tier(): void
    {
        $bill = $this->createBill([$this->ids[0]], 500);
        $this->setTierPrice(900);

        $html = $this->get("/production/{$this->order->id}/documents/invoice?transaction_ids={$bill->id}&include_employee_list=1")->assertOk()->getContent();
        $this->assertStringContainsString('500.00', $html);
        $this->assertStringNotContainsString('900.00', $html);
    }

    public function test_deleting_the_bill_returns_employees_to_the_current_price(): void
    {
        $bill = $this->createBill([$this->ids[0]], 500);
        $this->setTierPrice(700);
        $this->deleteJson("/production/transactions/{$bill->id}")->assertOk();

        $again = $this->createBill([$this->ids[0]], 700);
        $this->assertSame([$this->ids[0] => 700.0], $this->billedPrices($again));
    }

    public function test_a_zero_tier_price_is_not_frozen(): void
    {
        $this->setTierPrice(0);
        $bill = $this->createBill([$this->ids[0]], 0);
        $this->assertSame([$this->ids[0] => null], $this->billedPrices($bill), 'price not set yet → keeps following the tier');
    }

    public function test_a_fully_paid_bill_cannot_be_deleted(): void
    {
        $bill = $this->createBill([$this->ids[0]], 500);
        $this->postJson("/production/transactions/{$bill->id}/payments", ['amount' => 500, 'paid_at' => now()->toDateString(), 'notes' => 'cash'])->assertOk();
        $this->assertSame('paid', $bill->fresh()->status);

        $res = $this->deleteJson("/production/transactions/{$bill->id}")->assertStatus(422);
        $this->assertStringContainsString('credit note', $res->json('message'));
        $this->assertNotNull($bill->fresh());

        // a bill that is only partly paid can still be deleted (as before)
        $partial = $this->createBill([$this->ids[1]], 500);
        $this->postJson("/production/transactions/{$partial->id}/payments", ['amount' => 200, 'paid_at' => now()->toDateString(), 'notes' => 'cash'])->assertOk();
        $this->deleteJson("/production/transactions/{$partial->id}")->assertOk();
    }
}
