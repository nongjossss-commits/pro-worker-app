from playwright.sync_api import sync_playwright

def verify_layout():
    # Construct the HTML content manually, mimicking the Blade template's output.
    # This includes the head (Bootstrap), the 5-column grid stats, and the employer header.

    html_content = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Registration Resolution - Verification</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>
            .employer-stats-container {
                display: flex;
                gap: 8px;
            }
            .employer-stat-badge {
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                color: white;
                font-weight: bold;
                font-size: 0.8rem;
            }
        </style>
    </head>
    <body class="bg-light">
        <div class="container-fluid py-4">
            <!-- Top Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-2 col-sm-6">
                    <div class="card text-white h-100 shadow-sm" style="background-color: #FBBF24; border: none;">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h1 class="display-5 fw-bold mb-0" id="global-total-count">100</h1>
                            <p class="fs-6 fw-light mb-0">Total Employees</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="card text-white h-100 shadow-sm" style="background-color: #EF4444; border: none;">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h1 class="display-5 fw-bold mb-0" id="global-not-started-count">20</h1>
                            <p class="fs-6 fw-light mb-0">Not Started</p>
                        </div>
                    </div>
                </div>
                <!-- New: Cancelled -->
                <div class="col-md-2 col-sm-6">
                    <div class="card text-white h-100 shadow-sm" style="background-color: #6B7280; border: none;">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h1 class="display-5 fw-bold mb-0" id="global-cancelled-count">5</h1>
                            <p class="fs-6 fw-light mb-0">Cancelled</p>
                        </div>
                    </div>
                </div>
                <!-- New: Saved -->
                <div class="col-md-3 col-sm-6">
                    <div class="card text-white h-100 shadow-sm" style="background-color: #10B981; border: none;">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h1 class="display-5 fw-bold mb-0" id="global-saved-count">50</h1>
                            <p class="fs-6 fw-light mb-0">Saved to Database</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-12">
                    <div class="card bg-dark text-white h-100 shadow-sm" style="border: none;">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <h1 class="display-5 fw-bold mb-0" id="global-employers-count">10</h1>
                            <p class="fs-6 fw-light mb-0">Total Employers</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employer Header (Mockup for one employer) -->
            <div class="card mb-4 border-0 shadow-sm overflow-hidden">
                <div class="card-header bg-white py-4 px-4 border-bottom">
                    <div class="row w-100 align-items-center gy-3">
                        <div class="col-lg-6 d-flex align-items-center gap-4">
                            <div>
                                <h3 class="fw-bold mb-1">Acme Corp</h3>
                                <h5 class="text-muted mb-0 fw-light">Acme Corporation Ltd.</h5>
                            </div>
                            <div class="d-flex align-items-center gap-3 border-start ps-4 flex-wrap">
                                <!-- Total -->
                                <div class="d-flex flex-column align-items-center position-relative">
                                    <div class="rounded-circle d-flex justify-content-center align-items-center shadow-sm" style="width: 40px; height: 40px; background-color: #F3F4F6;">
                                        <span class="fw-bold fs-6 text-dark" id="employer-total-1">15</span>
                                    </div>
                                    <span class="badge bg-secondary rounded-pill position-absolute top-100 start-50 translate-middle-x mt-1 border border-white" style="font-size: 0.65rem;">TOTAL</span>
                                </div>
                                <!-- Not Started -->
                                <div class="d-flex flex-column align-items-center position-relative">
                                    <div class="rounded-circle d-flex justify-content-center align-items-center shadow-sm" style="width: 40px; height: 40px; background-color: #FEE2E2;">
                                        <span class="fw-bold fs-6 text-danger" id="employer-not-started-1">3</span>
                                    </div>
                                    <span class="badge bg-danger rounded-pill position-absolute top-100 start-50 translate-middle-x mt-1 border border-white" style="font-size: 0.65rem;">NOT STARTED</span>
                                </div>
                                <!-- Cancelled (New) -->
                                <div class="d-flex flex-column align-items-center position-relative">
                                    <div class="rounded-circle d-flex justify-content-center align-items-center shadow-sm" style="width: 40px; height: 40px; background-color: #E5E7EB;">
                                        <span class="fw-bold fs-6 text-muted" id="employer-cancelled-1">1</span>
                                    </div>
                                    <span class="badge bg-secondary rounded-pill position-absolute top-100 start-50 translate-middle-x mt-1 border border-white" style="font-size: 0.65rem; background-color: #6B7280 !important;">CANCELLED</span>
                                </div>
                                <!-- Saved (New) -->
                                <div class="d-flex flex-column align-items-center position-relative">
                                    <div class="rounded-circle d-flex justify-content-center align-items-center shadow-sm" style="width: 40px; height: 40px; background-color: #D1FAE5;">
                                        <span class="fw-bold fs-6 text-success" id="employer-saved-1">5</span>
                                    </div>
                                    <span class="badge bg-success rounded-pill position-absolute top-100 start-50 translate-middle-x mt-1 border border-white" style="font-size: 0.65rem;">SAVED</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test Button -->
            <div class="my-4">
                <button class="btn btn-primary" onclick="simulateUpdate()">Simulate Update</button>
            </div>
        </div>

        <script>
            function updateCounts(counts) {
                if (!counts) return;

                // Global
                if (counts.global) {
                    const elTotal = document.getElementById('global-total-count');
                    const elNotStarted = document.getElementById('global-not-started-count');
                    const elCancelled = document.getElementById('global-cancelled-count');
                    const elSaved = document.getElementById('global-saved-count');
                    const elEmployers = document.getElementById('global-employers-count');

                    if (elTotal) elTotal.innerText = counts.global.total;
                    if (elNotStarted) elNotStarted.innerText = counts.global.not_started;
                    if (elCancelled) elCancelled.innerText = counts.global.cancelled;
                    if (elSaved) elSaved.innerText = counts.global.saved;
                    if (elEmployers) elEmployers.innerText = counts.global.total_employers;
                }

                // Employer
                if (counts.employer) {
                    const id = counts.employer.id;
                    const elTotal = document.getElementById(`employer-total-${id}`);
                    const elNotStarted = document.getElementById(`employer-not-started-${id}`);
                    const elCancelled = document.getElementById(`employer-cancelled-${id}`);
                    const elSaved = document.getElementById(`employer-saved-${id}`);

                    if (elTotal) elTotal.innerText = counts.employer.total;
                    if (elNotStarted) elNotStarted.innerText = counts.employer.not_started;
                    if (elCancelled) elCancelled.innerText = counts.employer.cancelled;
                    if (elSaved) elSaved.innerText = counts.employer.saved;
                }
            }

            function simulateUpdate() {
                // Mock response
                const data = {
                    global: {
                        total: 101,
                        not_started: 19,
                        cancelled: 6,
                        saved: 51,
                        total_employers: 10
                    },
                    employer: {
                        id: 1,
                        total: 16,
                        not_started: 2,
                        cancelled: 2,
                        saved: 6
                    }
                };
                updateCounts(data);
            }
        </script>
    </body>
    </html>
    """

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        page.set_content(html_content)

        # Take initial screenshot
        page.screenshot(path="verification/verification_initial.png")

        # Click button to simulate update
        page.click("button:has-text('Simulate Update')")

        # Verify changes
        saved_count = page.locator("#global-saved-count").inner_text()
        print(f"Global Saved Count after update: {saved_count}")

        # Take updated screenshot
        page.screenshot(path="verification/verification_updated.png")

        browser.close()

if __name__ == "__main__":
    verify_layout()
