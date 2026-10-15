<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Employees search: picking one suggestion shows that employee only (not every
 * name match), employees offered by the suggestions are found by box 1, and the
 * list page doesn't run queries per card.
 */
class EmployeeSearchPickTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        foreach (['admin', 'staff', 'caretaker'] as $r) { Role::findOrCreate($r); }
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $this->actingAs($user);
    }

    protected function cardCount(string $html): int
    {
        return substr_count($html, 'class="emp-card-no"');
    }

    public function test_picking_a_suggestion_shows_only_that_employee(): void
    {
        $a = Employee::factory()->create(['employer_id' => Employer::factory()->create()->id, 'employeeNameEn' => 'Jai Jian']);
        Employee::factory()->create(['employer_id' => Employer::factory()->create()->id, 'employeeNameEn' => 'Jai Jian']);

        // Suggestions carry the exact pick.
        $json = $this->getJson(route('employees.preview_search_combined', ['q' => 'Jai Jian']))->assertOk()->json();
        $picks = collect($json)->pluck('pick.employee_pick_id')->filter()->values()->all();
        $this->assertCount(2, $picks);

        // Text only (Enter, no pick) → both; with the pick → just the one.
        $this->assertSame(2, $this->cardCount($this->get(route('employees.index', ['employer_filter_name' => 'Jai Jian']))->getContent()));
        $this->assertSame(1, $this->cardCount($this->get(route('employees.index', ['employer_filter_name' => 'Jai Jian', 'employee_pick_id' => $a->id]))->getContent()));
    }

    public function test_box_one_finds_terminated_employees_the_suggestions_offer(): void
    {
        $employer = Employer::factory()->create();
        Employee::factory()->create(['employer_id' => $employer->id, 'employeeNameEn' => 'Gone Worker', 'terminated_at' => now()->subDay()]);

        $this->assertNotEmpty($this->getJson(route('employees.preview_search_combined', ['q' => 'Gone Worker']))->json());
        // Was 0 before: the "hide terminated" check ran before box 1's text became the search.
        $this->assertSame(1, $this->cardCount($this->get(route('employees.index', ['employer_filter_name' => 'Gone Worker']))->getContent()));
        // Plain browsing still hides them.
        $this->assertSame(0, $this->cardCount($this->get(route('employees.index'))->getContent()));
    }

    public function test_list_queries_do_not_grow_with_the_number_of_cards(): void
    {
        $employer = Employer::factory()->create();
        Employee::factory()->count(5)->create(['employer_id' => $employer->id]);
        $small = $this->countQueries(fn () => $this->get(route('employees.index'))->assertOk());

        Employee::factory()->count(40)->create(['employer_id' => $employer->id]);
        $big = $this->countQueries(fn () => $this->get(route('employees.index'))->assertOk());

        $this->assertLessThan(10, $big - $small, "queries grew from {$small} to {$big} for 40 more cards");
    }

    protected function countQueries(callable $fn): int
    {
        $n = 0;
        DB::listen(function () use (&$n) { $n++; });
        $before = $n;
        $fn();

        return $n - $before;
    }
}
