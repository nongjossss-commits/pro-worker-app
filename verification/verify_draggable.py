from playwright.sync_api import sync_playwright
import os

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    # Load the mock HTML file
    file_path = os.path.abspath("verification/verification_mock.html")
    page.goto(f"file://{file_path}")

    # Check if elements are present
    print("Checking for draggable elements...")
    notification_row = page.locator("tr[draggable='true']").first
    employer_card = page.locator("div.card[draggable='true']").first

    if notification_row.count() > 0:
        print("PASS: Notification row is draggable")
    else:
        print("FAIL: Notification row not found or not draggable")

    if employer_card.count() > 0:
        print("PASS: Employer card is draggable")
    else:
        print("FAIL: Employer card not found or not draggable")

    # Simulate a drag event (simplified verification via JS execution)
    # We trigger the ondragstart manually to see if the function is called
    print("Simulating drag start on Notification Row...")
    page.evaluate("document.querySelector('tr[draggable=\"true\"]').dispatchEvent(new DragEvent('dragstart'))")

    # Check debug output
    debug_text = page.inner_text("#debug-output")
    print(f"Debug Output: {debug_text}")

    if "Drag started: notification" in debug_text:
        print("PASS: Drag handler executed correctly for Notification")
    else:
        print("FAIL: Drag handler did not update debug output")

    # Take screenshot
    page.screenshot(path="verification/draggable_verification.png")
    browser.close()

with sync_playwright() as playwright:
    run(playwright)
