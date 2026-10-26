/**
 * The components on a server-rendered page with no Vue app of its own and no
 * template compiler: the page writes plain markup with `data-kk-*`
 * attributes and these mounts turn it into the components, with render
 * functions only, so nothing is compiled in the browser and the page works
 * under `script-src 'self'`. The PHP elements `ui/list_table`, `ui/modal`,
 * `ui/date_time_picker` and `ui/file_drop` write the markup.
 *
 *   <div data-kk-modal="reject-12" data-title="Reject" hidden>
 *       …body markup…
 *       <div data-kk-modal-footer>…buttons…</div>
 *   </div>
 *   <button type="button" data-kk-modal-open="reject-12">Reject</button>
 *
 *   <input type="text" name="send_after" value="2026-10-27 08:00" data-kk-date-time-picker>
 *   <input type="file" name="attachment" data-kk-file-drop accept=".pdf">
 *
 *   <div data-kk-list-table>
 *       <script type="application/json" data-kk-list-table-config>{"rows": […], "columns": […]}</script>
 *   </div>
 *
 * The server's markup stays live: a modal's body and footer, a list's bulk
 * bar and pager are the page's own DOM nodes, moved into the component while
 * it shows and back when it hides, so forms, CSRF fields and event listeners
 * keep working.
 */
import { createApp, h, reactive } from 'vue';
import KkModal from './components/KkModal.vue';
import KkListTable from './components/KkListTable.vue';
import KkDateTimePicker from './components/KkDateTimePicker.vue';
import KkFileDrop from './components/KkFileDrop.vue';

const MOUNTED = '__kkUiMounted';

/** Markup modals by name: their reactive state and the nodes they show. */
const modals = {};

/**
 * Shows some of the page's own DOM nodes inside a component and puts them
 * back where they live (`home`) when the component goes away.
 */
export const KkDomSlot = {
    name: 'KkDomSlot',
    props: {
        nodes: { type: Array, required: true },
        home: { type: Object, required: true },
        tag: { type: String, default: 'div' },
        cls: { type: String, default: '' },
    },
    mounted() {
        this.nodes.forEach((node) => this.$el.appendChild(node));
    },
    beforeUnmount() {
        this.nodes.forEach((node) => this.home.appendChild(node));
    },
    render() {
        return h(this.tag, { class: this.cls || null });
    },
};

function isTrue(value, fallback) {
    if (value === undefined || value === null || value === '') {
        return fallback;
    }
    return value !== 'false' && value !== '0';
}

/** A container for an app, placed right after the element it replaces. */
function hostAfter(el, className) {
    const host = document.createElement('div');
    if (className) {
        host.className = className;
    }
    el.parentNode.insertBefore(host, el.nextSibling);
    return host;
}

function fire(el, type) {
    el.dispatchEvent(new Event(type, { bubbles: true }));
}

/**
 * A markup modal: `[data-kk-modal="<name>"]`, `data-title`, `data-size`,
 * `data-closable`, an optional `[data-kk-modal-footer]` child.
 *
 * @param {HTMLElement} el
 */
export function mountModal(el) {
    const name = el.getAttribute('data-kk-modal');
    const footer = el.querySelector(':scope > [data-kk-modal-footer]');
    const bodyNodes = Array.from(el.childNodes).filter((node) => node !== footer);
    const footerNodes = footer ? Array.from(footer.childNodes) : [];
    const state = reactive({ open: false, title: el.getAttribute('data-title') || '' });
    const app = createApp({
        render() {
            const slots = { default: () => h(KkDomSlot, { nodes: bodyNodes, home: el }) };
            if (footer) {
                slots.footer = () => h(KkDomSlot, { nodes: footerNodes, home: footer, tag: 'div', cls: 'kk-modal-footer-nodes' });
            }
            return h(KkModal, {
                open: state.open,
                title: state.title,
                size: el.getAttribute('data-size') || 'md',
                closable: isTrue(el.getAttribute('data-closable'), true),
                'onUpdate:open': (isOpen) => { state.open = isOpen; },
            }, slots);
        },
    });
    app.mount(hostAfter(el, 'kk-modal-host'));
    modals[name] = { state, nodes: bodyNodes.concat(footerNodes) };
    return app;
}

/**
 * Opens a markup modal by name. Returns false when the page has none of that name.
 *
 * @param {string} name
 * @returns {boolean}
 */
export function openModal(name) {
    if (!modals[name]) {
        return false;
    }
    modals[name].state.open = true;
    return true;
}

/**
 * Closes a markup modal by name, or every one.
 *
 * @param {string} [name]
 */
export function closeModal(name) {
    Object.keys(modals).forEach((key) => {
        if (name === undefined || key === name) {
            modals[key].state.open = false;
        }
    });
}

/**
 * A date picker over an `<input data-kk-date-time-picker>`: the input turns
 * hidden and keeps the value the form posts ('Y-m-d H:i', 'Y-m-d', 'H:i' or
 * 'Y-m'); `data-mode`, `data-time-step`, `data-size`, `data-min`, `data-max`,
 * `data-clearable`. The input's id, placeholder, aria-describedby and
 * classes (such as `is-invalid`) go to the visible field. A change fires
 * `input` and `change` on the input.
 *
 * @param {HTMLInputElement} input
 */
export function mountDateTimePicker(input) {
    const state = reactive({ value: input.value });
    const id = input.id;
    input.removeAttribute('id');
    const extraClass = String(input.className || '').split(/\s+/)
        .filter((c) => c && c !== 'form-control' && !c.startsWith('form-control-')).join(' ');
    const props = {
        mode: input.getAttribute('data-mode') || 'datetime',
        timeStep: Number(input.getAttribute('data-time-step')) || 30,
        size: input.getAttribute('data-size') || '',
        min: input.getAttribute('data-min') || '',
        max: input.getAttribute('data-max') || '',
        clearable: isTrue(input.getAttribute('data-clearable'), !input.required),
        disabled: input.disabled,
        placeholder: input.getAttribute('placeholder') || '',
        inputId: id || '',
        ariaLabel: input.getAttribute('aria-label') || '',
        describedBy: input.getAttribute('aria-describedby') || '',
        inputClass: extraClass,
    };
    input.type = 'hidden';
    const app = createApp({
        render() {
            return h(KkDateTimePicker, {
                ...props,
                modelValue: state.value,
                'onUpdate:modelValue': (value) => {
                    state.value = value || '';
                    input.value = state.value;
                    fire(input, 'input');
                    fire(input, 'change');
                },
            });
        },
    });
    app.mount(hostAfter(input, ''));
    return app;
}

/**
 * A drop target over an `<input type="file" data-kk-file-drop>`: the input
 * stays in the form, hidden, and holds the files chosen, so a plain form
 * post sends them; `accept`, `multiple`, `disabled`, `data-max-bytes`,
 * `data-text`, `data-hint`. A change fires `change` on the input.
 *
 * @param {HTMLInputElement} input
 */
export function mountFileDrop(input) {
    const state = reactive({ files: [] });
    const id = input.id;
    input.removeAttribute('id');
    input.hidden = true;
    const props = {
        accept: input.getAttribute('accept') || '',
        multiple: input.multiple,
        maxBytes: Number(input.getAttribute('data-max-bytes')) || 0,
        text: input.getAttribute('data-text') || '',
        hint: input.getAttribute('data-hint') || '',
        inputId: id || '',
        disabled: input.disabled,
    };
    const app = createApp({
        render() {
            return h(KkFileDrop, {
                ...props,
                modelValue: state.files,
                'onUpdate:modelValue': (files) => {
                    state.files = files;
                    if (typeof DataTransfer !== 'undefined') {
                        const transfer = new DataTransfer();
                        files.forEach((file) => transfer.items.add(file));
                        input.files = transfer.files;
                    }
                    fire(input, 'change');
                },
            });
        },
    });
    app.mount(hostAfter(input, ''));
    return app;
}

/** The text of a row a text filter searches: its `_search`, else its plain columns. */
function searchText(row, columns) {
    if (row._search !== undefined) {
        return String(row._search).toLowerCase();
    }
    return columns.filter((c) => !c.isHtml).map((c) => {
        const value = row[c.key];
        return Array.isArray(value) ? value.join(' ') : String(value ?? '');
    }).join(' ').toLowerCase();
}

/**
 * A filter's `match` from its JSON description: `matchField` for a select
 * (the field equals, or as a list holds, a picked value) or a date range (the
 * field's day is inside it); a text filter searches the row.
 */
export function matcherOf(filter, columns) {
    if (filter.type === 'text') {
        return (row, value) => searchText(row, columns).includes(String(value).toLowerCase());
    }
    const field = filter.matchField;
    if (filter.type === 'date_range') {
        return (row, value) => {
            const day = String(row[field] ?? '').slice(0, 10);
            return day !== '' && (!value.from || day >= value.from) && (!value.to || day <= value.to);
        };
    }
    return (row, value) => {
        const wanted = (Array.isArray(value) ? value : [value]).map(String);
        const actual = row[field];
        return Array.isArray(actual) ? actual.some((a) => wanted.includes(String(a))) : wanted.includes(String(actual));
    };
}

/**
 * The address after a server filter or sort changed: the query parameters
 * set (or removed when empty) and the page dropped.
 *
 * @param {string} href
 * @param {Record<string, string|string[]|null>} params
 * @returns {string}
 */
export function urlWith(href, params) {
    const url = new URL(href, 'http://localhost');
    url.searchParams.delete('page');
    Object.entries(params).forEach(([key, value]) => {
        url.searchParams.delete(key);
        url.searchParams.delete(`${key}[]`);
        if (Array.isArray(value)) {
            value.forEach((v) => url.searchParams.append(`${key}[]`, String(v)));
        } else if (value !== null && value !== undefined && value !== '') {
            url.searchParams.set(key, String(value));
        }
    });
    const query = url.searchParams.toString();
    return url.pathname + (query ? `?${query}` : '') + url.hash;
}

/** Where the page goes; replaced in tests. */
export const navigation = {
    go(url) {
        window.location.assign(url);
    },
};

/**
 * A list table from JSON: `[data-kk-list-table]` holding a
 * `<script type="application/json" data-kk-list-table-config>`, and
 * optionally `[data-kk-list-table-bulk]` (the bulk bar's markup) and
 * `[data-kk-list-table-pager]` (the server's pager, such as the
 * `ui/pagination` element). See the `ui/list_table` element for the keys.
 *
 * @param {HTMLElement} el
 */
export function mountListTable(el) {
    const configEl = el.querySelector(':scope > script[data-kk-list-table-config]');
    const config = configEl ? JSON.parse(configEl.textContent || '{}') : {};
    const columns = config.columns || [];
    const filters = (config.filters || []).map((filter) => {
        const out = { ...filter };
        if (filter.matchField || (filter.type === 'text' && !filter.param)) {
            out.match = matcherOf(filter, columns);
        }
        return out;
    });
    const bulk = el.querySelector(':scope > [data-kk-list-table-bulk]');
    const pager = el.querySelector(':scope > [data-kk-list-table-pager]');
    const state = reactive({
        filterValues: { ...(config.filterValues || {}) },
        sortKey: config.sortKey || '',
        sortAsc: config.sortAsc !== false,
        selected: (config.selected || []).slice(),
    });
    let sortPending = false;
    const reloadSorted = () => {
        if (sortPending) {
            return;
        }
        sortPending = true;
        Promise.resolve().then(() => {
            sortPending = false;
            const column = columns.find((c) => c.key === state.sortKey);
            if (!column) {
                return;
            }
            navigation.go(urlWith(window.location.href, {
                sort: column.sortField || column.key,
                direction: state.sortAsc ? 'asc' : 'desc',
            }));
        });
    };
    const onCommit = (key, value) => {
        const filter = filters.find((f) => f.key === key);
        if (!filter || filter.match) {
            return;
        }
        if (filter.type === 'date_range') {
            if (filter.paramFrom || filter.paramTo) {
                navigation.go(urlWith(window.location.href, {
                    ...(filter.paramFrom ? { [filter.paramFrom]: value.from } : {}),
                    ...(filter.paramTo ? { [filter.paramTo]: value.to } : {}),
                }));
            }
        } else if (filter.param) {
            navigation.go(urlWith(window.location.href, { [filter.param]: value }));
        }
    };
    const htmlCell = (key) => ({ row }) => h('span', { class: 'kk-list-table-html', innerHTML: String(row[key] ?? '') });
    const slots = {};
    columns.filter((c) => c.isHtml).forEach((c) => { slots[`cell-${c.key}`] = htmlCell(c.key); });
    if ((config.rows || []).some((row) => row._actions)) {
        slots.actions = ({ row }) => h('span', { class: 'kk-list-table-html', innerHTML: String(row._actions || '') });
    }
    if (bulk) {
        const nodes = Array.from(bulk.childNodes);
        slots.bulk = () => h(KkDomSlot, { nodes, home: bulk, tag: 'div', cls: 'kk-list-table-bulk-nodes' });
    }
    if (pager) {
        const nodes = Array.from(pager.childNodes);
        slots.pager = () => h(KkDomSlot, { nodes, home: pager, tag: 'div', cls: 'kk-list-table-pager' });
    }
    const app = createApp({
        render() {
            const table = h(KkListTable, {
                rows: config.rows || [],
                rowKey: config.rowKey || 'id',
                columns,
                filters,
                filterValues: state.filterValues,
                sortKey: state.sortKey,
                sortAsc: state.sortAsc,
                isServerSorted: config.isServerSorted === true,
                pagination: config.pagination || null,
                selectable: config.selectable === true,
                selected: state.selected,
                rowClickable: config.rowClickable === true,
                emptyText: config.emptyText || undefined,
                'onUpdate:filterValues': (values) => { state.filterValues = values; },
                'onUpdate:sortKey': (key) => {
                    state.sortKey = key;
                    if (config.isServerSorted) {
                        reloadSorted();
                    }
                },
                'onUpdate:sortAsc': (isAsc) => {
                    state.sortAsc = isAsc;
                    if (config.isServerSorted) {
                        reloadSorted();
                    }
                },
                'onUpdate:selected': (keys) => { state.selected = keys; },
                'onUpdate:page': (page) => {
                    navigation.go(pageUrl(window.location.href, page));
                },
                onFilterCommit: onCommit,
                onRowClick: (row) => {
                    if (row.url) {
                        navigation.go(row.url);
                    }
                },
            }, slots);
            if (!config.selectionName) {
                return table;
            }
            // The chosen rows as form fields, for the bulk bar's buttons to post.
            const inputs = state.selected.map((key) => h('input', {
                type: 'hidden',
                name: `${config.selectionName}[]`,
                value: String(key),
                form: config.selectionForm || null,
                key: `selected-${key}`,
            }));
            return [table, ...inputs];
        },
    });
    app.mount(hostAfter(el, 'kk-list-table-host'));
    return app;
}

/**
 * The address of another page of a server-paged list.
 *
 * @param {string} href
 * @param {number} page
 * @returns {string}
 */
export function pageUrl(href, page) {
    const url = new URL(href, 'http://localhost');
    url.searchParams.set('page', String(page));
    return url.pathname + `?${url.searchParams.toString()}` + url.hash;
}

const MOUNTS = [
    ['[data-kk-modal]', mountModal],
    ['[data-kk-list-table]', mountListTable],
    ['input[data-kk-date-time-picker]', mountDateTimePicker],
    ['input[data-kk-file-drop]', mountFileDrop],
];

/** The selector of every element a mount turns into a component. */
export const SELECTOR = MOUNTS.map(([selector]) => selector).join(', ');

/**
 * Mounts every component's markup under a root not mounted yet. Run at page
 * load; call it again (`window.KkUi.mount(element)`) for markup a script adds later.
 *
 * @param {ParentNode} [root]
 */
export function mountAll(root = document) {
    MOUNTS.forEach(([selector, mount]) => {
        root.querySelectorAll(selector).forEach((el) => {
            if (el[MOUNTED]) {
                return;
            }
            el[MOUNTED] = true;
            mount(el);
        });
    });
}

let isListening = false;

/** The page-wide clicks that open and close markup modals. */
export function listen() {
    if (isListening || typeof document === 'undefined') {
        return;
    }
    isListening = true;
    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) {
            return;
        }
        const opener = target.closest('[data-kk-modal-open]');
        if (opener && openModal(opener.getAttribute('data-kk-modal-open'))) {
            event.preventDefault();
            return;
        }
        const closer = target.closest('[data-kk-modal-close]');
        if (closer && closer.closest('.kk-modal')) {
            Object.keys(modals).forEach((name) => {
                if (modals[name].nodes.some((node) => node === closer || node.contains(closer))) {
                    event.preventDefault();
                    modals[name].state.open = false;
                }
            });
        }
    });
}
