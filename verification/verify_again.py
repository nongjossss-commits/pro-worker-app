from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        page.goto("http://localhost:8000/login")
        page.fill("input[name='email']", "test@example.com")
        page.fill("input[name='password']", "admin_password_1234")
        page.click("button[type='submit']")
        page.wait_for_load_state("networkidle")

        page.goto("http://localhost:8000/production")
        button = page.locator("button[data-bs-toggle='collapse']").first
        button.click()
        page.wait_for_selector(".employee-checkbox", timeout=5000)
        checkbox = page.locator(".employee-checkbox:enabled").first
        checkbox.check()
        page.click("#bulkActionDropdown")
        page.screenshot(path="verification/bulk_actions.png")
        browser.close()

if __name__ == "__main__":
    run()
