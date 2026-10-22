<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\JobOwner;
use App\Models\User;
use App\Services\TwoStageSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Two-stage search: box 1 takes an employee, employer or job owner (typed or
 * picked), box 2 narrows inside it; inside an employer's card a term that
 * matches the employer keeps everyone, otherwise only matching employees.
 */
class TwoStageSearchTest extends TestCase
{
    use RefreshDatabase;

    protected Employer $acme;
    protected Employer $other;
    protected JobOwner $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        foreach (['admin', 'staff', 'caretaker'] as $r) { Role::findOrCreate($r); }
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $this->actingAs($user);

        $this->owner = JobOwner::create(['name' => 'Owner Kittisak']);
        $this->acme = Employer::factory()->create(['employerNameTh' => 'Acme Thai', 'employerNameEn' => 'Acme Co', 'job_owner_id' => $this->owner->id]);
        $this->other = Employer::factory()->create(['employerNameTh' => 'Other Ltd', 'employerNameEn' => 'Other Ltd']);
        Employee::factory()->create(['employer_id' => $this->acme->id, 'employeeNameEn' => 'Somchai One']);
        Employee::factory()->create(['employer_id' => $this->acme->id, 'employeeNameEn' => 'Mali Two']);
        Employee::factory()->create(['employer_id' => $this->other->id, 'employeeNameEn' => 'Somchai Three']);
    }

    protected function cards(array $query): int
    {
        return substr_count($this->get(route('employees.index', $query))->assertOk()->getContent(), 'class="emp-card-no"');
    }

    public function test_box_one_finds_employee_employer_or_job_owner_and_box_two_narrows(): void
    {
        $this->assertSame(2, $this->cards(['employer_filter_name' => 'Somchai']));            // employee text
        $this->assertSame(2, $this->cards(['employer_filter_name' => 'Acme']));               // employer text
        $this->assertSame(2, $this->cards(['employer_filter_name' => 'Owner Kittisak']));     // job owner text
        $this->assertSame(1, $this->cards(['employer_filter_name' => 'Owner Kittisak', 'search' => 'Mali']));  // narrowed
        $this->assertSame(1, $this->cards(['employer_filter_name' => 'Somchai', 'search' => 'Other']));        // narrowed by employer
        $this->assertSame(2, $this->cards(['employer_filter_name' => 'x', 'job_owner_pick_id' => $this->owner->id])); // owner pick
        $this->assertSame(1, $this->cards(['employer_filter_name' => 'Acme Thai', 'employer_filter_id' => $this->acme->id, 'search' => 'Somchai']));
    }

    public function test_suggestions_offer_job_owners_with_a_pick(): void
    {
        $rows = collect($this->getJson(route('employees.preview_search_combined', ['q' => 'Kittisak', '_box' => 'employer_filter_name']))->json());
        $owner = $rows->firstWhere('pick.job_owner_pick_id', $this->owner->id);
        $this->assertNotNull($owner, 'job owner row missing');
        $this->assertNotNull($rows->firstWhere('id', $this->acme->id), "owner's employer row missing");

        // Box 2 suggestions stay inside box 1's typed text.
        $box2 = collect($this->getJson(route('employees.preview_search', ['q' => 'Somchai', '_box' => 'search', 'employer_filter_name' => 'Acme']))->json());
        $this->assertSame(['Somchai One'], $box2->pluck('value')->all());

        // Employers menu offers job owners too.
        $employers = collect($this->getJson(route('employers.preview_search', ['q' => 'Kittisak']))->json());
        $this->assertNotNull($employers->firstWhere('pick.job_owner_pick_id', $this->owner->id));
    }

    public function test_inside_an_employer_card_employer_terms_keep_everyone(): void
    {
        $names = function (array $params) {
            $q = Employee::where('employer_id', $this->acme->id);
            TwoStageSearch::applyInsideEmployer($q, $this->acme->id, Request::create('/', 'GET', $params));
            return $q->orderBy('employeeNameEn')->pluck('employeeNameEn')->all();
        };
        $this->assertSame(['Mali Two', 'Somchai One'], $names(['employer_filter_name' => 'Acme']));            // employer
        $this->assertSame(['Mali Two', 'Somchai One'], $names(['employer_filter_name' => 'Owner Kittisak']));  // job owner
        $this->assertSame(['Somchai One'], $names(['employer_filter_name' => 'Somchai']));                     // employee
        $this->assertSame(['Mali Two'], $names(['employer_filter_name' => 'Acme', 'search' => 'Mali']));       // narrowed
        $somchai = Employee::where('employeeNameEn', 'Somchai One')->value('id');
        $this->assertSame(['Somchai One'], $names(['employer_filter_name' => 'Somchai One', 'employee_pick_id' => $somchai]));
    }

    public function test_card_request_numbers_ra_and_passport_are_found_in_every_menu_and_in_suggestions(): void
    {
        $emp = Employee::factory()->create([
            'employer_id' => $this->acme->id, 'employeeNameEn' => 'Card Person',
            'employeePassport' => 'PX778899', 'name_list_number' => 'RA-55501', 'request_number' => null,
        ]);
        // Workflow / Pre-Production card: request no. lives on the item, not on the employee
        $order = \App\Models\ProductionOrder::create(['employer_id' => $this->acme->id, 'status' => 'active']);
        \App\Models\ProductionItem::create(['production_order_id' => $order->id, 'employee_id' => $emp->id, 'status' => 'pending', 'request_number' => 'REQ-CARD-901']);
        // Registration resolution card: request no. per resolution tab
        $tab = \App\Models\ResolutionTab::create(['name' => 'มติลงทะเบียนทดสอบ', 'type' => 'registration', 'slug' => 'reg-test-' . uniqid()]);
        $emp->forceFill(['resolution_tab_id' => $tab->id, 'status' => 'registration_pending'])->saveQuietly();
        \App\Models\EmployeeRequestNumber::create(['employee_id' => $emp->id, 'resolution_tab_id' => $tab->id, 'request_number' => 'RES-REQ-4455']);

        $names = fn (array $q) => collect(
            \Illuminate\Support\Arr::pluck($this->get(route('employees.index', $q))->viewData('employees')->items(), 'employeeNameEn')
        )->all();
        $suggests = fn (string $route, array $params) => collect($this->getJson(route($route, $params))->json())->pluck('label')->implode(' | ');

        foreach (['REQ-CARD-901', 'RES-REQ-4455', 'RA-55501', 'PX778899'] as $term) {
            // Employees menu (box 1) + its suggestions
            $this->assertContains('Card Person', $names(['employer_filter_name' => $term]), "employees menu finds $term");
            $this->assertTrue(str_contains($suggests('employees.preview_search_combined', ['q' => $term, '_box' => 'employer_filter_name']), 'Card Person'), "employees suggestions find $term");
            // Workflow suggestions (same employee is on an active card)
            $this->assertTrue(str_contains($suggests('workflow.preview_search', ['q' => $term, '_box' => 'employer_filter_name']), 'Card Person'), "workflow suggestions find $term");
        }
        // Registration resolution suggestions find the resolution card's request no.
        $this->assertTrue(str_contains($suggests('production.registration.preview_search', ['resolutionTab' => $tab->id, 'q' => 'RES-REQ-4455', '_box' => 'employer_filter_name']), 'Card Person'), 'registration suggestions find the card request no.');
    }
}
