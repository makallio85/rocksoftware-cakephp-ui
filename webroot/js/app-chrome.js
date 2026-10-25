/**
 * App chrome behaviour: the main-menu pin, the phone accordion and the desktop
 * flyout position. Plain JavaScript; the drawer itself is a Bootstrap offcanvas.
 *
 * The pin is remembered in this browser only (localStorage), under keys that
 * start with the drawer's `data-storage-key-prefix`. Docking is a desktop
 * affordance: below 992px the drawer stays an overlay whatever the pin. The
 * pin's labels come from its `data-label-pin` and `data-label-unpin`.
 */
(function () {
    'use strict';

    var DESKTOP = 992;

    var sidebar = document.getElementById('mainMenu');
    var pinBtn = document.getElementById('kkMenuPin');
    if (!sidebar) {
        return;
    }

    var prefix = sidebar.getAttribute('data-storage-key-prefix') || 'ui';
    var PIN_KEY = prefix + '.menuPinned';
    var COLLAPSED_KEY = prefix + '.menuCollapsed';

    function read(key, fallback) {
        try {
            var raw = window.localStorage.getItem(key);
            return raw === null ? fallback : JSON.parse(raw);
        } catch (e) {
            return fallback;
        }
    }

    function write(key, value) {
        try {
            window.localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            // Storage refused (private window, blocked site data): the choice
            // still applies to this page.
        }
    }

    // ---- pin -------------------------------------------------------------
    var pinned = read(PIN_KEY, false) === true;

    function applyPin() {
        document.body.classList.toggle('kk-menu-pinned', pinned);
        if (!pinBtn) {
            return;
        }
        pinBtn.setAttribute('aria-pressed', pinned ? 'true' : 'false');
        pinBtn.title = pinned
            ? (pinBtn.getAttribute('data-label-unpin') || 'Unpin menu')
            : (pinBtn.getAttribute('data-label-pin') || 'Pin menu');
        pinBtn.setAttribute('aria-label', pinBtn.title);
        var icon = pinBtn.querySelector('i');
        if (icon) {
            icon.className = pinned ? 'bi bi-pin-angle-fill' : 'bi bi-pin-angle';
        }
    }

    if (pinBtn) {
        pinBtn.addEventListener('click', function () {
            pinned = !pinned;
            write(PIN_KEY, pinned);
            applyPin();
            // Drop any overlay state so the docked style takes over cleanly,
            // or so un-pinning closes the drawer.
            if (window.bootstrap && window.bootstrap.Offcanvas) {
                var instance = window.bootstrap.Offcanvas.getInstance(sidebar);
                if (instance) {
                    instance.hide();
                }
            }
        });
    }
    applyPin();

    // ---- groups ----------------------------------------------------------
    var collapsed = read(COLLAPSED_KEY, {});
    if (typeof collapsed !== 'object' || collapsed === null) {
        collapsed = {};
    }

    Array.prototype.forEach.call(sidebar.querySelectorAll('.kk-group'), function (group) {
        var button = group.querySelector('.group-btn');
        var title = group.querySelector('.group-title');
        var key = title ? title.textContent.trim() : '';
        var sub = group.querySelector('.sub');

        if (collapsed[key]) {
            group.classList.add('collapsed');
        }

        if (button) {
            // Phones: tap to expand or collapse. Desktop: the flyout opens on
            // hover, so the click only blurs the button to let it close again.
            button.addEventListener('click', function () {
                if (window.innerWidth < DESKTOP) {
                    var isCollapsed = group.classList.toggle('collapsed');
                    collapsed[key] = isCollapsed;
                    write(COLLAPSED_KEY, collapsed);
                }
                button.blur();
            });
        }

        // Desktop: keep a flyout inside the viewport.
        group.addEventListener('mouseenter', function () {
            if (window.innerWidth < DESKTOP || !sub) {
                return;
            }
            sub.style.top = '';
            var rect = group.getBoundingClientRect();
            var margin = 8;
            var maxTop = window.innerHeight - sub.offsetHeight - margin;
            var top = Math.max(margin, Math.min(rect.top - 5.6, maxTop));
            sub.style.top = (top - rect.top) + 'px';
        });
    });
})();
