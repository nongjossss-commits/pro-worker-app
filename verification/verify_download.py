from playwright.sync_api import sync_playwright, expect
import os

def verify_download_center():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Load the mock HTML file
        file_path = os.path.abspath("verification/mock_download_modal.html")
        page.goto(f"file://{file_path}")

        # 1. Click "Download Employee 1" to open options modal
        page.get_by_role("button", name="Download Employee 1").click()
        expect(page.locator("#downloadOptionsModal")).to_be_visible()

        # 2. Click "Start Download"
        page.get_by_role("button", name="Start Download").click()

        # 3. Verify Toast appears
        expect(page.locator(".toast")).to_be_visible()
        expect(page.locator(".toast-body")).to_contain_text("Download prepared successfully")

        # 4. Verify Download Center opens
        expect(page.locator("#downloadCenterModal")).to_be_visible()

        # 5. Verify Task List populated (from mock data)
        # Should contain "Completed" badge
        expect(page.locator("#downloadTasksTableBody")).to_contain_text("completed")
        expect(page.locator("#downloadTasksTableBody")).to_contain_text("failed")

        # 6. Verify Auto Download Iframe created
        # Use evaluate to check if element exists in DOM, as it is hidden
        iframe_exists = page.evaluate("!!document.getElementById('autoDownloadIframe')")
        assert iframe_exists, "Auto-download iframe was not created"

        # Screenshot
        page.screenshot(path="verification/verification.png")
        print("Verification successful. Screenshot saved.")

        browser.close()

if __name__ == "__main__":
    verify_download_center()
