from playwright.sync_api import sync_playwright
import os

def verify_notification_mockup():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Load the mockup HTML file
        file_path = os.path.abspath("mockup_notifications.html")
        page.goto(f"file://{file_path}")

        # Take a screenshot
        screenshot_path = "/home/jules/verification/notification_mockup.png"
        page.screenshot(path=screenshot_path, full_page=True)

        print(f"Screenshot saved to {screenshot_path}")
        browser.close()

if __name__ == "__main__":
    verify_notification_mockup()
