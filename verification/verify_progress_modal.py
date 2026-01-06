from playwright.sync_api import sync_playwright

def verify_ui():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # HTML Mock of the Progress Modal UI
        html_content = """
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Progress Modal Mock</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        </head>
        <body>
            <!-- Button trigger modal -->
            <button type="button" class="btn btn-primary m-4" data-bs-toggle="modal" data-bs-target="#progressModal">
                Launch Progress Modal
            </button>

            <!-- Progress Modal -->
            <div class="modal fade show" id="progressModal" tabindex="-1" style="display: block;" role="dialog">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-body text-center p-5">
                            <div class="mb-4">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            <h5 class="modal-title fw-bold mb-3">Processing Documents...</h5>

                            <div class="progress mb-2" style="height: 20px;">
                                <div id="saveProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 45%">45%</div>
                            </div>

                            <p class="text-muted mb-0" id="saveProgressText">Processing batch 3 of 7...</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show"></div>
        </body>
        </html>
        """

        page.set_content(html_content)

        # Take Screenshot
        page.screenshot(path="verification/progress_modal.png")
        print("Screenshot saved to verification/progress_modal.png")

        browser.close()

if __name__ == "__main__":
    verify_ui()
