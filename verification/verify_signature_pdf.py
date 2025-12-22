from playwright.sync_api import sync_playwright
import base64

def test_signature_component():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # We will mock the builder UI to verify the 'Red Trash Can' and 'SweetAlert' logic
        # Since we can't run full PHP app, we create a mock HTML that resembles builder.blade.php

        mock_html = """
        <!DOCTYPE html>
        <html>
        <head>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script src="https://cdn.tailwindcss.com"></script>
            <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
        </head>
        <body class="bg-gray-200 p-10">
            <div x-data="{ items: [{type: 'db', label: 'Test Field'}, {type: 'signature', label: 'Sign Here'}] }">
                <!-- Simulate a placed item on canvas -->
                <div class="relative w-64 h-32 bg-white border shadow p-4 mb-4 group">
                    <span class="text-blue-800 font-bold">Name Field</span>

                    <!-- Controls (Hidden by default, show on hover in real app, strictly shown here for screenshot) -->
                    <div class="absolute -top-8 right-0 bg-white shadow rounded border flex gap-1 p-1 z-50">
                        <button class="p-1 hover:bg-gray-100 rounded text-gray-600">
                            <i class="bi bi-gear"></i>
                        </button>
                        <!-- THE NEW RED TRASH CAN BUTTON -->
                        <button id="delete-btn" onclick="confirmDelete()" class="p-1 hover:bg-red-50 rounded text-red-600 bg-white border border-red-200 shadow-sm">
                            <i class="bi bi-trash-fill text-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            <script>
                function confirmDelete() {
                    Swal.fire({
                        title: 'Remove Field?',
                        text: 'Are you sure you want to remove this field?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Yes, remove it!'
                    });
                }
            </script>
        </body>
        </html>
        """

        page.set_content(mock_html)

        # 1. Take Screenshot of the UI with the Red Trash Can
        page.screenshot(path="verification/1_ui_trash_can.png")

        # 2. Click the delete button to trigger SweetAlert
        page.click("#delete-btn")

        # Wait for SweetAlert
        page.wait_for_selector(".swal2-popup")

        # 3. Take Screenshot of the SweetAlert
        page.screenshot(path="verification/2_sweetalert.png")

        browser.close()

if __name__ == "__main__":
    test_signature_component()
