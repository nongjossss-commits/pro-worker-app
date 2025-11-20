
from playwright.sync_api import sync_playwright
import os

# Mock HTML that simulates the structure of layouts/app.blade.php and a content page
# This allows us to test the CSS/JS behavior of the sidebar without a running PHP server.
MOCK_HTML = """
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bs-primary: #F97316;
            --bs-primary-rgb: 249, 115, 22;
            --bs-primary-dark: #EA580C;
            --bs-primary-light: #FB923C;
            --bs-body-font-family: 'Inter', 'Sarabun', sans-serif;
            --bs-body-bg: #f8fafc;
            --bs-border-color: #e2e8f0;
        }

        body {
            font-size: 1rem;
            line-height: 1.6;
            background-color: var(--bs-body-bg);
        }

        .main-layout {
            display: flex;
            min-height: 100vh;
        }

        #sidebar {
            width: 260px;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
            --bs-offcanvas-width: 260px;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            border-right: 1px solid var(--bs-border-color);
        }

        #main-content {
            flex-grow: 1;
            padding: 2.5rem;
            overflow-y: auto;
            min-width: 0;
        }

        /* Restore padding to the new offcanvas body */
        #sidebar .offcanvas-body {
            padding: 1.5rem;
        }

        @media (max-width: 991.98px) {
            #main-content {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <div class="main-layout">
        <!-- Sidebar with offcanvas-lg -->
        <aside id="sidebar" class="offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar">
            <div class="offcanvas-header d-lg-none">
                <h5 class="offcanvas-title">Proworker labour</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebar"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column p-0">
                <div class="p-3">
                    <h4>Sidebar Content</h4>
                    <ul class="list-group">
                        <li class="list-group-item active">Dashboard</li>
                        <li class="list-group-item">Employees</li>
                        <li class="list-group-item">Employers</li>
                    </ul>
                </div>
            </div>
        </aside>

        <main id="main-content">
            <!-- Mobile Top Bar -->
            <nav class="navbar bg-white rounded shadow-sm mb-4 d-lg-none">
                <div class="container-fluid">
                    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <span class="navbar-brand">Proworker labour</span>
                </div>
            </nav>

            <div class="container">
                <h1>Main Content Area</h1>
                <p>This should be visible next to sidebar on desktop, and full width on mobile.</p>
                <div class="card p-3">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Col 1</th><th>Col 2</th><th>Col 3</th></tr></thead>
                            <tbody><tr><td>Data 1</td><td>Data 2</td><td>Data 3</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
"""

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)

        # Test Desktop
        page = browser.new_page(viewport={"width": 1280, "height": 800})
        page.set_content(MOCK_HTML)
        page.wait_for_load_state("networkidle")
        # Sidebar should be visible
        # .offcanvas-lg on desktop should behave as a normal block div if not hidden by other CSS
        # In BS5, offcanvas-lg is: display: none (hidden) by default?
        # No, "Responsive offcanvas" means hidden on small screens, visible on large.
        # Actually, the class .offcanvas-lg itself doesn't force display block on LG.
        # It just says "It is an offcanvas component below LG".
        # Above LG, it is just a div.
        # Our Custom CSS sets #sidebar { display: flex; }
        # So on Desktop: It is a flex item. Visible.

        page.screenshot(path="verification_desktop.png")
        print("Desktop screenshot taken.")

        # Test Mobile
        page_mob = browser.new_page(viewport={"width": 375, "height": 800})
        page_mob.set_content(MOCK_HTML)
        page_mob.wait_for_load_state("networkidle")

        # Sidebar should be hidden (offcanvas default state)
        # We can try to toggle it
        # page_mob.click("button.navbar-toggler")
        # page_mob.wait_for_timeout(500)

        page_mob.screenshot(path="verification_mobile.png")
        print("Mobile screenshot taken.")

        # Test Mobile Open Menu
        page_mob.click("button.navbar-toggler")
        page_mob.wait_for_timeout(1000) # Wait for animation
        page_mob.screenshot(path="verification_mobile_open.png")
        print("Mobile Open screenshot taken.")

        browser.close()

if __name__ == "__main__":
    run()
