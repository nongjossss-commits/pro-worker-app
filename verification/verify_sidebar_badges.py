from playwright.sync_api import sync_playwright

def verify_sidebar_badges():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Mock HTML content based on the modified layouts/app.blade.php
        # We simulate the state where variables are set to 5 and 3
        html_content = """
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
            <style>
                body { background-color: #f8fafc; font-family: sans-serif; }
                #sidebar { width: 260px; background-color: #ffffff; display: flex; flex-direction: column; padding: 1rem; height: 100vh; }
                .list-group-item { border: none; padding: 0.75rem 1rem; margin-bottom: 0.5rem; border-radius: 0.5rem; font-weight: 600; color: #475569; display: block; text-decoration: none; }
                .list-group-item:hover { background-color: #f1f5f9; color: #1e293b; }
                .list-group-item.active { background-color: #FB923C; color: #ffffff; }
            </style>
        </head>
        <body>
            <div id="sidebar">
                <h5>Sidebar Mock</h5>

                <!-- Notification Item (Existing Reference) -->
                <a href="#" class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-bell-fill me-2"></i>แจ้งเตือน</span>
                        <span class="badge bg-danger rounded-pill">10</span>
                    </div>
                </a>

                <hr>

                <!-- Admin Ticket Inbox (New Feature) -->
                <a href="#" class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-inbox-fill me-2"></i>Ticket Inbox</span>
                        <span class="badge bg-danger rounded-pill">5</span>
                    </div>
                </a>

                 <!-- Employer Ticket Inbox (New Feature) -->
                <a href="#" class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-ticket-detailed-fill me-2"></i>Submit Request/Track Work</span>
                        <span class="badge bg-danger rounded-pill">3</span>
                    </div>
                </a>

            </div>
        </body>
        </html>
        """

        page.set_content(html_content)

        # Take screenshot
        page.screenshot(path="verification/sidebar_badges.png")

        browser.close()

if __name__ == "__main__":
    verify_sidebar_badges()
