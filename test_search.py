import sys
from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        print("Navigating to login...")
        page.goto("http://localhost:8000/login")
        page.fill("input[name='email']", "staff@example.com")
        page.fill("input[name='password']", "staff_password_1234")
        page.click("button[type='submit']")

        print("Waiting for network idle...")
        page.wait_for_load_state("networkidle")

        print("Navigating to production/renewal...")
        page.goto("http://localhost:8000/production/renewal")
        page.wait_for_load_state("networkidle")

        # Test 1: We will capture a screenshot of the search. First, find a request_number or passport to search for.
        # But wait, we need an employee seeded. I'll rely on what's there.
        page.screenshot(path="verification_renewal_before_search.png", full_page=True)

        print("Testing Search on Renewal...")
        # Since I am in the testing environment, I will just put something in the search box
        page.fill("input[name='search']", "REN-002")
        page.keyboard.press("Enter")
        page.wait_for_load_state("networkidle")
        page.wait_for_timeout(2000)

        page.screenshot(path="verification_renewal_after_search.png", full_page=True)

        print("Clicking the first employer accordion header to expand...")
        try:
            page.locator('button[data-bs-toggle="collapse"]').first.click(timeout=5000)
            page.wait_for_timeout(2000) # wait for animation/fetch
        except Exception as e:
            print(f"Failed to click accordion: {e}")

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

        browser.close()

if __name__ == "__main__":
    run()
