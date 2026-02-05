from playwright.sync_api import sync_playwright, expect
import os

def test_import_link_logic():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Load local HTML file
        file_path = os.path.abspath("verification/verify_import_link.html")
        page.goto(f"file://{file_path}")

        # 1. Test Default Workflow (Global Add)
        # openAddEmployeeModal(null, null, 1, 'slug', 'workflow')
        page.evaluate("openAddEmployeeModal(null, null, 1, 'slug', 'workflow')")

        # Check Href
        # Expected: /employees/import?production_id=&employer_id=&work_type_id=1&return_to=workflow
        link = page.locator("#btn-go-import")
        href = link.get_attribute("href")
        print(f"Test 1 Href: {href}")
        assert "return_to=workflow" in href
        assert "work_type_id=1" in href
        assert "production_id=" in href # empty

        # Check Flag
        flag = page.locator("#add_employee_is_pre_production")
        expect(flag).to_have_value("0")

        # 2. Test Production Context
        # openAddEmployeeModal(null, null, 2, 'slug', 'production')
        page.evaluate("openAddEmployeeModal(null, null, 2, 'slug', 'production')")

        href = link.get_attribute("href")
        print(f"Test 2 Href: {href}")
        assert "return_to=production" in href
        assert "work_type_id=2" in href

        # Check Flag
        expect(flag).to_have_value("1")

        # 3. Test Specific Order
        # openAddEmployeeModal(100, 50, 3, 'slug', 'workflow')
        page.evaluate("openAddEmployeeModal(100, 50, 3, 'slug', 'workflow')")

        href = link.get_attribute("href")
        print(f"Test 3 Href: {href}")
        assert "production_id=100" in href
        assert "employer_id=50" in href

        page.screenshot(path="verification/verification.png")
        print("Verification passed!")

        browser.close()

if __name__ == "__main__":
    test_import_link_logic()
