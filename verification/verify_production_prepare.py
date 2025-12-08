from playwright.sync_api import sync_playwright

def verify_production_prepare():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # HTML Content that mimics the Prepare Page
        html_content = """
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Production Prepare Verification</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
            <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        </head>
        <body class="bg-light">
            <div class="container-fluid py-4" x-data="preparationManager()">

                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <div class="text-uppercase text-muted small fw-bold">Pre-Production Stage</div>
                        <h2 class="fw-bold mb-0">Visa Renewal Batch #101</h2>
                        <div class="text-muted">ABC Company Co., Ltd.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary">Exit</button>
                        <button class="btn btn-success btn-lg shadow-sm" :disabled="!isReadyToStart">
                            <i class="bi bi-send-check me-2"></i>Send to Workflow
                        </button>
                    </div>
                </div>

                <!-- Status Dashboard -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 h-100" :class="{'bg-success text-white': documentReady, 'bg-light': !documentReady}">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="fw-bold mb-1"><i class="bi bi-file-earmark-check me-2"></i>Documents Ready</h5>
                                    <div class="small" :class="{'text-white-50': documentReady, 'text-muted': !documentReady}">
                                        Checked by Staff / Assignee
                                    </div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input fs-4" type="checkbox" role="switch" x-model="documentReady">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 h-100" :class="{'bg-success text-white': financialApproved, 'bg-light': !financialApproved}">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="fw-bold mb-1"><i class="bi bi-cash-coin me-2"></i>Ready to Proceed</h5>
                                    <div class="small" :class="{'text-white-50': financialApproved, 'text-muted': !financialApproved}">
                                        Financial / Admin Approval
                                    </div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input fs-4" type="checkbox" role="switch" x-model="financialApproved">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Left Column: Settings -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white fw-bold">Project Details</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Project Name</label>
                                    <input type="text" class="form-control" value="Visa Renewal Batch #101">
                                </div>
                                <h6 class="fw-bold mt-4 mb-3 text-primary">Financial Data</h6>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="small text-muted">Total Amount</label>
                                        <input type="number" class="form-control form-control-sm" value="50000">
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted">Paid / Deposit</label>
                                        <input type="number" class="form-control form-control-sm" value="10000">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="small text-muted">Note</label>
                                    <textarea class="form-control form-control-sm" rows="2">Waiting for final payment.</textarea>
                                </div>
                                <button class="btn btn-sm btn-primary w-100 mt-2">Save Details</button>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Employee Management -->
                    <div class="col-lg-8">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h5 class="mb-0 fw-bold">Included Employees (2)</h5>
                                <div class="dropdown">
                                    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-person-plus me-1"></i> Add Employee
                                    </button>
                                    <ul class="dropdown-menu show" style="position: static; display: block; border: 1px solid #ddd;">
                                        <li><a class="dropdown-item" href="#"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Import from Excel</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="#">Select Existing (DB)</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4">Name</th>
                                                <th>Passport / ID</th>
                                                <th class="text-end pe-4">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold">นาย สมชาย ใจดี</div>
                                                    <div class="small text-muted">Mr. Somchai Jaidee</div>
                                                </td>
                                                <td>AA1234567</td>
                                                <td class="text-end pe-4">
                                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending Confirmation</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold">นาง สาว สวย</div>
                                                    <div class="small text-muted">Ms. Sao Suay</div>
                                                </td>
                                                <td>BB7654321</td>
                                                <td class="text-end pe-4">
                                                    <span class="badge bg-secondary">Pending Workflow</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <script>
                function preparationManager() {
                    return {
                        documentReady: false,
                        financialApproved: false,

                        get isReadyToStart() {
                            return this.documentReady && this.financialApproved;
                        }
                    }
                }
            </script>
        </body>
        </html>
        """

        page.set_content(html_content)

        # Take initial state screenshot
        page.screenshot(path="verification/production_prepare_initial.png")

        # Wait for Alpine to initialize
        page.wait_for_timeout(1000)

        # Toggle first checkbox (Documents Ready)
        page.locator("input[type=checkbox]").nth(0).click()

        # Toggle second checkbox (Financial Approved)
        page.locator("input[type=checkbox]").nth(1).click()

        # Wait for transition
        page.wait_for_timeout(1000)

        # Screenshot with both active (Green Cards)
        page.screenshot(path="verification/production_prepare_active.png")

if __name__ == "__main__":
    verify_production_prepare()
