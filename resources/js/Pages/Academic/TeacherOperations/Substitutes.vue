<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

interface Slot { id: number; subject: string | null; class_name: string | null; start_time: string; end_time: string; substitute: { id: number; name: string } | null; candidates: { id: number; name: string; nip: string | null }[] }
const props = defineProps<{ attendance: { id: number; date: string; status: string; teacher_name: string }; slots: Slot[] }>();
const page = usePage();
const message = computed(() => (page.props.flash as { message?: string }).message);
const errors = computed(() => page.props.errors as Record<string, string>);
const choices = reactive<Record<number, string>>({});
const notes = reactive<Record<number, string>>({});
const dateLabel = computed(() => new Date(`${props.attendance.date}T00:00:00`).toLocaleDateString('id-ID', { dateStyle: 'long' }));

function assign(slot: Slot) {
    router.post(`/akademik/absensi-guru/${props.attendance.id}/pengganti`, {
        schedule_id: slot.id,
        substitute_teacher_id: choices[slot.id],
        notes: notes[slot.id] ?? '',
    }, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Guru Pengganti">
        <Head title="Guru Pengganti" />
        <main class="view space-y-6">
            <section class="hero-card">
                <div><p class="eyebrow">Absensi guru · {{ dateLabel }}</p><h1>Atur guru pengganti</h1><p>{{ attendance.teacher_name }} berstatus {{ attendance.status }}. Pilih guru pengganti untuk sesi jadwal yang terdampak.</p></div>
                <Link class="secondary-button" href="/akademik/absensi-guru">Kembali ke absensi</Link>
            </section>
            <p v-if="message" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">{{ message }}</p>
            <p v-if="errors.schedule_id || errors.substitute_teacher_id" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800" role="alert">{{ errors.schedule_id || errors.substitute_teacher_id }}</p>
            <section class="panel overflow-hidden">
                <div v-if="!slots.length" class="px-6 py-14 text-center"><strong class="font-semibold text-slate-900">Tidak ada sesi terjadwal</strong><p class="mt-1 text-sm text-slate-500">Tidak ada jadwal terbit untuk guru ini pada tanggal tersebut.</p></div>
                <div v-else class="divide-y divide-slate-100">
                    <article v-for="slot in slots" :key="slot.id" class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_minmax(260px,380px)]">
                        <div><span class="text-xs font-semibold uppercase tracking-wide text-blue-700">{{ slot.start_time }}–{{ slot.end_time }}</span><h2 class="mt-1 font-semibold text-slate-900">{{ slot.subject || 'Mata pelajaran' }}</h2><p class="mt-1 text-sm text-slate-500">Kelas {{ slot.class_name || '—' }}</p><p v-if="slot.substitute" class="mt-3 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-800">Pengganti: {{ slot.substitute.name }}</p></div>
                        <form v-if="!slot.substitute" class="flex flex-col justify-center gap-2" @submit.prevent="assign(slot)">
                            <select v-model="choices[slot.id]" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm" required :disabled="!slot.candidates.length" :aria-label="`Guru pengganti untuk ${slot.subject}`"><option value="">{{ slot.candidates.length ? 'Pilih guru pengganti' : 'Tidak ada guru yang tersedia' }}</option><option v-for="candidate in slot.candidates" :key="candidate.id" :value="String(candidate.id)">{{ candidate.name }}<template v-if="candidate.nip"> · {{ candidate.nip }}</template></option></select>
                            <input v-model="notes[slot.id]" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm" maxlength="255" placeholder="Catatan opsional" />
                            <div class="flex justify-end"><button class="primary-button" :disabled="!slot.candidates.length">Tetapkan pengganti</button></div>
                        </form>
                    </article>
                </div>
            </section>
        </main>
    </AppLayout>
</template>
