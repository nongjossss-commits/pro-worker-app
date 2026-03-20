from playwright.sync_api import sync_playwright
import time

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context(viewport={'width': 1920, 'height': 1080})
    page = context.new_page()

    page.goto("http://localhost:8000/login")
    page.fill('input[name="email"]', "superadmin@proworker.com")
    page.fill('input[name="password"]', "SuperAdmin@2026")
    page.click('button[type="submit"]')
    page.wait_for_url("**/index")

    page.goto("http://localhost:8000/workflow?tab=renewal_mou")
    page.wait_for_timeout(2000)

    # Expand accordion
    try:
        page.click('.btn-light.rounded-circle.ms-2', timeout=2000)
        page.wait_for_timeout(2000)
    except Exception as e:
        print("Could not expand accordion:", e)

    page.screenshot(path="workflow_default.png", full_page=True)

    # Click toggle
    try:
        page.click("id=btn-global-toggle-cancelled", timeout=2000)
        page.wait_for_timeout(3000)

        # Expand accordion again after reload
        try:
            page.click('.btn-light.rounded-circle.ms-2', timeout=2000)
            page.wait_for_timeout(2000)
        except:
            pass

        page.screenshot(path="workflow_show_cancelled.png", full_page=True)
    except Exception as e:
        print("Could not click toggle:", e)

    context.close()
    browser.close()

with sync_playwright() as playwright:
    run(playwright)
