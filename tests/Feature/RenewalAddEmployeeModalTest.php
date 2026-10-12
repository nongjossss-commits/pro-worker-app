<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\ResolutionTab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Renewal Resolution → "เพิ่มลูกจ้าง" → "พนักงานใหม่" must always show the
 * employer picker. renewal/index.blade.php includes the modal after
 * `@foreach($employers as $employer)`, and the loop's last employer used to
 * leak into the form: the picker was hidden and that employer's id was sent.
 */
class RenewalAddEmployeeModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_employee_tab_always_shows_the_employer_picker(): void
    {
        Role::findOrCreate('super-admin');
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $this->actingAs($user);

        $tab = ResolutionTab::create(['name' => 'มติต่ออายุ', 'type' => 'renewal', 'slug' => 'renewal-' . uniqid()]);
        $leaked = Employer::factory()->create(['employerNameTh' => 'นายจ้างคนสุดท้ายในหน้า']);

        // Components read $errors from the shared view data (set by the web middleware in a real request).
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());

        // Same situation as the page: an $employer variable left over from a loop.
        $html = view('production.renewal.partials.add_employee_modal', ['currentTab' => $tab, 'employer' => $leaked])->render();

        $this->assertStringContainsString('x-data="employerSelector()"', $html);
        $this->assertStringContainsString('<input type="hidden" name="employer_id" :value="selectedId" required>', $html);
        $this->assertStringNotContainsString('<input type="hidden" name="employer_id" value="' . $leaked->id . '">', $html);
    }
}
