<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Employer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ImportEmployeeController extends Controller
{
    /**
     * Show the import form.
     */
    public function index()
    {
        // For admin/staff, they can select any employer.
        // For employer role, they are locked to their own employer record.
        $employers = collect();
        if (auth()->user()->can('view-employers')) {
             $employers = Employer::orderBy('employerNameTh')->get(['id', 'employerNameTh', 'employerNameEn']);
        } else {
             $user = auth()->user();
             if ($user->employer) {
                 $employers = collect([$user->employer]);
             }
        }

        return view('employees.import', compact('employers'));
    }

    /**
     * Handle the import (Grid or CSV).
     */
    public function store(Request $request)
    {
        // Check if it's the new Grid Import (array of employees)
        if ($request->has('employees') && is_array($request->employees)) {
            return $this->storeGrid($request);
        }

        // Fallback to old CSV Import
        return $this->storeCsv($request);
    }

    /**
     * Handle the new Grid/Excel-like Import
     */
    protected function storeGrid(Request $request)
    {
        $request->validate([
            'employer_id' => 'required|exists:employers,id',
            'employees' => 'required|array|min:1',
            // Basic validation for array items
            'employees.*.name_th' => 'nullable|string|max:255',
            'employees.*.name_en' => 'nullable|string|max:255',
            // Add more specific validations if needed, but loose validation allows partial data entry which can be fixed later.
        ]);

        $employerId = $request->input('employer_id');
        $employeesData = $request->input('employees'); // array of data
        $count = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($employeesData as $index => $row) {
                // Skip if both names are empty (empty row)
                if (empty($row['name_th']) && empty($row['name_en'])) {
                    continue;
                }

                // Handle Date of Birth
                $dob = null;
                if (!empty($row['dob'])) {
                    try {
                        $dob = Carbon::parse($row['dob'])->format('Y-m-d');
                    } catch (\Exception $e) {
                        $errors[] = "Row " . ($index + 1) . ": Invalid DOB format.";
                    }
                }

                // Determine nationality specific fields
                $nationality = $row['nationality'] ?? null;
                $passportTypeKey = 'passportType'; // default/Myanmar
                if ($nationality === 'กัมพูชา') {
                    $passportTypeKey = 'passport_type_cambodia';
                }

                $employeeData = [
                    'employer_id' => $employerId,
                    'employeeTitleTh' => $row['title_th'] ?? null,
                    'employeeNameTh' => $row['name_th'] ?? null,
                    'employeeNameEn' => $row['name_en'] ?? null,
                    'employeeDob' => $dob,
                    'employeeNationality' => $nationality,
                    'employeePassport' => $row['passport_no'] ?? null,
                    'employeeWorkPermit' => $row['work_permit_no'] ?? null,
                    'workPermitType' => $row['work_permit_type'] ?? null, // MOU/etc
                    'pinkCardNo' => $row['pink_card_no'] ?? null,
                    'status' => 'active',
                ];

                // Handle Book Type (CI, PJ, TD, etc)
                $bookType = $row['book_type'] ?? null;
                if ($nationality === 'กัมพูชา') {
                    $employeeData['passport_type_cambodia'] = $bookType;
                } else {
                    $employeeData['passportType'] = $bookType;
                }

                // Create Employee
                $employee = Employee::create($employeeData);

                // Handle Photo Upload
                // Since this is a file array: employees[0][photo]
                if ($request->hasFile("employees.{$index}.photo")) {
                    $file = $request->file("employees.{$index}.photo");
                    if ($file->isValid()) {
                        $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                        // Store in public disk: employee_files/{employer_id}/{filename}
                        $path = $file->storeAs("employee_files/{$employerId}", $filename, 'public');

                        $employee->update(['employeePhoto' => $path]);
                    }
                }

                $count++;
            }

            DB::commit();

            $message = "Successfully imported $count employees.";
            if (count($errors) > 0) {
                $message .= " Some warnings: " . implode(', ', $errors);
                return redirect()->route('employees.index')->with('success', $message)->with('import_errors', $errors);
            }

            return redirect()->route('employees.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return back()->with('error', 'Import failed: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Handle the CSV import (Legacy/Alternative).
     */
    protected function storeCsv(Request $request)
    {
        $request->validate([
            'employer_id' => 'required|exists:employers,id',
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $employerId = $request->input('employer_id');
        $file = $request->file('file');

        $path = $file->getRealPath();
        $handle = fopen($path, 'r');

        // Skip BOM
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            rewind($handle);
        }

        fgetcsv($handle); // Skip Header

        $count = 0;
        $errors = [];
        $rowNumber = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if (count($row) < 5 || empty($row[1])) {
                    continue;
                }

                $titleTh = trim($row[0] ?? '');
                $nameTh = trim($row[1] ?? '');
                $nameEn = trim($row[2] ?? '');
                // $gender = trim($row[3] ?? '');
                $dobRaw = trim($row[4] ?? '');
                $nationality = trim($row[5] ?? '');
                $passport = trim($row[6] ?? '');
                $wp = trim($row[7] ?? '');
                $wpType = trim($row[8] ?? '');
                $pinkCard = trim($row[9] ?? '');
                $bookType = trim($row[10] ?? '');

                if (empty($nameTh) && empty($nameEn)) {
                    $errors[] = "Row $rowNumber: Name is missing.";
                    continue;
                }

                $dob = null;
                if (!empty($dobRaw)) {
                    try {
                        $dob = Carbon::parse($dobRaw)->format('Y-m-d');
                    } catch (\Exception $e) {
                         $errors[] = "Row $rowNumber: Invalid Date format ($dobRaw).";
                         continue;
                    }
                }

                $employeeData = [
                    'employer_id' => $employerId,
                    'employeeTitleTh' => $titleTh,
                    'employeeNameTh' => $nameTh,
                    'employeeNameEn' => $nameEn,
                    'employeeDob' => $dob,
                    'employeeNationality' => $nationality,
                    'employeePassport' => $passport,
                    'employeeWorkPermit' => $wp,
                    'workPermitType' => $wpType,
                    'pinkCardNo' => $pinkCard,
                    'status' => 'active',
                ];

                if ($nationality === 'กัมพูชา') {
                    $employeeData['passport_type_cambodia'] = $bookType;
                } else {
                     $employeeData['passportType'] = $bookType;
                }

                Employee::create($employeeData);
                $count++;
            }

            DB::commit();
            return redirect()->route('employees.index')->with('success', "Imported $count employees (CSV).");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return back()->with('error', 'CSV Import failed: ' . $e->getMessage());
        } finally {
            fclose($handle);
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="employee_import_template.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [
            'Title (TH)', 'Name (TH)', 'Name (EN)', 'Gender',
            'Date of Birth (YYYY-MM-DD)', 'Nationality', 'Passport Number',
            'Work Permit Number', 'Work Permit Type', 'Pink Card Number', 'Book Type'
        ];

        $sample = ['นาย', 'สมชาย ใจดี', 'Somchai Jaidee', 'Male', '1990-01-31', 'เมียนมา', 'PP123456', 'WP987654', 'MOU', '-', 'CI'];

        $callback = function() use ($columns, $sample) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns);
            fputcsv($file, $sample);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
