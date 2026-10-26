import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import KkListTable from '../components/KkListTable.vue';

const COLUMNS = [{ key: 'name', label: 'Name' }, { key: 'size', label: 'Size' }];
const ROWS = [{ id: 1, name: 'Beta', size: 2 }, { id: 2, name: 'Alpha', size: 1 }];

describe('<kk-list-table> for a server-paged list', () => {
    it('keeps the server\'s order and reports a header click', async () => {
        const wrapper = mount(KkListTable, {
            props: { rows: ROWS, columns: COLUMNS, sortKey: 'name', sortAsc: true, isServerSorted: true },
        });
        expect(wrapper.findAll('tbody tr').map((tr) => tr.attributes('data-key'))).toEqual(['1', '2']);
        await wrapper.find('th[data-sort="name"]').trigger('click');
        expect(wrapper.emitted('update:sortAsc')[0]).toEqual([false]);
        expect(wrapper.findAll('tbody tr').map((tr) => tr.attributes('data-key'))).toEqual(['1', '2']);
    });

    it('shows the pager under the rows and reports the page asked for', async () => {
        const wrapper = mount(KkListTable, {
            props: { rows: ROWS, columns: COLUMNS, pagination: { page: 1, perPage: 2, count: 6 } },
        });
        expect(wrapper.find('.kk-pagination-counter').text()).toBe('Showing 1–2 of 6');
        await wrapper.find('button[data-page="2"]').trigger('click');
        expect(wrapper.emitted('update:page')[0]).toEqual([2]);
    });

    it('takes a pager from the pager slot instead', () => {
        const wrapper = mount(KkListTable, {
            props: { rows: ROWS, columns: COLUMNS, pagination: { page: 1, perPage: 2, count: 6 } },
            slots: { pager: '<div class="own-pager">Pages</div>' },
        });
        expect(wrapper.find('.own-pager').exists()).toBe(true);
        expect(wrapper.find('.kk-pagination-bar').exists()).toBe(false);
    });

    it('commits a text filter on Enter, not on every key', async () => {
        const wrapper = mount(KkListTable, {
            props: { rows: ROWS, columns: COLUMNS, filters: [{ key: 'q', label: 'Search', type: 'text' }] },
        });
        const input = wrapper.find('input[type="search"]');
        input.element.value = 'alp';
        await input.trigger('input');
        expect(wrapper.emitted('filter-commit')).toBeUndefined();
        await input.trigger('keydown', { key: 'Enter' });
        expect(wrapper.emitted('filter-commit')[0]).toEqual(['q', 'alp']);
    });

    it('commits a select filter when picked', async () => {
        const wrapper = mount(KkListTable, {
            props: {
                rows: ROWS,
                columns: COLUMNS,
                filters: [{ key: 'kind', label: 'Kind', type: 'select', options: [{ value: 'a', label: 'A' }] }],
            },
        });
        await wrapper.find('[data-filter="kind"] select').setValue('a');
        expect(wrapper.emitted('filter-commit')[0]).toEqual(['kind', 'a']);
    });
});
