<script setup lang="ts">
import { usePublicContentText } from '@/composables/usePublicContentText';
import PublicHeader from '@/components/PublicHeader.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { Link } from '@inertiajs/vue3';
import Head from '@/components/PublicHead.vue';
import { computed } from 'vue';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    transparency: {
        totals: { total: number; in_progress: number; resolved: number };
        by_category: { category: string; label: string; total: number; resolved: number }[];
        published_summaries: { id: number; category_label: string; status: string; summary: string; resolved_at?: string | null }[];
    };
}>();

const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const publicText = usePublicContentText();
</script>

<template>
    <Head :title="`Transparansi Pengaduan — ${school.name}`" />

    <div class="min-h-screen bg-[#f8f6f0]" :style="themeStyle">
        <PublicHeader :school="school" />

        <main class="mx-auto max-w-5xl px-4 py-10 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">{{ publicText("copy.vue_services_complainttransparency", "text_001", "Transparansi Penanganan Aduan Satgas") }}</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ publicText("copy.vue_services_complainttransparency", "text_002", "Statistik agregat dan ringkasan tindak lanjut termoderasi tanpa menampilkan identitas pelapor maupun bukti sensitif.") }}</p>
                </div>
                <Link href="/layanan/lapor" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white">
                    {{ publicText("copy.vue_services_complainttransparency", "text_003", "Buat Laporan Baru") }}
                </Link>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ publicText("copy.vue_services_complainttransparency", "text_004", "Total Laporan Masuk") }}</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ transparency.totals.total }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ publicText("copy.vue_services_complainttransparency", "text_005", "Sedang Ditangani") }}</p>
                    <p class="mt-1 text-2xl font-bold text-amber-700">{{ transparency.totals.in_progress }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ publicText("copy.vue_services_complainttransparency", "text_006", "Selesai / Ditutup") }}</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-700">{{ transparency.totals.resolved }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-900">{{ publicText("copy.vue_services_complainttransparency", "text_007", "Ringkasan Tindak Lanjut Termoderasi") }}</h2>
                <StateFeedback
                    v-if="transparency.published_summaries.length === 0"
                    state="empty"
                    :title="publicText('copy.vue_services_complainttransparency', 'title_008', 'Belum ada ringkasan yang dipublikasikan')"
                    :description="publicText('copy.vue_services_complainttransparency', 'description_009', 'Ringkasan penyelesaian laporan yang disetujui Satgas akan tampil di sini secara anonim.')"
                />
                <div v-else class="space-y-3">
                    <article v-for="item in transparency.published_summaries" :key="item.id" class="rounded-xl border border-slate-200 p-4 text-sm space-y-1">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-semibold text-slate-500">{{ item.category_label }}</span>
                            <StatusBadge :status="item.status" tone="success" size="sm" />
                        </div>
                        <p class="text-slate-800">{{ item.summary }}</p>
                        <p v-if="item.resolved_at" class="text-xs text-slate-500">{{ publicText("copy.vue_services_complainttransparency", "text_9001", "Diselesaikan:") }} {{ item.resolved_at }}</p>
                    </article>
                </div>
            </div>
        </main>
    </div>
</template>
