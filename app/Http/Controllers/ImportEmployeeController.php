<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProductionOrder;
use App\Models\ProductionItem;
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
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ImportEmployeeController extends Controller
{
    /**
     * Show the import form.
     */
    public function index(Request $request)
    {
        $productionId = $request->query('production_id');
        $production = null;

        if ($productionId) {
            $production = ProductionOrder::find($productionId);
        }

        $employers = collect();
        if (auth()->user()->can('view-employers')) {
             $employers = Employer::orderBy('employerNameTh')->get(['id', 'employerNameTh', 'employerNameEn']);
        } else {
             $user = auth()->user();
             if ($user->employer) {
                 $employers = collect([$user->employer]);
             }
        }

        return view('employees.import', compact('employers', 'production'));
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
        // We'll use columns A-P (increased from L), merging as needed.

        // Default Font
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Angsana New')->setSize(16);

        // Row 1: Employer Name & ID Card
        $sheet->setCellValue('A1', 'ชื่อนายจ้าง');
        $sheet->mergeCells('B1:F1'); // Name Input
        $sheet->setCellValue('G1', 'เลขประจำตัวประชาชน');
        $sheet->setCellValue('G2', '/ทะเบียนนิติบุคคล');
        $sheet->mergeCells('H1:P1'); // ID Input extended
        $sheet->mergeCells('H2:P2'); // ID Input continuation

        // Row 3: Business Type & Request No
        $sheet->setCellValue('A3', 'ประเภทธุรกิจ');
        $sheet->mergeCells('B3:F3'); // Business Input
        $sheet->setCellValue('G3', 'เลขคำขอ');
        $sheet->mergeCells('H3:P3'); // Request Input extended

        // Row 4: Address
        $sheet->setCellValue('A4', 'ที่อยู่');
        $sheet->mergeCells('B4:P4');

        // Row 5: Moo, Soi, Road
        $sheet->setCellValue('A5', 'หมู่ที่/อาคาร');
        $sheet->mergeCells('B5:D5');
        $sheet->setCellValue('E5', 'ซอย');
        $sheet->mergeCells('F5:H5');
        $sheet->setCellValue('I5', 'ถนน');
        $sheet->mergeCells('J5:P5');

        // Row 6: Subdistrict, District
        $sheet->setCellValue('A6', 'ตำบล/แขวง');
        $sheet->mergeCells('B6:E6');
        $sheet->setCellValue('F6', 'อำเภอ/เขต');
        $sheet->mergeCells('G6:P6');

        // Row 7: Province, Zip
        $sheet->setCellValue('A7', 'จังหวัด');
        $sheet->mergeCells('B7:E7');
        $sheet->setCellValue('F7', 'รหัสไปรษณีย์');
        $sheet->mergeCells('G7:P7');

        // Row 8: Tel, Email
        $sheet->setCellValue('A8', 'โทรศัพท์');
        $sheet->mergeCells('B8:E8');
        $sheet->setCellValue('F8', 'e-Mail');
        $sheet->mergeCells('G8:P8');

        // Row 9-10: Requirement Block
        $sheet->mergeCells('I9:P9');
        $sheet->setCellValue('I9', 'มีความต้องการจ้างแรงงานต่างด้าว');
        $sheet->getStyle('I9')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF0F0F0');
        $sheet->getStyle('I9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('I10:P10');
        $sheet->setCellValue('I10', 'สัญชาติ _________ จำนวน ____ คน');
        $sheet->getStyle('I10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF0F0F0');
        $sheet->getStyle('I10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('I11', 'ตามรายชื่อดังต่อไปนี้');


        // Add Underlines (Bottom Border) to input cells for form look
        $inputCells = [
            'B1:F1', 'H1:P2',
            'B3:F3', 'H3:P3',
            'B4:P4',
            'B5:D5', 'F5:H5', 'J5:P5',
            'B6:E6', 'G6:P6',
            'B7:E7', 'G7:P7',
            'B8:E8', 'G8:P8'
        ];
        foreach ($inputCells as $range) {
             $sheet->getStyle($range)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        }

        // --- DATA TABLE HEADERS (Row 12) ---
        $headerRow = 12;
        // UPDATED COLUMNS
        $columns = [
            'Photo (Insert Image)',           // A
            'Title (EN)',                     // B
            'Name (EN)',                      // C
            'Title (TH)',                     // D
            'Name (TH)',                      // E
            'Date of Birth (YYYY-MM-DD)',     // F
            'Nationality',                    // G
            'Passport Number',                // H
            'Passport Expiry Date',           // I (New)
            'Work Permit Number',             // J
            'Work Permit Expiry Date',        // K (New)
            'Work Permit Type',               // L
            'Pink Card Number',               // M
            'CI/PJ/TD/Inter',                 // N
            'Visa Expiry Date',               // O (New)
            '90-Day Report Date',             // P (New)
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
        $sheet->getColumnDimension('A')->setWidth(30);

        // Auto-size other columns (B to P)
        foreach (range('B', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // --- DATA VALIDATION CONFIGURATION ---
        $validations = [
            'B' => '"Mr.,Miss.,Mrs."', // Title EN
            'D' => '"นาย,นางสาว,นาง"', // Title TH
            'G' => '"ลาว,เมียนมา,กัมพูชา,เวียดนาม"', // Nationality
            'L' => '"MOU,มติต่ออายุในประเทศ,มติขึ้นทะเบียนใหม่,อื่นๆ ระบุ"', // WP Type
            'N' => '"CI,PJ,TD,เล่มอินเตอร์"', // Passport Type
        ];

        // Apply row formatting and validation
        for ($r = $startDataRow; $r <= $endDataRow; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(150);

            // Apply borders to all cells in the grid (A to P)
            $rowRange = "A{$r}:P{$r}";
            $sheet->getStyle($rowRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle($rowRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            // Apply Data Validations
            foreach ($validations as $colLetter => $formula) {
                $validation = $sheet->getCell($colLetter . $r)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($formula);
            }
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
            'production_id' => 'nullable|exists:production_orders,id',
            'target_status' => 'nullable|string', // Added target_status
        ]);

        $employerId = $request->input('employer_id');
        $productionId = $request->input('production_id');
        $targetStatus = $request->input('target_status'); // Get target status
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

                    Log::info("Found image at coords: $coords (Row: $row, Col: $column)");

                    // Broadened Search Logic:
                    // 1. Allow columns A or B (A=1, B=2) in case the user pasted carelessly.
                    // 2. Allow rows starting from 2.
                    // Store as list to avoid overwriting if multiple images map to the same row index
                    if (in_array($column, ['A', 'B']) && $row >= 2) {
                        $images[] = [
                            'drawing' => $drawing,
                            'row' => $row,
                            'col' => $column
                        ];
                    }
                } else {
                    Log::warning("Could not parse image coordinates: $coords");
                }
            }

            if (!empty($images)) {
                Log::info("Found " . count($images) . " images in import file.");
            }

            // Iterate rows starting from START_ROW
            $highestRow = $sheet->getHighestRow();
            $usedImageIndices = []; // Keep track of used images to prevent duplication

            for ($rowIdx = $START_ROW; $rowIdx <= $highestRow; $rowIdx++) {
                // Get cell values
                // Updated Mapping for New Template:
                // A (1) = Photo
                // B (2) = Title (EN)
                // C (3) = Name (EN)
                // D (4) = Title (TH)
                // E (5) = Name (TH)
                // F (6) = DOB
                // G (7) = Nationality
                // H (8) = Passport
                // I (9) = Passport Expiry (New)
                // J (10) = WP No
                // K (11) = WP Expiry (New)
                // L (12) = WP Type (Old J)
                // M (13) = Pink Card (Old K)
                // N (14) = Book Type (Old L)
                // O (15) = Visa Expiry (New)
                // P (16) = 90 Day Expiry (New)

                $row = [];
                // We read columns 2 (B) through 16 (P) for text data
                for ($colIdx = 2; $colIdx <= 16; $colIdx++) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                    $val = $sheet->getCell($colLetter . $rowIdx)->getValue();
                    $row[] = trim((string)$val);
                }

                // Check if empty row (Checking Title EN, Name EN, Title TH)
                if (empty($row[0]) && empty($row[1]) && empty($row[3])) {
                    continue;
                }

                // Parse Data
                $titleEn = $row[0];
                $nameEn = $row[1];
                $titleTh = $row[2];
                $nameTh = $row[3];
                $dobRaw = $row[4];
                $nationality = $row[5];
                $passport = $row[6];
                $passportExpiryRaw = $row[7];
                $wp = $row[8];
                $wpExpiryRaw = $row[9];
                $wpType = $row[10];
                $pinkCard = $row[11];
                $bookType = $row[12];
                $visaExpiryRaw = $row[13];
                $ninetyDayRaw = $row[14];

                // Validate and Parse Dates
                $dob = $this->parseDateStrict($dobRaw, $sheet->getCell('F' . $rowIdx));
                if ($dobRaw && !$dob) {
                    $errors[] = "Row $rowIdx: Invalid Date of Birth format ($dobRaw). Expected D/M/Y.";
                    continue;
                }

                $passportExpiry = $this->parseDateStrict($passportExpiryRaw, $sheet->getCell('I' . $rowIdx));
                if ($passportExpiryRaw && !$passportExpiry) {
                    $errors[] = "Row $rowIdx: Invalid Passport Expiry format ($passportExpiryRaw). Expected D/M/Y.";
                    continue;
                }

                $wpExpiry = $this->parseDateStrict($wpExpiryRaw, $sheet->getCell('K' . $rowIdx));
                if ($wpExpiryRaw && !$wpExpiry) {
                    $errors[] = "Row $rowIdx: Invalid Work Permit Expiry format ($wpExpiryRaw). Expected D/M/Y.";
                    continue;
                }

                $visaExpiry = $this->parseDateStrict($visaExpiryRaw, $sheet->getCell('O' . $rowIdx));
                if ($visaExpiryRaw && !$visaExpiry) {
                    $errors[] = "Row $rowIdx: Invalid Visa Expiry format ($visaExpiryRaw). Expected D/M/Y.";
                    continue;
                }

                $ninetyDay = $this->parseDateStrict($ninetyDayRaw, $sheet->getCell('P' . $rowIdx));
                if ($ninetyDayRaw && !$ninetyDay) {
                    $errors[] = "Row $rowIdx: Invalid 90-Day Report format ($ninetyDayRaw). Expected D/M/Y.";
                    continue;
                }

                // Process Image from Column A
                $photoPath = null;
                $drawing = null;

                // Attempt 1: Exact Match (Image anchored in this row)
                foreach ($images as $key => $imgData) {
                    if (in_array($key, $usedImageIndices)) continue;

                    if ($imgData['row'] == $rowIdx) {
                        $drawing = $imgData['drawing'];
                        $usedImageIndices[] = $key;
                        break;
                    }
                }

                // Attempt 2: Fuzzy Match (Image anchored in previous row - Floating Up)
                if (!$drawing) {
                    foreach ($images as $key => $imgData) {
                        if (in_array($key, $usedImageIndices)) continue;

                        if ($imgData['row'] == ($rowIdx - 1)) {
                            $drawing = $imgData['drawing'];
                            $usedImageIndices[] = $key;
                            break;
                        }
                    }
                }

                // Attempt 3: Fuzzy Match (Image anchored 2 rows up - Large Floating Up)
                // Only if row height is very small or image is huge, but safe to include.
                if (!$drawing) {
                     foreach ($images as $key => $imgData) {
                        if (in_array($key, $usedImageIndices)) continue;

                        if ($imgData['row'] == ($rowIdx - 2)) {
                            $drawing = $imgData['drawing'];
                            $usedImageIndices[] = $key;
                            break;
                        }
                    }
                }

                if ($drawing) {
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
                            } else {
                                // Fallback: Extract from Zip archive (XLSX is a zip)
                                try {
                                    $zip = new \ZipArchive();
                                    if ($zip->open($path) === true) {
                                        $drawingName = basename($imagePath);
                                        // Images in Excel are usually in xl/media/
                                        // We try to find the file in the zip that ends with the drawing name
                                        for ($i = 0; $i < $zip->numFiles; $i++) {
                                            $filename = $zip->getNameIndex($i);
                                            if (basename($filename) === $drawingName) {
                                                $imageContent = $zip->getFromIndex($i);
                                                $info = pathinfo($filename);
                                                $extension = $info['extension'] ?? 'jpg';
                                                break;
                                            }
                                        }
                                        $zip->close();
                                    }
                                } catch (\Exception $e) {
                                    Log::warning("Zip extraction failed for row $rowIdx: " . $e->getMessage());
                                }
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

                $status = 'active';
                if ($targetStatus) {
                    $status = $targetStatus;
                } elseif ($productionId) {
                    $status = 'pending_confirmation';
                }

                $employeeData = [
                    'employer_id' => $employerId,
                    'employeeTitleTh' => $titleTh,
                    'employeeNameTh' => $nameTh,
                    'employeeTitleEn' => $titleEn,
                    'employeeNameEn' => $nameEn,
                    'employeeDob' => $dob,
                    'employeeNationality' => $nationality,
                    'employeePassport' => $passport,
                    'passportExpiryDate' => $passportExpiry,
                    'employeeWorkPermit' => $wp,
                    'workPermitExpiryDate' => $wpExpiry,
                    'workPermitMOUGroup' => $wpType,
                    'pinkCardNo' => $pinkCard,
                    'visaExpiryDate' => $visaExpiry,
                    'ninetyDayReportDate' => $ninetyDay,
                    'employeePhoto' => $photoPath,
                    'status' => $status,
                ];

                if ($nationality === 'กัมพูชา') {
                    $employeeData['passport_type_cambodia'] = $bookType;
                } else {
                     $employeeData['passportType'] = $bookType;
                }

                $employee = Employee::create($employeeData);

                // If importing for Production, link immediately
                if ($productionId) {
                    ProductionItem::create([
                        'production_order_id' => $productionId,
                        'employee_id' => $employee->id,
                    ]);
                }

                $importedEmployees[] = $employee;
                $count++;
            }

            DB::commit();

            $importedEmployeeIds = collect($importedEmployees)->pluck('id')->toArray();

            $msg = "Successfully imported $count employees.";
            if (count($errors)) {
                $msg .= " With " . count($errors) . " errors.";
            }

            return back()->with('success', $msg)
                         ->with('import_errors', $errors)
                         ->with('imported_employees', $importedEmployees)
                         ->with('imported_employee_ids', $importedEmployeeIds)
                         ->with('production_id', $productionId);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Strict Date Parser Helper
     */
    private function parseDateStrict($value, $cell = null)
    {
        if (empty($value)) return null;

        // 1. Handle Excel Date Object (if cell is provided)
        if ($cell && Date::isDateTime($cell)) {
            try {
                return Carbon::instance(Date::excelToDateTimeObject($value))->format('Y-m-d');
            } catch (\Exception $e) {
                // Fallthrough to string parsing
            }
        }

        $value = trim((string)$value);

        // 2. Strict D/M/Y Regex
        // Supports 1/1/1999, 01/01/1999, 1-1-1999, 1.1.1999
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $value, $matches)) {
            $day = (int)$matches[1];
            $month = (int)$matches[2];
            $year = (int)$matches[3];

            if (checkdate($month, $day, $year)) {
                return Carbon::create($year, $month, $day)->format('Y-m-d');
            }
        }

        // 3. Reject other formats (Y-m-d, m/d/Y, etc.) as per user instruction
        return null;
    }

    /**
     * Fetch a batch of employees by ID for AJAX updates.
     */
    public function fetchBatch(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:employees,id',
        ]);

        $employees = Employee::whereIn('id', $request->input('ids'))->get();

        $rows = [];
        foreach ($employees as $employee) {
            $rows[$employee->id] = view('employees.partials._import_table_row', compact('employee'))->render();
        }

        return response()->json(['rows' => $rows]);
    }

    /**
     * Cancel the import by permanently deleting the imported employees.
     */
    public function cancelImport(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:employees,id',
        ]);

        $ids = $request->input('ids');
        $count = count($ids);

        DB::beginTransaction();
        try {
            // Force delete the employees
            Employee::whereIn('id', $ids)->forceDelete();

            DB::commit();

            // Clear session data related to import
            session()->forget(['imported_employees', 'imported_employee_ids', 'import_errors']);

            return response()->json([
                'success' => true,
                'message' => "Successfully cancelled import and deleted $count employees."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import cancellation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel import.'
            ], 500);
        }
    }
}
