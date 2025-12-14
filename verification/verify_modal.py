from playwright.sync_api import sync_playwright
import os

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    # Load the static mock page
    page.goto("file://" + os.path.abspath("verification/mock_index.html"))

    # Simulate opening the modal logic by injecting the JS I wrote
    # I'll inject the logic from `edit_modal_script.blade.php` but adapted for the mock
    page.evaluate("""
        window.openEditEmployeeModal = function(employeeId) {
            const modalBody = document.getElementById('editEmployeeModalBody');
            modalBody.innerHTML = 'Loading...';

            fetch(`/employees/${employeeId}/edit`)
            .then(res => res.text())
            .then(html => {
                modalBody.innerHTML = html;
                // Execute scripts in the injected HTML (simple simulation)
                const scripts = modalBody.getElementsByTagName('script');
                for(let i=0; i<scripts.length; i++) {
                    eval(scripts[i].innerText);
                }
            });
        }
    """)

    # Trigger the modal
    page.evaluate("openEditEmployeeModal(1)")

    # Wait for content
    page.wait_for_selector("#employeeEditForm")

    # Screenshot
    page.screenshot(path="verification/modal_verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
