import { describe, it, expect, beforeAll, beforeEach } from 'vitest';

// The plain script in webroot/ (no build step): loaded once, as a page would.
const markup = (expanded, labels = '') => `
    <div id="more"${expanded ? '' : ' hidden'}>Rest of the section</div>
    <button type="button" class="kk-expand-toggle" data-kk-expand-toggle aria-controls="more"
            aria-expanded="${expanded ? 'true' : 'false'}"${labels}>
        <span class="kk-expand-toggle__line" aria-hidden="true"></span>
        <span class="kk-expand-toggle__label"><span data-kk-expand-toggle-text>${expanded ? 'Show less' : 'Show more'}</span>
            <i class="bi ${expanded ? 'bi-chevron-up' : 'bi-chevron-down'}" aria-hidden="true"></i></span>
        <span class="kk-expand-toggle__line" aria-hidden="true"></span>
    </button>`;

const button = () => document.querySelector('[data-kk-expand-toggle]');
const target = () => document.getElementById('more');
const labelText = () => button().querySelector('[data-kk-expand-toggle-text]').textContent;
const icon = () => button().querySelector('.bi');

describe('kk-expand-toggle.js', () => {
    beforeAll(async () => {
        await import('../../../webroot/js/components/kk-expand-toggle.js');
    });

    beforeEach(() => {
        document.body.innerHTML = '';
    });

    it('opens and closes its target, keeping aria-expanded, the label and the chevron in step', () => {
        document.body.innerHTML = markup(false, ' data-show-label="Näytä lisää" data-hide-label="Näytä vähemmän"');

        button().click();
        expect(button().getAttribute('aria-expanded')).toBe('true');
        expect(target().hidden).toBe(false);
        expect(labelText()).toBe('Näytä vähemmän');
        expect(icon().classList.contains('bi-chevron-up')).toBe(true);
        expect(icon().classList.contains('bi-chevron-down')).toBe(false);

        button().click();
        expect(button().getAttribute('aria-expanded')).toBe('false');
        expect(target().hidden).toBe(true);
        expect(labelText()).toBe('Näytä lisää');
        expect(icon().classList.contains('bi-chevron-down')).toBe(true);
    });

    it('dispatches a bubbling kk:expand-toggle event with the new state', () => {
        document.body.innerHTML = markup(true);
        const seen = [];
        const listener = (event) => seen.push([event.target, event.detail.expanded]);
        document.addEventListener('kk:expand-toggle', listener);

        button().click();
        button().click();
        document.removeEventListener('kk:expand-toggle', listener);

        expect(seen).toEqual([[button(), false], [button(), true]]);
    });

    it('works from a click on the label inside the button', () => {
        document.body.innerHTML = markup(false);

        button().querySelector('[data-kk-expand-toggle-text]').click();

        expect(target().hidden).toBe(false);
    });

    it('falls back to English labels without data attributes or page texts', () => {
        document.body.innerHTML = markup(false);

        button().click();
        expect(labelText()).toBe('Show less');
        button().click();
        expect(labelText()).toBe('Show more');
    });

    it('opens one from a script without an event', () => {
        document.body.innerHTML = markup(false);
        let count = 0;
        const listener = () => { count++; };
        document.addEventListener('kk:expand-toggle', listener);

        window.KkExpandToggle.setExpanded(button(), true);
        document.removeEventListener('kk:expand-toggle', listener);

        expect(target().hidden).toBe(false);
        expect(button().getAttribute('aria-expanded')).toBe('true');
        expect(count).toBe(0);
    });

    it('does nothing when disabled', () => {
        document.body.innerHTML = markup(false);
        button().disabled = true;

        button().click();

        expect(target().hidden).toBe(true);
    });
});
