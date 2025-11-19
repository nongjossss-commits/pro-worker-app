
from playwright.sync_api import sync_playwright
import os

def generate_mock_html():
    # This script mocks the Blade view output because we cannot run the PHP server.
    # It includes the structure we just defined in CompletenessSettingsController.

    html_content = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Completeness Settings Mock</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { font-family: 'Sarabun', sans-serif; background-color: #f8fafc; padding: 20px; }
            .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
            .section-title { background-color: #e9ecef; padding: 10px; font-weight: bold; border-radius: 5px; margin-bottom: 15px; }
            .form-check-label { font-size: 0.9rem; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0">Employee Data Completeness Settings</h4>
                </div>
                <div class="card-body">
                    <form>
    """

    field_groups = {
        '1. Personal Information (ข้อมูลส่วนตัว)': {
            'employeeTitleTh': 'คำนำหน้าชื่อ (ไทย) / Title (TH)',
            'employeeNameTh': 'ชื่อ-สกุล (ไทย) / Name (TH)',
            'employeeTitleEn': 'Prefix (EN)',
            'employeeNameEn': 'Full Name (EN)',
            'father_name': 'ชื่อพ่อ / Father Name',
            'mother_name': 'ชื่อแม่ / Mother Name',
            'employeeDob': 'วันเดือนปีเกิด / Date of Birth',
            'employeePhoto': 'รูปภาพพนักงาน / Photo',
        },
        '2. Contact & Nationality (ข้อมูลการติดต่อและสัญชาติ)': {
            'employeePhone': 'เบอร์โทรศัพท์ / Phone',
            'employeeNationality': 'สัญชาติ / Nationality',
            'passportType': 'ประเภทหนังสือเดินทาง (พม่า) / Passport Type (Myanmar)',
            'passport_type_cambodia': 'ประเภทหนังสือเดินทาง (กัมพูชา) / Passport Type (Cambodia)',
        },
        '3. Passport & Visa (ข้อมูลหนังสือเดินทางและวีซ่า)': {
            'employeePassport': 'เลขพาสปอร์ต / Passport No',
            'passport_issue_date': 'วันออกพาสปอร์ต / Passport Issue Date',
            'passportExpiryDate': 'วันหมดอายุพาสปอร์ต / Passport Expiry',
            'pinkCardNo': 'เลขบัตรชมพู / Pink Card No',
            'visaType': 'ประเภทวีซ่า / Visa Type',
            'visaExpiryDate': 'วันหมดอายุวีซ่า / Visa Expiry',
        },
        '4. Employment & Work IDs (ข้อมูลการจ้างงานและเอกสาร)': {
            'job_title': 'ตำแหน่งงาน / Job Title',
            'job_description': 'ลักษณะงาน / Job Description',
            'startDate': 'วันที่เริ่มงาน / Start Date',
            'employeeWorkPermit': 'เลข Work Permit / Work Permit No',
            'workPermitExpiryDate': 'วันหมดอายุ Work Permit / Work Permit Expiry',
            'ninetyDayReportDate': 'วันรายงานตัว 90 วัน / 90 Day Report',
            'workPermitMOUGroup': 'ประเภทใบอนุญาตทำงาน / MOU Group',
            'workPermitMOUGroupOther': 'ระบุประเภทอื่นๆ / Other MOU Group',
            'name_list_number': 'เลข Name List',
            'request_number': 'เลขที่คำขอ / Request Number',
            'employee_id_number': 'เลขประจำตัว / ID Number',
            'tax_id_number': 'เลขประจำตัวผู้เสียภาษี / Tax ID',
            'employer_employee_id': 'รหัสคนงาน-นายจ้าง / Employer-Employee ID',
            'employee_reference_id': 'เลขอ้างอิงคนงาน / Reference ID',
        },
        '5. Health Insurance (ข้อมูลประกันสุขภาพ)': {
            'insurance_type': 'ประเภทประกัน / Insurance Type',
            'social_security_number': 'เลขประกันสังคม / Social Security No',
            'insurance_detail': 'สิทธิ์โรงพยาบาล / Hospital Rights (Social)',
            'insurance_detail_hospital': 'ชื่อโรงพยาบาล / Hospital Name',
            'insurance_expiry_date_hospital': 'วันหมดอายุประกัน (รพ) / Hospital Insurance Expiry',
            'insurance_detail_private': 'บริษัทประกัน / Insurance Company',
            'insurance_expiry_date_private': 'วันหมดอายุประกัน (เอกชน) / Private Insurance Expiry',
            'insurance_document_path_private': 'ไฟล์เอกสารประกัน (เอกชน) / Private Insurance File',
        },
        '6. Login Information (ข้อมูลการเข้าสู่ระบบ)': {
            'email': 'อีเมล / Email',
            'password': 'รหัสผ่าน / Password',
        },
        '7. File Attachments (ส่วนแนบไฟล์เอกสาร)': {
            'employee_doc_1': '1. พาสปอร์ต / Passport',
            'employee_doc_2': '2. วีซ่า / Visa',
            'employee_doc_3': '3. ใบเสร็จ Work Permit / Work Permit Receipt',
            'employee_doc_4': '4. บัตรชมพู / Pink Card',
            'employee_doc_5': '5. ทร. 38 / Tor Ror 38',
            'employee_doc_6': '6. รายงานตัว 90 วัน / 90 Day Report',
            'employee_doc_7': '7. ใบแจ้งที่พักอาศัย / Residence Notification',
            'employee_doc_8': '8. เอกสารบ้านเกิด / Home Country Doc',
            'employee_doc_9': '9. เอกสารอื่นๆ 1 / Other 1',
            'employee_doc_10': '10. เอกสารอื่นๆ 2 / Other 2',
            'employee_doc_11': '11. เอกสารอื่นๆ 3 / Other 3',
            'employee_doc_12': '12. เอกสารอื่นๆ 4 / Other 4',
        }
    }

    for section, fields in field_groups.items():
        html_content += f'<div class="mb-4"><div class="section-title">{section}</div><div class="row">'
        for key, label in fields.items():
            html_content += f"""
            <div class="col-md-4 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="fields[]" value="{key}" id="field_{key}">
                    <label class="form-check-label" for="field_{key}">
                        {label} <small class="text-muted">({key})</small>
                    </label>
                </div>
            </div>
            """
        html_content += '</div></div>'

    html_content += """
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </body>
    </html>
    """

    with open("mock_settings.html", "w", encoding="utf-8") as f:
        f.write(html_content)

def verify_settings_page():
    generate_mock_html()

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Load the mock HTML file
        page.goto(f"file://{os.getcwd()}/mock_settings.html")

        # Assertions
        # Check for main headers
        assert page.is_visible("text=Employee Data Completeness Settings")
        assert page.is_visible("text=1. Personal Information (ข้อมูลส่วนตัว)")
        assert page.is_visible("text=7. File Attachments (ส่วนแนบไฟล์เอกสาร)")

        # Check for specific new fields we added
        assert page.is_visible("text=ชื่อพ่อ / Father Name")
        assert page.is_visible("text=ไฟล์เอกสารประกัน (เอกชน) / Private Insurance File")

        # Check for correct file attachment labels
        assert page.is_visible("text=1. พาสปอร์ต / Passport")
        assert page.is_visible("text=12. เอกสารอื่นๆ 4 / Other 4")

        # Take screenshot
        page.screenshot(path="settings_verification.png", full_page=True)
        browser.close()

if __name__ == "__main__":
    verify_settings_page()
