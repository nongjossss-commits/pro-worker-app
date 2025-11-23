from playwright.sync_api import sync_playwright, expect
import os

def verify_chat_interactions():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Load the mock HTML file
        file_path = os.path.abspath("verification/mock_chat.html")
        page.goto(f"file://{file_path}")

        # 1. Verify Contact List Toggle
        print("Step 1: Opening Contact List...")
        launcher = page.locator(".chat-launcher-btn")
        launcher.click()

        contact_list = page.locator(".chat-window").filter(has_text="Contacts").first
        expect(contact_list).to_be_visible()
        print("Contact list opened.")

        # 2. Verify Contact List Minimize
        print("Step 2: Minimizing Contact List...")
        # Find the minimize button (dash icon) we added
        minimize_btn = contact_list.locator("i.bi-dash-lg").locator("..") # parent button
        minimize_btn.click()

        # Verification: contact_list should be hidden (d-none or x-show=false)
        expect(contact_list).to_be_hidden()
        print("Contact list minimized successfully.")

        # Reopen for next steps
        launcher.click()
        expect(contact_list).to_be_visible()

        # 3. Open a Chat Window
        print("Step 3: Opening Chat with 'Admin User'...")
        admin_contact = page.locator("li").filter(has_text="Admin User")
        admin_contact.click()

        # Use a more specific locator to avoid matching the contact list entry
        # The chat window header contains "Online" status which the list item might not in the same structure
        # Or simply filter by excluding "Contacts"
        chat_window = page.locator(".chat-window").filter(has_text="Online").filter(has_text="Admin User")
        expect(chat_window).to_be_visible()
        print("Chat window opened.")

        # 4. Verify Chat Window Minimize
        print("Step 4: Minimizing Chat Window...")
        chat_minimize_btn = chat_window.locator("i.bi-dash-lg").locator("..")
        chat_minimize_btn.click()

        expect(chat_window).to_be_hidden()
        print("Chat window hidden.")

        # Verify Dock Icon Appears
        dock_icon = page.locator(".rounded-circle[title='Admin User']")
        expect(dock_icon).to_be_visible()
        print("Dock icon visible.")

        # 5. Restore Chat Window
        print("Step 5: Restoring Chat Window...")
        dock_icon.click()
        expect(chat_window).to_be_visible()
        print("Chat window restored.")

        # 6. Verify Close Chat Window
        print("Step 6: Closing Chat Window...")
        chat_close_btn = chat_window.locator("i.bi-x-lg").locator("..")
        chat_close_btn.click()

        expect(chat_window).to_be_hidden()
        # Verify dock icon is ALSO gone (closed completely)
        expect(dock_icon).to_be_hidden()
        print("Chat window closed completely.")

        # Take screenshot of final state (Contact list open, no chats)
        page.screenshot(path="verification/chat_verification.png")
        print("Verification complete. Screenshot saved.")

        browser.close()

if __name__ == "__main__":
    try:
        verify_chat_interactions()
    except Exception as e:
        print(f"Verification Failed: {e}")
        exit(1)
