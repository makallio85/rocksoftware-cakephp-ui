import { describe, it, expect, afterEach } from 'vitest';
import { createApp, reactive, nextTick, h } from 'vue';
import KkModal from '../KkModal.vue';

let app = null;

function mountHost(render, initial) {
    document.body.innerHTML = '<button id="opener">Open</button><div id="host"></div>';
    const state = reactive({ closes: 0, ...initial });
    app = createApp({ render: () => render(state) });
    app.mount('#host');
    return state;
}

function basic(state, props = {}) {
    return h(KkModal, {
        open: state.open,
        title: 'Add supplier',
        onClose: () => { state.open = false; state.closes++; },
        ...props,
    }, {
        default: () => h('input', { id: 'field' }),
        footer: () => h('button', { id: 'save' }, 'Save'),
    });
}

function press(target) {
    target.dispatchEvent(new MouseEvent('mousedown', { bubbles: true, button: 0 }));
}

function click(target) {
    target.dispatchEvent(new MouseEvent('click', { bubbles: true }));
}

function escape() {
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
}

afterEach(() => {
    app?.unmount();
    app = null;
});

describe('KkModal', () => {
    it('renders on the body with its title, body and footer while open', async () => {
        const state = mountHost(basic, { open: false });
        expect(document.querySelector('.kk-modal')).toBeNull();

        state.open = true;
        await nextTick();
        const modal = document.querySelector('.kk-modal');
        expect(modal.parentElement).toBe(document.body);
        expect(modal.querySelector('.kk-modal-title').textContent).toBe('Add supplier');
        expect(modal.querySelector('.kk-modal-body #field')).not.toBeNull();
        expect(modal.querySelector('.kk-modal-footer #save')).not.toBeNull();
        expect(modal.querySelector('.kk-modal-dialog').getAttribute('aria-labelledby'))
            .toBe(modal.querySelector('.kk-modal-title').id);
    });

    it('keeps the header to the title, the move handle and the close button', async () => {
        mountHost(basic, { open: true });
        await nextTick();
        const header = document.querySelector('.kk-modal-header');
        expect(Array.from(header.children).map((el) => el.className))
            .toEqual(['kk-modal-title', 'kk-modal-handle', 'btn-close']);
    });

    it('announces on the document once an opened modal\'s content is in the page', async () => {
        const state = mountHost(basic, { open: false });
        let shown = null;
        document.addEventListener('kk-modal:shown', (event) => { shown = event.detail.dialog; }, { once: true });
        state.open = true;
        await nextTick();
        await nextTick();
        expect(shown).toBe(document.querySelector('.kk-modal-dialog'));
    });

    it('closes from the close button, Esc and a click on the backdrop', async () => {
        const state = mountHost(basic, { open: true });
        await nextTick();
        click(document.querySelector('.btn-close'));
        expect(state.closes).toBe(1);

        state.open = true;
        await nextTick();
        escape();
        expect(state.closes).toBe(2);

        state.open = true;
        await nextTick();
        const backdrop = document.querySelector('.kk-modal');
        press(backdrop);
        click(backdrop);
        expect(state.closes).toBe(3);
        await nextTick();
        expect(document.querySelector('.kk-modal')).toBeNull();
    });

    it('emits update:open false for v-model:open', async () => {
        const updates = [];
        mountHost((state) => basic(state, { 'onUpdate:open': (value) => updates.push(value) }), { open: true });
        await nextTick();
        escape();
        expect(updates).toEqual([false]);
    });

    it('stays open when a press inside the dialog is released on the backdrop', async () => {
        const state = mountHost(basic, { open: true });
        await nextTick();
        press(document.querySelector('#field'));
        click(document.querySelector('.kk-modal'));
        expect(state.closes).toBe(0);
    });

    it('does not close when closable is false', async () => {
        const state = mountHost((s) => basic(s, { closable: false }), { open: true });
        await nextTick();
        expect(document.querySelector('.btn-close')).toBeNull();
        escape();
        const backdrop = document.querySelector('.kk-modal');
        press(backdrop);
        click(backdrop);
        expect(state.closes).toBe(0);
        await nextTick();
        expect(document.querySelector('.kk-modal').classList.contains('kk-modal-refused')).toBe(true);
    });

    it('stacks a modal opened from a modal above it, and Esc closes only the top one', async () => {
        const state = mountHost((s) => [
            h(KkModal, { open: s.outer, title: 'Outer', onClose: () => { s.outer = false; } }),
            h(KkModal, { open: s.inner, title: 'Inner', onClose: () => { s.inner = false; } }),
        ], { outer: true, inner: false });
        await nextTick();
        state.inner = true;
        await nextTick();
        const [outer, inner] = document.querySelectorAll('.kk-modal');
        expect(Number(inner.style.zIndex)).toBeGreaterThan(Number(outer.style.zIndex));

        escape();
        await nextTick();
        expect(state.inner).toBe(false);
        expect(state.outer).toBe(true);
    });

    it('moves focus into the dialog and back to the opener', async () => {
        const state = mountHost(basic, { open: false });
        document.getElementById('opener').focus();
        state.open = true;
        await nextTick();
        await nextTick();
        expect(document.activeElement).toBe(document.querySelector('.kk-modal-dialog'));

        state.open = false;
        await nextTick();
        expect(document.activeElement).toBe(document.getElementById('opener'));
    });

    it('marks the body while any modal is open', async () => {
        const state = mountHost(basic, { open: true });
        await nextTick();
        expect(document.body.classList.contains('kk-modal-open')).toBe(true);
        state.open = false;
        await nextTick();
        expect(document.body.classList.contains('kk-modal-open')).toBe(false);
    });

    it('takes its size', async () => {
        mountHost((s) => basic(s, { size: 'lg' }), { open: true });
        await nextTick();
        expect(document.querySelector('.kk-modal-dialog').classList.contains('kk-modal-lg')).toBe(true);
    });
});
