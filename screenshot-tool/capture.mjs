/**
 * Training-manual screenshot capture tool.
 *
 * Reads manifest.json, logs into the running Pro-Worker app, and saves
 * screenshots to public/images/manuals/{key}.png for each entry.
 *
 * Usage:
 *   1. Start the app:           php artisan serve (default :8000)
 *   2. (one-time) install:      npx playwright install chromium
 *   3. Run:                     node screenshot-tool/capture.mjs
 *
 * Environment overrides (optional):
 *   APP_URL=http://127.0.0.1:8000
 *   ADMIN_EMAIL=superadmin@proworker.com
 *   ADMIN_PASSWORD=SuperAdmin@2026
 *   ONLY=workflow/01-main-view,dashboard/01-overview   (comma list of keys to run)
 *   HEADED=1                                            (show browser instead of headless)
 *   BROWSER_CHANNEL=chrome                              (use the installed Google Chrome instead of
 *                                                        Playwright's own Chromium — used automatically
 *                                                        if `npx playwright install chromium` wasn't run)
 *
 * A screenshot is only saved when the page really rendered: HTTP errors,
 * redirects to /login or /menu-unlock, and the app's error pages are reported
 * as failures and the existing image is left untouched (older runs silently
 * saved "403 / menu disabled" and "Menu Password" screens into the manual).
 * Make sure every menu is visible and has no menu password on the machine you
 * capture from.
 */
import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname  = path.dirname(__filename);
const PROJECT    = path.resolve(__dirname, '..');

const APP_URL        = process.env.APP_URL        || 'http://127.0.0.1:8000';
const ADMIN_EMAIL    = process.env.ADMIN_EMAIL    || 'superadmin@proworker.com';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'SuperAdmin@2026';
const HEADED         = !!process.env.HEADED;
const ONLY           = (process.env.ONLY || '').split(',').filter(Boolean);
const VIEWPORT       = { width: 1440, height: 900 };

const manifest = JSON.parse(fs.readFileSync(path.join(__dirname, 'manifest.json'), 'utf8'));
const entries  = manifest.entries.filter(e => ONLY.length === 0 || ONLY.includes(e.key));

// ANSI colors for prettier console output
const c = { dim:'\x1b[2m', red:'\x1b[31m', green:'\x1b[32m', yellow:'\x1b[33m', cyan:'\x1b[36m', reset:'\x1b[0m', bold:'\x1b[1m' };
const log  = (...a) => console.log(...a);
const ok   = (msg) => log(`  ${c.green}✓${c.reset} ${msg}`);
const skip = (msg) => log(`  ${c.yellow}↷${c.reset} ${msg}`);
const fail = (msg) => log(`  ${c.red}✗${c.reset} ${msg}`);

async function login(page) {
    log(`${c.cyan}→${c.reset} Logging in as ${ADMIN_EMAIL} ...`);
    await page.goto(`${APP_URL}/login`, { waitUntil: 'networkidle' });

    // Standard Laravel-style login form
    await page.fill('input[name="email"]', ADMIN_EMAIL);
    await page.fill('input[name="password"]', ADMIN_PASSWORD);
    await Promise.all([
        page.waitForURL(u => !u.toString().includes('/login'), { timeout: 15000 }),
        page.click('button[type="submit"]'),
    ]).catch(async (e) => {
        // If we got bounced back to login, dump body for debugging
        const url = page.url();
        if (url.includes('/login')) {
            throw new Error(`Login failed — still on /login after submit. Check credentials.`);
        }
        throw e;
    });
    ok(`Logged in. Landing URL: ${page.url()}`);
}

async function resolveTabIds(page) {
    const out = { REGISTRATION_TAB_ID: null, RENEWAL_TAB_ID: null };

    // 1. Environment overrides take precedence — useful when auto-detection fails
    if (process.env.REGISTRATION_TAB_ID) out.REGISTRATION_TAB_ID = process.env.REGISTRATION_TAB_ID;
    if (process.env.RENEWAL_TAB_ID)      out.RENEWAL_TAB_ID      = process.env.RENEWAL_TAB_ID;

    // 2. Heuristic auto-detect: navigate to the index and read tab IDs from anchor href patterns
    for (const [envKey, urlPath, hrefPattern] of [
        ['REGISTRATION_TAB_ID', '/production/registration', /\/production\/registration\/(\d+)\/operations/],
        ['RENEWAL_TAB_ID',      '/production/renewal',      /\/production\/renewal\/(\d+)\/operations/],
    ]) {
        if (out[envKey]) continue; // env override already set

        try {
            await page.goto(APP_URL + urlPath, { waitUntil: 'domcontentloaded', timeout: 10000 });
            await page.waitForTimeout(1500); // allow JS/redirect to settle

            // First: check current URL after any redirect
            let url = page.url();
            let m = url.match(hrefPattern);
            if (m) { out[envKey] = m[1]; continue; }

            // Second: scan anchor hrefs on the page (tab bar links to /{type}/{id}/operations)
            const ids = await page.$$eval('a[href]', (els, pattern) => {
                const re = new RegExp(pattern);
                const found = [];
                for (const el of els) {
                    const href = el.getAttribute('href') || '';
                    const m = href.match(re);
                    if (m) found.push(m[1]);
                }
                return found;
            }, hrefPattern.source);
            if (ids.length) out[envKey] = ids[0];
        } catch (_) { /* leave null */ }
    }

    log(`${c.cyan}→${c.reset} Resolved tab IDs:`, out);
    return out;
}

async function runActions(page, actions) {
    for (const action of actions || []) {
        try {
            if (action.type === 'click') {
                const el = await page.$(action.selector);
                if (!el) { skip(`  ${action.type} ${action.selector} (not found, ignored)`); continue; }
                await el.click({ timeout: 3000 }).catch(() => {});
            } else if (action.type === 'wait') {
                await page.waitForTimeout(action.ms);
            } else if (action.type === 'waitFor') {
                await page.waitForSelector(action.selector, { timeout: 5000 }).catch(() => {});
            } else if (action.type === 'scrollTo') {
                await page.locator(action.selector).scrollIntoViewIfNeeded().catch(() => {});
            }
        } catch (e) {
            skip(`  action ${action.type} failed: ${e.message.slice(0, 60)}`);
        }
    }
}

/**
 * Why this page must NOT be saved as a manual screenshot, or null if it's fine.
 */
async function badPageReason(page, response) {
    const status = response ? response.status() : 0;
    if (status >= 400) return `HTTP ${status}`;
    const url = page.url();
    if (url.includes('/login') && !page.__guest) return 'redirected to login';
    if (url.includes('/menu-unlock/')) return 'menu is password-locked (menu-unlock page)';
    const marker = await page.evaluate(() => {
        if (document.querySelector('main.card .code')) return 'app error page';          // errors/minimal
        if (document.querySelector('.auth-card') && !window.__captureGuest) return 'login / password page'; // layouts/guest
        const text = (document.body && document.body.innerText || '').slice(0, 400).toUpperCase();
        if (/(403|404|419|500|503)/.test(text) && text.length < 200) return 'framework error page';
        return null;
    }).catch(() => null);
    return marker;
}

async function capture(page, entry, tabIds, guestPage) {
    // "guest": true entries (login / forgot-password screens) are taken
    // from a separate, logged-out browser context.
    if (entry.guest) page = guestPage;
    let url = entry.url;
    if (entry.needsTabId) {
        const key = entry.needsTabId === 'registration' ? 'REGISTRATION_TAB_ID' : 'RENEWAL_TAB_ID';
        const id  = tabIds[key];
        if (!id) { skip(`${entry.key} — no ${key} resolved`); return false; }
        url = url.replace(`{${key}}`, id);
    }

    const fullUrl = url.startsWith('http') ? url : APP_URL + url;
    const outPath = path.join(PROJECT, 'public/images/manuals', `${entry.key}.png`);
    fs.mkdirSync(path.dirname(outPath), { recursive: true });

    try {
        const response = await page.goto(fullUrl, { waitUntil: 'networkidle', timeout: 20000 }).catch(() => null);
        // Generic settle pause for Alpine/Vue/Bootstrap to finish rendering
        await page.waitForTimeout(900);
        await runActions(page, entry.actions);

        const problem = await badPageReason(page, response);
        if (problem) {
            fail(`${entry.key} — ${problem} (kept the existing image)`);
            return false;
        }

        await page.screenshot({ path: outPath, fullPage: false });
        ok(`${entry.key.padEnd(45)} → ${path.relative(PROJECT, outPath)}`);
        return true;
    } catch (e) {
        fail(`${entry.key} — ${e.message.slice(0, 80)}`);
        return false;
    }
}

(async () => {
    log(`${c.bold}Pro-Worker Training Manual — Screenshot Capture${c.reset}`);
    log(`${c.dim}App URL:${c.reset}    ${APP_URL}`);
    log(`${c.dim}Viewport:${c.reset}   ${VIEWPORT.width}×${VIEWPORT.height}`);
    log(`${c.dim}Entries:${c.reset}    ${entries.length}${ONLY.length ? ` (filtered to ${ONLY.length})` : ''}`);
    log('');

    const launch = (channel) => chromium.launch({ headless: !HEADED, ...(channel ? { channel } : {}) });
    let browser;
    try {
        browser = await launch(process.env.BROWSER_CHANNEL);
    } catch (e) {
        if (process.env.BROWSER_CHANNEL) throw e;
        log(`${c.yellow}!${c.reset} Playwright Chromium not installed — using the installed Google Chrome instead.`);
        browser = await launch('chrome');
    }
    const context = await browser.newContext({
        viewport: VIEWPORT,
        locale: 'th-TH',
        timezoneId: 'Asia/Bangkok',
    });
    const page = await context.newPage();
    const guestContext = await browser.newContext({ viewport: VIEWPORT, locale: 'th-TH', timezoneId: 'Asia/Bangkok' });
    await guestContext.addInitScript(() => { window.__captureGuest = true; });
    const guestPage = await guestContext.newPage();
    guestPage.__guest = true;
    // Guests have no user locale — pick Thai the same way a visitor would (language switcher).
    await guestPage.goto(`${APP_URL}/lang/th`, { waitUntil: 'domcontentloaded' }).catch(() => {});

    try {
        await login(page);
        const tabIds = await resolveTabIds(page);
        log('');

        let okCount = 0, failCount = 0;
        for (const entry of entries) {
            const success = await capture(page, entry, tabIds, guestPage);
            success ? okCount++ : failCount++;
        }

        log('');
        log(`${c.bold}Summary:${c.reset} ${c.green}${okCount} captured${c.reset}, ${c.red}${failCount} failed/skipped${c.reset}`);
        log(`Output: ${path.join(PROJECT, 'public/images/manuals')}`);
        log(`Reload your Training Bundle page — images replace placeholders automatically.`);
    } finally {
        await browser.close();
    }
})();
