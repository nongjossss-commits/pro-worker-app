from playwright.sync_api import sync_playwright
import os

def verify_ticket_attachment_rendering():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # We cannot easily simulate the full Laravel session and database state in this environment.
        # Instead, we will construct a static HTML file that mimics the structure of the Create Ticket page
        # after Alpine.js has processed the data, focusing on the hidden inputs.

        # Load the Bootstrap and Alpine.js via CDN for this test
        html_content = """
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Verification</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        </head>
        <body>
            <div x-data="hybridAttachmentManager({
                employerId: 1,
                preselectedEmployeeIds: [101, 102]
            })">
                <form id="testForm">
                    <!-- Mimic the included partial _basket_form_inputs -->
                    <!-- Existing Employees Inputs -->
                    <template x-for="(item, index) in basket.existing_employees" :key="'input-e-' + item.id">
                        <input type="hidden" :name="'attachments[existing_employees][' + index + ']'" :value="item.id">
                    </template>
                </form>

                <!-- Mimic the basket display -->
                <div id="basket-debug">
                    <template x-for="item in basket.existing_employees">
                        <div x-text="item.employeeNameTh"></div>
                    </template>
                </div>
            </div>

            <script>
            function hybridAttachmentManager(config = {}) {
                return {
                    basket: { existing_employees: [], new_employees: [], files: [] },
                    init() {
                        // Simulate pre-loading employees
                        this.basket.existing_employees = [
                            { id: 101, employeeNameTh: 'Employee A' },
                            { id: 102, employeeNameTh: 'Employee B' }
                        ];
                    }
                }
            }
            </script>
        </body>
        </html>
        """

        # Save to a temp file
        with open("verification/test_alpine_inputs.html", "w") as f:
            f.write(html_content)

        # Open the file
        page.goto(f"file://{os.getcwd()}/verification/test_alpine_inputs.html")

        # Wait for Alpine to initialize
        page.wait_for_timeout(1000)

        # Take a screenshot
        page.screenshot(path="verification/alpine_inputs.png")

        # Verify the hidden inputs exist in the DOM
        inputs = page.locator("input[type='hidden']")
        count = inputs.count()
        print(f"Found {count} hidden inputs.")

        for i in range(count):
            input_el = inputs.nth(i)
            name = input_el.get_attribute("name")
            value = input_el.get_attribute("value")
            print(f"Input {i}: name='{name}', value='{value}'")

        browser.close()

if __name__ == "__main__":
    os.makedirs("verification", exist_ok=True)
    verify_ticket_attachment_rendering()
