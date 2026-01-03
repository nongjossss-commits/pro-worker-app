from playwright.sync_api import sync_playwright
import os

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    # Define a static HTML file that mimics the structure of an employee card with the new drag handle
    html_content = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Drag Handle Verification</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
            .cursor-grab { cursor: grab; }
        </style>
    </head>
    <body class="p-5">
        <h3>Employee Card Test</h3>
        <!-- Mimic the employee card structure -->
        <div id="employee-card-1" class="employee-card card mb-3" style="max-width: 600px;">
            <div class="card-body d-flex align-items-center">
                <div class="me-3">
                    <input class="form-check-input" type="checkbox">
                </div>

                <img src="https://placehold.co/48x48" class="rounded-circle me-3" style="width: 48px; height: 48px;">

                <div class="flex-grow-1">
                    <div><strong>John Doe</strong></div>
                    <div class="text-muted">Manager</div>
                </div>

                <div class="employee-actions d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></button>
                    <!-- The Drag Handle -->
                    <span class="btn btn-sm btn-light border cursor-grab ms-1"
                          draggable="true"
                          title="Drag">
                        <i class="bi bi-grid-3x2-gap-fill text-muted"></i>
                    </span>
                </div>
            </div>
        </div>

        <h3>Table Row Test</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Check</th>
                    <th>Drag</th>
                    <th>Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="checkbox"></td>
                    <td>
                        <!-- The Drag Handle in Table -->
                        <i class="bi bi-grid-3x2-gap-fill text-muted cursor-grab" draggable="true" title="Drag"></i>
                    </td>
                    <td>Jane Doe</td>
                    <td><button class="btn btn-sm btn-primary">Edit</button></td>
                </tr>
            </tbody>
        </table>
    </body>
    </html>
    """

    # Write the HTML to a file
    with open("verification/test.html", "w") as f:
        f.write(html_content)

    # Open the file
    page.goto("file://" + os.path.abspath("verification/test.html"))

    # Take a screenshot
    page.screenshot(path="verification/drag_handle_verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
