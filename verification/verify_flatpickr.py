from playwright.sync_api import sync_playwright, expect
import os

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    file_path = os.path.abspath("verification/test_flatpickr.html")
    page.goto(f"file://{file_path}")

    # 1. Verify Static Input
    print("Verifying Static Input...")

    # Check siblings of #static-date
    # The alt input should be immediately after
    static_wrapper = page.locator("div").first
    # Print inner HTML of the wrapper
    print(f"Wrapper HTML: {static_wrapper.inner_html()}")

    # Find the visible input
    # It should have value 01/01/2024
    visible_input = page.locator("input[type='text']").first
    if visible_input.count() > 0:
        print(f"Visible input found. Value: {visible_input.input_value()}")
        expect(visible_input).to_have_value("01/01/2024")
    else:
        print("No visible text input found!")

    # 2. Verify Dynamic Input
    print("Verifying Dynamic Input...")
    page.click("#add-btn")
    page.wait_for_timeout(500)

    # Check container HTML
    container = page.locator("#dynamic-container")
    print(f"Dynamic container HTML: {container.inner_html()}")

    dynamic_visible = container.locator("input[type='text']")
    if dynamic_visible.count() > 0:
        print(f"Dynamic visible input found. Value: {dynamic_visible.input_value()}")
        expect(dynamic_visible).to_have_value("01/03/2024")

    # 3. Verify Alpine Sync
    print("Verifying Alpine Sync...")
    alpine_visible = page.locator("input[type='text']").nth(2) # 0=static, 1=dynamic, 2=alpine?
    # Alpine visible input
    # Depending on order.
    # We have static (1), dynamic (1), alpine (1).

    # Let's target by proximity to label
    alpine_label = page.locator("text=Alpine Date:")
    # The input should be near

    # Just find all text inputs
    all_text = page.locator("input[type='text']")
    print(f"Total text inputs: {all_text.count()}")
    for i in range(all_text.count()):
        print(f"Input {i}: {all_text.nth(i).input_value()}")

    # Assume last one is Alpine
    alpine_inp = all_text.last
    alpine_inp.fill("15/02/2024")
    alpine_inp.press("Enter")

    expect(page.locator("#alpine-value")).to_have_text("2024-02-15")

    page.screenshot(path="verification/verification.png")
    browser.close()

with sync_playwright() as playwright:
    run(playwright)
