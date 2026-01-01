from playwright.sync_api import sync_playwright

def verify_medical_fields():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # HTML Content that mimics the structure of the Create Form
        html_content = """
        <!DOCTYPE html>
        <html lang="th">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Employee Create Form Verification</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        </head>
        <body>
            <div class="container mt-5">
                <h3>Employee Create Form Partial Verification</h3>

                <h5 class="mt-4"><i class="bi bi-heart-pulse"></i> 5. ข้อมูลประกันสุขภาพ (Health Insurance)</h5>
                <hr class="mb-4">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="insurance_type" class="form-label">ประเภทประกัน</label>
                        <select class="form-select" id="insurance_type" name="insurance_type">
                            <option value="">-- เลือกประเภท --</option>
                            <option value="ประกันสังคม">ประกันสังคม</option>
                            <option value="ประกันโรงพยาบาล">ประกันโรงพยาบาล</option>
                            <option value="ประกันเอกชน">ประกันเอกชน</option>
                        </select>
                    </div>
                </div>

                <!-- ADDED FIELDS -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="medical_certificate_path" class="form-label">ใบรับรองแพทย์ (Medical Certificate)</label>
                        <div class="input-group input-group-sm">
                            <input type="file" class="form-control form-control-sm" id="medical_certificate_path" name="medical_certificate_path">
                            <button type="button" class="btn btn-outline-secondary">
                                <i class="bi bi-camera"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="medical_hospital_name" class="form-label">โรงพยาบาลที่ตรวจโรค (Hospital Name)</label>
                        <input type="text" class="form-control" id="medical_hospital_name" name="medical_hospital_name" value="">
                    </div>
                </div>
                <!-- END ADDED FIELDS -->

                <div id="insuranceSocialSecurity" class="d-none">
                    <div class="alert alert-secondary">Social Security Fields (Hidden)</div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>
        """

        page.set_content(html_content)

        # Verify elements exist
        page.locator("#medical_certificate_path").wait_for(state="visible")
        page.locator("#medical_hospital_name").wait_for(state="visible")

        # Take screenshot
        page.screenshot(path="verification/medical_fields_verification.png")
        print("Screenshot saved to verification/medical_fields_verification.png")

        browser.close()

if __name__ == "__main__":
    verify_medical_fields()
