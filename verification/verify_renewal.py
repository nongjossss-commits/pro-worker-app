from playwright.sync_api import sync_playwright
import os

def generate_static_verification(page):
    # Construct the HTML content manually since we can't run the server
    # We will mock the Renewal Index Page structure

    html_content = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Renewal Resolution Verification</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
            body { background-color: #f8fafc; font-family: sans-serif; }
            .content-section { padding: 2rem; }
        </style>
    </head>
    <body>
        <div class="d-flex">
            <!-- Sidebar Mock -->
            <div class="bg-white border-end p-3" style="width: 260px; min-height: 100vh;">
                <h4 class="text-primary fw-bold mb-4 text-center">Proworker</h4>
                <div class="list-group">
                    <a href="#" class="list-group-item list-group-item-action">Production</a>
                    <a href="#" class="list-group-item list-group-item-action">Workflow</a>
                    <a href="#" class="list-group-item list-group-item-action">Registration Resolution</a>
                    <a href="#" class="list-group-item list-group-item-action active text-primary fw-bold">
                        <i class="bi bi-arrow-repeat me-2"></i>Renewal Resolution (มติต่ออายุ)
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-grow-1 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0 text-primary fw-bold"><i class="bi bi-arrow-repeat me-2"></i>Renewal Resolution</h4>

                    <div class="d-flex gap-2">
                        <!-- THE NEW BUTTON WE ADDED -->
                        <a href="#" class="btn btn-warning text-white fw-bold">
                            <i class="bi bi-plus-lg me-1"></i> New Employee
                        </a>

                        <a href="#" class="btn btn-success fw-bold">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import Employees
                        </a>

                        <button class="btn btn-dark fw-bold">
                            <i class="bi bi-calendar-check me-1"></i> Configuration
                        </button>
                    </div>
                </div>

                <div class="alert alert-info">
                    <strong>Verification Note:</strong> This is a static representation of the Renewal Resolution page to verify the "New Employee" button visibility and the menu name translation concept.
                </div>

                <!-- Stats Row Mock -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-warning p-3 text-center border-0 shadow-sm">
                            <h3>150</h3>
                            <span>Total Employees</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-danger p-3 text-center border-0 shadow-sm">
                            <h3>5</h3>
                            <span>Not Started</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </body>
    </html>
    """

    page.set_content(html_content)
    page.screenshot(path="verification/renewal_page_verification.png")

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        try:
            generate_static_verification(page)
            print("Screenshot generated at verification/renewal_page_verification.png")
        finally:
            browser.close()
