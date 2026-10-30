<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 2026-10-30: the Alerts & Checks menus page 25 / 50 / 100 like the Employees
 * menu (remembered per browser), and Advanced Export of employees from several
 * employers can make one file grouped by employer or a ZIP of one file each.
 */
class AlertsPerPageAndExportByEmployerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('super-admin');
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    public function test_notifications_offer_25_50_100_and_remember_the_pick(): void
    {
        $admin = $this->admin();

        $html = $this->actingAs($admin)->get(route('notifications.index'))->assertOk()->getContent();
        foreach ([25, 50, 100] as $n) {
            $this->assertTrue(str_contains($html, 'per_page=' . $n), "per_page=$n link");
        }
        $this->assertFalse(str_contains($html, 'per_page=10&') || str_contains($html, 'per_page=10"'), 'old 10 option');

        $this->get(route('notifications.index', ['per_page' => 50]))->assertOk()->assertCookie('pw_per_page_notifications', '50');
    }

    private function employeesOfTwoEmployers(): array
    {
        $a = Employer::factory()->create(['employerNameTh' => 'นายจ้างเอ']);
        $b = Employer::factory()->create(['employerNameTh' => 'นายจ้างบี']);
        $a1 = Employee::factory()->create(['employer_id' => $a->id, 'employeeNameEn' => 'Alpha One']);
        $b1 = Employee::factory()->create(['employer_id' => $b->id, 'employeeNameEn' => 'Bravo One']);
        $a2 = Employee::factory()->create(['employer_id' => $a->id, 'employeeNameEn' => 'Alpha Two']);

        return [$a1, $b1, $a2]; // picked mixed: A, B, A
    }

    public function test_employers_of_the_picked_employees_are_listed(): void
    {
        [$a1, $b1, $a2] = $this->employeesOfTwoEmployers();

        $this->actingAs($this->admin())
            ->postJson(route('employees.advanced_export.employers'), ['employee_ids' => json_encode([$a1->id, $b1->id, $a2->id])])
            ->assertOk()
            ->assertJsonCount(2, 'employers')
            ->assertJsonFragment(['name' => 'นายจ้างเอ', 'count' => 2])
            ->assertJsonFragment(['name' => 'นายจ้างบี', 'count' => 1]);
    }

    public function test_grouped_mode_writes_one_block_per_employer(): void
    {
        [$a1, $b1, $a2] = $this->employeesOfTwoEmployers();

        $response = $this->actingAs($this->admin())->post(route('employees.advanced_export'), [
            'employee_ids' => json_encode([$a1->id, $b1->id, $a2->id]),
            'columns' => ['employeeNameEn'],
            'employer_mode' => 'grouped',
        ])->assertOk();

        $tmp = tempnam(sys_get_temp_dir(), 'tst') . '.xlsx';
        file_put_contents($tmp, $response->streamedContent());
        $rows = IOFactory::load($tmp)->getActiveSheet()->toArray(null, false, false, false);
        unlink($tmp);

        $this->assertStringContainsString('นายจ้างเอ', $rows[0][0]);           // first-picked employer first
        $this->assertSame(['No.', 'Name (EN)'], $rows[1]);
        $this->assertSame([1, 'Alpha One'], $rows[2]);
        $this->assertSame([2, 'Alpha Two'], $rows[3]);                         // both of A together
        $this->assertStringContainsString('นายจ้างบี', $rows[5][0]);           // after one empty row
        $this->assertSame([1, 'Bravo One'], $rows[7]);                         // numbering starts again
    }

    public function test_separate_mode_returns_a_zip_with_one_file_per_employer(): void
    {
        [$a1, $b1, $a2] = $this->employeesOfTwoEmployers();

        $response = $this->actingAs($this->admin())->post(route('employees.advanced_export'), [
            'employee_ids' => json_encode([$a1->id, $b1->id, $a2->id]),
            'columns' => ['employeeNameEn'],
            'employer_mode' => 'separate',
        ])->assertOk();

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($response->getFile()->getPathname()));
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();
        @unlink($response->getFile()->getPathname());

        // "<employer> (n คน).xlsx" (App\Support\DownloadFileName)
        $this->assertSame([
            \App\Support\DownloadFileName::withCount('นายจ้างเอ', 2) . '.xlsx',
            \App\Support\DownloadFileName::withCount('นายจ้างบี', 1) . '.xlsx',
        ], $names);
    }

    public function test_without_a_mode_the_export_stays_one_list(): void
    {
        [$a1, $b1, $a2] = $this->employeesOfTwoEmployers();

        $response = $this->actingAs($this->admin())->post(route('employees.advanced_export'), [
            'employee_ids' => json_encode([$a1->id, $b1->id, $a2->id]),
            'columns' => ['employeeNameEn'],
        ])->assertOk();

        $tmp = tempnam(sys_get_temp_dir(), 'tst') . '.xlsx';
        file_put_contents($tmp, $response->streamedContent());
        $rows = IOFactory::load($tmp)->getActiveSheet()->toArray(null, false, false, false);
        unlink($tmp);

        $this->assertSame([['No.', 'Name (EN)'], [1, 'Alpha One'], [2, 'Bravo One'], [3, 'Alpha Two']], $rows);
    }
}
