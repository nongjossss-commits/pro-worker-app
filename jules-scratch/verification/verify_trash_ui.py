
import re
from playwright.sync_api import sync_playwright, Page, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Login
    page.goto("http://localhost:8000/login")
    page.get_by_label("Email").fill("admin@example.com")
    page.get_by_label("Password").fill("admin_password_1234")
    page.get_by_role("button", name="Log in").click()

    # Wait for navigation to dashboard
    expect(page).to_have_url(re.compile(".*dashboard"))

    # Go to trash page
    page.goto("http://localhost:8000/admin/trash")

    # Expect the header to be correct
    expect(page.get_by_role("heading", name="Central Trash")).to_be_visible()

    # Take screenshot
    page.screenshot(path="jules-scratch/verification/verification.png")

    context.close()
    browser.close()

with sync_playwright() as playwright:
    run(playwright)
