/**
 * The components page in headless Chromium, behind the test application's
 * strict Content-Security-Policy (`script-src 'self'`, `style-src 'self'`):
 * every component is used once, at desktop and at phone width, and the run
 * fails on any console error, page error or CSP violation.
 *
 *   php -S 127.0.0.1:8085 -t tests/test_app/webroot tests/test_app/webroot/index.php &
 *   node tests/browser/demo-page.mjs http://127.0.0.1:8085 [screenshot folder]
 *
 * With a folder, it saves a screenshot of each step there.
 */
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';
import path from 'node:path';

const base = (process.argv[2] || 'http://127.0.0.1:8085').replace(/\/$/, '');
const shots = process.argv[3] || '';
const sizes = {
    desktop: { viewport: { width: 1366, height: 900 }, deviceScaleFactor: 1 },
    phone: { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true },
};
const problems = [];

if (shots) {
    mkdirSync(shots, { recursive: true });
}

function expect(condition, message) {
    if (!condition) {
        throw new Error(message);
    }
}

async function shoot(page, name, label, locator) {
    if (!shots) {
        return;
    }
    const file = path.join(shots, `${name}-${label}.png`);
    // The page scrolls inside #app-scroll, so a part is shot as the
    // viewport with that part scrolled to the top.
    if (locator) {
        await locator.evaluate((el) => el.scrollIntoView({ block: 'start' }));
    }
    await page.screenshot({ path: file });
}

async function run(browser, label, size) {
    const context = await browser.newContext(size);
    const page = await context.newPage();
    page.on('console', (message) => {
        if (message.type() === 'error' || message.type() === 'warning') {
            problems.push(`${label} console ${message.type()}: ${message.text()}`);
        }
    });
    page.on('pageerror', (error) => problems.push(`${label} page error: ${error.message}`));
    // A violation is reported to the page as an event; the listener comes
    // from Playwright, not the page, so the policy does not apply to it.
    await page.exposeFunction('reportViolation', (text) => problems.push(`${label} CSP violation: ${text}`));
    await page.addInitScript(() => {
        document.addEventListener('securitypolicyviolation', (event) => {
            window.reportViolation(`${event.violatedDirective} ${event.blockedURI} ${event.sourceFile}:${event.lineNumber}`);
        });
    });
    const isPhone = label === 'phone';

    const response = await page.goto(`${base}/components`, { waitUntil: 'networkidle' });
    expect(response && response.ok(), 'the components page answers');
    expect((response.headers()['content-security-policy'] || '').includes("script-src 'self'"), 'the page sends a strict CSP');

    // The list table, server-paged, with its pager and the server's kebabs.
    const list = page.locator('#demo-list-table .kk-list-table');
    await list.waitFor();
    expect(await list.locator(isPhone ? '[data-role="cards"] .card' : 'tbody tr').count() === 4, 'the list table shows a page of rows');
    expect(await page.locator('#demo-list-table .kk-pagination-bar').isVisible(), 'the list table shows the pager');
    // Selection: tick a row, the bulk bar shows and the form gets the key.
    const firstBox = list.locator(isPhone ? '[data-role="cards"] input[type="checkbox"]' : 'tbody input[type="checkbox"]').first();
    await firstBox.check();
    await page.locator('#demo-list-table [data-role="bulk"]').waitFor();
    expect(await page.locator('input[type="hidden"][name="ids[]"][form="demo-bulk-form"]').count() === 1, 'a ticked row posts its key');
    await page.locator('#demo-list-table').scrollIntoViewIfNeeded();
    await shoot(page, '01-list-table', label, page.locator('#demo-list-table'));

    // A server filter reloads the page with its value.
    await Promise.all([
        page.waitForURL(/status=danger/),
        list.locator('[data-filter="status"] select').selectOption('danger'),
    ]);
    await page.locator('#demo-list-table .kk-list-table').waitFor();
    expect((await page.locator('#demo-list-table .kk-list-table').innerText()).includes('Failed'), 'the server filtered the rows');
    if (!isPhone) {
        // A header click reloads the page sorted the other way.
        await Promise.all([
            page.waitForURL(/direction=asc/),
            page.locator('#demo-list-table th[data-sort="sentAt"]').click(),
        ]);
        await page.locator('#demo-list-table .kk-list-table').waitFor();
    }
    await page.goto(`${base}/components`, { waitUntil: 'networkidle' });

    // The modal, with the date picker and the file drop in it.
    await page.locator('#demo-open-modal').click();
    const dialog = page.locator('.kk-modal-dialog');
    await dialog.waitFor();
    expect(await dialog.locator('.kk-modal-header button').count() === (isPhone ? 1 : 2), 'the header holds move and close only');
    await shoot(page, '02-modal', label);
    await dialog.locator('#demo-send-at').click();
    const menu = page.locator('.kk-dtp-menu');
    await menu.waitFor();
    await shoot(page, '03-date-picker-open', label);
    await menu.locator('.kk-dtp-time-item', { hasText: '14.30' }).click();
    expect(await dialog.locator('#demo-send-at').inputValue() === '27.10.2026 14.30', 'the picked time shows as DD.MM.YYYY HH.mm');
    expect(await page.locator('#demo-schedule-form input[type="hidden"][name="send_at"]').inputValue() === '2026-10-27 14:30',
        'the form posts Y-m-d H:i');
    await dialog.locator('#demo-modal-attachment').setInputFiles({
        name: 'opening-hours.pdf', mimeType: 'application/pdf', buffer: Buffer.alloc(2048, 1),
    });
    await dialog.locator('[data-role="files"]').waitFor();
    expect(await page.locator('#demo-schedule-form input[type="file"][name="attachment"]').evaluate((input) => input.files.length) === 1,
        'the form holds the dropped file');
    await shoot(page, '04-modal-file-drop', label);
    await page.keyboard.press('Escape');
    await dialog.waitFor({ state: 'detached' });

    // A kebab's button item opens the same modal.
    await page.locator('#demo-modal .kk-kebab-toggle').click();
    await page.locator('#demo-kebab-schedule').waitFor();
    await shoot(page, '02b-kebab-button-item', label, page.locator('#demo-modal'));
    await page.locator('#demo-kebab-schedule').click();
    await dialog.waitFor();
    expect((await dialog.locator('.kk-modal-title').innerText()) === 'Schedule a message', 'the kebab\'s button item opens the modal');
    await page.keyboard.press('Escape');
    await dialog.waitFor({ state: 'detached' });

    // The form's own picker and file drop, and a refused file.
    const form = page.locator('.kk-page-narrow');
    await form.locator('#logo').setInputFiles({ name: 'logo.gif', mimeType: 'image/gif', buffer: Buffer.alloc(10, 1) });
    await form.locator('[data-role="refusal"]').waitFor();
    await form.locator('#send-after').click();
    await page.locator('.kk-dtp-menu').waitFor();
    await form.scrollIntoViewIfNeeded();
    await shoot(page, '05-form-date-picker', label);
    await page.keyboard.press('Escape');

    // The formats in a script match the PHP helper's.
    const rows = await page.locator('#demo-format tbody tr').all();
    for (const row of rows) {
        const cells = await row.locator('td').allInnerTexts();
        expect(cells[1] === cells[2], `the script formats ${cells[0]} as PHP does (${cells[1]} / ${cells[2]})`);
    }

    // The Vue app built with a render function: sort, page, select.
    const app = page.locator('#demo-vue');
    await app.locator('.kk-list-table').waitFor();
    await app.locator('.kk-pagination').getByRole('button', { name: '2', exact: true }).click();
    await app.locator('.kk-pagination .active', { hasText: '2' }).waitFor();
    await app.locator(isPhone ? '[data-role="cards"] input[type="checkbox"]' : 'tbody input[type="checkbox"]').first().check();
    await app.locator('[data-role="bulk"]').waitFor();
    await app.scrollIntoViewIfNeeded();
    await shoot(page, '06-vue-app', label, app);
    await shoot(page, '07-formats', label, page.locator('#demo-format'));
    expect((await page.locator('#demo-format').innerText()).indexOf('-0') === -1, 'no format shows a minus zero');

    // Vue after parsing (a deferred script, as a Vite bundle sets it): the
    // markup still mounts.
    await page.goto(`${base}/components/late-vue`, { waitUntil: 'networkidle' });
    await page.locator('#late-vue .kk-list-table').waitFor();
    expect(await page.locator('#late-remind-at').inputValue() === '27.10.2026 08.00', 'the date picker mounted after Vue arrived');
    await page.locator('#late-kebab-note').evaluate((button) => button.click());
    await page.locator('.kk-modal-dialog #late-modal-body').waitFor();
    await shoot(page, '08-vue-after-parsing', label);
    await page.keyboard.press('Escape');

    await context.close();
}

const browser = await chromium.launch();
try {
    for (const [label, size] of Object.entries(sizes)) {
        await run(browser, label, size);
    }
} catch (error) {
    problems.push(error.message);
} finally {
    await browser.close();
}

if (problems.length) {
    console.error(`The components page failed:\n- ${problems.join('\n- ')}`);
    process.exit(1);
}
console.log('The components page works at desktop and phone width: no console error, no CSP violation.');
