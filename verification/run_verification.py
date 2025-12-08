from playwright.sync_api import sync_playwright
import os

def verify_frontend():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # 1. Verify Employee Cards
        page.goto(f"file://{os.path.abspath('verification/mock_employee_cards.html')}")
        page.screenshot(path="verification/verification_cards.png")
        print("Captured verification_cards.png")

        # 2. Verify Financial Tab (Initial State)
        page.goto(f"file://{os.path.abspath('verification/mock_financial_tab.html')}")
        page.screenshot(path="verification/verification_financial_tab.png")
        print("Captured verification_financial_tab.png")

        # 3. Verify Financial Tab (Add Modal)
        page.get_by_text("Add Installment").click()
        page.wait_for_selector("#addTransactionModal.show")
        page.screenshot(path="verification/verification_financial_modal.png")
        print("Captured verification_financial_modal.png")

        # 4. Verify Document Template
        page.goto(f"file://{os.path.abspath('verification/mock_quotation.html')}")
        page.screenshot(path="verification/verification_quotation.png")
        print("Captured verification_quotation.png")

        browser.close()

if __name__ == "__main__":
    verify_frontend()
