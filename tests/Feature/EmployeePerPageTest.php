<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Employees / Notified-Out / employer Employees tab: 25 / 50 / 100 per page,
 * and the last pick is remembered in a cookie per list (RemembersPerPage).
 */
class EmployeePerPageTest extends TestCase
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

    public function test_pick_is_saved_and_used_until_changed(): void
    {
        $employer = Employer::factory()->create();
        Employee::factory()->count(60)->create(['employer_id' => $employer->id]);

        // No pick yet → 100.
        $this->assertSame(60, substr_count($this->get(route('employees.index'))->assertOk()->getContent(), 'class="emp-card-no"'));

        // Pick 50 → 50 cards and a cookie for this list.
        $res = $this->get(route('employees.index', ['per_page' => 50]))->assertOk()->assertCookie('pw_per_page_employees', '50');
        $this->assertSame(50, substr_count($res->getContent(), 'class="emp-card-no"'));

        // Coming back without ?per_page uses the saved 50.
        $again = $this->withCookie('pw_per_page_employees', '50')->get(route('employees.index'))->assertOk()->getContent();
        $this->assertSame(50, substr_count($again, 'class="emp-card-no"'));

        // Values outside 25/50/100 are ignored.
        $odd = $this->withCookie('pw_per_page_employees', '7')->get(route('employees.index', ['per_page' => 9999]))->assertOk()->getContent();
        $this->assertSame(60, substr_count($odd, 'class="emp-card-no"'));
    }

    public function test_locate_uses_the_employer_tab_page_size_of_this_browser(): void
    {
        $employer = Employer::factory()->create();
        $employees = [];
        for ($i = 0; $i < 30; $i++) {
            $employees[] = Employee::factory()->create(['employer_id' => $employer->id, 'created_at' => now()->subMinutes($i)]);
        }

        // 26th newest is on page 2 when the employer tab shows 25 per page.
        $this->withCookie('pw_per_page_employer_employees', '25')
            ->get(route('employees.locate', $employees[25]->id))
            ->assertRedirect(route('employers.edit', ['employer' => $employer->id, 'page' => 2]));
    }
}
