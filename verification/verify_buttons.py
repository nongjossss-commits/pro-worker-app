from playwright.sync_api import sync_playwright
import os

def verify_button(page):
    # Load the mock HTML file
    file_path = os.path.abspath("verification/mock_index.html")
    page.goto(f"file://{file_path}")

    # Verify Import Button exists
    import_btn = page.get_by_role("link", name="Import", exact=True)
    if import_btn.is_visible():
        print("Import button is visible")
    else:
        print("Import button NOT visible")

    # Verify Configuration Button exists
    config_btn = page.get_by_role("button", name="Configuration / Import by Expiry")
    if config_btn.is_visible():
        print("Configuration button is visible")
    else:
         print("Configuration button NOT visible")

    # Take screenshot
    page.screenshot(path="verification/renewal_buttons.png")

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        try:
            verify_button(page)
        except Exception as e:
            print(f"Error: {e}")
        finally:
            browser.close()
