<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Advanced Export / Edit / Download follow the order the user ticks
 * (public/js/ordered-checks.js sends the values in that order):
 *  - export columns come out in the order sent — Photo is no longer forced
 *    to the first column;
 *  - the pages load the ordered-checkbox script and show the order summary.
 */
class AdvancedExportColumnOrderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        return $admin;
    }

    public function test_export_columns_follow_the_ticked_order(): void
    {
        $admin = $this->admin();
        $employee = Employee::factory()->create(['employeePassport' => 'PX123']);

        $response = $this->actingAs($admin)->post(route('employees.advanced_export'), [
            'employee_ids' => json_encode([$employee->id]),
            'columns' => ['employeePassport', 'employeePhoto', 'employeeAge'],
        ])->assertOk();

        $file = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($file, $response->streamedContent());
        $sheet = IOFactory::load($file)->getActiveSheet();
        @unlink($file);

        $headers = [];
        foreach (range(1, 4) as $col) {
            $headers[] = (string) $sheet->getCell([$col, 1])->getValue();
        }
        $this->assertSame(['No.', 'Passport No', 'Photo', 'Age'], $headers);
        $this->assertSame('PX123', (string) $sheet->getCell([2, 2])->getValue());
    }

    public function test_pages_load_the_ordered_checkbox_picker(): void
    {
        $html = $this->actingAs($this->admin())->get(route('employees.index'))->assertOk()->getContent();

        $this->assertTrue(str_contains($html, 'js/ordered-checks.js'), 'script loaded');
        $this->assertTrue(str_contains($html, 'window.OrderedChecksText'), 'texts passed');
        $this->assertTrue(str_contains($html, "OrderedChecks.attach(form, { selector: '.export-checkbox'"), 'export modal wired');
        $this->assertTrue(str_contains($html, "selector: 'input[name=\"files[]\"]'"), 'download modal wired');
        $this->assertTrue(substr_count($html, 'data-oc-summary') >= 2, 'order summaries shown');
        // Advanced Download opens with nothing ticked — the user picks no. 1, 2, 3 …
        preg_match_all('/<input[^>]*name="files\[\]"[^>]*>/', $html, $fileBoxes);
        $preChecked = array_values(array_filter($fileBoxes[0], fn ($tag) => preg_match('/\schecked[\s>]/', $tag)));
        $this->assertSame([], $preChecked, 'no document pre-ticked');
        $this->assertTrue(str_contains($html, 'downloadOrder.clear()'), 'reset when the window closes');
        // the "change order no. N?" confirm must sit above the download window (raised to 1070)
        $this->assertTrue(str_contains($html, '.swal2-container { z-index: 2400 !important; }'), 'confirm popups above every window');
    }
}
