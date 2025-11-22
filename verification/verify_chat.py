from playwright.sync_api import sync_playwright
import os

def test_chat_widget():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Load the local HTML file
        file_path = os.path.abspath("verification/verification_chat.html")
        page.goto(f"file://{file_path}")

        # 1. Verify Widget is Visible
        page.wait_for_selector(".chat-widget")

        # 2. Verify Contacts are loaded (Mock data)
        page.wait_for_selector("text=Admin User")

        # 3. Click on a contact to open conversation
        page.click("text=Admin User")

        # 4. Verify Conversation View
        page.wait_for_selector("#chatMessagesContainer")
        page.wait_for_selector("text=Hello there!")

        # 5. Type and Send a Message
        page.fill("input[placeholder='Type a message...']", "Testing message from Playwright")
        page.click("button:has(.bi-send-fill)")

        # 6. Verify Message appears
        page.wait_for_selector("text=Testing message from Playwright")

        # 7. Screenshot
        page.screenshot(path="verification/verification.png")

        browser.close()

if __name__ == "__main__":
    test_chat_widget()
