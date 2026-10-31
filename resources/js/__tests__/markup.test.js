import { describe, it, expect, afterEach, vi } from 'vitest';
import { nextTick } from 'vue';
import { mountAll, listen, urlWith, pageUrl, matcherOf, navigation, closeModal } from '../markup.js';

async function settle() {
    await nextTick();
    await nextTick();
}

describe('the markup mounts', () => {
    afterEach(() => {
        closeModal();
        document.body.innerHTML = '';
        vi.restoreAllMocks();
    });

    it('opens a markup modal with the page\'s own nodes and puts them back on close', async () => {
        document.body.innerHTML = `
            <div data-kk-modal="demo" data-title="Edit the note" hidden>
                <form id="note-form"><input name="note" value="Hello"></form>
                <div data-kk-modal-footer><button type="button" data-kk-modal-close>Cancel</button></div>
            </div>
            <button type="button" id="opener" data-kk-modal-open="demo">Edit</button>`;
        listen();
        mountAll(document);
        const form = document.getElementById('note-form');
        document.getElementById('opener').click();
        await settle();
        const modal = document.querySelector('.kk-modal');
        expect(modal.querySelector('.kk-modal-title').textContent).toBe('Edit the note');
        expect(modal.querySelector('.kk-modal-body').contains(form)).toBe(true);
        expect(modal.querySelector('.kk-modal-footer [data-kk-modal-close]')).not.toBeNull();
        modal.querySelector('[data-kk-modal-close]').click();
        await settle();
        expect(document.querySelector('.kk-modal')).toBeNull();
        expect(document.querySelector('[data-kk-modal="demo"]').contains(form)).toBe(true);
    });

    it('turns an input into a date picker that keeps the posted value', async () => {
        document.body.innerHTML = `<form><label for="at">At</label>
            <input type="text" id="at" name="at" value="2026-10-27 08:00" class="form-control is-invalid"
                   aria-describedby="at-help" data-kk-date-time-picker></form>`;
        mountAll(document);
        await settle();
        const hidden = document.querySelector('input[name="at"]');
        expect(hidden.type).toBe('hidden');
        const field = document.getElementById('at');
        expect(field).not.toBe(hidden);
        expect(field.value).toBe('27.10.2026 08.00');
        expect(field.classList.contains('is-invalid')).toBe(true);
        expect(field.getAttribute('aria-describedby')).toBe('at-help');
        const changed = vi.fn();
        hidden.addEventListener('change', changed);
        field.value = '28.10.2026 9.30';
        field.dispatchEvent(new Event('input'));
        field.dispatchEvent(new Event('blur'));
        await settle();
        expect(hidden.value).toBe('2026-10-28 09:30');
        expect(changed).toHaveBeenCalled();
    });

    it('turns a file input into a drop target and keeps the input in the form', async () => {
        document.body.innerHTML = '<form><input type="file" id="doc" name="doc" accept=".pdf" data-kk-file-drop></form>';
        mountAll(document);
        await settle();
        const input = document.querySelector('input[name="doc"]');
        expect(input.hidden).toBe(true);
        expect(document.querySelector('.kk-file-drop')).not.toBeNull();
        expect(document.querySelector('.kk-file-drop input[type="file"]').id).toBe('doc');
    });

    it('draws a list table from its JSON and reloads for a server filter', async () => {
        const go = vi.spyOn(navigation, 'go').mockImplementation(() => {});
        const config = {
            rows: [{ id: 1, name: 'North', state: '<span class="kk-badge kk-badge-ok">On</span>' }],
            columns: [{ key: 'name', label: 'Name' }, { key: 'state', label: 'State', isHtml: true }],
            filters: [{ key: 'kind', label: 'Kind', type: 'select', param: 'kind', options: [{ value: 'a', label: 'A' }] }],
            selectable: true,
            selectionName: 'ids',
            selectionForm: 'bulk',
        };
        document.body.innerHTML = `<div data-kk-list-table><script type="application/json" data-kk-list-table-config>${JSON.stringify(config)}</script></div>`;
        mountAll(document);
        await settle();
        expect(document.querySelector('tbody .kk-badge-ok').textContent).toBe('On');
        document.querySelector('tbody input[type="checkbox"]').click();
        await settle();
        expect(document.querySelector('input[name="ids[]"]').value).toBe('1');
        expect(document.querySelector('input[name="ids[]"]').getAttribute('form')).toBe('bulk');
        const select = document.querySelector('[data-filter="kind"] select');
        select.value = 'a';
        select.dispatchEvent(new Event('change'));
        expect(go).toHaveBeenCalledWith('/?kind=a');
    });

    it('sorts a column with sortField by that row field, not by its shown cell', async () => {
        const config = {
            rows: [
                { id: 1, day: '09.12.2026', daySort: '2026-12-09' },
                { id: 2, day: '10.01.2026', daySort: '2026-01-10' },
                { id: 3, day: '31.05.2026', daySort: '2026-05-31' },
            ],
            columns: [{ key: 'day', label: 'Day', sortField: 'daySort' }],
            sortKey: 'day',
        };
        document.body.innerHTML = `<div data-kk-list-table><script type="application/json" data-kk-list-table-config>${JSON.stringify(config)}</script></div>`;
        mountAll(document);
        await settle();
        const shown = Array.from(document.querySelectorAll('tbody tr')).map((tr) => tr.textContent.trim());
        expect(shown).toEqual(['10.01.2026', '31.05.2026', '09.12.2026']);
    });

    it('builds the addresses of a server-paged list', () => {
        expect(urlWith('http://x.test/list?page=3&q=old', { q: 'new' })).toBe('/list?q=new');
        expect(urlWith('http://x.test/list?q=old', { q: '' })).toBe('/list');
        expect(urlWith('http://x.test/list', { tag: ['a', 'b'] })).toBe('/list?tag%5B%5D=a&tag%5B%5D=b');
        expect(pageUrl('http://x.test/list?q=a', 2)).toBe('/list?q=a&page=2');
    });

    it('matches rows from a filter\'s description', () => {
        const columns = [{ key: 'name' }, { key: 'state', isHtml: true }];
        expect(matcherOf({ type: 'text' }, columns)({ name: 'North Gateway', state: '<b>x</b>' }, 'north')).toBe(true);
        expect(matcherOf({ type: 'text' }, columns)({ name: 'North', state: '<b>x</b>' }, 'b>')).toBe(false);
        expect(matcherOf({ type: 'select', matchField: 'tags' }, columns)({ tags: ['a', 'b'] }, ['b'])).toBe(true);
        const range = matcherOf({ type: 'date_range', matchField: 'day' }, columns);
        expect(range({ day: '2026-10-26 08:00' }, { from: '2026-10-26', to: '' })).toBe(true);
        expect(range({ day: '2026-10-25' }, { from: '2026-10-26', to: '' })).toBe(false);
    });
});
