from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    try:
        # Login
        page.goto("http://localhost:5173/login")
        page.get_by_label("Email").fill("admin@example.com")
        page.get_by_label("Password").fill("admin_password_1234")
        page.get_by_role("button", name="Log in").click()

        # Wait for navigation to the dashboard
        expect(page).to_have_url("http://localhost:5173/dashboard")

        # Navigate to the first employer's page
        page.goto("http://localhost:5173/employers")
        # Click the first "แก้ไข" (Edit) button
        page.locator("a.btn.btn-warning.btn-sm").first.click()

        # Wait for the employer's page to load
        expect(page).to_have_url(lambda url: "/edit" in url)

        # Click the "ประวัติการจ้างงาน" (Employment History) button
        page.get_by_role("button", name="ประวัติการจ้างงาน").click()

        # Wait for the modal to appear and the data to load
        modal_body = page.locator("#historyTableBody")
        # Check that the "Loading..." message is not present
        expect(modal_body.get_by_text("กำลังโหลดข้อมูล...")).to_have_count(0, timeout=10000)

        # Take a screenshot of the modal
        page.locator("#historyModal .modal-content").screenshot(path="jules-scratch/verification/verification.png")

    finally:
        context.close()
        browser.close()

with sync_playwright() as playwright:
    run(playwright)