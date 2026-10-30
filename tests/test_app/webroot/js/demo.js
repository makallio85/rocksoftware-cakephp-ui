/**
 * The components page's buttons that show toasts and ask a script's
 * question (window.KkConfirm.ask), and the expand toggle's event.
 */
(function () {
    'use strict';

    document.addEventListener('kk:expand-toggle', function (event) {
        var state = document.getElementById('demo-expand-state');
        if (state) {
            state.textContent = 'kk:expand-toggle: expanded = ' + event.detail.expanded;
        }
    });

    document.addEventListener('click', function (event) {
        var button = event.target instanceof Element ? event.target.closest('[data-demo-toast]') : null;
        if (button) {
            var kind = button.getAttribute('data-demo-toast');
            var messages = {
                success: { title: 'Saved.', text: 'The provider settings are stored.' },
                error: { title: 'Could not send.', text: 'The gateway did not answer.' },
                warning: 'Check the reply address.',
                info: 'Refreshing the list…'
            };
            window.appBanner[kind](messages[kind]);
            return;
        }
        var asker = event.target instanceof Element ? event.target.closest('[data-demo-ask]') : null;
        if (asker) {
            window.KkConfirm.ask({
                title: 'Send to every recipient?',
                message: '47 messages will be queued.',
                button: 'Send',
                tone: 'primary'
            }).then(function (isConfirmed) {
                window.appBanner[isConfirmed ? 'success' : 'info'](isConfirmed ? 'Queued.' : 'Nothing was sent.');
            });
        }
    });
}());

/**
 * The formats table's script column (window.KkFormat), and a Vue app built
 * with a render function, as an application with its own Vue mounts the
 * components: all rows in the page, sorted, filtered and paged here.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-demo-format]').forEach(function (cell) {
            var value = cell.getAttribute('data-value');
            var f = window.KkFormat;
            var text = {
                date: function () { return f.formatDate(value); },
                dateTime: function () { return f.formatDateTime(value); },
                number: function () { return f.formatNumber(value, 2); },
                money: function () { return f.formatMoney(value); },
                percent: function () { return f.formatPercent(value, 1); }
            }[cell.getAttribute('data-demo-format')]();
            cell.textContent = text;
        });

        var host = document.querySelector('[data-demo-vue-app]');
        if (!host || !window.Vue) {
            return;
        }
        var h = window.Vue.h;
        var names = ['North', 'South', 'East', 'West', 'Harbour', 'Hill', 'River', 'Forest', 'Lake', 'Meadow', 'Valley', 'Bay'];
        var providers = names.map(function (name, i) {
            return {
                id: i + 1,
                name: name + ' Example Gateway',
                channel: i % 3 === 0 ? 'SMS' : 'Email',
                sentToday: (i * 137) % 900,
                isActive: i % 4 !== 3
            };
        });
        var PER_PAGE = 5;
        var app = window.Vue.createApp({
            data: function () {
                return { filterValues: {}, sortKey: 'name', sortAsc: true, selected: [], page: 1 };
            },
            computed: {
                matching: function () {
                    var channel = this.filterValues.channel || '';
                    var rows = providers.filter(function (row) { return !channel || row.channel === channel; });
                    var key = this.sortKey;
                    var direction = this.sortAsc ? 1 : -1;
                    return rows.slice().sort(function (a, b) {
                        return (a[key] > b[key] ? 1 : (a[key] < b[key] ? -1 : 0)) * direction;
                    });
                },
                pageRows: function () {
                    return this.matching.slice((this.page - 1) * PER_PAGE, this.page * PER_PAGE);
                }
            },
            render: function () {
                var self = this;
                return h(window.KkListTable, {
                    rows: this.pageRows,
                    columns: [
                        { key: 'name', label: 'Provider' },
                        { key: 'channel', label: 'Channel' },
                        { key: 'sentToday', label: 'Sent today', class: 'text-end' },
                        { key: 'isActive', label: 'Status', sortable: false }
                    ],
                    filters: [{
                        key: 'channel', label: 'Channel', type: 'select',
                        options: [{ value: 'Email', label: 'Email' }, { value: 'SMS', label: 'SMS' }]
                    }],
                    filterValues: this.filterValues,
                    sortKey: this.sortKey,
                    sortAsc: this.sortAsc,
                    isServerSorted: true,
                    selectable: true,
                    selected: this.selected,
                    pagination: { page: this.page, perPage: PER_PAGE, count: this.matching.length },
                    'onUpdate:filterValues': function (v) { self.filterValues = v; self.page = 1; },
                    'onUpdate:sortKey': function (v) { self.sortKey = v; },
                    'onUpdate:sortAsc': function (v) { self.sortAsc = v; },
                    'onUpdate:selected': function (v) { self.selected = v; },
                    'onUpdate:page': function (v) { self.page = v; }
                }, {
                    'cell-sentToday': function (props) {
                        return window.KkFormat.formatNumber(props.row.sentToday);
                    },
                    'cell-isActive': function (props) {
                        return h('span', {
                            class: 'kk-badge ' + (props.row.isActive ? 'kk-badge-ok' : 'kk-badge-muted')
                        }, props.row.isActive ? 'Active' : 'Paused');
                    },
                    bulk: function (props) {
                        return [
                            h('span', { class: 'small text-body-secondary' }, props.selected.length + ' chosen'),
                            h('button', {
                                type: 'button',
                                class: 'btn btn-sm btn-outline-secondary',
                                onClick: function () {
                                    window.appBanner.info(props.selected.length + ' providers paused (demo).');
                                    self.selected = [];
                                }
                            }, 'Pause')
                        ];
                    }
                });
            }
        });
        app.mount(host);
    });
}());
