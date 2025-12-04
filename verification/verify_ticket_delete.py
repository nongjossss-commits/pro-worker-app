
import os
import sys
from playwright.sync_api import sync_playwright

def verify_ticket_delete(page):
    # 1. Define the HTML content directly
    html_content = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verify Ticket Delete</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <div class="container mt-5">
            <h2>My Request List</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th class="text-center">Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>101</td>
                            <td>Test Ticket Subject</td>
                            <td><span class="badge bg-warning">Pending Staff</span></td>
                            <td>02 Dec 2025 10:00</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="#" class="btn btn-sm btn-outline-primary">View Details</a>

                                    <!-- The Delete Button & Form (Mocked) -->
                                    <form action="/tickets/101" method="POST" class="d-inline">
                                        <input type="hidden" name="_token" value="mock_token">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-submit-swal"
                                            data-swal-title="Confirm Delete"
                                            data-swal-text="Are you sure you want to delete this ticket?"
                                            data-swal-icon="warning"
                                            data-swal-confirm-text="Yes, delete it">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        // Mock the SweetAlert logic (copied from the intended implementation)
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.btn-submit-swal').forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    const form = this.closest('form');
                    if (!form) return;

                    const title = this.dataset.swalTitle;
                    const text = this.dataset.swalText;
                    const icon = this.dataset.swalIcon;
                    const confirmText = this.dataset.swalConfirmText;

                    Swal.fire({
                        title: title,
                        text: text,
                        icon: icon,
                        showCancelButton: true,
                        confirmButtonColor: (icon === 'danger' || icon === 'warning') ? '#d33' : '#3085d6',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: confirmText,
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Mock submission for verification
                            console.log('Form submitted!');
                            document.body.innerHTML += '<div id="submitted-msg" class="alert alert-success mt-3">Form submitted!</div>';
                        }
                    });
                });
            });
        });
        </script>
    </body>
    </html>
    """

    # 2. Set the content
    page.set_content(html_content)

    # 3. Verify the button exists and click it
    delete_btn = page.locator('.btn-submit-swal')
    if delete_btn.is_visible():
        print("Delete button found.")
        delete_btn.click()
    else:
        print("Delete button NOT found.")

    # 4. Take a screenshot of the SweetAlert
    page.wait_for_selector('.swal2-container')
    page.screenshot(path="/home/jules/verification/ticket_delete_swal.png")
    print("Screenshot taken: ticket_delete_swal.png")

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        verify_ticket_delete(page)
        browser.close()
