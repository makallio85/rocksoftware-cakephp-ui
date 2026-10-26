import { describe, it, expect, afterEach } from 'vitest';
import { formatDate, formatDateTime, formatNumber, formatMoney, formatPercent, formatFileSize } from '../format.js';
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

    it('writes percentages of a fraction in English', () => {
        expect(formatPercent(0.75)).toBe('75%');
        expect(formatPercent('0.0425', 1)).toBe('4.3%');
        expect(formatPercent(12.5)).toBe('1,250%');
        expect(formatPercent(-0.125, 1)).toBe('-12.5%');
        expect(formatPercent(0)).toBe('0%');
    });

    it('writes percentages of a fraction in Finnish', () => {
        document.body.innerHTML = '<script type="application/json" id="kk-ui-texts">{"numberLocale":"fi-FI"}</script>';
        resetTexts();
        expect(formatPercent(0.75)).toBe('75\u00a0%');
        expect(formatPercent('0.0425', 1)).toBe('4,3\u00a0%');
        expect(formatPercent(-0.125, 1)).toBe('\u221212,5\u00a0%');
    });

    it('shows a tiny negative that rounds to zero without a minus, in English', () => {
        expect(formatNumber(-0.004, 2)).toBe('0.00');
        expect(formatNumber(-0.4)).toBe('0');
        expect(formatNumber(-0)).toBe('0');
        expect(formatNumber('-0.006', 2)).toBe('-0.01');
        expect(formatMoney('-0.001')).toBe('€0.00');
        expect(formatMoney(-0.4, 'EUR', 0)).toBe('€0');
        expect(formatMoney(-0.4, 'JPY')).toBe('¥0');
        expect(formatPercent(-0.0004, 1)).toBe('0.0%');
        expect(formatPercent(-0.004)).toBe('0%');
        expect(formatPercent(-0.006)).toBe('-1%');
    });

    it('shows a tiny negative that rounds to zero without a minus, in Finnish', () => {
        document.body.innerHTML = '<script type="application/json" id="kk-ui-texts">{"numberLocale":"fi-FI"}</script>';
        resetTexts();
        expect(formatNumber(-0.004, 2)).toBe('0,00');
        expect(formatNumber(-0.4)).toBe('0');
        expect(formatMoney('-0.001')).toBe('0,00\u00a0€');
        expect(formatPercent(-0.0004, 1)).toBe('0,0\u00a0%');
        expect(formatNumber(-0.006, 2)).toBe('\u22120,01');
    });

    it('shows a dash, or what it is given, for a missing percentage', () => {
        expect(formatPercent(null)).toBe('—');
        expect(formatPercent('abc')).toBe('—');
        expect(formatPercent(undefined, 0, '')).toBe('');
    });

    it('writes file sizes', () => {
        expect(formatFileSize(512)).toBe('512 B');
        expect(formatFileSize(34 * 1024)).toBe('34 kB');
        expect(formatFileSize(2.4 * 1024 * 1024)).toBe('2.4 MB');
    });
});
