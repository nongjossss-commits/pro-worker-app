<?php

namespace Tests\Feature;

use App\Models\LaborTeam;
use App\Models\ProWorkerContract;
use App\Models\ProWorkerContractTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-10-30 "ประวัติการเบิกสัญญา": when the list is narrower than the full
 * table (phone, tablet, laptop with the sidebar) each contract is a card —
 * number + status on top, details as "label … value", the signed-copy slot
 * across the card, buttons at the bottom. The page has its own cards, so the
 * generic phone cards (which pushed the attachment slot to the right as if
 * it were a button row) are switched off for this table.
 * The layout itself was checked in a browser at 390 / 768 / 1100 / 1400 px.
 */
class LaborContractListCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_list_renders_its_own_cards_markup(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $t = ProWorkerContractTemplate::create(['name' => 'สัญญา MOU', 'file_path' => 'x.pdf', 'field_mapping' => []]);
        $team = LaborTeam::create(['name' => 'CPS', 'is_active' => true]);
        ProWorkerContract::create([
            'contract_no' => 'PWC-CARD-1', 'pro_worker_contract_template_id' => $t->id, 'issued_by' => $admin->id,
            'labor_team_id' => $team->id, 'field_values' => [], 'file_path' => 'contracts/none.pdf', 'issued_at' => now(),
        ]);

        $html = $this->actingAs($admin)->get(route('labor.contracts.index'))->assertOk()->getContent();

        $this->assertStringContainsString('class="card shadow-sm border-0 ct-list"', $html);
        $this->assertStringContainsString('<div class="table-responsive" data-mobile-table="scroll">', $html);
        $this->assertStringContainsString('@container (max-width: 1099.98px)', $html);
        foreach (['ct-cb', 'ct-no', 'ct-status', 'ct-signed', 'ct-actions'] as $cell) {
            $this->assertStringContainsString('class="' . ($cell === 'ct-no' ? 'font-monospace ' : ($cell === 'ct-actions' ? 'text-end ' : '')) . $cell . '"', $html, $cell);
        }
        $this->assertSame(5, substr_count($html, 'ct-field" data-label='));
        $this->assertStringContainsString('id="contractSelectAll"', $html);
        $this->assertStringContainsString('PWC-CARD-1', $html);
    }
}
