from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Go to login page
    page.goto("http://localhost:5173/login")

    # Fill in login form
    page.get_by_label("Email").fill("test@example.com")
    page.get_by_label("Password").fill("admin_password_1234")
    page.get_by_role("button", name="Log in").click()

    # Wait for navigation to the dashboard
    expect(page).to_have_url("http://localhost:5173/dashboard")

    # Go to the employer creation page
    page.goto("http://localhost:5173/employers/create")

    # Click the button to open the modal
    # I need to find the correct selector for the button.
    # Looking at the code, there isn't an explicit button to open the modal,
    # it seems to be part of the address management partial.
    # Let's check the create employer view

    # For now, I'll just take a screenshot of the create page
    # and then inspect the page to find the button.
    page.screenshot(path="jules-scratch/verification/create_employer_page.png")


    # I will assume there is a button with the text 'Add Address' for now
    # I will correct this if the script fails.
    page.get_by_role("button", name="Add Address").click()

    # Wait for the modal to appear
    modal = page.locator("#addAddressModal")
    expect(modal).to_be_visible()

    # Take a screenshot of the modal
    modal.screenshot(path="jules-scratch/verification/modal_screenshot.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)