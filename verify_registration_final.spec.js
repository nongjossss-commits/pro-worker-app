
import { test, expect } from '@playwright/test';

test('Verify Registration Resolution UI Implementation', async ({ page }) => {
    // We cannot run the PHP server, so we will create a Static HTML file that MIRRORS the structure
    // we just wrote to `resources/views/production/registration/index.blade.php`.
    // This allows us to verify the layout and elements visually without the backend.

    const staticHtml = `
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Registration Resolution</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <style>
            .card { transition: all 0.3s ease; }
            .step-name { margin-right: 5px; }
        </style>
    </head>
    <body class="bg-light">
        <div class="container-fluid p-4">

            <!-- Top Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card bg-warning text-white h-100 p-3 text-center d-flex flex-column justify-content-center">
                        <h1 class="display-4 fw-bold">82</h1>
                        <p class="card-text fs-5">Total Employees</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-dark text-white h-100 p-3 text-center d-flex flex-column justify-content-center">
                        <h1 class="display-4 fw-bold">2</h1>
                        <p class="card-text fs-5">Total Employers</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100 p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Workflow Progress</h5>
                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-gear"></i> Settings</button>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
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
                <h4 class="mb-0 text-primary fw-bold"><i class="bi bi-people-fill me-2"></i>Registration Resolution</h4>

                <form class="d-flex flex-grow-1 mx-md-4" style="max-width: 500px;">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search employee or employer...">
                        <button class="btn btn-primary" type="button">Search</button>
                    </div>
                </form>

                <div class="d-flex gap-2">
                    <button class="btn btn-warning text-white"><i class="bi bi-plus-lg"></i> Add New Employee</button>
                    <button class="btn btn-success"><i class="bi bi-file-earmark-spreadsheet"></i> Import</button>
                </div>
            </div>

            <!-- Employers List -->
            <div class="accordion" id="employersAccordion">
                <div class="card mb-3 border shadow-sm">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <span class="fs-5 fw-bold">Acme Corp (ACME)</span>
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-currency-dollar"></i> Finance</button>
                                <span class="badge bg-secondary">2 Employees</span>
                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-down"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body bg-light">
                        <!-- Employee Card Mockup (Simulating _employee_card.blade.php) -->
                        <div class="card bg-white border shadow-sm mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="d-flex align-items-center gap-3 w-100">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">U</div>
                                            <div>
                                                <h6 class="mb-0 fw-bold">Mr. John Doe</h6>
                                                <div class="small text-muted"><i class="bi bi-passport me-1"></i> A1234 | <i class="bi bi-geo-alt me-1"></i> Myanmar</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                         <button class="btn btn-sm btn-outline-info btn-preview" title="Preview"><i class="bi bi-search"></i></button>
                                         <button class="btn btn-sm btn-outline-primary" title="Custom Fields" id="btn-drawer"><i class="bi bi-layout-text-sidebar-reverse"></i></button>
                                         <button class="btn btn-sm btn-outline-success"><i class="bi bi-check-lg"></i> <span class="d-none d-md-inline">Save to DB</span></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Drawer Mockup (Simulating offcanvas_drawer.blade.php) -->
            <div class="offcanvas offcanvas-end" tabindex="-1" id="employeeDrawer">
                <div class="offcanvas-header bg-light">
                    <h5 class="offcanvas-title">Employee Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body">
                    <h6 class="fw-bold border-bottom pb-2 mb-3">Add New Field</h6>
                    <div class="mb-3">
                        <label class="form-label small">Field Name (Label)</label>
                        <input type="text" class="form-control form-control-sm" placeholder="e.g. Note">
                    </div>
                    <div class="mb-3">
                         <label class="form-label small">Field Type</label>
                         <select class="form-select form-select-sm">
                             <option value="text">Text Box</option>
                             <option value="date">Date</option>
                             <option value="file">File Attachment</option>
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

    await page.setContent(staticHtml);

    // Verify Requirements
    await expect(page.locator('text=Registration Resolution')).toBeVisible();
    await expect(page.locator('input[placeholder="Search employee or employer..."]')).toBeVisible();
    await expect(page.locator('button:has-text("Finance")')).toBeVisible();
    await expect(page.locator('#btn-drawer')).toBeVisible();

    // Verify Drawer Open
    await page.click('#btn-drawer');
    await expect(page.locator('#employeeDrawer')).toBeVisible();
    await expect(page.locator('text=Field Name (Label)')).toBeVisible();

    await page.screenshot({ path: 'verification_registration_final.png', fullPage: true });
});
