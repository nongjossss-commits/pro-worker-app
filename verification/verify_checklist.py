from playwright.sync_api import sync_playwright
import os

def verify_download_checklist():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Load the mock HTML file
        file_path = os.path.abspath("verification/mock_download_modal.html")
        page.goto(f"file://{file_path}")

        # Verify key elements
        # Check for title
        assert page.locator("text=Download Employee Files (Mock)").is_visible()

        # Check for specific Thai labels
        assert page.locator("text=รูปถ่าย (Photo)").is_visible()
        assert page.locator("text=ไฟล์แนบประกัน (Insurance)").is_visible()
        assert page.locator("text=1. พาสปอร์ต (Passport)").is_visible()
        assert page.locator("text=5. ทร. 38").is_visible()
        assert page.locator("text=12. เอกสารอื่นๆ 4").is_visible()

        # Take screenshot
        screenshot_path = os.path.abspath("verification/verification.png")
        page.screenshot(path=screenshot_path)

        print(f"Verification successful. Screenshot saved to {screenshot_path}")
        browser.close()

if __name__ == "__main__":
    verify_download_checklist()
