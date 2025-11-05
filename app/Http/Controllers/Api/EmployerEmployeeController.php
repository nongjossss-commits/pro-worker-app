namespace App\Http\Controllers\Api;
use App\Helpers\CountryHelper;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
class EmployerEmployeeController extends Controller {
/**
* Get a list of employees.
* V2.4-S19 (Plan B) Fix:
* - If user is Employer, use Global Scope (default).
* - If user is Admin/Staff, use withoutGlobalScopes() to prevent leak.
* - Eager load employer relationship to add employer_id/name to response.
*/
public function index(): JsonResponse {
$user = Auth::user();
if (!$user->hasRole('employer') && !$user->can('manage-tickets')) {
return response()->json(['error' => 'Unauthorized'], 403);
}
// --- V2.4-S19 (Plan B) Logic START ---
$query = Employee::query();
if ($user->can('manage-tickets')) {
// Admin/Staff: Bypass tenancy scope to fetch ALL employees
$query->withoutGlobalScopes();
}
// Note: If user is 'employer', the Global Scope applies automatically.
// --- V2.4-S19 (Plan B) Logic END ---
// V2.4-S19: Eager load employer relationship
$employees = $query->with('employer:id,employerNameTh') // Load only necessary fields
->whereNull('terminated_at')
->orderBy('employeeNameTh')
->get(['id', 'employer_id', 'employeeNameTh', 'employeeNameEn', 'employeePassport', 'companyWorkerId', 'employeePhoto', 'employeeNationality']);
// CRITICAL: Append the accessor so it's included in the JSON response.
$employees->append('photo_url');
// V2.5-S2: Add nationality and flag URL
$employeesData = $employees->map(function ($employee) {
$nationality = $employee->employeeNationality;
$countryCode = CountryHelper::getCountryCode($nationality);
$flagUrl = $countryCode ? asset('images/flags/' . strtolower($countryCode) . '.png') : null;
return [
'id' => $employee->id,
// --- V2.4-S19 (Plan B) Additions START ---
'employer_id' => $employee->employer_id,
'employer_name' => $employee->employer?->employerNameTh ?? 'N/A', // Add Employer Name
// --- V2.4-S19 (Plan B) Additions END ---
'employeeNameTh' => $employee->employeeNameTh,
'employeeNameEn' => $employee->employeeNameEn,
'employeePassport' => $employee->employeePassport,
'companyWorkerId' => $employee->companyWorkerId,
'photo_url' => $employee->photo_url, // Accessor field
'nationality' => $nationality,
'flag_url' => $flagUrl,
];
});
return response()->json($employeesData);
}
}