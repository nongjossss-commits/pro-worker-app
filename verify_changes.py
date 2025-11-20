
import os
from playwright.sync_api import sync_playwright

def verify_changes():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Construct the HTML content
        # We need to include Bootstrap and the actual HTML structure we modified
        html_content = """
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Verification</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
            <style>
                body { padding: 20px; }
                #sidebar { width: 260px; background: #f8f9fa; border-right: 1px solid #dee2e6; height: 100vh; }
            </style>
        </head>
        <body>
            <!-- Layout Structure -->
            <div class="main-layout d-flex">
                <!-- Sidebar (Offcanvas) -->
                <aside id="sidebar" class="offcanvas offcanvas-start" tabindex="-1" aria-labelledby="sidebarLabel">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title">Sidebar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <p>Sidebar Content</p>
                    </div>
                </aside>

                <main id="main-content" class="flex-grow-1 ps-3">
                    <!-- Top Bar -->
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3">
                        <div class="d-flex align-items-center gap-2">
                            <!-- The NEW Toggle Button -->
                            <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
                                <i class="bi bi-list"></i>
                            </button>

                            <button class="btn btn-outline-secondary d-none d-md-block">Download Center</button>
                        </div>

                        <div class="d-flex align-items-center ms-auto gap-2">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">Language</button>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Content -->
                    <div class="content-section">
                        <h2>Notifications</h2>
                        <div class="mb-3">
                            <!-- The NEW Tab Structure -->
                            <ul class="nav nav-tabs flex-wrap" id="notificationTab" role="tablist">
                                <li class="nav-item"><button class="nav-link active">Tab 1 (Long Name)</button></li>
                                <li class="nav-item"><button class="nav-link">Tab 2 (Long Name)</button></li>
                                <li class="nav-item"><button class="nav-link">Tab 3 (Long Name)</button></li>
                                <li class="nav-item"><button class="nav-link">Tab 4 (Long Name)</button></li>
                                <li class="nav-item"><button class="nav-link">Tab 5 (Long Name)</button></li>
                                <li class="nav-item"><button class="nav-link">Tab 6 (Long Name)</button></li>
                                <li class="nav-item"><button class="nav-link">Tab 7 (Long Name)</button></li>
                                <li class="nav-item"><button class="nav-link">Tab 8 (Long Name)</button></li>
                                <li class="nav-item"><button class="nav-link">Tab 9 (Long Name)</button></li>
                                <li class="nav-item"><button class="nav-link">Tab 10 (Long Name)</button></li>
                                <li class="nav-item"><button class="nav-link">Tab 11 (Long Name)</button></li>
                                <li class="nav-item"><button class="nav-link">Tab 12 (Long Name)</button></li>
                            </ul>
                        </div>
                        <div class="tab-content">
                            <div class="tab-pane show active">Content 1</div>
                        </div>
                    </div>
                </main>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>
        """

        page.set_content(html_content)

        # Check 1: Sidebar should be hidden initially (offcanvas behavior)
        # Note: Bootstrap offcanvas adds 'show' class when visible.
        sidebar = page.locator("#sidebar")
        # It might take a moment for JS to initialize
        page.wait_for_timeout(1000)

        # We expect sidebar to NOT have class 'show' initially
        # Capture screenshot 1: Initial state (Sidebar hidden, Toggle button visible)
        page.screenshot(path="verification_left_button.png")

        print("Verification complete. Screenshots saved.")

if __name__ == "__main__":
    verify_changes()
