from playwright.sync_api import sync_playwright
import os

def verify_header():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Load the mock HTML file
        file_path = os.path.abspath("verification/mock_header.html")
        page.goto(f"file://{file_path}")

        # Take a screenshot
        screenshot_path = "verification/header_swapped.png"
        page.screenshot(path=screenshot_path)
        print(f"Screenshot saved to {screenshot_path}")

        browser.close()

if __name__ == "__main__":
    verify_header()
