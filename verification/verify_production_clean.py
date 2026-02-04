from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context(viewport={'width': 1280, 'height': 800}) # Set viewport
    page = context.new_page()

    # 1. Login
    page.goto("http://127.0.0.1:8001/login")
    page.fill("input[name='email']", "test@example.com")
    page.fill("input[name='password']", "password")
    page.click("button[type='submit']")

    # Wait for dashboard
    expect(page).to_have_url("http://127.0.0.1:8001/dashboard")
    print("Logged in successfully.")

    # 2. Go to Pre-Production
    page.goto("http://127.0.0.1:8001/production")

    # Verify Title (New Dashboard Style)
    expect(page.get_by_text("Preparation Progress")).to_be_visible()
    print("Pre-Production Dashboard loaded.")

    # No clicking modal. Just screenshot the list.

    page.screenshot(path="verification/pre_prod_dashboard_clean.png")
    print("Screenshot taken.")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
