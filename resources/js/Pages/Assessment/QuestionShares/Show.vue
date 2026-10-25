<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
defineProps<{ share: any; resource: { type: string; id: number; name: string; content: any }; can_copy: boolean }>();
</script>

<template>
    <Head title="Detail berbagi" />
    <AppLayout>
        <main class="mx-auto max-w-4xl space-y-5 p-4 md:p-6">
            <Link href="/penilaian/berbagi-soal" class="text-sm text-indigo-700 underline">← Kembali ke berbagi</Link>
            <header><p class="text-xs font-semibold uppercase text-indigo-600">{{ share.resource_type === 'question' ? 'Soal' : 'Paket soal' }} · {{ share.status }}</p><h1 class="mt-1 text-2xl font-semibold">{{ resource.name }}</h1><p class="mt-1 text-sm text-slate-500">Izin: {{ share.permission }} · Pemilik: {{ share.sender?.name }} · {{ share.request_note }}</p></header>
            <div v-if="share.status !== 'accepted'" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm">Isi sumber hanya bisa dilihat setelah permintaan disetujui dan selama akses belum dicabut.</div>
            <section v-else class="space-y-3 rounded-xl border border-slate-200 bg-white p-5">
                <template v-if="resource.type === 'question'"><p class="text-xs text-slate-500">{{ resource.content.type }} · {{ resource.content.points }} poin</p><div class="whitespace-pre-wrap font-medium">{{ resource.content.prompt }}</div><ol v-if="resource.content.options" class="list-inside list-[upper-alpha] space-y-1"> <li v-for="option in resource.content.options" :key="option">{{ option }}</li></ol><div v-if="resource.content.matching_pairs" class="space-y-1"><p v-for="pair in resource.content.matching_pairs" :key="pair.left">{{ pair.left }} ↔ {{ pair.right }}</p></div><p v-if="resource.content.essay_rubric" class="text-sm">Rubrik: {{ resource.content.essay_rubric }}</p><p class="text-sm text-emerald-700">Kunci: {{ resource.content.correct_answer }}</p><p v-if="resource.content.explanation" class="text-sm text-slate-600">{{ resource.content.explanation }}</p></template>
                <template v-else><p class="text-sm text-slate-600">{{ resource.content.description }}</p><p class="text-xs text-slate-500">{{ resource.content.jenjang }} · {{ resource.content.curriculum }} · {{ resource.content.items.length }} soal</p><article v-for="item in resource.content.items" :key="item.sequence" class="rounded border p-3"><p class="text-xs text-slate-500">{{ item.sequence }} · {{ item.type }} · {{ item.points }} poin</p><p class="mt-1 whitespace-pre-wrap">{{ item.prompt }}</p></article></template>
            </section>
            <button v-if="can_copy" @click="router.post(`/penilaian/berbagi-soal/${share.id}/salin`)" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Salin ke milik saya</button>
            <section class="rounded-xl border border-slate-200 bg-white p-5"><h2 class="mb-3 font-semibold">Riwayat</h2><ol class="space-y-2 text-sm"><li v-for="event in share.events" :key="event.id" class="border-l-2 border-indigo-200 pl-3"><span class="font-medium">{{ event.actor?.name }}</span> · {{ event.event_type }} <span class="text-slate-500">{{ event.old_status ?? '—' }} → {{ event.new_status ?? '—' }} · {{ event.created_at }}</span><p v-if="event.note" class="text-slate-600">{{ event.note }}</p></li></ol></section>
        </main>
    </AppLayout>
</template>
