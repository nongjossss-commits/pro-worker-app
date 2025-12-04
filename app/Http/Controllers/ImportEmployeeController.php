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
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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

        // --- EMPLOYER INFO HEADER SECTION (Rows 1-11) ---
        // We will create a layout mimicking the provided image roughly.
        // We'll use columns A-L, merging as needed.

        // Default Font
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Angsana New')->setSize(16);

        // Row 1: Employer Name & ID Card
        $sheet->setCellValue('A1', 'ชื่อนายจ้าง');
        $sheet->mergeCells('B1:F1'); // Name Input
        $sheet->setCellValue('G1', 'เลขประจำตัวประชาชน');
        $sheet->setCellValue('G2', '/ทะเบียนนิติบุคคล');
        $sheet->mergeCells('H1:L1'); // ID Input
        $sheet->mergeCells('H2:L2'); // ID Input continuation if needed or just align

        // Row 3: Business Type & Request No
        $sheet->setCellValue('A3', 'ประเภทธุรกิจ');
        $sheet->mergeCells('B3:F3'); // Business Input
        $sheet->setCellValue('G3', 'เลขคำขอ');
        $sheet->mergeCells('H3:L3'); // Request Input

        // Row 4: Address
        $sheet->setCellValue('A4', 'ที่อยู่');
        $sheet->mergeCells('B4:L4');

        // Row 5: Moo, Soi, Road
        $sheet->setCellValue('A5', 'หมู่ที่/อาคาร');
        $sheet->mergeCells('B5:D5');
        $sheet->setCellValue('E5', 'ซอย');
        $sheet->mergeCells('F5:H5');
        $sheet->setCellValue('I5', 'ถนน');
        $sheet->mergeCells('J5:L5');

        // Row 6: Subdistrict, District
        $sheet->setCellValue('A6', 'ตำบล/แขวง');
        $sheet->mergeCells('B6:E6');
        $sheet->setCellValue('F6', 'อำเภอ/เขต');
        $sheet->mergeCells('G6:L6');

        // Row 7: Province, Zip
        $sheet->setCellValue('A7', 'จังหวัด');
        $sheet->mergeCells('B7:E7');
        $sheet->setCellValue('F7', 'รหัสไปรษณีย์');
        $sheet->mergeCells('G7:L7');

        // Row 8: Tel, Email
        $sheet->setCellValue('A8', 'โทรศัพท์');
        $sheet->mergeCells('B8:E8');
        $sheet->setCellValue('F8', 'e-Mail');
        $sheet->mergeCells('G8:L8');

        // Row 9-10: Requirement Block
        // "มีความต้องการจ้างแรงงานต่างด้าว"
        $sheet->mergeCells('I9:L9');
        $sheet->setCellValue('I9', 'มีความต้องการจ้างแรงงานต่างด้าว');
        $sheet->getStyle('I9')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF0F0F0');
        $sheet->getStyle('I9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('I10:L10');
        $sheet->setCellValue('I10', 'สัญชาติ _________ จำนวน ____ คน');
        $sheet->getStyle('I10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF0F0F0');
        $sheet->getStyle('I10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('I11', 'ตามรายชื่อดังต่อไปนี้');


        // Add Underlines (Bottom Border) to input cells for form look
        $inputCells = [
            'B1:F1', 'H1:L2',
            'B3:F3', 'H3:L3',
            'B4:L4',
            'B5:D5', 'F5:H5', 'J5:L5',
            'B6:E6', 'G6:L6',
            'B7:E7', 'G7:L7',
            'B8:E8', 'G8:L8'
        ];
        foreach ($inputCells as $range) {
             $sheet->getStyle($range)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        }

        // --- DATA TABLE HEADERS (Row 12) ---
        $headerRow = 12;
        $columns = [
            'Photo (Insert Image)', // A
            'Title (TH)',           // B
            'Name (TH)',            // C
            'Name (EN)',            // D
            'Gender',               // E
            'Date of Birth (YYYY-MM-DD)', // F
            'Nationality',          // G
            'Passport Number',      // H
            'Work Permit Number',   // I
            'Work Permit Type',     // J
            'Pink Card Number',     // K
            'CI/PJ/TD/Inter'        // L
        ];

        foreach ($columns as $index => $header) {
            $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
            $cell = $columnLetter . $headerRow;
            $sheet->setCellValue($cell, $header);

            // Style header
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF0F0F0');
            $sheet->getStyle($cell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            // Add border
            $sheet->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        // Add instructions as a comment on Photo header
        $sheet->getComment('A'.$headerRow)->getText()->createTextRun('Insert employee photo here. Image should fit within the cell.');

        // --- PRE-FORMAT DATA ROWS (13 to 112) ---
        $startDataRow = 13;
        $endDataRow = 112; // 100 rows

        // Set dimensions
        // Column A (Photo) Width ~ 20 (approx 140px width)
        $sheet->getColumnDimension('A')->setWidth(20);

        // Auto-size other columns (B to L)
        foreach (range('B', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set Row Height for data rows to accommodate photos (e.g., 60-80 points)
        for ($r = $startDataRow; $r <= $endDataRow; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(80);

            // Apply borders to all cells in the grid
            $rowRange = "A{$r}:L{$r}";
            $sheet->getStyle($rowRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle($rowRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'employee_import_template.xlsx';

        return response()->streamDownload(function() use ($writer) {
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
        $importedEmployees = [];

        DB::beginTransaction();
        try {
            $spreadsheet = IOFactory::load($path);

            // Robustly get the first sheet.
            try {
                $sheet = $spreadsheet->getSheet(0);
            } catch (\Exception $e) {
                $sheet = $spreadsheet->getActiveSheet();
            }

            // Define Header Row
            // We assume headers are on Row 12, so Data starts at Row 13
            $START_ROW = 13;

            // Extract Images first to map them to rows
            $images = [];
            foreach ($sheet->getDrawingCollection() as $drawing) {
                // Get coordinates (e.g. "A13" or "A13:B14")
                $coords = $drawing->getCoordinates();

                // Extract top-left cell coordinate
                if (preg_match('/^([A-Z]+)(\d+)/', $coords, $matches)) {
                    $column = $matches[1];
                    $row = (int)$matches[2];

                    // Look for images in Column A
                    if ($column === 'A' && $row >= $START_ROW) {
                        $images[$row] = $drawing;
                    }
                }
            }

            if (!empty($images)) {
                Log::info("Found " . count($images) . " images in import file starting from row $START_ROW.");
            }

            // Iterate rows starting from START_ROW
            $highestRow = $sheet->getHighestRow();

            for ($rowIdx = $START_ROW; $rowIdx <= $highestRow; $rowIdx++) {
                // Get cell values
                // Mapped Columns:
                // A (1) = Photo
                // B (2) = Title (TH)
                // C (3) = Name (TH)
                // D (4) = Name (EN)
                // E (5) = Gender
                // F (6) = DOB
                // G (7) = Nationality
                // H (8) = Passport
                // I (9) = WP
                // J (10) = WP Type
                // K (11) = Pink Card
                // L (12) = Book Type

                $row = [];
                // We read columns 2 (B) through 12 (L) for text data
                for ($colIdx = 2; $colIdx <= 12; $colIdx++) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                    $val = $sheet->getCell($colLetter . $rowIdx)->getValue();
                    $row[] = trim((string)$val);
                }

                // Check if empty row (Title, NameTH, NameEN are in indices 0, 1, 2 of $row array)
                // $row array is 0-indexed relative to the loop.
                // Loop starts at col 2 (B), so $row[0] is Col B, $row[1] is Col C.
                if (empty($row[0]) && empty($row[1])) {
                    // It might be one of the pre-formatted empty rows.
                    continue;
                }

                // Parse Data
                $titleTh = $row[0]; // B
                $nameTh = $row[1];  // C
                $nameEn = $row[2];  // D
                // $gender = $row[3]; // E
                $dobRaw = $row[4];  // F
                $nationality = $row[5]; // G
                $passport = $row[6];    // H
                $wp = $row[7];          // I
                $wpType = $row[8];      // J
                $pinkCard = $row[9];    // K
                $bookType = $row[10];   // L

                 // Format Date
                $dob = null;
                if (!empty($dobRaw)) {
                    // DOB is at Col 6 (F)
                    $dobColLetter = 'F';
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

                // Process Image from Column A
                $photoPath = null;
                if (isset($images[$rowIdx])) {
                    $drawing = $images[$rowIdx];
                    $imageContent = null;
                    $extension = 'jpg';

                    try {
                        if ($drawing instanceof MemoryDrawing) {
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

                $employee = Employee::create($employeeData);
                $importedEmployees[] = $employee;
                $count++;
            }

            DB::commit();

            $msg = "Successfully imported $count employees.";
            if (count($errors)) {
                $msg .= " With " . count($errors) . " errors.";
            }

            return back()->with('success', $msg)
                         ->with('import_errors', $errors)
                         ->with('imported_employees', $importedEmployees);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
