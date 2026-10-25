<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

type LooseData = Record<string, unknown>;
interface Chapter { id: string | number; title: string; semester?: string }
interface Unit { id: string | number; chapter_id: string | number; objective?: string; material?: string; jp: number }
interface Plan {
    id: number;
    subject_name: string;
    grade_level: number;
    curriculum: string;
    data: { chapters: Chapter[]; units: Unit[]; references?: { name: string; content: string }[]; acuan?: { name: string; content: string }[]; waktu?: { jpPerPertemuan?: number }; modul?: Record<string, ChapterModule> };
}
interface ChapterModule { bagian: Record<string, string>; langkah: LearningStep[] }
interface LearningStep { topik: string; pembahasan: string; pendahuluan: string; inti: string; penutup: string }

const props = defineProps<{
    active_term?: { id: number; name: string } | null;
    plans?: Plan[];
    selected_plan?: Plan | null;
    selected_module_id?: number | null;
    profile?: { school_name?: string; school_type?: string; teacher_name?: string };
}>();

const groups = [
    { id: 'kesiapan', title: 'Identifikasi kesiapan peserta didik', fields: [
        ['pengetahuanAwal', 'Pengetahuan awal'], ['minat', 'Minat'], ['latarBelakang', 'Latar belakang'], ['kebutuhanBelajar', 'Kebutuhan belajar'],
    ] },
    { id: 'tema', title: 'Tema Kurikulum Berbasis Cinta', only: 'kbc', fields: [
        ['topikPancaCinta', 'Topik Panca Cinta'], ['materiInsersi', 'Materi insersi'],
    ] },
    { id: 'karakteristik', title: 'Karakteristik materi pelajaran', fields: [
        ['pengetahuanKonseptual', 'Pengetahuan konseptual'], ['pengetahuanProsedural', 'Pengetahuan prosedural'],
        ['relevansiKehidupan', 'Relevansi dengan kehidupan nyata'], ['tingkatKesulitan', 'Tingkat kesulitan'],
        ['strukturMateri', 'Struktur materi'], ['integrasiNilai', 'Integrasi nilai dan karakter'],
    ] },
    { id: 'profil', title: 'Dimensi profil lulusan', fields: [
        ['profilKeimanan', 'Keimanan dan ketakwaan'], ['profilKewargaan', 'Kewargaan'], ['profilPenalaran', 'Penalaran kritis'],
        ['profilKreativitas', 'Kreativitas'], ['profilKolaborasi', 'Kolaborasi'], ['profilKemandirian', 'Kemandirian'],
        ['profilKesehatan', 'Kesehatan'], ['profilKomunikasi', 'Komunikasi'],
    ] },
    { id: 'lintas', title: 'Lintas disiplin ilmu', fields: [['lintasDisiplin', 'Keterkaitan lintas disiplin']] },
    { id: 'indikator', title: 'Indikator ketercapaian tujuan', fields: [['indikator', 'Indikator ketercapaian']] },
    { id: 'iklim', title: 'Iklim dan budaya sekolah', fields: [['iklimBudaya', 'Iklim/budaya sekolah']] },
    { id: 'kontekstual', title: 'Topik pembelajaran kontekstual', fields: [['topikKontekstual', 'Topik kontekstual']] },
    { id: 'kerangka', title: 'Kerangka pembelajaran', fields: [
        ['modelPembelajaran', 'Model pembelajaran'], ['pendekatan', 'Pendekatan'], ['metodePembelajaran', 'Metode pembelajaran'],
        ['diferensiasiKonten', 'Diferensiasi konten'], ['diferensiasiProses', 'Diferensiasi proses'], ['diferensiasiProduk', 'Diferensiasi produk'],
        ['kemitraanSekolah', 'Kemitraan lingkungan sekolah'], ['kemitraanLuar', 'Kemitraan luar sekolah/masyarakat'],
        ['kemitraanDigital', 'Mitra digital'], ['lingkunganFisik', 'Ruang fisik'], ['lingkunganVirtual', 'Ruang virtual'],
        ['budayaBelajar', 'Budaya belajar'], ['pemanfaatanDigital', 'Pemanfaatan teknologi digital'],
    ] },
    { id: 'asesmen', title: 'Asesmen pembelajaran', fields: [
        ['asesmenDiagnostik', 'Asesmen diagnostik (awal)'], ['asesmenFormatif', 'Asesmen formatif (proses)'], ['asesmenSumatif', 'Asesmen sumatif (akhir)'],
    ] },
] as const;

const plans = computed(() => props.plans ?? []);
const selectedPlanId = ref<number | null>(props.selected_plan?.id ?? plans.value[0]?.id ?? null);
const plan = computed(() => plans.value.find((item) => item.id === selectedPlanId.value) ?? props.selected_plan ?? null);
const supported = computed(() => ['merdeka', 'kbc'].includes(plan.value?.curriculum ?? ''));
const chapters = computed(() => plan.value?.data?.chapters ?? []);
const chapterId = ref<string | number | null>(chapters.value[0]?.id ?? null);
const chapter = computed(() => chapters.value.find((item) => String(item.id) === String(chapterId.value)) ?? null);
const units = computed(() => (plan.value?.data?.units ?? []).filter((item) => String(item.chapter_id) === String(chapterId.value)));
const totalJp = computed(() => units.value.reduce((sum, unit) => sum + Math.max(0, Number(unit.jp) || 0), 0));
const jpPerMeeting = computed(() => Math.max(1, Number(plan.value?.data?.waktu?.jpPerPertemuan) || 2));
const meetingCount = computed(() => Math.ceil(totalJp.value / jpPerMeeting.value));
const visibleGroups = computed(() => groups.filter((group) => !('only' in group) || group.only === plan.value?.curriculum));
const planKey = computed(() => `${selectedPlanId.value ?? 'none'}:${chapterId.value ?? 'none'}`);
const moduleForm = useForm<{ plan_id: number | null; chapter_id: string | number | null; content: ChapterModule }>({
    plan_id: selectedPlanId.value,
    chapter_id: chapterId.value,
    content: { bagian: {}, langkah: [] },
});
const activeSection = ref('kesiapan');
const notice = ref('');

const blankStep = (): LearningStep => ({ topik: '', pembahasan: '', pendahuluan: '', inti: '', penutup: '' });
function loadChapter() {
    const saved = plan.value?.data?.modul?.[String(chapterId.value)] ?? { bagian: {}, langkah: [] };
    moduleForm.plan_id = selectedPlanId.value;
    moduleForm.chapter_id = chapterId.value;
    moduleForm.content = {
        bagian: { ...(saved.bagian ?? {}) },
        langkah: Array.from({ length: meetingCount.value }, (_, index) => ({ ...blankStep(), ...(saved.langkah?.[index] ?? {}) })),
    };
    moduleForm.clearErrors();
    notice.value = '';
}
watch(planKey, loadChapter, { immediate: true });
watch(meetingCount, (count) => {
    moduleForm.content.langkah = Array.from({ length: count }, (_, index) => ({ ...blankStep(), ...(moduleForm.content.langkah[index] ?? {}) }));
});
watch(() => props.selected_plan, (item) => {
    if (item) {
        selectedPlanId.value = item.id;
        chapterId.value = item.data.chapters?.[0]?.id ?? null;
    }
});

const completion = computed(() => {
    const values = Object.values(moduleForm.content.bagian);
    const stepValues = moduleForm.content.langkah.flatMap((step) => [step.pendahuluan, step.inti, step.penutup]);
    const filled = [...values, ...stepValues].filter((value) => String(value ?? '').trim()).length;
    const total = visibleGroups.value.reduce((sum, group) => sum + group.fields.length, 0) + meetingCount.value * 3;
    return { filled, total, percent: total ? Math.round((filled / total) * 100) : 0 };
});
const curriculumLabel = (value: string) => value === 'kbc' ? 'Kurikulum Berbasis Cinta' : 'Kurikulum Merdeka';
const chapterLabel = (item: Chapter, index: number) => item.title?.trim() || `Bab ${index + 1}`;
const readOnlyCp = computed(() => (plan.value?.data?.acuan ?? plan.value?.data?.references ?? []).map((item) => item.content).filter(Boolean));
const objectives = computed(() => units.value.map((unit) => unit.objective).filter(Boolean));
function goToSection(id: string) {
    document.getElementById(`section-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function submit() {
    if (!plan.value || !chapter.value) return;
    const payload = {
        plan_id: plan.value.id,
        chapter_id: chapter.value.id,
        content: { bagian: { ...moduleForm.content.bagian }, langkah: moduleForm.content.langkah.map((step) => ({ ...step })) },
    };
    if (props.selected_module_id) {
        moduleForm.transform(() => payload).put(`/akademik/perangkat/modul-ajar/${props.selected_module_id}`, {
            preserveScroll: true,
            onSuccess: () => { notice.value = 'Modul Ajar berhasil disimpan.'; },
        });
    } else {
        moduleForm.transform(() => payload).post('/akademik/perangkat/modul-ajar', {
            preserveScroll: true,
            onSuccess: () => { notice.value = 'Modul Ajar berhasil disimpan.'; },
        });
    }
}
</script>

<template>
    <AppLayout title="Modul Ajar">
        <Head title="Modul Ajar" />
        <main class="module-page">
            <header class="module-heading">
                <div>
                    <div class="heading-meta"><span>Perangkat pembelajaran</span><span class="term-pill">{{ active_term?.name ?? 'Periode belum aktif' }}</span></div>
                    <h1>Modul Ajar</h1>
                    <p>Rancang pembelajaran per bab, lalu simpan semua bagian dan kegiatan pertemuannya dalam satu dokumen.</p>
                </div>
                <div class="completion-mark" :aria-label="`${completion.percent}% terisi`"><strong>{{ completion.percent }}%</strong><span>terisi</span></div>
            </header>

            <section v-if="!plans.length" class="empty-state">
                <span class="empty-symbol" aria-hidden="true">↗</span>
                <div><h2>Mulai dari ATP/Silabus</h2><p>Modul Ajar mengikuti mata pelajaran, bab, capaian, dan tujuan yang sudah disusun di ATP/Silabus. Buat dokumen tersebut terlebih dahulu.</p></div>
            </section>
            <section v-else-if="!supported" class="empty-state" role="status">
                <span class="empty-symbol" aria-hidden="true">i</span>
                <div><h2>Kerangka kurikulum belum didukung</h2><p>Modul Ajar tersedia untuk Kurikulum Merdeka dan Kurikulum Berbasis Cinta. Struktur RPP Kurikulum 2013 belum ditambahkan.</p></div>
            </section>

            <template v-else-if="plan">
                <section class="plan-bar" aria-label="Pilihan rencana">
                    <label class="control-field"><span>Rencana pembelajaran</span><select v-model.number="selectedPlanId" @change="chapterId = plan?.data.chapters?.[0]?.id ?? null"><option v-for="item in plans" :key="item.id" :value="item.id">{{ item.subject_name }} · Kelas {{ item.grade_level }} · {{ curriculumLabel(item.curriculum) }}</option></select></label>
                    <div class="plan-context"><span>{{ plan.subject_name }}</span><b>Kelas {{ plan.grade_level }}</b><span>{{ curriculumLabel(plan.curriculum) }}</span></div>
                </section>

                <div v-if="!chapters.length" class="empty-state compact"><div><h2>Belum ada bab di ATP/Silabus</h2><p>Tambahkan bab dan satuan pembelajaran terlebih dahulu agar Modul Ajar dapat disusun per bab.</p></div></div>
                <div v-else class="editor-grid">
                    <aside class="chapter-rail" aria-label="Daftar bab">
                        <div class="rail-title"><div><span class="rail-overline">Dokumen saat ini</span><h2>{{ plan.subject_name }}</h2></div><span class="chapter-count">{{ chapters.length }}</span></div>
                        <p class="rail-caption">Pilih bab untuk mengisi modulnya.</p>
                        <div class="chapter-tabs" role="tablist" aria-label="Pilih bab">
                            <button v-for="(item, index) in chapters" :key="item.id" type="button" role="tab" :aria-selected="String(chapterId) === String(item.id)" :class="{ selected: String(chapterId) === String(item.id) }" @click="chapterId = item.id">
                                <span class="chapter-number">{{ String(index + 1).padStart(2, '0') }}</span><span class="chapter-name">{{ chapterLabel(item, index) }}<small>{{ item.semester || 'Bab pembelajaran' }}</small></span>
                            </button>
                        </div>
                        <div class="rail-summary"><span class="summary-dot"/><span>{{ completion.filled }} dari {{ completion.total }} isian utama</span></div>
                    </aside>

                    <form v-if="chapter" class="module-editor" @submit.prevent="submit">
                        <div class="editor-header">
                            <div><span class="editor-kicker">{{ curriculumLabel(plan.curriculum) }} · {{ chapter.semester || 'Perencanaan bab' }}</span><h2>{{ chapterLabel(chapter, chapters.indexOf(chapter)) }}</h2><p>{{ meetingCount }} pertemuan · {{ totalJp }} JP · {{ totalJp * 40 }} menit</p></div>
                            <span class="save-indicator" :class="{ busy: moduleForm.processing }"><i/>{{ moduleForm.processing ? 'Menyimpan' : 'Siap diedit' }}</span>
                        </div>

                        <section class="derived-strip" aria-label="Informasi dari ATP/Silabus">
                            <div><span>Mata pelajaran</span><strong>{{ plan.subject_name }}</strong></div>
                            <div><span>Kelas / semester</span><strong>{{ plan.grade_level }} / {{ chapter.semester || '—' }}</strong></div>
                            <div><span>Alokasi waktu</span><strong>{{ totalJp }} JP</strong></div>
                        </section>

                        <section class="derived-section">
                            <div class="section-title"><div><span class="section-index">A</span><h3>Landasan pembelajaran</h3></div><span class="derived-tag">Dari ATP/Silabus</span></div>
                            <div class="derived-grid">
                                <div class="derived-card"><h4>Capaian pembelajaran</h4><p v-for="(item, index) in readOnlyCp" :key="index">{{ item }}</p><p v-if="!readOnlyCp.length" class="muted">Belum ada acuan CP pada dokumen ini.</p></div>
                                <div class="derived-card"><h4>Tujuan pembelajaran bab</h4><ul v-if="objectives.length"><li v-for="(item, index) in objectives" :key="index">{{ item }}</li></ul><p v-else class="muted">Belum ada tujuan untuk bab ini.</p></div>
                            </div>
                        </section>

                        <nav class="section-nav" aria-label="Bagian Modul Ajar"><button v-for="(group, index) in visibleGroups" :key="group.id" type="button" :class="{ active: activeSection === group.id }" @click="activeSection = group.id; goToSection(group.id)"><span>{{ String(index + 1).padStart(2, '0') }}</span>{{ group.title }}</button><button type="button" :class="{ active: activeSection === 'langkah' }" @click="activeSection = 'langkah'; goToSection('langkah')"><span>{{ String(visibleGroups.length + 1).padStart(2, '0') }}</span>Langkah pembelajaran</button></nav>

                        <section v-for="(group, index) in visibleGroups" :id="`section-${group.id}`" :key="group.id" class="form-section" @mouseenter="activeSection = group.id">
                            <div class="section-title"><div><span class="section-index">{{ String.fromCharCode(66 + index) }}</span><h3>{{ group.title }}</h3></div></div>
                            <div class="field-grid" :class="{ 'single-column': group.fields.length === 1 }">
                                <label v-for="[key, label] in group.fields" :key="key" class="module-field"><span>{{ label }}</span><textarea v-model="moduleForm.content.bagian[key]" rows="3" maxlength="4000" :aria-label="label" :placeholder="`Tuliskan ${label.toLowerCase()}…`"/><small>{{ (moduleForm.content.bagian[key] ?? '').length }}/4000</small></label>
                            </div>
                        </section>

                        <section id="section-langkah" class="form-section meeting-section" @mouseenter="activeSection = 'langkah'">
                            <div class="section-title"><div><span class="section-index">{{ String.fromCharCode(66 + visibleGroups.length) }}</span><h3>Langkah pembelajaran</h3></div><span class="derived-tag">{{ meetingCount }} pertemuan</span></div>
                            <p class="section-description">Pertemuan dihitung dari jumlah JP satuan pada bab ini. Setiap JP setara 40 menit.</p>
                            <div v-if="!meetingCount" class="inline-empty">Bab ini belum memiliki satuan ber-JP. Lengkapi satuan dan alokasi waktu di ATP/Silabus.</div>
                            <details v-for="(step, index) in moduleForm.content.langkah" :key="index" class="meeting-card" :open="index === 0">
                                <summary><span class="meeting-badge">{{ String(index + 1).padStart(2, '0') }}</span><span><strong>Pertemuan {{ index + 1 }}</strong><small>{{ Math.min(jpPerMeeting, totalJp - index * jpPerMeeting) }} JP · {{ Math.min(jpPerMeeting, totalJp - index * jpPerMeeting) * 40 }} menit</small></span><span class="summary-chevron">⌄</span></summary>
                                <div class="meeting-fields">
                                    <label v-if="plan.curriculum === 'kbc'" class="module-field"><span>Topik Panca Cinta</span><textarea v-model="step.topik" rows="2" maxlength="3000" placeholder="Topik atau nilai yang diintegrasikan"/></label>
                                    <label class="module-field"><span>Pembahasan</span><textarea v-model="step.pembahasan" rows="2" maxlength="3000" :placeholder="units.map((unit) => unit.material || unit.objective).filter(Boolean).join('; ') || 'Materi yang dibahas pada pertemuan ini'"/></label>
                                    <label class="module-field"><span>Kegiatan pendahuluan</span><textarea v-model="step.pendahuluan" rows="3" maxlength="3000" placeholder="Pembukaan, apersepsi, dan pengantar tujuan"/></label>
                                    <label class="module-field"><span>Kegiatan inti</span><textarea v-model="step.inti" rows="5" maxlength="3000" placeholder="Aktivitas belajar dan strategi pembelajaran"/></label>
                                    <label class="module-field"><span>Kegiatan penutup</span><textarea v-model="step.penutup" rows="3" maxlength="3000" placeholder="Refleksi, umpan balik, dan tindak lanjut"/></label>
                                </div>
                            </details>
                        </section>

                        <footer class="editor-footer">
                            <p v-if="notice" class="success-message" role="status">{{ notice }}</p>
                            <p v-if="moduleForm.hasErrors" class="error-message" role="alert">Periksa kembali isian. Ada data yang belum valid.</p>
                            <div class="footer-progress"><span><b>{{ completion.percent }}%</b> lengkap</span><div><i :style="{ width: `${completion.percent}%` }"/></div></div>
                            <button class="save-button" type="submit" :disabled="moduleForm.processing"><span>{{ moduleForm.processing ? 'Menyimpan…' : 'Simpan Modul Ajar' }}</span><span aria-hidden="true">↗</span></button>
                        </footer>
                    </form>
                </div>
            </template>
        </main>
    </AppLayout>
</template>

<style scoped>
.module-page{--module-space:clamp(1rem,2vw,1.6rem);--module-ink:var(--ink,#17263b);--module-muted:var(--ink-soft,#65748b);--module-faint:var(--ink-faint,#8794a8);--module-line:var(--line,#dce3ed);--module-surface:var(--surface,#fff);--module-subtle:var(--surface-subtle,#f6f8fb);--module-primary:var(--primary,#345fae);--module-primary-soft:var(--primary-soft,#edf3ff);max-width:1500px;margin:0 auto;padding:var(--module-space);color:var(--module-ink)}
.module-heading{display:flex;align-items:center;justify-content:space-between;gap:1.25rem;padding:.2rem 0 1.2rem;border-bottom:1px solid var(--module-line)}.heading-meta{display:flex;flex-wrap:wrap;align-items:center;gap:.65rem;color:var(--module-muted);font-size:.78rem;font-weight:650}.term-pill{padding:.3rem .55rem;border:1px solid var(--module-line);border-radius:999px;color:var(--module-ink);background:var(--module-surface)}.module-heading h1{margin:.65rem 0 .3rem;font-size:clamp(1.65rem,3vw,2.25rem);font-weight:820;letter-spacing:-.045em;line-height:1.12}.module-heading p{max-width:680px;margin:0;color:var(--module-muted);font-size:.91rem;line-height:1.55}.completion-mark{display:grid;flex:0 0 76px;height:76px;align-content:center;justify-items:center;border:1px solid var(--module-line);border-radius:50%;background:var(--module-surface)}.completion-mark strong{font-size:1.25rem;line-height:1.1}.completion-mark span{color:var(--module-muted);font-size:.68rem}
.plan-bar{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin:1rem 0;padding:.8rem 1rem;border:1px solid var(--module-line);border-radius:.85rem;background:var(--module-surface)}.control-field{display:grid;min-width:min(390px,100%);gap:.35rem}.control-field>span,.module-field>span{color:var(--module-muted);font-size:.76rem;font-weight:720}.control-field select{min-height:42px;padding:.55rem .7rem;border:1px solid var(--module-line);border-radius:.55rem;background:var(--module-surface);color:var(--module-ink);font:inherit}.plan-context{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:.45rem;color:var(--module-muted);font-size:.77rem}.plan-context b{color:var(--module-ink)}
.editor-grid{display:grid;grid-template-columns:minmax(220px,270px) minmax(0,1fr);align-items:start;gap:1rem}.chapter-rail{position:sticky;top:1rem;overflow:hidden;border:1px solid var(--module-line);border-radius:.9rem;background:var(--module-surface)}.rail-title{display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:1rem 1rem .55rem}.rail-overline,.editor-kicker{color:var(--module-muted);font-size:.7rem;font-weight:680}.rail-title h2{margin:.25rem 0 0;font-size:1rem;font-weight:790}.chapter-count{display:grid;width:30px;height:30px;place-items:center;border-radius:.55rem;background:var(--module-primary-soft);color:var(--module-primary);font-size:.77rem;font-weight:800}.rail-caption{margin:0;padding:0 1rem .75rem;color:var(--module-muted);font-size:.76rem}.chapter-tabs{display:grid;gap:.25rem;padding:.25rem .55rem .75rem}.chapter-tabs button{display:flex;align-items:center;gap:.65rem;width:100%;min-height:56px;padding:.55rem;border:1px solid transparent;border-radius:.6rem;background:transparent;color:var(--module-ink);text-align:left;cursor:pointer}.chapter-tabs button:hover{background:var(--module-subtle)}.chapter-tabs button.selected{border-color:color-mix(in srgb,var(--module-primary) 22%,var(--module-line));background:var(--module-primary-soft)}.chapter-number{color:var(--module-primary);font-size:.72rem;font-weight:830}.chapter-name{min-width:0;font-size:.8rem;font-weight:740;line-height:1.35}.chapter-name small{display:block;margin-top:.18rem;color:var(--module-muted);font-size:.68rem;font-weight:500}.rail-summary{display:flex;align-items:center;gap:.45rem;padding:.8rem 1rem;border-top:1px solid var(--module-line);color:var(--module-muted);font-size:.72rem}.summary-dot,.save-indicator i{width:8px;height:8px;border-radius:50%;background:#419879}.module-editor{min-width:0;overflow:hidden;border:1px solid var(--module-line);border-radius:.9rem;background:var(--module-surface)}.editor-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding:1.25rem 1.35rem 1rem}.editor-header h2{margin:.3rem 0;font-size:clamp(1.25rem,2vw,1.65rem);font-weight:800;letter-spacing:-.03em}.editor-header p{margin:0;color:var(--module-muted);font-size:.8rem}.save-indicator{display:flex;align-items:center;gap:.4rem;padding:.38rem .55rem;border-radius:999px;background:var(--module-subtle);color:var(--module-muted);font-size:.69rem;white-space:nowrap}.save-indicator.busy i{background:#c98725}
.derived-strip{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));margin:0 1.35rem;padding:.8rem 0;border-top:1px solid var(--module-line);border-bottom:1px solid var(--module-line)}.derived-strip div{display:grid;gap:.22rem;padding:0 .8rem;border-right:1px solid var(--module-line)}.derived-strip div:first-child{padding-left:0}.derived-strip div:last-child{border:0}.derived-strip span{color:var(--module-muted);font-size:.68rem}.derived-strip strong{font-size:.8rem;font-weight:760}.derived-section,.form-section{padding:1.1rem 1.35rem;border-bottom:1px solid var(--module-line);scroll-margin-top:1rem}.section-title{display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:.75rem}.section-title>div{display:flex;align-items:center;gap:.6rem}.section-index{display:grid;flex:0 0 27px;height:27px;place-items:center;border:1px solid var(--module-line);border-radius:.45rem;color:var(--module-primary);font-size:.69rem;font-weight:820}.section-title h3{margin:0;font-size:.96rem;font-weight:790;letter-spacing:-.015em}.derived-tag{padding:.28rem .48rem;border:1px solid var(--module-line);border-radius:999px;color:var(--module-muted);font-size:.65rem;font-weight:690}.derived-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.65rem}.derived-card{min-width:0;padding:.8rem;border-left:2px solid var(--module-primary);background:var(--module-subtle)}.derived-card h4{margin:0 0 .4rem;font-size:.78rem;font-weight:760}.derived-card p,.derived-card li{margin:.25rem 0;color:var(--module-muted);font-size:.76rem;line-height:1.5}.derived-card ul{margin:0;padding-left:1rem}.derived-card .muted,.muted{color:var(--module-faint)}
.section-nav{display:flex;gap:.35rem;overflow-x:auto;padding:.65rem 1.35rem;border-bottom:1px solid var(--module-line);scrollbar-width:thin}.section-nav button{display:flex;flex:0 0 auto;align-items:center;gap:.4rem;min-height:34px;padding:.4rem .55rem;border:1px solid transparent;border-radius:.45rem;background:transparent;color:var(--module-muted);font-size:.69rem;font-weight:690;cursor:pointer}.section-nav button span{color:var(--module-faint);font-size:.62rem}.section-nav button.active{border-color:var(--module-line);background:var(--module-subtle);color:var(--module-primary)}.field-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem}.field-grid.single-column{grid-template-columns:1fr}.module-field{position:relative;display:grid;min-width:0;gap:.35rem}.module-field textarea{width:100%;min-height:88px;padding:.65rem .7rem;border:1px solid var(--module-line);border-radius:.55rem;outline:none;background:var(--module-surface);color:var(--module-ink);font:inherit;font-size:.81rem;line-height:1.5;resize:vertical}.module-field textarea::placeholder{color:var(--module-faint)}.module-field textarea:focus,.control-field select:focus{border-color:var(--module-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--module-primary) 18%,transparent)}.module-field small{justify-self:end;margin-top:-.2rem;color:var(--module-faint);font-size:.64rem}.meeting-section{padding-bottom:1.3rem}.section-description{margin:-.3rem 0 .8rem;color:var(--module-muted);font-size:.77rem;line-height:1.5}.meeting-card{overflow:hidden;margin:.55rem 0;border:1px solid var(--module-line);border-radius:.65rem}.meeting-card summary{display:flex;align-items:center;gap:.65rem;min-height:62px;padding:.65rem .8rem;cursor:pointer;list-style:none}.meeting-card summary::-webkit-details-marker{display:none}.meeting-badge{display:grid;flex:0 0 32px;height:32px;place-items:center;border-radius:.5rem;background:var(--module-primary-soft);color:var(--module-primary);font-size:.73rem;font-weight:820}.meeting-card summary strong,.meeting-card summary small{display:block}.meeting-card summary strong{font-size:.79rem}.meeting-card summary small{margin-top:.15rem;color:var(--module-muted);font-size:.69rem}.summary-chevron{margin-left:auto;color:var(--module-muted);transition:transform .15s}.meeting-card[open] .summary-chevron{transform:rotate(180deg)}.meeting-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem;padding:0 .8rem .85rem}.meeting-fields .module-field:nth-child(2),.meeting-fields .module-field:nth-child(4){grid-column:1/-1}.inline-empty{padding:.8rem;border:1px dashed var(--module-line);border-radius:.55rem;color:var(--module-muted);font-size:.78rem}
.editor-footer{position:sticky;bottom:0;z-index:2;display:grid;grid-template-columns:minmax(170px,1fr) auto;align-items:center;gap:.6rem 1rem;padding:.85rem 1.35rem;border-top:1px solid var(--module-line);background:var(--module-surface)}.footer-progress{display:flex;align-items:center;gap:.65rem;color:var(--module-muted);font-size:.73rem}.footer-progress b{color:var(--module-ink)}.footer-progress>div{overflow:hidden;flex:1;height:6px;border-radius:999px;background:var(--module-subtle)}.footer-progress i{display:block;height:100%;border-radius:inherit;background:var(--module-primary);transition:width .18s}.save-button{display:flex;align-items:center;justify-content:space-between;gap:1rem;min-height:42px;padding:.65rem .8rem;border:0;border-radius:.55rem;background:var(--module-primary);color:#fff;font:inherit;font-size:.78rem;font-weight:760;cursor:pointer}.save-button:hover{filter:brightness(1.08)}.save-button:disabled{opacity:.65;cursor:wait}.success-message,.error-message{grid-column:1/-1;margin:0;font-size:.76rem}.success-message{color:#1d7b54}.error-message{color:#b4323b}.empty-state{display:flex;align-items:flex-start;gap:.85rem;max-width:820px;margin:2rem auto;padding:1.2rem;border:1px solid var(--module-line);border-radius:.8rem;background:var(--module-surface)}.empty-state.compact{margin:1rem 0}.empty-symbol{display:grid;flex:0 0 34px;height:34px;place-items:center;border-radius:.55rem;background:var(--module-primary-soft);color:var(--module-primary);font-weight:850}.empty-state h2{margin:0 0 .3rem;font-size:1rem;font-weight:790}.empty-state p{margin:0;color:var(--module-muted);font-size:.82rem;line-height:1.55}
:global(html[data-theme='dark']) .module-page{--module-ink:var(--ink,#e8edf6);--module-muted:var(--ink-soft,#b1bed0);--module-faint:var(--ink-faint,#8d9bb0);--module-line:var(--line,#354154);--module-surface:var(--surface,#202a39);--module-subtle:var(--surface-subtle,#1b2432);--module-primary:var(--primary,#89adff);--module-primary-soft:var(--primary-soft,#273b5d)}:global(html[data-theme='dark']) .success-message{color:#75d5a8}:global(html[data-theme='dark']) .error-message{color:#ff9a9f}
.module-page button:focus-visible,.module-page summary:focus-visible{outline:3px solid color-mix(in srgb,var(--module-primary) 65%,transparent);outline-offset:2px}
@media(max-width:900px){.editor-grid{grid-template-columns:minmax(190px,225px) minmax(0,1fr)}.chapter-rail{top:.5rem}.editor-header,.derived-section,.form-section{padding-right:1rem;padding-left:1rem}.section-nav{padding-right:1rem;padding-left:1rem}.derived-strip{margin-right:1rem;margin-left:1rem}}
@media(max-width:700px){.module-heading{align-items:flex-start}.completion-mark{flex-basis:62px;height:62px}.plan-bar{align-items:stretch;flex-direction:column}.control-field{min-width:0}.plan-context{justify-content:flex-start}.editor-grid{grid-template-columns:1fr}.chapter-rail{position:static}.chapter-tabs{display:flex;overflow-x:auto}.chapter-tabs button{flex:0 0 min(230px,76vw)}.rail-summary{padding:.65rem .85rem}.derived-grid{grid-template-columns:1fr}.editor-footer{position:static;grid-template-columns:1fr}.save-button{width:100%}}
@media(max-width:460px){.module-page{padding:.85rem}.heading-meta{gap:.35rem}.module-heading h1{font-size:1.55rem}.completion-mark{display:none}.editor-header{flex-direction:column}.derived-strip{grid-template-columns:1fr;gap:.55rem}.derived-strip div,.derived-strip div:first-child{padding:.1rem 0;border:0}.field-grid,.meeting-fields{grid-template-columns:1fr}.meeting-fields .module-field{grid-column:auto}.section-title{align-items:flex-start}.footer-progress{align-items:flex-start;flex-direction:column}.footer-progress>div{width:100%}}
@media(prefers-reduced-motion:reduce){.module-page *{scroll-behavior:auto!important;transition-duration:.01ms!important}}
</style>
