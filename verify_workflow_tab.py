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

        print("Navigating to workflow...")
        page.goto("http://localhost:8000/workflow?tab=1")
        page.wait_for_load_state("networkidle")

        # Take a screenshot before clicking the button
        page.screenshot(path="verification_workflow_before.png", full_page=True)

        # Get the URL before clicking
        url_before = page.url
        print(f"URL Before: {url_before}")

        print("Clicking the Hide Cancelled button...")
        try:
            btn = page.locator('#btn-global-toggle-cancelled')
            btn.wait_for(state="visible", timeout=5000)
            btn.click()
            page.wait_for_load_state("networkidle")
            page.wait_for_timeout(1000)

            # Get the URL after clicking
            url_after = page.url
            print(f"URL After: {url_after}")

            # Take a screenshot after clicking the button
            page.screenshot(path="verification_workflow_after.png", full_page=True)
        except Exception as e:
            print(f"Error clicking button: {e}")
            page.screenshot(path="verification_workflow_error.png", full_page=True)

        browser.close()

if __name__ == "__main__":
    run()
