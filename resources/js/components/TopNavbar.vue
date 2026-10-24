<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ActivePeriodBadge from './ActivePeriodBadge.vue';

interface PageProps {
    school?: {
        name: string;
    };
    [key: string]: unknown;
}

const page = usePage<PageProps>();
const schoolName = computed(() => page.props.school?.name ?? 'Sekolah Pilot School Suite');
</script>

<template>
    <header class="h-16 border-b border-slate-200 bg-white/80 backdrop-blur-xs sticky top-0 z-20 flex items-center justify-between px-6">
        <div class="flex items-center gap-4">
            <div class="flex flex-col">
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Unit Sekolah</span>
                <span class="text-sm font-semibold text-slate-900 leading-tight">{{ schoolName }}</span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <ActivePeriodBadge />
            <div class="h-6 w-px bg-slate-200 mx-1" aria-hidden="true"></div>
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-between text-xs font-semibold text-slate-600 justify-center">
                    U
                </span>
                <span class="text-xs font-medium text-slate-700 hidden sm:inline">Operator</span>
            </div>
        </div>
    </header>
</template>

