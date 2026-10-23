<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Activity log year → month → day pages list the months / days that have
 * logs — without MySQL-only MONTH()/DAY() (the tests run on SQLite).
 */
class ActivityLogDateDrilldownTest extends TestCase
{
    use RefreshDatabase;

    public function test_year_and_month_pages_list_months_and_days_with_logs(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        foreach (['2025-03-07 10:00:00', '2025-10-05 09:00:00', '2025-10-21 15:00:00'] as $at) {
            $log = ActivityLog::create(['action' => 'update', 'subject_type' => User::class, 'subject_id' => $admin->id, 'description' => 'x']);
            $log->forceFill(['created_at' => $at])->save();
        }

        $year = $this->actingAs($admin)->get(route('admin.activity-logs.year', 2025))->assertOk();
        $this->assertEquals([10, 3], $year->viewData('months')->map(fn ($m) => (int) $m)->all());

        $month = $this->get(route('admin.activity-logs.month', [2025, 10]))->assertOk();
        $this->assertEquals([21, 5], $month->viewData('days')->map(fn ($d) => (int) $d)->all());
    }
}
