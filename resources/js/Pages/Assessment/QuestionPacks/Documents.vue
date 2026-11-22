<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { saveBlob } from '@/lib/assessment-http';
// @ts-expect-error Reference JavaScript module.
import { normalizeExamPackage, buildExamHtml, buildExamDocx, exportFilename } from '@/lib/reference/examPackage.js';
const props = defineProps<{ pack: { id: number; name: string; duration_minutes: number | null; settings: any }; questions: any[]; profile?: any }>();
const mode = ref('paper');
const busy = ref(false);
const error = ref('');
const frame = ref<HTMLIFrameElement>();
const labels: Record<string, string> = { paper: 'Naskah soal', key: 'Kunci & penskoran', blueprint: 'Kisi-kisi' };
const exam = computed(() => normalizeExamPackage({ kop: props.profile?.kop, ...props.pack.settings, nama: props.pack.name, waktu: props.pack.settings.waktu || (props.pack.duration_minutes ? `${props.pack.duration_minutes} menit` : ''), items: props.questions.map((snapshot, i) => ({ sourceId: String(i), snapshot })) }, props.profile));
const html = computed(() => {
    const nonce = document.querySelector<HTMLScriptElement>('script[nonce]')?.nonce ?? '';
    return buildExamHtml(exam.value, [], props.profile ?? {}, mode.value).replace('<style>', `<style nonce="${nonce}">`);
});
async function word() {
    busy.value = true; error.value = '';
    try { saveBlob(await buildExamDocx(exam.value, [], props.profile ?? {}, mode.value), exportFilename(exam.value, labels[mode.value], 'docx')); }
    catch { error.value = 'Dokumen Word gagal dibuat. Silakan coba lagi.'; }
    finally { busy.value = false; }
}
function print() { frame.value?.contentWindow?.focus(); frame.value?.contentWindow?.print(); }
</script>
<template>
    <Head :title="`Dokumen · ${pack.name}`" />
    <AppLayout title="Dokumen Paket Soal">
        <div class="documents-page">
            <header><div><Link href="/penilaian/paket-soal">← Paket soal</Link><h1>{{ pack.name }}</h1><p>{{ exam.paper.ukuran }} · {{ exam.paper.orientasi }} · {{ exam.paper.columns }} kolom</p></div><div class="actions"><AppButton icon="word" :loading="busy" @click="word">Unduh Word</AppButton><AppButton variant="secondary" icon="pdf" @click="print">Cetak / Simpan PDF</AppButton></div></header>
            <nav aria-label="Jenis dokumen"><button v-for="(label, key) in labels" :key="key" :aria-pressed="mode === key" @click="mode = key">{{ label }}</button></nav>
            <p>Untuk PDF, pilih “Simpan sebagai PDF” pada dialog cetak, ukuran {{ exam.paper.ukuran }}, orientasi {{ exam.paper.orientasi }}. Gunakan skala 100% dan nonaktifkan header/footer browser.</p>
            <p v-if="error" role="alert">{{ error }}</p>
            <iframe ref="frame" :srcdoc="html" title="Pratinjau dokumen siap cetak" sandbox="allow-same-origin allow-modals" />
        </div>
    </AppLayout>
</template>
<style scoped>
.documents-page{display:grid;gap:1rem;padding:1.5rem;color:var(--ink)}header{display:flex;justify-content:space-between;gap:1rem;align-items:center}h1{font-size:var(--type-3xl);font-weight:var(--weight-bold)}p{font-size:var(--type-sm);color:var(--muted)}nav,.actions{display:flex;gap:.5rem;flex-wrap:wrap}button{background:var(--surface);color:var(--ink);border:1px solid var(--line);border-radius:.5rem;padding:.6rem 1rem}button[aria-pressed=true]{background:var(--primary-action);color:white}iframe{width:100%;height:75vh;border:1px solid var(--line);border-radius:.5rem;background:#e8ebf0}@media(max-width:700px){header{align-items:start;flex-direction:column}.documents-page{padding:1rem}}
</style>
