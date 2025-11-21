
from playwright.sync_api import sync_playwright, expect
import os

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    # Load the mock HTML file
    file_path = os.path.abspath("verification/mock_page.html")
    page.goto(f"file://{file_path}")

    # Check if bulk action bar is visible
    expect(page.locator("#mock-bulk-bar")).to_be_visible()

    # Click the dropdown toggle first to make the item visible
    page.locator(".dropdown-toggle").click()

    # Click the Advanced Export button
    page.locator("#mock-advanced-export-btn").click()

    # Wait for modal to appear
    expect(page.locator("#advancedExportModal")).to_be_visible()

    # Take screenshot
    page.screenshot(path="verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
