import os
from playwright.sync_api import sync_playwright

def verify_frontend():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # 1. Verify Notification List Bulk Action Bar
        print("Verifying Notification List...")
        cwd = os.getcwd()
        page.goto(f"file://{cwd}/mock_notifications.html")

        # Initially hidden
        bar = page.locator("#bulk-action-bar-notifications")
        if bar.is_visible():
            print("Error: Bulk action bar should be hidden initially.")
        else:
            print("Success: Bulk action bar hidden initially.")

        # Select an item
        page.locator("#notification_checkbox_1").check()

        # Should be visible now
        if bar.is_visible():
            print("Success: Bulk action bar visible after selection.")
        else:
            print("Error: Bulk action bar NOT visible after selection.")

        page.screenshot(path="verification_notifications.png")

        # 2. Verify Ticket Detail Advanced Export
        print("Verifying Ticket Detail Advanced Export...")
        page.goto(f"file://{cwd}/mock_ticket_detail.html")

        # Check for Export Button
        export_btn = page.locator("#ticket-existing-bulk-advanced-export-btn")
        if export_btn.is_visible():
            print("Success: Advanced Export button is visible (inside dropdown).")
        else:
            # Use force=True to click dropdown first if needed, but here we just check existence in DOM or visibility if open
            # It's in a dropdown, so we click dropdown first
            page.locator("button.dropdown-toggle").click()
            if export_btn.is_visible():
                 print("Success: Advanced Export button visible after dropdown click.")
            else:
                 print("Error: Advanced Export button not found.")

        # Select Employee and Click Export
        page.locator(".employee-checkbox").first.check()
        export_btn.click()

        # Check if Modal opens
        modal = page.locator("#advancedExportModal")
        if modal.is_visible():
             print("Success: Advanced Export Modal opened.")
             # Check if ID is populated
             ids = page.locator("#export_employee_ids").input_value()
             print(f"Export IDs: {ids}")
             if '101' in ids:
                 print("Success: IDs populated correctly.")
        else:
             print("Error: Modal did not open.")

        page.screenshot(path="verification_ticket_export.png")

        browser.close()

if __name__ == "__main__":
    verify_frontend()
