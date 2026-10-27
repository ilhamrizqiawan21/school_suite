<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps<{
    share: any;
    resource: { type: string; id: number; name: string; content: any };
    can_copy: boolean;
}>();

function labelPermission(value: string) {
    return ({ view: 'Lihat isi', copy: 'Salin ke bank sendiri', edit: 'Edit sumber bersama' } as Record<string, string>)[value] ?? value;
}

function labelEvent(value: string) {
    return ({ requested: 'Mengirim permintaan', accepted: 'Menerima', rejected: 'Menolak', revoked: 'Mencabut akses', copied: 'Menyalin sumber' } as Record<string, string>)[value] ?? value;
}

function labelStatus(value: string) {
    return ({ pending: 'Menunggu keputusan', accepted: 'Diterima', rejected: 'Ditolak', revoked: 'Dicabut' } as Record<string, string>)[value] ?? value;
}

function copyShare(id: number) {
    if (!window.confirm('Salin sumber ini ke bank soal Anda sebagai draf baru?')) return;

    router.post(`/penilaian/berbagi-soal/${id}/salin`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Detail berbagi" />
    <AppLayout>
        <main class="mx-auto max-w-4xl space-y-5 p-4 md:p-6">
            <Link href="/penilaian/berbagi-soal" class="inline-flex items-center text-sm font-medium text-indigo-700 underline">← Kembali ke berbagi</Link>
            <header class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">{{ share.resource_type === 'question' ? 'Soal' : 'Paket soal' }}</p>
                <div class="mt-2 flex flex-wrap items-start justify-between gap-3"><div><h1 class="text-2xl font-semibold text-slate-900">{{ resource.name }}</h1><p class="mt-1 text-sm text-slate-500">Dari {{ share.sender?.name }} kepada {{ share.recipient?.name }}</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">{{ labelStatus(share.status) }}</span></div>
                <div class="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-2"><p><span class="font-medium text-slate-800">Izin:</span> {{ labelPermission(share.permission) }}</p><p><span class="font-medium text-slate-800">Dibuat:</span> {{ share.created_at }}</p></div>
                <p v-if="share.request_note" class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">{{ share.request_note }}</p>
            </header>
            <div v-if="share.status !== 'accepted'" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Isi sumber hanya bisa dilihat setelah permintaan disetujui dan selama akses belum dicabut.</div>
            <section v-else class="space-y-3 rounded-xl border border-slate-200 bg-white p-5">
                <template v-if="resource.type === 'question'"><p class="text-xs text-slate-500">{{ resource.content.type }} · {{ resource.content.points }} poin</p><div class="whitespace-pre-wrap font-medium">{{ resource.content.prompt }}</div><ol v-if="resource.content.options" class="list-inside list-[upper-alpha] space-y-1"> <li v-for="option in resource.content.options" :key="option">{{ option }}</li></ol><div v-if="resource.content.matching_pairs" class="space-y-1"><p v-for="pair in resource.content.matching_pairs" :key="pair.left">{{ pair.left }} ↔ {{ pair.right }}</p></div><p v-if="resource.content.essay_rubric" class="text-sm">Rubrik: {{ resource.content.essay_rubric }}</p><p class="text-sm text-emerald-700">Kunci: {{ resource.content.correct_answer }}</p><p v-if="resource.content.explanation" class="text-sm text-slate-600">{{ resource.content.explanation }}</p></template>
                <template v-else><p class="text-sm text-slate-600">{{ resource.content.description }}</p><p class="text-xs text-slate-500">{{ resource.content.jenjang }} · {{ resource.content.curriculum }} · {{ resource.content.items.length }} soal</p><article v-for="item in resource.content.items" :key="item.sequence" class="rounded border p-3"><p class="text-xs text-slate-500">{{ item.sequence }} · {{ item.type }} · {{ item.points }} poin</p><p class="mt-1 whitespace-pre-wrap">{{ item.prompt }}</p></article></template>
            </section>
            <button v-if="can_copy" @click="copyShare(share.id)" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Salin ke milik saya</button>
            <section class="rounded-xl border border-slate-200 bg-white p-5"><h2 class="mb-3 font-semibold">Riwayat</h2><ol v-if="share.events?.length" class="space-y-2 text-sm"><li v-for="event in share.events" :key="event.id" class="border-l-2 border-indigo-200 pl-3"><span class="font-medium">{{ event.actor?.name }}</span> · {{ event.event_type }} <span class="text-slate-500">{{ event.old_status ?? '—' }} → {{ event.new_status ?? '—' }} · {{ event.created_at }}</span><p v-if="event.note" class="text-slate-600">{{ event.note }}</p></li></ol><p v-else class="text-sm text-slate-500">Belum ada catatan aktivitas.</p></section>
        </main>
    </AppLayout>
</template>
