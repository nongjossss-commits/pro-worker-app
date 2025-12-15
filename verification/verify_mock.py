from playwright.sync_api import sync_playwright
import os

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()

        url = f"file://{os.getcwd()}/verification/mock.html"
        page.goto(url)

        # Click filter
        page.click('#filter-not-started')

        # Take screenshot of the state
        page.screenshot(path="verification/verify_mock.png", full_page=True)
        print("Screenshot saved to verification/verify_mock.png")

        browser.close()

if __name__ == "__main__":
    run()
