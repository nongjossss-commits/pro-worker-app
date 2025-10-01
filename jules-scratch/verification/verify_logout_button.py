from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    try:
        # Navigate to the login page
        page.goto("http://localhost:8000/login")

        # Fill in the login form
        page.get_by_label("Email").fill("test@example.com")
        page.get_by_label("Password").fill("password")
        page.get_by_role("button", name="เข้าสู่ระบบ").click()

        # Wait for navigation to the dashboard or another authenticated page
        # The URL should change after successful login.
        expect(page).to_have_url("http://localhost:8000/employers")

        # Verify that the user's name is visible
        expect(page.get_by_text("Admin User")).to_be_visible()

        # Locate the logout link within the form
        logout_link = page.get_by_role("link", name="Logout")
        expect(logout_link).to_be_visible()

        # Take a screenshot of the sidebar area including the logout button
        sidebar = page.locator("#sidebar")
        sidebar.screenshot(path="jules-scratch/verification/logout_button_verification.png")

        print("Verification script completed successfully.")

    except Exception as e:
        print(f"An error occurred: {e}")
        page.screenshot(path="jules-scratch/verification/error.png")

    finally:
        # Clean up
        context.close()
        browser.close()

with sync_playwright() as playwright:
    run(playwright)