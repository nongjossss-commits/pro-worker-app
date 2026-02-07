from playwright.sync_api import sync_playwright, expect
import time

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Login
    print("Navigating to login...")
    page.goto("http://127.0.0.1:8000/login")
    page.fill("input[name='email']", "admin@example.com")
    page.fill("input[name='password']", "password")
    page.click("button[type='submit']")

    print("Logged in. Navigating to registration...")
    page.wait_for_load_state("networkidle")

    if "/production/registration" not in page.url:
        page.goto("http://127.0.0.1:8000/production/registration")
        page.wait_for_load_state("networkidle")

    # 1. Verify Scoreboard Count
    count_el = page.locator("#global-appointments-count")
    print(f"Appointments Count: {count_el.inner_text()}")

    page.screenshot(path="/home/jules/verification/dashboard.png")

    # 2. Open Calendar Modal
    print("Opening Calendar...")
    page.locator(".card", has_text="Appointments").click()

    # Wait for grid to be populated
    try:
        # Wait for at least one badge that is NOT d-none
        page.wait_for_selector("#calendar-grid .col .badge:not(.d-none)", timeout=5000)
    except Exception as e:
        print("Timeout waiting for appointments badge in calendar. Taking screenshot anyway.")
        page.screenshot(path="/home/jules/verification/calendar_debug.png")
        # Proceed to close and fail or try to click anyway

    page.screenshot(path="/home/jules/verification/calendar.png")

    # 3. Click on Today (or a day with appointments)
    print("Clicking a day with appointments...")

    # Try to find a badge
    badges = page.locator("#calendar-grid .badge:not(.d-none)")
    if badges.count() > 0:
        badges.first.click()

        # Wait for Day Appointments Modal
        page.wait_for_selector("#dayAppointmentsModal.show")
        page.wait_for_selector("#dayAppointmentsContent .employee-card-wrapper")

        # Take screenshot of Popup
        page.screenshot(path="/home/jules/verification/appointments_popup.png")
        print("Popup screenshot taken.")
    else:
        print("No appointments found in calendar to click.")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
