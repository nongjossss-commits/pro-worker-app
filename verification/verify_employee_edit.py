from playwright.sync_api import sync_playwright
import os

def generate_mock_html():
    html_content = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verification Employee Edit Mock</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    </head>
    <body class="p-4 bg-light">
        <div class="container">
            <h4>Mock Employee Edit Form</h4>

            <div class="card mb-3">
                <div class="card-body">
                    <label class="form-label fw-bold">1. Passport</label>
                    <div class="mb-2 d-flex gap-1">
                        <a href="#" class="btn btn-success btn-sm text-white"><i class="bi bi-eye-fill"></i> ดูไฟล์ปัจจุบัน</a>
                        <!-- THE RED BUTTON WE ADDED -->
                        <a href="#" class="btn btn-danger btn-sm text-white"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <label class="form-label">แนบไฟล์เอกสารประกัน</label>
                    <div class="mb-2 d-flex gap-1">
                        <a href="#" class="btn btn-success btn-sm text-white"><i class="bi bi-eye-fill"></i> ดูไฟล์ปัจจุบัน</a>
                         <!-- THE RED BUTTON WE ADDED -->
                        <a href="#" class="btn btn-danger btn-sm text-white"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                    </div>
                </div>
            </div>

        </div>
    </body>
    </html>
    """
    with open("verification/mock_employee_edit.html", "w") as f:
        f.write(html_content)
    return os.path.abspath("verification/mock_employee_edit.html")

def verify_employee_edit():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        file_path = generate_mock_html()
        page.goto(f"file://{file_path}")

        # Verify the red button exists
        assert page.locator(".bi-file-earmark-pdf-fill").count() >= 2

        # Screenshot
        page.screenshot(path="verification/verification_employee_edit.png")
        print("Screenshot saved to verification/verification_employee_edit.png")

        browser.close()

if __name__ == "__main__":
    verify_employee_edit()
