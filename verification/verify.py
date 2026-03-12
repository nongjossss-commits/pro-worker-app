import time
from playwright.sync_api import sync_playwright, expect

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Login
        page.goto("http://localhost:8000/login")
        page.wait_for_load_state('networkidle')
        page.screenshot(path="/app/verification/login.png")

        try:
            page.fill("input[name='email']", "staff@example.com")
            page.fill("input[name='password']", "staff_password_1234")
            page.click("button[type='submit']")
            page.wait_for_url("**/index")

            # Navigate to Importer Create
            page.goto("http://localhost:8000/importers/create")
            page.wait_for_selector("h2:has-text('เพิ่มข้อมูลบริษัทนำเข้า')")

            # Scroll to bottom
            page.evaluate("window.scrollTo(0, document.body.scrollHeight)")
            time.sleep(1)

            page.screenshot(path="/app/verification/importer_create_bottom.png")

            # Add a test address
            page.click("button.add-address-btn[data-type='registered']")
            page.wait_for_selector("#addressModal.show")

            page.screenshot(path="/app/verification/importer_address_modal.png")

            # Navigate to Delegate Create
            page.goto("http://localhost:8000/delegates/create")
            page.wait_for_selector("h2:has-text('Add Delegate')")

            page.screenshot(path="/app/verification/delegate_create_top.png")

        except Exception as e:
            print(f"Exception: {e}")

        browser.close()

if __name__ == "__main__":
    run()