<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionItem;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Traits\AddressFilterTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * "คัดกรองตามที่อยู่" (components/address-filter + AddressFilterTrait), used
 * on 10 pages. Addresses belong to employers; the options used to be looked
 * up by the page's own model (Employee, ProductionOrder…), so every page not
 * built on employers got an empty province list.
 */
class AddressFilterTest extends TestCase
{
    use RefreshDatabase;

    private Employer $bangkok;
    private Employer $chiangMai;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bangkok = Employer::factory()->create(['employerNameTh' => 'นายจ้างกรุงเทพ']);
        $this->bangkok->addresses()->create([
            'type' => 'registered',
            'addrProvince' => 'กรุงเทพมหานคร', 'addrDistrict' => 'บางรัก', 'addrSubDistrict' => 'สีลม',
            'addrProvinceEn' => 'Bangkok', 'addrDistrictEn' => 'Bang Rak', 'addrSubDistrictEn' => 'Silom',
        ]);
        $this->chiangMai = Employer::factory()->create(['employerNameTh' => 'นายจ้างเชียงใหม่']);
        $this->chiangMai->addresses()->create([
            'type' => 'workplace',
            'addrProvince' => 'เชียงใหม่', 'addrDistrict' => 'เมืองเชียงใหม่', 'addrSubDistrict' => 'ช้างเผือก',
            'addrProvinceEn' => 'Chiang Mai', 'addrDistrictEn' => 'Mueang Chiang Mai', 'addrSubDistrictEn' => 'Chang Phueak',
        ]);
    }

    private function filter(): object
    {
        return new class { use AddressFilterTrait; };
    }

    public function test_options_come_from_employer_addresses_for_every_kind_of_page(): void
    {
        $empA = Employee::factory()->create(['employer_id' => $this->bangkok->id]);
        Employee::factory()->create(['employer_id' => $this->chiangMai->id]);
        $order = ProductionOrder::create(['employer_id' => $this->bangkok->id, 'status' => 'active']);
        ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $empA->id, 'status' => 'pending']);

        $both = ['กรุงเทพมหานคร', 'เชียงใหม่'];
        $f = $this->filter();
        // Employers / Groups / Resolution pages
        $this->assertSame($both, $f->getAddressOptions(Employer::query())['provinces']->all());
        // Employees / Incomplete data / Notifications (employer_document_expiry)
        $this->assertSame($both, $f->getAddressOptions(Employee::query(), 'employer_id')['provinces']->all());
        // Pre-Production / Workflow (orders)
        $this->assertSame(['กรุงเทพมหานคร'], $f->getAddressOptions(ProductionOrder::query(), 'employer_id')['provinces']->all());
        // Notifications (rows linked to an employee → its employer)
        $this->assertSame(['กรุงเทพมหานคร'], $f->getAddressOptions(ProductionItem::query(), 'employee_id', true)['provinces']->all());
    }

    public function test_districts_follow_the_selected_province(): void
    {
        Employee::factory()->create(['employer_id' => $this->bangkok->id]);
        Employee::factory()->create(['employer_id' => $this->chiangMai->id]);
        request()->merge(['addrProvince' => 'กรุงเทพมหานคร', 'addrDistrict' => 'บางรัก']);
        $options = $this->filter()->getAddressOptions(Employee::query(), 'employer_id');

        $this->assertSame(['บางรัก'], $options['districts']->all());
        $this->assertSame(['สีลม'], $options['subDistricts']->all());
    }

    public function test_employees_page_filters_and_speaks_the_interface_language(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $inBkk = Employee::factory()->create(['employer_id' => $this->bangkok->id, 'employeeNameEn' => 'Somchai Bangkokworker']);
        $inCm = Employee::factory()->create(['employer_id' => $this->chiangMai->id, 'employeeNameEn' => 'Mana Chiangmaiworker']);

        // Thai: Thai names and the original Thai wording.
        $th = $this->actingAs($admin)->withSession(['locale' => 'th'])
            ->get(route('employees.index', ['addrProvince' => 'เชียงใหม่']))->assertOk()->getContent();
        // English: same Thai values submitted, English names shown.
        $en = $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->get(route('employees.index', ['addrProvince' => 'กรุงเทพมหานคร']))->assertOk()->getContent();

        // Compared as a list of named checks so a failure names the check
        // instead of dumping the whole page.
        $checks = [
            'th shows the Chiang Mai worker' => str_contains($th, 'Mana Chiangmaiworker'),
            'th hides the Bangkok worker' => !str_contains($th, 'Somchai Bangkokworker'),
            'th title' => str_contains($th, 'คัดกรองตามที่อยู่'),
            'th option' => str_contains($th, '<option value="เชียงใหม่" selected>เชียงใหม่</option>'),
            'th matched-address tag' => str_contains($th, '(ที่อยู่สถานที่ทำงาน)'),
            'en shows the Bangkok worker' => str_contains($en, 'Somchai Bangkokworker'),
            'en hides the Chiang Mai worker' => !str_contains($en, 'Mana Chiangmaiworker'),
            'en title' => str_contains($en, 'Filter by Address'),
            'en province option' => str_contains($en, '<option value="กรุงเทพมหานคร" selected>Bangkok</option>'),
            'en district option' => (bool) preg_match('/<option value="บางรัก"\s*>Bang Rak<\/option>/u', $en),
            'en khet label' => str_contains($en, 'District (Khet)'),
            'en matched-address tag' => str_contains($en, '(Registered address)'),
        ];
        $this->assertSame([], array_keys(array_filter($checks, fn ($ok) => !$ok)), 'Failed checks');
    }

    public function test_groups_page_offers_provinces_before_any_search(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $html = $this->actingAs($admin)->get(route('groups.affiliated.index'))->assertOk()->getContent();
        $this->assertTrue(str_contains($html, '<option value="เชียงใหม่"'), 'province list is empty on first load');
    }

    public function test_employers_page_filters_by_sub_district(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->get(route('employers.index', [
            'addrProvince' => 'กรุงเทพมหานคร', 'addrDistrict' => 'บางรัก', 'addrSubDistrict' => 'สีลม',
        ]))->assertOk()->assertSee('นายจ้างกรุงเทพ')->assertDontSee('นายจ้างเชียงใหม่');
    }
}
