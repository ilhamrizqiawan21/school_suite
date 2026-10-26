<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface PackageItem { id: string; label: string; status: 'ready' | 'skipped'; reason: string }
interface PackagePlan { id: number; title: string; curriculum: string; items: PackageItem[] }
const props = defineProps<{ active_term: { id: number; name: string }; packages: PackagePlan[]; download_url: string }>();
const readyCount = computed(() => props.packages.reduce((sum, plan) => sum + plan.items.filter((item) => item.status === 'ready').length, 0));
const skippedCount = computed(() => props.packages.reduce((sum, plan) => sum + plan.items.filter((item) => item.status === 'skipped').length, 0));
</script>

<template>
    <AppLayout title="Paket Administrasi Pembelajaran">
        <Head title="Paket Administrasi Pembelajaran" />
        <main class="package-page">
            <header class="package-heading">
                <div><p class="eyebrow">Perangkat pembelajaran · {{ active_term.name }}</p><h1>Paket administrasi</h1><p>Pratinjau kelengkapan per mata pelajaran sebelum mengunduh dokumen. Bagian yang belum tersedia akan dicantumkan alasannya di manifest ZIP.</p></div>
                <a class="download-button" :href="download_url">Unduh paket ZIP</a>
            </header>
            <section class="summary-strip" aria-label="Ringkasan paket">
                <div><strong>{{ packages.length }}</strong><span>ATP / Silabus</span></div>
                <div><strong>{{ readyCount }}</strong><span>Dokumen disiapkan</span></div>
                <div><strong>{{ skippedCount }}</strong><span>Belum tersedia</span></div>
            </section>
            <section v-if="!packages.length" class="empty-package"><h2>Belum ada ATP / Silabus</h2><p>Buat ATP/Silabus terlebih dahulu agar paket administrasi bisa disusun.</p><a href="/akademik/perangkat/atp">Buka ATP / Silabus</a></section>
            <section v-for="plan in packages" :key="plan.id" class="plan-package">
                <header><div><span>{{ plan.curriculum }}</span><h2>{{ plan.title }}</h2></div><span class="completion-count">{{ plan.items.filter((item) => item.status === 'ready').length }} / {{ plan.items.length }} siap</span></header>
                <ul><li v-for="item in plan.items" :key="item.id" :class="item.status"><span class="status-mark" aria-hidden="true">{{ item.status === 'ready' ? '✓' : '–' }}</span><span><strong>{{ item.label }}</strong><small>{{ item.status === 'ready' ? 'Disiapkan dalam paket' : item.reason }}</small></span><span class="status-label">{{ item.status === 'ready' ? 'Siap' : 'Belum tersedia' }}</span></li></ul>
            </section>
            <footer class="package-note"><strong>Tentang paket</strong><p>Setiap dokumen siap diunduh sebagai berkas Word terpisah di dalam ZIP. Manifest menjelaskan dokumen yang dilewati; jurnal pertemuan belum diekspor karena integrasi kalender-jurnal belum tersedia.</p></footer>
        </main>
    </AppLayout>
</template>

<style scoped>
.package-page{--package-ink:var(--ink,#17263b);--package-muted:var(--ink-soft,#64748b);--package-line:var(--line,#dce3ed);--package-surface:var(--surface,#fff);--package-subtle:var(--surface-subtle,#f5f7fa);--package-primary:var(--primary,#315caa);max-width:1280px;margin:0 auto;padding:clamp(1rem,2vw,1.7rem);color:var(--package-ink)}
.package-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:1.2rem;padding:1.4rem;border:1px solid var(--line);border-radius:1rem;background:linear-gradient(125deg,var(--surface),var(--subtle));}.eyebrow{margin:0;color:var(--primary);font-size:.8rem;font-weight:760}.package-heading h1{margin:.35rem 0;font-size:clamp(1.65rem,3vw,2.4rem);letter-spacing:-.035em}.package-heading p:last-child{max-width:720px;margin:0;color:var(--muted);line-height:1.55}.download-button{flex:none;display:inline-flex;min-height:44px;align-items:center;padding:.7rem 1rem;border-radius:.65rem;background:var(--primary);color:#fff;text-decoration:none;font-weight:760}.download-button:focus-visible,.empty-package a:focus-visible{outline:3px solid color-mix(in srgb,var(--primary) 40%,transparent);outline-offset:2px}.summary-strip{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin:1rem 0}.summary-strip>div{display:flex;align-items:baseline;gap:.65rem;padding:.9rem 1rem;border:1px solid var(--line);border-radius:.75rem;background:var(--surface)}.summary-strip strong{font-size:1.35rem;color:var(--primary)}.summary-strip span{color:var(--muted);font-size:.83rem}.plan-package{margin:1rem 0;overflow:hidden;border:1px solid var(--line);border-radius:.9rem;background:var(--surface)}.plan-package>header{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.15rem;background:var(--subtle)}.plan-package>header>div>span{color:var(--muted);font-size:.73rem;font-weight:700}.plan-package h2{margin:.18rem 0 0;font-size:1.12rem}.completion-count{padding:.4rem .65rem;border:1px solid var(--line);border-radius:999px;color:var(--muted);font-size:.75rem;font-weight:760;white-space:nowrap}.plan-package ul{margin:0;padding:0;list-style:none}.plan-package li{display:grid;grid-template-columns:26px minmax(0,1fr) auto;align-items:center;gap:.7rem;padding:.75rem 1.1rem;border-top:1px solid var(--line)}.status-mark{display:grid;width:24px;height:24px;place-items:center;border-radius:50%;font-size:.8rem;font-weight:850}.ready .status-mark{background:#e7f6ee;color:#167447}.skipped .status-mark{background:#fff3df;color:#94620b}.plan-package li strong,.plan-package li small{display:block}.plan-package li strong{font-size:.84rem}.plan-package li small{margin-top:.18rem;color:var(--muted);font-size:.76rem;line-height:1.45}.status-label{font-size:.72rem;font-weight:780}.ready .status-label{color:#167447}.skipped .status-label{color:#94620b}.package-note,.empty-package{margin-top:1rem;padding:1rem 1.15rem;border:1px solid var(--line);border-radius:.8rem;background:var(--surface)}.package-note strong{font-size:.86rem}.package-note p,.empty-package p{margin:.25rem 0 0;color:var(--muted);font-size:.82rem;line-height:1.5}.empty-package h2{margin:0;font-size:1rem}.empty-package a{display:inline-flex;margin-top:.75rem;color:var(--primary);font-weight:740}@media(max-width:680px){.package-heading{align-items:stretch;flex-direction:column}.download-button{justify-content:center}.summary-strip{grid-template-columns:1fr}.summary-strip>div{justify-content:space-between}.plan-package>header{align-items:flex-start}.plan-package li{grid-template-columns:24px minmax(0,1fr)}.status-label{grid-column:2}}
</style>
