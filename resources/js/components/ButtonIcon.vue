<script setup lang="ts">
import { computed } from 'vue';

export type ButtonIconName = 'plus' | 'view' | 'edit' | 'delete' | 'archive' | 'close' | 'download' | 'upload' | 'pdf' | 'excel' | 'word';
const props = defineProps<{ name: ButtonIconName }>();
const paths = computed(() => ({
    plus: ['M12 5v14M5 12h14'],
    view: ['M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z', 'M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z'],
    edit: ['M12 20h9', 'M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z'],
    delete: ['M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6', 'M10 11v5m4-5v5'],
    archive: ['M3 4h18v5H3zM5 9v11h14V9m-9 4h4'],
    close: ['m6 6 12 12M18 6 6 18'],
    download: ['M12 3v12m-5-5 5 5 5-5M4 16v4h16v-4'],
    upload: ['M12 16V4m-5 5 5-5 5 5M4 16v4h16v-4'],
    pdf: ['M14 3H5v18h14V8l-5-5Z', 'M14 3v5h5M8 13h8M8 17h5'],
    excel: ['M14 3H5v18h14V8l-5-5Z', 'M14 3v5h5m-9 4 5 5m0-5-5 5'],
    word: ['M14 3H5v18h14V8l-5-5Z', 'M14 3v5h5M8 17l1.8-6 2.2 4.5 2.2-4.5 1.8 6'],
})[props.name]);
</script>

<template>
    <svg class="ui-button-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
        <template v-if="name === 'pdf'">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
            <path d="M14 2v6h6" />
            <text x="12" y="17.2" text-anchor="middle" fill="currentColor" stroke="none" font-size="6.4" font-weight="700" font-family="sans-serif">PDF</text>
        </template>
        <template v-else-if="name === 'word'">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
            <path d="M14 2v6h6" />
            <text x="12" y="17.2" text-anchor="middle" fill="currentColor" stroke="none" font-size="6.0" font-weight="700" font-family="sans-serif">DOC</text>
        </template>
        <path v-else v-for="path in paths" :key="path" :d="path" />
    </svg>
</template>
