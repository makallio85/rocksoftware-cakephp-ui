<!--
<kk-list-table>: the one list table. Every screen that shows records as rows
uses it: a sortable table on desktop, one card per row below Bootstrap `lg`,
and the filters above it. It never fetches; the host hands it its rows: all of
them (sorted and filtered here), or one page of them from the server
(`is-server-sorted`, `pagination`, and filters without `match`).

    <kk-list-table :rows="suppliers" :columns="columns" :filters="filters"
                   v-model:filter-values="filterValues" v-model:sort-key="sortKey"
                   v-model:sort-asc="sortAsc" row-clickable @row-click="open">
        <template #cell-name="{ row }">…</template>
        <template #card="{ row }">…</template>
        <template #actions="{ row }">…kebab…</template>
    </kk-list-table>

Props:
  rows          the records
  rowKey        the field that identifies a row ('id')
  columns       [{ key, label, sortable = true, sortValue?(row), class? }]
  filters       [{ key, label, type: 'select'|'text'|'date_range', options?: [{value, label}],
                   multiple?, placeholder?, id?, match?(row, value) }]
                A filter with `match` narrows the rows here. One without it is the
                server's: the table shows it and reports its value, and the host
                reloads its rows.
  filterValues  { key: value }; multiple select: Array; single select: value or '';
                text: String; date_range: { from: 'YYYY-MM-DD' | '', to: … }
  sortKey, sortAsc
  isServerSorted  the rows come sorted (and paged) by the server: a header click
                reports the new order (update:sortKey, update:sortAsc) and the
                host reloads; the rows are not sorted here
  pagination    { page, perPage, count, url? }: a server-paged list; the pager
                (<kk-pager>, the look of the PHP `ui/pagination` element)
                shows under the rows and reports update:page, or links to
                `url` with '{page}' replaced; the `pager` slot replaces it
  selectable, selected (row keys)
  rowClickable  a click on a row (not on a link, button or field in it) emits row-click
  loading, error, emptyText, countLabel(n)
Emits update:filterValues, update:sortKey, update:sortAsc, update:selected,
update:page, row-click, and filter-commit (key, value) when a filter's value is
settled: a select or date picked, a text field left or Enter pressed, the
moment a server filter reloads without reloading on every keystroke.
Slots: cell-<key> { row }, card { row, isSelected, selected, toggle },
       actions { row }, bulk { selected }, pager.
Styles: webroot/css/kk-list-table.css; the pager's are in kk-components.css.
-->
<template>
    <div class="kk-list-table">
        <div v-if="filters.length || sortableColumns.length" class="row g-2 align-items-end mb-2">
            <div v-for="f in filters" :key="f.key" :data-filter="f.key"
                 :class="f.type === 'text' ? 'col-12 col-lg-3' : 'col-12 col-sm-6 col-lg-auto'">
                <label class="form-label small text-muted mb-1" :for="controlId(f)">{{ f.label }}</label>
                <div v-if="f.type === 'select' && f.multiple" class="dropdown">
                    <button type="button" :id="controlId(f)"
                            class="btn btn-sm btn-outline-secondary dropdown-toggle w-100 text-start kk-list-table-picker"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                            :title="summary(f)">{{ summary(f) }}</button>
                    <div class="dropdown-menu shadow-sm kk-list-table-options">
                        <label v-for="o in f.options" :key="String(o.value)" class="dropdown-item d-flex align-items-center gap-2 small">
                            <input type="checkbox" class="form-check-input mt-0" :value="o.value" :checked="isChecked(f, o)"
                                   @change="toggleOption(f, o, $event.target.checked)">
                            <span>{{ o.label }}</span>
                        </label>
                        <div class="dropdown-divider"></div>
                        <div class="px-3 small text-muted">{{ t('listNoneMeansAll') }}</div>
                    </div>
                </div>
                <select v-else-if="f.type === 'select'" :id="controlId(f)" class="form-select form-select-sm"
                        :value="String(valueOf(f))" @change="setSelect(f, $event.target.value)">
                    <option value="">{{ t('listAll') }}</option>
                    <option v-for="o in f.options" :key="String(o.value)" :value="String(o.value)">{{ o.label }}</option>
                </select>
                <input v-else-if="f.type === 'text'" :id="controlId(f)" type="search" class="form-control form-control-sm"
                       :placeholder="f.placeholder || ''" :value="valueOf(f)" @input="setValue(f.key, $event.target.value)"
                       @change="commit(f)" @keydown.enter="commit(f)">
                <div v-else-if="f.type === 'date_range'" class="d-flex align-items-center gap-1">
                    <div data-part="from" class="flex-fill">
                        <kk-date-time-picker mode="date" size="sm" :model-value="valueOf(f).from" :aria-label="t('listFrom')"
                                             @update:model-value="setRange(f, 'from', $event)"></kk-date-time-picker>
                    </div>
                    <span class="text-muted">–</span>
                    <div data-part="to" class="flex-fill">
                        <kk-date-time-picker mode="date" size="sm" :model-value="valueOf(f).to" :aria-label="t('listTo')"
                                             @update:model-value="setRange(f, 'to', $event)"></kk-date-time-picker>
                    </div>
                </div>
            </div>
            <!-- Cards have no headers to click, so a phone sorts here. -->
            <div v-if="sortableColumns.length" class="col-12 d-lg-none">
                <label class="form-label small text-muted mb-1" :for="`kk-list-table-${uid}-sort`">{{ t('listOrder') }}</label>
                <div class="input-group input-group-sm">
                    <select :id="`kk-list-table-${uid}-sort`" data-role="sort" class="form-select form-select-sm"
                            :value="currentSortKey" @change="setSortKey($event.target.value)">
                        <option v-for="c in sortableColumns" :key="c.key" :value="c.key">{{ c.label }}</option>
                    </select>
                    <button type="button" data-role="sort-direction" class="btn btn-outline-secondary"
                            :title="currentSortAsc ? t('listAscending') : t('listDescending')"
                            :aria-label="currentSortAsc ? t('listAscending') : t('listDescending')" @click="flipSort">
                        <i class="bi" :class="currentSortAsc ? 'bi-sort-up' : 'bi-sort-down'"></i>
                    </button>
                </div>
            </div>
        </div>
        <div v-if="selectable && currentSelected.length && $slots.bulk" data-role="bulk"
             class="d-flex flex-wrap align-items-center gap-2 border rounded bg-light p-2 mb-2">
            <slot name="bulk" :selected="currentSelected"></slot>
        </div>
        <div v-if="loading && !rows.length" class="text-center py-5"><div class="spinner-border"></div></div>
        <div v-else-if="error" class="alert alert-danger small">{{ error }}</div>
        <div v-else-if="!visibleRows.length" class="text-muted py-4" data-role="empty">{{ emptyText }}</div>
        <template v-else>
            <div v-if="countLabel" class="small text-muted mb-2" data-role="count">{{ countLabel(visibleRows.length) }}</div>
            <div class="d-lg-none" data-role="cards">
                <div v-for="row in visibleRows" :key="keyOf(row)" class="card mb-2" :data-key="keyOf(row)"
                     :class="{ 'kk-list-table-clickable': rowClickable }" @click="onRowClick(row, $event)">
                    <div class="card-body p-3">
                        <slot name="card" :row="row" :is-selected="isSelected(row)" :selected="isSelected(row)"
                              :toggle="(checked) => toggleRow(row, checked)">
                            <div class="d-flex align-items-start gap-2 mb-1">
                                <input v-if="selectable" type="checkbox" class="form-check-input mt-1" :checked="isSelected(row)"
                                       :aria-label="t('listSelectRow')" @change="toggleRow(row, $event.target.checked)">
                                <div class="fw-semibold flex-grow-1">
                                    <slot :name="`cell-${columns[0].key}`" :row="row">{{ display(row, columns[0]) }}</slot>
                                </div>
                                <slot name="actions" :row="row"></slot>
                            </div>
                            <div class="row g-2 small">
                                <div v-for="c in columns.slice(1)" :key="c.key" class="col-6">
                                    <div class="text-muted">{{ c.label }}</div>
                                    <div><slot :name="`cell-${c.key}`" :row="row">{{ display(row, c) || '—' }}</slot></div>
                                </div>
                            </div>
                        </slot>
                    </div>
                </div>
            </div>
            <table class="table table-sm table-hover align-middle d-none d-lg-table">
                <thead class="table-light">
                    <tr>
                        <th v-if="selectable" class="kk-list-table-check">
                            <input type="checkbox" class="form-check-input" :checked="isAllSelected"
                                   :aria-label="t('listSelectAll')" @change="toggleAll($event.target.checked)">
                        </th>
                        <template v-for="c in columns" :key="c.key">
                            <th v-if="c.sortable !== false" role="button" class="text-nowrap" :class="c.class" :data-sort="c.key"
                                :aria-sort="ariaSort(c)" @click="sortBy(c.key)">
                                {{ c.label }}
                                <i v-if="currentSortKey === c.key" class="bi"
                                   :class="currentSortAsc ? 'bi-caret-up-fill' : 'bi-caret-down-fill'"></i>
                            </th>
                            <th v-else class="text-nowrap" :class="c.class">{{ c.label }}</th>
                        </template>
                        <th v-if="$slots.actions"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in visibleRows" :key="keyOf(row)" :data-key="keyOf(row)"
                        :class="{ 'kk-list-table-clickable': rowClickable }" @click="onRowClick(row, $event)">
                        <td v-if="selectable">
                            <input type="checkbox" class="form-check-input" :checked="isSelected(row)"
                                   :aria-label="t('listSelectRow')" @change="toggleRow(row, $event.target.checked)">
                        </td>
                        <td v-for="c in columns" :key="c.key" :class="c.class">
                            <slot :name="`cell-${c.key}`" :row="row">{{ display(row, c) }}</slot>
                        </td>
                        <td v-if="$slots.actions" class="text-end"><slot name="actions" :row="row"></slot></td>
                    </tr>
                </tbody>
            </table>
        </template>
        <slot v-if="!error" name="pager">
            <kk-pager v-if="pagination" :page="pagination.page || 1" :per-page="pagination.perPage || 20"
                      :count="pagination.count || 0" :url="pagination.url || ''"
                      @update:page="$emit('update:page', $event)"></kk-pager>
        </slot>
    </div>
</template>

<script>
import KkDateTimePicker from './KkDateTimePicker.vue';
import KkPager from './KkPager.vue';
import { t, texts } from '../texts.js';

const INTERACTIVE = 'a, button, input, select, textarea, label, .dropdown-menu';
let nextUid = 1;

function isEmpty(value) {
    return value === null || value === undefined || value === '';
}

/**
 * Empty values sort last whichever way the column runs; numbers sort as
 * numbers, a list as it reads, everything else in locale order.
 */
export function compare(x, y, direction) {
    const a = Array.isArray(x) ? x.join('; ') : x;
    const b = Array.isArray(y) ? y.join('; ') : y;
    if (isEmpty(a)) {
        return isEmpty(b) ? 0 : 1;
    }
    if (isEmpty(b)) {
        return -1;
    }
    if (typeof a === 'number' && typeof b === 'number') {
        return (a - b) * direction;
    }
    return String(a).localeCompare(String(b), texts().locale) * direction;
}

function defaultValue(filter) {
    if (filter.type === 'select') {
        return filter.multiple ? [] : '';
    }
    if (filter.type === 'date_range') {
        return { from: '', to: '' };
    }
    return '';
}

function isUnset(filter, value) {
    if (filter.type === 'select' && filter.multiple) {
        return !value || value.length === 0;
    }
    if (filter.type === 'date_range') {
        return !value || (!value.from && !value.to);
    }
    return isEmpty(value);
}

export default {
    name: 'KkListTable',
    components: { KkDateTimePicker, KkPager },
    props: {
        rows: { type: Array, default: () => [] },
        rowKey: { type: String, default: 'id' },
        columns: { type: Array, default: () => [] },
        filters: { type: Array, default: () => [] },
        filterValues: { type: Object, default: () => ({}) },
        sortKey: { type: String, default: '' },
        sortAsc: { type: Boolean, default: true },
        isServerSorted: { type: Boolean, default: false },
        pagination: { type: Object, default: null },
        selectable: { type: Boolean, default: false },
        selected: { type: Array, default: () => [] },
        rowClickable: { type: Boolean, default: false },
        loading: { type: Boolean, default: false },
        error: { type: String, default: '' },
        emptyText: { type: String, default: () => t('listEmpty') },
        countLabel: { type: Function, default: null },
    },
    emits: [
        'update:filterValues', 'update:sortKey', 'update:sortAsc', 'update:selected', 'update:page',
        'row-click', 'filter-commit',
    ],
    data() {
        return {
            uid: nextUid++,
            values: { ...this.filterValues },
            currentSortKey: this.sortKey,
            currentSortAsc: this.sortAsc,
            currentSelected: this.selected.slice(),
        };
    },
    watch: {
        filterValues(value) { this.values = { ...value }; },
        sortKey(value) { this.currentSortKey = value; },
        sortAsc(value) { this.currentSortAsc = value; },
        selected(value) { this.currentSelected = value.slice(); },
    },
    computed: {
        sortableColumns() {
            return this.columns.filter((column) => column.sortable !== false);
        },
        visibleRows() {
            const rows = this.rows.filter((row) => this.filters.every((filter) => {
                if (typeof filter.match !== 'function') {
                    return true;
                }
                const value = this.valueOf(filter);
                return isUnset(filter, value) || filter.match(row, value);
            }));
            const column = this.columns.find((c) => c.key === this.currentSortKey);
            if (!column || this.isServerSorted) {
                return rows;
            }
            const direction = this.currentSortAsc ? 1 : -1;
            const read = typeof column.sortValue === 'function' ? column.sortValue : (row) => row[column.key];
            return rows.slice().sort((a, b) => compare(read(a), read(b), direction));
        },
        isAllSelected() {
            return this.visibleRows.length > 0 && this.visibleRows.every((row) => this.isSelected(row));
        },
    },
    methods: {
        t,
        keyOf(row) {
            return row[this.rowKey];
        },
        // A date range has two fields and names them by aria-label, so its
        // label points at nothing.
        controlId(filter) {
            if (filter.type === 'date_range') {
                return null;
            }
            return filter.id || `kk-list-table-${this.uid}-${filter.key}`;
        },
        valueOf(filter) {
            const value = this.values[filter.key];
            return value === undefined ? defaultValue(filter) : value;
        },
        setValue(key, value) {
            const next = { ...this.values, [key]: value };
            this.values = next;
            this.$emit('update:filterValues', next);
        },
        isChecked(filter, option) {
            return this.valueOf(filter).includes(option.value);
        },
        toggleOption(filter, option, isChecked) {
            let current = this.valueOf(filter).filter((v) => v !== option.value);
            if (isChecked) {
                // Keep the options' own order, whatever order they were ticked in.
                const picked = [...current, option.value];
                current = filter.options.map((o) => o.value).filter((v) => picked.includes(v));
            }
            this.setValue(filter.key, current);
            this.commit(filter);
        },
        setSelect(filter, raw) {
            const option = (filter.options || []).find((o) => String(o.value) === raw);
            this.setValue(filter.key, option ? option.value : '');
            this.commit(filter);
        },
        setRange(filter, part, value) {
            this.setValue(filter.key, { ...this.valueOf(filter), [part]: value || '' });
            this.commit(filter);
        },
        commit(filter) {
            this.$emit('filter-commit', filter.key, this.valueOf(filter));
        },
        summary(filter) {
            const value = this.valueOf(filter);
            if (!value.length) {
                return t('listAll');
            }
            // Every chosen value is named, so the selection is never hidden
            // behind a count; a long one is cut by CSS and shown whole in the
            // title. A value whose option has gone is named by itself rather
            // than dropped, since it still narrows the rows.
            return value.map((v) => {
                const option = filter.options.find((o) => o.value === v);
                return option ? option.label : String(v);
            }).join(', ');
        },
        sortBy(key) {
            const isAsc = this.currentSortKey === key ? !this.currentSortAsc : true;
            this.currentSortKey = key;
            this.currentSortAsc = isAsc;
            this.$emit('update:sortKey', key);
            this.$emit('update:sortAsc', isAsc);
        },
        setSortKey(key) {
            this.currentSortKey = key;
            this.$emit('update:sortKey', key);
        },
        flipSort() {
            this.currentSortAsc = !this.currentSortAsc;
            this.$emit('update:sortAsc', this.currentSortAsc);
        },
        ariaSort(column) {
            if (this.currentSortKey !== column.key) {
                return 'none';
            }
            return this.currentSortAsc ? 'ascending' : 'descending';
        },
        display(row, column) {
            const value = row[column.key];
            if (Array.isArray(value)) {
                return value.join('; ');
            }
            return isEmpty(value) ? '' : value;
        },
        isSelected(row) {
            return this.currentSelected.includes(this.keyOf(row));
        },
        toggleRow(row, isChecked) {
            const key = this.keyOf(row);
            const next = this.currentSelected.filter((k) => k !== key);
            if (isChecked) {
                next.push(key);
            }
            this.currentSelected = next;
            this.$emit('update:selected', next);
        },
        toggleAll(isChecked) {
            const next = isChecked ? this.visibleRows.map((row) => this.keyOf(row)) : [];
            this.currentSelected = next;
            this.$emit('update:selected', next);
        },
        onRowClick(row, event) {
            if (!this.rowClickable || event.target.closest?.(INTERACTIVE)) {
                return;
            }
            this.$emit('row-click', row);
        },
    },
};
</script>
