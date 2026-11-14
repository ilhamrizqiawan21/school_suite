<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import RowActionButton from '@/components/RowActionButton.vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface ClassItem {
    id: number;
    name: string;
}

interface StudentItem {
    id: number;
    name: string;
    nis: string;
}

interface TermItem {
    id: number;
    name: string;
}

interface NoteItem {
    id: number;
    student_id: number;
    title: string;
    content: string;
    category: 'academic' | 'behavior' | 'counseling' | 'general';
    access_level: 'public' | 'internal' | 'confidential';
    created_at: string;
    author: { name: string };
}

const props = defineProps<{
    classes: ClassItem[];
    students: StudentItem[];
    notes: NoteItem[];
    active_term?: TermItem | null;
    filters: {
        class_group_id?: string;
        student_id?: string;
    };
}>();

const selectedClass = ref(props.filters.class_group_id || '');
const selectedStudent = ref(props.filters.student_id || '');
const showAddModal = ref(false);

const applyFilters = () => {
    router.get(
        '/wali-kelas/catatan',
        {
            class_group_id: selectedClass.value || undefined,
            student_id: selectedStudent.value || undefined,
        },
        { preserveState: true }
    );
};

const form = useForm({
    student_id: '',
    term_id: props.active_term?.id ? String(props.active_term.id) : '',
    class_group_id: selectedClass.value || '',
    title: '',
    content: '',
    category: 'general',
    access_level: 'internal',
});

const submitAdd = () => {
    form.class_group_id = selectedClass.value;
    form.post('/wali-kelas/catatan', {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset('title', 'content');
        },
    });
};

const deleteNote = (id: number) => {
    if (confirm('Hapus catatan observasi ini?')) {
        router.delete(`/wali-kelas/catatan/${id}`);
    }
};
</script>

<template>
    <AppLayout title="Catatan Observasi Siswa">
        <Head title="Catatan Observasi — School Suite" />

        <div class="space-y-6 observations-page">
            <!-- Header bar -->
            <div class="observations-header flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Catatan Observasi & Perkembangan Siswa</h1>
                    <p class="text-sm text-slate-600 mt-1">
                        Dokumentasi perkembangan karakter, bimbingan, dan rekam catatan berkas rapor.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <AppButton variant="primary" icon="plus"
                        type="button"
                        @click="showAddModal = true"
                        class="inline-flex items-center transition"
                    >

                        Tambah Catatan
                    </AppButton>
                </div>
            </div>

            <!-- Filters -->
            <div class="observations-filters bg-[var(--surface)] rounded-xl border border-[var(--line)] p-4 shadow-xs flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <select
                        v-model="selectedClass"
                        @change="applyFilters"
                        class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-[var(--surface)] focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Semua Rombel</option>
                        <option v-for="c in classes" :key="c.id" :value="String(c.id)">
                            Kelas {{ c.name }}
                        </option>
                    </select>
                </div>
                <div class="flex-1">
                    <select
                        v-model="selectedStudent"
                        @change="applyFilters"
                        class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-[var(--surface)] focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Pilih Siswa Tertentu (Semua Siswa)</option>
                        <option v-for="s in students" :key="s.id" :value="String(s.id)">
                            {{ s.name }} (NIS: {{ s.nis }})
                        </option>
                    </select>
                </div>
            </div>

            <!-- Notes List -->
            <div class="space-y-4">
                <div
                    v-for="note in notes"
                    :key="note.id"
                    class="observation-note bg-[var(--surface)] rounded-xl border border-[var(--line)] p-5 shadow-xs space-y-3"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span
                                :class="{
                                    'bg-indigo-50 text-indigo-700': note.category === 'academic',
                                    'bg-amber-50 text-amber-700': note.category === 'behavior',
                                    'bg-purple-50 text-purple-700': note.category === 'counseling',
                                    'bg-slate-100 text-slate-700': note.category === 'general',
                                }"
                                class="px-2.5 py-0.5 text-meta font-semibold rounded-md uppercase"
                            >
                                {{ note.category }}
                            </span>

                            <span
                                :class="{
                                    'bg-emerald-50 text-emerald-700 border border-emerald-200': note.access_level === 'public',
                                    'bg-slate-100 text-slate-700': note.access_level === 'internal',
                                    'bg-rose-50 text-rose-700 border border-rose-200 font-bold': note.access_level === 'confidential',
                                }"
                                class="px-2 py-0.5 text-meta rounded-sm"
                            >
                                {{ note.access_level === 'confidential' ? '🔒 RAHASIA (BK/Wali)' : note.access_level === 'public' ? 'PUBLIK (Rapor)' : 'INTERNAL' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3 text-meta text-slate-600">
                            <span>Oleh: <strong class="text-slate-600">{{ note.author.name }}</strong></span>
                            <RowActionButton action="delete" label="Hapus" :aria-label="`Hapus catatan ${note.title}`" @click="deleteNote(note.id)" />
                        </div>
                    </div>

                    <h3 class="font-bold text-slate-900 text-base">{{ note.title }}</h3>
                    <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ note.content }}</p>
                </div>

                <div v-if="notes.length === 0" class="bg-[var(--surface)] rounded-xl border border-dashed border-slate-300 p-12 text-center text-slate-600">
                    Belum ada catatan observasi untuk filter ini. Klik "Tambah Catatan" untuk memulai.
                </div>
            </div>
        </div>

        <!-- Modal Tambah Catatan -->
        <div v-if="showAddModal" class="observation-modal fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="observation-dialog bg-[var(--surface)] rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4" role="dialog" aria-modal="true" aria-labelledby="observation-modal-title">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 id="observation-modal-title" class="text-lg font-bold text-slate-900">Tambah Catatan Observasi</h3>
                    <button type="button" @click="showAddModal = false" class="text-slate-600 hover:text-slate-600">✕</button>
                </div>

                <form @submit.prevent="submitAdd" class="space-y-3">
                    <div>
                        <label class="block text-meta font-semibold text-slate-700 mb-1">Peserta Didik *</label>
                        <select v-model="form.student_id" required class="w-full px-3 py-2 text-sm border rounded-lg bg-[var(--surface)]">
                            <option value="">Pilih siswa...</option>
                            <option v-for="s in students" :key="s.id" :value="String(s.id)">{{ s.name }} (NIS: {{ s.nis }})</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-meta font-semibold text-slate-700 mb-1">Kategori *</label>
                            <select v-model="form.category" class="w-full px-3 py-2 text-sm border rounded-lg bg-[var(--surface)]">
                                <option value="general">Catatan Umum</option>
                                <option value="academic">Akademik</option>
                                <option value="behavior">Perilaku / Karakter</option>
                                <option value="counseling">Konseling / BK</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-meta font-semibold text-slate-700 mb-1">Tingkat Akses *</label>
                            <select v-model="form.access_level" class="w-full px-3 py-2 text-sm border rounded-lg bg-[var(--surface)]">
                                <option value="internal">Internal (Dewan Guru)</option>
                                <option value="public">Publik (Tercetak di Rapor / Wali Murid)</option>
                                <option value="confidential">🔒 Rahasia (Wali Kelas, BK, & Pimpinan Saja)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-meta font-semibold text-slate-700 mb-1">Judul Catatan *</label>
                        <input v-model="form.title" type="text" required placeholder="contoh: Pembinaan Kedisiplinan Seragam" class="w-full px-3 py-2 text-sm border rounded-lg" />
                    </div>

                    <div>
                        <label class="block text-meta font-semibold text-slate-700 mb-1">Isi Observasi / Catatan *</label>
                        <textarea v-model="form.content" rows="4" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <AppButton variant="secondary" type="button" @click="showAddModal = false" >
                            Batal
                        </AppButton>
                        <AppButton variant="primary" :loading="form.processing" type="submit" :disabled="form.processing" >
                            Simpan Catatan
                        </AppButton>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.observations-header { padding: clamp(1.25rem, 2.5vw, 2rem); border: 1px solid var(--line); border-radius: 1.25rem; background: linear-gradient(135deg, var(--surface), var(--surface-subtle)); }
.observations-header h1, .observation-note h3 { color: var(--ink); }
.observations-header p, .observation-note p { color: var(--ink-soft); line-height: 1.65; }
.observations-header button, .observation-dialog button[type='submit'] { min-height: 44px; background: var(--primary-action); }
.observations-header button:hover, .observation-dialog button[type='submit']:hover { background: var(--primary-action-strong); }
.observations-filters, .observation-note, .observation-dialog { border-color: var(--line); background: var(--surface); }
.observations-filters select, .observation-dialog :is(input, select, textarea) { min-height: 44px; border-color: var(--line-strong); color: var(--ink); background: var(--surface); }
.observation-dialog { max-height: min(90vh, 900px); overflow-y: auto; }
.observation-dialog h3 { color: var(--ink); }
.observation-dialog label { color: var(--ink-soft); }
.observation-dialog button[type='button'] { min-height: 44px; }
.observations-page :is(button, select, input, textarea):focus-visible,
.observation-dialog :is(button, select, input, textarea):focus-visible { outline: 3px solid color-mix(in srgb, var(--primary) 42%, transparent); outline-offset: 2px; }
@media (max-width: 640px) { .observations-header button { width: 100%; justify-content: center; } .observation-dialog .grid { grid-template-columns: 1fr; } }
</style>

