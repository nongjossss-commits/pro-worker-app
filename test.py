import asyncio
from playwright.async_api import async_playwright

async def run():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        page = await browser.new_page()

        await page.goto('http://localhost:8000/login')
        await page.fill('input[type="email"]', 'test@example.com')
        await page.fill('input[type="password"]', 'admin_password_1234')
        await page.click('button[type="submit"]')
        await page.wait_for_timeout(2000)

        await page.goto('http://localhost:8000/finance?tab=wht')
        await page.wait_for_timeout(2000)

        await page.screenshot(path='/home/jules/verification/wht_tab_debug.png', full_page=True)

        await page.goto('http://localhost:8000/finance?tab=expenses')
        await page.wait_for_timeout(2000)

        await page.screenshot(path='/home/jules/verification/expenses_tab_debug.png', full_page=True)

        await browser.close()

asyncio.run(run())
