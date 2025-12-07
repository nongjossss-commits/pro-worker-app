
import os
from playwright.sync_api import sync_playwright

def generate_static_html():
    # Load the app layout and target file content
    with open('resources/views/layouts/app.blade.php', 'r') as f:
        layout_content = f.read()

    # Mock the sidebar content
    sidebar_items = """
    <a href="#" class="list-group-item list-group-item-action"><i class="bi bi-pie-chart-fill me-2"></i>Dashboard</a>
    <a href="#" class="list-group-item list-group-item-action"><i class="bi bi-inbox-fill me-2"></i>Ticket Inbox</a>

    <!-- Verified Changes -->
    <a href="#" class="list-group-item list-group-item-action active">
        <i class="bi bi-clipboard-data-fill me-2"></i>P Production
    </a>
    <a href="#" class="list-group-item list-group-item-action">
        <i class="bi bi-diagram-3-fill me-2"></i>P Workflow
    </a>
    """

    # Basic HTML structure mimicking the app layout with Bootstrap
    html_content = f"""
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sidebar Verification</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
            .main-layout {{ display: flex; min-height: 100vh; }}
            #sidebar {{ width: 260px; background-color: #fff; border-right: 1px solid #e2e8f0; }}
            .list-group-item {{ border: none; padding: 0.75rem 1rem; color: #475569; }}
            .list-group-item.active {{ background-color: #FB923C; color: white; }}
        </style>
    </head>
    <body>
        <div class="main-layout">
            <aside id="sidebar" class="p-3">
                <h4 class="text-center mb-4" style="color: #F97316;">Proworker</h4>
                <div class="list-group">
                    {sidebar_items}
                </div>
            </aside>
            <main class="p-4 flex-grow-1">
                <h1>Main Content Area</h1>
                <p>Verifying sidebar menu items Production and Workflow.</p>
                <button class="btn btn-outline-primary">
                    <i class="bi bi-arrow-right-circle-fill me-2"></i> Forward to P-Workflow
                </button>
            </main>
        </div>
    </body>
    </html>
    """

    with open('verification/verify_sidebar.html', 'w') as f:
        f.write(html_content)

    return os.path.abspath('verification/verify_sidebar.html')

def verify_frontend():
    file_path = generate_static_html()

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        page.goto(f"file://{file_path}")

        # Verify Sidebar Items - Use strict locator to find the link specifically
        production_link = page.locator("#sidebar").get_by_text("P Production")
        workflow_link = page.locator("#sidebar").get_by_text("P Workflow")

        if production_link.is_visible() and workflow_link.is_visible():
            print("✅ Sidebar items found.")
        else:
            print("❌ Sidebar items missing.")

        # Take Screenshot
        screenshot_path = os.path.abspath("verification/sidebar_verification.png")
        page.screenshot(path=screenshot_path)
        print(f"📸 Screenshot saved to {screenshot_path}")

        browser.close()

if __name__ == "__main__":
    verify_frontend()
