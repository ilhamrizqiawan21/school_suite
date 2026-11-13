<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { nextTick, reactive, ref } from 'vue';
import RowActionButton from '@/components/RowActionButton.vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface Assignee { id: number; name: string }
interface Activity { id: number; action: string; changes?: Record<string, unknown> | null; created_at: string; actor?: { name: string } | null }
interface StaffTask {
    id: number; title: string; description?: string | null; category?: string | null; priority: string;
    attachment_name?: string | null;
    status: string; progress: number; due_at?: string | null; completed_at?: string | null;
    creator?: { name: string } | null; assignee?: { id: number; name: string } | null; activities: Activity[]; activities_count: number;
}

const props = defineProps<{ tasks: StaffTask[]; assignees: Assignee[]; can_manage: boolean; current_user_id: number;
    filters: { search?: string; status?: string; priority?: string; assignee_id?: number };
    summary: { active: number; overdue: number; completed: number };
    pagination: { current_page: number; last_page: number; total: number; from: number | null; to: number | null; prev_page_url: string | null; next_page_url: string | null };
}>();
const filters = reactive({ search: props.filters.search ?? '', status: props.filters.status ?? '', priority: props.filters.priority ?? '', assignee_id: String(props.filters.assignee_id ?? '') });
const applyFilters = () => router.get('/administrasi/tugas', { ...filters }, { preserveState: true, preserveScroll: true });
const resetFilters = () => { Object.assign(filters, { search: '', status: '', priority: '', assignee_id: '' }); applyFilters(); };
const progressErrors = reactive<Record<number, string>>({});
const savingProgress = reactive<Record<number, boolean>>({});
const focusForm = async () => { await nextTick(); document.getElementById('staff-task-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' }); };
const localDateTime = (value?: string | null) => { if (!value) return ''; const date = new Date(value); return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}T${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`; };
const overdue = (task: StaffTask) => ['assigned', 'in_progress'].includes(task.status) && !!task.due_at && new Date(task.due_at) < new Date();
const showForm = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({ title: '', description: '', category: '', priority: 'normal', due_at: '', assignee_id: '', status: 'assigned', attachment: null as File | null });

const openCreate = () => { editingId.value = null; form.reset(); form.priority = 'normal'; form.status = 'assigned'; form.clearErrors(); showForm.value = true; void focusForm(); };
const openEdit = (task: StaffTask) => {
    editingId.value = task.id;
    form.attachment = null;
    form.title = task.title;
    form.description = task.description ?? '';
    form.category = task.category ?? '';
    form.priority = task.priority;
    form.due_at = localDateTime(task.due_at);
    form.assignee_id = task.assignee?.id ? String(task.assignee.id) : '';
    form.status = task.status;
    form.clearErrors();
    showForm.value = true;
    void focusForm();
};
const save = () => {
    if (editingId.value) {
        form.transform((data) => ({ ...data, due_at: data.due_at ? new Date(data.due_at).toISOString() : '', _method: 'patch' })).post(`/administrasi/tugas/${editingId.value}`, { forceFormData: true, preserveScroll: true, onSuccess: () => { showForm.value = false; } });
    } else {
        form.transform((data) => ({ ...data, due_at: data.due_at ? new Date(data.due_at).toISOString() : '' })).post('/administrasi/tugas', { forceFormData: true, preserveScroll: true, onSuccess: () => { form.reset(); showForm.value = false; } });
    }
};
const selectAttachment = (event: Event) => { form.attachment = (event.target as HTMLInputElement).files?.[0] ?? null; };
const updateProgress = (task: StaffTask, event: Event) => {
    const value = Number((event.target as HTMLInputElement).value);
    const status = value === 100 ? 'completed' : value > 0 ? 'in_progress' : 'assigned';
    savingProgress[task.id] = true;
    delete progressErrors[task.id];
    router.patch(`/administrasi/tugas/${task.id}/progres`, { progress: value, status }, { preserveScroll: true, onError: (errors) => { progressErrors[task.id] = errors.progress ?? errors.status ?? 'Progres gagal disimpan.'; (event.target as HTMLInputElement).value = String(task.progress); }, onFinish: () => { savingProgress[task.id] = false; } });
};
const remove = (task: StaffTask) => {
    if (window.confirm(`Hapus tugas “${task.title}”? Riwayat tugas juga akan dihapus.`)) {
        router.delete(`/administrasi/tugas/${task.id}`, { preserveScroll: true });
    }
};
const statusLabel = (status: string) => ({ assigned: 'Ditugaskan', in_progress: 'Berjalan', completed: 'Selesai', cancelled: 'Dibatalkan' }[status] ?? status);
const priorityLabel = (priority: string) => ({ low: 'Rendah', normal: 'Normal', high: 'Tinggi', urgent: 'Mendesak' }[priority] ?? priority);
const statusClass = (status: string) => `task-status-${status}`;
const priorityClass = (priority: string) => `task-priority-${priority}`;
const dateLabel = (value?: string | null) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : 'Tanpa tenggat';
</script>

<template>
    <AppLayout title="Tugas Staf">
        <Head title="Tugas Staf" />
        <main class="view space-y-6 staff-tasks-page">
            <section class="hero-card staff-task-hero">
                <div>
                    <p class="eyebrow">Tata usaha</p>
                    <h1>Tugas staf</h1>
                    <p>Pantau penanggung jawab, tenggat, progres, dan riwayat tugas sekolah.</p>
                </div>
                <div v-if="can_manage" class="staff-task-hero-actions"><button type="button" class="primary-button" @click="openCreate">Tambah tugas</button></div>
            </section>

            <section class="task-summary-grid" aria-label="Ringkasan tugas">
                <div class="task-summary-card task-summary-active"><span>Tugas aktif</span><strong>{{ summary.active }}</strong><small>Menunggu atau sedang dikerjakan</small></div>
                <div class="task-summary-card task-summary-overdue"><span>Melewati tenggat</span><strong>{{ summary.overdue }}</strong><small>Perlu ditindaklanjuti</small></div>
                <div class="task-summary-card task-summary-completed"><span>Selesai</span><strong>{{ summary.completed }}</strong><small>Tugas yang sudah ditutup</small></div>
            </section>
            <div class="task-guidance"><strong>{{ can_manage ? 'Cara kerja tugas' : 'Perbarui tugas' }}</strong><span>{{ can_manage ? 'Buat tugas, pilih penanggung jawab, lalu pantau sampai selesai.' : 'Buka lampiran bila ada, lalu perbarui progres saat pekerjaan berjalan.' }}</span></div>
            <div v-if="showForm && can_manage" role="dialog" aria-modal="true" aria-labelledby="staff-task-modal-title" class="modal-backdrop" @click.self="showForm = false" @keydown.esc="showForm = false">
                <section id="staff-task-form" class="modal-card task-form-panel">
                    <div class="task-panel-heading"><div><p class="eyebrow">{{ editingId ? 'Perbarui pekerjaan' : 'Pekerjaan baru' }}</p><h2 id="staff-task-modal-title">{{ editingId ? 'Ubah tugas' : 'Tugas baru' }}</h2><p>Isi informasi inti agar penanggung jawab memahami hasil yang diharapkan.</p></div><button type="button" class="modal-close" aria-label="Tutup formulir tugas" @click="showForm = false">×</button></div>
                <ul v-if="Object.keys(form.errors).length" class="rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert"><li v-for="(error, field) in form.errors" :key="field">{{ error }}</li></ul>
                <form class="task-form-grid" @submit.prevent="save">
                    <label class="field md:col-span-2">Judul<input v-model="form.title" required maxlength="180" /><small v-if="form.errors.title">{{ form.errors.title }}</small></label>
                    <label class="field md:col-span-2">Uraian<textarea v-model="form.description" rows="3" maxlength="5000" /></label>
                    <label class="field">Kategori<input v-model="form.category" maxlength="60" placeholder="Contoh: administrasi" /></label>
                    <label class="field">Prioritas<select v-model="form.priority"><option value="low">Rendah</option><option value="normal">Normal</option><option value="high">Tinggi</option><option value="urgent">Mendesak</option></select></label>
                    <label class="field">Penanggung jawab<select v-model="form.assignee_id"><option value="">Belum ditentukan</option><option v-for="person in assignees" :key="person.id" :value="String(person.id)">{{ person.name }}</option></select><small v-if="form.errors.assignee_id">{{ form.errors.assignee_id }}</small></label>
                    <label class="field">Tenggat<input v-model="form.due_at" type="datetime-local" /></label>
                    <label class="field md:col-span-2">Lampiran<input type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" @change="selectAttachment" /><small v-if="form.errors.attachment">{{ form.errors.attachment }}</small><small>Maksimal 10 MB; file hanya dapat diunduh oleh admin/operator atau penanggung jawab.</small></label>
                    <label v-if="editingId" class="field">Status<select v-model="form.status"><option value="assigned">Ditugaskan</option><option value="in_progress">Berjalan</option><option value="completed">Selesai</option><option value="cancelled">Dibatalkan</option></select></label>
                    <div class="task-form-actions"><button type="button" class="secondary-button" @click="showForm = false">Batal</button><button type="submit" class="primary-button" :disabled="form.processing">Simpan tugas</button></div>
                </form>
                </section>
            </div>

            <section class="panel task-filter-panel">
                <div class="task-panel-heading compact"><div><p class="eyebrow">Penyaringan</p><h2>Daftar tugas</h2><p>Temukan tugas berdasarkan kata kunci, status, prioritas, atau penanggung jawab.</p></div></div>
                <form class="task-filter-grid" @submit.prevent="applyFilters">
                    <label class="field">Cari tugas<input v-model="filters.search" class="search-control" type="search" maxlength="180" placeholder="Judul atau kategori" /></label>
                    <label class="field">Status<select v-model="filters.status"><option value="">Semua status</option><option value="assigned">Ditugaskan</option><option value="in_progress">Berjalan</option><option value="completed">Selesai</option><option value="cancelled">Dibatalkan</option></select></label>
                    <label class="field">Prioritas<select v-model="filters.priority"><option value="">Semua prioritas</option><option value="low">Rendah</option><option value="normal">Normal</option><option value="high">Tinggi</option><option value="urgent">Mendesak</option></select></label>
                    <label v-if="can_manage" class="field">Penanggung jawab<select v-model="filters.assignee_id"><option value="">Semua staf</option><option v-for="person in assignees" :key="person.id" :value="String(person.id)">{{ person.name }}</option></select></label>
                    <div class="task-filter-actions"><button type="submit" class="primary-button">Terapkan filter</button><button type="button" class="secondary-button" @click="resetFilters">Reset</button></div>
                </form>
            </section>
            <section class="panel task-list-panel">
                <div v-if="!tasks.length" class="flex min-h-64 flex-col items-center justify-center px-6 py-12 text-center">
                    <div class="mb-4 grid h-12 w-12 place-items-center rounded-xl bg-blue-50 text-blue-700" aria-hidden="true">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 4.75h8A2.25 2.25 0 0 1 18.25 7v12A2.25 2.25 0 0 1 16 21.25H8A2.25 2.25 0 0 1 5.75 19V7A2.25 2.25 0 0 1 8 4.75Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75h6A1.25 1.25 0 0 1 16.25 5v1.5h-8.5V5A1.25 1.25 0 0 1 9 3.75ZM9 11h6m-6 4h6" />
                        </svg>
                    </div>
                    <strong class="text-base font-semibold text-slate-900">{{ filters.search || filters.status || filters.priority || filters.assignee_id ? 'Tidak ada tugas sesuai filter' : 'Belum ada tugas' }}</strong>
                    <p class="mt-1 max-w-md text-sm leading-6 text-slate-500">
                        {{ can_manage ? 'Buat tugas untuk membagikan pekerjaan dan tenggat kepada staf sekolah.' : 'Tugas yang diberikan kepada Anda akan muncul di sini.' }}
                    </p>
                    <button v-if="can_manage" type="button" class="primary-button mt-5" @click="openCreate">Buat tugas pertama</button>
                </div>
                <div v-else class="task-list">
                    <article v-for="task in tasks" :key="task.id" class="task-item">
                        <div class="task-main">
                            <div class="task-heading"><div class="task-title-block"><span class="task-category">{{ task.category || 'Umum' }}</span><h2>{{ task.title }}</h2></div><div class="task-badges"><span class="task-status-pill" :class="statusClass(task.status)">{{ statusLabel(task.status) }}</span><span class="task-priority-pill" :class="priorityClass(task.priority)">{{ priorityLabel(task.priority) }}</span></div></div>
                            <p v-if="task.description" class="task-description">{{ task.description }}</p>
                            <a v-if="task.attachment_name" class="block max-w-full break-all text-sm font-medium text-blue-700 hover:underline" :href="`/administrasi/tugas/${task.id}/lampiran`">Unduh lampiran: {{ task.attachment_name }}</a>
                            <div class="task-meta"><span><b>Penanggung jawab</b>{{ task.assignee?.name || 'Belum ditentukan' }}</span><span><b>Tenggat</b>{{ dateLabel(task.due_at) }}</span></div>
                            <p v-if="overdue(task)" class="task-overdue">Melewati tenggat</p>
                            <details v-if="task.activities?.length" class="task-history"><summary>Riwayat ({{ task.activities_count }}) · 5 terbaru</summary><ul><li v-for="activity in task.activities" :key="activity.id">{{ activity.actor?.name || 'Pengguna' }} · {{ ({ created: 'Tugas dibuat', updated: 'Tugas diubah', progress_updated: 'Progres diperbarui' } as Record<string, string>)[activity.action] ?? activity.action }} · {{ dateLabel(activity.created_at) }}</li></ul></details>
                        </div>
                        <div class="task-side">
                            <div class="task-progress"><div class="task-progress-heading"><span>Progres</span><strong>{{ task.progress }}%</strong></div><progress max="100" :value="task.progress" :aria-label="`Progres tugas ${task.title}`" /></div>
                            <label v-if="!can_manage && task.assignee?.id === current_user_id && task.status !== 'cancelled'" class="task-progress-editor">Perbarui progres<input type="range" min="0" max="100" :disabled="savingProgress[task.id]" step="10" :value="task.progress" @change="updateProgress(task, $event)" /></label>
                            <p v-if="progressErrors[task.id]" class="text-sm text-red-700" role="alert">{{ progressErrors[task.id] }}</p>
                            <div v-if="can_manage" class="task-actions"><RowActionButton action="edit" label="Ubah" :aria-label="`Ubah tugas ${task.title}`" @click="openEdit(task)" /><RowActionButton action="delete" label="Hapus" :aria-label="`Hapus tugas ${task.title}`" @click="remove(task)" /></div>
                            <small class="task-creator">Dibuat oleh {{ task.creator?.name || 'staf' }}</small>
                        </div>
                    </article>
                </div>
            </section>
            <nav v-if="pagination.total" class="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500" aria-label="Halaman tugas">
                <p>{{ pagination.from }}–{{ pagination.to }} dari {{ pagination.total }} tugas · Halaman {{ pagination.current_page }} / {{ pagination.last_page }}</p>
                <div class="flex gap-2"><button type="button" class="secondary-button" :disabled="!pagination.prev_page_url" @click="pagination.prev_page_url && router.get(pagination.prev_page_url, {}, { preserveState: true, preserveScroll: true })">Sebelumnya</button><button type="button" class="secondary-button" :disabled="!pagination.next_page_url" @click="pagination.next_page_url && router.get(pagination.next_page_url, {}, { preserveState: true, preserveScroll: true })">Berikutnya</button></div>
            </nav>
        </main>
    </AppLayout>
</template>

<style scoped>
.staff-tasks-page { --task-ink: #172b4d; --task-muted: #64748b; --task-line: #dbe3ef; }
.staff-task-hero { align-items: center; }
.staff-task-hero-actions { display: flex; align-items: center; gap: .6rem; }
.task-summary-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .8rem; }
.task-summary-card { display: grid; gap: .25rem; min-height: 7.25rem; border: 1px solid var(--task-line); border-radius: 1rem; background: #fff; padding: 1rem 1.1rem; }
.task-summary-card span { color: var(--task-muted); font-size: .76rem; font-weight: 800; }
.task-summary-card strong { color: var(--task-ink); font-size: 2rem; line-height: 1; }
.task-summary-card small { color: var(--task-muted); font-size: .74rem; line-height: 1.35; }
.task-summary-active { border-color: #c9d7f3; background: #f4f7ff; }
.task-summary-overdue { border-color: #f2c9c5; background: #fff8f7; }
.task-summary-overdue strong { color: #b42318; }
.task-summary-completed { border-color: #b9dec8; background: #f2fcf6; }
.task-summary-completed strong { color: #067647; }
.task-guidance { display: flex; align-items: center; gap: .65rem; border: 1px solid #d7e1f2; border-radius: .8rem; background: #f7faff; padding: .8rem 1rem; color: #52637c; font-size: .83rem; line-height: 1.45; }
.task-guidance strong { color: #3157a4; white-space: nowrap; }
.task-form-panel, .task-filter-panel { display: grid; gap: 1.25rem; padding: 1.25rem; }
.task-panel-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.task-panel-heading h2 { margin: .15rem 0 .25rem; color: var(--task-ink); font-size: 1.1rem; }
.task-panel-heading p:not(.eyebrow) { margin: 0; color: var(--task-muted); font-size: .82rem; line-height: 1.5; }
.task-panel-heading.compact { margin-bottom: -.15rem; }
.task-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
.task-form-grid .md\:col-span-2 { grid-column: 1 / -1; }
.task-form-actions { display: flex; justify-content: flex-end; gap: .5rem; grid-column: 1 / -1; border-top: 1px solid #e2e8f0; padding-top: 1rem; }
.task-filter-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .8rem; }
.task-filter-actions { display: flex; align-items: center; justify-content: flex-end; gap: .5rem; grid-column: 1 / -1; border-top: 1px solid #e2e8f0; padding-top: .9rem; }
.task-list-panel { overflow: hidden; padding: 0; }
.task-list { display: grid; }
.task-item { display: grid; grid-template-columns: minmax(0, 1fr) 220px; gap: 1.5rem; padding: 1.35rem 1.5rem; border-bottom: 1px solid #e2e8f0; }
.task-item:last-child { border-bottom: 0; }
.task-main, .task-side { min-width: 0; }
.task-main { display: grid; align-content: start; gap: .75rem; }
.task-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.task-title-block { min-width: 0; }
.task-category { display: block; margin-bottom: .25rem; color: #64748b; font-size: .7rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
.task-title-block h2 { margin: 0; color: var(--task-ink); font-size: 1rem; line-height: 1.35; overflow-wrap: anywhere; }
.task-badges { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: .4rem; flex: 0 0 auto; }
.task-status-pill, .task-priority-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: .3rem .6rem; font-size: .7rem; font-weight: 800; white-space: nowrap; }
.task-status-assigned { background: #eef4ff; color: #3157a4; }
.task-status-in_progress { background: #fff7e6; color: #a15c00; }
.task-status-completed { background: #ecfdf3; color: #067647; }
.task-status-cancelled { background: #f1f5f9; color: #64748b; }
.task-priority-low, .task-priority-normal { background: #f1f5f9; color: #475569; }
.task-priority-high { background: #fff7e6; color: #a15c00; }
.task-priority-urgent { background: #fef3f2; color: #b42318; }
.task-description { margin: 0; color: #475569; font-size: .84rem; line-height: 1.6; white-space: pre-wrap; overflow-wrap: anywhere; }
.task-meta { display: flex; flex-wrap: wrap; gap: .8rem 1.5rem; color: var(--task-muted); font-size: .78rem; }
.task-meta span { display: grid; gap: .15rem; min-width: min(12rem, 100%); }
.task-meta b { color: #94a3b8; font-size: .68rem; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; }
.task-overdue { margin: 0; color: #b42318; font-size: .76rem; font-weight: 800; }
.task-history { color: var(--task-muted); font-size: .75rem; }
.task-history summary { cursor: pointer; font-weight: 700; }
.task-history ul { display: grid; gap: .3rem; margin: .5rem 0 0; padding-left: 1rem; line-height: 1.4; }
.task-side { display: grid; align-content: center; gap: .85rem; border-left: 1px solid #e2e8f0; padding-left: 1.25rem; }
.task-progress { display: grid; gap: .45rem; }
.task-progress-heading { display: flex; align-items: center; justify-content: space-between; color: #52637c; font-size: .76rem; font-weight: 700; }
.task-progress-heading strong { color: var(--task-ink); font-size: .9rem; }
.task-progress progress { width: 100%; height: .5rem; }
.task-progress-editor { display: grid; gap: .35rem; color: #52637c; font-size: .76rem; font-weight: 700; }
.task-progress-editor input { width: 100%; accent-color: #315db3; }
.task-actions { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: .4rem; }
.task-creator { color: var(--task-muted); font-size: .72rem; text-align: right; }
.field { display: grid; min-width: 0; gap: .4rem; color: #42526b; font-size: .84rem; font-weight: 600; }
.field input, .field select, .field textarea { width: 100%; min-width: 0; min-height: 2.65rem; border: 1px solid #cbd5e1; border-radius: .6rem; padding: .6rem .75rem; background: white; color: #172b4d; font-weight: 400; }
.field small { font-weight: 400; line-height: 1.5; }
.hero-card { flex-wrap: wrap; }
.hero-card > div { min-width: 0; }
progress { overflow: hidden; border-radius: 999px; background: #e2e8f0; }
progress::-webkit-progress-bar { background: #e2e8f0; }
progress::-webkit-progress-value { background: #315db3; border-radius: 999px; }
progress::-moz-progress-bar { background: #315db3; }
@media (max-width: 900px) {
    .task-item { grid-template-columns: minmax(0, 1fr) 180px; gap: 1rem; }
    .task-side { padding-left: 1rem; }
    .task-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 680px) {
    .task-summary-grid, .task-form-grid, .task-filter-grid { grid-template-columns: 1fr; }
    .task-form-grid .md\:col-span-2, .task-filter-actions, .task-form-actions { grid-column: auto; }
    .task-guidance { align-items: flex-start; flex-direction: column; gap: .2rem; }
    .task-panel-heading, .task-heading { align-items: stretch; flex-direction: column; }
    .task-panel-heading .secondary-button { align-self: flex-start; }
    .task-item { grid-template-columns: 1fr; gap: 1rem; padding: 1.1rem; }
    .task-side { border-top: 1px solid #e2e8f0; border-left: 0; padding-top: 1rem; padding-left: 0; }
    .task-badges { justify-content: flex-start; }
    .task-actions, .task-creator { justify-content: flex-start; text-align: left; }
}
/* Shared card rhythm: task summaries stay neutral, accents carry the meaning. */
.task-summary-card { border-radius: var(--card-radius-compact); background: var(--surface) !important; }
.task-summary-active { border-top: 3px solid var(--primary) !important; }
.task-summary-overdue { border-top: 3px solid var(--red) !important; }
.task-summary-completed { border-top: 3px solid var(--green) !important; }
.task-form-panel, .task-filter-panel, .task-list-panel { border-radius: var(--card-radius); }
:global(html[data-theme='dark'] .staff-tasks-page) { --task-ink: var(--ink); --task-muted: var(--ink-soft); --task-line: var(--line); }
:global(html[data-theme='dark'] .staff-tasks-page .task-summary-card) ,
:global(html[data-theme='dark'] .staff-tasks-page .task-guidance) ,
:global(html[data-theme='dark'] .staff-tasks-page .task-form-panel) ,
:global(html[data-theme='dark'] .staff-tasks-page .task-filter-panel) ,
:global(html[data-theme='dark'] .staff-tasks-page .task-list-panel) { border-color: var(--line) !important; }
:global(html[data-theme='dark'] .staff-tasks-page .task-summary-card) { background: var(--surface) !important; }
:global(html[data-theme='dark'] .staff-tasks-page .task-guidance) { background: var(--surface-subtle) !important; color: var(--ink-soft) !important; }
:global(html[data-theme='dark'] .staff-tasks-page .task-guidance strong) { color: var(--primary); }
:global(html[data-theme='dark'] .staff-tasks-page .task-status-assigned) ,
:global(html[data-theme='dark'] .staff-tasks-page .task-priority-high) { background: var(--primary-soft) !important; color: var(--primary) !important; }
:global(html[data-theme='dark'] .staff-tasks-page .task-status-in_progress) { background: var(--gold-soft) !important; color: var(--gold) !important; }
:global(html[data-theme='dark'] .staff-tasks-page .task-status-completed) { background: var(--green-soft) !important; color: var(--green) !important; }
:global(html[data-theme='dark'] .staff-tasks-page .task-status-cancelled) ,
:global(html[data-theme='dark'] .staff-tasks-page .task-priority-low) ,
:global(html[data-theme='dark'] .staff-tasks-page .task-priority-normal) { background: var(--surface-strong) !important; color: var(--ink-soft) !important; }
:global(html[data-theme='dark'] .staff-tasks-page .task-priority-urgent) { background: var(--red-soft) !important; color: var(--red) !important; }
:global(html[data-theme='dark'] .staff-tasks-page .task-description) ,
:global(html[data-theme='dark'] .staff-tasks-page .task-category) ,
:global(html[data-theme='dark'] .staff-tasks-page .task-progress-heading) ,
:global(html[data-theme='dark'] .staff-tasks-page .task-progress-editor) ,
:global(html[data-theme='dark'] .staff-tasks-page .field) { color: var(--ink-soft) !important; }
:global(html[data-theme='dark'] .staff-tasks-page .task-side) ,
:global(html[data-theme='dark'] .staff-tasks-page .task-item) ,
:global(html[data-theme='dark'] .staff-tasks-page .task-form-actions) ,
:global(html[data-theme='dark'] .staff-tasks-page .task-filter-actions) { border-color: var(--line) !important; }
:global(html[data-theme='dark'] .staff-tasks-page .field input) ,
:global(html[data-theme='dark'] .staff-tasks-page .field select) ,
:global(html[data-theme='dark'] .staff-tasks-page .field textarea) { background: var(--surface) !important; border-color: var(--line) !important; color: var(--ink) !important; }
:global(html[data-theme='dark'] .staff-tasks-page progress) ,
:global(html[data-theme='dark'] .staff-tasks-page progress::-webkit-progress-bar) { background: var(--surface-strong); }
:global(html[data-theme='dark'] .staff-tasks-page progress::-webkit-progress-value) ,
:global(html[data-theme='dark'] .staff-tasks-page progress::-moz-progress-bar) { background: var(--primary); }
.staff-tasks-page .hero-card, .staff-tasks-page .panel { background: var(--surface); border-color: var(--line); border-radius: 14px; box-shadow: none; }
.staff-tasks-page .staff-task-hero { padding: clamp(1rem, 2.5vw, 1.5rem); }
.staff-tasks-page .task-summary-card { color: var(--ink); background: var(--surface) !important; border: 1px solid var(--line); border-radius: 12px; }
.staff-tasks-page .task-summary-active, .staff-tasks-page .task-summary-overdue, .staff-tasks-page .task-summary-completed { border-top-width: 3px; }
.staff-tasks-page .task-summary-active { border-top-color: var(--primary); }
.staff-tasks-page .task-summary-overdue { border-top-color: var(--red); }
.staff-tasks-page .task-summary-completed { border-top-color: var(--green); }
.staff-tasks-page .task-summary-card span, .staff-tasks-page .task-summary-card small { color: var(--ink-soft); }
.staff-tasks-page .task-summary-card strong { color: var(--ink); }
.staff-tasks-page .field input, .staff-tasks-page .field select, .staff-tasks-page .field textarea { min-height: 44px; color: var(--ink); background: var(--surface); border-color: var(--line-strong); border-radius: 10px; }
.staff-tasks-page .task-item { border-color: var(--line); }
.staff-tasks-page button:focus-visible, .staff-tasks-page input:focus-visible, .staff-tasks-page select:focus-visible, .staff-tasks-page textarea:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
@media (max-width: 680px) { .staff-tasks-page .staff-task-hero-actions, .staff-tasks-page .task-filter-actions, .staff-tasks-page .task-form-actions { width: 100%; } .staff-tasks-page .staff-task-hero-actions > *, .staff-tasks-page .task-filter-actions > *, .staff-tasks-page .task-form-actions > * { flex: 1; } }
:global(html[data-theme='dark'] .staff-tasks-page .task-summary-overdue strong),
:global(html[data-theme='dark'] .staff-tasks-page .task-overdue) { color: var(--red) !important; }
:global(html[data-theme='dark'] .staff-tasks-page .task-summary-completed strong) { color: var(--green) !important; }
:global(html[data-theme='dark'] .staff-tasks-page .secondary-button) { background: var(--surface); border-color: var(--line-strong); color: var(--ink); }
.modal-backdrop { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 1rem; background: rgb(15 23 42 / .56); backdrop-filter: blur(3px); }
.modal-card { width: min(700px, 100%); max-height: 90dvh; overflow-y: auto; padding: 1.35rem; border: 1px solid var(--line); border-radius: 16px; background: var(--surface); box-shadow: 0 24px 80px rgb(15 23 42 / .25); }
.modal-close { min-width: 40px; min-height: 40px; border-radius: 10px; border: 1px solid var(--line); background: var(--surface); color: var(--ink-soft); font-size: 1.4rem; line-height: 1; cursor: pointer; }
</style>
