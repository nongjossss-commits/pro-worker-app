from playwright.sync_api import sync_playwright

def verify_sidebar():
    html_content = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sidebar Verification</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
            :root {
                --bs-primary: #F97316;
                --bs-primary-light: #FB923C;
            }
            #sidebar {
                width: 260px;
                background-color: #ffffff;
                display: flex;
                flex-direction: column;
                height: 100vh;
                border-right: 1px solid #e2e8f0;
            }
            .list-group-item {
                border: none;
                padding: 0.75rem 1rem;
                margin-bottom: 0.5rem;
                color: #475569;
                text-decoration: none;
                display: block;
            }
            .list-group-item:hover {
                background-color: #f1f5f9;
            }
        </style>
    </head>
    <body>
        <div id="sidebar">
            <div class="list-group p-3">
                <a href="#" class="list-group-item">
                    <i class="bi bi-shield-lock-fill me-2"></i>Roles & Permissions
                </a>

                <!-- Added Item -->
                <a href="/admin/pdf-templates" class="list-group-item">
                    <i class="bi bi-file-earmark-pdf-fill me-2"></i>PDF Templates
                </a>

                <a href="#" class="list-group-item">
                    <i class="bi bi-bar-chart-steps me-2"></i>Workflow Barriers
                </a>
            </div>
        </div>
    </body>
    </html>
    """

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        page.set_content(html_content)

        # Verify the element exists
        pdf_link = page.locator('a[href="/admin/pdf-templates"]')
        if pdf_link.is_visible():
            print("PDF Templates link is visible.")
        else:
            print("PDF Templates link is NOT visible.")

        page.screenshot(path="verification/sidebar_verification.png")
        browser.close()

if __name__ == "__main__":
    verify_sidebar()
