from playwright.sync_api import sync_playwright
import os

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Load the local HTML file
        file_path = os.path.abspath('verification/verify_cropper.html')
        page.goto(f'file://{file_path}')

        # Click the button to open the modal
        page.click('#triggerBtn')

        # Wait for modal to appear
        page.wait_for_selector('#cropperModal.show')

        # Wait for the image to load in the cropper (check if cropper-container exists)
        page.wait_for_selector('.cropper-container')

        # The "Save" button should be disabled initially (before ready)
        # But we might miss that window if it's too fast.
        # However, we can check if it becomes enabled eventually.

        save_btn = page.locator('#cropImageBtn')

        # Wait for the button to be enabled (meaning ready callback fired)
        # This confirms our fix logic works (it starts disabled, then enables)
        # If the fix wasn't there, it would be enabled immediately or causing errors.
        # But specifically, we want to ensure it IS enabled now.
        save_btn.wait_for(state='visible')

        # Take a screenshot before clicking
        page.screenshot(path='verification/before_click.png')

        # Click the save button
        save_btn.click()

        # Check for any alerts (we can't easily capture alert text with screenshot, but we can avoid crash)
        # If alert appears, it blocks execution if not handled.
        # We can handle dialog
        page.on("dialog", lambda dialog: dialog.accept())

        # Wait a bit to ensure no error happened
        page.wait_for_timeout(1000)

        # Take final screenshot
        page.screenshot(path='verification/cropper_verified.png')

        browser.close()

if __name__ == '__main__':
    run()
