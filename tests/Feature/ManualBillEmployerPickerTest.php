<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Finance → Create Manual Bill: the employer box is a search-as-you-type
 * picker (or an A–Z list to scroll) instead of a 500-row dropdown.
 */
class ManualBillEmployerPickerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);
    }

    public function test_search_browse_and_paging(): void
    {
        Employer::factory()->create(['employerNameTh' => 'บริษัท ฟ้าใส จำกัด', 'employerId' => 'EMP-777']);
        Employer::factory()->count(35)->create();

        $hit = $this->getJson(route('finance.create.employer_options', ['q' => 'ฟ้าใส']))->assertOk()->json();
        $this->assertSame(['บริษัท ฟ้าใส จำกัด'], collect($hit['items'])->pluck('label')->all());
        $this->assertSame(1, count($this->getJson(route('finance.create.employer_options', ['q' => 'EMP-777']))->json('items')), 'matches employer ID too');

        $p1 = $this->getJson(route('finance.create.employer_options'))->json();
        $this->assertCount(30, $p1['items']);
        $this->assertTrue($p1['more']);
        $p2 = $this->getJson(route('finance.create.employer_options', ['page' => 2]))->json();
        $this->assertCount(6, $p2['items']);
        $this->assertFalse($p2['more']);
        $this->assertEmpty(array_intersect(collect($p1['items'])->pluck('id')->all(), collect($p2['items'])->pluck('id')->all()));
    }

    public function test_employers_past_the_old_500_limit_can_be_found(): void
    {
        Employer::factory()->count(501)->create(['employerNameTh' => 'ก นายจ้าง']);
        Employer::factory()->create(['employerNameTh' => 'ฮ นายจ้างคนสุดท้าย']);
        $items = $this->getJson(route('finance.create.employer_options', ['q' => 'คนสุดท้าย']))->json('items');
        $this->assertSame('ฮ นายจ้างคนสุดท้าย', $items[0]['label']);
    }

    public function test_page_renders_the_picker_with_a_preselected_employer(): void
    {
        $er = Employer::factory()->create(['employerNameTh' => 'บริษัท เลือกไว้แล้ว จำกัด']);
        $html = $this->get(route('finance.create', ['employer_id' => $er->id]))->assertOk()->getContent();
        $this->assertTrue(str_contains($html, 'manualEmployerPicker(') && str_contains($html, 'name="employer_id"'));
        // the picker starts with that employer (inside @js(), a quote is written as \u0022)
        $q = '\\u0022';
        $this->assertTrue(str_contains($html, $q . 'id' . $q . ':' . $er->id . ','));
        $this->assertFalse(str_contains($html, '<select name="employer_id"'));
    }
}
