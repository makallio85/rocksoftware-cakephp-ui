/**
 * When the markup mounts run: once the page is parsed and `window.Vue` is
 * there. No Vue is imported here; the build puts this in front of the
 * components' script, which reads Vue from `window.Vue`.
 *
 * - Vue already there as parsing ends (the plugin's own runtime, or an
 *   application's plain `<script src>` of Vue): the mounts run then, before
 *   any DOMContentLoaded listener, as before.
 * - Vue not there yet (an application's Vite bundle, a `type="module"` script
 *   that sets `window.Vue` after parsing): the mounts wait for
 *   DOMContentLoaded, then the window's `load`, then retry a few times.
 *   Should Vue never come, a warning says so; the page can still mount them
 *   itself with `window.KkUi.mount(document)` once it has set `window.Vue`.
 */

/** Retries after `load` before giving up, and the wait between them. */
export const RETRIES = 20;
export const RETRY_MS = 100;

/**
 * Whether a window has Vue 3 as `window.Vue`.
 *
 * @param {Window} win
 * @returns {boolean}
 */
export function hasVue(win) {
    return !!win.Vue && typeof win.Vue.createApp === 'function';
}

/**
 * Runs `boot` once the document is parsed and `win.Vue` is there. A page
 * `isNeeded` says has nothing to mount, once parsed, waits for nothing.
 *
 * @param {Window} win
 * @param {() => void} boot
 * @param {{retries?: number, retryMs?: number, isNeeded?: () => boolean}} [options]
 */
export function bootWhenVueReady(win, boot, options = {}) {
    const doc = win.document;
    const retries = options.retries ?? RETRIES;
    const retryMs = options.retryMs ?? RETRY_MS;
    const isNeeded = options.isNeeded || (() => true);
    let isDone = false;

    const tryBoot = () => {
        if (!isDone && !isNeeded()) {
            isDone = true;
        }
        if (isDone || !hasVue(win)) {
            return isDone;
        }
        isDone = true;
        boot();
        return true;
    };

    const retry = (left) => {
        if (tryBoot()) {
            return;
        }
        if (left <= 0) {
            win.console.warn('RockSoftware/Ui: window.Vue never came, so the data-kk-* markup is not mounted. '
                + 'Set window.Vue, then call window.KkUi.mount(document).');
            return;
        }
        win.setTimeout(() => retry(left - 1), retryMs);
    };

    const afterLoad = () => retry(retries);

    const afterContentLoaded = () => {
        if (tryBoot()) {
            return;
        }
        if (doc.readyState === 'complete') {
            afterLoad();
        } else {
            win.addEventListener('load', afterLoad, { once: true });
        }
    };

    const afterParsed = () => {
        if (tryBoot()) {
            return;
        }
        // At `interactive`, deferred and module scripts have not run yet;
        // DOMContentLoaded fires after them.
        if (doc.readyState === 'interactive') {
            doc.addEventListener('DOMContentLoaded', afterContentLoaded, { once: true });
        } else {
            afterContentLoaded();
        }
    };

    if (doc.readyState === 'loading') {
        const onChange = () => {
            if (doc.readyState !== 'loading') {
                doc.removeEventListener('readystatechange', onChange);
                afterParsed();
            }
        };
        doc.addEventListener('readystatechange', onChange);
    } else {
        afterParsed();
    }
}
