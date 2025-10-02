import re
from playwright.sync_api import Page, expect

def test_termination_button_is_visible_for_staff(page: Page):
    """
    This test verifies that a staff user can see the new 'Terminate' button
    on an employee card, confirming the 'terminate-employees' permission is working.
    """
    # 1. Arrange: Go to the login page.
    # The APP_URL is http://127.0.0.1:8000, which is where `php artisan serve` runs.
    page.goto("http://127.0.0.1:8000/login")

    # 2. Act: Log in as the staff user.
    # The seeder creates 'staff@example.com'. The default password is 'password'.
    page.get_by_label("อีเมล").fill("staff@example.com")
    page.get_by_label("รหัสผ่าน").fill("password")
    page.get_by_role("button", name="เข้าสู่ระบบ").click()

    # 3. Assert: Ensure we are on the dashboard after login.
    expect(page).to_have_url(re.compile(r".*/dashboard"))

    # 4. Act: Navigate to the first employer's edit page to see employee cards.
    # We assume an employer with ID 1 exists from the seeders.
    page.goto("http://127.0.0.1:8000/employers/1/edit")

    # 5. Assert: Check that the "Terminate" button is visible.
    # We locate the button by its unique title attribute.
    terminate_button = page.get_by_title("แจ้งออก/เลิกจ้าง")

    # Ensure at least one such button exists and is visible on the page.
    expect(terminate_button.first).to_be_visible()

    # 6. Screenshot: Capture the result for visual confirmation.
    # We'll screenshot the whole page to provide context.
    page.screenshot(path="jules-scratch/verification/verification.png")