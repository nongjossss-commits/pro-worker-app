import re
from playwright.sync_api import sync_playwright, expect

def run(playwright):
    # --- SETUP ---
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # --- SCRIPT ---

    # 1. Go to login page
    # Since I cannot run the server, I will assume it's running on localhost:8000
    # and the user will run it before I run the script.
    # The user mentioned they will handle artisan commands.
    # I will inform the user to run the servers before I execute this script.
    try:
        page.goto("http://localhost:8000/login")

        # 2. Log in
        page.get_by_label("อีเมล").fill("test@example.com")
        page.get_by_label("รหัสผ่าน").fill("password")
        page.get_by_role("button", name="เข้าสู่ระบบ").click()

        # Wait for navigation to the dashboard or employees page
        expect(page).to_url(re.compile(r".*/dashboard|.*/employees"))
        print("Login successful.")

        # 3. Navigate to employees page
        page.goto("http://localhost:8000/employees")
        expect(page).to_have_title(re.compile("Employees"))
        print("Navigated to employees page.")

        # 4. Find and click the first "แจ้งออก" (Terminate) button
        # The button has the class 'js-terminate-btn'
        first_terminate_button = page.locator(".js-terminate-btn").first
        expect(first_terminate_button).to_be_visible()
        print("Found the terminate button.")
        first_terminate_button.click()
        print("Clicked the terminate button.")

        # 5. Wait for the modal to appear and take a screenshot
        terminate_modal = page.locator("#terminateEmployeeModal")
        expect(terminate_modal).to_be_visible()

        # Check for the modal title
        modal_title = terminate_modal.locator(".modal-title")
        expect(modal_title).to_have_text("แจ้งออก / เลิกจ้าง")
        print("Modal is visible with the correct title.")

        # Take screenshot
        screenshot_path = "jules-scratch/verification/verification.png"
        page.screenshot(path=screenshot_path)
        print(f"Screenshot saved to {screenshot_path}")

    except Exception as e:
        print(f"An error occurred: {e}")
        # Take a screenshot on error to help debug
        page.screenshot(path="jules-scratch/verification/error.png")
        raise

    finally:
        # --- TEARDOWN ---
        context.close()
        browser.close()

with sync_playwright() as playwright:
    run(playwright)