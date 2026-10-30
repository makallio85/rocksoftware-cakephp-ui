import { describe, it, expect, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import KkListTable, { rememberKey, rememberedValue } from '../components/KkListTable.vue';
import { mountAll } from '../markup.js';
import { resetTexts } from '../texts.js';

const COLUMNS = [{ key: 'name', label: 'Name' }, { key: 'channel', label: 'Channel' }];
const ROWS = [
    { id: 1, name: 'North', channel: 'SMS' },
    { id: 2, name: 'South', channel: 'Email' },
    { id: 3, name: 'East', channel: 'Email' },
];
const CHANNEL = {
    key: 'channel',
    label: 'Channel',
    type: 'select',
    options: [{ value: 'Email', label: 'Email' }, { value: 'SMS', label: 'SMS' }],
    match: (row, value) => row.channel === value,
};
const SEARCH = { key: 'q', label: 'Search', type: 'text', match: (row, value) => row.name.includes(value) };
const SERVER = { key: 'status', label: 'Status', type: 'select', options: [{ value: 'ok', label: 'OK' }] };

function keys(wrapper) {
    return wrapper.findAll('tbody tr').map((tr) => tr.attributes('data-key'));
}

function table(props = {}) {
    return mount(KkListTable, {
        props: {
            rows: ROWS,
            columns: COLUMNS,
            filters: [CHANNEL, SEARCH, SERVER],
            rememberFilters: ['channel', 'q', 'status'],
            listId: 'gateways',
            ...props,
        },
    });
}

describe('<kk-list-table> remembering filters', () => {
    afterEach(() => {
        window.localStorage.clear();
        document.body.innerHTML = '';
        resetTexts();
        vi.restoreAllMocks();
    });

    it('keys a value by the prefix, the list and the filter', () => {
        expect(rememberKey('gateways', 'channel')).toBe('ui:list-filters:gateways:channel');
        document.body.innerHTML = '<script type="application/json" id="kk-ui-texts">{"storageKeyPrefix":"demo"}</script>';
        resetTexts();
        expect(rememberKey('gateways', 'channel')).toBe('demo:list-filters:gateways:channel');
    });

    it('saves a picked value and restores it in a new table', async () => {
        const first = table();
        await first.find('[data-filter="channel"] select').setValue('Email');
        expect(window.localStorage.getItem('ui:list-filters:gateways:channel')).toBe('"Email"');

        const second = table();
        expect(second.find('[data-filter="channel"] select').element.value).toBe('Email');
        expect(keys(second)).toEqual(['2', '3']);
        expect(second.emitted('update:filterValues')[0][0]).toMatchObject({ channel: 'Email' });
    });

    it('forgets a value set back to all', async () => {
        const wrapper = table();
        await wrapper.find('[data-filter="channel"] select').setValue('SMS');
        await wrapper.find('[data-filter="channel"] select').setValue('');
        expect(window.localStorage.getItem('ui:list-filters:gateways:channel')).toBeNull();
    });

    it('restores over the host\'s values, and keeps the text a person typed', async () => {
        const first = table();
        const input = first.find('[data-filter="q"] input');
        input.element.value = 'th';
        await input.trigger('input');

        const second = table({ filterValues: { q: 'x', channel: 'SMS' } });
        expect(second.find('[data-filter="q"] input').element.value).toBe('th');
        expect(second.find('[data-filter="channel"] select').element.value).toBe('SMS');
        expect(keys(second)).toEqual(['1']);
    });

    it('does not remember a server filter, a filter not named or a list without an id', async () => {
        const wrapper = table();
        await wrapper.find('[data-filter="status"] select').setValue('ok');
        expect(window.localStorage.length).toBe(0);

        const unnamed = table({ rememberFilters: ['q'] });
        await unnamed.find('[data-filter="channel"] select').setValue('SMS');
        expect(window.localStorage.length).toBe(0);

        const anonymous = table({ listId: '' });
        await anonymous.find('[data-filter="channel"] select').setValue('SMS');
        expect(window.localStorage.length).toBe(0);
    });

    it('ignores a value that no longer fits its filter', () => {
        window.localStorage.setItem('ui:list-filters:gateways:channel', '"Fax"');
        window.localStorage.setItem('ui:list-filters:gateways:q', '{not json');
        const wrapper = table();
        expect(wrapper.find('[data-filter="channel"] select').element.value).toBe('');
        expect(keys(wrapper)).toEqual(['1', '2', '3']);
        expect(wrapper.emitted('update:filterValues')).toBeUndefined();
    });

    it('works on when the browser refuses storage', async () => {
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => { throw new Error('denied'); });
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => { throw new Error('denied'); });
        const wrapper = table();
        await wrapper.find('[data-filter="channel"] select').setValue('SMS');
        expect(keys(wrapper)).toEqual(['1']);
    });

    it('checks a remembered value against the filter', () => {
        const multiple = { ...CHANNEL, multiple: true };
        expect(rememberedValue(multiple, ['SMS', 'Fax', 'Email'])).toEqual(['Email', 'SMS']);
        expect(rememberedValue(multiple, ['Fax'])).toBeUndefined();
        expect(rememberedValue(multiple, 'SMS')).toBeUndefined();
        expect(rememberedValue({ type: 'select', options: [{ value: 3, label: 'Three' }] }, '3')).toBe(3);
        const range = { key: 'sent', type: 'date_range' };
        expect(rememberedValue(range, { from: '2026-10-01', to: 'soon' })).toEqual({ from: '2026-10-01', to: '' });
        expect(rememberedValue(range, { from: '', to: '' })).toBeUndefined();
        expect(rememberedValue(SEARCH, 42)).toBeUndefined();
    });

    it('restores a markup list\'s filter from the element\'s config', async () => {
        window.localStorage.setItem('ui:list-filters:gateways:channel', '"SMS"');
        const config = {
            rows: ROWS,
            columns: COLUMNS,
            filters: [{ key: 'channel', label: 'Channel', type: 'select', matchField: 'channel', options: CHANNEL.options }],
            rememberFilters: ['channel'],
            listId: 'gateways',
        };
        document.body.innerHTML = `<div data-kk-list-table id="gateways">
            <script type="application/json" data-kk-list-table-config>${JSON.stringify(config)}</script></div>`;
        mountAll(document);
        await Promise.resolve();
        const rows = Array.from(document.querySelectorAll('tbody tr')).map((tr) => tr.getAttribute('data-key'));
        expect(rows).toEqual(['1']);
        expect(document.querySelector('[data-filter="channel"] select').value).toBe('SMS');
    });
});
