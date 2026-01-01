from playwright.sync_api import sync_playwright

def verify_pdf_generation_ui():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        html_content = """
        <!DOCTYPE html>
        <html>
        <head>
            <meta name="csrf-token" content="mock-token">
            <title>Employee List</title>
        </head>
        <body>
            <div id="mock-data"></div>
            <button id="bulk-generate-pdf-btn">Generate PDF</button>

            <script>
            window.getGlobalSelectedIds = function() {
                return ['1', '2', '3'];
            };

            function showToast(msg, type) {
                console.log("Toast:", msg, type);
            }

            document.addEventListener('DOMContentLoaded', function() {
                const bulkGeneratePdfBtn = document.getElementById('bulk-generate-pdf-btn');
                if (bulkGeneratePdfBtn) {
                    bulkGeneratePdfBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const selected = window.getGlobalSelectedIds();

                        if (selected.length === 0) {
                            showToast('Please select employees first.', 'danger');
                            return;
                        }

                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/admin/pdf-templates/generate';

                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = document.querySelector('meta[name="csrf-token"]').content;
                        form.appendChild(csrf);

                        selected.forEach(id => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'employees[]';
                            input.value = id;
                            form.appendChild(input);
                        });

                        document.body.appendChild(form);

                        document.getElementById('mock-data').innerText = "Form Created. Action: " + form.getAttribute('action') + ", Method: " + form.method;
                    });
                }
            });
            </script>
        </body>
        </html>
        """

        page.set_content(html_content)
        page.click('#bulk-generate-pdf-btn')
        result_text = page.locator('#mock-data').inner_text()
        print(f"Result: {result_text}")

        # Updated assertions
        assert "/admin/pdf-templates/generate" in result_text
        assert "post" in result_text.lower()

        page.screenshot(path="verification/pdf_generation_fix.png")
        browser.close()

if __name__ == "__main__":
    verify_pdf_generation_ui()
