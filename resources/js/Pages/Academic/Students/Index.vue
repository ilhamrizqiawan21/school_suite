<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';
import type { SharedProps, Student } from '../../../types';

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
    can_manage: boolean;
    stats: { active: number; graduated: number; exited: number };
}>();

const page = usePage<SharedProps>();

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedClass = ref(props.filters.class_group_id || '');

const showAddModal = ref(false);
const showImportModal = ref(false);
const showEditModal = ref(false);
const editingStudent = ref<Student | null>(null);
const statusLabels: Record<string, string> = {
    active: 'Aktif',
    graduated: 'Lulus',
    transferred: 'Pindah',
    dropped_out: 'Keluar',
};

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

const resetFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedClass.value = '';
    applyFilters();
};

const accountStatusLabels: Record<string, string> = {
    no_account: 'Belum ada akun',
    default: 'Perlu ganti sandi',
    changed: 'Sudah diganti',
    unavailable: 'Tidak tersedia',
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

const editForm = useForm({
    nis: '',
    nisn: '',
    name: '',
    gender: 'L',
    birth_place: '',
    birth_date: '',
    religion: 'Islam',
    parent_phone: '',
    parent_name: '',
    status: 'active',
});

const openEdit = (student: Student) => {
    editingStudent.value = student;
    editForm.clearErrors();
    editForm.nis = student.nis;
    editForm.nisn = student.nisn || '';
    editForm.name = student.name;
    editForm.gender = student.gender;
    editForm.birth_place = student.birth_place || '';
    editForm.birth_date = student.birth_date ? student.birth_date.slice(0, 10) : '';
    editForm.religion = student.religion || 'Islam';
    editForm.parent_phone = student.parent_phone || '';
    editForm.parent_name = student.parent_name || '';
    editForm.status = student.status;
    showEditModal.value = true;
};

const closeEdit = () => {
    showEditModal.value = false;
    editingStudent.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const submitEdit = () => {
    if (!editingStudent.value) return;

    editForm.put(`/akademik/siswa/${editingStudent.value.id}`, {
        preserveScroll: true,
        onSuccess: closeEdit,
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
    importForm.post('/akademik/siswa/import/preview', {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};

const exportExcelUrl = () => {
    const params = new URLSearchParams({
        search: search.value,
        status: selectedStatus.value,
        class_group_id: selectedClass.value,
    });
    return `/akademik/siswa/export/excel?${params.toString()}`;
};

const resetPassword = (student: Student) => {
    if (!student.user_id || !['default', 'changed'].includes(student.account_status ?? '') || !window.confirm(`Buat sandi sementara baru untuk ${student.name}? Sandi akan ditampilkan setelah reset. Sampaikan secara pribadi kepada siswa.`)) return;

    router.post(`/akademik/siswa/${student.id}/reset-password`, {}, { preserveScroll: true });
};

const deleteStudent = (student: Student) => {
    if (!window.confirm(`Hapus data siswa ${student.name}? Hanya siswa tanpa riwayat akademik yang dapat dihapus. Akun pengguna tidak ikut dihapus. Tindakan ini tidak dapat dibatalkan.`)) return;

    router.delete(`/akademik/siswa/${student.id}`, { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Data Siswa">
        <Head title="Data Siswa — School Suite" />

        <div class="view student-page space-y-5">
            <!-- Header bar -->
            <div class="page-heading compact-heading">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Manajemen Data Siswa</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        {{ can_manage ? 'Kelola data induk siswa, penempatan rombel semester aktif, dan impor massal.' : 'Lihat data induk siswa dan penempatan rombel semester aktif.' }}
                    </p>
                </div>
                <div class="student-header-actions">
                    <button v-if="can_manage" type="button" class="student-action-button student-action-add" @click="showAddModal = true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" /></svg>
                        Tambah siswa
                    </button>
                    <button
                        v-if="can_manage"
                        type="button"
                        @click="showImportModal = true"
                        class="student-action-button student-action-import"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Impor Siswa
                    </button>
                    <a :href="exportExcelUrl()" class="student-action-button student-action-export">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414A2 2 0 0018.414 8L14 3.586A2 2 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2zM13 3v6h6M8 13h8m-8 4h5" />
                        </svg>
                        Ekspor Excel
                    </a>
                </div>
            </div>

            <div v-if="page.props.flash?.message" class="student-notice student-notice-success" role="status">{{ page.props.flash.message }}</div>
            <div v-if="page.props.errors?.student" class="student-notice student-notice-error" role="alert">{{ page.props.errors.student }}</div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3" aria-label="Ringkasan siswa seluruh sekolah">
                <div class="panel student-stat"><span>Siswa aktif</span><strong>{{ stats.active }}</strong></div>
                <div class="panel student-stat"><span>Lulus</span><strong>{{ stats.graduated }}</strong></div>
                <div class="panel student-stat"><span>Keluar / pindah</span><strong>{{ stats.exited }}</strong></div>
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
                        aria-label="Cari nama, NIS, atau NISN"
                    />
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </label>

                <div class="flex flex-wrap gap-2">
                    <select
                        aria-label="Filter rombel"
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
                        aria-label="Filter status siswa"
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
                    <button type="button" class="secondary-button" @click="resetFilters">Reset filter</button>
                </div>
            </div>

            <!-- Table -->
            <div class="table-card panel">
                <div class="table-scroll">
                    <table class="student-table w-full text-left border-collapse text-sm">
                        <thead>
                            <tr>
                                <th class="px-5 py-3.5">NIS / NISN</th>
                                <th class="px-5 py-3.5">Nama Lengkap</th>
                                <th class="px-5 py-3.5">L/P</th>
                                <th class="px-5 py-3.5">Rombel Aktif</th>
                                <th class="px-5 py-3.5">Kontak Ortu</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th v-if="can_manage" class="px-5 py-3.5">Status sandi</th>
                                <th v-if="can_manage" class="student-actions-heading px-5 py-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="s in students.data" :key="s.id" class="student-data-row">
                                <td class="px-5 py-3.5 font-medium text-slate-900">
                                    <div>{{ s.nis }}</div>
                                    <div class="text-xs text-slate-400 font-normal">{{ s.nisn || '-' }}</div>
                                </td>
                                <td class="px-5 py-3.5 font-medium text-slate-900">
                                    {{ s.name }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="student-gender-badge" :class="s.gender === 'L' ? 'text-blue-700 bg-blue-50 px-2 py-0.5 rounded-sm font-semibold' : 'text-pink-700 bg-pink-50 px-2 py-0.5 rounded-sm font-semibold'">
                                        {{ s.gender }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span v-if="s.enrollments && s.enrollments.length > 0 && s.enrollments[0].class_group" class="student-enrollment-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">
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
                                        {{ statusLabels[s.status] ?? s.status }}
                                    </span>
                                </td>
                                <td v-if="can_manage" class="px-5 py-3.5"><span class="student-account-status" :class="`is-${s.account_status ?? 'unavailable'}`">{{ accountStatusLabels[s.account_status ?? 'unavailable'] }}</span></td>
                                <td v-if="can_manage" class="student-actions-cell px-5 py-3.5">
                                    <div class="student-row-actions">
                                        <button v-if="can_manage" type="button" class="student-row-action student-row-action-edit" aria-label="Edit data siswa" title="Edit data siswa" @click="openEdit(s)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" />
                                            </svg>
                                            <span class="sr-only">Edit</span>
                                        </button>
                                        <button v-if="can_manage" type="button" class="student-row-action student-row-action-reset" :disabled="!s.user_id || !['default', 'changed'].includes(s.account_status ?? '')" :aria-label="s.user_id ? 'Buat sandi sementara baru' : 'Siswa belum memiliki akun'" :title="s.user_id ? 'Buat sandi sementara baru' : 'Siswa belum memiliki akun'" @click="resetPassword(s)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                                <rect x="5" y="10" width="14" height="10" rx="2" stroke-width="2" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10V7a4 4 0 118 0v3" />
                                            </svg>
                                            <span class="sr-only">Reset password</span>
                                        </button>
                                        <button v-if="can_manage" type="button" class="student-row-action student-row-action-delete" aria-label="Hapus data siswa" title="Hapus data siswa" @click="deleteStudent(s)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3" />
                                            </svg>
                                            <span class="sr-only">Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="students.data.length === 0">
                                <td :colspan="can_manage ? 8 : 6" class="px-5 py-12 text-center text-slate-400">
                                    Tidak ada data siswa yang cocok dengan filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="student-mobile-list" aria-label="Daftar siswa">
                    <article v-for="s in students.data" :key="s.id" class="student-mobile-card">
                        <div class="student-mobile-top">
                            <div>
                                <h2>{{ s.name }}</h2>
                                <p>NIS {{ s.nis }}<span v-if="s.nisn"> · NISN {{ s.nisn }}</span></p>
                            </div>
                            <span class="student-mobile-status" :class="s.status === 'active' ? 'is-active' : ''">{{ statusLabels[s.status] ?? s.status }}</span>
                        </div>
                        <div class="student-mobile-details">
                            <span>{{ s.enrollments?.[0]?.class_group?.name ?? 'Belum ada rombel' }}</span>
                            <span>{{ s.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                            <span v-if="s.parent_name || s.parent_phone">Wali: {{ s.parent_name || '—' }}<span v-if="s.parent_phone"> · {{ s.parent_phone }}</span></span>
                        </div>
                        <p v-if="can_manage" class="student-mobile-password">Status sandi: <span class="student-account-status" :class="`is-${s.account_status ?? 'unavailable'}`">{{ accountStatusLabels[s.account_status ?? 'unavailable'] }}</span></p>
                        <div v-if="can_manage" class="student-mobile-actions">
                            <button type="button" :aria-label="`Edit data ${s.name}`" @click="openEdit(s)">Edit</button>
                            <button type="button" :disabled="!s.user_id || !['default', 'changed'].includes(s.account_status ?? '')" :aria-label="s.user_id ? `Reset password ${s.name}` : `${s.name} belum memiliki akun`" @click="resetPassword(s)">Reset sandi</button>
                            <button type="button" class="student-mobile-delete" :aria-label="`Hapus data ${s.name}`" @click="deleteStudent(s)">Hapus</button>
                        </div>
                    </article>
                    <p v-if="students.data.length === 0" class="student-mobile-empty">Tidak ada data siswa yang cocok dengan filter.</p>
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
        <div v-if="showAddModal" role="dialog" aria-modal="true" aria-labelledby="student-add-title" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 id="student-add-title" class="text-lg font-bold text-slate-900">Tambah siswa</h3>
                    <button type="button" @click="showAddModal = false" class="modal-close-button text-slate-400 hover:text-slate-600" aria-label="Tutup formulir tambah siswa">✕</button>
                </div>

                <form @submit.prevent="submitAdd" class="space-y-3">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
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

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
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

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
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
                            Simpan siswa
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit Siswa -->
        <div v-if="showEditModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b pb-3">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[.12em] text-indigo-600">Perbarui data</p>
                        <h3 class="text-lg font-bold text-slate-900">Edit data siswa</h3>
                    </div>
                    <button type="button" @click="closeEdit" class="text-slate-400 hover:text-slate-600" aria-label="Tutup">✕</button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="text-xs font-semibold text-slate-700">NIS
                            <input v-model="editForm.nis" type="text" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                            <span v-if="editForm.errors.nis" class="mt-1 block text-xs font-medium text-red-600">{{ editForm.errors.nis }}</span>
                        </label>
                        <label class="text-xs font-semibold text-slate-700">NISN
                            <input v-model="editForm.nisn" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                            <span v-if="editForm.errors.nisn" class="mt-1 block text-xs font-medium text-red-600">{{ editForm.errors.nisn }}</span>
                        </label>
                    </div>
                    <label class="block text-xs font-semibold text-slate-700">Nama lengkap
                        <input v-model="editForm.name" type="text" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        <span v-if="editForm.errors.name" class="mt-1 block text-xs font-medium text-red-600">{{ editForm.errors.name }}</span>
                    </label>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="text-xs font-semibold text-slate-700">Gender
                            <select v-model="editForm.gender" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Status
                            <select v-model="editForm.status" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                                <option value="active">Aktif</option>
                                <option value="graduated">Lulus</option>
                                <option value="transferred">Pindah</option>
                                <option value="dropped_out">Keluar</option>
                            </select>
                        </label>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="text-xs font-semibold text-slate-700">Nama wali / orang tua
                            <input v-model="editForm.parent_name" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </label>
                        <label class="text-xs font-semibold text-slate-700">No. HP orang tua
                            <input v-model="editForm.parent_phone" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </label>
                    </div>
                    <div class="flex justify-end gap-2 border-t pt-4">
                        <button type="button" @click="closeEdit" class="rounded-lg border px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" :disabled="editForm.processing" class="primary-button !text-white disabled:cursor-not-allowed disabled:opacity-50">Simpan perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Impor siswa -->
        <div v-if="showImportModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-slate-900">Impor Massal Siswa</h3>
                    <button type="button" @click="showImportModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="p-3 bg-indigo-50 border border-indigo-100 rounded-lg text-xs text-indigo-800 space-y-1">
                    <div class="font-semibold">Terima CSV School Suite dan ekspor siswa eMadrasah (CSV/XLSX).</div>
                    <p>Kolom Kelas harus cocok dengan satu kelas tujuan yang dipilih. Impor tidak membuat kelas atau akun pengguna.</p>
                    <p>NIK dan kolom yang tidak didukung akan ditandai di preview dan tidak disimpan.</p>
                    <code class="block font-mono text-[11px] text-indigo-900">NIS, NISN, NIK, Nama Lengkap, Kelas, L/P, Tempat Lahir, Tanggal Lahir, Agama, Alamat, Nama Orang Tua, No. HP, Status</code>
                    <div class="flex flex-wrap gap-3 pt-2"><a href="/akademik/siswa/template?format=xlsx" class="font-medium text-indigo-600 underline">Unduh template Excel</a><a href="/akademik/siswa/template" class="font-medium text-indigo-600 underline">Template CSV</a></div>
                </div>

                <form @submit.prevent="submitImport" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Berkas CSV atau XLSX (maks. 5 MB) *</label>
                        <input type="file" accept=".csv,.txt,.xlsx" @change="handleFileUpload" required class="w-full text-sm border rounded-lg p-2" />
                        <p class="mt-1 text-xs text-slate-500">Maksimal 1.000 siswa per berkas. Periksa data melalui preview sebelum menyimpan.</p>
                        <div v-if="importForm.errors.csv_file" class="text-xs text-red-600 mt-1 font-medium">{{ importForm.errors.csv_file }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kelas tujuan (wajib jika kolom Kelas terisi)</label>
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
                            Lanjut ke preview
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.student-notice { padding: .85rem 1rem; border-radius: var(--radius-sm); font-size: .85rem; overflow-wrap: anywhere; }
.student-notice-success { color: var(--green); background: var(--green-soft); }
.student-notice-error { color: var(--red); background: var(--red-soft); }
.student-stat { display: grid; gap: .3rem; padding: 1rem 1.2rem; }
.student-stat span { color: var(--ink-soft); font-size: .85rem; }
.student-stat strong { color: var(--ink); font-size: 1.75rem; font-weight: 800; }
.student-account-status { display: inline-block; padding: .25rem .55rem; border-radius: 999px; color: var(--ink-soft); background: var(--surface-strong); font-size: .75rem; white-space: nowrap; }
.student-account-status.is-changed { color: var(--green); background: var(--green-soft); }
.student-account-status.is-default { color: var(--gold); background: var(--gold-soft); }
.student-mobile-password { margin-top: .75rem; color: var(--ink-soft); font-size: .75rem; }

.student-header-actions { display: flex; align-items: center; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
.student-action-button { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; min-height: 44px; padding: .65rem .9rem; color: var(--ink-soft); background: var(--surface); border: 1px solid var(--line-strong); border-radius: .65rem; font-size: .8rem; font-weight: 700; text-decoration: none; white-space: nowrap; transition: background .16s ease, border-color .16s ease; }
.student-action-button:hover { color: var(--primary); background: var(--primary-soft); border-color: var(--primary); }
.student-action-add { color: #fff; background: var(--primary); border-color: var(--primary); }
.student-action-add:hover { color: #fff; background: var(--primary-strong); }
.student-action-import { color: var(--primary); border-color: var(--primary); }
.student-page :deep(.page-heading) { margin-bottom: 0; }
.student-page :deep(.toolbar) { gap: .75rem; }
.modal-close-button { display: grid; width: 44px; height: 44px; flex: 0 0 auto; place-items: center; border-radius: .65rem; }
.student-table { min-width: 920px; }
.student-table thead { background: #172d4c; color: #fff; }
.student-table thead th { border-bottom: 0; font-size: .75rem; font-weight: 750; letter-spacing: .025em; line-height: 1.3; white-space: nowrap; }
.student-table tbody tr { background: var(--surface); border-bottom: 1px solid var(--line); transition: background-color .18s ease, box-shadow .18s ease; }.student-table tbody tr:hover { background: color-mix(in srgb, var(--primary-soft) 48%, var(--surface)); box-shadow: inset 3px 0 0 var(--primary); }.student-table td { vertical-align: middle; }
.student-data-row:hover .student-gender-badge, .student-data-row:hover .student-enrollment-badge { box-shadow: 0 0 0 2px color-mix(in srgb, var(--surface) 72%, transparent); }
.student-actions-heading, .student-actions-cell { position: sticky; right: 0; width: 150px; min-width: 150px; }
.student-actions-heading { z-index: 3; color: var(--ink-faint); text-align: center; background: var(--surface-subtle); box-shadow: -8px 0 14px rgba(17, 38, 64, .12); }
.student-actions-cell { z-index: 2; background: var(--surface); box-shadow: -8px 0 14px rgba(24, 48, 78, .08); }
.student-table tbody tr:hover .student-actions-cell { background: var(--surface-subtle) !important; }
.student-row-actions { display: flex; align-items: center; justify-content: center; gap: .45rem; white-space: nowrap; }
.student-row-action { display: inline-grid; width: 40px; height: 40px; place-items: center; border: 1px solid transparent; border-radius: .55rem; transition: background .16s ease, box-shadow .16s ease; }
.student-row-action svg { width: 16px; height: 16px; stroke-width: 2; }
.student-row-action:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 5px 10px rgba(24, 48, 78, .12); }
.student-row-action:focus-visible, .student-action-button:focus-visible { outline: 3px solid rgba(50, 105, 188, .28); outline-offset: 2px; }
.student-row-action-edit { color: #275da8; background: #edf4ff; border-color: #c9dcf8; }.student-row-action-edit:hover { background: #dfeeff; }
.student-row-action-reset { color: #9a6508; background: #fff7dc; border-color: #ecd594; }.student-row-action-reset:hover:not(:disabled) { background: #f8e9b9; }
.student-row-action-delete { color: #b33d3d; background: #fff0f0; border-color: #f2caca; }.student-row-action-delete:hover { background: #ffe1e1; }
.student-row-action:disabled { cursor: not-allowed; opacity: .42; }
.student-mobile-list { display: none; }
@media (max-width: 720px) {
    .student-header-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); width: 100%; }
    .student-action-add { grid-column: 1 / -1; }
    .table-scroll { display: none; }
    .student-mobile-list { display: grid; gap: 0; }
    .student-mobile-card { padding: 16px; background: var(--surface); border-bottom: 1px solid var(--line); }
    .student-mobile-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
    .student-mobile-top h2 { margin: 0; color: var(--ink); font-size: 15px; font-weight: 750; line-height: 1.35; }
    .student-mobile-top p { margin: 4px 0 0; color: var(--ink-soft); font-size: 12px; line-height: 1.45; }
    .student-mobile-status { flex: 0 0 auto; padding: 4px 8px; color: var(--ink-soft); background: var(--surface-strong); border-radius: 999px; font-size: 11px; font-weight: 700; }
    .student-mobile-status.is-active { color: var(--green); background: var(--green-soft); }
    .student-mobile-details { display: flex; flex-wrap: wrap; gap: 5px 10px; margin-top: 12px; color: var(--ink-soft); font-size: 12px; line-height: 1.45; }
    .student-mobile-details > span:nth-child(3) { flex-basis: 100%; }
    .student-mobile-actions { display: flex; gap: 8px; margin-top: 14px; }
    .student-mobile-actions button { min-height: 44px; padding: 0 10px; color: var(--primary); background: var(--primary-soft); border: 1px solid var(--line); border-radius: 9px; font-size: 12px; font-weight: 750; }
    .student-mobile-actions button:disabled { cursor: not-allowed; opacity: .45; }
    .student-mobile-actions button:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
    .student-mobile-actions .student-mobile-delete { margin-left: auto; color: var(--red); background: var(--red-soft); }
    .student-mobile-empty { padding: 24px 16px; color: var(--ink-soft); font-size: 13px; text-align: center; }
    .student-page :deep(.table-footer) { align-items: flex-start; flex-direction: column; gap: 10px; }
}
</style>
