import { describe, it, expect, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import KkDateTimePicker, { partsOf, parseTyped } from '../KkDateTimePicker.vue';
import KkListTable from '../KkListTable.vue';

let wrapper = null;

function mountPicker(props) {
    wrapper = mount(KkDateTimePicker, { props, attachTo: document.body });
    return wrapper;
}

function lastEmitted() {
    return wrapper.emitted('update:modelValue')?.at(-1)?.[0];
}

async function open() {
    await wrapper.find('input').trigger('click');
    await nextTick();
}

function menu() {
    return document.querySelector('.kk-dtp-menu');
}

async function type(text) {
    const input = wrapper.find('input');
    input.element.value = text;
    await input.trigger('input');
    await input.trigger('blur');
}

afterEach(() => {
    wrapper?.unmount();
    wrapper = null;
    vi.useRealTimers();
    document.body.innerHTML = '';
});

describe('KkDateTimePicker', () => {
    it('shows the wire value as DD.MM.YYYY HH.mm', () => {
        mountPicker({ modelValue: '2026-03-05 14:30' });
        expect(wrapper.find('input').element.value).toBe('05.03.2026 14.30');
    });

    it('shows a date, a time and a month in their own form', async () => {
        mountPicker({ modelValue: '2026-03-05', mode: 'date' });
        expect(wrapper.find('input').element.value).toBe('05.03.2026');
        await wrapper.setProps({ modelValue: '09:00', mode: 'time' });
        expect(wrapper.find('input').element.value).toBe('09.00');
        await wrapper.setProps({ modelValue: '2026-03', mode: 'month' });
        expect(wrapper.find('input').element.value).toBe('03/2026');
    });

    it('lists the day in 30-minute steps beside the calendar, or in the step asked for', async () => {
        mountPicker({ modelValue: '2026-03-05 14:30' });
        await open();
        expect(menu().querySelector('.kk-dtp-calendar')).not.toBeNull();
        const items = menu().querySelectorAll('.kk-dtp-time-item');
        expect(items).toHaveLength(48);
        expect(menu().querySelector('.kk-dtp-time-item.active').textContent).toBe('14.30');

        wrapper.unmount();
        mountPicker({ modelValue: '', timeStep: 15 });
        await open();
        expect(menu().querySelectorAll('.kk-dtp-time-item')).toHaveLength(96);
    });

    it('emits the wire string for a picked time and closes', async () => {
        mountPicker({ modelValue: '2026-03-05 14:30' });
        await open();
        const nine = Array.from(menu().querySelectorAll('.kk-dtp-time-item')).find((b) => b.textContent === '09.00');
        nine.click();
        await nextTick();
        expect(lastEmitted()).toBe('2026-03-05 09:00');
        expect(menu()).toBeNull();
    });

    it('keeps the time and the menu open when a day is picked from the calendar', async () => {
        mountPicker({ modelValue: '2026-03-05 14:30' });
        await open();
        wrapper.vm.onCalendar(new Date(2026, 3, 10));
        await nextTick();
        expect(lastEmitted()).toBe('2026-04-10 14:30');
        expect(menu()).not.toBeNull();
    });

    it('gives a day picked before any time the next step from now', async () => {
        vi.useFakeTimers({ toFake: ['Date'] });
        vi.setSystemTime(new Date(2026, 2, 5, 10, 12));
        mountPicker({ modelValue: '' });
        await open();
        wrapper.vm.onCalendar(new Date(2026, 2, 6));
        expect(lastEmitted()).toBe('2026-03-06 10:30');
    });

    it('closes on a day in date mode and has no time list', async () => {
        mountPicker({ modelValue: '', mode: 'date' });
        await open();
        expect(menu().querySelector('.kk-dtp-time')).toBeNull();
        wrapper.vm.onCalendar(new Date(2026, 0, 2));
        await nextTick();
        expect(lastEmitted()).toBe('2026-01-02');
        expect(menu()).toBeNull();
    });

    it('picks a time of day alone in time mode', async () => {
        mountPicker({ modelValue: '', mode: 'time' });
        await open();
        expect(menu().querySelector('.kk-dtp-calendar')).toBeNull();
        menu().querySelectorAll('.kk-dtp-time-item')[17].click();
        expect(lastEmitted()).toBe('08:30');
    });

    it('picks a month in month mode', async () => {
        mountPicker({ modelValue: '', mode: 'month' });
        await open();
        wrapper.vm.onCalendar({ month: 10, year: 2026 });
        expect(lastEmitted()).toBe('2026-11');
    });

    it('takes a typed value day first and keeps a typed time to the minute', async () => {
        mountPicker({ modelValue: '' });
        await type('5.3.2026 14.20');
        expect(lastEmitted()).toBe('2026-03-05 14:20');
    });

    it('puts back what was there when the typed text is not a date', async () => {
        mountPicker({ modelValue: '2026-03-05 14:30' });
        await type('soon');
        expect(wrapper.emitted('update:modelValue')).toBeUndefined();
        expect(wrapper.find('input').element.value).toBe('05.03.2026 14.30');
    });

    it('speaks Date objects to a screen that holds them', async () => {
        mountPicker({ modelValue: new Date(2026, 2, 5, 14, 30), modelType: 'date' });
        expect(wrapper.find('input').element.value).toBe('05.03.2026 14.30');
        await type('6.3.2026 8:00');
        expect(lastEmitted()).toEqual(new Date(2026, 2, 6, 8, 0));
    });

    it('clears, unless it may not', async () => {
        mountPicker({ modelValue: '2026-03-05', mode: 'date' });
        await wrapper.find('.kk-dtp-clear').trigger('click');
        expect(lastEmitted()).toBe('');

        await wrapper.setProps({ clearable: false });
        expect(wrapper.find('.kk-dtp-clear').exists()).toBe(false);
    });

    it('refuses a typed day outside min and max', async () => {
        mountPicker({ modelValue: '', mode: 'date', min: '2026-03-01', max: '2026-03-31' });
        await type('28.2.2026');
        expect(wrapper.emitted('update:modelValue')).toBeUndefined();
        await type('15.3.2026');
        expect(lastEmitted()).toBe('2026-03-15');
    });

    it('takes the size and the classes the screen asks for', () => {
        mountPicker({ modelValue: '', size: 'sm', inputClass: 'is-invalid' });
        expect(wrapper.find('input').classes()).toEqual(expect.arrayContaining(['form-control-sm', 'is-invalid']));
        expect(wrapper.classes()).toContain('kk-dtp-sm');
    });

    it('reads wire strings by their digits', () => {
        expect(partsOf('2026-03-05 14:30', 'datetime')).toEqual({ y: 2026, mo: 2, d: 5, h: 14, mi: 30 });
        expect(partsOf('2026-02-30', 'date').y).toBeNull();
        expect(parseTyped('5.3.26', 'date')).toMatchObject({ y: 2026, mo: 2, d: 5 });
        expect(parseTyped('25:00', 'time')).toBeNull();
    });
});

describe('KkListTable date_range filter', () => {
    it('reports its range as from/to wire strings', async () => {
        wrapper = mount(KkListTable, {
            props: {
                rows: [{ id: 1, name: 'A' }],
                columns: [{ key: 'name', label: 'Name' }],
                filters: [{ key: 'created', label: 'Created', type: 'date_range' }],
            },
            attachTo: document.body,
        });
        const from = wrapper.find('[data-filter="created"] [data-part="from"] input');
        from.element.value = '01.03.2026';
        await from.trigger('input');
        await from.trigger('blur');
        expect(wrapper.emitted('update:filterValues').at(-1)).toEqual([{ created: { from: '2026-03-01', to: '' } }]);
    });
});
