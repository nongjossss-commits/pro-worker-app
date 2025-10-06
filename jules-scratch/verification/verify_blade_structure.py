from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Register a new user
    page.goto("http://localhost:5173/register")
    page.get_by_label("Name").fill("Test User")
    page.get_by_label("Email Address").fill("test@example.com")
    page.get_by_label("Password", exact=True).fill("password")
    page.get_by_label("Confirm Password").fill("password")
    page.get_by_role("button", name="Register").click()

    # Log in
    page.goto("http://localhost:5173/login")
    page.get_by_label("Email").fill("test@example.com")
    page.get_by_label("Password").fill("password")
    page.get_by_role("button", name="Log in").click()

    # Verify login by checking for dashboard URL
    expect(page).to_have_url("http://localhost:5173/dashboard")

    # Navigate to create employer page and take screenshot
    page.goto("http://localhost:5173/employers/create")
    page.screenshot(path="jules-scratch/verification/create_employer.png")

    # For the edit page, we need an employer.
    # Let's assume an employer with ID 1 exists for verification purposes.
    # If this fails, I'll need to create one first.
    page.goto("http://localhost:5173/employers/1/edit")
    page.screenshot(path="jules-scratch/verification/edit_employer.png")

    context.close()
    browser.close()

with sync_playwright() as playwright:
    run(playwright)