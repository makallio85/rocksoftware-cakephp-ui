/**
 * The components' texts in the page's language. The layout writes them as a
 * JSON block (`#kk-ui-texts`, from `UiHelper::componentTexts()`); every text
 * has its English default here, used when the block or the key is missing.
 * The block is read once, on first use, so a script loaded before it (or a
 * test that writes one) still finds it.
 */

/** @type {Record<string, string>} */
export const DEFAULTS = {
    modalMove: 'Move the window',
    modalClose: 'Close',
    listEmpty: 'No rows match the filters.',
    listAll: 'All',
    listNoneMeansAll: 'None selected = all',
    listFrom: 'From',
    listTo: 'To',
    listOrder: 'Order',
    listAscending: 'Ascending',
    listDescending: 'Descending',
    listSelectRow: 'Select row',
    listSelectAll: 'Select all',
    pagerShowing: 'Showing {start}–{end} of {count}',
    pagerPrevious: 'Previous page',
    pagerNext: 'Next page',
    pagerPages: 'Pages',
    pickerDateTime: 'Pick a date and time',
    pickerDate: 'Pick a date',
    pickerTime: 'Pick a time',
    pickerMonth: 'Pick a month',
    pickerTimeList: 'Time',
    pickerClear: 'Clear',
    fileDropText: 'Drop a file here or click to choose',
    fileDropRemove: 'Remove {name}',
    fileDropWrongType: '{name} is not a file type this field takes.',
    fileDropTooLarge: '{name} is larger than {size}.',
    locale: 'en',
    numberLocale: 'en-US',
    timeZone: 'Europe/Helsinki',
};

let cache = null;

function read() {
    if (typeof document === 'undefined') {
        return {};
    }
    const el = document.getElementById('kk-ui-texts');
    if (!el) {
        return {};
    }
    try {
        const parsed = JSON.parse(el.textContent || '{}');
        return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (e) {
        return {};
    }
}

/**
 * Every text, the page's over the defaults.
 *
 * @returns {Record<string, string>}
 */
export function texts() {
    if (cache === null) {
        const lang = typeof document !== 'undefined' && document.documentElement.lang;
        cache = { ...DEFAULTS, ...(lang ? { locale: lang } : {}), ...read() };
    }
    return cache;
}

/**
 * One text, with `{name}` placeholders filled in.
 *
 * @param {string} key
 * @param {Record<string, string|number>} [values]
 * @returns {string}
 */
export function t(key, values = {}) {
    const text = String(texts()[key] ?? key);
    return text.replace(/\{(\w+)\}/g, (match, name) => (name in values ? String(values[name]) : match));
}

/** Forgets the texts read, so the next call reads the block again (tests). */
export function resetTexts() {
    cache = null;
}
