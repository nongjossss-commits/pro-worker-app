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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

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
        // Check for required extensions
        if (!extension_loaded('gd')) {
            return back()->with('error', 'The PHP GD extension is required to generate the template with image support. Please enable it in your server configuration (php.ini).');
        }
        if (!extension_loaded('zip')) {
            return back()->with('error', 'The PHP Zip extension is required to generate Excel files. Please enable it in your server configuration (php.ini).');
        }

        // Prevent any output buffering from corrupting the Excel file
        if (ob_get_length()) ob_end_clean();

        $spreadsheet = new Spreadsheet();
        // Remove the default sheet to ensure we start clean
        $spreadsheet->removeSheetByIndex(0);

        // Create a new sheet with a specific name
        $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Employees');
        $spreadsheet->addSheet($sheet, 0);

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
            $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($columnLetter . '1', $header);
            // Style header
            $sheet->getStyle($columnLetter . '1')->getFont()->setBold(true);
            $sheet->getStyle($columnLetter . '1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF0F0F0');
        }

        // Add instructions in a comment or separate row?
        // Let's add a note above headers (Row 1) and headers at Row 2?
        // No, let's keep headers at Row 1 for simplicity, but add a comment to the Photo header.
        $sheet->getComment('L1')->getText()->createTextRun('Insert your employee photos into this column. Ensure the image fits within the cell boundaries.');

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
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . '2', $value);
        }

        // Auto-size columns
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set Photo column width larger to encourage image placement
        $sheet->getColumnDimension('L')->setAutoSize(false);
        $sheet->getColumnDimension('L')->setWidth(30);
        $sheet->getRowDimension(2)->setRowHeight(80); // Make the sample row taller for image space

        $writer = new Xlsx($spreadsheet);

        $filename = 'employee_import_template.xlsx';

        return response()->streamDownload(function() use ($writer) {
             // Ensure clean buffer again inside the callback
             if (ob_get_length()) ob_end_clean();
             $writer->save('php://output');
        }, $filename);
    }

    /**
     * Handle the Excel import.
     */
    public function store(Request $request)
    {
        // Check for required extensions
        if (!extension_loaded('gd')) {
            return back()->with('error', 'The PHP GD extension is required to process images in the import file. Please enable it in your server configuration (php.ini).');
        }
        if (!extension_loaded('zip')) {
            return back()->with('error', 'The PHP Zip extension is required to read Excel files. Please enable it in your server configuration (php.ini).');
        }

        $request->validate([
            'employer_id' => 'required|exists:employers,id',
            'file' => 'required|file|mimes:xlsx,xls,xlsm|max:20480', // 20MB limit
        ]);

        $employerId = $request->input('employer_id');
        $file = $request->file('file');

        if (!auth()->user()->can('create-employees')) {
            // Check ownership if strict
        }

        $path = $file->getPathname();

        $count = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            $spreadsheet = IOFactory::load($path);

            // Robustly get the first sheet.
            // This fixes "Sheet not found" if 'getActiveSheet' relies on internal state that might be empty or invalid.
            try {
                $sheet = $spreadsheet->getSheet(0);
            } catch (\Exception $e) {
                // Fallback to active sheet if getSheet(0) somehow fails (unlikely)
                $sheet = $spreadsheet->getActiveSheet();
            }

            // Extract Images first to map them to rows
            $images = [];
            foreach ($sheet->getDrawingCollection() as $drawing) {
                // Get coordinates (e.g. "L2")
                $coords = $drawing->getCoordinates();

                // Parse column and row
                // The regex captures the column letter(s) and the row number
                if (preg_match('/^([A-Z]+)(\d+)$/', $coords, $matches)) {
                    $column = $matches[1];
                    $row = (int)$matches[2];

                    // We strictly look for images anchored in Column L (Photo column)
                    if ($column === 'L') {
                        $images[$row] = $drawing;
                    }
                }
            }

            if (!empty($images)) {
                Log::info("Found " . count($images) . " images in import file.");
            }

            // Iterate rows starting from 2
            $highestRow = $sheet->getHighestRow();

            for ($rowIdx = 2; $rowIdx <= $highestRow; $rowIdx++) {
                // Get cell values
                $row = [];
                for ($colIdx = 1; $colIdx <= 11; $colIdx++) {
                    // Use format value or raw value? GetValue is raw.
                    $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                    $val = $sheet->getCell($colLetter . $rowIdx)->getValue();
                    $row[] = trim((string)$val);
                }

                // Check if empty row (skip if NameTH and NameEN are empty)
                if (empty($row[1]) && empty($row[2])) {
                    continue;
                }

                // Parse Data
                $titleTh = $row[0];
                $nameTh = $row[1];
                $nameEn = $row[2];
                // $gender = $row[3];
                $dobRaw = $row[4];
                $nationality = $row[5];
                $passport = $row[6];
                $wp = $row[7];
                $wpType = $row[8];
                $pinkCard = $row[9];
                $bookType = $row[10];

                 // Format Date
                $dob = null;
                if (!empty($dobRaw)) {
                    $dobColLetter = Coordinate::stringFromColumnIndex(5);
                    if (Date::isDateTime($sheet->getCell($dobColLetter . $rowIdx))) {
                         $dob = Carbon::instance(Date::excelToDateTimeObject($dobRaw))->format('Y-m-d');
                    } else {
                         try {
                            $dob = Carbon::parse($dobRaw)->format('Y-m-d');
                         } catch (\Exception $e) {
                             $errors[] = "Row $rowIdx: Invalid Date format ($dobRaw).";
                         }
                    }
                }

                // Process Image
                $photoPath = null;
                if (isset($images[$rowIdx])) {
                    $drawing = $images[$rowIdx];
                    $imageContent = null;
                    $extension = 'jpg';

                    try {
                        if ($drawing instanceof MemoryDrawing) {
                            // Ensure buffer is clean before starting specific image capture
                            if (ob_get_length()) ob_end_clean();

                            ob_start();
                            call_user_func(
                                $drawing->getRenderingFunction(),
                                $drawing->getImageResource()
                            );
                            $imageContent = ob_get_contents();
                            ob_end_clean();

                            switch ($drawing->getMimeType()) {
                                case MemoryDrawing::MIMETYPE_PNG :
                                    $extension = 'png'; break;
                                case MemoryDrawing::MIMETYPE_GIF:
                                    $extension = 'gif'; break;
                                case MemoryDrawing::MIMETYPE_JPEG :
                                    $extension = 'jpg'; break;
                            }
                        } elseif ($drawing instanceof Drawing) {
                            // Helper to get image contents safely
                            $imagePath = $drawing->getPath();
                            if ($imagePath && file_exists($imagePath)) {
                                 $imageContent = file_get_contents($imagePath);
                                 $info = pathinfo($imagePath);
                                 $extension = $info['extension'] ?? 'jpg';
                            }
                        }

                        if ($imageContent) {
                            $filename = 'import_' . uniqid() . '.' . $extension;
                            $storagePath = 'employee_photos/' . $filename;

                            // Ensure directory exists
                            if (!Storage::disk('public')->exists('employee_photos')) {
                                Storage::disk('public')->makeDirectory('employee_photos');
                            }

                            Storage::disk('public')->put($storagePath, $imageContent);
                            $photoPath = $storagePath;
                        }
                    } catch (\Exception $imgEx) {
                        Log::warning("Failed to process image for row $rowIdx: " . $imgEx->getMessage());
                    }
                }

                // Determine passport type
                $passportTypeKey = 'passportType';
                if ($nationality === 'กัมพูชา') {
                    $passportTypeKey = 'passport_type_cambodia';
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
                    'employeePhoto' => $photoPath,
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

            $msg = "Successfully imported $count employees.";
            if (count($errors)) {
                $msg .= " With " . count($errors) . " errors.";
                return redirect()->route('employees.index')->with('success', $msg)->with('import_errors', $errors);
            }

            return redirect()->route('employees.index')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
