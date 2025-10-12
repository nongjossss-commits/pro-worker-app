from playwright.sync_api import Page, expect

def test_verify_bars(page: Page):
    # Go to the employers edit page
    page.goto("http://127.0.0.1:8000/employers/1/edit")

    # Wait for the page to load
    expect(page.get_by_text("แก้ไขข้อมูลนายจ้าง")).to_be_visible()

    # Take a screenshot of the employers edit page
    page.screenshot(path="jules-scratch/verification/employers-edit.png")

    # Go to the employees index page
    page.goto("http://127.0.0.1:8000/employees")

    # Wait for the page to load
    expect(page.get_by_text("รายการข้อมูลลูกจ้างทั้งหมด")).to_be_visible()

    # Take a screenshot of the employees index page
    page.screenshot(path="jules-scratch/verification/employees-index.png")