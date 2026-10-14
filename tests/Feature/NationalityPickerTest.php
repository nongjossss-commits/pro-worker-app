<?php

namespace Tests\Feature;

use App\Helpers\NationalityList;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Every nationality is selectable (stored as the Thai name, shown in the
 * user's language) via the searchable picker; the expiry-date filter matches
 * work permit OR visa expiry.
 */
class NationalityPickerTest extends TestCase
{
    use RefreshDatabase;

    protected function signIn(): void
    {
        Role::findOrCreate('super-admin');
        foreach (['admin', 'staff', 'caretaker'] as $r) { Role::findOrCreate($r); }
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $this->actingAs($user);
    }

    public function test_list_keeps_existing_values_and_translates(): void
    {
        $values = array_column(NationalityList::options('th'), 'value');
        $this->assertGreaterThan(190, count($values));
        $this->assertSame(count($values), count(array_unique($values)));
        // The four main ones first, with the exact values already stored.
        $this->assertSame(['เมียนมา', 'ลาว', 'กัมพูชา', 'เวียดนาม'], array_slice($values, 0, 4));

        $this->assertSame('Vietnamese', NationalityList::label('เวียดนาม', 'en'));
        $this->assertSame('越南', NationalityList::label('เวียดนาม', 'zh'));
        $this->assertSame('ဗီယက်နမ်', NationalityList::label('เวียดนาม', 'my'));
        $this->assertSame('Japanese', NationalityList::label('ญี่ปุ่น', 'en'));
        // A value outside the list is shown unchanged.
        $this->assertSame('พม่า (เก่า)', NationalityList::label('พม่า (เก่า)', 'en'));
    }

    public function test_filter_and_cards_work_for_any_nationality(): void
    {
        $this->signIn();
        $employer = Employer::factory()->create();
        Employee::factory()->create(['employer_id' => $employer->id, 'employeeNationality' => 'ญี่ปุ่น', 'employeeNameEn' => 'Taro Yamada']);
        Employee::factory()->create(['employer_id' => $employer->id, 'employeeNationality' => 'ลาว', 'employeeNameEn' => 'Somchai Lao']);

        $html = $this->get(route('employees.index'))->assertOk()->getContent();
        $this->assertTrue(str_contains($html, 'js-nationality-select'), 'missing: ' . 'js-nationality-select');
        $this->assertTrue(str_contains($html, 'value="ญี่ปุ่น"'), 'missing: ' . 'value="ญี่ปุ่น"');

        $filtered = $this->get(route('employees.index', ['nationality' => 'ญี่ปุ่น']))->assertOk()->getContent();
        $this->assertTrue(str_contains($filtered, 'Taro Yamada'), 'missing: ' . 'Taro Yamada');
        $this->assertFalse(str_contains($filtered, 'Somchai Lao'), 'unexpected: ' . 'Somchai Lao');
        $this->assertSame(1, preg_match('/value="ญี่ปุ่น"[^>]*selected/u', $filtered), 'filter value not selected');

        session(['locale' => 'en']);
        $en = $this->get(route('employees.index'))->assertOk()->getContent();
        $this->assertTrue(str_contains($en, '<span>Japanese</span>'), 'missing: ' . '<span>Japanese</span>');   // card, no flag image needed
    }

    public function test_expiry_filter_matches_work_permit_or_visa(): void
    {
        $this->signIn();
        $employer = Employer::factory()->create();
        Employee::factory()->create(['employer_id' => $employer->id, 'employeeNameEn' => 'WP Match', 'workPermitExpiryDate' => '2027-03-15', 'visaExpiryDate' => '2027-09-01']);
        Employee::factory()->create(['employer_id' => $employer->id, 'employeeNameEn' => 'Visa Match', 'workPermitExpiryDate' => '2027-01-01', 'visaExpiryDate' => '2027-03-15']);
        Employee::factory()->create(['employer_id' => $employer->id, 'employeeNameEn' => 'No Match', 'workPermitExpiryDate' => '2027-01-02', 'visaExpiryDate' => '2027-01-03']);

        $html = $this->get(route('employees.index', ['work_permit_expiry_date' => '2027-03-15']))->assertOk()->getContent();
        $this->assertTrue(str_contains($html, 'WP Match'), 'missing: ' . 'WP Match');
        $this->assertTrue(str_contains($html, 'Visa Match'), 'missing: ' . 'Visa Match');
        $this->assertFalse(str_contains($html, 'No Match'), 'unexpected: ' . 'No Match');
        $this->assertTrue(str_contains($html, 'placeholder="' . __('Filter by work permit and visa expiry date') . '"'), 'missing: ' . 'placeholder="' . __('Filter by work permit and visa expiry date') . '"');
    }
}
