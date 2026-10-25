<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

type DocumentType = 'prota' | 'promes' | 'alokasi_waktu' | 'kktp';
type Semester = 'ganjil' | 'genap' | null;
type JsonPrimitive = string | number | boolean | null;
type JsonLevelOne = JsonPrimitive | JsonPrimitive[] | { [key: string]: JsonPrimitive };
type JsonLevelTwo = JsonLevelOne | JsonLevelOne[] | { [key: string]: JsonLevelOne };
type JsonObject = { [key: string]: JsonLevelTwo };

interface PlanningDocument {
    id: number;
    type: DocumentType;
    title: string;
    semester: Semester;
    content: JsonObject;
    updated_at?: string | null;
}

interface ActiveTerm {
    id: number;
    name: string;
}

const props = withDefaults(defineProps<{
    documents?: PlanningDocument[];
    active_term?: ActiveTerm | null;
}>(), {
    documents: () => [],
    active_term: null,
});

const documentTypes: { value: DocumentType; label: string; description: string; annual: boolean }[] = [
    { value: 'prota', label: 'Program Tahunan (Prota)', description: 'Distribusi tujuan, materi, dan JP sepanjang tahun ajaran.', annual: true },
    { value: 'promes', label: 'Program Semester (Promes)', description: 'Pemetaan materi dan JP ke minggu-minggu dalam semester.', annual: false },
    { value: 'alokasi_waktu', label: 'Analisis Alokasi Waktu', description: 'Bandingkan minggu efektif, JP tersedia, dan kebutuhan materi.', annual: true },
    { value: 'kktp', label: 'KKTP / KKM', description: 'Tetapkan kriteria capaian untuk setiap tujuan pembelajaran.', annual: false },
];

const activeId = ref<number | null>(null);
const jsonText = ref('');
const jsonError = ref('');
const notice = ref('');
const form = useForm<{
    type: DocumentType;
    title: string;
    academic_term_id: number | null;
    semester: Semester;
    content: JsonObject;
}>({
    type: 'prota',
    title: '',
    academic_term_id: props.active_term?.id ?? null,
    semester: 'ganjil',
    content: {},
});

const currentType = computed(() => documentTypes.find((item) => item.value === form.type) ?? documentTypes[0]);
const isEditing = computed(() => activeId.value !== null);
const endpoint = '/akademik/perangkat/dokumen';

function starterContent(type: DocumentType): JsonObject {
    switch (type) {
        case 'prota':
            return { chapters: [{ title: '', semester: 'ganjil' }], units: [{ chapter_id: '', objective: '', material: '', jp: 0 }] };
        case 'promes':
            return { semester: 'ganjil', months: [], units: [], distribution: [] };
        case 'alokasi_waktu':
            return { effective_weeks: { ganjil: 18, genap: 18 }, jp_per_week: 2, monthly_period: { ganjil: 7, genap: 1 }, planned_units: [] };
        case 'kktp':
            return { scale: 'interval', intervals: [{ label: 'Perlu bimbingan', range: '' }, { label: 'Mulai berkembang', range: '' }, { label: 'Berkembang sesuai harapan', range: '' }, { label: 'Sangat berkembang', range: '' }], units: [] };
    }
}

function prettyJson(value: JsonObject) {
    return JSON.stringify(value, null, 2);
}

function syncJsonToForm() {
    try {
        const parsed: unknown = JSON.parse(jsonText.value);
        if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
            throw new Error('Konten harus berupa objek JSON, misalnya { "units": [] }.');
        }
        form.content = parsed as JsonObject;
        jsonError.value = '';
        return true;
    } catch (error) {
        jsonError.value = error instanceof Error ? error.message : 'Format JSON tidak valid.';
        return false;
    }
}

watch(() => form.type, (type) => {
    if (!isEditing.value) {
        form.content = starterContent(type);
        jsonText.value = prettyJson(form.content);
        form.title = documentTypes.find((item) => item.value === type)?.label ?? '';
    }
    if (currentType.value.annual) form.semester = null;
    else if (!form.semester) form.semester = 'ganjil';
});

watch(() => props.active_term, (term) => {
    if (!isEditing.value) form.academic_term_id = term?.id ?? null;
}, { immediate: true });

function resetDocument() {
    activeId.value = null;
    form.clearErrors();
    form.reset();
    form.type = 'prota';
    form.title = documentTypes[0].label;
    form.academic_term_id = props.active_term?.id ?? null;
    form.semester = null;
    form.content = starterContent('prota');
    jsonText.value = prettyJson(form.content);
    jsonError.value = '';
    notice.value = '';
}

function createDocument() {
    resetDocument();
    form.semester = 'ganjil';
}

function editDocument(document: PlanningDocument) {
    activeId.value = document.id;
    form.clearErrors();
    form.type = document.type;
    form.title = document.title;
    form.academic_term_id = props.active_term?.id ?? null;
    form.semester = document.semester;
    form.content = structuredClone(document.content ?? {});
    jsonText.value = prettyJson(form.content);
    jsonError.value = '';
    notice.value = '';
}

function changeType(type: DocumentType) {
    if (isEditing.value || type === form.type) return;
    form.type = type;
}

function saveDocument() {
    notice.value = '';
    if (!syncJsonToForm()) return;
    if (!form.academic_term_id) {
        form.setError('academic_term_id', 'Periode akademik aktif belum tersedia.');
        return;
    }
    const options = {
        preserveScroll: true,
        onSuccess: () => { notice.value = 'Dokumen berhasil disimpan.'; },
    };
    if (activeId.value !== null) form.put(`${endpoint}/${activeId.value}`, options);
    else form.post(endpoint, options);
}

function formatUpdatedAt(value?: string | null) {
    if (!value) return 'Belum pernah diperbarui';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(date);
}

// Keep the starter JSON available on first render without relying on a watcher flush.
form.content = starterContent('prota');
form.title = documentTypes[0].label;
jsonText.value = prettyJson(form.content);
</script>

<template>
    <AppLayout title="Dokumen Perangkat Pembelajaran">
        <Head title="Dokumen Perangkat Pembelajaran" />
        <main class="documents-page">
            <header class="page-heading">
                <div>
                    <p class="page-context">Perangkat pembelajaran <span class="term-label">{{ active_term?.name ?? 'Periode belum aktif' }}</span></p>
                    <h1>Dokumen perencanaan</h1>
                    <p class="page-description">Kelola Prota, Promes, alokasi waktu, dan kriteria ketuntasan dalam satu tempat.</p>
                </div>
                <button type="button" class="button button-primary" @click="createDocument">Dokumen baru</button>
            </header>

            <section v-if="!active_term" class="notice-card" role="status">
                <strong>Periode akademik belum aktif</strong>
                <p>Dokumen perencanaan dapat disimpan setelah sekolah menetapkan periode akademik aktif.</p>
            </section>

            <div class="workspace">
                <aside class="document-list" aria-label="Dokumen tersimpan">
                    <div class="list-heading"><div><h2>Dokumen tersimpan</h2><p>Pilih dokumen untuk melanjutkan penyuntingan.</p></div><span class="document-count">{{ documents.length }}</span></div>
                    <button v-for="document in documents" :key="document.id" type="button" class="document-item" :class="{ selected: activeId === document.id }" :aria-current="activeId === document.id ? 'page' : undefined" @click="editDocument(document)">
                        <span class="document-kind">{{ documentTypes.find((item) => item.value === document.type)?.label ?? document.type }}</span>
                        <strong>{{ document.title }}</strong>
                        <span class="document-meta">{{ document.semester ? `Semester ${document.semester}` : 'Tahunan' }} · {{ formatUpdatedAt(document.updated_at) }}</span>
                    </button>
                    <div v-if="!documents.length" class="empty-list"><strong>Belum ada dokumen</strong><span>Dokumen yang disimpan akan muncul di sini.</span></div>
                </aside>

                <section class="editor" aria-labelledby="editor-title">
                    <div class="editor-heading">
                        <div><p class="section-label">{{ isEditing ? 'Penyuntingan dokumen' : 'Dokumen baru' }}</p><h2 id="editor-title">{{ isEditing ? form.title || 'Dokumen tanpa judul' : 'Siapkan dokumen' }}</h2></div>
                        <span class="period-badge">{{ active_term?.name ?? 'Periode tidak tersedia' }}</span>
                    </div>

                    <form class="editor-form" @submit.prevent="saveDocument">
                        <fieldset class="type-fieldset" :disabled="isEditing">
                            <legend>Jenis dokumen</legend>
                            <div class="type-grid">
                                <button v-for="item in documentTypes" :key="item.value" type="button" class="type-option" :class="{ active: form.type === item.value }" :aria-pressed="form.type === item.value" @click="changeType(item.value)">
                                    <span class="type-radio" aria-hidden="true" /><span><strong>{{ item.label }}</strong><small>{{ item.description }}</small></span>
                                </button>
                            </div>
                        </fieldset>

                        <div class="metadata-grid">
                            <label class="field"><span>Nama dokumen</span><input v-model="form.title" required maxlength="180" autocomplete="off" placeholder="Contoh: Prota Matematika Kelas VII" /><small v-if="form.errors.title" class="field-error">{{ form.errors.title }}</small></label>
                            <label class="field"><span>Semester</span><select v-model="form.semester" :disabled="currentType.annual"><option :value="null">Tahunan</option><option value="ganjil">Ganjil</option><option value="genap">Genap</option></select><small v-if="form.errors.semester" class="field-error">{{ form.errors.semester }}</small></label>
                        </div>

                        <div class="content-heading"><div><h3>Konten dokumen (JSON)</h3><p>{{ currentType.description }} Konten harus berupa objek JSON yang valid.</p></div><span class="format-tag">JSON</span></div>
                        <label class="json-label" for="document-json">Struktur konten</label>
                        <textarea id="document-json" v-model="jsonText" class="json-editor" spellcheck="false" autocapitalize="off" autocomplete="off" aria-describedby="json-help json-error" @input="syncJsonToForm" />
                        <p id="json-help" class="field-hint">Prota dan Promes memakai struktur bab/unit; Alokasi Waktu menyimpan minggu efektif dan JP; KKTP menyimpan interval serta kriteria per tujuan. Anda dapat menyesuaikan objek JSON dengan kebutuhan dokumen.</p>
                        <p v-if="jsonError" id="json-error" class="field-error" role="alert">{{ jsonError }}</p>
                        <p v-else-if="form.errors.content" class="field-error" role="alert">{{ form.errors.content }}</p>
                        <p v-if="form.errors.academic_term_id" class="field-error" role="alert">{{ form.errors.academic_term_id }}</p>
                        <p v-if="notice" class="success-message" role="status">{{ notice }}</p>
                        <p v-if="form.hasErrors" class="error-summary" role="alert">Periksa isian yang ditandai; dokumen belum disimpan.</p>

                        <footer class="editor-actions">
                            <span class="save-context">{{ isEditing ? 'Perubahan akan disimpan ke dokumen ini.' : 'Dokumen terkait dengan periode akademik aktif.' }}</span>
                            <button type="submit" class="button button-primary" :disabled="form.processing || !active_term || Boolean(jsonError)">{{ form.processing ? 'Menyimpan…' : isEditing ? 'Simpan perubahan' : 'Simpan dokumen' }}</button>
                        </footer>
                    </form>
                </section>
            </div>
        </main>
    </AppLayout>
</template>

<style scoped>
.documents-page{--doc-ink:var(--ink,#17243a);--doc-muted:var(--ink-soft,#61718a);--doc-line:var(--line,#dce4ef);--doc-surface:var(--surface,#fff);--doc-canvas:var(--surface-subtle,#f5f7fb);--doc-primary:var(--primary,#315caa);--doc-primary-soft:var(--primary-soft,#eaf0fb);max-width:1480px;margin:0 auto;padding:clamp(1rem,2.5vw,2.25rem);color:var(--doc-ink)}
.page-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:1.25rem;margin-bottom:1.35rem}.page-context,.section-label{margin:0;color:var(--doc-primary);font-size:.78rem;font-weight:780}.term-label,.period-badge{display:inline-flex;margin-left:.4rem;padding:.32rem .58rem;border:1px solid var(--doc-line);border-radius:999px;color:var(--doc-muted);font-size:.73rem;font-weight:650}.page-heading h1{margin:.35rem 0;font-size:clamp(1.65rem,3vw,2.3rem);line-height:1.12;font-weight:820;letter-spacing:-.035em}.page-description{max-width:650px;margin:0;color:var(--doc-muted);line-height:1.55}.button{display:inline-flex;min-height:44px;align-items:center;justify-content:center;padding:.65rem 1rem;border:1px solid transparent;border-radius:.65rem;font:inherit;font-size:.86rem;font-weight:760;cursor:pointer}.button-primary{background:var(--doc-primary);color:#fff;box-shadow:0 4px 12px rgb(49 92 170 / 16%)}.button-primary:hover:not(:disabled){filter:brightness(1.07)}.button:disabled{cursor:not-allowed;opacity:.55}.workspace{display:grid;grid-template-columns:minmax(245px,300px) minmax(0,1fr);align-items:start;gap:1rem}.document-list,.editor,.notice-card{min-width:0;border:1px solid var(--doc-line);border-radius:.9rem;background:var(--doc-surface);box-shadow:0 6px 20px rgb(24 44 78 / 4%)}.document-list{position:sticky;top:1rem;overflow:hidden;padding:.9rem}.list-heading,.editor-heading{display:flex;align-items:center;justify-content:space-between;gap:.8rem}.list-heading{margin-bottom:.7rem}.list-heading h2,.editor-heading h2{margin:0;font-size:1.05rem;font-weight:810}.list-heading p{margin:.22rem 0 0;color:var(--doc-muted);font-size:.75rem;line-height:1.45}.document-count{display:grid;min-width:28px;height:28px;place-items:center;border-radius:50%;background:var(--doc-primary-soft);color:var(--doc-primary);font-size:.77rem;font-weight:800}.document-item{display:grid;width:100%;gap:.28rem;margin-top:.38rem;padding:.72rem;border:1px solid transparent;border-radius:.65rem;background:transparent;color:var(--doc-ink);text-align:left;cursor:pointer}.document-item:hover{background:var(--doc-canvas)}.document-item.selected{border-color:color-mix(in srgb,var(--doc-primary) 38%,transparent);background:var(--doc-primary-soft)}.document-kind{color:var(--doc-primary);font-size:.7rem;font-weight:760}.document-item strong{font-size:.86rem;line-height:1.4}.document-meta{color:var(--doc-muted);font-size:.71rem;line-height:1.4}.empty-list{display:grid;gap:.25rem;margin-top:.7rem;padding:.85rem;border:1px dashed var(--doc-line);border-radius:.65rem;color:var(--doc-muted);font-size:.78rem;line-height:1.45}.empty-list strong{color:var(--doc-ink)}.editor{padding:clamp(1rem,2vw,1.45rem)}.editor-heading{align-items:flex-start;padding-bottom:1rem;border-bottom:1px solid var(--doc-line)}.editor-heading h2{margin-top:.25rem;font-size:1.25rem;letter-spacing:-.02em}.period-badge{margin:0;white-space:nowrap}.editor-form{padding-top:1rem}.type-fieldset{min-width:0;margin:0;padding:0 0 1rem;border:0;border-bottom:1px solid var(--doc-line)}.type-fieldset legend{margin-bottom:.65rem;padding:0;color:var(--doc-muted);font-size:.75rem;font-weight:780}.type-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.55rem}.type-option{display:flex;min-height:82px;align-items:flex-start;gap:.6rem;padding:.72rem;border:1px solid var(--doc-line);border-radius:.7rem;background:var(--doc-surface);color:var(--doc-ink);text-align:left;cursor:pointer}.type-option.active{border-color:var(--doc-primary);background:var(--doc-primary-soft);box-shadow:inset 0 0 0 1px var(--doc-primary)}.type-option strong,.type-option small{display:block}.type-option strong{font-size:.8rem;line-height:1.35}.type-option small{margin-top:.25rem;color:var(--doc-muted);font-size:.72rem;line-height:1.45}.type-radio{flex:0 0 16px;width:16px;height:16px;margin-top:.08rem;border:1.5px solid #94a3b8;border-radius:50%}.type-option.active .type-radio{border:4px solid var(--doc-primary);background:var(--doc-surface)}.type-fieldset:disabled .type-option{cursor:not-allowed;opacity:.7}.metadata-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(170px,.45fr);gap:.75rem;margin:1rem 0 1.2rem}.field{display:grid;gap:.35rem}.field>span,.json-label{color:var(--doc-muted);font-size:.75rem;font-weight:760}.field input,.field select,.json-editor{width:100%;border:1px solid var(--doc-line);border-radius:.58rem;outline:none;background:var(--doc-surface);color:var(--doc-ink);font:inherit}.field input,.field select{min-height:42px;padding:.62rem .68rem;font-size:.85rem}.field select:disabled{background:var(--doc-canvas);color:var(--doc-muted)}.field input:focus,.field select:focus,.json-editor:focus{border-color:var(--doc-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--doc-primary) 20%,transparent)}.content-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:.8rem;margin-bottom:.6rem}.content-heading h3{margin:0;font-size:.96rem;font-weight:800}.content-heading p{margin:.25rem 0 0;color:var(--doc-muted);font-size:.77rem;line-height:1.45}.format-tag{padding:.25rem .48rem;border-radius:.4rem;background:var(--doc-canvas);color:var(--doc-muted);font-size:.68rem;font-weight:800}.json-label{display:block;margin-bottom:.35rem}.json-editor{display:block;min-height:310px;padding:.8rem .9rem;background:var(--doc-canvas);font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.82rem;line-height:1.55;tab-size:2;resize:vertical}.field-hint{margin:.45rem 0 0;color:var(--doc-muted);font-size:.74rem;line-height:1.5}.field-error{display:block;margin:.4rem 0 0;color:#a52c37;font-size:.76rem;line-height:1.45}.success-message,.error-summary{margin:.8rem 0 0;padding:.68rem .75rem;border-radius:.55rem;font-size:.8rem}.success-message{background:#e9f7f0;color:#176c4b}.error-summary{background:#fff0f0;color:#a52c37}.editor-actions{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin:1rem -1px 0;padding-top:.9rem;border-top:1px solid var(--doc-line)}.save-context{color:var(--doc-muted);font-size:.75rem;line-height:1.45}.notice-card{margin:1rem 0;padding:1rem 1.1rem;border-left:4px solid var(--doc-primary)}.notice-card strong{font-size:.9rem}.notice-card p{margin:.25rem 0 0;color:var(--doc-muted);font-size:.82rem;line-height:1.5}button:focus-visible{outline:3px solid color-mix(in srgb,var(--doc-primary) 55%,transparent);outline-offset:2px}
:global(.dark) .documents-page{--doc-ink:var(--ink,#e5eaf3);--doc-muted:var(--ink-soft,#a7b4c8);--doc-line:var(--line,#344258);--doc-surface:var(--surface,#202a3a);--doc-canvas:var(--surface-subtle,#182130);--doc-primary:var(--primary,#8fb1ff);--doc-primary-soft:rgb(73 112 184 / 22%)}
@media(max-width:900px){.workspace{grid-template-columns:minmax(0,1fr)}.document-list{position:static}.document-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));align-items:start}.list-heading,.empty-list{grid-column:1/-1}.document-item{min-width:0;margin-top:0}}
@media(max-width:620px){.documents-page{padding:1rem}.page-heading{align-items:flex-start;flex-direction:column}.page-heading>.button{width:100%}.document-list{grid-template-columns:minmax(0,1fr)}.list-heading,.empty-list{grid-column:auto}.editor-heading{flex-direction:column}.period-badge{white-space:normal}.type-grid,.metadata-grid{grid-template-columns:minmax(0,1fr)}.editor-actions{align-items:stretch;flex-direction:column}.editor-actions .button{width:100%}.json-editor{min-height:260px}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}}
</style>
