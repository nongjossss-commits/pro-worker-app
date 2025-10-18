from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Login
    page.goto("http://localhost:5173/login")
    page.fill('input[name="email"]', "admin@example.com")
    page.fill('input[name="password"]', "admin_password_1234")
    page.click('button[type="submit"]')
    page.wait_for_url("http://localhost:5173/dashboard")

    # Go to employers page
    page.goto("http://localhost:5173/employers")

    # Click first employer
    page.click('a.btn.btn-info.btn-sm')

    # Click employment history
    page.click('button[data-bs-target="#employmentHistoryModal"]')

    # Wait for modal to be visible
    page.wait_for_selector('#employmentHistoryModal.show')

    # Click the restore button
    page.click('#historyTableBody .js-restore-form button')

    # Wait for the confirmation modal to be visible
    page.wait_for_selector('#restoreConfirmationModal.show')

    # Take screenshot
    page.screenshot(path="jules-scratch/verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
