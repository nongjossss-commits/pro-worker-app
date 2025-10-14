from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Login
    page.goto("http://localhost:8000/login")
    page.get_by_label("Email").fill("admin@example.com")
    page.get_by_label("Password").fill("admin_password_1234")
    page.get_by_role("button", name="Log in").click()
    expect(page).to_have_url("http://localhost:8000/dashboard")

    # Navigate to employer edit page
    page.goto("http://localhost:8000/employers/1/edit")

    # Verify nationality flag in table view
    page.get_by_role("link", name="ตาราง").click()
    nationality_cell = page.locator("td:has-text('เมียนมา')").first
    expect(nationality_cell.locator(".flag-icon-mm")).to_be_visible()

    # Open history modal and verify buttons
    page.get_by_role("button", name="ดูประวัติการจ้างงาน").click()

    # Wait for the modal to be visible and content to load
    expect(page.locator("#employmentHistoryModal")).to_be_visible()
    expect(page.get_by_text("ประวัติการจ้างงาน")).to_be_visible()

    # Wait for the row to appear, might need a longer timeout if data loading is slow
    restore_button = page.locator('.js-restore-btn').first
    delete_button = page.locator('.js-force-delete-btn').first

    expect(restore_button).to_be_visible(timeout=10000) # Increased timeout
    expect(restore_button).to_have_text("คืนสถานะ")

    expect(delete_button).to_be_visible()
    expect(delete_button).to_have_text("ลบถาวร")

    # Take screenshot
    page.screenshot(path="jules-scratch/verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)