import re
from playwright.sync_api import sync_playwright, Page, expect

def run(playwright):
    """
    This script verifies the new SweetAlert2 confirmation for force-deleting an employee.
    """
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    try:
        # 1. Navigate to the employer's edit page.
        # Assuming the app is running on localhost:8000 and employer with ID 1 exists.
        page.goto("http://localhost:8000/employers/1/edit")

        # 2. Open the Employment History modal.
        history_button = page.get_by_role("button", name=re.compile("ดูประวัติการจ้างงาน"))
        expect(history_button).to_be_visible()
        history_button.click()

        # 3. Wait for the modal and data to load, then click a "force delete" button.
        # We'll target the first force-delete button available in the modal.
        # The data is loaded via fetch, so we wait for the button to appear.
        force_delete_button = page.locator('#history-body .btn-force-delete').first
        expect(force_delete_button).to_be_visible(timeout=10000) # Wait up to 10s for fetch
        force_delete_button.click()

        # 4. Assert that the SweetAlert2 confirmation dialog is visible.
        swal_title = page.locator('#swal2-title')
        expect(swal_title).to_have_text("คุณแน่ใจหรือไม่?")

        swal_content = page.locator('#swal2-html-container')
        expect(swal_content).to_have_text("การกระทำนี้จะลบข้อมูลพนักงานอย่างถาวรและไม่สามารถย้อนกลับได้!")

        # 5. Take a screenshot for visual confirmation.
        page.screenshot(path="jules-scratch/verification/verification.png")
        print("Screenshot saved to jules-scratch/verification/verification.png")

    except Exception as e:
        print(f"An error occurred: {e}")
        page.screenshot(path="jules-scratch/verification/error.png")

    finally:
        # 6. Clean up.
        context.close()
        browser.close()

with sync_playwright() as playwright:
    run(playwright)