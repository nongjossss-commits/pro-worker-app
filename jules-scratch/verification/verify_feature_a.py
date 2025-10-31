
import re
from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Log in
    page.goto("http://localhost:8000/login")
    page.get_by_label("Email").fill("admin@example.com")
    page.get_by_label("Password").fill("admin_password_1234")
    page.get_by_role("button", name="Log in").click()
    expect(page).to_have_url("http://localhost:8000/dashboard")

    # Go to create user page
    page.goto("http://localhost:8000/admin/users/create")

    # Check that the employer dropdown is hidden by default
    employer_dropdown = page.get_by_label("Link to Employer (Required)")
    expect(employer_dropdown).not_to_be_visible()

    # Select the 'employer' role
    page.get_by_label("Role").select_option("employer")

    # Check that the employer dropdown is now visible
    expect(employer_dropdown).to_be_visible()

    page.screenshot(path="jules-scratch/verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
