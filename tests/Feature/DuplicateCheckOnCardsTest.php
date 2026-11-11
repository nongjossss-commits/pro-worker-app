<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\SalesLead;
use App\Models\SalesLeadEmployee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-11-11: identity fields typed straight on a job card (RA number,
 * Outsource login e-mail) skipped the duplicate check — the warning only
 * showed the next time someone saved the full Edit form. The cards now run
 * the same check first (partials/_duplicate_field_check, checked in a
 * browser on Registration + Workflow cards); the Registration/Renewal login
 * editor refuses an e-mail another employee uses, like Workflow already did;
 * the Sales → employees hand-over checks every identity field.
 */
class DuplicateCheckOnCardsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Employer $employer;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super-admin');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
        $this->employer = Employer::factory()->create();
        $this->actingAs($this->admin);
    }

    public function test_card_login_editor_refuses_an_email_another_employee_uses(): void
    {
        Employee::factory()->create(['employer_id' => $this->employer->id, 'email' => 'taken@example.test']);
        $me = Employee::factory()->create(['employer_id' => $this->employer->id, 'email' => 'mine@example.test']);

        $this->postJson(route('production.update_outsource_login', $me), ['email' => 'taken@example.test', 'password' => 'x', 'outsource_code' => 'y'])
            ->assertStatus(422)->assertJsonValidationErrors('email');
        $this->assertSame('mine@example.test', $me->fresh()->email);

        // own e-mail / a new one / empty are fine
        $this->postJson(route('production.update_outsource_login', $me), ['email' => 'mine@example.test'])->assertOk();
        $this->postJson(route('production.update_outsource_login', $me), ['email' => 'new@example.test'])->assertOk();
        $this->postJson(route('production.update_outsource_login', $me), ['email' => ''])->assertOk();
    }

    public function test_the_check_the_cards_use_finds_ra_and_email_duplicates(): void
    {
        $other = Employee::factory()->create(['employer_id' => $this->employer->id, 'name_list_number' => 'RA-777', 'email' => 'taken@example.test']);
        $me = Employee::factory()->create(['employer_id' => $this->employer->id]);

        $dups = $this->postJson(route('employees.check_duplicate'), ['name_list_number' => 'RA-777', 'employeeEmail' => 'taken@example.test', 'exclude_id' => $me->id])
            ->assertOk()->json('duplicates');
        $this->assertSame(['name_list_number', 'employeeEmail'], array_column($dups, 'field'));
        $this->assertTrue(collect($dups)->firstWhere('field', 'employeeEmail')['blocking']);
        $this->assertSame($other->id, $dups[0]['record']['id']);

        // the employee's own values never count
        $this->assertSame([], $this->postJson(route('employees.check_duplicate'), ['name_list_number' => 'RA-777', 'exclude_id' => $other->id])->json('duplicates'));
    }

    public function test_cards_and_layout_run_the_check_before_inline_saves(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString("@include('partials._duplicate_field_check')", $layout);

        foreach (['production/registration/_employee_card.blade.php', 'workflow/partials/_item_card.blade.php'] as $card) {
            $src = file_get_contents(resource_path('views/' . $card));
            $this->assertStringContainsString('checkEmployeeFieldDuplicates({ name_list_number: this.nameList }', $src, $card);
            $this->assertStringContainsString('checkEmployeeFieldDuplicates({ employeeEmail: this.email }', $src, $card);
        }
    }

    public function test_sales_hand_over_checks_every_identity_field(): void
    {
        Employee::factory()->create(['employer_id' => $this->employer->id, 'pinkCardNo' => 'PINK-1', 'name_list_number' => 'RA-9', 'employee_id_number' => 'ID-5']);

        foreach (['pinkCardNo' => 'PINK-1', 'name_list_number' => 'RA-9', 'employee_id_number' => 'ID-5'] as $field => $value) {
            $lead = SalesLead::create(['employerNameTh' => 'บริษัท ทดสอบ ' . $field, 'status' => 'confirmed']);
            SalesLeadEmployee::create(['sales_lead_id' => $lead->id, 'employeeNameEn' => 'NEW PERSON', $field => $value]);

            $this->post(route('sales.transition', $lead), ['destination' => 'production.registration'])
                ->assertSessionHas('transition_duplicate_warning');
            $this->assertSame(1, Employee::where($field, $value)->count(), "$field: nobody new was created");
        }
    }
}
