<?php

namespace App\Http\Controllers\Sales;

use App\Models\SalesLead;
use App\Models\SalesLeadEmployee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SalesLeadImportController extends Controller
{
    public function importExcel(Request $request, SalesLead $sales)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Expected Columns:
            // 0: NO.
            // 1: Name En
            // 2: Name Th
            // 3: Gender
            // 4: Nationality
            // 5: Passport
            // 6: Work Permit

            $importedCount = 0;
            // Skip header (row 0)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                // Name EN is mandatory as per business rule for temporary lead
                $nameEn = $row[1] ?? null;
                if (empty($nameEn)) {
                    continue; // Skip rows without name
                }

                SalesLeadEmployee::create([
                    'sales_lead_id' => $sales->id,
                    'employeeNameEn' => $nameEn,
                    'employeeNameTh' => $row[2] ?? null,
                    'employeeGender' => $row[3] ?? null,
                    'employeeNationality' => $row[4] ?? null,
                    'employeePassport' => $row[5] ?? null,
                    'employeeWorkPermit' => $row[6] ?? null,
                ]);

                $importedCount++;
            }

            return redirect()->back()->with('success', "นำเข้าข้อมูลลูกจ้างสำเร็จ {$importedCount} รายการ");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาดในการอ่านไฟล์ Excel: ' . $e->getMessage());
        }
    }
}
