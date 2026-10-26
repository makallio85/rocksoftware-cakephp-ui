<!--
<kk-date-time-picker>: the one date and time picker.

A text field that opens a menu: the calendar of @vuepic/vue-datepicker (bundled
into the plugin's build, no CDN), shown inline, with a "Time" list beside it.
One click picks a day, one click picks a time, and picking the time closes the
menu. The field shows DD.MM.YYYY HH.mm. Typing is allowed (5.3.2026 14.30 or
05.03.2026 14:30) and a typed time is kept to the minute: 14.20 stays 14.20, and
the list then shows no selected row.

The value is wall-clock time in the display time zone (the `displayTimezone`
setting, default Europe/Helsinki): the server sends and reads 'Y-m-d H:i' in
that zone, and "now" (the time a day picked without one gets) is now there, not
in the browser's zone. Styles: webroot/css/kk-date-time-picker.css.

    <kk-date-time-picker v-model="form.startsAt"></kk-date-time-picker>
    <kk-date-time-picker v-model="filters.from" mode="date" size="sm"></kk-date-time-picker>

Props:
  mode         'datetime' (default): calendar and time list
               'date': calendar only; picking a day closes the menu
               'time': time list only
               'month': month and year only
  model-type   'string' (default): the wire value the server reads,
                   'Y-m-d H:i', 'Y-m-d', 'H:i' or 'Y-m'; '' when empty
               'date': a local Date, or null when empty (datetime and date
                   modes), for screens whose state holds Dates
  time-step    minutes between the list's times (default 30)
  size         '' | 'sm' | 'lg': the Bootstrap size of the field
  clearable    shows the clear button (default true)
  min, max     the first and last day that may be picked, 'Y-m-d' (date and
               datetime modes); other days are greyed out and a typed day
               outside them is not taken
  input-class  extra classes on the field, such as 'is-invalid'
  disabled, placeholder, input-id, aria-label, described-by (the field's
               aria-describedby, such as a help text's id)
Emits `update:modelValue`.
-->
<template>
    <div class="kk-dtp" :class="size ? `kk-dtp-${size}` : ''">
        <i :class="iconClass" class="kk-dtp-icon" aria-hidden="true"></i>
        <input ref="input" type="text" class="form-control kk-dtp-input"
               :class="[sizeClass, inputClass, { 'kk-dtp-input-clearable': canClear }]"
               :id="inputId || null" :value="text" :placeholder="shownPlaceholder" :disabled="disabled"
               :aria-label="ariaLabel || null" :aria-describedby="describedBy || null" aria-haspopup="dialog" :aria-expanded="isOpen ? 'true' : 'false'"
               autocomplete="off" @click="show" @input="onType" @blur="commitTyped" @keydown="onKeydown">
        <button v-if="canClear" type="button" class="kk-dtp-clear" :title="t('pickerClear')" :aria-label="t('pickerClear')"
                @mousedown.prevent @click="clear"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        <teleport to="body">
            <div v-if="isOpen" ref="menu" class="kk-dtp-menu"
                 :class="[`kk-dtp-menu-${mode}`, { 'kk-dtp-menu-with-calendar': hasCalendar }]" :style="menuStyle"
                 role="dialog" :aria-label="menuLabel" @keydown="onMenuKeydown">
                <div v-if="hasCalendar" class="kk-dtp-calendar">
                    <vue-date-picker :model-value="calendarValue" @update:model-value="onCalendar"
                                     inline auto-apply :locale="calendarLocale" :week-start="1" :enable-time-picker="false"
                                     :month-picker="mode === 'month'" :month-change-on-scroll="false"
                                     :min-date="minDate" :max-date="maxDate"></vue-date-picker>
                </div>
                <div v-if="hasList" class="kk-dtp-time">
                    <div class="kk-dtp-time-header">{{ t('pickerTimeList') }}</div>
                    <div ref="list" class="kk-dtp-time-list">
                        <button v-for="t in times" :key="t.label" type="button" class="kk-dtp-time-item"
                                :class="{ active: t.isSelected }" :aria-pressed="t.isSelected ? 'true' : 'false'"
                                @click="pickTime(t)">{{ t.label }}</button>
                    </div>
                </div>
            </div>
        </teleport>
    </div>
</template>

<script>
import VueDatePicker from '@vuepic/vue-datepicker';
import { t, texts } from '../texts.js';
import { zonedParts } from '../format.js';

const pad = (n) => String(n).padStart(2, '0');

const EMPTY = { y: null, mo: null, d: null, h: null, mi: null };

const DEFAULT_STEP = 30;
const MINUTES_PER_DAY = 24 * 60;
// Gap between the field and the menu, and between the menu and the viewport edge.
const MENU_GAP = 4;
const VIEWPORT_MARGIN = 8;

function validDay(y, mo, d) {
    const date = new Date(y, mo, d);
    return date.getFullYear() === y && date.getMonth() === mo && date.getDate() === d;
}

function validTime(h, mi) {
    return h >= 0 && h <= 23 && mi >= 0 && mi <= 59;
}

/**
 * A model value as its parts. Strings are read off their digits, never through
 * Date parsing, so no time zone moves the wall-clock time the server sent.
 */
export function partsOf(value, mode) {
    if (value instanceof Date) {
        if (Number.isNaN(value.getTime())) {
            return EMPTY;
        }
        return {
            y: value.getFullYear(), mo: value.getMonth(), d: value.getDate(),
            h: value.getHours(), mi: value.getMinutes(),
        };
    }
    const raw = typeof value === 'string' ? value.trim() : '';
    if (raw === '') {
        return EMPTY;
    }
    if (mode === 'time') {
        const t = /^(\d{1,2}):(\d{2})/.exec(raw);
        return t && validTime(+t[1], +t[2]) ? { ...EMPTY, h: +t[1], mi: +t[2] } : EMPTY;
    }
    const m = /^(\d{4})-(\d{2})(?:-(\d{2}))?(?:[T ](\d{2}):(\d{2}))?/.exec(raw);
    if (!m) {
        return EMPTY;
    }
    const parts = {
        y: +m[1], mo: +m[2] - 1, d: m[3] ? +m[3] : 1,
        h: m[4] !== undefined ? +m[4] : null, mi: m[5] !== undefined ? +m[5] : null,
    };
    return validDay(parts.y, parts.mo, parts.d) ? parts : EMPTY;
}

/**
 * The wall-clock day and time now in a time zone.
 *
 * @param {string} zone
 * @returns {{y: number, mo: number, d: number, h: number, mi: number}}
 */
export function nowIn(zone) {
    const p = zonedParts(new Date(), zone);
    return { y: +p.year, mo: +p.month - 1, d: +p.day, h: +p.hour, mi: +p.minute };
}

/** A time as the field shows it: 08.30. */
export function timeLabel(h, mi) {
    return `${pad(h)}.${pad(mi)}`;
}

/** What the user types, day first: 5.3.2026 14.30, 05.03.2026 14:30. */
export function parseTyped(text, mode) {
    const raw = text.trim();
    if (mode === 'time') {
        const t = /^(\d{1,2})(?:[.:](\d{2}))?$/.exec(raw);
        if (!t || !validTime(+t[1], t[2] ? +t[2] : 0)) {
            return null;
        }
        return { ...EMPTY, h: +t[1], mi: t[2] ? +t[2] : 0 };
    }
    if (mode === 'month') {
        const mm = /^(\d{1,2})[./](\d{4})$/.exec(raw);
        if (!mm || +mm[1] < 1 || +mm[1] > 12) {
            return null;
        }
        return { ...EMPTY, y: +mm[2], mo: +mm[1] - 1, d: 1 };
    }
    const m = /^(\d{1,2})\.(\d{1,2})\.(\d{2}|\d{4})(?:\s+(\d{1,2})(?:[.:](\d{2}))?)?$/.exec(raw);
    if (!m) {
        return null;
    }
    const y = m[3].length === 2 ? 2000 + +m[3] : +m[3];
    const parts = { y, mo: +m[2] - 1, d: +m[1], h: null, mi: null };
    if (!validDay(parts.y, parts.mo, parts.d)) {
        return null;
    }
    if (mode === 'datetime' && m[4] !== undefined) {
        parts.h = +m[4];
        parts.mi = m[5] !== undefined ? +m[5] : 0;
        if (!validTime(parts.h, parts.mi)) {
            return null;
        }
    }
    return parts;
}

export default {
    name: 'KkDateTimePicker',
    components: { VueDatePicker },
    props: {
        modelValue: { type: [String, Date], default: null },
        mode: { type: String, default: 'datetime' },
        modelType: { type: String, default: 'string' },
        timeStep: { type: Number, default: DEFAULT_STEP },
        size: { type: String, default: '' },
        clearable: { type: Boolean, default: true },
        disabled: { type: Boolean, default: false },
        placeholder: { type: String, default: '' },
        inputId: { type: String, default: '' },
        ariaLabel: { type: String, default: '' },
        describedBy: { type: String, default: '' },
        min: { type: String, default: '' },
        max: { type: String, default: '' },
        inputClass: { type: String, default: '' },
    },
    emits: ['update:modelValue'],
    data() {
        return { isOpen: false, typed: null, menuStyle: {} };
    },
    computed: {
        step() {
            return this.timeStep > 0 ? this.timeStep : DEFAULT_STEP;
        },
        parts() {
            return partsOf(this.modelValue, this.mode);
        },
        hasDay() {
            return this.mode !== 'time' && this.parts.y !== null;
        },
        hasTime() {
            return (this.mode === 'datetime' || this.mode === 'time') && this.parts.h !== null;
        },
        hasValue() {
            return this.mode === 'time' ? this.hasTime : this.hasDay;
        },
        hasCalendar() {
            return this.mode !== 'time';
        },
        hasList() {
            return this.mode === 'datetime' || this.mode === 'time';
        },
        /** The calendar's own model: the picked day, or {month, year}. */
        calendarValue() {
            if (!this.hasDay) {
                return null;
            }
            const p = this.parts;
            return this.mode === 'month' ? { month: p.mo, year: p.y } : new Date(p.y, p.mo, p.d);
        },
        text() {
            if (this.typed !== null) {
                return this.typed;
            }
            const p = this.parts;
            const time = this.hasTime ? timeLabel(p.h, p.mi) : '';
            if (this.mode === 'time') {
                return time;
            }
            if (!this.hasDay) {
                return '';
            }
            if (this.mode === 'month') {
                return `${pad(p.mo + 1)}/${p.y}`;
            }
            const day = `${pad(p.d)}.${pad(p.mo + 1)}.${p.y}`;
            return this.mode === 'datetime' && time ? `${day} ${time}` : day;
        },
        shownPlaceholder() {
            if (this.placeholder) {
                return this.placeholder;
            }
            return {
                datetime: t('pickerDateTime'), date: t('pickerDate'),
                time: t('pickerTime'), month: t('pickerMonth'),
            }[this.mode] || '';
        },
        times() {
            const picked = this.hasTime ? this.parts.h * 60 + this.parts.mi : -1;
            const list = [];
            for (let m = 0; m < MINUTES_PER_DAY; m += this.step) {
                const h = Math.floor(m / 60);
                list.push({ h, mi: m % 60, label: timeLabel(h, m % 60), isSelected: m === picked });
            }
            return list;
        },
        sizeClass() {
            return this.size === 'sm' || this.size === 'lg' ? `form-control-${this.size}` : '';
        },
        iconClass() {
            return this.mode === 'time' ? 'bi bi-clock' : 'bi bi-calendar3';
        },
        menuLabel() {
            return { time: t('pickerTime'), month: t('pickerMonth') }[this.mode] || t('pickerDate');
        },
        /** The calendar's month and day names: Finnish on a Finnish page, else English with Monday first. */
        calendarLocale() {
            const lang = String(texts().locale || 'en').toLowerCase();
            return lang.startsWith('en') ? 'en-GB' : lang;
        },
        minDate() {
            const p = partsOf(this.min, 'date');
            return p.y === null ? null : new Date(p.y, p.mo, p.d);
        },
        maxDate() {
            const p = partsOf(this.max, 'date');
            return p.y === null ? null : new Date(p.y, p.mo, p.d);
        },
        canClear() {
            return this.clearable && !this.disabled && this.hasValue;
        },
    },
    mounted() {
        document.addEventListener('mousedown', this.onOutside, true);
        document.addEventListener('focusin', this.onOutside, true);
        window.addEventListener('scroll', this.onViewport, true);
        window.addEventListener('resize', this.onViewport);
    },
    beforeUnmount() {
        document.removeEventListener('mousedown', this.onOutside, true);
        document.removeEventListener('focusin', this.onOutside, true);
        window.removeEventListener('scroll', this.onViewport, true);
        window.removeEventListener('resize', this.onViewport);
    },
    methods: {
        t,
        emitParts(p) {
            const isEmpty = this.mode === 'time' ? p.h === null : p.y === null;
            if (this.modelType === 'date' && (this.mode === 'datetime' || this.mode === 'date')) {
                const withTime = this.mode === 'datetime';
                this.$emit('update:modelValue', isEmpty ? null : new Date(
                    p.y, p.mo, p.d,
                    withTime && p.h !== null ? p.h : 0,
                    withTime && p.mi !== null ? p.mi : 0,
                ));
                return;
            }
            if (isEmpty) {
                this.$emit('update:modelValue', '');
                return;
            }
            const day = `${p.y}-${pad(p.mo + 1)}-${pad(p.d)}`;
            const time = p.h !== null ? `${pad(p.h)}:${pad(p.mi)}` : '';
            this.$emit('update:modelValue', {
                datetime: time ? `${day} ${time}` : day,
                date: day,
                time,
                month: `${p.y}-${pad(p.mo + 1)}`,
            }[this.mode]);
        },
        /** The time a day picked before any time gets: the next step from now, in the display time zone. */
        defaultTime() {
            const now = nowIn(texts().timeZone);
            const next = Math.min(
                Math.floor((now.h * 60 + now.mi) / this.step + 1) * this.step,
                MINUTES_PER_DAY - this.step,
            );
            return { h: Math.floor(next / 60), mi: next % 60 };
        },
        show() {
            if (this.disabled || this.isOpen) {
                return;
            }
            this.isOpen = true;
            this.$nextTick(() => {
                this.place();
                this.scrollList();
            });
        },
        hide() {
            this.isOpen = false;
        },
        /**
         * The menu is teleported to <body> and placed `fixed` against the
         * field: a scrolling modal body or card would otherwise cut it off at
         * its edge.
         */
        place() {
            const input = this.$refs.input;
            const menu = this.$refs.menu;
            if (!input || !menu) {
                return;
            }
            const r = input.getBoundingClientRect();
            const w = menu.offsetWidth;
            const h = menu.offsetHeight;
            const vw = document.documentElement.clientWidth;
            const vh = document.documentElement.clientHeight;
            const left = Math.max(VIEWPORT_MARGIN, Math.min(r.left, vw - w - VIEWPORT_MARGIN));
            const below = r.bottom + MENU_GAP;
            const fitsAbove = r.top - h - MENU_GAP >= VIEWPORT_MARGIN;
            const top = below + h > vh - VIEWPORT_MARGIN && fitsAbove ? r.top - h - MENU_GAP : below;
            this.menuStyle = { top: `${top}px`, left: `${left}px` };
        },
        /** Opens the list on the picked time, else on the current hour. */
        scrollList() {
            const list = this.$refs.list;
            if (!list) {
                return;
            }
            let target = list.querySelector('.active');
            if (!target) {
                const minutes = this.hasTime ? this.parts.h * 60 + this.parts.mi : nowIn(texts().timeZone).h * 60;
                target = list.children[Math.min(Math.floor(minutes / this.step), list.children.length - 1)];
            }
            if (target) {
                list.scrollTop = target.offsetTop - list.clientHeight / 2 + target.offsetHeight / 2;
            }
        },
        onCalendar(value) {
            this.typed = null;
            if (this.mode === 'month') {
                if (value && value.year !== undefined) {
                    this.emitParts({ y: value.year, mo: value.month, d: 1, h: null, mi: null });
                }
                this.hide();
                return;
            }
            if (!(value instanceof Date)) {
                return;
            }
            const p = { y: value.getFullYear(), mo: value.getMonth(), d: value.getDate(), h: null, mi: null };
            if (this.mode === 'datetime') {
                const time = this.hasTime ? { h: this.parts.h, mi: this.parts.mi } : this.defaultTime();
                this.emitParts({ ...p, h: time.h, mi: time.mi });
                this.$nextTick(this.scrollList);
                return;
            }
            this.emitParts(p);
            this.hide();
        },
        pickTime(t) {
            this.typed = null;
            let p = this.parts;
            if (this.mode === 'datetime' && p.y === null) {
                p = nowIn(texts().timeZone);
            }
            this.emitParts({ y: p.y, mo: p.mo, d: p.d, h: t.h, mi: t.mi });
            this.hide();
            this.$refs.input?.focus();
        },
        onType(event) {
            this.typed = event.target.value;
        },
        /** A typed value is taken when it reads as one; otherwise the field goes back. */
        commitTyped() {
            if (this.typed === null) {
                return;
            }
            const text = this.typed;
            this.typed = null;
            // The text was typed and dropped in the same tick, so Vue sees no
            // change to render: the field is set back by hand, and the value
            // emitted below then renders over it.
            if (this.$refs.input) {
                this.$refs.input.value = this.text;
            }
            if (text.trim() === '') {
                if (this.clearable) {
                    this.emitParts(EMPTY);
                }
                return;
            }
            const p = parseTyped(text, this.mode);
            if (!p || this.isOutOfRange(p)) {
                return;
            }
            if (this.mode === 'datetime' && p.h === null) {
                const time = this.hasTime ? { h: this.parts.h, mi: this.parts.mi } : this.defaultTime();
                p.h = time.h;
                p.mi = time.mi;
            }
            this.emitParts(p);
        },
        isOutOfRange(p) {
            if (p.y === null || this.mode === 'time') {
                return false;
            }
            const day = new Date(p.y, p.mo, p.d);
            return (this.minDate !== null && day < this.minDate) || (this.maxDate !== null && day > this.maxDate);
        },
        onKeydown(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                this.commitTyped();
                this.hide();
            } else if (event.key === 'Escape' && this.isOpen) {
                // Kept from the modal underneath, which would close on the same Esc.
                event.stopPropagation();
                this.typed = null;
                this.hide();
            } else if (event.key === 'ArrowDown' && !this.isOpen) {
                event.preventDefault();
                this.show();
            }
        },
        onMenuKeydown(event) {
            if (event.key === 'Escape') {
                event.stopPropagation();
                this.hide();
                this.$refs.input?.focus();
            }
        },
        clear() {
            this.typed = null;
            this.emitParts(EMPTY);
            this.hide();
        },
        isInside(node) {
            return this.$el?.contains(node) || this.$refs.menu?.contains(node);
        },
        onOutside(event) {
            if (this.isOpen && !this.isInside(event.target)) {
                this.commitTyped();
                this.hide();
            }
        },
        onViewport() {
            if (this.isOpen) {
                this.place();
            }
        },
    },
};
</script>
