<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';

interface AlumniRow {
    id: number;
    full_name: string;
    graduation_year: number;
    current_status: string;
    institution_or_company?: string | null;
    moderation_status: string;
    opt_in_directory: boolean;
    ekta_number?: string | null;
}

const props = defineProps<{
    profiles: AlumniRow[];
    statusLabels: Record<string, string>;
    filters: { status: string };
}>();

const moderate = (id: number, decision: 'approved' | 'rejected') => {
    const note = decision === 'rejected' ? window.prompt('Alasan penolakan moderasi alumni:') : null;
    if (decision === 'rejected' && !note) return;
    router.post(`/akademik/alumni/${id}/moderasi`, {
        moderation_status: decision,
        moderation_note: note,
        issue_ekta: decision === 'approved',
    }, { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Moderasi Alumni &amp; Tracer">
        <Head title="Moderasi Alumni &amp; Tracer" />

        <div class="space-y-6">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Moderasi Profil Alumni, Tracer &amp; E-KTA</h1>
                <p class="text-sm text-slate-600">Setujui publikasi profil alumni ke direktori dan terbitkan Kartu Alumni Elektronik (E-KTA).</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <StateFeedback
                    v-if="profiles.length === 0"
                    state="empty"
                    title="Belum ada pengajuan alumni"
                    description="Data alumni yang masuk melalui portal tracer akan muncul di sini."
                />

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2.5">Alumni</th>
                                <th class="px-3 py-2.5">Status &amp; Instansi</th>
                                <th class="px-3 py-2.5">Direktori</th>
                                <th class="px-3 py-2.5">Moderasi</th>
                                <th class="px-3 py-2.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="p in profiles" :key="p.id">
                                <td class="px-3 py-3">
                                    <p class="font-medium">{{ p.full_name }}</p>
                                    <p class="text-xs text-slate-500">Angkatan {{ p.graduation_year }} • E-KTA: {{ p.ekta_number || '-' }}</p>
                                </td>
                                <td class="px-3 py-3 text-xs">
                                    <p>{{ statusLabels[p.current_status] || p.current_status }}</p>
                                    <p class="text-slate-500">{{ p.institution_or_company || '-' }}</p>
                                </td>
                                <td class="px-3 py-3 text-xs">
                                    {{ p.opt_in_directory ? 'Opt-In Publikasi' : 'Privat' }}
                                </td>
                                <td class="px-3 py-3">
                                    <StatusBadge :status="p.moderation_status" :tone="p.moderation_status === 'approved' ? 'success' : (p.moderation_status === 'rejected' ? 'danger' : 'warning')" size="sm" />
                                </td>
                                <td class="px-3 py-3 text-right space-x-2">
                                    <AppButton type="button" @click="moderate(p.id, 'approved')">Setujui &amp; Terbit E-KTA</AppButton>
                                    <AppButton type="button" variant="secondary" @click="moderate(p.id, 'rejected')">Tolak</AppButton>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
