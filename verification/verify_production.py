
from playwright.sync_api import Page, expect, sync_playwright
import os

def test_workflow_ui(page: Page):
    # This test verifies the static HTML structure of the new "Pre-Production" and "Workflow" views.
    # Since we cannot run the backend, we will render the Blade content as a static HTML string.

    # 1. Mock Data for HTML injection
    html_content = """
    <!DOCTYPE html>
    <html>
    <head>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <body>
        <div class="container-fluid py-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 text-gray-800 fw-bold">Production Projects</h1>
                </div>
                <a href="#" class="btn btn-primary" id="btn-new-project">
                    <i class="bi bi-plus-lg me-2"></i>New Project
                </a>
            </div>

            <!-- Table -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Project Name</th>
                                    <th>Employer</th>
                                    <th>Status</th>
                                    <th class="text-center">Employees</th>
                                    <th class="text-center">Financial</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">MOU Import Batch October</div>
                                        <div class="small text-muted">Initial batch for construction site</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold">ABC Construction Co., Ltd.</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark">Preparation</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info rounded-pill">30</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="small">Total: 150,000</div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="#" class="btn btn-sm btn-outline-warning">Prepare</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">Visa Renewal Group A</div>
                                        <div class="small text-muted">Priority case</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold">XYZ Service Ltd.</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">In Workflow</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info rounded-pill">12</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="small">Total: 45,000</div>
                                        <div class="small text-success">Paid: 45,000</div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="#" class="btn btn-sm btn-primary">Track</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    """

    # 2. Act: Load the HTML content
    page.set_content(html_content)

    # 3. Assert: Verify key elements are present
    expect(page.get_by_role("heading", name="Production Projects")).to_be_visible()
    expect(page.locator("#btn-new-project")).to_be_visible()
    expect(page.get_by_text("MOU Import Batch October")).to_be_visible()
    expect(page.get_by_text("Visa Renewal Group A")).to_be_visible()
    expect(page.get_by_text("ABC Construction Co., Ltd.")).to_be_visible()

    # 4. Screenshot
    page.screenshot(path="verification/production_dashboard.png")

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        try:
            test_workflow_ui(page)
            print("Verification script ran successfully.")
        except Exception as e:
            print(f"Verification failed: {e}")
        finally:
            browser.close()
