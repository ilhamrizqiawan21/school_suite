<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import RowActionButton from '@/components/RowActionButton.vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface Assignee { id: number; name: string }
interface Activity { id: number; action: string; changes?: Record<string, unknown> | null; created_at: string; actor?: { name: string } | null }
interface StaffTask {
    id: number; title: string; description?: string | null; category?: string | null; priority: string;
    attachment_name?: string | null;
    status: string; progress: number; due_at?: string | null; completed_at?: string | null;
    creator?: { name: string } | null; assignee?: { id: number; name: string } | null; activities: Activity[];
}

const props = defineProps<{ tasks: StaffTask[]; assignees: Assignee[]; can_manage: boolean; current_user_id: number }>();
const showForm = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({ title: '', description: '', category: '', priority: 'normal', due_at: '', assignee_id: '', status: 'assigned', attachment: null as File | null });

const openCreate = () => { editingId.value = null; form.reset(); form.priority = 'normal'; form.status = 'assigned'; showForm.value = true; };
const openEdit = (task: StaffTask) => {
    editingId.value = task.id;
    form.attachment = null;
    form.title = task.title;
    form.description = task.description ?? '';
    form.category = task.category ?? '';
    form.priority = task.priority;
    form.due_at = task.due_at ? task.due_at.slice(0, 16) : '';
    form.assignee_id = task.assignee?.id ? String(task.assignee.id) : '';
    form.status = task.status;
    showForm.value = true;
};
const save = () => {
    if (editingId.value) {
        form.transform((data) => ({ ...data, _method: 'patch' })).post(`/administrasi/tugas/${editingId.value}`, { forceFormData: true, preserveScroll: true, onSuccess: () => { showForm.value = false; } });
    } else {
        form.post('/administrasi/tugas', { forceFormData: true, preserveScroll: true, onSuccess: () => { form.reset(); showForm.value = false; } });
    }
};
const selectAttachment = (event: Event) => { form.attachment = (event.target as HTMLInputElement).files?.[0] ?? null; };
const updateProgress = (task: StaffTask, event: Event) => {
    const value = Number((event.target as HTMLInputElement).value);
    const status = value === 100 ? 'completed' : value > 0 ? 'in_progress' : 'assigned';
    router.patch(`/administrasi/tugas/${task.id}/progres`, { progress: value, status }, { preserveScroll: true });
};
const remove = (task: StaffTask) => {
    if (window.confirm(`Hapus tugas “${task.title}”? Riwayat tugas juga akan dihapus.`)) {
        router.delete(`/administrasi/tugas/${task.id}`, { preserveScroll: true });
    }
};
const statusLabel = (status: string) => ({ assigned: 'Ditugaskan', in_progress: 'Berjalan', completed: 'Selesai', cancelled: 'Dibatalkan' }[status] ?? status);
const priorityLabel = (priority: string) => ({ low: 'Rendah', normal: 'Normal', high: 'Tinggi', urgent: 'Mendesak' }[priority] ?? priority);
const dateLabel = (value?: string | null) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(value)) : 'Tanpa tenggat';
</script>

<template>
    <AppLayout title="Tugas Staf">
        <Head title="Tugas Staf" />
        <main class="view space-y-6">
            <section class="hero-card">
                <div>
                    <p class="eyebrow">Tata usaha</p>
                    <h1>Tugas staf</h1>
                    <p>Pantau penanggung jawab, tenggat, progres, dan riwayat tugas sekolah.</p>
                </div>
                <button v-if="can_manage" class="primary-button" @click="openCreate">Tambah tugas</button>
            </section>

            <section v-if="showForm && can_manage" class="panel space-y-4 p-5">
                <div class="flex items-center justify-between gap-4"><h2 class="text-lg font-semibold">{{ editingId ? 'Ubah tugas' : 'Tugas baru' }}</h2><button class="secondary-button" @click="showForm = false">Tutup</button></div>
                <form class="grid gap-4 md:grid-cols-2" @submit.prevent="save">
                    <label class="field md:col-span-2">Judul<input v-model="form.title" required maxlength="180" /><small v-if="form.errors.title">{{ form.errors.title }}</small></label>
                    <label class="field md:col-span-2">Uraian<textarea v-model="form.description" rows="3" maxlength="5000" /></label>
                    <label class="field">Kategori<input v-model="form.category" maxlength="60" placeholder="Contoh: administrasi" /></label>
                    <label class="field">Prioritas<select v-model="form.priority"><option value="low">Rendah</option><option value="normal">Normal</option><option value="high">Tinggi</option><option value="urgent">Mendesak</option></select></label>
                    <label class="field">Penanggung jawab<select v-model="form.assignee_id"><option value="">Belum ditentukan</option><option v-for="person in assignees" :key="person.id" :value="String(person.id)">{{ person.name }}</option></select><small v-if="form.errors.assignee_id">{{ form.errors.assignee_id }}</small></label>
                    <label class="field">Tenggat<input v-model="form.due_at" type="datetime-local" /></label>
                    <label class="field md:col-span-2">Lampiran<input type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" @change="selectAttachment" /><small v-if="form.errors.attachment">{{ form.errors.attachment }}</small><small>Maksimal 10 MB; file hanya dapat diunduh oleh admin/operator atau penanggung jawab.</small></label>
                    <label v-if="editingId" class="field">Status<select v-model="form.status"><option value="assigned">Ditugaskan</option><option value="in_progress">Berjalan</option><option value="completed">Selesai</option><option value="cancelled">Dibatalkan</option></select></label>
                    <div class="flex justify-end gap-2 md:col-span-2"><button type="button" class="secondary-button" @click="showForm = false">Batal</button><button class="primary-button" :disabled="form.processing">Simpan tugas</button></div>
                </form>
            </section>

            <section class="panel overflow-hidden">
                <div v-if="!tasks.length" class="flex min-h-64 flex-col items-center justify-center px-6 py-12 text-center">
                    <div class="mb-4 grid h-12 w-12 place-items-center rounded-xl bg-blue-50 text-blue-700" aria-hidden="true">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 4.75h8A2.25 2.25 0 0 1 18.25 7v12A2.25 2.25 0 0 1 16 21.25H8A2.25 2.25 0 0 1 5.75 19V7A2.25 2.25 0 0 1 8 4.75Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75h6A1.25 1.25 0 0 1 16.25 5v1.5h-8.5V5A1.25 1.25 0 0 1 9 3.75ZM9 11h6m-6 4h6" />
                        </svg>
                    </div>
                    <strong class="text-base font-semibold text-slate-900">Belum ada tugas</strong>
                    <p class="mt-1 max-w-md text-sm leading-6 text-slate-500">
                        {{ can_manage ? 'Buat tugas untuk membagikan pekerjaan dan tenggat kepada staf sekolah.' : 'Tugas yang diberikan kepada Anda akan muncul di sini.' }}
                    </p>
                    <button v-if="can_manage" class="primary-button mt-5" @click="openCreate">Buat tugas pertama</button>
                </div>
                <div v-else class="divide-y divide-slate-200">
                    <article v-for="task in tasks" :key="task.id" class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_220px]">
                        <div class="min-w-0 space-y-3">
                            <div class="flex flex-wrap items-center gap-2"><h2 class="text-base font-semibold text-slate-900">{{ task.title }}</h2><span class="status-pill">{{ statusLabel(task.status) }}</span><span class="text-xs text-slate-500">Prioritas {{ priorityLabel(task.priority) }}</span></div>
                            <p v-if="task.description" class="whitespace-pre-wrap text-sm text-slate-600">{{ task.description }}</p>
                            <a v-if="task.attachment_name" class="inline-flex text-sm font-medium text-blue-700 hover:underline" :href="`/administrasi/tugas/${task.id}/lampiran`">Unduh lampiran: {{ task.attachment_name }}</a>
                            <p class="text-sm text-slate-500">{{ task.category || 'Umum' }} · Penanggung jawab: {{ task.assignee?.name || 'Belum ditentukan' }} · Tenggat: {{ dateLabel(task.due_at) }}</p>
                            <details v-if="task.activities?.length" class="text-xs text-slate-500"><summary class="cursor-pointer font-medium">Riwayat ({{ task.activities.length }})</summary><ul class="mt-2 space-y-1"><li v-for="activity in task.activities.slice().reverse().slice(0, 5)" :key="activity.id">{{ activity.actor?.name || 'Pengguna' }} · {{ activity.action.replaceAll('_', ' ') }} · {{ dateLabel(activity.created_at) }}</li></ul></details>
                        </div>
                        <div class="flex flex-col justify-center gap-3">
                            <label v-if="!can_manage && task.assignee?.id === current_user_id" class="text-sm font-medium text-slate-700">Progres {{ task.progress }}%<input class="mt-2 w-full accent-blue-700" type="range" min="0" max="100" step="10" :value="task.progress" @change="updateProgress(task, $event)" /></label>
                            <div v-if="can_manage" class="flex justify-end gap-2"><RowActionButton action="edit" label="Ubah" :aria-label="`Ubah tugas ${task.title}`" @click="openEdit(task)" /><RowActionButton action="delete" label="Hapus" :aria-label="`Hapus tugas ${task.title}`" @click="remove(task)" /></div>
                            <small class="text-right text-slate-500">Dibuat oleh {{ task.creator?.name || 'staf' }}</small>
                        </div>
                    </article>
                </div>
            </section>
        </main>
    </AppLayout>
</template>
