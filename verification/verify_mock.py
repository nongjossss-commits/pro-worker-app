from playwright.sync_api import sync_playwright
import os

def run(playwright):
    browser = playwright.chromium.launch()
    page = browser.new_page()

    # Load the mock HTML file
    file_path = os.path.abspath("verification/mock_group_manage.html")
    page.goto(f"file://{file_path}")

    # Screenshot
    page.screenshot(path="verification/mock_group_manage.png")
    browser.close()

with sync_playwright() as playwright:
    run(playwright)
