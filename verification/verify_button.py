from playwright.sync_api import sync_playwright

def verify_button():
    html_content = """
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Notification List</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            body { background-color: #f8f9fa; padding: 20px; font-family: 'Sarabun', sans-serif; }
            .content-section { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        </style>
    </head>
    <body>
        <div class="content-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Notification List</h2>
                <div class="d-flex gap-2">
                    <!-- Manual Re-check Button -->
                    <form action="/notifications/check-expiries" method="POST">
                        <input type="hidden" name="_token" value="mock_csrf_token">
                        <button type="submit" class="btn btn-primary" title="Manually check for expiring documents immediately">
                            <i class="bi bi-arrow-clockwise"></i> Re-check Expiries
                        </button>
                    </form>

                    <a href="/admin/notification-settings" class="btn btn-outline-primary">
                        <i class="bi bi-gear-fill"></i> Notification Settings
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <p class="text-muted text-center">Mocked Notification Content</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    """

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        page.set_content(html_content)

        # Take a screenshot of the header area
        page.screenshot(path="verification/notification_button.png", full_page=False, clip={"x":0, "y":0, "width": 1000, "height": 200})

        browser.close()

if __name__ == "__main__":
    verify_button()
