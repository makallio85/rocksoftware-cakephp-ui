/**
 * Toasts: the one way a script reports the outcome of a call to the server,
 * as a short message in a corner (top-right on desktop, at the bottom on
 * phones) that closes itself.
 *
 *     window.appBanner.success('Saved.');
 *     window.appBanner.error('Could not send.');
 *     window.appBanner.warning('Check the address.');
 *     window.appBanner.info('Refreshing…');
 *     window.appBanner.success({ title: 'Saved.', text: 'The settings are stored.' });
 *     window.appBanner('success', 'Saved.', 0);  // kind, message, ms until it closes (0 = until closed)
 *
 * Each call returns the toast's element. Errors stay 6 s, the rest 3.5 s.
 * The text is set as text, never as markup. Loaded by the default layout when
 * the `toast` component is on; texts come from the layout's `kk-ui-texts`.
 */
(function () {
    'use strict';

    var CONTAINER_ID = 'kk-toast-container';
    var KINDS = ['success', 'danger', 'warning', 'info'];
    var LEAVE_MS = 200;
    var DEFAULTS = { toastClose: 'Close' };
    var texts = null;

    function text(key) {
        if (texts === null) {
            texts = {};
            var el = document.getElementById('kk-ui-texts');
            if (el) {
                try {
                    texts = JSON.parse(el.textContent) || {};
                } catch (e) {
                    texts = {};
                }
            }
        }

        return texts[key] || DEFAULTS[key];
    }

    function container() {
        var el = document.getElementById(CONTAINER_ID);
        if (!el) {
            el = document.createElement('div');
            el.id = CONTAINER_ID;
            document.body.appendChild(el);
        }

        return el;
    }

    function normalizeKind(kind) {
        if (kind === 'error') {
            return 'danger';
        }

        return KINDS.indexOf(kind) === -1 ? 'info' : kind;
    }

    function dismiss(el) {
        if (!el || el.hasAttribute('data-dismissed')) {
            return;
        }
        el.setAttribute('data-dismissed', '');
        el.classList.remove('kk-toast-show');
        window.setTimeout(function () { el.remove(); }, LEAVE_MS);
    }

    /**
     * @param {string} kind success | error | warning | info ('danger' is error)
     * @param {string|{title?: string, text?: string}} message
     * @param {number} [timeout] ms until it closes; 0 keeps it until closed
     * @returns {HTMLElement}
     */
    function banner(kind, message, timeout) {
        var shown = normalizeKind(kind);
        var ms = typeof timeout === 'number' ? timeout : (shown === 'danger' ? 6000 : 3500);

        var el = document.createElement('div');
        el.className = 'kk-toast kk-toast-' + shown;
        el.setAttribute('role', shown === 'danger' ? 'alert' : 'status');

        var dot = document.createElement('span');
        dot.className = 'kk-toast-dot';
        dot.setAttribute('aria-hidden', 'true');

        var body = document.createElement('div');
        body.className = 'kk-toast-message';
        if (message && typeof message === 'object') {
            if (message.title) {
                var strong = document.createElement('strong');
                strong.textContent = String(message.title);
                body.appendChild(strong);
            }
            if (message.text) {
                body.appendChild(document.createTextNode((message.title ? ' ' : '') + String(message.text)));
            }
        } else {
            body.textContent = message == null ? '' : String(message);
        }

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'kk-toast-close';
        close.setAttribute('aria-label', text('toastClose'));
        var icon = document.createElement('i');
        icon.className = 'bi bi-x-lg';
        icon.setAttribute('aria-hidden', 'true');
        close.appendChild(icon);
        close.addEventListener('click', function () { dismiss(el); });

        el.append(dot, body, close);
        container().appendChild(el);

        // A reflow between insertion and the class change lets the entrance run.
        void el.offsetWidth;
        el.classList.add('kk-toast-show');

        if (ms > 0) {
            window.setTimeout(function () { dismiss(el); }, ms);
        }

        return el;
    }

    banner.success = function (message, timeout) { return banner('success', message, timeout); };
    banner.error = function (message, timeout) { return banner('danger', message, timeout); };
    banner.warning = function (message, timeout) { return banner('warning', message, timeout); };
    banner.info = function (message, timeout) { return banner('info', message, timeout); };
    banner.dismiss = dismiss;

    window.appBanner = banner;
}());
