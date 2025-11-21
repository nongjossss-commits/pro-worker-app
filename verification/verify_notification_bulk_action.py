from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    html_content = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Notification Bulk Action Test</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            /* Mock styles */
            .bulk-action-bar { display: none; }
        </style>
    </head>
    <body>
        <div class="container mt-5">
            <!-- Bulk Action Bar -->
            <div id="bulk-action-bar-notifications" class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3 py-2 px-3 bg-light border rounded gap-2" style="display: none;">
                <div>
                    <input class="form-check-input" type="checkbox" id="select-all-checkbox-notifications">
                    <label class="form-check-label ms-2" for="select-all-checkbox-notifications">
                        Select All (<span id="selected-count-notifications">0</span>)
                    </label>
                </div>
                <div class="dropdown w-100 w-md-auto">
                    <button class="btn btn-primary btn-sm dropdown-toggle w-100" type="button" id="notificationBulkActionBtn" data-bs-toggle="dropdown" aria-expanded="false" disabled>
                        Action on selected items
                    </button>
                    <ul class="dropdown-menu w-100" aria-labelledby="notificationBulkActionBtn">
                        <li><a class="dropdown-item" href="#">Download Files</a></li>
                    </ul>
                </div>
            </div>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="work_permit_mou-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr><th colspan="8">For Renewal (1 Item)</th></tr>
                                <tr>
                                    <th style="width: 1%;"><input class="form-check-input" type="checkbox" id="select-all-checkbox-notifications-mou1"></th>
                                    <th>#</th>
                                    <th>Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input class="form-check-input bulk-action-checkbox" type="checkbox" value="1"></td>
                                    <td>1</td>
                                    <td>Employee 1</td>
                                </tr>
                            </tbody>

                            <thead class="table-light mt-4">
                                <tr><th colspan="8">For New Application (1 Item)</th></tr>
                                <tr>
                                    <th style="width: 1%;"><input class="form-check-input" type="checkbox" id="select-all-checkbox-notifications-mou2"></th>
                                    <th>#</th>
                                    <th>Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input class="form-check-input bulk-action-checkbox" type="checkbox" value="2"></td>
                                    <td>2</td>
                                    <td>Employee 2</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            // --- Bulk Action Script (Copied and Adapted from View) ---
            const container = document.querySelector('.tab-content');
            const actionBar = document.getElementById('bulk-action-bar-notifications');
            const selectAllCheckbox = document.getElementById('select-all-checkbox-notifications');
            const selectAllCheckboxMOU1 = document.getElementById('select-all-checkbox-notifications-mou1');
            const selectAllCheckboxMOU2 = document.getElementById('select-all-checkbox-notifications-mou2');
            const selectedCountSpan = document.getElementById('selected-count-notifications');
            const actionButton = document.getElementById('notificationBulkActionBtn');

            function updateActionBar() {
                const activePane = container.querySelector('.tab-pane.active');
                if (!activePane) return;

                const itemCheckboxes = activePane.querySelectorAll('.bulk-action-checkbox');
                const selectedCheckboxes = activePane.querySelectorAll('.bulk-action-checkbox:checked');
                const count = selectedCheckboxes.length;

                if (count > 0) {
                    actionBar.style.display = 'flex';
                    selectedCountSpan.textContent = count;
                    actionButton.disabled = false;
                } else {
                    actionBar.style.display = 'none';
                    selectedCountSpan.textContent = 0;
                    actionButton.disabled = true;
                }
            }

            container.addEventListener('change', function(e) {
                if (e.target.classList.contains('bulk-action-checkbox')) {
                    updateActionBar();
                }
            });

            // Function to handle section-specific select all (for MOU table)
            function handleSectionSelectAll(triggerCheckbox) {
                 const thead = triggerCheckbox.closest('thead');
                 if(!thead) return;
                 const tbody = thead.nextElementSibling;
                 if(tbody && tbody.tagName === 'TBODY') {
                     const checkboxes = tbody.querySelectorAll('.bulk-action-checkbox');
                     checkboxes.forEach(cb => cb.checked = triggerCheckbox.checked);
                     updateActionBar();
                 }
            }

            if (selectAllCheckboxMOU1) {
                selectAllCheckboxMOU1.addEventListener('change', function() {
                    handleSectionSelectAll(this);
                });
            }

            if (selectAllCheckboxMOU2) {
                selectAllCheckboxMOU2.addEventListener('change', function() {
                    handleSectionSelectAll(this);
                });
            }
        });
        </script>
    </body>
    </html>
    """

    page.set_content(html_content)

    # Test Case: Click "Select All" for the first section (Renewal)
    page.check("#select-all-checkbox-notifications-mou1")

    # Verification
    # 1. Check if row 1 checkbox is checked
    row1_checkbox = page.locator("tbody tr td input[value='1']")
    assert row1_checkbox.is_checked(), "Row 1 checkbox should be checked"

    # 2. Check if row 2 checkbox is NOT checked (should be independent)
    row2_checkbox = page.locator("tbody tr td input[value='2']")
    assert not row2_checkbox.is_checked(), "Row 2 checkbox should NOT be checked"

    # 3. Check if bulk action bar is visible
    action_bar = page.locator("#bulk-action-bar-notifications")
    assert action_bar.is_visible(), "Bulk action bar should be visible"

    # 4. Check if count is 1
    count_span = page.locator("#selected-count-notifications")
    assert count_span.inner_text() == "1", "Selected count should be 1"

    # 5. Check if action button is enabled
    action_button = page.locator("#notificationBulkActionBtn")
    assert not action_button.is_disabled(), "Action button should be enabled"

    print("Verification Successful: Section selection works correctly.")

    page.screenshot(path="verification/notification_bulk_action.png")
    browser.close()

with sync_playwright() as playwright:
    run(playwright)
