from playwright.sync_api import sync_playwright
import os

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    # Load the local HTML file
    file_path = os.path.abspath("verification/mock_prepare.html")
    page.goto(f"file://{file_path}")

    # Wait for content to load
    page.wait_for_selector("h2")

    # Take screenshot
    page.screenshot(path="verification/prepare_view.png", full_page=True)
    print("Screenshot saved to verification/prepare_view.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
