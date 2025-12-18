from playwright.sync_api import sync_playwright
import os

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    # Mock HTML content to simulate the Registration UI
    html_content = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Registration Resolution Mock</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
            /* CSS from index.blade.php */
            .employer-sequence-number {
                min-width: 50px;
                text-align: center;
                font-size: 2.5rem;
                font-weight: bold;
                color: #6c757d;
                opacity: 0.5;
            }
            .employee-sequence-number {
                min-width: 40px; /* VERIFY THIS CHANGE */
                text-align: right;
                font-weight: bold;
                white-space: nowrap; /* VERIFY THIS CHANGE */
            }
        </style>
    </head>
    <body class="bg-light p-4">
        <div class="container">
            <h3>Mock Registration UI</h3>

            <!-- Employee Card Example with Large Number -->
            <div class="d-flex align-items-center mb-3">
                <div class="employee-sequence-number me-2 fs-5 fw-bold text-muted opacity-50 text-end">100</div>
                <div class="card p-3 shadow-sm w-100">
                    <div class="fw-bold">Employee 100</div>
                    <div>Should not wrap</div>
                </div>
            </div>

            <div class="d-flex align-items-center mb-3">
                <div class="employee-sequence-number me-2 fs-5 fw-bold text-muted opacity-50 text-end">999</div>
                <div class="card p-3 shadow-sm w-100">
                    <div class="fw-bold">Employee 999</div>
                    <div>Should not wrap</div>
                </div>
            </div>

        </div>
    </body>
    </html>
    """

    page.set_content(html_content)

    # Take screenshot
    screenshot_path = os.path.abspath("verification/verify_registration_ui.png")
    page.screenshot(path=screenshot_path)
    print(f"Screenshot saved to {screenshot_path}")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
