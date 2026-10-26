import { describe, it, expect, afterEach } from 'vitest';
import { formatDate, formatDateTime, formatNumber, formatMoney, formatFileSize } from '../format.js';
import { resetTexts } from '../texts.js';

// The expected strings are the PHP helper's (tests/TestCase/View/Helper/UiHelperTest.php).
describe('format.js', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        resetTexts();
    });

    it('shows a calendar day as it is', () => {
        expect(formatDate('2026-10-26')).toBe('26.10.2026');
    });

    it('shows a moment as its day in Helsinki', () => {
        expect(formatDate('2026-10-26T22:30:00Z')).toBe('27.10.2026');
        expect(formatDate(new Date(Date.UTC(2026, 9, 26, 22, 30)))).toBe('27.10.2026');
    });

    it('shows Helsinki time with a period between hours and minutes', () => {
        expect(formatDateTime('2026-10-24T09:05:00Z')).toBe('24.10.2026 12.05');
        expect(formatDateTime('2026-10-26T06:14:00+00:00')).toBe('26.10.2026 08.14');
        expect(formatDateTime(1792995240)).toBe('26.10.2026 08.14');
        // Without an offset, read as UTC as the server reads it.
        expect(formatDateTime('2026-10-26 06:14:00')).toBe('26.10.2026 08.14');
    });

    it('takes the display time zone from the page', () => {
        document.body.innerHTML = '<script type="application/json" id="kk-ui-texts">{"timeZone":"UTC"}</script>';
        resetTexts();
        expect(formatDateTime('2026-10-26T06:14:00Z')).toBe('26.10.2026 06.14');
    });

    it('shows a dash, or what it is given, for a missing or unreadable value', () => {
        expect(formatDate(null)).toBe('—');
        expect(formatDateTime('')).toBe('—');
        expect(formatDateTime('not a date')).toBe('—');
        expect(formatNumber('abc')).toBe('—');
        expect(formatNumber(' ')).toBe('—');
        expect(formatMoney(undefined, 'EUR', null, '')).toBe('');
    });

    it('writes numbers and money in English', () => {
        expect(formatNumber('1842.3', 2)).toBe('1,842.30');
        expect(formatNumber(1842.5)).toBe('1,843');
        expect(formatMoney('1842.3')).toBe('€1,842.30');
        expect(formatMoney(7000, 'usd', 0)).toBe('$7,000');
    });

    it('writes numbers and money in Finnish', () => {
        document.body.innerHTML = '<script type="application/json" id="kk-ui-texts">{"numberLocale":"fi-FI"}</script>';
        resetTexts();
        expect(formatNumber('1842.3', 2)).toBe('1 842,30');
        expect(formatMoney('1842.30')).toBe('1 842,30 €');
        expect(formatMoney(-12.5)).toBe('−12,50 €');
    });

    it('writes file sizes', () => {
        expect(formatFileSize(512)).toBe('512 B');
        expect(formatFileSize(34 * 1024)).toBe('34 kB');
        expect(formatFileSize(2.4 * 1024 * 1024)).toBe('2.4 MB');
    });
});
