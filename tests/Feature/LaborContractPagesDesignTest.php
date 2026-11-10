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
 * 2026-11-11: Pro Worker contract pages got a fresher look
 * (public/css/proworker-contracts.css, wrapper .pwc). Visual only — every
 * page still renders with its usual content; checked in a browser in light /
 * dark mode at 390, 1280, 1366, 1440 and 1920 px.
 */
class LaborContractPagesDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_contract_page_renders_with_the_new_design(): void
    {
        Role::findOrCreate('super-admin');
        $team = LaborTeam::create(['name' => 'CPS', 'is_active' => true]);
        $admin = User::factory()->create(['labor_team_id' => $team->id]);
        $admin->assignRole('super-admin');
        $t = ProWorkerContractTemplate::create(['name' => 'สัญญา MOU', 'file_path' => 'x.pdf', 'field_mapping' => []]);
        $contract = ProWorkerContract::create([
            'contract_no' => 'PWC-DESIGN-1', 'pro_worker_contract_template_id' => $t->id, 'issued_by' => $admin->id,
            'labor_team_id' => $team->id, 'field_values' => [], 'file_path' => 'contracts/none.pdf', 'issued_at' => now(),
            'employer_name_snapshot' => 'บริษัท ทดสอบ จำกัด',
        ]);
        $this->actingAs($admin);

        $pages = [
            route('labor.contracts.index'),
            route('labor.contracts.create'),
            route('labor.contracts.show', $contract),
            route('labor.contracts.edit', $contract),
            route('labor.contracts.history', $contract),
            route('labor.contracts.verify.form'),
            route('labor.contract-templates.index'),
            route('labor.contract-reports.index'),
        ];
        foreach ($pages as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('css/proworker-contracts.css', $html, $url);
            $this->assertStringContainsString('container-fluid pwc', $html, $url);
        }

        $list = $this->get(route('labor.contracts.index'))->getContent();
        $this->assertStringContainsString('class="pwc-no"', $list);
        $this->assertStringContainsString('pwc-status is-wait', $list);
        $this->assertStringContainsString('class="pwc-avatar"', $list);
        $this->assertStringContainsString('>ท</span>', $list, 'initial skips the company prefix');

        $verify = $this->post(route('labor.contracts.verify'), ['contract_no' => 'PWC-DESIGN-1'])->assertOk()->getContent();
        $this->assertStringContainsString('pwc-result is-ok', $verify);
        $this->assertStringContainsString('PWC-DESIGN-1', $verify);
    }

    public function test_dark_mode_pairs_exist_for_the_fixed_colours(): void
    {
        $dark = file_get_contents(public_path('css/theme-dark.css'));
        $this->assertStringContainsString('html[data-bs-theme="dark"] .pwc {', $dark);
    }
}
