from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
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

    # 3. Create a Job (to ensure we have a card with Active Item)
    # We click "Create Preparation Job"
    page.click("button[data-bs-target='#createJobModal']")

    # Wait for modal
    expect(page.locator("#createJobModal")).to_be_visible()

    # Fill form
    # We need an Employer. Seeder should have one. We'll try to select the first one.
    # The modal uses Select2 or standard select?
    # Let's check the view... it's included from workflow.partials.create_modal
    # Usually standard select for simple tests unless it's select2.
    # If it's select2, we might need to click it.

    # Let's try to assume we can select by value or label.
    # If standard select:
    # page.select_option("select[name='employer_id']", index=0)

    # But first, let's see if we can just use the UI to trigger the "Hide Empty" verification.
    # If we create a job but don't add employees, it should be hidden.

    # Actually, automating the creation might be complex if the UI uses Select2.
    # I'll create the data via `php artisan tinker` *before* running the visual check part.
    # This is more robust.

    page.screenshot(path="verification/pre_prod_dashboard.png")
    print("Screenshot taken.")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
