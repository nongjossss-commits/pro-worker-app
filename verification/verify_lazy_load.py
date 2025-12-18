from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    # Simulate basic HTML structure with expected Placeholders
    html_content = """
    <html>
    <head>
        <meta name="csrf-token" content="TEST_TOKEN">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="accordion" id="employersAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading1">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="false" aria-controls="collapse1">
                        Employer 1
                    </button>
                </h2>
                <div id="collapse1" class="accordion-collapse collapse" aria-labelledby="heading1" data-bs-parent="#employersAccordion">
                    <div class="accordion-body">
                        <div id="employee-list-1">
                             <div class="spinner-border"></div> Loading...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Mock Route function
            window.route = function() { return 'http://localhost/production/registration'; }

            // Injected Logic from index.blade.php
            window.loadedEmployers = {};
            window.loadEmployees = function(employerId) {
                if (window.loadedEmployers[employerId]) return;

                // MOCK FETCH for Verification
                const container = document.getElementById(`employee-list-${employerId}`);
                container.innerHTML = "FETCH_INITIATED";

                // Simulate Network Delay
                setTimeout(() => {
                    container.innerHTML = '<div class="employee-card">MOCKED EMPLOYEE DATA</div>';
                    window.loadedEmployers[employerId] = true;
                }, 100);
            }

            document.addEventListener('DOMContentLoaded', function() {
                const accordion = document.getElementById('employersAccordion');
                accordion.addEventListener('show.bs.collapse', function (e) {
                    if (e.target.classList.contains('accordion-collapse')) {
                        const employerId = e.target.id.replace('collapse', '');
                        loadEmployees(employerId);
                    }
                });
            });
        </script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    """

    page.set_content(html_content)

    # 1. Verify Placeholder exists initially
    initial_content = page.inner_text("#employee-list-1")
    assert "Loading" in initial_content, f"Expected Loading spinner, found: {initial_content}"
    print("Verified: Initial state shows Loading spinner.")

    # 2. Click to Expand
    page.click("button[data-bs-target='#collapse1']")

    # 3. Wait for Fetch Trigger (Mocked as immediate text change to FETCH_INITIATED)
    # The bootstrap collapse animation takes time, but our listener fires on 'show.bs.collapse' which is immediate.
    page.wait_for_function("document.getElementById('employee-list-1').innerHTML.includes('FETCH_INITIATED')")
    print("Verified: AJAX Fetch triggered on expand.")

    # 4. Wait for 'Network' completion
    page.wait_for_function("document.getElementById('employee-list-1').innerHTML.includes('MOCKED EMPLOYEE DATA')")
    print("Verified: Content updated after fetch.")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
