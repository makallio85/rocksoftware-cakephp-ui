/**
 * <kk-list-table> — the one list table. Every screen that shows records as
 * rows uses it: a sortable table on desktop, one card per row below
 * Bootstrap `lg`, and the filters above it.
 *
 * Loaded by the default layout when the `listTable` component is on (it needs
 * the application's Vue 3 as `window.Vue`). A Vue app registers it:
 *   app.component('kk-list-table', window.KkListTable)
 * The host registers `kk-date-time-picker` too when it declares a date_range
 * filter, as every screen with a date field does.
 *
 *   <kk-list-table :rows="leases" :columns="columns" :filters="filters"
 *                  v-model:filter-values="filterValues" v-model:sort-key="sortKey"
 *                  v-model:sort-asc="sortAsc" row-clickable @row-click="open">
 *       <template #cell-customer="{ row }">…</template>
 *       <template #card="{ row }">…</template>
 *       <template #actions="{ row }">…kebab…</template>
 *   </kk-list-table>
 *
 * Props:
 *   rows          the records
 *   rowKey        the field that identifies a row ('id')
 *   columns       [{ key, label, sortable = true, sortValue?(row), class? }]
 *   filters       [{ key, label, type: 'select'|'text'|'date_range', options?: [{value, label}],
 *                    multiple?, placeholder?, id?, match?(row, value) }]
 *                 A filter with `match` narrows the rows here. One without it is the
 *                 server's: the table shows it and reports its value, and the host
 *                 reloads its rows.
 *   filterValues  { key: value } — multiple select: Array; single select: value or '';
 *                 text: String; date_range: { from: 'YYYY-MM-DD' | '', to: … }
 *   sortKey, sortAsc
 *   selectable, selected (row keys)
 *   rowClickable  a click on a row (not on a link, button or field in it) emits row-click
 *   loading, error, emptyText, countLabel(n)
 * Slots: cell-<key> { row }, card { row, selected, toggle }, actions { row },
 *        bulk { selected }.
 */
window.KkListTable = (function () {
    'use strict';

    // The texts in the page's language, from the layout's `kk-ui-texts`
    // block; English when the block is missing.
    var T = (function (defaults) {
        var el = document.getElementById('kk-ui-texts');
        var texts = {};
        try {
            texts = el ? JSON.parse(el.textContent) : {};
        } catch (e) {
            texts = {};
        }
        return Object.assign({}, defaults, texts);
    })({
        listEmpty: 'No rows match the filters.',
        listAll: 'All',
        listNoneMeansAll: 'None selected = all',
        listFrom: 'From',
        listTo: 'To',
        listOrder: 'Order',
        listAscending: 'Ascending',
        listDescending: 'Descending',
        listSelectRow: 'Select row',
        listSelectAll: 'Select all',
        locale: document.documentElement.lang || 'en',
    });

    var INTERACTIVE = 'a, button, input, select, textarea, label, .dropdown-menu';
    var nextUid = 1;

    function isEmpty(value) {
        return value === null || value === undefined || value === '';
    }

    // Empty values sort last whichever way the column runs; numbers sort as
    // numbers, a list as it reads, everything else in the page language's order.
    function compare(x, y, direction) {
        if (Array.isArray(x)) { x = x.join('; '); }
        if (Array.isArray(y)) { y = y.join('; '); }
        if (isEmpty(x)) { return isEmpty(y) ? 0 : 1; }
        if (isEmpty(y)) { return -1; }
        if (typeof x === 'number' && typeof y === 'number') {
            return (x - y) * direction;
        }
        return String(x).localeCompare(String(y), T.locale) * direction;
    }

    function defaultValue(filter) {
        if (filter.type === 'select') { return filter.multiple ? [] : ''; }
        if (filter.type === 'date_range') { return { from: '', to: '' }; }
        return '';
    }

    function isUnset(filter, value) {
        if (filter.type === 'select' && filter.multiple) { return !value || value.length === 0; }
        if (filter.type === 'date_range') { return !value || (!value.from && !value.to); }
        return isEmpty(value);
    }

    return {
        name: 'KkListTable',
        props: {
            rows: { type: Array, default: function () { return []; } },
            rowKey: { type: String, default: 'id' },
            columns: { type: Array, default: function () { return []; } },
            filters: { type: Array, default: function () { return []; } },
            filterValues: { type: Object, default: function () { return {}; } },
            sortKey: { type: String, default: '' },
            sortAsc: { type: Boolean, default: true },
            selectable: { type: Boolean, default: false },
            selected: { type: Array, default: function () { return []; } },
            rowClickable: { type: Boolean, default: false },
            loading: { type: Boolean, default: false },
            error: { type: String, default: '' },
            emptyText: { type: String, default: function () { return T.listEmpty; } },
            countLabel: { type: Function, default: null },
        },
        emits: ['update:filterValues', 'update:sortKey', 'update:sortAsc', 'update:selected', 'row-click'],
        data: function () {
            return {
                uid: nextUid++,
                values: Object.assign({}, this.filterValues),
                currentSortKey: this.sortKey,
                currentSortAsc: this.sortAsc,
                currentSelected: this.selected.slice(),
            };
        },
        watch: {
            filterValues: function (value) { this.values = Object.assign({}, value); },
            sortKey: function (value) { this.currentSortKey = value; },
            sortAsc: function (value) { this.currentSortAsc = value; },
            selected: function (value) { this.currentSelected = value.slice(); },
        },
        computed: {
            t: function () {
                return T;
            },
            sortableColumns: function () {
                return this.columns.filter(function (column) { return column.sortable !== false; });
            },
            visibleRows: function () {
                var self = this;
                var rows = this.rows.filter(function (row) {
                    return self.filters.every(function (filter) {
                        if (typeof filter.match !== 'function') { return true; }
                        var value = self.valueOf(filter);
                        return isUnset(filter, value) || filter.match(row, value);
                    });
                });
                var column = this.columns.find(function (c) { return c.key === self.currentSortKey; });
                if (!column) { return rows; }
                var direction = this.currentSortAsc ? 1 : -1;
                var read = typeof column.sortValue === 'function'
                    ? column.sortValue
                    : function (row) { return row[column.key]; };
                return rows.slice().sort(function (a, b) { return compare(read(a), read(b), direction); });
            },
            allSelected: function () {
                var self = this;
                return this.visibleRows.length > 0 && this.visibleRows.every(function (row) {
                    return self.isSelected(row);
                });
            },
        },
        methods: {
            keyOf: function (row) { return row[this.rowKey]; },
            // A date range has two fields and names them by aria-label, so its
            // label points at nothing.
            controlId: function (filter) {
                if (filter.type === 'date_range') { return null; }
                return filter.id || ('kk-list-table-' + this.uid + '-' + filter.key);
            },
            valueOf: function (filter) {
                var value = this.values[filter.key];
                return value === undefined ? defaultValue(filter) : value;
            },
            setValue: function (key, value) {
                var next = Object.assign({}, this.values);
                next[key] = value;
                this.values = next;
                this.$emit('update:filterValues', next);
            },
            isChecked: function (filter, option) {
                return this.valueOf(filter).indexOf(option.value) !== -1;
            },
            toggleOption: function (filter, option, checked) {
                var current = this.valueOf(filter).filter(function (v) { return v !== option.value; });
                if (checked) {
                    // Keep the options' own order, whatever order they were ticked in.
                    var picked = current.concat([option.value]);
                    current = filter.options
                        .map(function (o) { return o.value; })
                        .filter(function (v) { return picked.indexOf(v) !== -1; });
                }
                this.setValue(filter.key, current);
            },
            setSelect: function (filter, raw) {
                var option = (filter.options || []).find(function (o) { return String(o.value) === raw; });
                this.setValue(filter.key, option ? option.value : '');
            },
            setRange: function (filter, part, value) {
                var next = Object.assign({}, this.valueOf(filter));
                next[part] = value || '';
                this.setValue(filter.key, next);
            },
            summary: function (filter) {
                var value = this.valueOf(filter);
                if (!value.length) { return T.listAll; }
                // Every chosen value is named, so the selection is never hidden
                // behind a count; a long one is cut by CSS and shown whole in the
                // title. A value whose option has gone is named by itself rather
                // than dropped, since it still narrows the rows.
                return value.map(function (v) {
                    var option = filter.options.find(function (o) { return o.value === v; });
                    return option ? option.label : String(v);
                }).join(', ');
            },
            sortBy: function (key) {
                var asc = this.currentSortKey === key ? !this.currentSortAsc : true;
                this.currentSortKey = key;
                this.currentSortAsc = asc;
                this.$emit('update:sortKey', key);
                this.$emit('update:sortAsc', asc);
            },
            setSortKey: function (key) {
                this.currentSortKey = key;
                this.$emit('update:sortKey', key);
            },
            flipSort: function () {
                this.currentSortAsc = !this.currentSortAsc;
                this.$emit('update:sortAsc', this.currentSortAsc);
            },
            ariaSort: function (column) {
                if (this.currentSortKey !== column.key) { return 'none'; }
                return this.currentSortAsc ? 'ascending' : 'descending';
            },
            display: function (row, column) {
                var value = row[column.key];
                if (Array.isArray(value)) { return value.join('; '); }
                return isEmpty(value) ? '' : value;
            },
            isSelected: function (row) {
                return this.currentSelected.indexOf(this.keyOf(row)) !== -1;
            },
            toggleRow: function (row, checked) {
                var key = this.keyOf(row);
                var next = this.currentSelected.filter(function (k) { return k !== key; });
                if (checked) { next.push(key); }
                this.currentSelected = next;
                this.$emit('update:selected', next);
            },
            toggleAll: function (checked) {
                var self = this;
                var next = checked ? this.visibleRows.map(function (row) { return self.keyOf(row); }) : [];
                this.currentSelected = next;
                this.$emit('update:selected', next);
            },
            onRowClick: function (row, event) {
                if (!this.rowClickable) { return; }
                if (event.target.closest && event.target.closest(INTERACTIVE)) { return; }
                this.$emit('row-click', row);
            },
        },
        template: ''
            + '<div class="kk-list-table">'
            + '  <div v-if="filters.length || sortableColumns.length" class="row g-2 align-items-end mb-2">'
            + '    <div v-for="f in filters" :key="f.key" :data-filter="f.key"'
            + '         :class="f.type === \'text\' ? \'col-12 col-lg-3\' : \'col-12 col-sm-6 col-lg-auto\'">'
            + '      <label class="form-label small text-muted mb-1" :for="controlId(f)">{{ f.label }}</label>'
            + '      <div v-if="f.type === \'select\' && f.multiple" class="dropdown">'
            + '        <button type="button" :id="controlId(f)" class="btn btn-sm btn-outline-secondary dropdown-toggle w-100 text-start kk-list-table-picker"'
            + '                data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" :title="summary(f)">{{ summary(f) }}</button>'
            + '        <div class="dropdown-menu shadow-sm kk-list-table-options">'
            + '          <label v-for="o in f.options" :key="String(o.value)" class="dropdown-item d-flex align-items-center gap-2 small">'
            + '            <input type="checkbox" class="form-check-input mt-0" :value="o.value" :checked="isChecked(f, o)"'
            + '                   @change="toggleOption(f, o, $event.target.checked)">'
            + '            <span>{{ o.label }}</span>'
            + '          </label>'
            + '          <div class="dropdown-divider"></div>'
            + '          <div class="px-3 small text-muted">{{ t.listNoneMeansAll }}</div>'
            + '        </div>'
            + '      </div>'
            + '      <select v-else-if="f.type === \'select\'" :id="controlId(f)" class="form-select form-select-sm"'
            + '              :value="String(valueOf(f))" @change="setSelect(f, $event.target.value)">'
            + '        <option value="">{{ t.listAll }}</option>'
            + '        <option v-for="o in f.options" :key="String(o.value)" :value="String(o.value)">{{ o.label }}</option>'
            + '      </select>'
            + '      <input v-else-if="f.type === \'text\'" :id="controlId(f)" type="search" class="form-control form-control-sm"'
            + '             :placeholder="f.placeholder || \'\'" :value="valueOf(f)" @input="setValue(f.key, $event.target.value)">'
            + '      <div v-else-if="f.type === \'date_range\'" class="d-flex align-items-center gap-1">'
            + '        <div data-part="from" class="flex-fill"><kk-date-time-picker mode="date" size="sm" :model-value="valueOf(f).from"'
            + '             @update:model-value="setRange(f, \'from\', $event)" :aria-label="t.listFrom"></kk-date-time-picker></div>'
            + '        <span class="text-muted">–</span>'
            + '        <div data-part="to" class="flex-fill"><kk-date-time-picker mode="date" size="sm" :model-value="valueOf(f).to"'
            + '             @update:model-value="setRange(f, \'to\', $event)" :aria-label="t.listTo"></kk-date-time-picker></div>'
            + '      </div>'
            + '    </div>'
            // Cards have no headers to click, so a phone sorts here.
            + '    <div v-if="sortableColumns.length" class="col-12 d-lg-none">'
            + '      <label class="form-label small text-muted mb-1">{{ t.listOrder }}</label>'
            + '      <div class="input-group input-group-sm">'
            + '        <select data-role="sort" class="form-select form-select-sm" :value="currentSortKey" @change="setSortKey($event.target.value)">'
            + '          <option v-for="c in sortableColumns" :key="c.key" :value="c.key">{{ c.label }}</option>'
            + '        </select>'
            + '        <button type="button" class="btn btn-outline-secondary" :title="currentSortAsc ? t.listAscending : t.listDescending"'
            + '                :aria-label="currentSortAsc ? t.listAscending : t.listDescending" @click="flipSort">'
            + '          <i class="bi" :class="currentSortAsc ? \'bi-sort-up\' : \'bi-sort-down\'"></i>'
            + '        </button>'
            + '      </div>'
            + '    </div>'
            + '  </div>'
            + '  <div v-if="selectable && currentSelected.length && $slots.bulk" data-role="bulk"'
            + '       class="d-flex flex-wrap align-items-center gap-2 border rounded bg-light p-2 mb-2">'
            + '    <slot name="bulk" :selected="currentSelected"></slot>'
            + '  </div>'
            + '  <div v-if="loading && !rows.length" class="text-center py-5"><div class="spinner-border"></div></div>'
            + '  <div v-else-if="error" class="alert alert-danger small">{{ error }}</div>'
            + '  <div v-else-if="!visibleRows.length" class="text-muted py-4" data-role="empty">{{ emptyText }}</div>'
            + '  <template v-else>'
            + '    <div v-if="countLabel" class="small text-muted mb-2" data-role="count">{{ countLabel(visibleRows.length) }}</div>'
            + '    <div class="d-lg-none" data-role="cards">'
            + '      <div v-for="row in visibleRows" :key="keyOf(row)" class="card mb-2" :data-key="keyOf(row)"'
            + '           :class="{ \'kk-list-table-clickable\': rowClickable }" @click="onRowClick(row, $event)">'
            + '        <div class="card-body p-3">'
            + '          <slot name="card" :row="row" :selected="isSelected(row)" :toggle="function (checked) { toggleRow(row, checked); }">'
            + '            <div class="d-flex align-items-start gap-2 mb-1">'
            + '              <input v-if="selectable" type="checkbox" class="form-check-input mt-1" :checked="isSelected(row)"'
            + '                     :aria-label="t.listSelectRow" @change="toggleRow(row, $event.target.checked)">'
            + '              <div class="fw-semibold flex-grow-1"><slot :name="\'cell-\' + columns[0].key" :row="row">{{ display(row, columns[0]) }}</slot></div>'
            + '              <slot name="actions" :row="row"></slot>'
            + '            </div>'
            + '            <div class="row g-2 small">'
            + '              <div v-for="c in columns.slice(1)" :key="c.key" class="col-6">'
            + '                <div class="text-muted">{{ c.label }}</div>'
            + '                <div><slot :name="\'cell-\' + c.key" :row="row">{{ display(row, c) || \'—\' }}</slot></div>'
            + '              </div>'
            + '            </div>'
            + '          </slot>'
            + '        </div>'
            + '      </div>'
            + '    </div>'
            + '    <table class="table table-sm table-hover align-middle d-none d-lg-table">'
            + '      <thead class="table-light">'
            + '        <tr>'
            + '          <th v-if="selectable" style="width:1px"><input type="checkbox" class="form-check-input" :checked="allSelected"'
            + '              :aria-label="t.listSelectAll" @change="toggleAll($event.target.checked)"></th>'
            + '          <template v-for="c in columns" :key="c.key">'
            + '            <th v-if="c.sortable !== false" role="button" class="text-nowrap" :class="c.class" :data-sort="c.key"'
            + '                :aria-sort="ariaSort(c)" @click="sortBy(c.key)">'
            + '              {{ c.label }}'
            + '              <i v-if="currentSortKey === c.key" class="bi" :class="currentSortAsc ? \'bi-caret-up-fill\' : \'bi-caret-down-fill\'"></i>'
            + '            </th>'
            + '            <th v-else class="text-nowrap" :class="c.class">{{ c.label }}</th>'
            + '          </template>'
            + '          <th v-if="$slots.actions"></th>'
            + '        </tr>'
            + '      </thead>'
            + '      <tbody>'
            + '        <tr v-for="row in visibleRows" :key="keyOf(row)" :data-key="keyOf(row)"'
            + '            :class="{ \'kk-list-table-clickable\': rowClickable }" @click="onRowClick(row, $event)">'
            + '          <td v-if="selectable"><input type="checkbox" class="form-check-input" :checked="isSelected(row)"'
            + '              :aria-label="t.listSelectRow" @change="toggleRow(row, $event.target.checked)"></td>'
            + '          <td v-for="c in columns" :key="c.key" :class="c.class">'
            + '            <slot :name="\'cell-\' + c.key" :row="row">{{ display(row, c) }}</slot>'
            + '          </td>'
            + '          <td v-if="$slots.actions" class="text-end"><slot name="actions" :row="row"></slot></td>'
            + '        </tr>'
            + '      </tbody>'
            + '    </table>'
            + '  </template>'
            + '</div>',
    };
})();
