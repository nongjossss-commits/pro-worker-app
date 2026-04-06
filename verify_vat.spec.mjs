import { test, expect } from '@playwright/test';

test('Verify Enable VAT Toggle', async ({ page }) => {
  console.log('Logging in...');
  await page.goto('http://localhost:8000/login');

  // Wait for network to be completely idle
  await page.waitForLoadState('networkidle');

  // Fill inputs
  await page.fill('input#email', 'test@example.com');
  await page.fill('input#password', 'password');
  await page.click('button[type="submit"]');

  console.log('Navigating to Production Registration...');

  // Ignore checking dashboard, just go directly
  await page.goto('http://localhost:8000/production/registration/operations');
  await page.waitForLoadState('networkidle');

  console.log('Wait for table row to appear...');
  const orderId = await page.evaluate(() => {
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
      console.log('No order row found! Screenshotting...');
      await page.waitForTimeout(5000);
      await page.screenshot({ path: '/home/jules/verification/no_order_row.png', fullPage: true });
      return;
  }

  await page.waitForTimeout(2000);

  console.log('Clicking financial tab button...');
  await page.evaluate((id) => {
      const btn = document.querySelector(`#collapse-${id} button[onclick*="openFinancialModal"]`);
      if (btn) btn.click();
  }, orderId);

  await page.waitForSelector('#financialModal.show', { timeout: 10000 });
  await page.waitForTimeout(2000);

  console.log('Taking screenshot of VAT toggle visible...');
  await page.screenshot({ path: '/home/jules/verification/vat_toggle_visible.png', fullPage: true });

  console.log('Toggling VAT off...');
  await page.evaluate(() => {
      const toggle = document.querySelector('input[x-model="vatEnabled"]');
      if (toggle) {
          toggle.click();
          toggle.dispatchEvent(new Event('change'));
      }
  });

  await page.waitForTimeout(1000);
  console.log('Taking screenshot of VAT toggled off...');
  await page.screenshot({ path: '/home/jules/verification/vat_toggle_off.png', fullPage: true });
});
