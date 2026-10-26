/**
 * Dates and numbers as a person reads them, the same as the PHP helper's
 * `Ui->date()`, `Ui->dateTime()`, `Ui->number()`, `Ui->money()` and
 * `Ui->percent()`:
 *
 *     formatDate('2026-10-26')                 // '26.10.2026'
 *     formatDate('2026-10-26T22:30:00Z')       // '27.10.2026' (Helsinki)
 *     formatDateTime('2026-10-26T06:14:00Z')   // '26.10.2026 08.14'
 *     formatNumber(1842.3, 2)                  // '1 842,30' in Finnish, '1,842.30' in English
 *     formatMoney('1842.30')                   // '1 842,30 €' in Finnish, '€1,842.30' in English
 *     formatMoney(7000, 'USD', 0)
 *     formatPercent(0.75)                      // '75 %' in Finnish, '75%' in English
 *
 * A calendar day ('YYYY-MM-DD') shows as it is; a moment (a Date, an ISO
 * string, a Unix timestamp in seconds) shows in the display time zone (the
 * `displayTimezone` setting, default Europe/Helsinki). A string without an
 * offset is read as UTC, as the server's default zone reads it. Numbers use
 * the page's number locale, through the same CLDR data PHP's intl uses. A
 * missing or unreadable value shows '—', or the last argument.
 *
 * The page's time zone and locale come from the layout's `#kk-ui-texts`
 * block (`timeZone`, `numberLocale`); `options` overrides them.
 */
import { texts } from './texts.js';

export const EMPTY = '—';

const DATE_ONLY = /^(\d{4})-(\d{2})-(\d{2})$/;
const WITHOUT_OFFSET = /^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?$/;

function isMissing(value) {
    return value === null || value === undefined || value === '';
}

function timeZone(options) {
    return (options && options.timeZone) || texts().timeZone || 'Europe/Helsinki';
}

function numberLocale(options) {
    return (options && options.locale) || texts().numberLocale || 'en-US';
}

/**
 * A value as a Date, or null.
 *
 * @param {Date|string|number} value
 * @returns {Date|null}
 */
export function toMoment(value) {
    let date;
    if (value instanceof Date) {
        date = value;
    } else if (typeof value === 'number') {
        date = new Date(value * 1000);
    } else {
        const raw = String(value).trim();
        date = new Date(WITHOUT_OFFSET.test(raw) ? `${raw.replace(' ', 'T')}Z` : raw);
    }
    return Number.isNaN(date.getTime()) ? null : date;
}

/**
 * A moment's wall-clock parts in a time zone: { year, month, day, hour, minute }, as strings.
 *
 * @param {Date} date
 * @param {string} zone
 * @returns {Record<string, string>}
 */
export function zonedParts(date, zone) {
    const parts = new Intl.DateTimeFormat('en-GB', {
        timeZone: zone,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    }).formatToParts(date);
    const out = {};
    parts.forEach((part) => { out[part.type] = part.value; });
    return out;
}

/**
 * A day as DD.MM.YYYY.
 *
 * @param {Date|string|number|null|undefined} value
 * @param {string} [empty]
 * @param {{timeZone?: string}} [options]
 * @returns {string}
 */
export function formatDate(value, empty = EMPTY, options = {}) {
    if (isMissing(value)) {
        return empty;
    }
    const match = typeof value === 'string' ? DATE_ONLY.exec(value.trim()) : null;
    if (match) {
        return `${match[3]}.${match[2]}.${match[1]}`;
    }
    const date = toMoment(value);
    if (!date) {
        return empty;
    }
    const p = zonedParts(date, timeZone(options));
    return `${p.day}.${p.month}.${p.year}`;
}

/**
 * A moment as DD.MM.YYYY HH.mm in the display time zone. A calendar day
 * shows as DD.MM.YYYY.
 *
 * @param {Date|string|number|null|undefined} value
 * @param {string} [empty]
 * @param {{timeZone?: string}} [options]
 * @returns {string}
 */
export function formatDateTime(value, empty = EMPTY, options = {}) {
    if (isMissing(value)) {
        return empty;
    }
    if (typeof value === 'string' && DATE_ONLY.test(value.trim())) {
        return formatDate(value, empty, options);
    }
    const date = toMoment(value);
    if (!date) {
        return empty;
    }
    const p = zonedParts(date, timeZone(options));
    return `${p.day}.${p.month}.${p.year} ${p.hour}.${p.minute}`;
}

function toNumber(value) {
    if (isMissing(value) || typeof value === 'boolean') {
        return null;
    }
    const raw = typeof value === 'number' ? value : String(value).trim();
    if (raw === '') {
        return null;
    }
    const number = Number(raw);
    return Number.isFinite(number) ? number : null;
}

/**
 * A number in the page's locale, rounded half away from zero.
 *
 * @param {number|string|null|undefined} value a number or a decimal string ('1842.30')
 * @param {number} [decimals]
 * @param {string} [empty]
 * @param {{locale?: string}} [options]
 * @returns {string}
 */
export function formatNumber(value, decimals = 0, empty = EMPTY, options = {}) {
    const number = toNumber(value);
    if (number === null) {
        return empty;
    }
    return new Intl.NumberFormat(numberLocale(options), {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(number);
}

/**
 * An amount of money in the currency's units ('1842.30', not cents).
 *
 * @param {number|string|null|undefined} value
 * @param {string} [currency] ISO 4217
 * @param {number|null} [decimals] null: the currency's own (2 for EUR)
 * @param {string} [empty]
 * @param {{locale?: string}} [options]
 * @returns {string}
 */
export function formatMoney(value, currency = 'EUR', decimals = null, empty = EMPTY, options = {}) {
    const number = toNumber(value);
    if (number === null) {
        return empty;
    }
    const settings = { style: 'currency', currency: String(currency || 'EUR').toUpperCase() };
    if (decimals !== null && decimals !== undefined) {
        settings.minimumFractionDigits = decimals;
        settings.maximumFractionDigits = decimals;
    }
    return new Intl.NumberFormat(numberLocale(options), settings).format(number);
}

/**
 * A share as a percentage in the page's locale: '75 %' in Finnish, '75%' in
 * English, for a fraction (0.75).
 *
 * @param {number|string|null|undefined} fraction 0.75 for 75 %
 * @param {number} [decimals]
 * @param {string} [empty]
 * @param {{locale?: string}} [options]
 * @returns {string}
 */
export function formatPercent(fraction, decimals = 0, empty = EMPTY, options = {}) {
    const number = toNumber(fraction);
    if (number === null) {
        return empty;
    }
    return new Intl.NumberFormat(numberLocale(options), {
        style: 'percent',
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(number);
}

/**
 * A file size: 512 B, 34 kB, 2.4 MB (the decimal in the page's locale).
 *
 * @param {number} bytes
 * @param {{locale?: string}} [options]
 * @returns {string}
 */
export function formatFileSize(bytes, options = {}) {
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} kB`;
    }
    return `${formatNumber(bytes / (1024 * 1024), 1, EMPTY, options)} MB`;
}

export default { formatDate, formatDateTime, formatNumber, formatMoney, formatPercent, formatFileSize, toMoment, zonedParts };
