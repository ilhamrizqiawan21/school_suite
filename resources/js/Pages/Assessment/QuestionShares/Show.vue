<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
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
        <div class="view share-detail-page mx-auto max-w-4xl space-y-5">
            <Link href="/penilaian/berbagi-soal" class="inline-flex items-center text-sm font-medium text-indigo-700 underline">← Kembali ke berbagi</Link>
            <header class="share-panel rounded-xl border border-[var(--line)] bg-[var(--surface)] p-5">
                <p class="text-meta font-semibold uppercase tracking-wide text-indigo-600">{{ share.resource_type === 'question' ? 'Soal' : 'Paket soal' }}</p>
                <div class="mt-2 flex flex-wrap items-start justify-between gap-3"><div><h1 class="text-2xl font-semibold text-slate-900">{{ resource.name }}</h1><p class="mt-1 text-sm text-slate-600">Dari {{ share.sender?.name }} kepada {{ share.recipient?.name }}</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">{{ labelStatus(share.status) }}</span></div>
                <div class="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-2"><p><span class="font-medium text-slate-800">Izin:</span> {{ labelPermission(share.permission) }}</p><p><span class="font-medium text-slate-800">Dibuat:</span> {{ share.created_at }}</p></div>
                <p v-if="share.request_note" class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">{{ share.request_note }}</p>
            </header>
            <div v-if="share.status !== 'accepted'" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Isi sumber hanya bisa dilihat setelah permintaan disetujui dan selama akses belum dicabut.</div>
            <section v-else class="share-panel space-y-3 rounded-xl border border-[var(--line)] bg-[var(--surface)] p-5">
                <template v-if="resource.type === 'question'"><p class="text-meta text-slate-600">{{ resource.content.type }} · {{ resource.content.points }} poin</p><div class="whitespace-pre-wrap font-medium">{{ resource.content.prompt }}</div><ol v-if="resource.content.options" class="list-inside list-[upper-alpha] space-y-1"> <li v-for="option in resource.content.options" :key="option">{{ option }}</li></ol><div v-if="resource.content.matching_pairs" class="space-y-1"><p v-for="pair in resource.content.matching_pairs" :key="pair.left">{{ pair.left }} ↔ {{ pair.right }}</p></div><p v-if="resource.content.essay_rubric" class="text-sm">Rubrik: {{ resource.content.essay_rubric }}</p><p class="text-sm text-emerald-700">Kunci: {{ resource.content.correct_answer }}</p><p v-if="resource.content.explanation" class="text-sm text-slate-600">{{ resource.content.explanation }}</p></template>
                <template v-else><p class="text-sm text-slate-600">{{ resource.content.description }}</p><p class="text-meta text-slate-600">{{ resource.content.jenjang }} · {{ resource.content.curriculum }} · {{ resource.content.items.length }} soal</p><article v-for="item in resource.content.items" :key="item.sequence" class="rounded border p-3"><p class="text-meta text-slate-600">{{ item.sequence }} · {{ item.type }} · {{ item.points }} poin</p><p class="mt-1 whitespace-pre-wrap">{{ item.prompt }}</p></article></template>
            </section>
            <AppButton variant="primary" v-if="can_copy" type="button" @click="copyShare(share.id)" class="primary-button">Salin ke milik saya</AppButton>
            <section class="share-panel rounded-xl border border-[var(--line)] bg-[var(--surface)] p-5"><h2 class="mb-3 font-semibold">Riwayat</h2><ol v-if="share.events?.length" class="space-y-2 text-sm"><li v-for="event in share.events" :key="event.id" class="border-l-2 border-indigo-200 pl-3"><span class="font-medium">{{ event.actor?.name }}</span> · {{ event.event_type }} <span class="text-slate-600">{{ event.old_status ?? '—' }} → {{ event.new_status ?? '—' }} · {{ event.created_at }}</span><p v-if="event.note" class="text-slate-600">{{ event.note }}</p></li></ol><p v-else class="text-sm text-slate-600">Belum ada catatan aktivitas.</p></section>
        </div>
    </AppLayout>
</template>

<style scoped>
.share-detail-page{padding:0;max-width:1100px}.share-detail-page>header{border-bottom:1px solid var(--line)}.share-detail-page>header h1{color:var(--ink);font-size:var(--type-3xl);font-weight:var(--weight-bold);letter-spacing:-.035em}.share-panel{background:var(--surface);border-color:var(--line);color:var(--ink);box-shadow:var(--shadow-sm)}.share-detail-page .text-indigo-700,.share-detail-page .text-indigo-600{color:var(--primary)}.share-detail-page .text-slate-900,.share-detail-page .text-slate-800,.share-detail-page .text-slate-700{color:var(--ink)}.share-detail-page .text-slate-600,.share-detail-page .text-slate-500{color:var(--ink-soft)}.share-detail-page .bg-slate-100,.share-detail-page .bg-slate-50{background:var(--surface-subtle)}.share-detail-page .border-slate-200{border-color:var(--line)}.share-detail-page .border-indigo-200{border-color:var(--primary-soft)}@media(max-width:640px){.share-panel{padding:1rem}.share-detail-page>button{width:100%}}
</style>
