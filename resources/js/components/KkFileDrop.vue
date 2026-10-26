<!--
<kk-file-drop>: the one file input. A dashed target to click or
drop files on, the chosen files listed under it with their sizes and a way to
remove each. A bare <input type="file"> is not used anywhere else.

    <kk-file-drop v-model="files" accept="image/jpeg,image/png,.webp" :max-bytes="10485760"
                  hint="JPG, PNG or WebP, at most 10 MB." @change="upload"></kk-file-drop>

Props:
  modelValue   the chosen files (Array of File); also `v-model`
  accept       the input's accept list: MIME types ('image/png'), wildcards
               ('image/*') and extensions ('.webp'); a dropped file outside it
               is refused, as the picker would not offer it
  multiple     several files; otherwise a new choice replaces the old one
  maxBytes     the largest file taken (0: no limit); a larger one is refused
  text         the line inside the target (default: Drop a file here or click to choose)
  hint         the help line under it
  inputId, disabled
  isBusy       shows a spinner and takes no files, such as while uploading
  isListed     lists the chosen files under the target (default true)
Slots: default (extra lines inside the target, such as the current file).
Emits `update:modelValue` and `change` with the chosen files after every
choice, and `rejected` with [{ file, reason }] for files refused by accept or
maxBytes; a refusal is also shown under the target.
Styles: webroot/css/kk-file-drop.css.
-->
<template>
    <div class="kk-file-drop-wrap">
        <div class="kk-file-drop" data-role="drop" role="button" :tabindex="isInert ? -1 : 0"
             :class="{ 'is-drag': isDragging, 'has-files': modelValue.length > 0, 'is-disabled': isInert }"
             :aria-disabled="isInert ? 'true' : 'false'" :aria-describedby="hint ? `${id}-hint` : null"
             @click="open" @keydown.enter.prevent="open" @keydown.space.prevent="open"
             @dragenter.prevent="onDragOver" @dragover.prevent="onDragOver"
             @dragleave.prevent="isDragging = false" @drop.prevent="onDrop">
            <span v-if="isBusy" class="spinner-border spinner-border-sm text-secondary" aria-hidden="true"></span>
            <i v-else class="bi bi-cloud-arrow-up kk-file-drop-icon" aria-hidden="true"></i>
            <span class="kk-file-drop-text">{{ text || t('fileDropText') }}</span>
            <slot></slot>
            <input ref="input" type="file" class="kk-file-drop-input" :id="id" :accept="accept || null"
                   :multiple="multiple" :disabled="isInert" tabindex="-1" @click.stop @change="onPick">
        </div>
        <ul v-if="isListed && modelValue.length" class="kk-file-list" data-role="files">
            <li v-for="(file, index) in modelValue" :key="`${file.name}-${index}`" class="kk-file-row">
                <span class="kk-file-name"><i class="bi bi-file-earmark me-1" aria-hidden="true"></i>{{ file.name }}</span>
                <span class="kk-file-size">{{ formatSize(file.size) }}</span>
                <button type="button" class="kk-file-remove" :title="t('fileDropRemove', { name: file.name })"
                        :aria-label="t('fileDropRemove', { name: file.name })"
                        :disabled="isInert" @click="remove(index)"><i class="bi bi-x-lg"></i></button>
            </li>
        </ul>
        <div v-if="refusal" class="invalid-feedback d-block" data-role="refusal">{{ refusal }}</div>
        <div v-if="hint" :id="`${id}-hint`" class="form-text">{{ hint }}</div>
    </div>
</template>

<script>
import { t } from '../texts.js';
import { formatFileSize } from '../format.js';

let counter = 0;

/**
 * A size as people read it: 512 B, 34 kB, 2,4 MB.
 *
 * @param {number} bytes
 * @returns {string}
 */
export function formatSize(bytes) {
    return formatFileSize(bytes);
}

/**
 * Whether a file matches an input's accept list.
 *
 * @param {File} file
 * @param {string} accept comma-separated MIME types, wildcards and extensions
 * @returns {boolean}
 */
export function isAccepted(file, accept) {
    const rules = String(accept || '').split(',').map((rule) => rule.trim().toLowerCase()).filter(Boolean);
    if (!rules.length) {
        return true;
    }
    const name = file.name.toLowerCase();
    const type = String(file.type || '').toLowerCase();
    return rules.some((rule) => {
        if (rule.startsWith('.')) {
            return name.endsWith(rule);
        }
        if (rule.endsWith('/*')) {
            return type.startsWith(rule.slice(0, -1));
        }
        return type === rule;
    });
}

export default {
    name: 'KkFileDrop',
    props: {
        modelValue: { type: Array, default: () => [] },
        accept: { type: String, default: '' },
        multiple: { type: Boolean, default: false },
        maxBytes: { type: Number, default: 0 },
        text: { type: String, default: '' },
        hint: { type: String, default: '' },
        inputId: { type: String, default: '' },
        disabled: { type: Boolean, default: false },
        isBusy: { type: Boolean, default: false },
        isListed: { type: Boolean, default: true },
    },
    emits: ['update:modelValue', 'change', 'rejected'],
    data() {
        counter += 1;
        return { uid: counter, isDragging: false, refusal: '' };
    },
    computed: {
        id() {
            return this.inputId || `kk-file-drop-${this.uid}`;
        },
        isInert() {
            return this.disabled || this.isBusy;
        },
    },
    methods: {
        t,
        formatSize,
        open() {
            if (!this.isInert) {
                this.$refs.input.click();
            }
        },
        onDragOver() {
            if (!this.isInert) {
                this.isDragging = true;
            }
        },
        onDrop(event) {
            this.isDragging = false;
            if (this.isInert || !event.dataTransfer) {
                return;
            }
            this.take(Array.from(event.dataTransfer.files || []));
        },
        onPick(event) {
            this.take(Array.from(event.target.files || []));
            // The same file chosen again must fire change again.
            event.target.value = '';
        },
        take(files) {
            if (!files.length) {
                return;
            }
            const rejected = [];
            const taken = [];
            files.forEach((file) => {
                if (!isAccepted(file, this.accept)) {
                    rejected.push({ file, reason: t('fileDropWrongType', { name: file.name }) });
                } else if (this.maxBytes > 0 && file.size > this.maxBytes) {
                    rejected.push({
                        file,
                        reason: t('fileDropTooLarge', { name: file.name, size: formatSize(this.maxBytes) }),
                    });
                } else {
                    taken.push(file);
                }
            });
            this.refusal = rejected.map((r) => r.reason).join(' ');
            if (rejected.length) {
                this.$emit('rejected', rejected);
            }
            if (!taken.length) {
                return;
            }
            // A single-file field keeps the last file chosen, as the picker would.
            const next = this.multiple ? [...this.modelValue, ...taken] : [taken[taken.length - 1]];
            this.emitFiles(next);
        },
        remove(index) {
            this.emitFiles(this.modelValue.filter((_, i) => i !== index));
        },
        emitFiles(files) {
            this.$emit('update:modelValue', files);
            this.$emit('change', files);
        },
    },
};
</script>
