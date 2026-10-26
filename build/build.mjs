/**
 * Builds the plugin's Vue components into plain files under webroot/, which
 * the layouts load with `<script src>` and `<link>`: precompiled render
 * functions, no template compiler, no eval, so they run under
 * `script-src 'self'`.
 *
 *   webroot/js/components/kk-vue-components.js  every component and the markup
 *       mounts; reads Vue 3 from window.Vue the first time a component is used
 *   webroot/js/kk-format.js                     window.KkFormat, no Vue needed
 *   webroot/js/vue.runtime.global.prod.js       Vue's runtime-only build, for an
 *       application without its own Vue
 *   webroot/css/kk-date-time-picker.css         the calendar's styles and ours
 *
 * Run `npm ci && npm run build`; commit the output with the sources. CI
 * builds again and fails when the output differs from what is committed.
 */
import { build } from 'vite';
import vue from '@vitejs/plugin-vue';
import { readFileSync, writeFileSync, mkdirSync, rmSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const tmp = path.join(root, 'tmp', 'build');
const out = (...parts) => path.join(root, 'webroot', ...parts);
const pkg = (name) => JSON.parse(readFileSync(path.join(root, 'node_modules', name, 'package.json'), 'utf8'));

/** The component names the script defines on window, from index.js's exports. */
const GLOBALS = ['KkUi', 'KkModal', 'KkListTable', 'KkPager', 'KkDateTimePicker', 'KkFileDrop'];

async function bundle(entry, format, fileName, external = []) {
    await build({
        root,
        configFile: false,
        logLevel: 'warn',
        plugins: [vue()],
        define: { 'process.env.NODE_ENV': '"production"' },
        build: {
            outDir: tmp,
            emptyOutDir: false,
            minify: 'esbuild',
            target: 'es2019',
            lib: { entry: path.join(root, entry), formats: [format], fileName: () => fileName, name: 'KkFormat' },
            rollupOptions: { external, output: { exports: 'named' } },
        },
    });
    return readFileSync(path.join(tmp, fileName), 'utf8').trim();
}

rmSync(tmp, { recursive: true, force: true });
mkdirSync(tmp, { recursive: true });

const vueVersion = pkg('vue').version;
const pickerVersion = pkg('@vuepic/vue-datepicker').version;

// The components: a CommonJS bundle with `vue` left out, wrapped so that it
// runs on first use with window.Vue as `vue`. The application's Vue may load
// after this script (an element in `bodyEnd`), and every component then uses
// that one copy of Vue.
const components = await bundle('resources/js/index.js', 'cjs', 'components.cjs', ['vue']);
writeFileSync(out('js', 'components', 'kk-vue-components.js'), `/*!
 * RockSoftware/Ui components: kk-modal, kk-list-table, kk-pager, kk-date-time-picker, kk-file-drop.
 * Built by build/build.mjs from resources/js; do not edit. Precompiled, CSP-safe.
 * Needs Vue 3 as window.Vue (the plugin's vue.runtime.global.prod.js or the application's own)
 * when a component is first used. Bundles @vuepic/vue-datepicker ${pickerVersion} (MIT).
 */
(function () {
    'use strict';
    var loaded = null;
    function factory(require, exports, module) {
${components}
    }
    function load() {
        if (loaded) {
            return loaded;
        }
        if (!window.Vue || typeof window.Vue.createApp !== 'function') {
            throw new Error('RockSoftware/Ui: the components need Vue 3 as window.Vue.');
        }
        var module = { exports: {} };
        factory(function (name) {
            if (name === 'vue') {
                return window.Vue;
            }
            throw new Error('RockSoftware/Ui: unknown module ' + name);
        }, module.exports, module);
        loaded = module.exports;
        return loaded;
    }
    ${JSON.stringify(GLOBALS)}.forEach(function (name) {
        Object.defineProperty(window, name, {
            configurable: true,
            enumerable: true,
            get: function () {
                return load()[name];
            },
        });
    });
    // The markup mounts (data-kk-modal, data-kk-list-table, data-kk-date-time-picker,
    // data-kk-file-drop) once the page is parsed, before DOMContentLoaded listeners run.
    function boot() {
        var selector = '[data-kk-modal], [data-kk-list-table], input[data-kk-date-time-picker], input[data-kk-file-drop]';
        if (document.querySelector(selector) || document.querySelector('[data-kk-modal-open]')) {
            load().KkUi.mount(document);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('readystatechange', function onReady() {
            if (document.readyState !== 'loading') {
                document.removeEventListener('readystatechange', onReady);
                boot();
            }
        });
    } else {
        boot();
    }
})();
`);

const format = await bundle('resources/js/format-global.js', 'iife', 'format.js');
writeFileSync(out('js', 'kk-format.js'), `/*!
 * RockSoftware/Ui formats as window.KkFormat: formatDate, formatDateTime, formatNumber,
 * formatMoney, formatPercent, formatFileSize. Built by build/build.mjs from resources/js/format.js; do not edit.
 */
${format.replace(/^var KkFormat\s*=/, 'window.KkFormat =')}
`);

const runtime = readFileSync(path.join(root, 'node_modules', 'vue', 'dist', 'vue.runtime.global.prod.js'), 'utf8');
writeFileSync(out('js', 'vue.runtime.global.prod.js'), `/*! Vue ${vueVersion} runtime-only build (MIT), copied by build/build.mjs from the vue package. */\n${runtime.trim()}\n`);

const pickerCss = readFileSync(path.join(root, 'node_modules', '@vuepic', 'vue-datepicker', 'dist', 'main.css'), 'utf8')
    .replace(/\/\*# sourceMappingURL=.*?\*\//g, '').trim();
const ownCss = readFileSync(path.join(root, 'resources', 'css', 'kk-date-time-picker.css'), 'utf8').trim();
writeFileSync(out('css', 'kk-date-time-picker.css'), `/*! Built by build/build.mjs: @vuepic/vue-datepicker ${pickerVersion} (MIT), then resources/css/kk-date-time-picker.css. */
${pickerCss}

${ownCss}
`);

rmSync(tmp, { recursive: true, force: true });
console.log('Built webroot/js/components/kk-vue-components.js, webroot/js/kk-format.js, webroot/js/vue.runtime.global.prod.js, webroot/css/kk-date-time-picker.css');
