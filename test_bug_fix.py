from playwright.sync_api import sync_playwright

def test_employer_address_edit():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        page.goto("http://127.0.0.1:8000/login")
        page.fill('input[name="email"]', 'test@example.com')
        page.fill('input[name="password"]', 'admin_password_1234')
        page.click('button[type="submit"]')

        page.goto("http://127.0.0.1:8000/employers/create")

        # Inject a MutationObserver or proxy on addrProvince to track when options are added
        page.evaluate('''() => {
            window.provinceAddTrace = [];
            const select = document.getElementById('addrProvince');
            const originalAdd = select.add;
            select.add = function() {
                try { throw new Error('Trace'); }
                catch(e) { window.provinceAddTrace.push({type: 'add', count: select.options.length, trace: e.stack}); }
                return originalAdd.apply(this, arguments);
            };
        }''')

        # Click "Add Address"
        page.evaluate('''() => {
            const btns = document.querySelectorAll('button[data-bs-target="#addressModal"]');
            btns.forEach(btn => btn.removeAttribute('disabled'));
        }''')
        page.click('button[data-bs-target="#addressModal"]')
        page.wait_for_selector('#addressModal.show')
        page.wait_for_timeout(1000)

        traces = page.evaluate('window.provinceAddTrace')
        print(f"Total traces: {len(traces)}")
        for t in traces[-5:]: # Just print last 5 to avoid spam
            print(f"Type: {t['type']} Options count: {t['count']}\\n{t['trace']}\\n---")

        browser.close()

if __name__ == "__main__":
    test_employer_address_edit()
