<?php

namespace Tests\Feature;

use App\Http\Controllers\Labor\LaborContractController;
use App\Models\ProWorkerContractTemplate;
use App\Services\ProWorkerFormFieldsResolver;
use Tests\TestCase;

/**
 * Pro Worker contract issuance: every address after the first (form order)
 * can be ticked "same as the main address" — copied in the browser and again
 * on the server (LaborContractController::applySameAsBaseAddress()).
 */
class ProWorkerSameAddressTest extends TestCase
{
    private function template(): ProWorkerContractTemplate
    {
        // Main (registered) address first in form order, work-site second, a third one last.
        return new ProWorkerContractTemplate(['name' => 'T', 'field_mapping' => [
            ['key' => 'work_th', 'type' => 'address_th', 'label' => 'ที่อยู่สถานที่ทำงาน', 'addressGroup' => 'gWork', 'formOrder' => 5],
            ['key' => 'work_en', 'type' => 'address_en', 'label' => 'Work address', 'addressGroup' => 'gWork', 'formOrder' => 5],
            ['key' => 'home_th', 'type' => 'address_th', 'label' => 'ที่อยู่ตามทะเบียนบ้าน', 'addressGroup' => 'gHome', 'formOrder' => 1],
            ['key' => 'home_en', 'type' => 'address_en', 'label' => 'Registered address', 'addressGroup' => 'gHome', 'formOrder' => 1],
            ['key' => 'other_th', 'type' => 'address_th', 'label' => 'ที่อยู่สาขา', 'addressGroup' => 'gOther', 'formOrder' => 9],
        ]]);
    }

    private function apply(array $fields): array
    {
        $controller = app(LaborContractController::class);
        $m = new \ReflectionMethod($controller, 'applySameAsBaseAddress');
        $m->setAccessible(true);
        return $m->invoke($controller, $this->template(), $fields);
    }

    public function test_ticked_address_copies_the_main_address_on_the_server(): void
    {
        $out = $this->apply([
            'gHome_province' => 'กรุงเทพมหานคร', 'gHome_district' => 'บางรัก', 'gHome_subdistrict' => 'สีลม',
            'gHome_no' => '99/1', 'gHome_soi' => 'สีลม 5', 'gHome_soi_en' => 'Silom 5',
            'home_th' => '99/1 ซอย สีลม 5 ต.สีลม อ.บางรัก จ.กรุงเทพมหานคร 10500',
            'home_en' => '99/1, Soi Silom 5, Si Lom, Bang Rak, Bangkok, 10500',
            'gWork_same_as' => '1', 'gWork_no' => 'old', 'work_th' => 'พิมพ์ไว้ก่อน',
            'gOther_same_as' => '0', 'other_th' => 'ที่อยู่สาขาเดิม',
        ]);

        $this->assertSame('99/1 ซอย สีลม 5 ต.สีลม อ.บางรัก จ.กรุงเทพมหานคร 10500', $out['work_th']);
        $this->assertSame('99/1, Soi Silom 5, Si Lom, Bang Rak, Bangkok, 10500', $out['work_en']);
        $this->assertSame('99/1', $out['gWork_no']);
        $this->assertSame('สีลม', $out['gWork_subdistrict']);
        $this->assertSame('Silom 5', $out['gWork_soi_en']);
        $this->assertSame('ที่อยู่สาขาเดิม', $out['other_th'], 'unticked address keeps what was typed');
    }

    public function test_main_address_is_never_overwritten(): void
    {
        $out = $this->apply(['gHome_same_as' => '1', 'home_th' => 'หลัก', 'work_th' => 'งาน']);

        $this->assertSame('หลัก', $out['home_th']);
        $this->assertSame('งาน', $out['work_th']);
    }

    public function test_form_shows_the_tick_box_only_under_later_addresses(): void
    {
        $template = $this->template();
        $items = app(ProWorkerFormFieldsResolver::class)->unifiedItems($template);

        $html = view('labor.contracts._fields', ['template' => $template, 'formItems' => $items, 'values' => ['gWork_same_as' => '1']])->render();

        $this->assertStringNotContainsString('id="sameAs_gHome"', $html);
        $this->assertStringContainsString('id="sameAs_gWork"', $html);
        $this->assertStringContainsString('id="sameAs_gOther"', $html);
        $this->assertStringContainsString('data-base-group="gHome"', $html);
        $this->assertStringContainsString('ที่อยู่ตามทะเบียนบ้าน', $html);
        // Previously ticked (edit form): box checked, its picker hidden
        $this->assertMatchesRegularExpression('/id="sameAs_gWork"[^>]*\schecked/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="sameAs_gOther"[^>]*\schecked/', $html);
        $this->assertMatchesRegularExpression('/proworker-address-group[^"]*d-none[^"]*" data-group="gWork"/', $html);
        $this->assertDoesNotMatchRegularExpression('/proworker-address-group[^"]*d-none[^"]*" data-group="gOther"/', $html);
    }
}
