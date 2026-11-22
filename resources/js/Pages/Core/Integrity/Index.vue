<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { useUnsavedDialog } from '@/composables/useUnsavedDialog';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface SurveyItem {
    id: number;
    respondent_name: string;
    respondent_role: string;
    service_name: string;
    rating: number;
    comment?: string | null;
    is_approved: boolean;
    created_at?: string | null;
}

const props = defineProps<{
    surveys: SurveyItem[];
    summary: { average_rating: number; total_responses: number };
    integrityConfig: {
        declaration: string;
        sop_category: string;
        areas: string[];
        service_options: string[];
        questions: { id: string; label: string; weight: number }[];
        answer_labels: Record<string, string>;
        period: { id: string; label: string; starts_at: string | null; ends_at: string | null };
        calculation_mode: 'weighted_mean' | 'simple_mean';
        instrument_version: string;
        config_revision: string;
        is_open: boolean;
    };
    schoolTimezone?: string;
    downloadCategories: Record<string, string>;
    can_manage: boolean;
}>();

const page = usePage<{ flash?: { message?: string } }>();
const pendingDelete = ref<SurveyItem | null>(null);
const deleting = ref(false);
const pendingQuestionIndex = ref<number | null>(null);
const localDateTime = (value: string | null) => {
    if (!value) return '';
    const parts = Object.fromEntries(new Intl.DateTimeFormat('en-CA', { timeZone: props.schoolTimezone || 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).formatToParts(new Date(value)).map((part) => [part.type, part.value]));
    return `${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}`;
};

const contentForm = useForm({
    expected_revision: props.integrityConfig.config_revision,
    declaration: props.integrityConfig.declaration,
    sop_category: props.integrityConfig.sop_category,
    areasText: props.integrityConfig.areas.join('\n'),
    servicesText: props.integrityConfig.service_options.join('\n'),
    questions: props.integrityConfig.questions.map((q) => ({ ...q })),
    answer_labels: { ...props.integrityConfig.answer_labels },
    calculation_mode: props.integrityConfig.calculation_mode,
    period: { ...props.integrityConfig.period, starts_at: localDateTime(props.integrityConfig.period.starts_at), ends_at: localDateTime(props.integrityConfig.period.ends_at) },
});

const showContentEditor = ref(false);
const { discardOpen, requestClose, discard } = useUnsavedDialog(contentForm, () => { contentForm.reset(); contentForm.clearErrors(); showContentEditor.value = false; });
const saveContent = () => {
    contentForm.transform((data) => ({
        expected_revision: data.expected_revision,
        declaration: data.declaration,
        sop_category: data.sop_category,
        areas: data.areasText.split(/\r?\n/).map((s) => s.trim()).filter(Boolean),
        service_options: data.servicesText.split(/\r?\n/).map((s) => s.trim()).filter(Boolean),
        questions: data.questions, answer_labels: data.answer_labels, calculation_mode: data.calculation_mode,
        period: { ...data.period, starts_at: data.period.starts_at || null, ends_at: data.period.ends_at || null },
    })).put('/pengaturan/zona-integritas/konten', {
        preserveScroll: true,
        onSuccess: () => { contentForm.expected_revision = props.integrityConfig.config_revision; contentForm.defaults(); },
    });
};

const addQuestion = () => contentForm.questions.push({ id: crypto.randomUUID(), label: '', weight: 1 });
const moveQuestion = (index: number, offset: number) => {
    const next = index + offset;
    if (next < 0 || next >= contentForm.questions.length) return;
    [contentForm.questions[index], contentForm.questions[next]] = [contentForm.questions[next], contentForm.questions[index]];
};
const removeQuestion = () => {
    if (pendingQuestionIndex.value !== null && contentForm.questions.length > 1) contentForm.questions.splice(pendingQuestionIndex.value, 1);
    pendingQuestionIndex.value = null;
};

const moderate = (survey: SurveyItem, approved: boolean) => {
    router.post(`/pengaturan/zona-integritas/${survey.id}/moderasi`, { approved }, { preserveScroll: true });
};

const confirmDelete = () => {
    if (!pendingDelete.value) return;
    deleting.value = true;
    router.delete(`/pengaturan/zona-integritas/${pendingDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            pendingDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Zona Integritas & IKM" />
    <AppLayout title="Zona Integritas & IKM">
        <main class="view integrity-admin-page">
            <section class="hero-card">
                <div>
                    <p class="eyebrow">Mutu layanan publik</p>
                    <h1>Zona Integritas & moderasi IKM</h1>
                    <p>Kelola maklumat pelayanan, 6 area perubahan WBK/WBBM, dan moderasi tanggapan Indeks Kepuasan Masyarakat.</p>
                </div>
                <div class="metrics-bar">
                    <div>
                        <span>Rata-rata IKM Terbit</span>
                        <strong>{{ summary.average_rating.toFixed(2) }} / 5.00</strong>
                    </div>
                    <div>
                        <span>Responden Disetujui</span>
                        <strong>{{ summary.total_responses }}</strong>
                    </div>
                    <AppButton variant="secondary" as="a" href="/pengaturan/zona-integritas/ekspor" icon="excel" icon-only title="Unduh rekap IKM dalam CSV" aria-label="Unduh rekap IKM dalam CSV">Ekspor CSV</AppButton>
                    <AppButton variant="secondary" as="a" href="/zona-integritas" target="_blank" rel="noreferrer">Lihat halaman publik</AppButton>
                </div>
            </section>

            <div v-if="page.props.flash?.message" class="notice success" role="status">{{ page.props.flash.message }}</div>

            <section class="panel integrity-panel"><div class="admin-form-header"><h2>Konfigurasi Zona Integritas dan IKM</h2><AppButton v-if="can_manage" variant="primary" type="button" @click="showContentEditor = true">Ubah konfigurasi IKM</AppButton></div><p>{{ integrityConfig.period.label }} · {{ integrityConfig.questions.length }} unsur penilaian</p></section>
            <Teleport to="body"><div v-if="showContentEditor" class="admin-form-overlay" role="dialog" aria-modal="true" aria-labelledby="integrity-editor-title" @click.self="requestClose" @keydown.esc.prevent="requestClose">
            <section class="panel integrity-panel admin-form-dialog"><header class="admin-form-header"><h2 id="integrity-editor-title">Konfigurasi Zona Integritas dan IKM</h2><AppButton type="button" icon="close" icon-only aria-label="Tutup konfigurasi IKM" title="Tutup konfigurasi IKM" :disabled="contentForm.processing" @click="requestClose" /></header>
                <form class="content-form" @submit.prevent="saveContent">
                    <h2>Konten maklumat & area perubahan Zona Integritas</h2>
                    <div class="form-grid">
                        <label class="wide">
                            <span>Teks maklumat pelayanan *</span>
                            <textarea v-model="contentForm.declaration" rows="3" maxlength="2000" required></textarea>
                        </label>
                        <label>
                            <span>Kategori tautan SOP Pusat Unduh</span>
                            <select v-model="contentForm.sop_category">
                                <option v-for="(label, key) in downloadCategories" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </label>
                        <label class="wide">
                            <span>Area perubahan Zona Integritas (satu area per baris) *</span>
                            <textarea v-model="contentForm.areasText" rows="6" required></textarea>
                        </label>
                        <label class="wide">
                            <span>Daftar unit layanan yang dinilai pada survei IKM (satu per baris)</span>
                            <textarea v-model="contentForm.servicesText" rows="4"></textarea>
                        </label>
                    </div>
                    <fieldset class="instrument-editor">
                        <legend>Instrumen dan periode survei IKM</legend>
                        <p>Perubahan hanya berlaku untuk jawaban baru. Pertanyaan dan bobot jawaban lama tetap tersimpan.</p>
                        <div class="form-grid">
                            <label><span>Kode periode *</span><input v-model="contentForm.period.id" required maxlength="80" pattern="[A-Za-z0-9_-]+" /></label>
                            <label><span>Nama periode *</span><input v-model="contentForm.period.label" required maxlength="120" /></label>
                            <label><span>Mulai menerima jawaban ({{ schoolTimezone || 'Asia/Jakarta' }})</span><input v-model="contentForm.period.starts_at" type="datetime-local" /></label>
                            <label><span>Akhir penerimaan jawaban</span><input v-model="contentForm.period.ends_at" type="datetime-local" /></label>
                            <label><span>Cara menghitung nilai</span><select v-model="contentForm.calculation_mode"><option value="weighted_mean">Rata-rata berbobot</option><option value="simple_mean">Rata-rata setiap unsur</option></select></label>
                        </div>
                        <article v-for="(question, index) in contentForm.questions" :key="question.id" class="instrument-question">
                            <label><span>Unsur {{ index + 1 }} *</span><input v-model="question.label" required maxlength="240" /></label>
                            <label><span>Bobot *</span><input v-model.number="question.weight" type="number" min="0.01" max="100" step="0.01" required /></label>
                            <div class="flex gap-2">
                                <AppButton type="button" variant="ghost" size="sm" :disabled="index === 0" @click="moveQuestion(index, -1)" aria-label="Pindah unsur ke atas">↑</AppButton>
                                <AppButton type="button" variant="ghost" size="sm" :disabled="index === contentForm.questions.length - 1" @click="moveQuestion(index, 1)" aria-label="Pindah unsur ke bawah">↓</AppButton>
                                <AppButton type="button" variant="danger" size="sm" :disabled="contentForm.questions.length === 1" @click="pendingQuestionIndex = index">Hapus unsur</AppButton>
                            </div>
                        </article>
                        <AppButton type="button" variant="secondary" :disabled="contentForm.questions.length >= 20" @click="addQuestion">Tambah unsur penilaian</AppButton>
                        <div class="form-grid">
                            <label v-for="score in [5, 4, 3, 2, 1]" :key="score"><span>Label jawaban nilai {{ score }} *</span><input v-model="contentForm.answer_labels[String(score)]" required maxlength="100" /></label>
                        </div>
                    </fieldset>
                    <div v-if="Object.keys(contentForm.errors).length" class="notice error" role="alert"><p v-for="(error, field) in contentForm.errors" :key="field">{{ error }}</p></div>
                    <div class="flex justify-end">
                        <AppButton variant="primary" type="submit" :loading="contentForm.processing" :disabled="contentForm.processing">
                            Simpan konten Zona Integritas
                        </AppButton>
                    </div>
                </form>
            </section>
            </div></Teleport>
            <ConfirmDialog :open="discardOpen" title="Buang perubahan IKM?" message="Perubahan konfigurasi yang belum disimpan akan hilang." confirm-label="Buang perubahan" @confirm="discard" @cancel="discardOpen = false" />

            <section class="panel integrity-panel">
                <h2>Antrean moderasi survei kepuasan masyarakat (IKM)</h2>
                <div class="table-scroll">
                    <table v-if="surveys.length" class="admin-table">
                        <thead>
                            <tr>
                                <th class="text-center">Responden</th>
                                <th class="text-center">Layanan</th>
                                <th class="text-center">Nilai</th>
                                <th class="text-center">Masukan / Komentar</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in surveys" :key="item.id">
                                <td>
                                    <strong>{{ item.respondent_name }}</strong>
                                    <small class="block text-xs text-[var(--ink-faint)]">{{ item.respondent_role }}</small>
                                </td>
                                <td class="text-center">{{ item.service_name }}</td>
                                <td class="text-center"><strong>{{ item.rating }} / 5</strong></td>
                                <td>{{ item.comment || '—' }}</td>
                                <td class="text-center">
                                    <span :class="['status-pill', item.is_approved ? 'approved' : 'pending']">
                                        {{ item.is_approved ? 'Disetujui' : 'Menunggu' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="row-actions">
                                        <AppButton
                                            variant="secondary"
                                            size="sm"
                                            icon-only
                                            :icon="item.is_approved ? 'close' : 'view'"
                                            type="button"
                                            :aria-label="item.is_approved ? 'Tarik persetujuan IKM' : 'Setujui tanggapan IKM'"
                                            :title="item.is_approved ? 'Tarik persetujuan IKM' : 'Setujui tanggapan IKM'"
                                            @click="moderate(item, !item.is_approved)"
                                        />
                                        <AppButton
                                            variant="danger"
                                            size="sm"
                                            icon-only
                                            icon="delete"
                                            type="button"
                                            aria-label="Hapus tanggapan IKM"
                                            title="Hapus tanggapan IKM"
                                            @click="pendingDelete = item"
                                        />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="empty-state">
                        <strong>Belum ada tanggapan survei IKM</strong>
                        <p>Penilaian dari pengunjung halaman `/zona-integritas` akan masuk ke tabel moderasi ini.</p>
                    </div>
                </div>
            </section>

            <ConfirmDialog
                :open="pendingDelete !== null"
                title="Hapus tanggapan survei IKM?"
                :message="pendingDelete ? `Hapus tanggapan IKM dari &quot;${pendingDelete.respondent_name}&quot; secara permanen?` : ''"
                confirm-label="Ya, hapus tanggapan"
                variant="danger"
                :loading="deleting"
                @confirm="confirmDelete"
                @cancel="pendingDelete = null"
            />
            <ConfirmDialog :open="pendingQuestionIndex !== null" title="Hapus unsur penilaian?" message="Unsur akan dihapus dari instrumen baru. Jawaban lama tetap tersimpan bersama pertanyaannya." confirm-label="Hapus unsur" @confirm="removeQuestion" @cancel="pendingQuestionIndex = null" />
        </main>
    </AppLayout>
</template>

<style scoped>
.instrument-editor { display: grid; gap: 1rem; padding: 1rem; border: 1px solid var(--line); border-radius: 8px; }
.instrument-question { display: grid; gap: .75rem; padding: 1rem; border: 1px solid var(--line); border-radius: 8px; background: var(--surface); }
.instrument-editor legend { font-weight: 600; padding-inline: .5rem; }
.integrity-admin-page { display: grid; gap: 1.25rem; max-width: 1100px; margin: auto; }
.hero-card, .integrity-panel { padding: 1.25rem; background: var(--surface); border: 1px solid var(--line); border-radius: 14px; }
.hero-card { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.metrics-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; }
.metrics-bar > div { padding: .6rem .9rem; border-radius: 10px; background: var(--surface-subtle); border: 1px solid var(--line); display: grid; gap: .15rem; }
.metrics-bar span { font-size: var(--type-meta); color: var(--ink-soft); }
.content-form { display: grid; gap: 1rem; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; }
.form-grid label { display: grid; gap: .35rem; font-size: var(--type-meta); font-weight: 600; color: var(--ink-soft); }
.form-grid label.wide { grid-column: 1 / -1; }
.form-grid select, .form-grid textarea { padding: .6rem .75rem; border: 1px solid var(--line-strong); border-radius: 10px; background: var(--surface); color: var(--ink); font: inherit; }
.table-scroll { overflow-x: auto; margin-top: .85rem; }
.admin-table { width: 100%; border-collapse: collapse; font-size: var(--type-sm); }
.admin-table th, .admin-table td { padding: .75rem; border-bottom: 1px solid var(--line); vertical-align: middle; }
.admin-table th { font-weight: 700; text-align: center; background: var(--surface-subtle); }
.row-actions { display: inline-flex; align-items: center; justify-content: flex-end; gap: .4rem; }
.status-pill { display: inline-block; padding: .2rem .55rem; border-radius: 999px; font-size: var(--type-meta); font-weight: 700; }
.status-pill.approved { background: var(--green-soft); color: var(--green); }
.status-pill.pending { background: var(--gold-soft); color: var(--gold); }
.empty-state { padding: 2.5rem 1rem; text-align: center; color: var(--ink-soft); }
.notice.success { padding: .85rem 1rem; border-radius: 10px; background: var(--green-soft); color: var(--green); border: 1px solid var(--line); }

.integrity-admin-page { width: 100%; min-width: 0; }
.integrity-panel, .content-form, .instrument-editor, .instrument-question, .form-grid, .form-grid label { min-width: 0; }
.hero-card > *, .instrument-editor legend { max-width: 100%; overflow-wrap: anywhere; }
.form-grid input, .form-grid select, .form-grid textarea { width: 100%; min-width: 0; max-width: 100%; box-sizing: border-box; }
@media (max-width: 640px) { .form-grid { grid-template-columns: minmax(0, 1fr); } .instrument-editor, .instrument-question { padding: .75rem; } }
</style>
