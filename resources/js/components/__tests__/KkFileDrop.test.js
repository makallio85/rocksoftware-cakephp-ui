import { describe, it, expect, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import KkFileDrop, { formatSize, isAccepted } from '../KkFileDrop.vue';

function file(name, type, size = 10) {
    const f = new File(['x'], name, { type });
    Object.defineProperty(f, 'size', { value: size });
    return f;
}

function drop(wrapper, files) {
    return wrapper.find('[data-role="drop"]').trigger('drop', { dataTransfer: { files } });
}

let wrapper = null;

afterEach(() => {
    wrapper?.unmount();
    wrapper = null;
});

describe('helpers', () => {
    it('formats sizes', () => {
        expect(formatSize(512)).toBe('512 B');
        expect(formatSize(34 * 1024)).toBe('34 kB');
        expect(formatSize(2.4 * 1024 * 1024)).toBe('2.4 MB');
    });

    it('matches MIME types, wildcards and extensions', () => {
        expect(isAccepted(file('a.png', 'image/png'), 'image/png')).toBe(true);
        expect(isAccepted(file('a.png', 'image/png'), 'image/*')).toBe(true);
        expect(isAccepted(file('a.WEBP', ''), '.webp')).toBe(true);
        expect(isAccepted(file('a.pdf', 'application/pdf'), 'image/*,.webp')).toBe(false);
        expect(isAccepted(file('a.pdf', 'application/pdf'), '')).toBe(true);
    });
});

describe('KkFileDrop', () => {
    it('opens the file picker on click and on Enter', async () => {
        wrapper = mount(KkFileDrop);
        const click = vi.spyOn(wrapper.find('input[type="file"]').element, 'click');

        await wrapper.find('[data-role="drop"]').trigger('click');
        await wrapper.find('[data-role="drop"]').trigger('keydown', { key: 'Enter' });

        expect(click).toHaveBeenCalledTimes(2);
    });

    it('takes a dropped file, lists it and emits it', async () => {
        wrapper = mount(KkFileDrop, { props: { accept: 'image/*' } });

        await drop(wrapper, [file('hero.png', 'image/png', 2048)]);

        const emitted = wrapper.emitted('update:modelValue')[0][0];
        expect(emitted.map((f) => f.name)).toEqual(['hero.png']);
        expect(wrapper.emitted('change')[0][0]).toEqual(emitted);
        await wrapper.setProps({ modelValue: emitted });
        expect(wrapper.find('[data-role="files"]').text()).toContain('hero.png');
        expect(wrapper.find('[data-role="files"]').text()).toContain('2 kB');
    });

    it('keeps only the last file when it takes one', async () => {
        wrapper = mount(KkFileDrop);

        await drop(wrapper, [file('a.png', 'image/png'), file('b.png', 'image/png')]);

        expect(wrapper.emitted('update:modelValue')[0][0].map((f) => f.name)).toEqual(['b.png']);
    });

    it('adds to the chosen files when it takes several', async () => {
        wrapper = mount(KkFileDrop, { props: { multiple: true, modelValue: [file('a.png', 'image/png')] } });

        await drop(wrapper, [file('b.png', 'image/png')]);

        expect(wrapper.emitted('update:modelValue')[0][0].map((f) => f.name)).toEqual(['a.png', 'b.png']);
    });

    it('refuses a file of another type or over the size limit, and says why', async () => {
        wrapper = mount(KkFileDrop, { props: { accept: 'image/*', maxBytes: 1000 } });

        await drop(wrapper, [file('notes.pdf', 'application/pdf'), file('huge.png', 'image/png', 5000)]);

        expect(wrapper.emitted('update:modelValue')).toBeUndefined();
        expect(wrapper.emitted('rejected')[0][0].map((r) => r.file.name)).toEqual(['notes.pdf', 'huge.png']);
        const refusal = wrapper.find('[data-role="refusal"]').text();
        expect(refusal).toContain('notes.pdf is not a file type');
        expect(refusal).toContain('huge.png is larger than 1000 B');
    });

    it('removes a listed file', async () => {
        wrapper = mount(KkFileDrop, { props: { multiple: true, modelValue: [file('a.png', 'image/png'), file('b.png', 'image/png')] } });

        await wrapper.findAll('.kk-file-remove')[0].trigger('click');

        expect(wrapper.emitted('update:modelValue')[0][0].map((f) => f.name)).toEqual(['b.png']);
    });

    it('takes nothing while busy or disabled', async () => {
        wrapper = mount(KkFileDrop, { props: { isBusy: true } });
        await drop(wrapper, [file('a.png', 'image/png')]);
        expect(wrapper.emitted('update:modelValue')).toBeUndefined();
        expect(wrapper.find('.spinner-border').exists()).toBe(true);

        await wrapper.setProps({ isBusy: false, disabled: true });
        await drop(wrapper, [file('a.png', 'image/png')]);
        expect(wrapper.emitted('update:modelValue')).toBeUndefined();
    });

    it('shows the hint and the default slot', () => {
        wrapper = mount(KkFileDrop, { props: { hint: 'At most 10 MB.' }, slots: { default: '<span class="current">Current: a.png</span>' } });

        expect(wrapper.find('.form-text').text()).toBe('At most 10 MB.');
        expect(wrapper.find('.current').exists()).toBe(true);
    });
});
