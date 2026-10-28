<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import RowActionButton from '@/components/RowActionButton.vue';

type DocumentType = 'prota' | 'promes' | 'alokasi_waktu' | 'kktp';
type Semester = 'ganjil' | 'genap' | null;
type AnyRecord = Record<string, any>;
interface Plan { id: number; subject_name: string; grade_level: number; curriculum: string; data: { chapters?: AnyRecord[]; units?: AnyRecord[]; waktu?: AnyRecord; references?: AnyRecord[] } }
interface PlanningDocument { id: number; type: DocumentType; title: string; semester: Semester; plan_id?: number | null; content: AnyRecord; updated_at?: string | null }
interface ActiveTerm { id: number; name: string }

const props = withDefaults(defineProps<{ documents?: PlanningDocument[]; active_term?: ActiveTerm | null; plans?: Plan[] }>(), {
    documents: () => [], active_term: null, plans: () => [],
});
const documentTypes: { value: DocumentType; label: string; description: string; annual: boolean }[] = [
    { value: 'prota', label: 'Program Tahunan (Prota)', description: 'Susun bab dan unit pembelajaran sepanjang tahun.', annual: true },
    { value: 'promes', label: 'Program Semester (Promes)', description: 'Pilih materi dan alokasi JP untuk semester.', annual: false },
    { value: 'alokasi_waktu', label: 'Analisis Alokasi Waktu', description: 'Atur JP mingguan, minggu efektif, dan bulan mulai.', annual: true },
    { value: 'kktp', label: 'KKTP / KKM', description: 'Atur interval capaian dan KKM untuk setiap unit.', annual: false },
];
const intervalDefaults = [
    { label: 'Belum mencapai, remedial di seluruh bagian', range: '0–40%' },
    { label: 'Belum mencapai ketuntasan, remedial di bagian yang diperlukan', range: '41–65%' },
    { label: 'Sudah mencapai ketuntasan, tidak perlu remedial', range: '66–85%' },
    { label: 'Sudah mencapai ketuntasan, perlu pengayaan', range: '86–100%' },
];
const semesters = ['ganjil', 'genap'] as const;
const months = Array.from({ length: 12 }, (_, index) => index + 1);
const endpoint = '/akademik/perangkat/dokumen';
const activeId = ref<number | null>(null);
const notice = ref('');
const selectedPlan = computed(() => props.plans.find((plan) => plan.id === Number(form.plan_id)) ?? null);
const currentType = computed(() => documentTypes.find((item) => item.value === form.type) ?? documentTypes[0]);
const isEditing = computed(() => activeId.value !== null);
const chapters = computed<AnyRecord[]>(() => Array.isArray(form.content.chapters) ? form.content.chapters : []);
const units = computed<AnyRecord[]>(() => Array.isArray(form.content.units) ? form.content.units : []);
const promesWeeks = computed(() => {
    if (form.type !== 'promes' || !selectedPlan.value) return [];
    const semester = normalizeSemester(form.semester);
    const key = semester === 'genap' ? '2' : '1';
    const allocationDoc = props.documents.find((item) => item.plan_id === selectedPlan.value?.id && item.type === 'alokasi_waktu');
    const allocation = allocationDoc?.content ?? {};
    const effectiveWeeks = Number(allocation.effective_weeks?.[semester] ?? selectedPlan.value.data.waktu?.minggu_efektif?.[key] ?? 18);
    const jpPerWeek = Number(allocation.jp_per_week ?? selectedPlan.value.data.waktu?.jp_per_minggu ?? 2);
    const monthStart = Number(allocation.monthly_period?.[semester] ?? (semester === 'ganjil' ? 7 : 1));
    const weeks = Array.from({ length: 30 }, (_, index) => ({
        month: ((monthStart - 1 + Math.floor(index / 5)) % 12) + 1,
        week: (index % 5) + 1,
        capacity: index < effectiveWeeks ? jpPerWeek : 0,
        units: [] as Array<{ id: string | number; label: string; jp: number }>,
    }));
    const chapterIds = new Set(chapters.value.filter((chapter) => normalizeSemester(chapter.semester) === semester).map((chapter) => String(chapter.id)));
    const semesterUnits = units.value.filter((unit) => chapterIds.has(String(unit.chapter_id)));
    let cursor = 0;
    for (const unit of semesterUnits) {
        let remaining = Math.max(0, Number(unit.jp) || 0);
        while (remaining > 0 && cursor < weeks.length) {
            if (weeks[cursor].capacity <= 0) { cursor++; continue; }
            const allocated = Math.min(remaining, weeks[cursor].capacity);
            weeks[cursor].units.push({ id: unit.id, label: unitLabel(unit), jp: allocated });
            weeks[cursor].capacity -= allocated;
            remaining -= allocated;
            if (weeks[cursor].capacity === 0) cursor++;
        }
    }
    return weeks;
});
const promesOverage = computed(() => {
    if (form.type !== 'promes' || !selectedPlan.value) return 0;
    const semester = normalizeSemester(form.semester);
    const key = semester === 'genap' ? '2' : '1';
    const allocationDoc = props.documents.find((item) => item.plan_id === selectedPlan.value?.id && item.type === 'alokasi_waktu');
    const allocation = allocationDoc?.content ?? {};
    const effectiveWeeks = Number(allocation.effective_weeks?.[semester] ?? selectedPlan.value.data.waktu?.minggu_efektif?.[key] ?? 18);
    const jpPerWeek = Number(allocation.jp_per_week ?? selectedPlan.value.data.waktu?.jp_per_minggu ?? 2);
    const chapterIds = new Set(chapters.value.filter((chapter) => normalizeSemester(chapter.semester) === semester).map((chapter) => String(chapter.id)));
    const planned = units.value.filter((unit) => chapterIds.has(String(unit.chapter_id))).reduce((sum, unit) => sum + Math.max(0, Number(unit.jp) || 0), 0);
    return Math.max(0, planned - effectiveWeeks * jpPerWeek);
});

function starterContent(type: DocumentType): AnyRecord {
    switch (type) {
        case 'prota': return { chapters: [], units: [] };
        case 'promes': return { semester: 'ganjil', months: [], chapters: [], units: [], distribution: [] };
        case 'alokasi_waktu': return { effective_weeks: { ganjil: 18, genap: 18 }, jp_per_week: 2, monthly_period: { ganjil: 7, genap: 1 }, planned_units: [] };
        case 'kktp': return { scale: 'interval', intervals: intervalDefaults.map((item) => ({ ...item })), units: [] };
    }
}
function mergeContent(type: DocumentType, saved: AnyRecord = {}): AnyRecord {
    const base = starterContent(type);
    const merged = { ...base, ...(saved && typeof saved === 'object' ? saved : {}) };
    if (type === 'prota' || type === 'promes') {
        merged.chapters = Array.isArray(saved.chapters) ? saved.chapters.map((row: AnyRecord, i: number) => ({ id: row.id ?? `chapter-${i}`, title: row.title ?? row.name ?? '', semester: normalizeSemester(row.semester) })) : [];
        merged.units = Array.isArray(saved.units) ? saved.units.map((row: AnyRecord, i: number) => ({ id: row.id ?? `unit-${i}`, chapter_id: row.chapter_id ?? '', code: row.code ?? '', objective: row.objective ?? row.title ?? '', material: row.material ?? row.name ?? '', jp: Number(row.jp ?? row.jam_pelajaran ?? 0), ...(row.kkm !== undefined ? { kkm: row.kkm } : {}) })) : [];
    }
    if (type === 'alokasi_waktu') {
        const weeks = saved.effective_weeks ?? saved.minggu_efektif ?? {};
        const months = saved.monthly_period ?? saved.bulan_mulai ?? {};
        merged.jp_per_week = Number(saved.jp_per_week ?? saved.jp_per_minggu ?? 2);
        merged.effective_weeks = { ganjil: Number(weeks.ganjil ?? weeks['1'] ?? 18), genap: Number(weeks.genap ?? weeks['2'] ?? 18) };
        merged.monthly_period = { ganjil: Number(months.ganjil ?? 7), genap: Number(months.genap ?? 1) };
        merged.planned_units = Array.isArray(saved.planned_units) ? saved.planned_units : [];
    }
    if (type === 'kktp') {
        const source = Array.isArray(saved.intervals) ? saved.intervals : Array.isArray(saved.interval) ? saved.interval : [];
        merged.intervals = intervalDefaults.map((fallback, index) => ({ ...fallback, ...(source[index] ?? {}) }));
        merged.units = Array.isArray(saved.units) ? saved.units.map((row: AnyRecord, i: number) => ({ ...row, id: row.id ?? `unit-${i}`, kkm: row.kkm ?? row.kkm_value ?? null })) : [];
    }
    return merged;
}
function normalizeSemester(value: unknown): 'ganjil' | 'genap' {
    return ['genap', '2', 'even'].includes(String(value).toLowerCase()) ? 'genap' : 'ganjil';
}
const form = useForm<{ type: DocumentType; title: string; academic_term_id: number | null; semester: Semester; plan_id: number | null; content: AnyRecord }>({
    type: 'prota', title: documentTypes[0].label, academic_term_id: props.active_term?.id ?? null, semester: null, plan_id: null, content: starterContent('prota'),
});

function newKey(prefix: string) { return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`; }
function syncUnitsFromPlan() {
    const data = selectedPlan.value?.data;
    if (!data) return;
    if (form.type === 'alokasi_waktu') {
        form.content.jp_per_week = Number(data.waktu?.jp_per_minggu ?? 2);
        form.content.effective_weeks = { ganjil: Number(data.waktu?.minggu_efektif?.['1'] ?? 18), genap: Number(data.waktu?.minggu_efektif?.['2'] ?? 18) };
        return;
    }
    const savedChapters = Array.isArray(form.content.chapters) ? form.content.chapters : [];
    const savedUnits = Array.isArray(form.content.units) ? form.content.units : [];
    form.content.chapters = (data.chapters ?? []).map((chapter, index) => {
        const id = chapter.id ?? `chapter-${index}`;
        const saved = savedChapters.find((row: AnyRecord) => String(row.id) === String(id));
        return { ...chapter, ...saved, id, title: saved?.title ?? chapter.title ?? '', semester: normalizeSemester(saved?.semester ?? chapter.semester) };
    });
    form.content.units = (data.units ?? []).map((unit, index) => {
        const id = unit.id ?? `unit-${index}`;
        const saved = savedUnits.find((row: AnyRecord) => String(row.id) === String(id) || (unit.code && row.code === unit.code));
        return { ...unit, ...saved, id, chapter_id: saved?.chapter_id ?? unit.chapter_id ?? '', code: unit.code ?? '', objective: unit.objective ?? '', material: unit.material ?? '', jp: Number(saved?.jp ?? unit.jp ?? 0), ...(form.type === 'kktp' ? { kkm: saved?.kkm ?? unit.kkm ?? null } : {}) };
    });
    if (form.type === 'kktp') form.content.units = form.content.units.map((unit: AnyRecord) => ({ ...unit, kkm: unit.kkm ?? null }));
}
watch(() => form.plan_id, (next, previous) => { if (next && next !== previous) syncUnitsFromPlan(); });
watch(() => form.type, (type) => {
    if (!isEditing.value) { form.content = starterContent(type); form.title = documentTypes.find((item) => item.value === type)?.label ?? ''; if (selectedPlan.value && ['prota', 'promes', 'alokasi_waktu', 'kktp'].includes(type)) syncUnitsFromPlan(); }
    if (currentType.value.annual) form.semester = null;
    else if (!form.semester) form.semester = 'ganjil';
});
watch(() => props.active_term, (term) => { if (!isEditing.value) form.academic_term_id = term?.id ?? null; }, { immediate: true });

function resetDocument() {
    activeId.value = null; form.clearErrors(); form.reset(); form.type = 'prota'; form.title = documentTypes[0].label;
    form.academic_term_id = props.active_term?.id ?? null; form.semester = null; form.plan_id = null; form.content = starterContent('prota'); form.defaults(); notice.value = '';
}
function confirmDiscardChanges(message = 'Ada perubahan dokumen yang belum disimpan. Buang perubahan?') {
    return !form.isDirty || window.confirm(message);
}
function createDocument() {
    if (!confirmDiscardChanges()) return;
    resetDocument();
}
function editDocument(document: PlanningDocument) {
    if (!confirmDiscardChanges()) return;
    activeId.value = document.id; form.clearErrors(); form.type = document.type; form.title = document.title;
    form.academic_term_id = props.active_term?.id ?? null; form.semester = document.semester; form.plan_id = document.plan_id ?? null;
    form.content = mergeContent(document.type, document.content ?? {}); notice.value = '';
    if (selectedPlan.value && ['prota', 'promes', 'kktp'].includes(form.type)) syncUnitsFromPlan();
    form.defaults();
}
function changeType(type: DocumentType) {
    if (isEditing.value || type === form.type || !confirmDiscardChanges('Mengganti jenis dokumen akan membuang isian dokumen baru saat ini. Lanjutkan?')) return;
    form.type = type;
}
function addChapter() { form.content.chapters.push({ id: newKey('chapter'), title: '', semester: form.semester ?? 'ganjil' }); }
function removeChapter(id: string | number) {
    const chapter = chapters.value.find((row) => row.id === id);
    const removedUnits = units.value.filter((row) => row.chapter_id === id).length;
    if (!window.confirm(`Hapus bab “${chapter?.title || 'tanpa judul'}”${removedUnits ? ` beserta ${removedUnits} unit pembelajaran` : ''} dari dokumen?`)) return;
    form.content.chapters = chapters.value.filter((row) => row.id !== id); form.content.units = units.value.filter((row) => row.chapter_id !== id);
}
function addUnit(chapterId?: string | number) { form.content.units.push({ id: newKey('unit'), chapter_id: chapterId ?? chapters.value[0]?.id ?? '', code: '', objective: '', material: '', jp: 2, ...(form.type === 'kktp' ? { kkm: null } : {}) }); }
function removeUnit(id: string | number) {
    const unit = units.value.find((row) => row.id === id);
    if (!window.confirm(`Keluarkan unit “${unitLabel(unit ?? {})}” dari dokumen?`)) return;
    form.content.units = units.value.filter((row) => row.id !== id);
}
function chapterName(id: string | number) { return chapters.value.find((chapter) => String(chapter.id) === String(id))?.title || 'Tanpa bab'; }
function saveDocument() {
    notice.value = '';
    if (!form.academic_term_id) { form.setError('academic_term_id', 'Periode akademik aktif belum tersedia.'); return; }
    if (!form.plan_id) { form.setError('plan_id', 'Pilih ATP/Silabus sebelum menyimpan dokumen.'); return; }
    const options = { preserveScroll: true, onSuccess: () => { form.defaults(); notice.value = 'Dokumen berhasil disimpan.'; } };
    if (activeId.value !== null) form.put(`${endpoint}/${activeId.value}`, options); else form.post(endpoint, options);
}
function deleteDocument(document: PlanningDocument) {
    if (!window.confirm(`Hapus dokumen “${document.title}”? Tindakan ini tidak dapat dibatalkan.`)) return;
    router.delete(`${endpoint}/${document.id}`, { preserveScroll: true, onSuccess: () => { if (activeId.value === document.id) resetDocument(); } });
}
function formatUpdatedAt(value?: string | null) { if (!value) return 'Belum pernah diperbarui'; const date = new Date(value); return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(date); }
function unitLabel(unit: AnyRecord) { return [unit.code, unit.objective || unit.material].filter(Boolean).join(' — ') || 'Unit tanpa judul'; }
function monthName(month: number) { return new Intl.DateTimeFormat('id-ID', { month: 'long' }).format(new Date(2026, month - 1, 1)); }
</script>

<template>
    <AppLayout title="Dokumen Perangkat Pembelajaran">
        <Head title="Dokumen Perangkat Pembelajaran" />
        <main class="documents-page">
            <header class="page-heading"><div><p class="page-context">Perangkat pembelajaran <span class="term-label">{{ active_term?.name ?? 'Periode belum aktif' }}</span></p><h1>Dokumen perencanaan</h1><p class="page-description">Kelola Prota, Promes, alokasi waktu, dan kriteria ketuntasan berdasarkan ATP/Silabus.</p></div><div class="heading-actions"><a class="button button-secondary" href="/akademik/perangkat/paket-administrasi">Pratinjau paket administrasi</a><button type="button" class="button button-primary" @click="createDocument">Dokumen baru</button></div></header>
            <section v-if="!active_term" class="notice-card" role="status"><strong>Periode akademik belum aktif</strong><p>Dokumen perencanaan dapat disimpan setelah sekolah menetapkan periode akademik aktif.</p></section>
            <div class="workspace">
                <aside class="document-list" aria-label="Dokumen tersimpan"><div class="list-heading"><div><h2>Dokumen tersimpan</h2><p>Pilih dokumen untuk melanjutkan penyuntingan.</p></div><span class="document-count">{{ documents.length }}</span></div><article v-for="document in documents" :key="document.id" class="document-entry"><button type="button" class="document-item" :class="{ selected: activeId === document.id }" :aria-current="activeId === document.id ? 'page' : undefined" @click="editDocument(document)"><span class="document-kind">{{ documentTypes.find((item) => item.value === document.type)?.label ?? document.type }}</span><strong>{{ document.title }}</strong><span class="document-meta">{{ document.semester ? `Semester ${document.semester}` : 'Tahunan' }} · {{ formatUpdatedAt(document.updated_at) }}</span></button><RowActionButton action="delete" label="Hapus" :aria-label="`Hapus ${document.title}`" @click="deleteDocument(document)" /></article><div v-if="!documents.length" class="empty-list"><strong>Belum ada dokumen</strong><span>Dokumen tersimpan akan muncul di sini.</span></div></aside>
                <section class="editor" aria-labelledby="editor-title">
                    <div class="editor-heading"><div><p class="section-label">{{ isEditing ? 'Penyuntingan dokumen' : 'Dokumen baru' }}</p><h2 id="editor-title">{{ isEditing ? form.title || 'Dokumen tanpa judul' : 'Siapkan dokumen' }}</h2></div><span class="period-badge">{{ active_term?.name ?? 'Periode tidak tersedia' }}</span></div>
                    <form class="editor-form" @submit.prevent="saveDocument">
                        <fieldset class="type-fieldset" :disabled="isEditing"><legend>Jenis dokumen</legend><div class="type-grid"><button v-for="item in documentTypes" :key="item.value" type="button" class="type-option" :class="{ active: form.type === item.value }" :aria-pressed="form.type === item.value" @click="changeType(item.value)"><span class="type-radio" aria-hidden="true"/><span><strong>{{ item.label }}</strong><small>{{ item.description }}</small></span></button></div></fieldset>
                        <div class="metadata-grid"><label class="field plan-field"><span>ATP / Silabus <b aria-hidden="true">*</b></span><select v-model.number="form.plan_id" required :disabled="(isEditing && Boolean(form.plan_id)) || !plans.length" aria-describedby="plan-help"><option :value="null" disabled>Pilih ATP/Silabus</option><option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.subject_name }} · Kelas {{ plan.grade_level }} · {{ plan.curriculum }}</option></select><small id="plan-help" class="field-hint">{{ selectedPlan ? `${selectedPlan.subject_name}, kelas ${selectedPlan.grade_level}` : 'Pilih sumber perencanaan untuk memuat bab dan unit.' }}</small><small v-if="form.errors.plan_id" class="field-error">{{ form.errors.plan_id }}</small></label><label class="field"><span>Nama dokumen</span><input v-model="form.title" required maxlength="180" autocomplete="off" placeholder="Contoh: Prota Matematika Kelas VII"/><small v-if="form.errors.title" class="field-error">{{ form.errors.title }}</small></label><label class="field"><span>Semester</span><select v-model="form.semester" :disabled="currentType.annual"><option :value="null">Tahunan</option><option value="ganjil">Ganjil</option><option value="genap">Genap</option></select><small v-if="form.errors.semester" class="field-error">{{ form.errors.semester }}</small></label></div>

                        <section v-if="form.type === 'prota' || form.type === 'promes'" class="structured-section"><div class="section-heading"><div><h3>{{ form.type === 'prota' ? 'Rencana tahunan' : 'Pemetaan semester' }}</h3><p>Bab dan unit diturunkan dari ATP/Silabus terpilih; sesuaikan alokasi JP untuk dokumen ini.</p></div><button type="button" class="button button-secondary compact-button" @click="addChapter">Tambah bab</button></div><div v-if="!chapters.length" class="empty-state"><strong>Belum ada bab di ATP/Silabus</strong><span>Pilih ATP/Silabus yang memiliki bab dan unit, atau tambahkan bab di ATP terlebih dahulu.</span></div><article v-for="(chapter, index) in chapters" :key="chapter.id" class="chapter-card"><div class="chapter-heading"><span class="chapter-index">{{ index + 1 }}</span><label class="field chapter-title"><span>Nama bab</span><input v-model="chapter.title" maxlength="255" :aria-label="`Nama bab ${index + 1}`" placeholder="Contoh: Bilangan"/></label><label class="field"><span>Semester</span><select v-model="chapter.semester"><option value="ganjil">Ganjil</option><option value="genap">Genap</option></select></label><button type="button" class="icon-action danger-action" :aria-label="`Hapus bab ${index + 1}`" @click="removeChapter(chapter.id)">Hapus</button></div><div class="unit-list"><div v-for="unit in units.filter((row) => String(row.chapter_id) === String(chapter.id))" :key="unit.id" class="unit-row"><div class="unit-copy"><strong>{{ unit.code || 'Unit pembelajaran' }}</strong><span>{{ unit.objective || unit.material || 'Tujuan/materi belum diisi pada ATP.' }}</span></div><label class="field jp-field"><span>JP</span><input v-model.number="unit.jp" type="number" min="0" max="100" :aria-label="`Alokasi JP ${unitLabel(unit)}`"/></label><button type="button" class="icon-action danger-action" :aria-label="`Keluarkan ${unitLabel(unit)} dari dokumen`" @click="removeUnit(unit.id)">Hapus</button></div><p v-if="!units.some((row) => String(row.chapter_id) === String(chapter.id))" class="empty-inline">Belum ada unit pada bab ini.</p><button type="button" class="text-action" @click="addUnit(chapter.id)">+ Tambah unit ke bab</button></div></article></section>

                        <section v-else-if="form.type === 'alokasi_waktu'" class="structured-section"><div class="section-heading"><div><h3>Pengaturan waktu belajar</h3><p>Isi kapasitas pembelajaran dan bulan pertama tiap semester.</p></div></div><div class="allocation-grid"><label class="field"><span>JP per minggu</span><input v-model.number="form.content.jp_per_week" type="number" min="1" max="60"/></label><label v-for="semester in semesters" :key="semester" class="field"><span>Minggu efektif {{ semester }}</span><input v-model.number="form.content.effective_weeks[semester]" type="number" min="0" max="30"/></label><label v-for="semester in semesters" :key="`month-${semester}`" class="field"><span>Bulan mulai {{ semester }}</span><select v-model.number="form.content.monthly_period[semester]"><option v-for="month in months" :key="month" :value="month">{{ monthName(month) }}</option></select></label></div><div class="capacity-summary"><span>Perkiraan kapasitas semester ganjil</span><strong>{{ Number(form.content.jp_per_week || 0) * Number(form.content.effective_weeks.ganjil || 0) }} JP</strong><span>Semester genap</span><strong>{{ Number(form.content.jp_per_week || 0) * Number(form.content.effective_weeks.genap || 0) }} JP</strong></div></section>

                        <section v-if="form.type === 'promes'" class="structured-section distribution-section"><div class="section-heading"><div><h3>Distribusi per minggu</h3><p>Materi dialokasikan berurutan sesuai JP efektif dan kapasitas JP per minggu dari ATP atau Analisis Alokasi Waktu.</p></div></div><p v-if="promesOverage" class="capacity-warning" role="status">Alokasi materi melebihi kapasitas minggu efektif sebanyak {{ promesOverage }} JP. Tambahkan minggu efektif atau sesuaikan JP di ATP/Analisis Alokasi Waktu.</p><div v-if="!promesWeeks.some((week) => week.units.length)" class="empty-state"><strong>Belum ada unit untuk semester ini</strong><span>Pilih semester yang memiliki bab, unit, dan alokasi JP di ATP/Silabus.</span></div><div v-else class="month-grid"><section v-for="month in [...new Set(promesWeeks.map((week) => week.month))]" :key="month" class="month-card"><h4>{{ monthName(month) }}</h4><div v-for="week in promesWeeks.filter((item) => item.month === month)" :key="week.week" class="week-row"><b>M{{ week.week }}</b><span v-if="week.units.length">{{ week.units.map((unit) => `${unit.label} · ${unit.jp} JP`).join('; ') }}</span><span v-else class="week-empty">—</span></div></section></div></section>

                        <section v-else class="structured-section"><div class="section-heading"><div><h3>Kriteria ketercapaian</h3><p>Sesuaikan empat deskripsi rentang dan tetapkan KKM per unit ATP.</p></div></div><div class="interval-list"><div v-for="(interval, index) in form.content.intervals" :key="index" class="interval-row"><span class="interval-number">{{ Number(index) + 1 }}</span><label class="field"><span>Rentang nilai</span><input v-model="interval.range" maxlength="40" :aria-label="`Rentang interval ${Number(index) + 1}`" placeholder="0–40%"/></label><label class="field"><span>Deskripsi capaian</span><input v-model="interval.label" maxlength="120" :aria-label="`Deskripsi interval ${Number(index) + 1}`" placeholder="Perlu bimbingan"/></label></div></div><div class="section-heading unit-heading"><div><h3>KKM per unit</h3><p>Nilai awal dapat mengikuti ATP dan disesuaikan khusus untuk dokumen ini.</p></div></div><div v-if="!units.length" class="empty-state"><strong>ATP belum memiliki unit</strong><span>Tambahkan unit pembelajaran pada ATP/Silabus untuk mengisi KKM per unit.</span></div><div v-for="unit in units" :key="unit.id" class="threshold-row"><span><strong>{{ unit.code || 'Unit pembelajaran' }}</strong><small>{{ unit.objective || unit.material || 'Tujuan/materi belum diisi' }}</small></span><label class="field kkm-field"><span>KKM</span><input v-model.number="unit.kkm" type="number" min="0" max="100" placeholder="75" :aria-label="`KKM ${unitLabel(unit)}`"/></label></div></section>

                        <p v-if="form.errors.content" class="field-error" role="alert">{{ form.errors.content }}</p><p v-if="form.errors.academic_term_id" class="field-error" role="alert">{{ form.errors.academic_term_id }}</p><p v-if="notice" class="success-message" role="status">{{ notice }}</p><p v-if="form.hasErrors" class="error-summary" role="alert">Periksa isian yang ditandai; dokumen belum disimpan.</p>
                        <footer class="editor-actions"><span class="save-context">{{ isEditing ? 'Perubahan akan disimpan ke dokumen ini.' : 'Dokumen terikat pada ATP dan periode aktif.' }}</span><button type="submit" class="button button-primary" :disabled="form.processing || !active_term || !form.plan_id">{{ form.processing ? 'Menyimpan…' : isEditing ? 'Simpan perubahan' : 'Simpan dokumen' }}</button></footer>
                    </form>
                </section>
            </div>
        </main>
    </AppLayout>
</template>

<style scoped>
.documents-page{--doc-ink:var(--ink,#17243a);--doc-muted:var(--ink-soft,#61718a);--doc-line:var(--line,#dce4ef);--doc-surface:var(--surface,#fff);--doc-canvas:var(--surface-subtle,#f5f7fb);--doc-primary:var(--primary,#315caa);--doc-primary-soft:var(--primary-soft,#eaf0fb);max-width:1480px;margin:0 auto;padding:clamp(1rem,2.5vw,2.25rem);color:var(--doc-ink)}
.page-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:1.25rem;margin-bottom:1.35rem}.page-context,.section-label{margin:0;color:var(--doc-primary);font-size:.78rem;font-weight:780}.term-label,.period-badge{display:inline-flex;margin-left:.4rem;padding:.32rem .58rem;border:1px solid var(--doc-line);border-radius:999px;color:var(--doc-muted);font-size:.73rem;font-weight:650}.page-heading h1{margin:.35rem 0;font-size:clamp(1.65rem,3vw,2.3rem);line-height:1.12;font-weight:820;letter-spacing:-.035em}.page-description{max-width:650px;margin:0;color:var(--doc-muted);line-height:1.55}.heading-actions{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:.6rem}.button{display:inline-flex;min-height:44px;align-items:center;justify-content:center;padding:.65rem 1rem;border:1px solid transparent;border-radius:.65rem;font:inherit;font-size:.86rem;font-weight:760;cursor:pointer}.button-primary{background:var(--doc-primary);color:#fff;box-shadow:0 4px 12px rgb(49 92 170 / 16%)}.button-secondary{border-color:var(--doc-line);background:var(--doc-surface);color:var(--doc-ink);text-decoration:none}.button:disabled{cursor:not-allowed;opacity:.55}.workspace{display:grid;grid-template-columns:minmax(245px,300px) minmax(0,1fr);align-items:start;gap:1rem}.document-list,.editor,.notice-card{min-width:0;border:1px solid var(--doc-line);border-radius:.9rem;background:var(--doc-surface);box-shadow:0 6px 20px rgb(24 44 78 / 4%)}.document-list{position:sticky;top:1rem;overflow:hidden;padding:.9rem}.list-heading,.editor-heading{display:flex;align-items:center;justify-content:space-between;gap:.8rem}.list-heading{margin-bottom:.7rem}.list-heading h2,.editor-heading h2{margin:0;font-size:1.05rem;font-weight:810}.list-heading p{margin:.22rem 0 0;color:var(--doc-muted);font-size:.75rem;line-height:1.45}.document-count{display:grid;min-width:28px;height:28px;place-items:center;border-radius:50%;background:var(--doc-primary-soft);color:var(--doc-primary);font-size:.77rem;font-weight:800}.document-item{display:grid;width:100%;gap:.28rem;margin-top:.38rem;padding:.72rem;border:1px solid transparent;border-radius:.65rem;background:transparent;color:var(--doc-ink);text-align:left;cursor:pointer}.document-item:hover{background:var(--doc-canvas)}.document-item.selected{border-color:color-mix(in srgb,var(--doc-primary) 38%,transparent);background:var(--doc-primary-soft)}.document-kind{color:var(--doc-primary);font-size:.7rem;font-weight:760}.document-item strong{font-size:.86rem;line-height:1.4}.document-meta{color:var(--doc-muted);font-size:.71rem;line-height:1.4}.empty-list,.empty-state{display:grid;gap:.25rem;padding:.85rem;border:1px dashed var(--doc-line);border-radius:.65rem;color:var(--doc-muted);font-size:.78rem;line-height:1.45}.empty-list{margin-top:.7rem}.empty-list strong,.empty-state strong{color:var(--doc-ink)}.editor{padding:clamp(1rem,2vw,1.45rem)}.editor-heading{align-items:flex-start;padding-bottom:1rem;border-bottom:1px solid var(--doc-line)}.editor-heading h2{margin-top:.25rem;font-size:1.25rem;letter-spacing:-.02em}.period-badge{margin:0;white-space:nowrap}.editor-form{padding-top:1rem}.type-fieldset{min-width:0;margin:0;padding:0 0 1rem;border:0;border-bottom:1px solid var(--doc-line)}.type-fieldset legend{margin-bottom:.65rem;padding:0;color:var(--doc-muted);font-size:.75rem;font-weight:780}.type-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.55rem}.type-option{display:flex;min-height:82px;align-items:flex-start;gap:.6rem;padding:.72rem;border:1px solid var(--doc-line);border-radius:.7rem;background:var(--doc-surface);color:var(--doc-ink);text-align:left;cursor:pointer}.type-option.active{border-color:var(--doc-primary);background:var(--doc-primary-soft);box-shadow:inset 0 0 0 1px var(--doc-primary)}.type-option strong,.type-option small{display:block}.type-option strong{font-size:.8rem;line-height:1.35}.type-option small{margin-top:.25rem;color:var(--doc-muted);font-size:.72rem;line-height:1.45}.type-radio{flex:0 0 16px;width:16px;height:16px;margin-top:.08rem;border:1.5px solid #94a3b8;border-radius:50%}.type-option.active .type-radio{border:4px solid var(--doc-primary);background:var(--doc-surface)}.type-fieldset:disabled .type-option{cursor:not-allowed;opacity:.7}.metadata-grid{display:grid;grid-template-columns:minmax(220px,1.1fr) minmax(200px,1fr) minmax(140px,.55fr);gap:.75rem;margin:1rem 0 1.2rem}.field{display:grid;min-width:0;gap:.35rem}.field>span{color:var(--doc-muted);font-size:.75rem;font-weight:760}.field b{color:#b42332}.field input,.field select{width:100%;min-height:42px;padding:.62rem .68rem;border:1px solid var(--doc-line);border-radius:.58rem;outline:none;background:var(--doc-surface);color:var(--doc-ink);font:inherit;font-size:.85rem}.field select:disabled{background:var(--doc-canvas);color:var(--doc-muted)}.field input:focus,.field select:focus,button:focus-visible{border-color:var(--doc-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--doc-primary) 20%,transparent);outline:none}.field-hint,.field-error{font-size:.74rem;line-height:1.45}.field-hint{color:var(--doc-muted)}.field-error{display:block;margin:.25rem 0 0;color:#a52c37}.structured-section{padding-top:.15rem}.section-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:.8rem;margin:1rem 0 .7rem}.section-heading h3{margin:0;font-size:1rem;font-weight:810}.section-heading p{margin:.25rem 0 0;color:var(--doc-muted);font-size:.77rem;line-height:1.5}.compact-button{min-height:38px;padding:.5rem .75rem;font-size:.78rem}.chapter-card{margin:.65rem 0;padding:.8rem;border:1px solid var(--doc-line);border-radius:.7rem;background:var(--doc-canvas)}.chapter-heading{display:grid;grid-template-columns:28px minmax(0,1fr) minmax(130px,.4fr) auto;align-items:end;gap:.6rem}.chapter-index,.interval-number{display:grid;width:26px;height:26px;place-items:center;border-radius:50%;background:var(--doc-primary-soft);color:var(--doc-primary);font-size:.75rem;font-weight:800}.chapter-title{min-width:0}.icon-action{min-height:38px;padding:.45rem .65rem;border:1px solid var(--doc-line);border-radius:.55rem;background:var(--doc-surface);color:var(--doc-ink);font:inherit;font-size:.75rem;font-weight:700;cursor:pointer}.danger-action{color:#a52c37}.unit-list{display:grid;gap:.5rem;margin:.75rem 0 0 2.1rem}.unit-row{display:grid;grid-template-columns:minmax(0,1fr) 76px auto;align-items:end;gap:.55rem;padding:.6rem;border-left:2px solid color-mix(in srgb,var(--doc-primary) 35%,transparent);background:var(--doc-surface);border-radius:.15rem .5rem .5rem .15rem}.unit-copy{display:grid;align-self:center;gap:.2rem;min-width:0}.unit-copy strong{font-size:.78rem}.unit-copy span{color:var(--doc-muted);font-size:.74rem;line-height:1.4}.jp-field{width:76px}.text-action{justify-self:start;padding:.3rem 0;border:0;background:none;color:var(--doc-primary);font:inherit;font-size:.77rem;font-weight:750;cursor:pointer}.empty-inline{margin:.2rem 0;color:var(--doc-muted);font-size:.75rem}.allocation-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.75rem}.capacity-summary{display:grid;grid-template-columns:1fr auto;gap:.55rem 1rem;margin-top:1rem;padding:.85rem 1rem;border-radius:.65rem;background:var(--doc-primary-soft);color:var(--doc-muted);font-size:.78rem}.capacity-summary strong{color:var(--doc-primary);font-size:.9rem}.interval-list{display:grid;gap:.55rem}.interval-row{display:grid;grid-template-columns:28px minmax(130px,.65fr) minmax(0,1.35fr);align-items:end;gap:.65rem;padding:.65rem;border:1px solid var(--doc-line);border-radius:.65rem}.interval-number{margin-bottom:.45rem}.unit-heading{margin-top:1.4rem;padding-top:1rem;border-top:1px solid var(--doc-line)}.threshold-row{display:grid;grid-template-columns:minmax(0,1fr) 110px;align-items:center;gap:1rem;padding:.68rem 0;border-bottom:1px solid var(--doc-line)}.threshold-row>span{display:grid;gap:.2rem;min-width:0}.threshold-row strong{font-size:.8rem}.threshold-row small{color:var(--doc-muted);font-size:.74rem;line-height:1.4}.kkm-field{width:110px}.editor-actions{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin:1rem -1px 0;padding-top:.9rem;border-top:1px solid var(--doc-line)}.save-context{color:var(--doc-muted);font-size:.75rem;line-height:1.45}.notice-card{margin:1rem 0;padding:1rem 1.1rem;border-left:4px solid var(--doc-primary)}.notice-card strong{font-size:.9rem}.notice-card p{margin:.25rem 0 0;color:var(--doc-muted);font-size:.82rem;line-height:1.5}.success-message,.error-summary{margin:.8rem 0 0;padding:.68rem .75rem;border-radius:.55rem;font-size:.8rem}.success-message{background:#e9f7f0;color:#176c4b}.error-summary{background:#fff0f0;color:#a52c37}
:global(.dark) .documents-page{--doc-ink:var(--ink,#e5eaf3);--doc-muted:var(--ink-soft,#a7b4c8);--doc-line:var(--line,#344258);--doc-surface:var(--surface,#202a3a);--doc-canvas:var(--surface-subtle,#182130);--doc-primary:var(--primary,#8fb1ff);--doc-primary-soft:rgb(73 112 184 / 22%)}
@media(max-width:1000px){.workspace{grid-template-columns:minmax(0,1fr)}.document-list{position:static;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));align-items:start}.list-heading,.empty-list{grid-column:1/-1}.document-item{min-width:0;margin-top:0}}
@media(max-width:700px){.documents-page{padding:1rem}.page-heading{align-items:flex-start;flex-direction:column}.heading-actions,.heading-actions .button{width:100%}.heading-actions{flex-direction:column}.document-list{grid-template-columns:minmax(0,1fr)}.list-heading,.empty-list{grid-column:auto}.editor-heading{flex-direction:column}.period-badge{white-space:normal}.type-grid,.metadata-grid,.allocation-grid{grid-template-columns:minmax(0,1fr)}.chapter-heading{grid-template-columns:26px minmax(0,1fr) auto}.chapter-heading>.field:nth-of-type(2){grid-column:2}.chapter-heading>.icon-action{grid-column:3;grid-row:1}.unit-list{margin-left:0}.unit-row{grid-template-columns:minmax(0,1fr) 64px auto}.jp-field{width:64px}.interval-row{grid-template-columns:26px minmax(0,1fr)}.interval-row .field:last-child{grid-column:2}.threshold-row{grid-template-columns:minmax(0,1fr) 82px}.kkm-field{width:82px}.editor-actions{align-items:stretch;flex-direction:column}.editor-actions .button{width:100%}.section-heading{align-items:flex-start}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}}
.document-entry{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:.3rem;border-top:1px solid var(--doc-line)}.document-entry .document-item{margin-top:0}.delete-document{padding:.35rem .45rem;border:0;border-radius:.4rem;background:transparent;color:#a52c37;font:inherit;font-size:.7rem;font-weight:740;cursor:pointer}.delete-document:hover{background:#fff0f0}.month-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.65rem}.month-card{overflow:hidden;border:1px solid var(--doc-line);border-radius:.65rem;background:var(--doc-canvas)}.month-card h4{margin:0;padding:.55rem .7rem;border-bottom:1px solid var(--doc-line);font-size:.79rem;text-transform:capitalize}.week-row{display:grid;grid-template-columns:30px minmax(0,1fr);gap:.45rem;padding:.45rem .65rem;border-bottom:1px solid var(--doc-line);font-size:.7rem;line-height:1.4}.week-row:last-child{border-bottom:0}.week-row b{color:var(--doc-primary)}.week-row span{overflow-wrap:anywhere}.week-empty{color:var(--doc-faint)}.distribution-section{margin-top:1.2rem;padding-top:.85rem;border-top:1px solid var(--doc-line)}
.capacity-warning{padding:.7rem .8rem;border:1px solid #d6a333;border-radius:.55rem;background:#fff6dd;color:#684708;font-size:.78rem;line-height:1.45}
@media(max-width:700px){.month-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:420px){.month-grid{grid-template-columns:minmax(0,1fr)}}
.documents-page{container-type:inline-size}
@container (max-width: 950px){.page-heading{align-items:stretch;flex-direction:column}.heading-actions{width:100%;flex-wrap:wrap}.workspace{grid-template-columns:minmax(0,1fr)}.document-list{position:static;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));max-height:300px;overflow:auto}.list-heading,.empty-list{grid-column:1/-1}.document-item{min-width:0;margin-top:0}.metadata-grid{grid-template-columns:minmax(0,1.1fr) minmax(0,1fr) minmax(130px,.55fr)}}
@container (max-width: 700px){.document-list{grid-template-columns:minmax(0,1fr);max-height:240px}.list-heading,.empty-list{grid-column:auto}.type-grid,.metadata-grid,.allocation-grid{grid-template-columns:minmax(0,1fr)}.month-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
