/**
 * Expand toggles: a button with `data-kk-expand-toggle` and
 * `aria-controls="<id>"` shows and hides the element with that id (its
 * `hidden`), and keeps its own `aria-expanded`, label and chevron in step.
 *
 *     <button type="button" class="kk-expand-toggle" data-kk-expand-toggle
 *             aria-controls="more" aria-expanded="false">…</button>
 *
 * The `ui/expand_toggle` element writes that markup. Each click dispatches a
 * bubbling `kk:expand-toggle` CustomEvent on the button with
 * `{ expanded }`, so an application can remember the state:
 *
 *     document.addEventListener('kk:expand-toggle', (event) => save(event.target, event.detail.expanded));
 *
 * `window.KkExpandToggle.setExpanded(button, isExpanded)` opens or closes one
 * from a script without the event. The labels come from the button's
 * `data-show-label` / `data-hide-label`, else the layout's `kk-ui-texts`,
 * else English. Loaded by the default layout when the `expandToggle`
 * component is on or the element is used on the page.
 */
(function () {
    'use strict';

    var SELECTOR = '[data-kk-expand-toggle]';
    var DEFAULTS = { expandToggleShow: 'Show more', expandToggleHide: 'Show less' };
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

    function label(button, isExpanded) {
        return button.getAttribute(isExpanded ? 'data-hide-label' : 'data-show-label')
            || text(isExpanded ? 'expandToggleHide' : 'expandToggleShow');
    }

    /**
     * @param {HTMLElement} button
     * @param {boolean} isExpanded
     */
    function setExpanded(button, isExpanded) {
        button.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        var textEl = button.querySelector('[data-kk-expand-toggle-text]');
        if (textEl) {
            textEl.textContent = label(button, isExpanded);
        }
        var icon = button.querySelector('.bi');
        if (icon) {
            icon.classList.toggle('bi-chevron-down', !isExpanded);
            icon.classList.toggle('bi-chevron-up', isExpanded);
        }
        var id = button.getAttribute('aria-controls');
        var target = id ? document.getElementById(id) : null;
        if (target) {
            target.hidden = !isExpanded;
        }
    }

    function syncAll() {
        Array.prototype.forEach.call(document.querySelectorAll(SELECTOR), function (button) {
            setExpanded(button, button.getAttribute('aria-expanded') === 'true');
        });
    }

    document.addEventListener('click', function (event) {
        var button = event.target instanceof Element ? event.target.closest(SELECTOR) : null;
        if (!button || button.disabled) {
            return;
        }
        var isExpanded = button.getAttribute('aria-expanded') !== 'true';
        setExpanded(button, isExpanded);
        button.dispatchEvent(new CustomEvent('kk:expand-toggle', {
            bubbles: true,
            detail: { expanded: isExpanded },
        }));
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncAll);
    } else {
        syncAll();
    }

    window.KkExpandToggle = { setExpanded: setExpanded };
}());
