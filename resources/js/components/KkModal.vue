<!--
<kk-modal>: the one modal. Bootstrap's modal and hand-built backdrops are not
used. The header holds the title, the move handle and the close button, and
nothing else. Styles: webroot/css/kk-modal.css.

    <kk-modal :open="isFormOpen" title="Add supplier" @close="isFormOpen = false">
        …body…
        <template #footer>…buttons…</template>
    </kk-modal>

Props:
  open      whether the modal exists; also `v-model:open`
  title     header text (or the `title` slot)
  size      'sm' | 'md' (default) | 'lg' | 'xl'
  closable  false: no close button, and Esc and the backdrop do nothing; the
            footer's own buttons are the only way out
Slots: default (body), `title`, `tabs` (a tab strip under the header that stays
put while the body scrolls), `footer`.
Emits `close` and `update:open` (false) when the user asks to close it. The host
decides: the modal stays until `open` turns false.
Dispatches `kk-modal:shown` on the document once an opened modal's content is in
the page (`detail.dialog`), for code outside the host app that mounts into it.

Every modal gets:
  - Esc, the close button and a click on the backdrop close it. A press that
    starts inside the dialog and is released on the backdrop does not: the
    browser sends that click to the backdrop.
  - Desktop: a move handle in the header and a resize corner. Wheel over the
    backdrop scrolls the page behind (#app-scroll).
  - Phone and touch (below Bootstrap `lg`): full screen, footer pinned, no
    handle, the page behind locked.
  - Focus moves into the dialog and back to the opener on close; Tab stays
    inside. A modal opened from a modal sits above it, and Esc closes only the
    top one.
-->
<template>
    <teleport to="body">
        <div v-if="open" class="kk-modal" :class="{ 'kk-modal-phone': isPhone, 'kk-modal-refused': isRefused }"
             :style="{ zIndex: zIndex }" @mousedown="onBackdropDown" @click="onBackdropClick" @wheel="onWheel"
             @focusin.stop>
            <div ref="dialog" class="kk-modal-dialog" :class="dialogClass" :style="dialogStyle"
                 role="dialog" aria-modal="true" :aria-labelledby="titleId" tabindex="-1">
                <div class="kk-modal-content">
                    <div class="kk-modal-header">
                        <h5 :id="titleId" class="kk-modal-title"><slot name="title">{{ title }}</slot></h5>
                        <button v-if="!isPhone" type="button" class="kk-modal-handle" :title="t('modalMove')"
                                :aria-label="t('modalMove')" @mousedown="startDrag"><i class="bi bi-arrows-move"></i></button>
                        <button v-if="closable" type="button" class="btn-close" :aria-label="t('modalClose')" @click="requestClose"></button>
                    </div>
                    <div v-if="$slots.tabs" class="kk-modal-tabs"><slot name="tabs"></slot></div>
                    <div class="kk-modal-body"><slot></slot></div>
                    <div v-if="$slots.footer" class="kk-modal-footer"><slot name="footer"></slot></div>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script>
import { t } from '../texts.js';

// Bootstrap's modal layer, so dropdown menus and the top bar stay below.
const Z_BASE = 1055;
const Z_STEP = 10;
const PHONE_QUERY = '(max-width: 991.98px), (pointer: coarse)';
const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]),'
    + ' select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
// How close to the viewport edge the move handle may be dragged.
const DRAG_MARGIN = 8;
const REFUSE_MS = 200;

// Open modals, the top one last. Shared by every instance so Esc and Tab reach
// only the top one.
const stack = [];
let nextId = 1;

function matchesPhone() {
    return window.matchMedia ? window.matchMedia(PHONE_QUERY).matches : false;
}

function topModal() {
    return stack[stack.length - 1] || null;
}

function syncBody() {
    document.body.classList.toggle('kk-modal-open', stack.length > 0);
}

if (typeof document !== 'undefined') {
    document.addEventListener('keydown', (event) => {
        const modal = topModal();
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
}

export default {
    name: 'KkModal',
    props: {
        open: { type: Boolean, default: false },
        title: { type: String, default: '' },
        size: { type: String, default: 'md' },
        closable: { type: Boolean, default: true },
    },
    emits: ['close', 'update:open'],
    data() {
        return {
            titleId: `kk-modal-title-${nextId++}`,
            level: 0,
            isPhone: false,
            offset: { x: 0, y: 0 },
            isRefused: false,
            isPressedOnBackdrop: false,
            opener: null,
        };
    },
    computed: {
        dialogClass() {
            return `kk-modal-${['sm', 'md', 'lg', 'xl'].includes(this.size) ? this.size : 'md'}`;
        },
        dialogStyle() {
            if (this.isPhone || (!this.offset.x && !this.offset.y)) {
                return null;
            }
            return { transform: `translate(${this.offset.x}px, ${this.offset.y}px)` };
        },
        zIndex() {
            return Z_BASE + this.level * Z_STEP;
        },
    },
    watch: {
        open(isOpen) {
            if (isOpen) {
                this.activate();
            } else {
                this.deactivate();
            }
        },
    },
    mounted() {
        if (this.open) {
            this.activate();
        }
    },
    beforeUnmount() {
        this.deactivate();
    },
    methods: {
        t,
        activate() {
            if (stack.includes(this)) {
                return;
            }
            this.opener = document.activeElement;
            this.isPhone = matchesPhone();
            this.offset = { x: 0, y: 0 };
            stack.push(this);
            this.level = stack.length - 1;
            syncBody();
            window.addEventListener('resize', this.onResize);
            this.$nextTick(() => {
                this.focusInitial();
                document.dispatchEvent(new CustomEvent('kk-modal:shown', { detail: { dialog: this.$refs.dialog } }));
            });
        },
        deactivate() {
            const at = stack.indexOf(this);
            if (at < 0) {
                return;
            }
            stack.splice(at, 1);
            syncBody();
            window.removeEventListener('resize', this.onResize);
            this.stopDrag();
            const opener = this.opener;
            this.opener = null;
            if (opener && opener.focus && document.contains(opener)) {
                opener.focus();
            }
        },
        requestClose() {
            this.$emit('update:open', false);
            this.$emit('close');
        },
        // The answer to Esc or a backdrop click on a modal that cannot be
        // closed that way: a short pulse, as Bootstrap's static modal does.
        refuse() {
            this.isRefused = true;
            setTimeout(() => { this.isRefused = false; }, REFUSE_MS);
        },
        onResize() {
            this.isPhone = matchesPhone();
        },
        focusInitial() {
            const dialog = this.$refs.dialog;
            if (!dialog) {
                return;
            }
            (dialog.querySelector('[autofocus]') || dialog).focus();
        },
        keepFocus(event) {
            const dialog = this.$refs.dialog;
            if (!dialog) {
                return;
            }
            const items = Array.from(dialog.querySelectorAll(FOCUSABLE))
                .filter((el) => el.offsetParent !== null || el === document.activeElement);
            if (!items.length) {
                event.preventDefault();
                dialog.focus();
                return;
            }
            const first = items[0];
            const last = items[items.length - 1];
            const isInside = dialog.contains(document.activeElement);
            if (event.shiftKey && (document.activeElement === first || !isInside)) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && (document.activeElement === last || !isInside)) {
                event.preventDefault();
                first.focus();
            }
        },
        onBackdropDown(event) {
            this.isPressedOnBackdrop = event.target === event.currentTarget;
        },
        onBackdropClick(event) {
            const wasPressed = this.isPressedOnBackdrop;
            this.isPressedOnBackdrop = false;
            if (event.target !== event.currentTarget || !wasPressed) {
                return;
            }
            if (this.closable) {
                this.requestClose();
            } else {
                this.refuse();
            }
        },
        onWheel(event) {
            if (this.isPhone || event.target.closest?.('.kk-modal-dialog')) {
                return;
            }
            const scroller = document.getElementById('app-scroll');
            if (!scroller) {
                return;
            }
            const unit = event.deltaMode === 1 ? 16 : (event.deltaMode === 2 ? scroller.clientHeight : 1);
            scroller.scrollTop += event.deltaY * unit;
            event.preventDefault();
        },
        startDrag(event) {
            if (event.button !== 0 || this.isPhone) {
                return;
            }
            event.preventDefault();
            const handle = event.currentTarget.getBoundingClientRect();
            const start = { x: this.offset.x, y: this.offset.y };
            const baseLeft = handle.left - start.x;
            const baseTop = handle.top - start.y;
            const bounds = {
                minX: DRAG_MARGIN - baseLeft,
                maxX: Math.max(DRAG_MARGIN - baseLeft, window.innerWidth - DRAG_MARGIN - handle.width - baseLeft),
                minY: DRAG_MARGIN - baseTop,
                maxY: Math.max(DRAG_MARGIN - baseTop, window.innerHeight - DRAG_MARGIN - handle.height - baseTop),
            };
            const originX = event.clientX;
            const originY = event.clientY;
            const clamp = (value, lo, hi) => Math.min(Math.max(value, lo), hi);
            this.onDragMove = (move) => {
                this.offset = {
                    x: clamp(start.x + move.clientX - originX, bounds.minX, bounds.maxX),
                    y: clamp(start.y + move.clientY - originY, bounds.minY, bounds.maxY),
                };
            };
            this.onDragEnd = () => this.stopDrag();
            document.addEventListener('mousemove', this.onDragMove);
            document.addEventListener('mouseup', this.onDragEnd);
        },
        stopDrag() {
            if (this.onDragMove) {
                document.removeEventListener('mousemove', this.onDragMove);
                document.removeEventListener('mouseup', this.onDragEnd);
                this.onDragMove = null;
                this.onDragEnd = null;
            }
        },
    },
};
</script>
