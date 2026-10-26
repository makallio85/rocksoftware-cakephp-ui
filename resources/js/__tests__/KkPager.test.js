import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import KkPager, { pagerItems } from '../components/KkPager.vue';

describe('<kk-pager>', () => {
    it('shows the counter and the pages in the pagination element\'s markup', () => {
        const wrapper = mount(KkPager, { props: { page: 1, perPage: 4, count: 47 } });
        expect(wrapper.find('.kk-pagination-counter').text()).toBe('Showing 1–4 of 47');
        expect(wrapper.find('ul.pagination.kk-pagination').exists()).toBe(true);
        expect(wrapper.find('.page-item.active').text()).toBe('1');
        expect(wrapper.find('.page-item.active').attributes('aria-current')).toBe('page');
        expect(wrapper.findAll('.page-item.disabled').map((li) => li.text())).toEqual(['Previous page', '…']);
        expect(wrapper.findAll('.kk-page-edge').map((li) => li.text())).toEqual(['…', '12']);
    });

    it('asks for a page with buttons, or links to it', async () => {
        const wrapper = mount(KkPager, { props: { page: 2, perPage: 10, count: 30 } });
        await wrapper.find('button[data-page="3"]').trigger('click');
        expect(wrapper.emitted('update:page')[0]).toEqual([3]);

        const linked = mount(KkPager, { props: { page: 2, perPage: 10, count: 30, url: '/list?page={page}' } });
        expect(linked.find('a[rel="next"]').attributes('href')).toBe('/list?page=3');
        expect(linked.find('button').exists()).toBe(false);
    });

    it('shows nothing for an empty list and the counter alone for one page', () => {
        expect(mount(KkPager, { props: { count: 0 } }).html()).toBe('<!--v-if-->');
        const one = mount(KkPager, { props: { page: 1, perPage: 20, count: 3 } });
        expect(one.find('.kk-pagination-counter').text()).toBe('Showing 1–3 of 3');
        expect(one.find('nav').exists()).toBe(false);
    });

    it('keeps the window of numbers inside the pages', () => {
        const numbers = (items) => items.filter((i) => /^\d+$/.test(i.label)).map((i) => i.label);
        expect(numbers(pagerItems(12, 12, 4))).toEqual(['1', '8', '9', '10', '11', '12']);
        expect(numbers(pagerItems(1, 3, 4))).toEqual(['1', '2', '3']);
    });
});
