<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Employer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportEmployeeController extends Controller
{
    /**
     * Show the import form.
     */
    public function index()
    {
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
     * Download an Excel template for importing employees.
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $columns = [
            'Title (TH)',
            'Name (TH)',
            'Name (EN)',
            'Gender',
            'Date of Birth (YYYY-MM-DD)',
            'Nationality',
            'Passport Number',
            'Work Permit Number',
            'Work Permit Type',
            'Pink Card Number',
            'CI/PJ/TD/Inter',
            'Photo (Insert Image in Cell)'
        ];

        // Set Headers
        foreach ($columns as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        // Sample Data
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

        foreach ($sample as $index => $value) {
            $sheet->setCellValueByColumnAndRow($index + 1, 2, $value);
        }

        // Auto-size columns
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set Photo column width larger to encourage image placement
        $sheet->getColumnDimension('L')->setAutoSize(false);
        $sheet->getColumnDimension('L')->setWidth(30);
        $sheet->getRowDimension(2)->setRowHeight(50); // Make the sample row taller for image space

        $writer = new Xlsx($spreadsheet);

        $callback = function() use ($writer) {
            $writer->save('php://output');
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="employee_import_template.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Handle the Excel import.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employer_id' => 'required|exists:employers,id',
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB limit
        ]);

        $employerId = $request->input('employer_id');
        $file = $request->file('file');
        $path = $file->getPathname();

        $errors = [];
        $employeesToCreate = [];

        try {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            // Extract images first to map them to rows
            $images = [];
            foreach ($sheet->getDrawingCollection() as $drawing) {
                $coordinates = $drawing->getCoordinates();
                $column = preg_replace('/[0-9]+/', '', $coordinates);
                $row = (int) preg_replace('/[A-Z]+/', '', $coordinates);
                if ($column === 'L') {
                    $images[$row] = $drawing;
                }
            }

            // Phase 1: Validate all rows before collecting data for import
            for ($rowIdx = 2; $rowIdx <= $highestRow; $rowIdx++) {
                $rowValues = [];
                for ($colIdx = 1; $colIdx <= 11; $colIdx++) {
                    $val = $sheet->getCellByColumnAndRow($colIdx, $rowIdx)->getValue();
                    $rowValues[] = trim((string)$val);
                }

                if (empty($rowValues[1]) && empty($rowValues[2])) { // Skip empty rows
                    continue;
                }

                $rowErrors = []; // Store errors for the current row
                $nameTh = $rowValues[1];
                $dobRaw = $rowValues[4];
                $dob = null;

                // ** Per-Row Validation Logic **
                if (empty($nameTh)) {
                    $rowErrors[] = "Row $rowIdx: Name (TH) is a required field.";
                }

                if (!empty($dobRaw)) {
                    if (Date::isDateTime($sheet->getCellByColumnAndRow(5, $rowIdx))) {
                        $dob = Carbon::instance(Date::excelToDateTimeObject($dobRaw))->format('Y-m-d');
                    } else {
                        try {
                            $dob = Carbon::parse($dobRaw)->format('Y-m-d');
                        } catch (\Exception $e) {
                            $rowErrors[] = "Row $rowIdx: Invalid Date of Birth format ('$dobRaw'). Please use YYYY-MM-DD.";
                        }
                    }
                }

                // If this row has errors, add them to the main error list and continue
                if (count($rowErrors) > 0) {
                    $errors = array_merge($errors, $rowErrors);
                    continue;
                }

                // If validation for this row passed, prepare its data for import
                $employeesToCreate[] = [
                    'rowIdx' => $rowIdx,
                    'data' => [
                        'employer_id' => $employerId,
                        'employeeTitleTh' => $rowValues[0],
                        'employeeNameTh' => $nameTh,
                        'employeeNameEn' => $rowValues[2],
                        'employeeDob' => $dob,
                        'employeeNationality' => $rowValues[5],
                        'employeePassport' => $rowValues[6],
                        'employeeWorkPermit' => $rowValues[7],
                        'workPermitType' => $rowValues[8],
                        'pinkCardNo' => $rowValues[9],
                        'passportType' => $rowValues[10], // Generic passport type
                        'status' => 'active',
                    ],
                    'drawing' => $images[$rowIdx] ?? null
                ];
            }

            // Phase 2: If validation fails, redirect back with errors
            if (!empty($errors)) {
                return back()->with('import_errors', $errors)->withInput();
            }

            // Phase 3: If validation succeeds, proceed with database transaction
            DB::beginTransaction();

            $count = 0;
            foreach ($employeesToCreate as $employee) {
                $employeeData = $employee['data'];
                $drawing = $employee['drawing'];
                $photoPath = null;

                if ($drawing) {
                    try {
                        $imageContent = null;
                        $extension = 'jpg';
                        if ($drawing instanceof MemoryDrawing) {
                            ob_start();
                            call_user_func($drawing->getRenderingFunction(), $drawing->getImageResource());
                            $imageContent = ob_get_contents();
                            ob_end_clean();
                            switch ($drawing->getMimeType()) {
                                case MemoryDrawing::MIMETYPE_PNG: $extension = 'png'; break;
                                case MemoryDrawing::MIMETYPE_GIF: $extension = 'gif'; break;
                                case MemoryDrawing::MIMETYPE_JPEG: $extension = 'jpg'; break;
                            }
                        } elseif ($drawing instanceof Drawing) {
                            $imagePath = $drawing->getPath();
                            if ($imagePath && file_exists($imagePath)) {
                                $imageContent = file_get_contents($imagePath);
                                $extension = pathinfo($imagePath, PATHINFO_EXTENSION) ?? 'jpg';
                            }
                        }

                        if ($imageContent) {
                            $filename = 'import_' . uniqid() . '.' . $extension;
                            $storagePath = 'employee_photos/' . $filename;
                            Storage::disk('public')->put($storagePath, $imageContent);
                            $photoPath = $storagePath;
                        }
                    } catch (\Exception $imgEx) {
                        Log::warning("Failed to process image for row {$employee['rowIdx']}: " . $imgEx->getMessage());
                    }
                }
                $employeeData['employeePhoto'] = $photoPath;

                // Handle nationality-specific logic
                if ($employeeData['employeeNationality'] === 'กัมพูชา') {
                    $employeeData['passport_type_cambodia'] = $employeeData['passportType'];
                    unset($employeeData['passportType']);
                }

                Employee::create($employeeData);
                $count++;
            }

            DB::commit();

            return redirect()->route('employees.index')->with('success', "Successfully imported $count employees.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return back()->with('error', 'An unexpected error occurred during import: ' . $e->getMessage());
        }
    }
}
