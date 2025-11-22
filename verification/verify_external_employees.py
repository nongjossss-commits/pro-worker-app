from playwright.sync_api import sync_playwright, expect
import os

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    # Load the local HTML file
    file_path = os.path.abspath("verification/test_external_employees.html")
    page.goto(f"file://{file_path}")

    # 1. Verify the button exists
    external_btn = page.locator("#btn-external-employees")
    expect(external_btn).to_be_visible()
    expect(external_btn).to_have_text("แนบลูกจ้างภายนอก")
    print("Button found.")

    # 2. Click the button to open modal
    external_btn.click()

    # 3. Verify modal opens
    modal = page.locator("#existingEmployeeModal")
    expect(modal).to_be_visible()
    print("Modal opened.")

    # 4. Type in search to trigger mock fetch (simulated by typing)
    search_input = page.locator("input[placeholder='ค้นหา...']")
    search_input.fill("Som")

    # Wait for mock results
    page.wait_for_timeout(1000)

    # 5. Select "Somsak (External)"
    # Find checkbox with value 2 (Somsak)
    checkbox = page.locator("input[type='checkbox'][value='2']")
    checkbox.check()

    # 6. Confirm selection
    page.locator("#btn-confirm-selection").click()

    # 7. Verify item added to basket (External Employees section)
    # Look for element with border-warning which we added to the template
    added_item = page.locator(".list-group-item.border-warning")
    expect(added_item).to_be_visible()
    expect(added_item).to_contain_text("Somsak (External)")
    expect(added_item).to_contain_text("(Ext)")
    print("External employee added to basket successfully.")

    # Take screenshot
    page.screenshot(path="verification/external_employees_verified.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
