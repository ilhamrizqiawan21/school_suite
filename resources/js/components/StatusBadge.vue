<script setup lang="ts">
import { computed } from 'vue';

export type StatusTone = 'neutral' | 'info' | 'warning' | 'success' | 'danger';

const props = withDefaults(defineProps<{
    status: string;
    label?: string;
    tone?: StatusTone;
    size?: 'sm' | 'md';
}>(), {
    label: undefined,
    tone: undefined,
    size: 'md',
});

const STATUS_MAP: Record<string, { label: string; tone: StatusTone; icon: string }> = {
    draft: { label: 'Draf', tone: 'neutral', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
    submitted: { label: 'Diajukan', tone: 'info', icon: 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8' },
    in_review: { label: 'Ditinjau', tone: 'info', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    needs_revision: { label: 'Perlu Perbaikan', tone: 'warning', icon: 'M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z' },
    warning: { label: 'Perhatian', tone: 'warning', icon: 'M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z' },
    approved: { label: 'Disetujui', tone: 'success', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    completed: { label: 'Selesai', tone: 'success', icon: 'M5 13l4 4L19 7' },
    published: { label: 'Terbit', tone: 'success', icon: 'M5 13l4 4L19 7' },
    active: { label: 'Aktif', tone: 'success', icon: 'M5 13l4 4L19 7' },
    rejected: { label: 'Ditolak', tone: 'danger', icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z' },
    cancelled: { label: 'Dibatalkan', tone: 'danger', icon: 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' },
    expired: { label: 'Kedaluwarsa', tone: 'neutral', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    inactive: { label: 'Nonaktif', tone: 'neutral', icon: 'M18 12H6' },
};

const normalizedKey = computed(() => props.status.trim().toLowerCase());
const resolved = computed(() => STATUS_MAP[normalizedKey.value] ?? {
    label: props.status,
    tone: 'neutral' as StatusTone,
    icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
});

const displayLabel = computed(() => props.label || resolved.value.label);
const displayTone = computed(() => props.tone || resolved.value.tone);
</script>

<template>
    <span class="ss-status-badge" :class="[`ss-status-badge--${displayTone}`, `ss-status-badge--${size}`]">
        <svg class="ss-status-badge__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" :d="resolved.icon" />
        </svg>
        <span>{{ displayLabel }}</span>
    </span>
</template>

<style scoped>
.ss-status-badge {
    display: inline-flex;
    align-items: center;
    gap: .375rem;
    padding: .25rem .625rem;
    border-radius: var(--radius-pill, 999px);
    font-size: var(--type-meta, .8125rem);
    font-weight: var(--weight-semibold, 600);
    line-height: 1.25;
    white-space: nowrap;
    border: 1px solid transparent;
}

.ss-status-badge--sm {
    padding: .15rem .5rem;
    font-size: var(--type-caption, .75rem);
}

.ss-status-badge__icon {
    width: .9rem;
    height: .9rem;
    flex: 0 0 auto;
}

.ss-status-badge--neutral {
    color: var(--ink-soft, #536174);
    background: var(--surface-strong, #edf1f7);
    border-color: var(--line, #dee4ee);
}

.ss-status-badge--info {
    color: var(--blue, #3167b9);
    background: var(--blue-soft, #e6effd);
    border-color: color-mix(in srgb, var(--blue, #3167b9) 24%, transparent);
}

.ss-status-badge--warning {
    color: var(--gold, #80500d);
    background: var(--gold-soft, #fff1d5);
    border-color: color-mix(in srgb, var(--gold, #80500d) 26%, transparent);
}

.ss-status-badge--success {
    color: var(--green, #277450);
    background: var(--green-soft, #e1f2e8);
    border-color: color-mix(in srgb, var(--green, #277450) 24%, transparent);
}

.ss-status-badge--danger {
    color: var(--red, #b13f38);
    background: var(--red-soft, #fbe9e7);
    border-color: var(--delete-line, #f4c6cd);
}
</style>
