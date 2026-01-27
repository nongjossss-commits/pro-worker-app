from playwright.sync_api import sync_playwright

def verify_workflow():
    html_content = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Workflow Verification</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <style>
            .stat-badge { width: 32px; height: 32px; font-size: 0.85rem; }
            .grayscale-mode { filter: grayscale(100%); opacity: 0.8; }
        </style>
    </head>
    <body class="bg-light p-4">
        <div class="container-fluid">
            <!-- Scoreboard -->
            <h4 class="mb-3">Scoreboard</h4>
            <div class="row row-cols-1 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
                <div class="col">
                    <div class="card text-white h-100 shadow-sm border-0" style="background-color: #FBBF24;">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h1 class="display-4 fw-bold mb-0">120</h1>
                            <p class="fs-5 fw-light mb-0">Total Employees</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card text-white h-100 shadow-sm border-0" style="background-color: #EF4444;">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h1 class="display-4 fw-bold mb-0">10</h1>
                            <p class="fs-5 fw-light mb-0">Not Started</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card text-white h-100 shadow-sm border-0" style="background-color: #6B7280;">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h1 class="display-4 fw-bold mb-0">5</h1>
                            <p class="fs-5 fw-light mb-0">Cancelled</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card text-white h-100 shadow-sm border-0" style="background-color: #10B981;">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h1 class="display-4 fw-bold mb-0">50</h1>
                            <p class="fs-5 fw-light mb-0">Completed</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card text-white h-100 shadow-sm border-0 bg-primary bg-gradient">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h1 class="display-4 fw-bold mb-0">12</h1>
                            <p class="fs-5 fw-light mb-0">Active Projects</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Global Workflow Progress -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title fw-bold text-secondary mb-0">
                            <i class="bi bi-bar-chart-fill me-2"></i>Workflow Progress (Global) - Notify In
                        </h5>
                        <button class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-gear-fill me-1"></i> Settings
                        </button>
                    </div>
                    <div class="d-flex gap-2 flex-wrap justify-content-start align-items-center">
                        <div class="d-inline-flex align-items-center bg-white border rounded-pill py-2 px-3 shadow-sm gap-2">
                            <span class="badge rounded-circle bg-success shadow-sm d-flex align-items-center justify-content-center stat-badge">10</span>
                            <span class="fw-bold text-dark fs-6">Step 1: Document</span>
                        </div>
                        <div class="d-inline-flex align-items-center bg-white border rounded-pill py-2 px-3 shadow-sm gap-2">
                            <span class="badge rounded-circle bg-success shadow-sm d-flex align-items-center justify-content-center stat-badge">20</span>
                            <span class="fw-bold text-dark fs-6">Step 2: Submission</span>
                        </div>
                        <div class="d-inline-flex align-items-center bg-white border rounded-pill py-2 px-3 shadow-sm gap-2">
                            <span class="badge rounded-circle bg-secondary bg-opacity-50 text-white shadow-sm d-flex align-items-center justify-content-center stat-badge">0</span>
                            <span class="fw-bold text-dark fs-6">Step 3: Approval</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Item Card Mockup -->
            <h4 class="mb-3">Employee Card (Mock)</h4>
            <div class="card w-100 border shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                        <div class="d-flex align-items-center gap-3 w-100">
                             <img src="https://via.placeholder.com/50" class="rounded-circle shadow-sm" width="50">
                             <div>
                                <div class="fw-bold text-dark">John Doe</div>
                                <div class="text-muted small">นาย สมชาย</div>
                             </div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap justify-content-end">
                            <button class="btn btn-sm btn-outline-info rounded-pill px-3"><i class="bi bi-eye-fill"></i></button>
                            <!-- Manage Team Button -->
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="bi bi-people-fill"></i> Team
                            </button>
                            <button class="btn btn-sm btn-success rounded-pill px-3"><i class="bi bi-check-lg"></i> Finish</button>
                            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-x-circle"></i> Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Mockup (Visual only) -->
            <div class="modal fade show" style="display: block; position: relative; z-index: 1050; margin-top: 20px;" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content shadow">
                        <div class="modal-header">
                            <h5 class="modal-title">Manage Team (Mock Modal)</h5>
                            <button type="button" class="btn-close"></button>
                        </div>
                        <div class="modal-body">
                            <h6 class="fw-bold mt-2">Team Group A</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" checked>
                                <label class="form-check-label">Team Alpha</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox">
                                <label class="form-check-label">Team Beta</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary">Close</button>
                            <button type="button" class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </body>
    </html>
    """

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        page.set_content(html_content)
        page.screenshot(path="verification/verification.png", full_page=True)
        browser.close()

if __name__ == "__main__":
    verify_workflow()
