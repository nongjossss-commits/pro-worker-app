
import os
from playwright.sync_api import sync_playwright

def create_mock_html():
    # Read the Blade file
    with open('resources/views/tickets/partials/_new_employee_modal.blade.php', 'r') as f:
        blade_content = f.read()

    # Simple cleanup of Blade specific syntax that might break HTML rendering if not processed
    # Removing blade comments
    import re
    blade_content = re.sub(r'\{\{--.*?--\}\}', '', blade_content, flags=re.DOTALL)

    # We can leave x-model and :class as they are valid HTML attributes (Alpine)

    html_content = f"""
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mock Modal Verification</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
        <style>
            /* Force modal to be visible for screenshot */
            .modal {{
                display: block !important;
                opacity: 1 !important;
                position: relative !important;
                z-index: 1055 !important;
            }}
            .modal-backdrop {{
                display: none !important;
            }}
        </style>
    </head>
    <body class="bg-light p-5" x-data="{{ newEmployeeForm: {{}} }}">

        <!-- Inject Blade Content Here -->
        {blade_content}

    </body>
    </html>
    """

    with open('verification/mock_modal.html', 'w') as f:
        f.write(html_content)

def verify_modal():
    create_mock_html()

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Load the local HTML file
        file_path = os.path.abspath('verification/mock_modal.html')
        page.goto(f'file://{file_path}')

        # Assertions to verify attributes are gone
        # 1. Title (Thai) should NOT have required
        title_select = page.locator('#modal_employeeTitleTh')
        assert title_select.get_attribute('required') is None, "Error: modal_employeeTitleTh still has required attribute"

        # 2. Passport should NOT have required
        passport_input = page.locator('#modal_employeePassport')
        assert passport_input.get_attribute('required') is None, "Error: modal_employeePassport still has required attribute"

        # 3. Name (Thai) SHOULD have required
        name_input = page.locator('#modal_employeeNameTh')
        assert name_input.get_attribute('required') is not None, "Error: modal_employeeNameTh missing required attribute"

        # Screenshot
        page.locator('.modal-content').screenshot(path='verification/verification.png')

        print("Verification successful! Screenshot saved to verification/verification.png")

        browser.close()

if __name__ == "__main__":
    verify_modal()
