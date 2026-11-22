<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

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

const rejectingProfile = ref<AlumniRow | null>(null);
const rejectionNote = ref('');

const moderate = (id: number, decision: 'approved' | 'rejected') => {
    if (decision === 'rejected') {
        const target = props.profiles.find((p) => p.id === id) ?? null;
        rejectingProfile.value = target;
        rejectionNote.value = '';
        return;
    }
    router.post(`/akademik/alumni/${id}/moderasi`, {
        moderation_status: decision,
        moderation_note: null,
        issue_ekta: true,
    }, { preserveScroll: true });
};

const submitReject = () => {
    if (!rejectingProfile.value || !rejectionNote.value.trim()) return;
    router.post(`/akademik/alumni/${rejectingProfile.value.id}/moderasi`, {
        moderation_status: 'rejected',
        moderation_note: rejectionNote.value.trim(),
        issue_ekta: false,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            rejectingProfile.value = null;
            rejectionNote.value = '';
        },
    });
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
                        <thead class="bg-slate-50 text-center text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2.5 text-center">Alumni</th>
                                <th class="px-3 py-2.5 text-center">Status &amp; Instansi</th>
                                <th class="px-3 py-2.5 text-center">Direktori</th>
                                <th class="px-3 py-2.5 text-center">Moderasi</th>
                                <th class="px-3 py-2.5 text-center">Aksi</th>
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

        <div
            v-if="rejectingProfile"
            role="dialog"
            aria-modal="true"
            tabindex="-1"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="rejectingProfile = null"
            @keydown.esc="rejectingProfile = null"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900">Tolak Moderasi Alumni</h3>
                    <button type="button" class="text-sm text-slate-500 hover:text-slate-700" @click="rejectingProfile = null">Tutup</button>
                </div>
                <form class="space-y-3" @submit.prevent="submitReject">
                    <p class="text-xs text-slate-600">Alumni: <strong>{{ rejectingProfile.full_name }}</strong></p>
                    <div>
                        <label for="alumni-reject-note" class="block text-xs font-semibold text-slate-700">Alasan Penolakan</label>
                        <textarea
                            id="alumni-reject-note"
                            v-model="rejectionNote"
                            rows="3"
                            required
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        />
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <AppButton type="button" variant="secondary" @click="rejectingProfile = null">Batal</AppButton>
                        <AppButton type="submit">Simpan Penolakan</AppButton>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
