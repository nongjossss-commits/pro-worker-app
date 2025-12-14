<?php

use App\Models\Employee;
use App\Models\Employer;
use App\Models\RegistrationStep;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Debugging Registration Stats ---\n";

// 1. Check Steps
$steps = RegistrationStep::orderBy('order')->get();
echo "Total Steps: " . $steps->count() . "\n";
if ($steps->isEmpty()) {
    echo "WARNING: No steps found!\n";
} else {
    foreach ($steps as $step) {
        echo "Step ID: {$step->id}, Order: {$step->order}, Name: {$step->name}\n";
    }
}
$stepOneId = $steps->sortBy('order')->first()?->id;
echo "Step One ID: " . ($stepOneId ?? 'NULL') . "\n";

// 2. Check Employees Query
$query = Employee::whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled']);
$countQuery = $query->count();
echo "Total Query Match Count: $countQuery\n";

$registrationEmployees = $query->get();
echo "Collection Count: " . $registrationEmployees->count() . "\n";

// 3. Check Specific Statuses
$pending = $registrationEmployees->where('status', 'registration_pending')->count();
$completed = $registrationEmployees->where('status', 'registration_completed')->count();
$cancelled = $registrationEmployees->where('status', 'registration_cancelled')->count();
echo "Pending: $pending\n";
echo "Completed: $completed\n";
echo "Cancelled: $cancelled\n";

// 4. Check Not Started Logic
$notStartedCount = $registrationEmployees->filter(function ($emp) use ($stepOneId) {
     if ($emp->status === 'registration_cancelled') return false;
     // Check relationship loaded?
     // We didn't eager load in this script, let's fix that.
     return false; // placeholder
})->count();

// Re-run with eager loading
$registrationEmployees = Employee::whereIn('status', ['registration_pending', 'registration_completed', 'registration_cancelled'])
                         ->with('registrationSteps')
                         ->get();

$notStartedCount = $registrationEmployees->filter(function ($emp) use ($stepOneId) {
     if ($emp->status === 'registration_cancelled') return false;
     $hasStep = $emp->registrationSteps->contains('id', $stepOneId);
     return !$hasStep;
})->count();

echo "Calculated Not Started Count: $notStartedCount\n";

// 5. Check if 'status' field is actually populated
$firstEmp = $registrationEmployees->first();
if ($firstEmp) {
    echo "First Employee Status: '{$firstEmp->status}'\n";
    echo "First Employee Steps Count: " . $firstEmp->registrationSteps->count() . "\n";
    if ($stepOneId) {
        echo "First Employee Has Step 1? " . ($firstEmp->registrationSteps->contains('id', $stepOneId) ? 'YES' : 'NO') . "\n";
    }
}

// 6. Check Employer Logic
$filteredEmployerIds = $registrationEmployees->pluck('employer_id')->unique();
echo "Unique Employer IDs: " . $filteredEmployerIds->count() . "\n";

$employers = Employer::whereIn('id', $filteredEmployerIds)->get();
echo "Employers Fetched: " . $employers->count() . "\n";
