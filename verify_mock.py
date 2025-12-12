from playwright.sync_api import sync_playwright
import os

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    # Load the local HTML file
    file_path = os.path.abspath("mock_verification.html")
    page.goto(f"file://{file_path}")

    # 1. Verify "Add Custom Field" Modal
    # Click the button
    page.click("text=Test Add Custom Field Modal")
    page.wait_for_selector("#addCustomFieldModal.show")

    # Check if modal content is visible
    assert page.is_visible("text=Field Name (Label)")
    assert page.is_visible("text=Field Type")

    # Close modal
    page.click(".btn-close")

    # 2. Verify Finance Tab "Discount" Logic
    # Check initial values (Fixed Total 10000, VAT 7%, Excl)
    # Base: 10000. Net: 10000. VAT: 700. Total: 10700.

    # Enter Discount 1000
    page.fill("input[x-model='discount']", "1000")

    # Allow Alpine to update
    page.wait_for_timeout(500)

    # Verify Text in Financial Summary Card
    # We find the card that contains "Financial Summary" header, then check its body
    summary_card = page.locator(".card", has_text="Financial Summary")

    # Check for "Discount: - ฿1,000.00"
    # Note: Using generic text check to avoid issues with exact whitespace
    expect_discount = "1,000.00"
    expect_total = "9,630.00"

    assert summary_card.get_by_text(expect_discount).is_visible()
    assert summary_card.get_by_text(expect_total).is_visible()

    page.screenshot(path="verification_mock.png")
    print("Verification Successful. Screenshot saved.")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
