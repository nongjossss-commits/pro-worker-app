from playwright.sync_api import sync_playwright

def verify_ui():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # HTML Mock of the Builder Page with Settings Modal
        html_content = """
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>PDF Builder Mock</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        </head>
        <body>
            <div x-data="{
                metaData: { auto_prefix_titles: false },
                openTemplateSettings() {
                    new bootstrap.Modal(document.getElementById('templateSettingsModal')).show();
                }
            }">
                <!-- Toolbar -->
                <div class="bg-white border-bottom px-4 py-3 d-flex justify-content-between align-items-center shadow-sm">
                    <div class="d-flex align-items-center gap-3">
                        <button type="button" @click="openTemplateSettings()" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                            <i class="bi bi-sliders"></i> Settings
                        </button>
                    </div>
                </div>

                <!-- Template Settings Modal -->
                <div class="modal fade" id="templateSettingsModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-sliders me-2"></i>Template Settings</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info border-0 d-flex align-items-center mb-4">
                                    <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                                    <div>
                                        These settings apply globally to this template when generating documents.
                                    </div>
                                </div>

                                <div class="form-check form-switch p-3 border rounded bg-light mb-3">
                                    <input class="form-check-input" type="checkbox" id="autoPrefixToggle" x-model="metaData.auto_prefix_titles">
                                    <label class="form-check-label fw-bold" for="autoPrefixToggle">
                                        Auto-Prefix Titles
                                    </label>
                                    <div class="text-muted small mt-1">
                                        Automatically add "Mr./Ms." or "นาย/นาง/นางสาว" to names if missing.
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        """

        page.set_content(html_content)

        # 1. Click Settings Button
        page.get_by_role("button", name="Settings").click()

        # 2. Wait for Modal
        page.wait_for_selector("#templateSettingsModal.show", state="visible")

        # 3. Toggle Switch
        page.locator("#autoPrefixToggle").click()

        # 4. Take Screenshot
        page.screenshot(path="verification/settings_modal.png")
        print("Screenshot saved to verification/settings_modal.png")

        browser.close()

if __name__ == "__main__":
    verify_ui()
