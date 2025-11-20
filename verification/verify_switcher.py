from playwright.sync_api import sync_playwright
import os

def verify_language_switcher():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Load the local HTML file
        file_path = os.path.abspath("verification/test_layout.html")
        page.goto(f"file://{file_path}")

        # Click the dropdown to show options
        page.get_by_role("button", name="English").click()

        # Verify options are visible
        page.wait_for_selector("text=ไทย (Thai)")
        page.wait_for_selector("text=English")
        page.wait_for_selector("text=中文 (Chinese)")

        # Take screenshot
        page.screenshot(path="verification/language_switcher.png")

        browser.close()

if __name__ == "__main__":
    verify_language_switcher()
