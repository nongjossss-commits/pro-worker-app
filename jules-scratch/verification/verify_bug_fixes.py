from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Log in
    page.goto("http://localhost:8000/login")
    page.get_by_label("Email address").fill("test@example.com")
    page.get_by_label("Password").fill("admin_password_1234")
    page.get_by_role("button", name="Log in").click()

    # Go to employees page
    page.goto("http://localhost:8000/employees")

    # Verify the layout and bulk action checkbox
    page.screenshot(path="jules-scratch/verification/employees_page.png")

    # Go to an employer's edit page
    page.goto("http://localhost:8000/employers/1/edit")

    # Verify the fatal error fix and the layout
    page.screenshot(path="jules-scratch/verification/employer_edit_page.png")

    context.close()
    browser.close()

with sync_playwright() as playwright:
    run(playwright)