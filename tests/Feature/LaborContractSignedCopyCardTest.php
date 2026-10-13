<?php

namespace Tests\Feature;

use App\Models\ProWorkerContract;
use App\Models\ProWorkerContractTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pro Worker contracts: the signed-copy attachment uses the same attachment
 * card as the main app — plain picker (no scanner) on the list rows, the
 * full scan/edit card on the contract page — and still posts `signed_copy`.
 */
class LaborContractSignedCopyCardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Role::findOrCreate('super-admin');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    private function contract(?string $signed, int $n): ProWorkerContract
    {
        $template = ProWorkerContractTemplate::firstOrCreate(['name' => 'แม่แบบ'], ['file_path' => 'x.pdf']);
        return ProWorkerContract::create([
            'contract_no' => 'PW-CARD-' . $n,
            'pro_worker_contract_template_id' => $template->id,
            'issued_by' => $this->admin->id,
            'employer_name_snapshot' => 'นายจ้าง ' . $n,
            'file_path' => 'contracts/c.pdf',
            'signed_copy_path' => $signed,
            'issued_at' => now(),
        ]);
    }

    public function test_list_rows_use_plain_cards_and_the_contract_page_keeps_the_scanner(): void
    {
        $signed = $this->contract('proworker_contracts/signed_copies/a.pdf', 1);
        $pending = $this->contract(null, 2);

        $list = $this->actingAs($this->admin)->get(route('labor.contracts.index'))->assertOk()->getContent();
        $this->assertSame(2, substr_count($list, 'data-att-card>'));
        $this->assertSame(1, substr_count($list, 'att-card has-file'));
        $this->assertStringContainsString('id="signed_copy_' . $pending->id . '"', $list);
        // No scanner on list rows (its multi-step flow lost files there).
        $this->assertStringNotContainsString("inputId: 'signed_copy_", $list);
        $this->assertStringNotContainsString('interceptFileSelect', $list);
        $this->assertStringContainsString('att-when-pending', $list);
        // A wrongly picked file can be dropped again: Cancel next to Attach/Replace on every row.
        $this->assertSame(2, substr_count($list, 'data-att-clear>'));

        $show = $this->actingAs($this->admin)->get(route('labor.contracts.show', $signed))->assertOk()->getContent();
        $this->assertSame(1, substr_count($show, 'data-att-card>'));
        $this->assertStringContainsString("inputId: 'signed_copy'", $show); // camera / edit still there
    }

    public function test_uploading_from_the_card_still_saves_the_signed_copy(): void
    {
        $contract = $this->contract(null, 3);

        $this->actingAs($this->admin)
            ->from(route('labor.contracts.index'))
            ->put(route('labor.contracts.signed-copy.update', $contract), [
                'signed_copy' => UploadedFile::fake()->create('signed.pdf', 50, 'application/pdf'),
            ])->assertRedirect(route('labor.contracts.index'));

        $this->assertNotNull($contract->fresh()->signed_copy_path);
        Storage::disk('public')->assertExists($contract->fresh()->signed_copy_path);
    }
}
