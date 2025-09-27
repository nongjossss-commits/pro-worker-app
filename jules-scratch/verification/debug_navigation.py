from playwright.sync_api import sync_playwright, Page

def debug_navigation(page: Page):
    """
    This script navigates to a URL and takes an immediate screenshot for debugging purposes.
    """
    print("Navigating to http://localhost:5173/employers/create...")
    page.goto("http://localhost:5173/employers/create", wait_until="networkidle")

    screenshot_path = "jules-scratch/verification/debug_page.png"
    print(f"Taking debug screenshot: {screenshot_path}")
    page.screenshot(path=screenshot_path)
    print(f"Current page title: '{page.title()}'")
    print(f"Current page URL: {page.url}")

def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        try:
            debug_navigation(page)
        except Exception as e:
            print(f"An error occurred: {e}")
        finally:
            browser.close()

if __name__ == "__main__":
    main()