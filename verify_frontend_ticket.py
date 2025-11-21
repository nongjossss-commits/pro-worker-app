import os
from playwright.sync_api import sync_playwright

def verify_frontend():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # 2. Verify Ticket Detail Advanced Export
        print("Verifying Ticket Detail Advanced Export...")
        cwd = os.getcwd()
        page.goto(f"file://{cwd}/mock_ticket_detail.html")

        # Select Employee FIRST
        page.locator(".employee-checkbox").first.check()

        # Click Dropdown Toggle
        page.locator("button.dropdown-toggle").click()

        # Find the export button - Using force=True to ignore visibility checks if animation is slow
        export_btn = page.locator("#ticket-existing-bulk-advanced-export-btn")

        # Click with force=True
        export_btn.click(force=True)

        # Check if Modal opens (wait for it)
        modal = page.locator("#advancedExportModal")
        try:
            modal.wait_for(state="visible", timeout=2000)
            print("Success: Advanced Export Modal opened.")

            ids = page.locator("#export_employee_ids").input_value()
            print(f"Export IDs: {ids}")
            if '101' in ids:
                print("Success: IDs populated correctly.")
        except:
             print("Error: Modal did not open within timeout.")

        page.screenshot(path="verification_ticket_export_fixed.png")

        browser.close()

if __name__ == "__main__":
    verify_frontend()
