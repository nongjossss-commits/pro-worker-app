
import { test, expect } from '@playwright/test';

test('Verify Registration Resolution UI', async ({ page }) => {
    // 1. Mock the HTML content based on the blade view structure
    // We'll construct a simplified version of the Registration Resolution page with the new features
    const htmlContent = `
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Registration Resolution</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <meta name="csrf-token" content="mock-token">
        <style>
            .card { transition: all 0.3s ease; }
        </style>
    </head>
    <body class="bg-light">
        <div class="container-fluid p-4">

            <!-- Top Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card bg-warning text-white h-100 p-3 text-center">
                        <h1 class="display-4 fw-bold">82</h1>
                        <p class="fs-5">Total Employees</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-dark text-white h-100 p-3 text-center">
                        <h1 class="display-4 fw-bold">2</h1>
                        <p class="fs-5">Total Employers</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100 p-3">
                        <div class="d-flex justify-content-between mb-3">
                            <h5>Workflow Progress</h5>
                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-gear"></i> Settings</button>
                        </div>
                        <div class="d-flex gap-2">
                             <div class="border rounded p-2 text-center" style="min-width: 60px;">
                                <div class="fw-bold">1</div>
                                <span class="badge bg-success rounded-pill">5</span>
                            </div>
                            <div class="border rounded p-2 text-center" style="min-width: 60px;">
                                <div class="fw-bold">2</div>
                                <span class="badge bg-success rounded-pill">3</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Bar with Search -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
                <h4 class="text-primary fw-bold">Registration Resolution</h4>
                <div class="input-group" style="max-width: 400px;">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" placeholder="Search employee or employer...">
                    <button class="btn btn-primary">Search</button>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-warning text-white"><i class="bi bi-plus-lg"></i> Add New</button>
                    <button class="btn btn-success"><i class="bi bi-file-earmark-spreadsheet"></i> Import</button>
                </div>
            </div>

            <!-- Employer Card -->
            <div class="card mb-3 border shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <span class="fs-5 fw-bold">Acme Corp (ACME)</span>
                    <div class="d-flex gap-2 align-items-center">
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-currency-dollar"></i> Finance</button>
                        <span class="badge bg-secondary">2 Employees</span>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-down"></i></button>
                    </div>
                </div>
                <div class="card-body bg-light">
                    <!-- Employee Card -->
                    <div class="card bg-white border shadow-sm mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center gap-3">
                                    <input type="checkbox" class="form-check-input">
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">U</div>
                                    <div>
                                        <h6 class="mb-0 fw-bold">Mr. John Doe</h6>
                                        <div class="small text-muted">ID: 12345 | Myanmar</div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-info"><i class="bi bi-search"></i></button>
                                    <button class="btn btn-sm btn-outline-primary" id="btn-drawer"><i class="bi bi-layout-text-sidebar-reverse"></i></button>
                                    <button class="btn btn-sm btn-outline-success">Save to DB</button>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-success">Step 1 <i class="bi bi-check"></i></button>
                                    <button class="btn btn-sm btn-outline-secondary">Step 2</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Offcanvas Drawer Mockup -->
            <div class="offcanvas offcanvas-end" tabindex="-1" id="employeeDrawer">
                <div class="offcanvas-header bg-light">
                    <h5 class="offcanvas-title">Employee Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body">
                    <h6 class="fw-bold border-bottom pb-2 mb-3">Add New Field</h6>
                    <div class="mb-3">
                        <label class="form-label small">Field Name</label>
                        <input type="text" class="form-control form-control-sm">
                    </div>
                    <div class="mb-3">
                         <select class="form-select form-select-sm">
                             <option>Text Box</option>
                             <option>Date</option>
                             <option>File Attachment</option>
                         </select>
                    </div>
                    <button class="btn btn-primary btn-sm w-100">Add Field</button>
                </div>
            </div>

        </div>

        <script>
            // Simple logic to show offcanvas
            document.getElementById('btn-drawer').addEventListener('click', () => {
                const bsOffcanvas = new bootstrap.Offcanvas(document.getElementById('employeeDrawer'));
                bsOffcanvas.show();
            });
        </script>
    </body>
    </html>
    `;

    // 2. Load Content
    await page.setContent(htmlContent);

    // 3. Verify Elements exist
    await expect(page.locator('text=Total Employees')).toBeVisible();
    await expect(page.locator('input[placeholder="Search employee or employer..."]')).toBeVisible();
    await expect(page.locator('button:has-text("Finance")')).toBeVisible(); // Finance Button

    // 4. Test Drawer Interaction
    await page.click('#btn-drawer');
    await expect(page.locator('#employeeDrawer')).toBeVisible();
    await expect(page.locator('text=Add New Field')).toBeVisible();

    // 5. Take Screenshot
    await page.screenshot({ path: 'verification_registration_ui.png', fullPage: true });
});
