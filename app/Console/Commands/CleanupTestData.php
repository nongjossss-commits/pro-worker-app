<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employer;
use Illuminate\Support\Facades\DB;

class CleanupTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-test-data {--force : Force the operation to run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes test data (Employers and Employees) created by seeders. Targets Employers without a user_id.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('This will delete all Employers (and their Employees) that do not have an associated User. Are you sure?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Starting cleanup of test data...');

        // Find employers with null user_id
        // The StabilityTestSeeder creates employers with null user_id (via factory).
        // Normal employers should have a user_id (from RoleAndPermissionSeeder or Manual Creation).
        $employersToDelete = Employer::whereNull('user_id');

        $count = $employersToDelete->count();

        if ($count === 0) {
            $this->info('No test data (Employers with null user_id) found.');
            return 0;
        }

        $this->info("Found {$count} employers to delete.");

        // We need to force delete to actually remove them from the database if SoftDeletes is on.
        // Since the request is to "delete" (remove), force delete is appropriate for cleaning test data.

        // We can't just call forceDelete on the builder if relationships need to be handled,
        // but the database usually handles cascade if defined.
        // However, Laravel's model events (like deleting files) only fire if we retrieve models.
        // For 100 records (or thousands), we should chunk or iterate if we want model events.
        // Given the likely scale (100 employers * 100 employees = 10,000 records),
        // iterating might be slow but safer for cleanup.
        // But wait, StabilityTestSeeder disables query log.

        // Let's just use the builder for speed, assuming no critical side effects (like files) for *test* data.
        // The factory doesn't seem to create files (Faker creates strings).

        // NOTE: Employees are linked to Employer. If we delete Employer, we should ensure Employees are deleted.
        // database/migrations/2025_08_29_134131_create_employees_table.php says:
        // $table->foreignId('employer_id')->constrained()->onDelete('cascade');
        // So database level cascade should work.

        // However, SoftDeletes on models might interfere with DB cascade if we use Eloquent delete().
        // If we use forceDelete(), it performs a HARD DELETE query.

        // Let's verify if forceDelete() on Builder works for SoftDeletes models.
        // Yes, it performs `DELETE FROM table WHERE ...`.

        DB::beginTransaction();
        try {
            // We need to delete employers.
            // To be safe and ensure cascade works (if DB constraint is relied upon), we just delete employers.
            // But if SoftDeletes is enabled on Employee, the DB cascade `ON DELETE CASCADE` *will* delete rows physically if the parent is physically deleted.

            $deleted = $employersToDelete->forceDelete();

            DB::commit();
            $this->info("Successfully deleted {$deleted} employers and their associated employees (via DB cascade).");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("An error occurred: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
