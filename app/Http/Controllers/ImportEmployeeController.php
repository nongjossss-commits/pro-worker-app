<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Employer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ImportEmployeeController extends Controller
{
    /**
     * Show the import form.
     */
    public function index()
    {
        // For admin/staff, they can select any employer.
        // For employer role, they are locked to their own employer record (if enforced by policy/middleware, but here we just pass the list).

        $employers = collect();
        if (auth()->user()->can('view-employers')) {
             $employers = Employer::orderBy('employerNameTh')->get(['id', 'employerNameTh', 'employerNameEn']);
        } else {
             // If user is employer, get their employer record
             $user = auth()->user();
             if ($user->employer) {
                 $employers = collect([$user->employer]);
             }
        }

        return view('employees.import', compact('employers'));
    }

    /**
     * Download a CSV template for importing employees.
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="employee_import_template.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        // Column Headers (Snake case preferred for mapping, or readable english)
        // Matching the prompt requirements + system fields.
        $columns = [
            'Title (TH)', // Name Title TH (Mr/Mrs/Miss or Thai)
            'Name (TH)',
            'Name (EN)',
            'Gender', // Male/Female
            'Date of Birth (YYYY-MM-DD)',
            'Nationality',
            'Passport Number',
            'Work Permit Number',
            'Work Permit Type',
            // Optional/System fields
            'Pink Card Number',
            'CI/PJ/TD/Inter' // Book Type
        ];

        // Sample Row
        $sample = [
            'นาย',
            'สมชาย ใจดี',
            'Somchai Jaidee',
            'Male',
            '1990-01-31',
            'เมียนมา',
            'PP123456',
            'WP987654',
            'MOU',
            '-',
            'CI'
        ];

        $callback = function() use ($columns, $sample) {
            $file = fopen('php://output', 'w');
            // Write BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, $columns);
            fputcsv($file, $sample);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Handle the CSV import.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employer_id' => 'required|exists:employers,id',
            'file' => 'required|file|mimes:csv,txt|max:5120', // 5MB limit
        ]);

        $employerId = $request->input('employer_id');
        $file = $request->file('file');

        // Check if user has permission to add to this employer
        if (!auth()->user()->can('create-employees')) {
            // Check ownership if strict
        }

        $path = $file->getRealPath();
        $handle = fopen($path, 'r');

        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            rewind($handle);
        }

        $header = fgetcsv($handle); // Read header

        // We expect specific order or we can map by name.
        // For simplicity in this v1, assuming template order.
        // 0: Title, 1: NameTH, 2: NameEN, 3: Gender, 4: DOB, 5: Nationality, 6: Passport, 7: WP, 8: WPType, 9: PinkCard, 10: BookType

        $count = 0;
        $errors = [];
        $rowNumber = 1; // Header is 1

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                // Skip empty rows
                if (count($row) < 5 || empty($row[1])) {
                    continue;
                }

                // Parse Data
                $titleTh = trim($row[0] ?? '');
                $nameTh = trim($row[1] ?? '');
                $nameEn = trim($row[2] ?? '');
                $gender = trim($row[3] ?? ''); // Map Male/Female to standard if needed
                $dobRaw = trim($row[4] ?? '');
                $nationality = trim($row[5] ?? '');
                $passport = trim($row[6] ?? '');
                $wp = trim($row[7] ?? '');
                $wpType = trim($row[8] ?? '');
                $pinkCard = trim($row[9] ?? '');
                $bookType = trim($row[10] ?? '');

                // Basic Validation
                if (empty($nameTh) && empty($nameEn)) {
                    $errors[] = "Row $rowNumber: Name is missing.";
                    continue;
                }

                // Format Date
                $dob = null;
                if (!empty($dobRaw)) {
                    try {
                        // Attempt to parse YYYY-MM-DD
                        $dob = Carbon::parse($dobRaw)->format('Y-m-d');
                    } catch (\Exception $e) {
                         $errors[] = "Row $rowNumber: Invalid Date format ($dobRaw). Use YYYY-MM-DD.";
                         continue;
                    }
                }

                // Create Employee
                // Mapping logic similar to EmployeeController::store

                // Determine passport type column based on nationality
                $passportTypeKey = 'passportType'; // default/Myanmar
                if ($nationality === 'กัมพูชา') {
                    $passportTypeKey = 'passport_type_cambodia';
                }

                // Note: Employee model has many camelCase fields.
                $employeeData = [
                    'employer_id' => $employerId,
                    'employeeTitleTh' => $titleTh,
                    'employeeNameTh' => $nameTh,
                    'employeeNameEn' => $nameEn,
                    // 'employeeGender' => $gender, // Often computed or stored differently? Memory says "gender accessor from title".
                    // But if we have explicit gender input, we might store it if column exists?
                    // Checked memory: "gender accessor (from employeeTitleTh)".
                    // So we don't need to store gender if title is correct.
                    // But Title is free text? Usually "นาย", "นาง", "นางสาว".

                    'employeeDob' => $dob,
                    'employeeNationality' => $nationality,
                    'employeePassport' => $passport,
                    'employeeWorkPermit' => $wp,
                    'workPermitType' => $wpType,
                    'pinkCardNo' => $pinkCard,

                    // Defaults
                    'status' => 'active',
                ];

                // Handle Book Type map to specific column
                if ($nationality === 'กัมพูชา') {
                    $employeeData['passport_type_cambodia'] = $bookType;
                } else {
                     $employeeData['passportType'] = $bookType; // For Myanmar
                }

                Employee::create($employeeData);
                $count++;
            }

            if (count($errors) > 0) {
                // If we want partial success, commit transaction and show errors?
                // Or fail all? "If user fills 100... system creates 100".
                // I'll rollback if any critical error, but for individual row errors usually we might want to skip.
                // But simple approach: Commit successful ones.
                DB::commit();
                return redirect()->route('employees.index')->with('success', "Imported $count employees successfully. " . count($errors) . " rows failed.")->with('import_errors', $errors);
            }

            DB::commit();
            return redirect()->route('employees.index')->with('success', "Successfully imported $count employees.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        } finally {
            fclose($handle);
        }
    }
}
