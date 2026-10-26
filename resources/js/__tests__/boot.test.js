import { describe, it, expect, vi, afterEach } from 'vitest';
import { bootWhenVueReady } from '../boot.js';

/** A window stand-in whose document's readyState the test moves along. */
function fakeWindow(readyState) {
    const docListeners = {};
    const winListeners = {};
    const on = (map) => (type, fn) => { (map[type] ||= []).push(fn); };
    const off = (map) => (type, fn) => { map[type] = (map[type] || []).filter((f) => f !== fn); };
    const fire = (map, type) => (map[type] || []).slice().forEach((fn) => fn(new Event(type)));
    const document = {
        readyState,
        addEventListener: on(docListeners),
        removeEventListener: off(docListeners),
    };
    const win = {
        document,
        Vue: undefined,
        console: { warn: vi.fn() },
        setTimeout: (fn, ms) => setTimeout(fn, ms),
        addEventListener: on(winListeners),
        removeEventListener: off(winListeners),
    };
    return {
        win,
        moveTo(state) {
            document.readyState = state;
            fire(docListeners, 'readystatechange');
        },
        contentLoaded() {
            fire(docListeners, 'DOMContentLoaded');
        },
        loaded() {
            document.readyState = 'complete';
            fire(docListeners, 'readystatechange');
            fire(winListeners, 'load');
        },
    };
}

const vue = { createApp: () => ({}) };

describe('booting the markup mounts', () => {
    afterEach(() => {
        vi.useRealTimers();
    });

    it('boots as parsing ends when Vue is already there (the plugin\'s own Vue)', () => {
        const page = fakeWindow('loading');
        page.win.Vue = vue;
        const boot = vi.fn();
        bootWhenVueReady(page.win, boot);
        expect(boot).not.toHaveBeenCalled();
        page.moveTo('interactive');
        expect(boot).toHaveBeenCalledTimes(1);
        page.contentLoaded();
        page.loaded();
        expect(boot).toHaveBeenCalledTimes(1);
    });

    it('waits for DOMContentLoaded when a module script sets Vue after parsing (Vite)', () => {
        const page = fakeWindow('loading');
        const boot = vi.fn();
        bootWhenVueReady(page.win, boot);
        page.moveTo('interactive');
        expect(boot).not.toHaveBeenCalled();
        // The module script runs between `interactive` and DOMContentLoaded.
        page.win.Vue = vue;
        page.contentLoaded();
        expect(boot).toHaveBeenCalledTimes(1);
    });

    it('waits for the window\'s load, then retries, when Vue comes later still', () => {
        vi.useFakeTimers();
        const page = fakeWindow('loading');
        const boot = vi.fn();
        bootWhenVueReady(page.win, boot, { retries: 5, retryMs: 100 });
        page.moveTo('interactive');
        page.contentLoaded();
        page.loaded();
        expect(boot).not.toHaveBeenCalled();
        vi.advanceTimersByTime(250);
        page.win.Vue = vue;
        vi.advanceTimersByTime(100);
        expect(boot).toHaveBeenCalledTimes(1);
        vi.advanceTimersByTime(1000);
        expect(boot).toHaveBeenCalledTimes(1);
        expect(page.win.console.warn).not.toHaveBeenCalled();
    });

    it('boots at once when the script runs after the page has loaded', () => {
        const page = fakeWindow('complete');
        page.win.Vue = vue;
        const boot = vi.fn();
        bootWhenVueReady(page.win, boot);
        expect(boot).toHaveBeenCalledTimes(1);
    });

    it('waits for nothing on a page with no markup to mount', () => {
        vi.useFakeTimers();
        const page = fakeWindow('loading');
        const boot = vi.fn();
        bootWhenVueReady(page.win, boot, { isNeeded: () => false });
        page.moveTo('interactive');
        page.contentLoaded();
        page.loaded();
        vi.advanceTimersByTime(5000);
        expect(boot).not.toHaveBeenCalled();
        expect(page.win.console.warn).not.toHaveBeenCalled();
    });

    it('warns once when Vue never comes, and does not boot', () => {
        vi.useFakeTimers();
        const page = fakeWindow('complete');
        const boot = vi.fn();
        bootWhenVueReady(page.win, boot, { retries: 3, retryMs: 100 });
        vi.advanceTimersByTime(1000);
        expect(boot).not.toHaveBeenCalled();
        expect(page.win.console.warn).toHaveBeenCalledTimes(1);
    });
});
