/**
 * The plugin's Vue components and formats, for an application's own bundler
 * (Vite) and for the plugin's build. The build (build/build.mjs) turns this
 * into webroot/js/components/kk-vue-components.js, a plain script that reads
 * Vue from `window.Vue`.
 *
 *     import { KkUi, KkListTable } from '<vendor>/makallio85/rocksoftware-cakephp-ui/resources/js/index.js';
 *     createApp(Root).use(KkUi).mount('#app');
 */
import KkModal from './components/KkModal.vue';
import KkListTable from './components/KkListTable.vue';
import KkPager from './components/KkPager.vue';
import KkDateTimePicker from './components/KkDateTimePicker.vue';
import KkFileDrop from './components/KkFileDrop.vue';
import * as format from './format.js';
import { closeModal, listen, mountAll, openModal, SELECTOR } from './markup.js';

export { KkModal, KkListTable, KkPager, KkDateTimePicker, KkFileDrop, format };

/** Every component under its tag name. */
export const components = {
    'kk-modal': KkModal,
    'kk-list-table': KkListTable,
    'kk-pager': KkPager,
    'kk-date-time-picker': KkDateTimePicker,
    'kk-file-drop': KkFileDrop,
};

/**
 * A Vue plugin registering every component: `app.use(KkUi)`.
 * Also the page's handle on the markup mounts: `KkUi.mount(element)` for
 * markup a script added, `KkUi.openModal(name)`, `KkUi.closeModal(name)`.
 */
export const KkUi = {
    install(app) {
        Object.entries(components).forEach(([tag, component]) => app.component(tag, component));
    },
    components,
    format,
    mount: (root) => {
        listen();
        mountAll(root);
    },
    openModal,
    closeModal,
    selector: SELECTOR,
};

export default KkUi;
