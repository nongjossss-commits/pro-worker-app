<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employer;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class StabilityTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Disable query logging to save memory
        DB::disableQueryLog();

        $totalEmployers = 100;
        $employeesPerEmployer = 100;

        $this->command->info("Starting Stability Test Seeding...");
        $this->command->info("Creating {$totalEmployers} Employers with {$employeesPerEmployer} Employees each.");

        // Create employers in chunks to manage memory if needed, though 100 is small for employers.
        // We iterate to create employees for each.

        $bar = $this->command->getOutput()->createProgressBar($totalEmployers);
        $bar->start();

        for ($i = 0; $i < $totalEmployers; $i++) {
            $employer = Employer::factory()->create();

            // Create 100 employees for this employer
            // Using createMany or just factory count
            Employee::factory()
                ->count($employeesPerEmployer)
                ->create(['employer_id' => $employer->id]);

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("Seeding complete.");
    }
}
