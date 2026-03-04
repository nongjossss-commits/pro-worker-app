import sys
from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        print("Navigating to login...")
        page.goto("http://localhost:8000/login")
        page.fill("input[name='email']", "test@example.com")
        page.fill("input[name='password']", "admin_password_1234")
        page.click("button[type='submit']")

        print("Waiting for network idle...")
        page.wait_for_load_state("networkidle")

        print("Navigating to production/registration...")
        page.goto("http://localhost:8000/production/registration")
        page.wait_for_load_state("networkidle")

        print("Clicking the first employer accordion header to expand...")
        try:
            # Try to click the first button that toggles a collapse
            page.locator('button[data-bs-toggle="collapse"]').first.click(timeout=5000)
            page.wait_for_timeout(2000) # wait for animation/fetch
        except Exception as e:
            print(f"Failed to click accordion: {e}")
            page.screenshot(path="verification_before_fail.png", full_page=True)

        print("Waiting for employee cards to appear...")
        try:
            employee_card = page.wait_for_selector(".employee-card-outer", timeout=10000)
            if employee_card:
                print("Card found! Taking screenshot...")
                employee_card.screenshot(path="verification_card_screenshot.png")
                page.screenshot(path="verification_full_fallback.png", full_page=True)
            else:
                print("Card not found!")
                page.screenshot(path="verification_full_fallback.png", full_page=True)
        except Exception as e:
            print(f"Error finding card: {e}")
            page.screenshot(path="verification_full_fallback.png", full_page=True)
            sys.exit(1)

        browser.close()

if __name__ == "__main__":
    run()
