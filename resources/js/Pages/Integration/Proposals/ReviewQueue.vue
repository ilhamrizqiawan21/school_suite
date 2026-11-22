<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import AppCard from '@/components/AppCard.vue';
import AppButton from '@/components/AppButton.vue';
import MetricSummaryCard from '@/components/MetricSummaryCard.vue';
import StatusBadge, { type StatusTone } from '@/components/StatusBadge.vue';
import StateFeedback from '@/components/StateFeedback.vue';

interface BudgetRow {
    name: string;
    qty: number;
    unit: string;
    unit_price: number;
    subtotal?: number;
}

interface ProposalItem {
    id: number;
    proposal_code: string;
    proposal_mode: 'teacher_work' | 'independent';
    proposal_mode_label: string;
    title: string;
    activity_type_label: string;
    organizer_name: string;
    organizer_unit?: string | null;
    start_date: string;
    purpose: string;
    budget_items: BudgetRow[];
    total_requested_amount: number;
    revision_number: number;
    attachment_document_id?: number | null;
    status: string;
    status_label: string;
    review_note?: string | null;
    submitted_by_name?: string | null;
    lpj_realization_amount?: number | null;
    lpj_summary?: string | null;
    lpj_document_ids: number[];
}

const props = defineProps<{
    proposals: ProposalItem[];
    activeMode: string | null;
    activityTypes: Record<string, string>;
    statusLabels: Record<string, string>;
}>();

const notes = ref<Record<number, string>>({});

const metrics = computed(() => ({
    total: props.proposals.length,
    waiting: props.proposals.filter((p) => p.status === 'submitted' || p.status === 'in_review').length,
    lpjPending: props.proposals.filter((p) => p.status === 'lpj_submitted').length,
    closed: props.proposals.filter((p) => p.status === 'closed').length,
}));

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);

const toneForStatus = (status: string): StatusTone => {
    if (status === 'approved' || status === 'closed') return 'success';
    if (status === 'needs_revision' || status === 'lpj_submitted') return 'warning';
    if (status === 'rejected') return 'danger';
    return 'info';
};

const decide = (proposalId: number, decision: string) => {
    router.post(`/administrasi/proposal/${proposalId}/keputusan`, {
        decision,
        note: notes.value[proposalId] ?? '',
    }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Telaah Proposal & LPJ" />

    <AppLayout title="Telaah Proposal & LPJ">
        <div class="space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-[var(--ink)]">Antrean Telaah Proposal & LPJ</h1>
                    <p class="mt-1 text-sm text-[var(--ink-soft)]">
                        Evaluasi usulan penugasan guru maupun kegiatan mandiri organisasi/siswa beserta penutupan LPJ pasca-kegiatan.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        href="/administrasi/proposal"
                        class="rounded-lg border px-3 py-1.5 text-xs font-semibold"
                        :class="!activeMode ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 bg-white text-slate-700'"
                    >
                        Semua Jalur
                    </Link>
                    <Link
                        href="/administrasi/proposal?mode=teacher_work"
                        class="rounded-lg border px-3 py-1.5 text-xs font-semibold"
                        :class="activeMode === 'teacher_work' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 bg-white text-slate-700'"
                    >
                        Penugasan Guru
                    </Link>
                    <Link
                        href="/administrasi/proposal?mode=independent"
                        class="rounded-lg border px-3 py-1.5 text-xs font-semibold"
                        :class="activeMode === 'independent' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 bg-white text-slate-700'"
                    >
                        Mandiri Siswa/OSIS
                    </Link>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <MetricSummaryCard label="Total Usulan" :value="metrics.total" icon="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z" tone="blue" />
                <MetricSummaryCard label="Perlu Telaah" :value="metrics.waiting" icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" tone="amber" />
                <MetricSummaryCard label="LPJ Siap Ditutup" :value="metrics.lpjPending" icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" tone="violet" />
                <MetricSummaryCard label="Selesai Ditutup" :value="metrics.closed" icon="M5 13l4 4L19 7" tone="green" />
            </div>

            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                Batas cakupan (SS-D-009): Reviewer menyetujui rencana kegiatan & menutup LPJ secara administratif tanpa fitur pencairan kas atau transfer rekening bank.
            </div>

            <AppCard>
                <div class="p-5 space-y-4">
                    <StateFeedback
                        v-if="proposals.length === 0"
                        state="empty"
                        title="Antrean proposal kosong"
                        description="Belum ada proposal pada filter jalur ini."
                    />

                    <div v-else class="space-y-4">
                        <div
                            v-for="item in proposals"
                            :key="item.id"
                            class="rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4 space-y-3"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono text-xs font-semibold text-[var(--ink-faint)]">{{ item.proposal_code }}</span>
                                        <StatusBadge :status="item.proposal_mode" :label="item.proposal_mode_label" :tone="item.proposal_mode === 'teacher_work' ? 'info' : 'neutral'" />
                                        <StatusBadge :status="item.status" :label="`${item.status_label} (v${item.revision_number})`" :tone="toneForStatus(item.status)" />
                                    </div>
                                    <h3 class="mt-1 text-base font-semibold text-[var(--ink)]">{{ item.title }}</h3>
                                    <p class="text-xs text-[var(--ink-soft)]">
                                        {{ item.activity_type_label }} · {{ item.organizer_name }} · {{ item.start_date }} · RAB: <strong>{{ formatCurrency(item.total_requested_amount) }}</strong>
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <a
                                        v-if="item.attachment_document_id"
                                        :href="`/proposal/${item.id}/dokumen/${item.attachment_document_id}`"
                                        class="rounded-md border border-[var(--line)] bg-[var(--surface-subtle)] px-2.5 py-1.5 text-xs font-medium text-[var(--ink-soft)] hover:bg-[var(--surface-strong)]"
                                    >
                                        Lampiran Proposal
                                    </a>
                                    <a
                                        v-for="(docId, idx) in item.lpj_document_ids"
                                        :key="docId"
                                        :href="`/proposal/${item.id}/dokumen/${docId}`"
                                        class="rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-800 hover:bg-emerald-100"
                                    >
                                        Bukti LPJ #{{ idx + 1 }}
                                    </a>
                                </div>
                            </div>

                            <p class="text-sm text-[var(--ink-soft)]">{{ item.purpose }}</p>

                            <div v-if="item.lpj_realization_amount !== null && item.lpj_realization_amount !== undefined" class="rounded-lg border border-emerald-200 bg-emerald-50/70 p-3 text-xs text-emerald-950">
                                <strong>Realisasi LPJ:</strong> {{ formatCurrency(item.lpj_realization_amount) }} — {{ item.lpj_summary }}
                            </div>

                            <div
                                v-if="['submitted', 'in_review', 'needs_revision', 'lpj_submitted'].includes(item.status)"
                                class="border-t border-[var(--line)] pt-3 space-y-3"
                            >
                                <input
                                    v-model="notes[item.id]"
                                    type="text"
                                    placeholder="Catatan keputusan / alasan revisi atau penolakan..."
                                    class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm"
                                >
                                <div class="flex flex-wrap justify-end gap-2">
                                    <AppButton v-if="item.status === 'submitted'" variant="secondary" size="sm" @click="decide(item.id, 'in_review')">
                                        Mulai Telaah
                                    </AppButton>
                                    <AppButton v-if="['submitted', 'in_review'].includes(item.status)" variant="warning" size="sm" @click="decide(item.id, 'needs_revision')">
                                        Minta Revisi
                                    </AppButton>
                                    <AppButton v-if="['submitted', 'in_review', 'needs_revision'].includes(item.status)" variant="danger" size="sm" @click="decide(item.id, 'rejected')">
                                        Tolak
                                    </AppButton>
                                    <AppButton v-if="['submitted', 'in_review'].includes(item.status)" variant="primary" size="sm" @click="decide(item.id, 'approved')">
                                        Setujui Proposal
                                    </AppButton>
                                    <AppButton v-if="item.status === 'lpj_submitted'" variant="primary" size="sm" @click="decide(item.id, 'closed')">
                                        Tutup LPJ & Selesaikan
                                    </AppButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </AppCard>
        </div>
    </AppLayout>
</template>
