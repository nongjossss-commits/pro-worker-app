from playwright.sync_api import sync_playwright
import os

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    # Load the local mock HTML file
    file_path = os.path.abspath("verification/mock_chat_settings.html")
    page.goto(f"file://{file_path}")

    # Wait for Bootstrap/JS
    page.wait_for_timeout(1000)

    # 1. Take initial screenshot (Default state)
    page.screenshot(path="verification/chat_settings_initial.png")
    print("Initial screenshot taken.")

    # 2. Open Settings Modal
    page.click("button[data-bs-target='#chatSettingsModal']")
    page.wait_for_timeout(500) # Wait for modal animation
    page.screenshot(path="verification/chat_settings_modal.png")
    print("Modal screenshot taken.")

    # 3. Select 'Preset 1'
    # Find the element with onclick="selectPreset('preset-1')"
    page.click("div[onclick=\"selectPreset('preset-1')\"]")

    # 4. Wait for modal close and update
    page.wait_for_timeout(1000)

    # 5. Take screenshot of result (Should have background)
    page.screenshot(path="verification/chat_settings_applied.png")
    print("Applied screenshot taken.")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
