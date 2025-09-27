from playwright.sync_api import sync_playwright, Page, expect

def run_verification(page: Page):
    """
    This script verifies the address modal functionality on the employer creation page.
    """
    print("Navigating to the employer creation page...")
    page.goto("http://localhost:5173/employers/create")

    # Wait for the main heading to ensure the page is loaded
    print("Waiting for page to load...")
    expect(page.get_by_role("heading", name="เพิ่มข้อมูลนายจ้าง")).to_be_visible()

    # Click the first "Add Address" button to open the modal
    print("Opening the address modal...")
    add_address_button = page.get_by_role("button", name="เพิ่มที่อยู่").first
    add_address_button.click()

    # Wait for the modal to appear and the province dropdown to be populated
    print("Waiting for modal to be ready...")
    province_dropdown = page.locator("#addrProvince")
    expect(province_dropdown).to_be_visible()
    # Wait for the options to be loaded from the JSON file
    expect(province_dropdown.locator("option")).to_have_count(78, timeout=10000)

    # --- Step 1: Select Province ---
    print("Selecting Province: กรุงเทพมหานคร")
    province_dropdown.select_option("กรุงเทพมหานคร")

    # --- Step 2: Select District ---
    district_dropdown = page.locator("#addrDistrict")
    expect(district_dropdown).to_be_enabled(timeout=5000)
    print("Selecting District: เขตดุสิต")
    district_dropdown.select_option("เขตดุสิต")

    # --- Step 3: Select Sub-district ---
    sub_district_dropdown = page.locator("#addrSubDistrict")
    expect(sub_district_dropdown).to_be_enabled(timeout=5000)
    print("Selecting Sub-district: ดุสิต")
    sub_district_dropdown.select_option("ดุสิต")

    # --- Step 4: Assertions ---
    print("Verifying auto-populated fields...")
    # Assert English translations are set
    expect(page.locator("#addrProvinceEn")).to_have_value("Bangkok")
    expect(page.locator("#addrDistrictEn")).to_have_value("Dusit")
    expect(page.locator("#addrSubDistrictEn")).to_have_value("Dusit")

    # Assert Zip Code is set
    expect(page.locator("#addrZipCode")).to_have_value("10300")
    print("All fields verified successfully.")

    # --- Step 5: Screenshot ---
    screenshot_path = "jules-scratch/verification/verification.png"
    print(f"Taking screenshot: {screenshot_path}")
    page.screenshot(path=screenshot_path)

def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        try:
            run_verification(page)
        except Exception as e:
            print(f"An error occurred: {e}")
        finally:
            browser.close()

if __name__ == "__main__":
    main()