<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Employer;
use App\Models\Employee;
use Database\Seeders\StabilityTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StabilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the StabilityTestSeeder creates the correct amount of data.
     *
     * @return void
     */
    public function test_create_large_scale_data()
    {
        // Assert initial state
        $this->assertEquals(0, Employer::count());
        $this->assertEquals(0, Employee::count());

        // Run the seeder
        $this->seed(StabilityTestSeeder::class);

        // Assert final state
        $expectedEmployers = 100;
        $expectedEmployees = 100 * 100; // 10,000

        $this->assertEquals($expectedEmployers, Employer::count(), "Employer count should be {$expectedEmployers}");
        $this->assertEquals($expectedEmployees, Employee::count(), "Employee count should be {$expectedEmployees}");
    }
}
