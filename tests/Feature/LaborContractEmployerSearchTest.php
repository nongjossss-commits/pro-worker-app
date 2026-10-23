<?php

namespace Tests\Feature;

use App\Models\LaborTeam;
use App\Models\ProWorkerContract;
use App\Models\ProWorkerContractTemplate;
use App\Models\User;
use App\Services\ProWorkerContractEmployerFields;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * "ประวัติการเบิกสัญญา": the employer's name and ID number come from the
 * template's own form fields (picked by a Super Admin, or guessed from the
 * labels) — shown in the Employer column, searchable, with a per-team
 * count of the employer's contracts.
 */
class LaborContractEmployerSearchTest extends TestCase
{
    use RefreshDatabase;

    private function template(): ProWorkerContractTemplate
    {
        return ProWorkerContractTemplate::create(['name' => 'สัญญา MOU', 'file_path' => 'x.pdf', 'field_mapping' => [
            ['type' => 'text', 'key' => 'f_company', 'label' => 'ชื่อนายจ้าง / สถานประกอบการ'],
            ['type' => 'text', 'key' => 'f_company_addr', 'label' => 'ที่อยู่นายจ้าง'],
            ['type' => 'text', 'key' => 'f_tax', 'label' => 'เลขประจำตัวผู้เสียภาษีนายจ้าง'],
            ['type' => 'text', 'key' => 'f_other', 'label' => 'ชื่อผู้ประสานงาน'],
        ]]);
    }

    private function contract(ProWorkerContractTemplate $t, User $by, LaborTeam $team, string $no, array $values, ?string $signed = null): ProWorkerContract
    {
        return ProWorkerContract::create([
            'contract_no' => $no, 'pro_worker_contract_template_id' => $t->id, 'issued_by' => $by->id,
            'labor_team_id' => $team->id, 'field_values' => $values, 'file_path' => 'contracts/none.pdf',
            'signed_copy_path' => $signed, 'issued_at' => now(),
        ]);
    }

    public function test_fields_are_guessed_synced_and_searchable_with_a_team_summary(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $t = $this->template();
        $cps = LaborTeam::create(['name' => 'CPS', 'is_active' => true]);
        $took = LaborTeam::create(['name' => 'Tookta', 'is_active' => true]);

        $fields = app(ProWorkerContractEmployerFields::class);
        $this->assertEquals(['name' => 'f_company', 'ref' => 'f_tax', 'nameAuto' => true, 'refAuto' => true], $fields->keys($t));

        // contracts issued while the snapshot stayed empty ("-")
        $this->contract($t, $admin, $cps, 'PWC-T-1', ['f_company' => 'บริษัท ฟ้าใส จำกัด', 'f_tax' => '0105561234567']);
        $this->contract($t, $admin, $cps, 'PWC-T-2', ['f_company' => 'บริษัท ฟ้าใส จำกัด', 'f_tax' => '0105561234567'], 'signed/x.pdf');
        $this->contract($t, $admin, $took, 'PWC-T-3', ['f_company' => 'บริษัท ฟ้าใส จำกัด', 'f_tax' => '0105561234567']);
        $this->contract($t, $admin, $took, 'PWC-T-4', ['f_company' => 'หจก. ทะเลทอง', 'f_tax' => '0993000111222']);

        $this->assertSame(4, $fields->syncTemplate($t));
        $this->assertSame('บริษัท ฟ้าใส จำกัด', ProWorkerContract::where('contract_no', 'PWC-T-1')->value('employer_name_snapshot'));
        $this->assertSame('0105561234567', ProWorkerContract::where('contract_no', 'PWC-T-1')->value('employer_ref_snapshot'));

        // search by name → 3 contracts, CPS 2 (1 signed) · Tookta 1
        $res = $this->actingAs($admin)->get(route('labor.contracts.index', ['q' => 'ฟ้าใส']))->assertOk();
        $html = $res->getContent();
        $this->assertTrue(str_contains($html, 'PWC-T-1') && str_contains($html, 'PWC-T-3'));
        $this->assertFalse(str_contains($html, 'PWC-T-4'));
        $summary = $res->viewData('searchSummary');
        $this->assertSame([3, 1], [$summary['total'], $summary['signed']]);
        $this->assertSame([['name' => 'CPS', 'total' => 2, 'signed' => 1], ['name' => 'Tookta', 'total' => 1, 'signed' => 0]], $summary['teams']);

        // search by employer ID number
        $html = $this->actingAs($admin)->get(route('labor.contracts.index', ['q' => '0993000']))->assertOk()->getContent();
        $this->assertTrue(str_contains($html, 'PWC-T-4') && !str_contains($html, 'PWC-T-1'));

        // Super Admin picks another field → contracts re-synced
        $this->actingAs($admin)->put(route('labor.contract-templates.employer-fields.update', $t), [
            'name_field' => 'f_other', 'ref_field' => ProWorkerContractEmployerFields::NONE,
        ])->assertRedirect();
        $t->refresh();
        $this->assertEquals(['name' => 'f_other', 'ref' => null, 'nameAuto' => false, 'refAuto' => false], $fields->keys($t));
        $this->assertNull(ProWorkerContract::where('contract_no', 'PWC-T-1')->value('employer_name_snapshot'));
        $this->assertNull(ProWorkerContract::where('contract_no', 'PWC-T-1')->value('employer_ref_snapshot'));

        // unknown field refused
        $this->actingAs($admin)->put(route('labor.contract-templates.employer-fields.update', $t), [
            'name_field' => 'not_a_field', 'ref_field' => 'auto',
        ])->assertSessionHasErrors('name_field');

        // the sync is search data only — not an edit in the contract's history
        $this->assertSame(0, \App\Models\ActivityLog::where('model_type', ProWorkerContract::class)->where('action', 'update')->count());
    }
}
