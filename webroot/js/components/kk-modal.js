/**
 * <kk-modal> — the one modal. Every modal is this component; Bootstrap's
 * modal and hand-built backdrops are not used.
 *
 * Loaded by the default layout when the `modal` component is on (it needs the
 * application's Vue 3 as `window.Vue`). A Vue app registers it:
 *   app.component('kk-modal', window.KkModal)
 *
 *   <kk-modal :open="showForm" title="Add supplier" @close="closeForm">
 *       …body…
 *       <template #footer>…buttons…</template>
 *   </kk-modal>
 *
 * Props:
 *   open      whether the modal exists; also `v-model:open`
 *   title     header text (or the `title` slot)
 *   size      'sm' | 'md' (default) | 'lg' | 'xl'
 *   closable  false: no close button, and Esc and the backdrop do nothing —
 *             the footer's own buttons are the only way out
 * Slots: default (body), `title`, `tabs` (a tab strip under the header that
 * stays put while the body scrolls), `footer`.
 * Emits `close` and `update:open` (false) when the user asks to close it. The
 * host decides: the modal stays until `open` turns false.
 * Dispatches `kk-modal:shown` on the document once an opened modal's content is
 * in the page (`detail.dialog`), for code outside the host app that mounts into
 * it — the activity feeds of resources/js/app.js.
 *
 * What every modal gets:
 *   - Esc, the close button and a click on the backdrop close it. A press that
 *     starts inside the dialog and is released on the backdrop does not: the
 *     browser sends that click to the backdrop.
 *   - Desktop: a move handle in the header and a resize corner. Wheel over the
 *     backdrop scrolls the page behind (#app-scroll), as Bootstrap modals do
 *     with draggable-modal.js.
 *   - Phone and touch (below Bootstrap `lg`): full screen, footer pinned, no
 *     handle, the page behind locked.
 *   - Focus moves into the dialog and back to the opener on close; Tab stays
 *     inside. A modal opened from a modal sits above it, and Esc closes only
 *     the top one.
 *   - It may open over a Bootstrap modal: focus events stop at its root, so
 *     Bootstrap's focus trap (a focusin listener on the document) does not
 *     pull the focus back into its own dialog.
 */
window.KkModal = (function () {
    'use strict';

    // The texts in the page's language, from the layout's `kk-ui-texts`
    // block; English when the block is missing.
    var T = (function (defaults) {
        var el = document.getElementById('kk-ui-texts');
        var texts = {};
        try {
            texts = el ? JSON.parse(el.textContent) : {};
        } catch (e) {
            texts = {};
        }
        return Object.assign({}, defaults, texts);
    })({ modalMove: 'Move the window', modalClose: 'Close' });

    // Bootstrap's modal layer, so dropdown menus and the top bar stay below.
    var Z_BASE = 1055;
    var Z_STEP = 10;
    var PHONE_QUERY = '(max-width: 991.98px), (pointer: coarse)';
    var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]),'
        + ' select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
    // How close to the viewport edge the move handle may be dragged.
    var DRAG_MARGIN = 8;

    var stack = [];
    var nextId = 1;

    function isPhone() {
        return window.matchMedia ? window.matchMedia(PHONE_QUERY).matches : false;
    }

    function top() {
        return stack[stack.length - 1] || null;
    }

    document.addEventListener('keydown', function (event) {
        var modal = top();
        if (!modal) {
            return;
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            if (modal.closable) {
                modal.requestClose();
            } else {
                modal.refuse();
            }
        } else if (event.key === 'Tab') {
            modal.keepFocus(event);
        }
    });

    function syncBody() {
        document.body.classList.toggle('kk-modal-open', stack.length > 0);
    }

    return {
        name: 'KkModal',
        props: {
            open: { type: Boolean, default: false },
            title: { type: String, default: '' },
            size: { type: String, default: 'md' },
            closable: { type: Boolean, default: true },
        },
        emits: ['close', 'update:open'],
        data: function () {
            return {
                titleId: 'kk-modal-title-' + (nextId++),
                level: 0,
                phone: false,
                offset: { x: 0, y: 0 },
                refused: false,
                pressedOnBackdrop: false,
                opener: null,
            };
        },
        computed: {
            t: function () {
                return T;
            },
            dialogClass: function () {
                return 'kk-modal-' + (['sm', 'md', 'lg', 'xl'].indexOf(this.size) >= 0 ? this.size : 'md');
            },
            dialogStyle: function () {
                if (this.phone || (!this.offset.x && !this.offset.y)) {
                    return null;
                }
                return { transform: 'translate(' + this.offset.x + 'px, ' + this.offset.y + 'px)' };
            },
        },
        watch: {
            open: function (isOpen) {
                if (isOpen) {
                    this.activate();
                } else {
                    this.deactivate();
                }
            },
        },
        mounted: function () {
            if (this.open) {
                this.activate();
            }
        },
        beforeUnmount: function () {
            this.deactivate();
        },
        methods: {
            activate: function () {
                if (stack.indexOf(this) >= 0) {
                    return;
                }
                this.opener = document.activeElement;
                this.phone = isPhone();
                this.offset = { x: 0, y: 0 };
                stack.push(this);
                this.level = stack.length - 1;
                syncBody();
                window.addEventListener('resize', this.onResize);
                var self = this;
                this.$nextTick(function () {
                    self.focusInitial();
                    document.dispatchEvent(new CustomEvent('kk-modal:shown', { detail: { dialog: self.$refs.dialog } }));
                });
            },
            deactivate: function () {
                var at = stack.indexOf(this);
                if (at < 0) {
                    return;
                }
                stack.splice(at, 1);
                syncBody();
                window.removeEventListener('resize', this.onResize);
                this.stopDrag();
                var opener = this.opener;
                this.opener = null;
                if (opener && opener.focus && document.contains(opener)) {
                    opener.focus();
                }
            },
            requestClose: function () {
                this.$emit('update:open', false);
                this.$emit('close');
            },
            // The answer to Esc or a backdrop click on a modal that cannot be
            // closed that way: a short pulse, as Bootstrap's static modal does.
            refuse: function () {
                var self = this;
                self.refused = true;
                setTimeout(function () { self.refused = false; }, 200);
            },
            zIndex: function () {
                return Z_BASE + this.level * Z_STEP;
            },
            onResize: function () {
                this.phone = isPhone();
            },
            focusInitial: function () {
                var dialog = this.$refs.dialog;
                if (!dialog) {
                    return;
                }
                var target = dialog.querySelector('[autofocus]') || dialog;
                target.focus();
            },
            keepFocus: function (event) {
                var dialog = this.$refs.dialog;
                if (!dialog) {
                    return;
                }
                var items = Array.prototype.filter.call(dialog.querySelectorAll(FOCUSABLE), function (el) {
                    return el.offsetParent !== null || el === document.activeElement;
                });
                if (!items.length) {
                    event.preventDefault();
                    dialog.focus();
                    return;
                }
                var first = items[0];
                var last = items[items.length - 1];
                var inside = dialog.contains(document.activeElement);
                if (event.shiftKey && (document.activeElement === first || !inside)) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && (document.activeElement === last || !inside)) {
                    event.preventDefault();
                    first.focus();
                }
            },
            onBackdropDown: function (event) {
                this.pressedOnBackdrop = event.target === event.currentTarget;
            },
            onBackdropClick: function (event) {
                var pressed = this.pressedOnBackdrop;
                this.pressedOnBackdrop = false;
                if (event.target !== event.currentTarget || !pressed) {
                    return;
                }
                if (this.closable) {
                    this.requestClose();
                } else {
                    this.refuse();
                }
            },
            onWheel: function (event) {
                if (this.phone || (event.target.closest && event.target.closest('.kk-modal-dialog'))) {
                    return;
                }
                var scroller = document.getElementById('app-scroll');
                if (!scroller) {
                    return;
                }
                var unit = event.deltaMode === 1 ? 16 : (event.deltaMode === 2 ? scroller.clientHeight : 1);
                scroller.scrollTop += event.deltaY * unit;
                event.preventDefault();
            },
            startDrag: function (event) {
                if (event.button !== 0 || this.phone) {
                    return;
                }
                event.preventDefault();
                var handle = event.currentTarget.getBoundingClientRect();
                var start = { x: this.offset.x, y: this.offset.y };
                var baseLeft = handle.left - start.x;
                var baseTop = handle.top - start.y;
                var bounds = {
                    minX: DRAG_MARGIN - baseLeft,
                    maxX: Math.max(DRAG_MARGIN - baseLeft, window.innerWidth - DRAG_MARGIN - handle.width - baseLeft),
                    minY: DRAG_MARGIN - baseTop,
                    maxY: Math.max(DRAG_MARGIN - baseTop, window.innerHeight - DRAG_MARGIN - handle.height - baseTop),
                };
                var self = this;
                var originX = event.clientX;
                var originY = event.clientY;
                function clamp(value, lo, hi) {
                    return value < lo ? lo : (value > hi ? hi : value);
                }
                this.onDragMove = function (move) {
                    self.offset = {
                        x: clamp(start.x + move.clientX - originX, bounds.minX, bounds.maxX),
                        y: clamp(start.y + move.clientY - originY, bounds.minY, bounds.maxY),
                    };
                };
                this.onDragEnd = function () { self.stopDrag(); };
                document.addEventListener('mousemove', this.onDragMove);
                document.addEventListener('mouseup', this.onDragEnd);
            },
            stopDrag: function () {
                if (this.onDragMove) {
                    document.removeEventListener('mousemove', this.onDragMove);
                    document.removeEventListener('mouseup', this.onDragEnd);
                    this.onDragMove = null;
                    this.onDragEnd = null;
                }
            },
        },
        template: ''
            + '<teleport to="body">'
            + '<div v-if="open" class="kk-modal" :class="{ \'kk-modal-phone\': phone, \'kk-modal-refused\': refused }"'
            + ' :style="{ zIndex: zIndex() }" @mousedown="onBackdropDown" @click="onBackdropClick" @wheel="onWheel"'
            + ' @focusin.stop>'
            + '  <div ref="dialog" class="kk-modal-dialog" :class="dialogClass" :style="dialogStyle"'
            + '       role="dialog" aria-modal="true" :aria-labelledby="titleId" tabindex="-1">'
            + '    <div class="kk-modal-content">'
            + '      <div class="kk-modal-header">'
            + '        <h5 class="kk-modal-title" :id="titleId"><slot name="title">{{ title }}</slot></h5>'
            + '        <button v-if="!phone" type="button" class="kk-modal-handle" :title="t.modalMove"'
            + '                :aria-label="t.modalMove" @mousedown="startDrag"><i class="bi bi-arrows-move"></i></button>'
            + '        <button v-if="closable" type="button" class="btn-close" :aria-label="t.modalClose" @click="requestClose"></button>'
            + '      </div>'
            + '      <div v-if="$slots.tabs" class="kk-modal-tabs"><slot name="tabs"></slot></div>'
            + '      <div class="kk-modal-body"><slot></slot></div>'
            + '      <div v-if="$slots.footer" class="kk-modal-footer"><slot name="footer"></slot></div>'
            + '    </div>'
            + '  </div>'
            + '</div>'
            + '</teleport>',
    };
})();
