/**
 * <kk-modal> on a server-rendered page with no Vue app of its own.
 *
 *   <div data-kk-modals hidden>
 *       <kk-modal :open="open === 'reject-12'" title="Reject" @close="open = null">
 *           <form id="reject-12-form" …>…</form>
 *           <template v-slot:footer>
 *               <button type="submit" form="reject-12-form" class="btn btn-danger">Reject</button>
 *           </template>
 *       </kk-modal>
 *   </div>
 *   <button type="button" data-kk-modal-open="reject-12">Reject</button>
 *
 * Each `[data-kk-modals]` block is the template of a small Vue app whose one
 * piece of state is `open`, the name of the modal showing. Any element carrying
 * `data-kk-modal-open="<name>"` opens that modal; `data-kk-modal-close` inside
 * one closes it. The block stays `hidden`: the modals teleport to the body, and
 * before Vue mounts it is raw markup. A page whose script needs the modal's
 * content listens for `kk-modal:shown`; one that saves the form itself closes
 * the modal with `KkModalMarkup.close()` once the save is through.
 *
 * Loaded by the default layout after kk-modal.js when the `modal` component is on. Scripts that wire markup at
 * DOMContentLoaded therefore never see a block before it is mounted.
 */
(function () {
    'use strict';

    var apps = [];

    function mount(el) {
        if (el.__kkModals || !window.Vue || !window.KkModal) {
            return;
        }
        var vm = Vue.createApp({ data: function () { return { open: null }; } })
            .component('kk-modal', window.KkModal)
            .mount(el);
        el.__kkModals = vm;
        apps.push(vm);
    }

    function mountAll() {
        document.querySelectorAll('[data-kk-modals]').forEach(mount);
    }

    document.addEventListener('click', function (event) {
        if (!event.target.closest) {
            return;
        }
        var opener = event.target.closest('[data-kk-modal-open]');
        if (opener) {
            event.preventDefault();
            var name = opener.getAttribute('data-kk-modal-open');
            apps.forEach(function (vm) { vm.open = name; });
            return;
        }
        var closer = event.target.closest('[data-kk-modal-close]');
        if (closer && closer.closest('.kk-modal')) {
            event.preventDefault();
            apps.forEach(function (vm) { vm.open = null; });
        }
    });

    window.KkModalMarkup = {
        close: function () {
            apps.forEach(function (vm) { vm.open = null; });
        }
    };

    // Mounted as parsing ends, before any DOMContentLoaded listener runs. A
    // view's scripts join the layout's script block ahead of this one, so their
    // DOMContentLoaded listeners would otherwise wire the raw template — and
    // what they add to it would be compiled into every opening of the modal.
    if (document.readyState === 'loading') {
        document.addEventListener('readystatechange', function () {
            if (document.readyState !== 'loading') {
                mountAll();
            }
        });
    } else {
        mountAll();
    }
})();
