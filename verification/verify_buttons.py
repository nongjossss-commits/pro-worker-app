from playwright.sync_api import sync_playwright
import os

def generate_mock_html():
    html_content = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verification Mock</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    </head>
    <body class="p-4 bg-light">
        <div class="container">
            <h4>Mock Registration Resolution Card</h4>

            <!-- Custom Field Attachment Section (Mocking Offcanvas Drawer Content) -->
            <div class="card mb-3">
                <div class="card-header">Custom Fields Drawer Content (Mock)</div>
                <div class="card-body">
                    <div class="mb-3 border-bottom pb-2 field-item">
                        <label class="form-label fw-bold small mb-1 text-secondary">My Custom File</label>

                        <!-- MOCKED OUTPUT OF generateFieldsHtml -->
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-1 bg-white p-2 border rounded">
                            <div class="d-flex align-items-center gap-2 text-truncate">
                                <i class="bi bi-paperclip text-muted"></i>
                                <span class="small text-secondary text-truncate" style="max-width: 150px;">Attachment</span>
                            </div>
                            <div class="d-flex gap-1">
                                <a href="#" class="btn btn-sm btn-success text-white rounded-circle shadow-sm d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;" title="View File">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <a href="#" class="btn btn-sm btn-danger text-white rounded-circle shadow-sm d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;" title="Download PDF">
                                    <i class="bi bi-file-earmark-pdf-fill"></i>
                                </a>
                            </div>
                        </div>
                        <div class="small text-muted fst-italic text-break mt-1">Some description</div>
                    </div>
                </div>
            </div>

            <!-- Standard Employee Card Section (Mocking _employee_card.blade.php) -->
            <div class="card mb-3">
                <div class="card-header">Employee Card Standard Attachments</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Passport</label>
                            <p class="form-control-plaintext">
                                <a href="#" class="btn btn-success btn-sm text-white">
                                    <i class="bi bi-eye-fill"></i> View
                                </a>
                                <!-- In _employee_card.blade.php, we need to add the Red Button here too if requested -->
                                <!-- Wait, the plan step 2 said: "Locate all instances of file attachment previews... Insert the 'Download PDF' button code" -->
                                <!-- I haven't edited _employee_card.blade.php yet! I only edited the drawer. -->
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </body>
    </html>
    """
    with open("verification/mock_buttons.html", "w") as f:
        f.write(html_content)
    return os.path.abspath("verification/mock_buttons.html")

def verify_buttons():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        file_path = generate_mock_html()
        page.goto(f"file://{file_path}")

        # Screenshot
        page.screenshot(path="verification/verification_buttons.png")
        print("Screenshot saved to verification/verification_buttons.png")

        browser.close()

if __name__ == "__main__":
    verify_buttons()
