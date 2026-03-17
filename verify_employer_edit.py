from playwright.sync_api import sync_playwright

def verify_employer_edit():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()

        # Go to login page
        page.goto("http://localhost:8000/login")

        # Login
        page.fill("input[name='email']", "staff@example.com")
        page.fill("input[name='password']", "staff_password_1234")
        page.click("button[type='submit']")
        page.wait_for_url("**/index")

        # Go to employer edit page
        page.goto("http://localhost:8000/employers/1/edit")

        # Wait for checkboxes
        page.wait_for_selector(".employee-checkbox")

        # Select a checkbox
        checkbox = page.locator(".employee-checkbox").first
        checkbox.check()

        # Wait for bulk action bar to become visible
        page.wait_for_selector("#employer-edit-bulk-bar", state="visible")

        # Click Actions dropdown
        page.click("#employer-edit-bulk-bar .dropdown-toggle")

        # Wait for the dropdown menu
        page.wait_for_selector(".dropdown-menu.show")

        # Take a screenshot
        page.screenshot(path="verification_employer_edit_bulk_action.png", full_page=True)

        browser.close()

if __name__ == "__main__":
    verify_employer_edit()
