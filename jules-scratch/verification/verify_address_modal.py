import re
from playwright.sync_api import Page, expect

def test_address_modal_opens(page: Page):
    """
    This test verifies that a user can register, log in, navigate to the
    employer creation page, and open the address modal.
    """
    # 1. Arrange: Go to the registration page and create a new user.
    page.goto("http://127.0.0.1:5173/register")

    # Use a unique name to avoid conflicts on re-runs
    import time
    unique_name = f"testuser_{int(time.time())}"

    page.get_by_label("Name").fill(unique_name)
    page.get_by_label("Email Address").fill(f"{unique_name}@example.com")
    page.get_by_label("Password", exact=True).fill("password123")
    page.get_by_label("Confirm Password").fill("password123")
    page.get_by_role("button", name="Register").click()

    # Wait for navigation to the dashboard after registration.
    expect(page).to_have_url(re.compile(r".*/dashboard"))

    # 2. Act: Navigate to the employer creation page.
    page.goto("http://127.0.0.1:5173/employers/create")

    # 3. Act: Find the "Add Address" button for "ที่อยู่ตามทะเบียน" and click it.
    add_address_button = page.get_by_role("button", name="เพิ่มที่อยู่").first
    add_address_button.click()

    # 4. Assert: Confirm the modal is visible.
    # The modal has the id "addAddressModal".
    address_modal = page.locator("#addAddressModal")
    expect(address_modal).to_be_visible()

    # 5. Screenshot: Capture the final result for visual verification.
    page.screenshot(path="jules-scratch/verification/verification.png")