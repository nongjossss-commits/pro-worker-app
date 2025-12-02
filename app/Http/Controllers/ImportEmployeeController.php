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

        if (!auth()->user()->can('create-employees')) {
            // Check ownership if strict
        }

        $path = $file->getPathname();

        $count = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();

            // Extract Images first to map them to rows
            $images = [];
            foreach ($sheet->getDrawingCollection() as $drawing) {
                // Check if it's in the Photo column (Column L = 12)
                $coordinates = $drawing->getCoordinates(); // e.g. "L2"
                $column = preg_replace('/[0-9]+/', '', $coordinates);
                $row = (int) preg_replace('/[A-Z]+/', '', $coordinates);

                if ($column === 'L') {
                    $images[$row] = $drawing;
                }
            }

            // Iterate rows starting from 2
            $highestRow = $sheet->getHighestRow();

            for ($rowIdx = 2; $rowIdx <= $highestRow; $rowIdx++) {
                // Get cell values
                $row = [];
                for ($colIdx = 1; $colIdx <= 11; $colIdx++) {
                    $val = $sheet->getCellByColumnAndRow($colIdx, $rowIdx)->getValue();
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
                    if (Date::isDateTime($sheet->getCellByColumnAndRow(5, $rowIdx))) {
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
