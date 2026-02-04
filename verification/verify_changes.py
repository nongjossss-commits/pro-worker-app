from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context(viewport={'width': 1280, 'height': 720})
    page = context.new_page()

    # Login
    page.goto("http://localhost:8080/login")
    page.fill("input[name='email']", "test@example.com")
    page.fill("input[name='password']", "password")
    page.click("button:has-text('Log in')")
    page.wait_for_url("http://localhost:8080/dashboard")

    # Verify Workflow Dashboard
    page.goto("http://localhost:8080/workflow")
    page.wait_for_timeout(2000)

    # Verify Import Button (by icon to avoid language issues)
    # The button has <i class="bi bi-file-earmark-spreadsheet me-1"></i>
    import_icon = page.locator("i.bi-file-earmark-spreadsheet")
    import_btn = import_icon.locator("xpath=..") # Parent button

    if import_btn.count() > 0 and import_btn.first.is_visible():
        print(f"Workflow: Import button is visible. Text: '{import_btn.first.inner_text().strip()}'")
    else:
        print("Workflow: Import button is NOT visible.")

    # Verify Search Bar Centering
    # Search for any form with 'workflow' in action
    search_bar_container = page.locator("form[action*='/workflow']").first
    if search_bar_container.count() > 0:
        classes = search_bar_container.get_attribute("class")
        if "justify-content-center" in classes:
            print("Workflow: Search bar has justify-content-center.")
        else:
            print(f"Workflow: Search bar classes: {classes}")
    else:
        print("Workflow: Search form not found.")

    page.screenshot(path="verification/workflow_dashboard.png")

    # Verify Production Dashboard
    page.goto("http://localhost:8080/production")
    page.wait_for_timeout(2000)

    import_icon_prod = page.locator("i.bi-file-earmark-spreadsheet")
    import_btn_prod = import_icon_prod.locator("xpath=..")

    if import_btn_prod.count() > 0 and import_btn_prod.first.is_visible():
        print(f"Production: Import button is visible. Text: '{import_btn_prod.first.inner_text().strip()}'")
    else:
        print("Production: Import button is NOT visible.")

    # Verify Search Bar Centering (Production uses current url which is production)
    # The form action might be fully qualified url, so we just look for form[method='GET'] inside the main container
    # Or just generic form
    search_bar_prod = page.locator("form").first
    # Production form has action url()->current().
    if search_bar_prod.count() > 0:
        classes_prod = search_bar_prod.get_attribute("class")
        if "justify-content-center" in classes_prod:
            print("Production: Search bar has justify-content-center.")
        else:
            print(f"Production: Search bar classes: {classes_prod}")
    else:
        print("Production: Search form not found.")

    page.screenshot(path="verification/production_dashboard.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
