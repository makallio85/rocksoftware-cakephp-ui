import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import KkListTable from '../KkListTable.vue';

const COLUMNS = [
    { key: 'name', label: 'Name' },
    { key: 'spend', label: 'Spend' },
    { key: 'status', label: 'Status', sortable: false },
];

const ROWS = [
    { id: 1, name: 'Beta', spend: 10, status: 'active' },
    { id: 2, name: 'alpha', spend: 9, status: 'paused' },
    { id: 3, name: '', spend: null, status: 'active' },
    { id: 4, name: 'Gamma', spend: 100, status: 'ended' },
];

function names(wrapper) {
    return wrapper.findAll('tbody tr').map((tr) => tr.attributes('data-key'));
}

describe('KkListTable', () => {
    it('sorts by the clicked header, flips on a second click, and keeps empties last', async () => {
        const wrapper = mount(KkListTable, { props: { rows: ROWS, columns: COLUMNS } });
        await wrapper.find('th[data-sort="name"]').trigger('click');
        expect(names(wrapper)).toEqual(['2', '1', '4', '3']);
        expect(wrapper.find('th[data-sort="name"]').attributes('aria-sort')).toBe('ascending');
        expect(wrapper.emitted('update:sortKey')[0]).toEqual(['name']);

        await wrapper.find('th[data-sort="name"]').trigger('click');
        expect(names(wrapper)).toEqual(['4', '1', '2', '3']);
        expect(wrapper.emitted('update:sortAsc').at(-1)).toEqual([false]);
    });

    it('sorts numbers numerically and uses sortValue', async () => {
        const wrapper = mount(KkListTable, { props: { rows: ROWS, columns: COLUMNS, sortKey: 'spend' } });
        expect(names(wrapper)).toEqual(['2', '1', '4', '3']);

        await wrapper.setProps({
            columns: [{ key: 'name', label: 'Name', sortValue: (row) => (row.spend === null ? null : -row.spend) }],
            sortKey: 'name',
        });
        expect(names(wrapper)).toEqual(['4', '1', '2', '3']);
    });

    it('does not sort by a column marked unsortable', () => {
        const wrapper = mount(KkListTable, { props: { rows: ROWS, columns: COLUMNS } });
        expect(wrapper.find('th[data-sort="status"]').exists()).toBe(false);
    });

    it('applies a browser filter with match and reports its value', async () => {
        const filters = [{
            key: 'status', label: 'Status', type: 'select', multiple: true,
            options: [{ value: 'active', label: 'Active' }, { value: 'paused', label: 'Paused' }],
            match: (row, value) => value.includes(row.status),
        }];
        const wrapper = mount(KkListTable, { props: { rows: ROWS, columns: COLUMNS, filters } });
        expect(names(wrapper)).toHaveLength(4);
        expect(wrapper.find('.kk-list-table-picker').text()).toBe('All');

        const boxes = wrapper.findAll('[data-filter="status"] input[type="checkbox"]');
        await boxes[1].setValue(true);
        expect(names(wrapper)).toEqual(['2']);
        await boxes[0].setValue(true);
        expect(wrapper.emitted('update:filterValues').at(-1)).toEqual([{ status: ['active', 'paused'] }]);
        expect(wrapper.find('.kk-list-table-picker').text()).toBe('Active, Paused');
    });

    it('leaves a filter without match to the server', async () => {
        const filters = [{ key: 'q', label: 'Search', type: 'text' }];
        const wrapper = mount(KkListTable, { props: { rows: ROWS, columns: COLUMNS, filters } });
        await wrapper.find('[data-filter="q"] input').setValue('nothing matches this');
        expect(names(wrapper)).toHaveLength(4);
        expect(wrapper.emitted('update:filterValues').at(-1)).toEqual([{ q: 'nothing matches this' }]);
    });

    it('reports a single select\'s value with its option\'s type', async () => {
        const filters = [{
            key: 'year', label: 'Year', type: 'select',
            options: [{ value: 2025, label: '2025' }, { value: 2026, label: '2026' }],
        }];
        const wrapper = mount(KkListTable, { props: { rows: ROWS, columns: COLUMNS, filters } });
        await wrapper.find('[data-filter="year"] select').setValue('2026');
        expect(wrapper.emitted('update:filterValues').at(-1)).toEqual([{ year: 2026 }]);
    });

    it('gives a filter its own id, and its label points at it', () => {
        const filters = [{ key: 'q', label: 'Search', type: 'text' }];
        const wrapper = mount(KkListTable, { props: { rows: ROWS, columns: COLUMNS, filters } });
        const input = wrapper.find('[data-filter="q"] input');
        expect(input.attributes('id')).toBeTruthy();
        expect(wrapper.find('[data-filter="q"] label').attributes('for')).toBe(input.attributes('id'));
    });

    it('shows the empty text when nothing is left', () => {
        const wrapper = mount(KkListTable, { props: { rows: [], columns: COLUMNS, emptyText: 'No suppliers.' } });
        expect(wrapper.find('[data-role="empty"]').text()).toBe('No suppliers.');
        expect(wrapper.find('table').exists()).toBe(false);
    });

    it('shows the count label', () => {
        const wrapper = mount(KkListTable, {
            props: { rows: ROWS, columns: COLUMNS, countLabel: (n) => `${n} suppliers` },
        });
        expect(wrapper.find('[data-role="count"]').text()).toBe('4 suppliers');
    });

    it('renders a card per row below lg, with the phone sort control', async () => {
        const wrapper = mount(KkListTable, {
            props: { rows: ROWS, columns: COLUMNS, sortKey: 'name' },
            slots: { card: '<template #card="{ row }"><span class="custom">{{ row.name }}</span></template>' },
        });
        const cards = wrapper.find('[data-role="cards"]');
        expect(cards.classes()).toContain('d-lg-none');
        expect(wrapper.find('table').classes()).toEqual(expect.arrayContaining(['d-none', 'd-lg-table']));
        expect(cards.findAll('.card')).toHaveLength(4);
        expect(cards.findAll('.custom').map((el) => el.text())).toEqual(['alpha', 'Beta', 'Gamma', '']);

        const sort = wrapper.find('[data-role="sort"]');
        expect(sort.findAll('option').map((o) => o.text())).toEqual(['Name', 'Spend']);
        await sort.setValue('spend');
        expect(wrapper.emitted('update:sortKey').at(-1)).toEqual(['spend']);
        await wrapper.find('[data-role="sort-direction"]').trigger('click');
        expect(wrapper.emitted('update:sortAsc').at(-1)).toEqual([false]);
    });

    it('selects rows and all rows, and shows the bulk slot', async () => {
        const wrapper = mount(KkListTable, {
            props: { rows: ROWS, columns: COLUMNS, selectable: true },
            slots: { bulk: '<template #bulk="{ selected }"><span class="bulk">{{ selected.length }}</span></template>' },
        });
        await wrapper.find('tbody tr input[type="checkbox"]').setValue(true);
        expect(wrapper.emitted('update:selected').at(-1)).toEqual([[1]]);
        expect(wrapper.find('.bulk').text()).toBe('1');

        await wrapper.find('thead input[type="checkbox"]').setValue(true);
        expect(wrapper.emitted('update:selected').at(-1)).toEqual([[1, 2, 3, 4]]);
    });

    it('emits row-click on a row and not on a link or button inside it', async () => {
        const wrapper = mount(KkListTable, {
            props: { rows: ROWS, columns: COLUMNS, rowClickable: true },
            slots: { actions: '<template #actions="{ row }"><button class="act">More</button></template>' },
        });
        await wrapper.find('tbody tr td').trigger('click');
        expect(wrapper.emitted('row-click')[0][0]).toEqual(ROWS[0]);

        await wrapper.find('tbody tr .act').trigger('click');
        expect(wrapper.emitted('row-click')).toHaveLength(1);
    });

    it('ignores row clicks unless rows are clickable', async () => {
        const wrapper = mount(KkListTable, { props: { rows: ROWS, columns: COLUMNS } });
        await wrapper.find('tbody tr td').trigger('click');
        expect(wrapper.emitted('row-click')).toBeUndefined();
    });
});
