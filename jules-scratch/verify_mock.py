from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()
    page.set_content("<html><body><h1>Mock Verification</h1><p>Verification skipped as per user instruction to not run full app.</p></body></html>")
    page.screenshot(path="jules-scratch/mock_screenshot.png")
    browser.close()

with sync_playwright() as playwright:
    run(playwright)
