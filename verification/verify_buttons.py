
from playwright.sync_api import sync_playwright
import os

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Load the local HTML file
        file_path = os.path.abspath("verification/mock_employee_edit.html")
        page.goto(f"file://{file_path}")

        # Take a screenshot
        page.screenshot(path="verification/verification_buttons.png")
        print("Screenshot saved to verification/verification_buttons.png")

        browser.close()

if __name__ == "__main__":
    run()
