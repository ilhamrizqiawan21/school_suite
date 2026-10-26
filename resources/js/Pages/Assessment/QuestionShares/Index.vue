<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps<{ sent: any; received: any; recipients: { id: number; name: string; username: string }[]; resource: { type?: string; id?: string } }>();
const form = useForm({
    resource_type: ['question', 'pack'].includes(String(props.resource.type)) ? String(props.resource.type) : 'question',
    resource_id: props.resource.id ? Number(props.resource.id) : '',
    recipient_id: '', permission: 'view', request_note: '',
});
const decision = useForm({ decision_note: '' });
const page = usePage<any>();

function submit() { form.post('/penilaian/berbagi-soal', { preserveScroll: true }); }
function decide(id: number, status: 'accept' | 'reject') { router.post(`/penilaian/berbagi-soal/${id}/${status}`, { decision_note: decision.decision_note }, { preserveScroll: true }); }
function revoke(id: number) { router.post(`/penilaian/berbagi-soal/${id}/cabut`, {}, { preserveScroll: true }); }
function labelPermission(value: string) { return ({ view: 'Lihat', copy: 'Salin', edit: 'Edit sumber' } as Record<string, string>)[value] ?? value; }
function labelStatus(value: string) { return ({ pending: 'Menunggu', accepted: 'Diterima', rejected: 'Ditolak', revoked: 'Dicabut' } as Record<string, string>)[value] ?? value; }
</script>

<template>
    <Head title="Berbagi Bank Soal" />
    <AppLayout>
        <div class="view teacher-sharing-page space-y-6">
            <section class="hero-card compact-hero">
                <div><p class="eyebrow">Kolaborasi bank soal</p><h1>Berbagi soal dan paket</h1><p>Penerima mendapat akses sesuai izin setelah menyetujui. Kepemilikan sumber tetap pada pembuatnya.</p></div>
            </section>
            <div v-if="page.props.flash?.message" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800" role="status">{{ page.props.flash.message }}</div>
            <form @submit.prevent="submit" class="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 md:grid-cols-2">
                <h2 class="text-base font-semibold md:col-span-2">Kirim permintaan berbagi</h2>
                <label class="text-sm">Jenis sumber<select v-model="form.resource_type" class="mt-1 w-full rounded-lg border-slate-300"><option value="question">Soal</option><option value="pack">Paket soal</option></select></label>
                <label class="text-sm">ID sumber<input v-model.number="form.resource_id" type="number" min="1" required class="mt-1 w-full rounded-lg border-slate-300" /><small class="block text-slate-500">Buka dari tombol “Bagikan” pada katalog untuk mengisi otomatis.</small></label>
                <label class="text-sm">Penerima<select v-model="form.recipient_id" required class="mt-1 w-full rounded-lg border-slate-300"><option value="">Pilih guru/pengelola</option><option v-for="user in recipients" :key="user.id" :value="user.id">{{ user.name }} · {{ user.username }}</option></select><small v-if="form.errors.recipient_id" class="text-red-600">{{ form.errors.recipient_id }}</small></label>
                <label class="text-sm">Izin<select v-model="form.permission" class="mt-1 w-full rounded-lg border-slate-300"><option value="view">Lihat isi sumber</option><option value="copy">Salin menjadi milik penerima</option><option value="edit">Ubah sumber (pemilik tetap)</option></select></label>
                <label class="text-sm md:col-span-2">Catatan<textarea v-model="form.request_note" maxlength="2000" rows="2" class="mt-1 w-full rounded-lg border-slate-300" /></label>
                <div class="md:col-span-2"><button :disabled="form.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Kirim permintaan</button></div>
            </form>

            <section class="grid gap-6 lg:grid-cols-2">
                <label class="text-sm lg:col-span-2">Catatan keputusan penerimaan/penolakan<textarea v-model="decision.decision_note" maxlength="2000" rows="2" class="mt-1 w-full rounded-lg border-slate-300" /></label>
                <div class="rounded-xl border border-slate-200 bg-white p-5"><h2 class="mb-4 font-semibold">Permintaan masuk</h2><div v-if="!received.data.length" class="text-sm text-slate-500">Belum ada permintaan.</div><article v-for="share in received.data" :key="share.id" class="mb-3 rounded-lg border border-slate-200 p-3 text-sm">
                    <div class="flex items-start justify-between gap-3"><div><p class="font-medium">{{ share.resource_type === 'question' ? 'Soal' : 'Paket' }} #{{ share.resource_id }} · {{ labelPermission(share.permission) }}</p><p class="text-slate-500">Dari {{ share.sender?.name }} · {{ labelStatus(share.status) }}</p><p v-if="share.request_note" class="mt-1 text-slate-600">{{ share.request_note }}</p></div><Link :href="`/penilaian/berbagi-soal/${share.id}`" class="text-indigo-700 underline">Detail</Link></div>
                    <div v-if="share.status === 'pending'" class="mt-3 flex gap-2"><button @click="decide(share.id, 'accept')" class="rounded bg-emerald-600 px-3 py-1.5 text-white">Terima</button><button @click="decide(share.id, 'reject')" class="rounded bg-slate-200 px-3 py-1.5">Tolak</button></div>
                    <div v-if="share.events?.length" class="mt-3 border-t pt-2 text-xs text-slate-500"><p v-for="event in share.events" :key="event.id">{{ event.actor?.name }} · {{ event.event_type }} · {{ event.created_at }}</p></div>
                </article></div>
                <div class="rounded-xl border border-slate-200 bg-white p-5"><h2 class="mb-4 font-semibold">Permintaan terkirim</h2><div v-if="!sent.data.length" class="text-sm text-slate-500">Belum ada permintaan terkirim.</div><article v-for="share in sent.data" :key="share.id" class="mb-3 rounded-lg border border-slate-200 p-3 text-sm">
                    <div class="flex items-start justify-between gap-3"><div><p class="font-medium">{{ share.resource_type === 'question' ? 'Soal' : 'Paket' }} #{{ share.resource_id }} · {{ labelPermission(share.permission) }}</p><p class="text-slate-500">Kepada {{ share.recipient?.name }} · {{ labelStatus(share.status) }}</p><p v-if="share.decision_note" class="mt-1 text-slate-600">{{ share.decision_note }}</p></div><Link :href="`/penilaian/berbagi-soal/${share.id}`" class="text-indigo-700 underline">Riwayat</Link></div>
                    <button v-if="['pending','accepted'].includes(share.status)" @click="revoke(share.id)" class="mt-3 rounded border border-red-200 px-3 py-1.5 text-red-700">Cabut akses</button>
                    <div v-if="share.events?.length" class="mt-3 border-t pt-2 text-xs text-slate-500"><p v-for="event in share.events" :key="event.id">{{ event.actor?.name }} · {{ event.event_type }} · {{ event.created_at }}</p></div>
                </article></div>
            </section>
        </div>
    </AppLayout>
</template>
