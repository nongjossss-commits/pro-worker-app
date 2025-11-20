
from playwright.sync_api import sync_playwright
import os

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    # Get absolute path to the HTML file
    file_path = os.path.abspath("verification/test_sidebar.html")
    page.goto(f"file://{file_path}")

    # Take screenshot of the sidebar area
    sidebar = page.locator("#sidebar")
    sidebar.screenshot(path="verification/sidebar_logo.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
