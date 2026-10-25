<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';
import type { Student } from '../../../types';

interface ClassItem {
    id: number;
    name: string;
    grade_level: number;
}

interface TermItem {
    id: number;
    name: string;
    academic_year_id: number;
}

const props = defineProps<{
    students: {
        data: Student[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    };
    filters: {
        search?: string;
        status?: string;
        class_group_id?: string;
    };
    classes: ClassItem[];
    active_term?: TermItem | null;
}>();

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedClass = ref(props.filters.class_group_id || '');

const showAddModal = ref(false);
const showImportModal = ref(false);

const applyFilters = () => {
    router.get(
        '/akademik/siswa',
        {
            search: search.value || undefined,
            status: selectedStatus.value || undefined,
            class_group_id: selectedClass.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const form = useForm({
    nis: '',
    nisn: '',
    name: '',
    gender: 'L',
    birth_place: '',
    birth_date: '',
    religion: 'Islam',
    parent_phone: '',
    parent_name: '',
    class_group_id: '',
    term_id: props.active_term?.id ? String(props.active_term.id) : '',
});

const submitAdd = () => {
    form.post('/akademik/siswa', {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        },
    });
};

const importForm = useForm({
    csv_file: null as File | null,
    enroll_class_group_id: '',
    enroll_term_id: props.active_term?.id ? String(props.active_term.id) : '',
});

const handleFileUpload = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        importForm.csv_file = target.files[0];
    }
};

const submitImport = () => {
    importForm.post('/akademik/siswa/import', {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};
</script>

<template>
    <AppLayout title="Data Siswa">
        <Head title="Data Siswa — School Suite" />

        <div class="view space-y-6">
            <!-- Header bar -->
            <div class="page-heading compact-heading">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Peserta Didik (Siswa)</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Kelola data induk siswa, penempatan rombel semester aktif, dan impor massal.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="showImportModal = true"
                        class="secondary-button"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Impor CSV
                    </button>
                    <button
                        type="button"
                        @click="showAddModal = true"
                        class="primary-button"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Siswa
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="toolbar panel">
                <label class="table-search">
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="search"
                        placeholder="Cari nama, NIS, atau NISN... (tekan enter)"
                        class="w-full"
                    />
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </label>

                <div class="flex gap-2">
                    <select
                        v-model="selectedClass"
                        @change="applyFilters"
                        class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white"
                    >
                        <option value="">Semua Rombel</option>
                        <option v-for="cls in classes" :key="cls.id" :value="String(cls.id)">
                            Kelas {{ cls.name }}
                        </option>
                    </select>

                    <select
                        v-model="selectedStatus"
                        @change="applyFilters"
                        class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white"
                    >
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="graduated">Lulus</option>
                        <option value="transferred">Pindah</option>
                        <option value="dropped_out">Keluar</option>
                    </select>

                    <button
                        type="button"
                        @click="applyFilters"
                        class="secondary-button"
                    >
                        Filter
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="table-card panel">
                <div class="table-scroll">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-600">
                            <tr>
                                <th class="px-5 py-3.5">NIS / NISN</th>
                                <th class="px-5 py-3.5">Nama Lengkap</th>
                                <th class="px-5 py-3.5">L/P</th>
                                <th class="px-5 py-3.5">Rombel Aktif</th>
                                <th class="px-5 py-3.5">Kontak Ortu</th>
                                <th class="px-5 py-3.5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="s in students.data" :key="s.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-5 py-3.5 font-medium text-slate-900">
                                    <div>{{ s.nis }}</div>
                                    <div class="text-xs text-slate-400 font-normal">{{ s.nisn || '-' }}</div>
                                </td>
                                <td class="px-5 py-3.5 font-medium text-slate-900">
                                    {{ s.name }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span :class="s.gender === 'L' ? 'text-blue-700 bg-blue-50 px-2 py-0.5 rounded-sm font-semibold' : 'text-pink-700 bg-pink-50 px-2 py-0.5 rounded-sm font-semibold'">
                                        {{ s.gender }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span v-if="s.enrollments && s.enrollments.length > 0 && s.enrollments[0].class_group" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        {{ s.enrollments[0].class_group.name }}
                                    </span>
                                    <span v-else class="text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded-sm">
                                        Belum Ada Rombel
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-xs text-slate-500">
                                    <div>{{ s.parent_name || '-' }}</div>
                                    <div class="text-slate-400">{{ s.parent_phone || '' }}</div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span :class="s.status === 'active' ? 'text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full text-xs font-medium' : 'text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full text-xs'">
                                        {{ s.status }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="students.data.length === 0">
                                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                    Tidak ada data siswa yang cocok dengan filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination footer -->
                <div class="table-footer">
                    <div>Total {{ students.total }} siswa</div>
                    <div class="flex gap-1">
                        <template v-for="(link, i) in students.links" :key="i">
                            <span
                                v-if="!link.url"
                                v-html="link.label"
                                class="px-2.5 py-1 text-slate-300 border border-transparent rounded-sm"
                            />
                            <a
                                v-else
                                :href="link.url"
                                v-html="link.label"
                                :class="link.active ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-600 hover:bg-slate-100'"
                                class="px-2.5 py-1 rounded-sm border border-slate-200 transition"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah Siswa -->
        <div v-if="showAddModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-slate-900">Tambah Peserta Didik Baru</h3>
                    <button type="button" @click="showAddModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form @submit.prevent="submitAdd" class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">NIS *</label>
                            <input v-model="form.nis" type="text" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                            <div v-if="form.errors.nis" class="text-xs text-red-600 mt-1">{{ form.errors.nis }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">NISN</label>
                            <input v-model="form.nisn" type="text" class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                        <input v-model="form.name" type="text" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        <div v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Kelamin *</label>
                            <select v-model="form.gender" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Daftarkan ke Rombel</label>
                            <select v-model="form.class_group_id" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="">(Belum Ditempatkan)</option>
                                <option v-for="c in classes" :key="c.id" :value="String(c.id)">Kelas {{ c.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Wali / Orang Tua</label>
                            <input v-model="form.parent_name" type="text" class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">No. HP Orang Tua</label>
                            <input v-model="form.parent_phone" type="text" class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="showAddModal = false" class="px-4 py-2 text-sm border rounded-lg text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            Simpan Data
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Impor CSV -->
        <div v-if="showImportModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-slate-900">Impor Massal Siswa (CSV)</h3>
                    <button type="button" @click="showImportModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="p-3 bg-indigo-50 border border-indigo-100 rounded-lg text-xs text-indigo-800 space-y-1">
                    <div class="font-semibold">Format Kolom Header:</div>
                    <code class="block font-mono text-[11px] text-indigo-900">nis,nisn,name,gender,birth_place,birth_date,religion,address,parent_phone,parent_name</code>
                    <a href="/akademik/siswa/template" class="inline-block mt-2 font-medium text-indigo-600 underline">Unduh Berkas Template CSV</a>
                </div>

                <form @submit.prevent="submitImport" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Berkas CSV *</label>
                        <input type="file" accept=".csv,.txt" @change="handleFileUpload" required class="w-full text-sm border rounded-lg p-2" />
                        <div v-if="importForm.errors.csv_file" class="text-xs text-red-600 mt-1 font-medium">{{ importForm.errors.csv_file }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Opsi: Langsung Masukkan ke Rombel</label>
                        <select v-model="importForm.enroll_class_group_id" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                            <option value="">(Hanya simpan data siswa, jangan tempatkan ke rombel)</option>
                            <option v-for="c in classes" :key="c.id" :value="String(c.id)">Kelas {{ c.name }}</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="showImportModal = false" class="px-4 py-2 text-sm border rounded-lg text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="importForm.processing" class="px-4 py-2 text-sm font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            Mulai Impor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
