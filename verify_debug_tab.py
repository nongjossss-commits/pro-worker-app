from playwright.sync_api import sync_playwright

def verify_workflow_ui():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context()
        page = context.new_page()

        # 1. Login
        print("Logging in as Admin...")
        page.goto("http://127.0.0.1:8080/login")
        page.fill("input[name='email']", "test@example.com")
        page.fill("input[name='password']", "password")
        page.click("button[type='submit']")

        # Wait for redirect
        page.wait_for_url("**/dashboard", timeout=10000)

        # 2. Go to Workflow Tab
        print("Navigating to Workflow (Notify In)...")
        page.goto("http://127.0.0.1:8080/workflow?tab=notify_in")
        page.wait_for_timeout(3000)

        page.screenshot(path="workflow_tab.png")
        print("Screenshot saved.")

        browser.close()

if __name__ == "__main__":
    verify_workflow_ui()
