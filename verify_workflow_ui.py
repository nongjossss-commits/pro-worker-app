from playwright.sync_api import sync_playwright

def verify_workflow_ui():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context()
        page = context.new_page()

        try:
            # 1. Login
            print("Logging in as Admin...")
            page.goto("http://127.0.0.1:8080/login")
            page.fill("input[name='email']", "test@example.com")
            page.fill("input[name='password']", "password")
            page.click("button[type='submit']")

            # Wait for redirect
            page.wait_for_url("**/dashboard", timeout=10000)
            print("Login successful.")

            # 2. Go to Workflow Tab
            print("Navigating to Workflow (Notify In)...")
            page.goto("http://127.0.0.1:8080/workflow?tab=notify_in")

            # 3. Find Order
            # We need to expand the accordion
            print("Expanding order...")

            # Wait for accordion
            page.wait_for_selector(".production-order-card", timeout=5000)

            # Get the first collapse button
            collapse_btn = page.locator(".production-order-card button[data-bs-toggle='collapse']").first
            collapse_btn.click()

            # Get Order ID from ID
            order_target_id = collapse_btn.get_attribute("data-bs-target").replace("#collapse-", "")
            print(f"Targeting Order ID: {order_target_id}")

            # Wait for items to load (AJAX)
            wrapper_selector = f"#order-content-{order_target_id}"

            # Take screenshot before waiting
            page.wait_for_timeout(1000)
            page.screenshot(path="debug_accordion_open.png")
            print("Accordion open screenshot saved.")

            page.wait_for_selector(f"{wrapper_selector} .item-card-wrapper", timeout=5000)
            print("Items loaded.")

            # 4. Check Visibility of Cancelled Items
            # ... (Rest of logic)
            # Shortened for debug

            # Screenshot
            page.screenshot(path="verification_workflow_final.png")
            print("Final screenshot saved.")

        except Exception as e:
            print(f"Error: {e}")
            page.screenshot(path="error_state.png")
            print("Error screenshot saved.")

        browser.close()

if __name__ == "__main__":
    verify_workflow_ui()
