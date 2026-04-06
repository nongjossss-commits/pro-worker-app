const { test, expect } = require('@playwright/test');
const fs = require('fs');

test('Verify Enable VAT Toggle', async ({ page }) => {
  console.log('Logging in...');
  await page.goto('http://localhost:8000/login');
  await page.fill('input[name="email"]', 'admin@example.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('http://localhost:8000/dashboard');

  console.log('Navigating to Production Registration...');
  await page.goto('http://localhost:8000/production/registration/operations');
  await page.waitForLoadState('networkidle');

  console.log('Wait for table row to appear...');
  // Force click using JS directly if normal click times out
  const orderId = await page.evaluate(() => {
    // find the first accordion
    const row = document.querySelector('[data-bs-target^="#collapse-"]');
    if (row) {
        row.click();
        const target = row.getAttribute('data-bs-target');
        return target.replace('#collapse-', '');
    }
    return null;
  });

  console.log('Order ID:', orderId);
  if (!orderId) {
      console.log('No order row found! Wait 5 seconds and screenshot...');
      await page.waitForTimeout(5000);
      await page.screenshot({ path: '/home/jules/verification/no_order_row.png', fullPage: true });
      return;
  }

  // wait for collapse to open
  await page.waitForTimeout(2000);

  // click the financial tab button
  console.log('Clicking financial tab button...');
  await page.evaluate((id) => {
      const btn = document.querySelector(`#collapse-${id} button[onclick*="openFinancialModal"]`);
      if (btn) btn.click();
  }, orderId);

  // Wait for modal to appear
  await page.waitForSelector('#financialModal.show', { timeout: 10000 });
  await page.waitForTimeout(2000); // let animations finish

  console.log('Taking screenshot of VAT toggle...');
  await page.screenshot({ path: '/home/jules/verification/vat_toggle_visible.png', fullPage: true });

  console.log('Toggling VAT off...');
  // Find the toggle and click it
  await page.evaluate(() => {
      const toggle = document.querySelector('input[x-model="vatEnabled"]');
      if (toggle) {
          toggle.click();
          // dispatch change event to trigger alpine
          toggle.dispatchEvent(new Event('change'));
      }
  });

  await page.waitForTimeout(1000); // allow alpine to re-render
  console.log('Taking screenshot of VAT toggled off...');
  await page.screenshot({ path: '/home/jules/verification/vat_toggle_off.png', fullPage: true });
});
