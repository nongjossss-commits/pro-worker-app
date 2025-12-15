from playwright.sync_api import sync_playwright, expect
import os

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    # Load the local HTML file
    file_path = os.path.abspath("verification/test_bulk_edit.html")
    page.goto(f"file://{file_path}")

    # Wait for the status element to appear
    # This element is added by the script inside DOMContentLoaded
    status_locator = page.locator("#test-status")
    expect(status_locator).to_be_visible(timeout=5000)

    # Check the text content
    text = status_locator.text_content()
    print(f"Status: {text}")

    # Assert success
    expect(status_locator).to_have_text("SUCCESS: Bootstrap loaded and Modal initialized")

    # Take a screenshot
    page.screenshot(path="verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
