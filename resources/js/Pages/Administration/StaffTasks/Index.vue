<script setup lang="ts">
import { watchPostEffect, onUnmounted } from 'vue';
import AppButton from '@/components/AppButton.vue';
import MetricSummaryCard from '@/components/MetricSummaryCard.vue';
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

let dialogTrigger: HTMLElement | null = null;
let activeDialog: HTMLElement | null = null;
const dialogControls = () => Array.from(activeDialog?.querySelectorAll<HTMLElement>('button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), a[href], summary') ?? []).filter(element => element.getClientRects().length > 0);
const trapDialogFocus = (event: KeyboardEvent) => {
    if (!activeDialog || event.key !== 'Tab') return;
    const controls = dialogControls();
    const first = controls[0];
    const last = controls.at(-1);
    if (first && last && (!activeDialog.contains(document.activeElement) || (event.shiftKey ? document.activeElement === first : document.activeElement === last))) {
        event.preventDefault();
        (event.shiftKey ? last : first).focus();
    }
};
watchPostEffect(() => {
    const open = showForm.value;
    if (open && !activeDialog) {
        dialogTrigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        activeDialog = Array.from(document.querySelectorAll<HTMLElement>('[role="dialog"]')).at(-1) ?? null;
        dialogControls()[0]?.focus();
        document.addEventListener('keydown', trapDialogFocus);
    } else if (!open && activeDialog) {
        document.removeEventListener('keydown', trapDialogFocus);
        activeDialog = null;
        if (dialogTrigger?.isConnected) dialogTrigger.focus();
    }
});
onUnmounted(() => document.removeEventListener('keydown', trapDialogFocus));

</script>

<template>
    <AppLayout title="Tugas Staf">
        <Head title="Tugas Staf" />
        <div class="view space-y-6 staff-tasks-page">
            <section class="page-heading staff-task-hero">
                <div>
                    <p class="eyebrow">Tata usaha</p>
                    <h1>Tugas staf</h1>
                </div>
                <div v-if="can_manage" class="staff-task-hero-actions"><AppButton variant="primary" icon="plus" type="button" class="primary-button" @click="openCreate">Tambah tugas</AppButton></div>
            </section>

            <section class="ss-summary-grid ss-summary-grid--3" aria-label="Ringkasan tugas">
                <MetricSummaryCard
                    label="Tugas aktif"
                    :value="summary.active"
                    note="Menunggu atau sedang dikerjakan"
                    tone="blue"
                    icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                />
                <MetricSummaryCard
                    label="Melewati tenggat"
                    :value="summary.overdue"
                    note="Perlu ditindaklanjuti"
                    tone="rose"
                    icon="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
                <MetricSummaryCard
                    label="Selesai"
                    :value="summary.completed"
                    note="Tugas yang sudah ditutup"
                    tone="green"
                    icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </section>
            <div v-if="showForm && can_manage" role="dialog" aria-modal="true" aria-labelledby="staff-task-modal-title" class="modal-backdrop" @click.self="showForm = false" @keydown.esc="showForm = false">
                <section id="staff-task-form" class="modal-card task-form-panel">
                    <div class="task-panel-heading"><div><p class="eyebrow">{{ editingId ? 'Perbarui pekerjaan' : 'Pekerjaan baru' }}</p><h2 id="staff-task-modal-title">{{ editingId ? 'Ubah tugas' : 'Tugas baru' }}</h2><p>Isi informasi inti agar penanggung jawab memahami hasil yang diharapkan.</p></div><AppButton variant="ghost" type="button" class="modal-close" aria-label="Tutup formulir tugas" @click="showForm = false">×</AppButton></div>
                <ul v-if="Object.keys(form.errors).length" class="task-error-list rounded-lg p-3 text-sm" role="alert"><li v-for="(error, field) in form.errors" :key="field">{{ error }}</li></ul>
                <form class="task-form-grid" @submit.prevent="save">
                    <label class="field md:col-span-2">Judul<input v-model="form.title" required maxlength="180" /><small v-if="form.errors.title">{{ form.errors.title }}</small></label>
                    <label class="field md:col-span-2">Uraian<textarea v-model="form.description" rows="3" maxlength="5000" /></label>
                    <label class="field">Kategori<input v-model="form.category" maxlength="60" placeholder="Contoh: administrasi" /></label>
                    <label class="field">Prioritas<select v-model="form.priority"><option value="low">Rendah</option><option value="normal">Normal</option><option value="high">Tinggi</option><option value="urgent">Mendesak</option></select></label>
                    <label class="field">Penanggung jawab<select v-model="form.assignee_id"><option value="">Belum ditentukan</option><option v-for="person in assignees" :key="person.id" :value="String(person.id)">{{ person.name }}</option></select><small v-if="form.errors.assignee_id">{{ form.errors.assignee_id }}</small></label>
                    <label class="field">Tenggat<input v-model="form.due_at" type="datetime-local" /></label>
                    <label class="field md:col-span-2">Lampiran<input type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" @change="selectAttachment" /><small v-if="form.errors.attachment">{{ form.errors.attachment }}</small><small>Maksimal 10 MB; file hanya dapat diunduh oleh admin/operator atau penanggung jawab.</small></label>
                    <label v-if="editingId" class="field">Status<select v-model="form.status"><option value="assigned">Ditugaskan</option><option value="in_progress">Berjalan</option><option value="completed">Selesai</option><option value="cancelled">Dibatalkan</option></select></label>
                    <div class="task-form-actions"><AppButton variant="secondary" type="button" class="secondary-button" @click="showForm = false">Batal</AppButton><AppButton variant="primary" :loading="form.processing" type="submit" class="primary-button" :disabled="form.processing">Simpan tugas</AppButton></div>
                </form>
                </section>
            </div>

            <section class="panel task-filter-panel">
                <div class="task-panel-heading compact"><h2>Daftar tugas</h2></div>
                <form class="task-filter-grid" @submit.prevent="applyFilters">
                    <label class="field">Cari tugas<input v-model="filters.search" class="search-control" type="search" maxlength="180" placeholder="Judul atau kategori" /></label>
                    <label class="field">Status<select v-model="filters.status"><option value="">Semua status</option><option value="assigned">Ditugaskan</option><option value="in_progress">Berjalan</option><option value="completed">Selesai</option><option value="cancelled">Dibatalkan</option></select></label>
                    <details class="task-advanced-filters"><summary>Filter lanjutan</summary><label class="field">Prioritas<select v-model="filters.priority"><option value="">Semua prioritas</option><option value="low">Rendah</option><option value="normal">Normal</option><option value="high">Tinggi</option><option value="urgent">Mendesak</option></select></label>
                    <label v-if="can_manage" class="field">Penanggung jawab<select v-model="filters.assignee_id"><option value="">Semua staf</option><option v-for="person in assignees" :key="person.id" :value="String(person.id)">{{ person.name }}</option></select></label></details>
                    <div class="task-filter-actions"><AppButton variant="primary" type="submit" class="primary-button">Terapkan filter</AppButton><AppButton variant="secondary" type="button" class="secondary-button" @click="resetFilters">Reset</AppButton></div>
                </form>
            </section>
            <section class="panel task-list-panel">
                <div v-if="!tasks.length" class="flex min-h-64 flex-col items-center justify-center px-6 py-12 text-center">
                    <div class="task-empty-icon mb-4 grid h-12 w-12 place-items-center rounded-xl" aria-hidden="true">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 4.75h8A2.25 2.25 0 0 1 18.25 7v12A2.25 2.25 0 0 1 16 21.25H8A2.25 2.25 0 0 1 5.75 19V7A2.25 2.25 0 0 1 8 4.75Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75h6A1.25 1.25 0 0 1 16.25 5v1.5h-8.5V5A1.25 1.25 0 0 1 9 3.75ZM9 11h6m-6 4h6" />
                        </svg>
                    </div>
                    <strong class="text-base font-semibold text-[var(--ink)]">{{ filters.search || filters.status || filters.priority || filters.assignee_id ? 'Tidak ada tugas sesuai filter' : 'Belum ada tugas' }}</strong>
                    <p class="mt-1 max-w-md text-sm leading-6 text-[var(--ink-soft)]">
                        {{ can_manage ? 'Buat tugas untuk membagikan pekerjaan dan tenggat kepada staf sekolah.' : 'Tugas yang diberikan kepada Anda akan muncul di sini.' }}
                    </p>
                    <AppButton variant="primary" v-if="can_manage" type="button" class="primary-button mt-5" @click="openCreate">Buat tugas pertama</AppButton>
                </div>
                <div v-else class="task-list">
                    <article v-for="task in tasks" :key="task.id" class="task-item">
                        <div class="task-main">
                            <div class="task-heading"><div class="task-title-block"><span class="task-category">{{ task.category || 'Umum' }}</span><h2>{{ task.title }}</h2></div><div class="task-badges"><span class="task-status-pill" :class="statusClass(task.status)">{{ statusLabel(task.status) }}</span><span v-if="task.priority !== 'normal'" class="task-priority-pill" :class="priorityClass(task.priority)">{{ priorityLabel(task.priority) }}</span></div></div>
                            <details class="task-details"><summary>Detail tugas</summary><p v-if="task.description" class="task-description">{{ task.description }}</p>
                            <a v-if="task.attachment_name" class="task-attachment-link block max-w-full break-all text-sm font-medium hover:underline" :href="`/administrasi/tugas/${task.id}/lampiran`">Unduh lampiran: {{ task.attachment_name }}</a>
                            <small class="task-creator">Dibuat oleh {{ task.creator?.name || 'staf' }}</small></details>
                            <div class="task-meta"><span><b>Penanggung jawab</b>{{ task.assignee?.name || 'Belum ditentukan' }}</span><span><b>Tenggat</b>{{ dateLabel(task.due_at) }}</span></div>
                            <p v-if="overdue(task)" class="task-overdue">Melewati tenggat</p>
                            <details v-if="task.activities?.length" class="task-history"><summary>Riwayat ({{ task.activities_count }}) · 5 terbaru</summary><ul><li v-for="activity in task.activities" :key="activity.id">{{ activity.actor?.name || 'Pengguna' }} · {{ ({ created: 'Tugas dibuat', updated: 'Tugas diubah', progress_updated: 'Progres diperbarui' } as Record<string, string>)[activity.action] ?? activity.action }} · {{ dateLabel(activity.created_at) }}</li></ul></details>
                        </div>
                        <div class="task-side">
                            <div v-if="can_manage || task.assignee?.id !== current_user_id || task.status === 'cancelled'" class="task-progress"><div class="task-progress-heading"><span>Progres</span><strong>{{ task.progress }}%</strong></div><progress max="100" :value="task.progress" :aria-label="`Progres tugas ${task.title}`" /></div>
                            <label v-if="!can_manage && task.assignee?.id === current_user_id && task.status !== 'cancelled'" class="task-progress-editor"><span class="task-progress-heading"><span>Progres</span><strong>{{ task.progress }}%</strong></span><input type="range" min="0" max="100" :aria-label="`Ubah progres tugas ${task.title}`" :disabled="savingProgress[task.id]" step="10" :value="task.progress" @change="updateProgress(task, $event)" /><small>{{ savingProgress[task.id] ? 'Menyimpan…' : 'Geser untuk memperbarui' }}</small></label>
                            <p v-if="progressErrors[task.id]" class="task-progress-error text-sm" role="alert">{{ progressErrors[task.id] }}</p>
                            <div v-if="can_manage" class="task-actions"><RowActionButton action="edit" label="Ubah" :aria-label="`Ubah tugas ${task.title}`" @click="openEdit(task)" /><RowActionButton action="delete" label="Hapus" :aria-label="`Hapus tugas ${task.title}`" @click="remove(task)" /></div>
                        </div>
                    </article>
                </div>
            </section>
            <nav v-if="pagination.total" class="flex flex-wrap items-center justify-between gap-3 text-sm text-[var(--ink-soft)]" aria-label="Halaman tugas">
                <p>{{ pagination.from }}–{{ pagination.to }} dari {{ pagination.total }} tugas · Halaman {{ pagination.current_page }} / {{ pagination.last_page }}</p>
                <div class="flex gap-2"><AppButton variant="secondary" type="button" class="secondary-button" :disabled="!pagination.prev_page_url" @click="pagination.prev_page_url && router.get(pagination.prev_page_url, {}, { preserveState: true, preserveScroll: true })">Sebelumnya</AppButton><AppButton variant="secondary" type="button" class="secondary-button" :disabled="!pagination.next_page_url" @click="pagination.next_page_url && router.get(pagination.next_page_url, {}, { preserveState: true, preserveScroll: true })">Berikutnya</AppButton></div>
            </nav>
        </div>
    </AppLayout>
</template>

<style scoped>
.staff-tasks-page { --task-ink: var(--ink); --task-muted: var(--ink-soft); --task-line: var(--line); }
.staff-task-hero { margin-bottom: 0; }
.staff-task-hero .eyebrow { margin: 0; color: var(--primary); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.staff-task-hero h1 { margin: .25rem 0 0; color: var(--ink); font-size: var(--type-3xl); font-weight: var(--weight-bold); letter-spacing: -.035em; }
.staff-task-hero-actions { display: flex; align-items: center; gap: .6rem; }
.task-error-list { background: var(--red-soft); color: var(--red); border: 1px solid color-mix(in srgb, var(--red) 28%, var(--line)); }
.task-empty-icon { background: var(--primary-soft); color: var(--primary); }
.task-attachment-link { color: var(--primary); }
.task-progress-error { color: var(--red); }
.task-form-panel, .task-filter-panel { display: grid; gap: 1.25rem; padding: 1.25rem; }
.task-panel-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.task-panel-heading h2 { margin: .15rem 0 .25rem; color: var(--task-ink); font-size: 1.1rem; }
.task-panel-heading p:not(.eyebrow) { margin: 0; color: var(--task-muted); font-size: var(--type-sm); line-height: 1.5; }
.task-panel-heading.compact { margin-bottom: -.15rem; }
.task-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
.task-form-grid .md\:col-span-2 { grid-column: 1 / -1; }
.task-form-actions { display: flex; justify-content: flex-end; gap: .5rem; grid-column: 1 / -1; border-top: 1px solid var(--line); padding-top: 1rem; }
.task-filter-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); align-items: start; gap: .8rem; }
.task-filter-actions { display: flex; align-items: center; justify-content: flex-end; gap: .5rem; grid-column: 1 / -1; border-top: 1px solid var(--line); padding-top: .9rem; }
.task-advanced-filters { grid-column: 1 / -1; }
.task-list-panel { overflow: hidden; padding: 0; }
.task-list { display: grid; }
.task-item { display: grid; grid-template-columns: minmax(0, 1fr) minmax(170px, 200px); gap: 1.25rem; padding: 1rem 1.35rem; border-bottom: 1px solid var(--line); }
.task-item:last-child { border-bottom: 0; }
.task-main, .task-side { min-width: 0; }
.task-main { display: grid; align-content: start; gap: .45rem; }
.task-heading { order: 0; display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.task-meta { order: 1; }
.task-overdue { order: 2; }
.task-details { order: 3; }
.task-history { order: 4; }
.task-title-block { min-width: 0; }
.task-category { display: block; margin-bottom: .25rem; color: var(--task-muted); font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .04em; text-transform: uppercase; }
.task-title-block h2 { margin: 0; color: var(--task-ink); font-size: var(--type-md); line-height: 1.35; overflow-wrap: anywhere; }
.task-badges { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: .4rem; flex: 0 0 auto; }
.task-status-pill, .task-priority-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: .3rem .6rem; font-size: var(--type-meta); font-weight: var(--weight-bold); white-space: nowrap; }
.task-status-assigned { background: var(--primary-soft); color: var(--primary-strong); }
.task-status-in_progress { background: var(--gold-soft); color: var(--gold); }
.task-status-completed { background: var(--green-soft); color: var(--green); }
.task-status-cancelled { background: var(--surface-strong); color: var(--ink-soft); }
.task-priority-low, .task-priority-normal { background: var(--surface-strong); color: var(--ink-soft); }
.task-priority-high { background: var(--gold-soft); color: var(--gold); }
.task-priority-urgent { background: var(--red-soft); color: var(--red); }
.task-description { margin: 0; color: var(--ink-soft); font-size: var(--type-sm); line-height: 1.6; white-space: pre-wrap; overflow-wrap: anywhere; }
.task-meta { display: flex; flex-wrap: wrap; gap: .8rem 1.5rem; color: var(--task-muted); font-size: var(--type-meta); }
.task-meta span { display: inline-flex; flex-wrap: wrap; gap: .3rem; min-width: 0; }
.task-meta b { color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-semibold); }
.task-meta b::after { content: ':'; }
.task-overdue { margin: 0; color: var(--red); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.task-history { color: var(--task-muted); font-size: var(--type-meta); }
.task-history summary { cursor: pointer; font-weight: var(--weight-bold); }
.task-history ul { display: grid; gap: .3rem; margin: .5rem 0 0; padding-left: 1rem; line-height: 1.4; }
.task-side { display: grid; align-content: center; gap: .6rem; border-left: 1px solid var(--line); padding-left: 1.25rem; }
.task-details summary, .task-history summary { width: fit-content; padding: .2rem 0; color: var(--primary); cursor: pointer; font-size: var(--type-meta); font-weight: var(--weight-semibold); }
.task-details summary:focus-visible, .task-history summary:focus-visible { outline: 2px solid var(--primary); outline-offset: 3px; }
.task-progress { display: grid; gap: .45rem; }
.task-progress-heading { display: flex; align-items: center; justify-content: space-between; color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.task-progress-heading strong { color: var(--task-ink); font-size: var(--type-md); }
.task-progress progress { width: 100%; height: .5rem; }
.task-progress-editor { display: grid; gap: .35rem; color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.task-progress-editor input { width: 100%; accent-color: var(--primary); }
.task-progress-editor small { color: var(--task-muted); font-size: var(--type-meta); font-weight: var(--weight-regular); }
.task-actions { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: .4rem; }
.task-creator { color: var(--task-muted); font-size: var(--type-meta); text-align: right; }
.field { display: grid; min-width: 0; gap: .4rem; color: var(--ink-soft); font-size: var(--type-sm); font-weight: var(--weight-semibold); }
.field input, .field select, .field textarea { width: 100%; min-width: 0; min-height: 44px; border: 1px solid var(--line-strong); border-radius: 10px; padding: .6rem .75rem; background: var(--surface); color: var(--ink); font-weight: var(--weight-regular); }
.field small { font-weight: var(--weight-regular); line-height: 1.5; }
progress { overflow: hidden; border-radius: 999px; background: var(--surface-strong); }
progress::-webkit-progress-bar { background: var(--surface-strong); }
progress::-webkit-progress-value { background: var(--primary); border-radius: 999px; }
progress::-moz-progress-bar { background: var(--primary); }
.staff-tasks-page .panel { background: var(--surface); border-color: var(--line); border-radius: var(--card-radius); box-shadow: none; }
.staff-tasks-page button:focus-visible, .staff-tasks-page input:focus-visible, .staff-tasks-page select:focus-visible, .staff-tasks-page textarea:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
.modal-backdrop { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 1rem; background: rgb(15 23 42 / .56); backdrop-filter: blur(3px); }
.modal-card { width: min(700px, 100%); max-height: 90dvh; overflow-y: auto; padding: 1.35rem; border: 1px solid var(--line); border-radius: 16px; background: var(--surface); box-shadow: 0 24px 80px rgb(15 23 42 / .25); }
.task-advanced-filters summary, .task-details summary { cursor: pointer; color: var(--ink-soft); font-size: var(--type-sm); padding: .5rem 0; }
.task-advanced-filters .field { margin-top: .5rem; }
@media (max-width: 900px) {
    .task-item { grid-template-columns: minmax(0, 1fr) 180px; gap: 1rem; }
    .task-side { padding-left: 1rem; }
    .task-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 680px) {
    .task-form-grid, .task-filter-grid { grid-template-columns: 1fr; }
    .task-form-grid .md\:col-span-2, .task-filter-actions, .task-form-actions { grid-column: auto; }
    .task-panel-heading, .task-heading { align-items: stretch; flex-direction: column; }
    .task-panel-heading .secondary-button { align-self: flex-start; }
    .task-item { grid-template-columns: 1fr; gap: 1rem; padding: 1.1rem; }
    .task-side { border-top: 1px solid var(--line); border-left: 0; padding-top: 1rem; padding-left: 0; }
    .task-badges { justify-content: flex-start; }
    .task-actions, .task-creator { justify-content: flex-start; text-align: left; }
    .staff-tasks-page .staff-task-hero-actions, .staff-tasks-page .task-filter-actions, .staff-tasks-page .task-form-actions { width: 100%; }
    .staff-tasks-page .staff-task-hero-actions > *, .staff-tasks-page .task-filter-actions > *, .staff-tasks-page .task-form-actions > * { flex: 1; }
}
</style>
