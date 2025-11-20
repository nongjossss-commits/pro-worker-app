from playwright.sync_api import sync_playwright, expect
import os
import re

def verify_sidebar_logic():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        # Emulate a mobile device
        context = browser.new_context(viewport={'width': 375, 'height': 667})
        page = context.new_page()

        # Load the mock file
        file_path = os.path.abspath("verification/mock_dashboard.html")
        page.goto(f"file://{file_path}")

        # Wait for logic to run
        page.wait_for_timeout(1000)

        sidebar = page.locator("#sidebar")

        # 1. Verify sidebar is visible initially (due to our script)
        # Bootstrap adds 'show' class to the element or makes it visible.
        # We check if the class attribute contains 'show'.
        expect(sidebar).to_have_class(re.compile(r"show"))
        print("Verification 1 Passed: Sidebar is open automatically on mobile.")

        # Take screenshot 1
        page.screenshot(path="verification/sidebar_open.png")

        # 2. Click a link
        link = sidebar.locator("a").first
        link.click()

        # Wait for transition (Bootstrap transition takes time)
        page.wait_for_timeout(1000)

        # 3. Verify sidebar is hidden
        # Use regex to ensure 'show' is NOT present in the class list logic,
        # but Playwright's not_to_have_class with regex means "class does not match pattern".
        # If we want to ensure "show" is absent, we can just check it doesn't have the class "offcanvas-lg offcanvas-start show".
        # Or better, check if it is hidden.
        # Since it's an offcanvas, when hidden, it loses the 'show' class.

        # We expect it NOT to match the regex containing "show"
        expect(sidebar).not_to_have_class(re.compile(r"show"))

        print("Verification 2 Passed: Sidebar closed after link click.")

        # Take screenshot 2
        page.screenshot(path="verification/sidebar_closed.png")

        browser.close()

if __name__ == "__main__":
    verify_sidebar_logic()
