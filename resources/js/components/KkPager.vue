<!--
<kk-pager>: the pages of a server-paged list, with the same markup and look as
the PHP element `ui/pagination` (part of kk-components.css): "Showing 1–20 of
240", then the previous link, the page numbers around the current one with the
first and the last, and the next link. The edges and ellipses drop on phones.
Nothing renders for an empty list, the counter alone for one page.

    <kk-pager :page="page" :per-page="20" :count="total" @update:page="load"></kk-pager>
    <kk-pager :page="3" :per-page="20" :count="240" url="/messages?page={page}"></kk-pager>

Props:
  page, perPage, count   the current page (from 1), rows per page, rows in all
  modulus                how many page numbers show around the current one (4)
  url                    links to '{page}' in this address instead of buttons
  showsCounter           whether "Showing … of …" shows (default true)
Emits `update:page` with the page asked for (buttons only).
<kk-list-table> shows one under its rows when it gets a `pagination` prop.
-->
<template>
    <div v-if="count > 0" class="kk-pagination-bar" data-role="pager">
        <span v-if="showsCounter" class="kk-pagination-counter">{{ counter }}</span>
        <nav v-if="pageCount > 1" :aria-label="t('pagerPages')">
            <ul class="pagination kk-pagination">
                <li v-for="item in items" :key="item.key" class="page-item"
                    :class="{ disabled: item.isDisabled, active: item.isCurrent, 'kk-page-edge': item.isEdge }"
                    :aria-current="item.isCurrent ? 'page' : null">
                    <span v-if="item.isDisabled || item.isCurrent" class="page-link">
                        <template v-if="item.icon">
                            <i class="bi" :class="item.icon" aria-hidden="true"></i><span class="visually-hidden">{{ item.label }}</span>
                        </template>
                        <template v-else>{{ item.label }}</template>
                    </span>
                    <a v-else-if="url" class="page-link" :href="hrefOf(item.page)" :rel="item.rel || null">
                        <template v-if="item.icon">
                            <i class="bi" :class="item.icon" aria-hidden="true"></i><span class="visually-hidden">{{ item.label }}</span>
                        </template>
                        <template v-else>{{ item.label }}</template>
                    </a>
                    <button v-else type="button" class="page-link" :data-page="item.page" @click="go(item.page)">
                        <template v-if="item.icon">
                            <i class="bi" :class="item.icon" aria-hidden="true"></i><span class="visually-hidden">{{ item.label }}</span>
                        </template>
                        <template v-else>{{ item.label }}</template>
                    </button>
                </li>
            </ul>
        </nav>
    </div>
</template>

<script>
import { t } from '../texts.js';
import { formatNumber } from '../format.js';

/**
 * The entries of the page strip: previous, numbers (with the first, the last
 * and ellipses), next.
 *
 * @param {number} page
 * @param {number} pageCount
 * @param {number} modulus
 * @returns {Array<object>}
 */
export function pagerItems(page, pageCount, modulus = 4) {
    const items = [];
    const half = Math.floor(modulus / 2);
    let start = Math.max(1, page - half);
    let end = Math.min(pageCount, start + modulus);
    start = Math.max(1, Math.min(start, end - modulus));
    items.push({
        key: 'prev', page: page - 1, rel: 'prev', icon: 'bi-chevron-left',
        label: t('pagerPrevious'), isDisabled: page <= 1,
    });
    if (start > 1) {
        items.push({ key: 'first', page: 1, label: '1', isEdge: true });
        if (start > 2) {
            items.push({ key: 'gap-start', label: '…', isDisabled: true, isEdge: true });
        }
    }
    for (let n = start; n <= end; n++) {
        items.push({ key: `page-${n}`, page: n, label: String(n), isCurrent: n === page });
    }
    if (end < pageCount) {
        if (end < pageCount - 1) {
            items.push({ key: 'gap-end', label: '…', isDisabled: true, isEdge: true });
        }
        items.push({ key: 'last', page: pageCount, label: String(pageCount), isEdge: true });
    }
    items.push({
        key: 'next', page: page + 1, rel: 'next', icon: 'bi-chevron-right',
        label: t('pagerNext'), isDisabled: page >= pageCount,
    });
    return items;
}

export default {
    name: 'KkPager',
    props: {
        page: { type: Number, default: 1 },
        perPage: { type: Number, default: 20 },
        count: { type: Number, default: 0 },
        modulus: { type: Number, default: 4 },
        url: { type: String, default: '' },
        showsCounter: { type: Boolean, default: true },
    },
    emits: ['update:page'],
    computed: {
        pageCount() {
            return Math.max(1, Math.ceil(this.count / Math.max(1, this.perPage)));
        },
        current() {
            return Math.min(Math.max(1, this.page), this.pageCount);
        },
        counter() {
            const start = (this.current - 1) * this.perPage + 1;
            const end = Math.min(this.count, this.current * this.perPage);
            return t('pagerShowing', {
                start: formatNumber(start),
                end: formatNumber(end),
                count: formatNumber(this.count),
            });
        },
        items() {
            return pagerItems(this.current, this.pageCount, this.modulus);
        },
    },
    methods: {
        t,
        hrefOf(page) {
            return this.url.split('{page}').join(String(page));
        },
        go(page) {
            this.$emit('update:page', page);
        },
    },
};
</script>
