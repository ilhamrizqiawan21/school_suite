<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface PageProps {
    activePeriod?: {
        academic_year: string;
        term: string;
        label: string;
        is_active: boolean;
    };
    [key: string]: unknown;
}

const page = usePage<PageProps>();
const period = computed(() => page.props.activePeriod ?? {
    academic_year: '2026/2027',
    term: 'Ganjil',
    label: '2026/2027 — Semester Ganjil',
    is_active: true,
});
</script>

<template>
    <div
        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 bg-white shadow-xs text-xs font-medium text-slate-700"
        role="status"
        aria-label="Periode Akademik Aktif"
    >
        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500" aria-hidden="true"></span>
        <span class="text-slate-500">Periode:</span>
        <span class="font-semibold text-slate-900">{{ period.label }}</span>
    </div>
</template>
