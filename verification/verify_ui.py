from playwright.sync_api import sync_playwright
import time

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    try:
        # 1. Login
        page.goto("http://127.0.0.1:8000/login", timeout=60000)
        page.fill('input[name="email"]', 'test@example.com')
        page.fill('input[name="password"]', 'admin_password_1234')
        page.click('button[type="submit"]')
        page.wait_for_url(lambda url: "login" not in url, timeout=60000)

        # 3. Production Dashboard
        print("Navigating to Production...")
        page.goto("http://127.0.0.1:8000/production")
        time.sleep(2)

        # Screenshot Production
        page.screenshot(path="verification/production_dashboard.png")
        print("Production captured.")

    except Exception as e:
        print(f"Error: {e}")
    finally:
        browser.close()

with sync_playwright() as playwright:
    run(playwright)
