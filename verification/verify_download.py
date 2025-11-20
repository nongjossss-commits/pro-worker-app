from playwright.sync_api import sync_playwright
import os

def verify_download_modal():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Load the mock HTML file
        file_path = os.path.abspath("verification/mock_download_modal.html")
        page.goto(f"file://{file_path}")

        # Click the button to open the modal
        page.click("button[data-bs-target='#downloadCenterModal']")

        # Wait for modal to appear and table to populate
        page.wait_for_selector("#downloadTasksTableBody tr")

        # Hover over the info icon for the failed task (ID 3)
        # The icon is the second child in the action cell (index 4) of the 3rd row (index 2)
        # But simpler selector:
        info_icon = page.locator(".bi-info-circle")
        info_icon.hover()

        # Wait for tooltip
        page.wait_for_selector(".tooltip-inner")

        # Take screenshot
        page.screenshot(path="verification/download_modal_verified.png")

        browser.close()

if __name__ == "__main__":
    verify_download_modal()
