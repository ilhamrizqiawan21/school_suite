<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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
const temporaryCredential = computed(() => page.props.temporary_credential);
const hideTemporaryCredential = ref(false);

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedClass = ref(props.filters.class_group_id || '');

const showAddModal = ref(false);
const showImportModal = ref(false);
const showEditModal = ref(false);
const editingStudent = ref<Student | null>(null);
const activeOverlay = computed(() => showEditModal.value || showImportModal.value || showAddModal.value);
const overlayRoot = ref<HTMLElement | null>(null);
let overlayTrigger: HTMLElement | null = null;
watch(activeOverlay, async (open) => {
    if (open) {
        overlayTrigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        await nextTick();
        overlayRoot.value?.querySelector<HTMLElement>('input:not([type="hidden"]), select, button')?.focus();
    } else {
        await nextTick();
        overlayTrigger?.focus();
        overlayTrigger = null;
    }
});
const closeOverlayOnEscape = (event: KeyboardEvent) => {
    if (!activeOverlay.value) return;
    if (event.key === 'Tab' && overlayRoot.value) {
        const focusable = Array.from(overlayRoot.value.querySelectorAll<HTMLElement>('button:not([disabled]), input:not([disabled]), select:not([disabled]), a[href]'));
        const first = focusable[0];
        const last = focusable.at(-1);
        if (first && last && ((event.shiftKey && document.activeElement === first) || (!event.shiftKey && document.activeElement === last))) {
            event.preventDefault();
            (event.shiftKey ? last : first).focus();
        }
    }
    if (event.key === 'Escape') {
        if (showEditModal.value) closeEdit();
        else if (showImportModal.value) showImportModal.value = false;
        else showAddModal.value = false;
    }
};
onMounted(() => document.addEventListener('keydown', closeOverlayOnEscape));
onBeforeUnmount(() => document.removeEventListener('keydown', closeOverlayOnEscape));
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
    address: '',
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
    address: '',
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
    editForm.address = student.address || '';
    editForm.parent_phone = student.parent_phone || '';
    editForm.parent_name = student.parent_name || '';
    editForm.status = student.status;
    editForm.defaults();
    showEditModal.value = true;
};

const closeEdit = (saved = false) => {
    if (!saved && editForm.isDirty && !window.confirm('Perubahan belum disimpan. Tutup formulir dan buang perubahan?')) return;
    showEditModal.value = false;
    editingStudent.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const submitEdit = () => {
    if (!editingStudent.value) return;

    editForm.put(`/akademik/siswa/${editingStudent.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeEdit(true),
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

const exportPdfUrl = () => {
    const params = new URLSearchParams({
        search: search.value,
        status: selectedStatus.value,
        class_group_id: selectedClass.value,
    });
    return `/akademik/siswa/export/pdf?${params.toString()}`;
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
                    <p class="student-page-kicker">Akademik · Data kesiswaan</p>
                    <h1 class="student-page-title">Data siswa</h1>
                    <p class="student-page-summary">
                        {{ can_manage ? 'Kelola data induk siswa, penempatan rombel semester aktif, dan impor massal.' : 'Lihat data induk siswa dan penempatan rombel semester aktif.' }}
                    </p>
                    <p class="student-mobile-count">{{ stats.active }} siswa aktif · {{ classes.length }} rombel</p>
                </div>
                <div class="student-header-actions">
                    <button v-if="can_manage" type="button" class="student-action-button student-action-add" aria-label="Tambah siswa" title="Tambah siswa" @click="showAddModal = true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" /></svg>
                        <span>Tambah siswa</span>
                    </button>
                    <button
                        v-if="can_manage"
                        type="button"
                        @click="showImportModal = true"
                        class="student-action-button student-action-import"
                        aria-label="Impor siswa"
                        title="Impor siswa"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <span>Impor siswa</span>
                    </button>
                    <a :href="exportExcelUrl()" class="student-action-button student-action-export student-action-file file-action-button file-action-button--excel" aria-label="Ekspor Excel" title="Ekspor Excel">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414A2 2 0 0018.414 8L14 3.586A2 2 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2zM13 3v6h6M8 13h8m-8 4h5" />
                        </svg>
                        <span>Ekspor Excel</span>
                    </a>
                    <a :href="exportPdfUrl()" class="student-action-button student-action-export student-action-file file-action-button file-action-button--pdf" aria-label="Ekspor PDF" title="Ekspor PDF">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3h7l5 5v13H7a2 2 0 01-2-2V5a2 2 0 012-2zm7 0v5h5M8 14h8m-8 3h6" /></svg>
                        <span>Ekspor PDF</span>
                    </a>
                </div>
            </div>

            <div v-if="temporaryCredential && !hideTemporaryCredential" class="student-notice border-amber-200 bg-amber-50 text-amber-950" role="alert">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <strong>Sandi sementara untuk {{ temporaryCredential.subject }}</strong>
                        <p class="mt-1 text-sm">Tampilan ini hanya muncul sekali selama 5 menit. Sampaikan melalui kanal aman, lalu tutup panel ini.</p>
                        <code class="mt-3 inline-block rounded bg-white px-3 py-2 text-base font-semibold tracking-wide">{{ temporaryCredential.password }}</code>
                    </div>
                    <button type="button" class="text-sm font-semibold underline" @click="hideTemporaryCredential = true">Tutup</button>
                </div>
            </div>
            <div v-if="page.props.errors?.student" class="student-notice student-notice-error" role="alert">{{ page.props.errors.student }}</div>

            <div class="student-summary-grid" aria-label="Ringkasan siswa seluruh sekolah">
                <div class="panel student-stat student-stat-active"><span>Siswa aktif</span><strong>{{ stats.active }}</strong><small>terdaftar pada data sekolah</small></div>
                <div class="panel student-stat student-stat-graduated"><span>Lulus</span><strong>{{ stats.graduated }}</strong><small>riwayat siswa selesai</small></div>
                <div class="panel student-stat student-stat-exited"><span>Keluar / pindah</span><strong>{{ stats.exited }}</strong><small>perlu ditinjau bila diperlukan</small></div>
            </div>

            <!-- Filters -->
            <div class="toolbar panel student-filterbar">
                <label class="table-search search-control-shell">
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

                <div class="student-filter-controls">
                    <select
                        aria-label="Filter rombel"
                        v-model="selectedClass"
                        @change="applyFilters"
                        class="student-filter-select"
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
                        class="student-filter-select"
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
            <div class="table-card panel student-table-card">
                <div class="student-list-heading"><h2>Daftar peserta didik</h2><span>{{ students.total }} siswa</span></div>
                <div class="table-scroll" tabindex="0" role="region" aria-label="Tabel data">
                    <table class="app-table student-table w-full text-left border-collapse text-sm">
                        <thead>
                            <tr>
                                <th scope="col" class="px-5 py-3.5">NIS / NISN</th>
                                <th scope="col" class="px-5 py-3.5">Nama Lengkap</th>
                                <th scope="col" class="px-5 py-3.5">L/P</th>
                                <th scope="col" class="px-5 py-3.5">Rombel Aktif</th>
                                <th scope="col" class="px-5 py-3.5">Kontak Ortu</th>
                                <th scope="col" class="px-5 py-3.5">Status</th>
                                <th scope="col" v-if="can_manage" class="px-5 py-3.5">Status sandi</th>
                                <th scope="col" v-if="can_manage" class="student-actions-heading px-5 py-3.5 text-center">Aksi</th>
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
                                        <button v-if="can_manage" type="button" class="student-row-action student-row-action-edit" :aria-label="`Edit data ${s.name}`" :title="`Edit data ${s.name}`" @click="openEdit(s)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" />
                                            </svg>
                                            <span class="sr-only">Edit</span>
                                        </button>
                                        <button v-if="can_manage" type="button" class="student-row-action student-row-action-reset" :disabled="!s.user_id || !['default', 'changed'].includes(s.account_status ?? '')" :aria-label="s.user_id ? `Buat sandi sementara baru untuk ${s.name}` : `${s.name} belum memiliki akun`" :title="s.user_id ? `Buat sandi sementara baru untuk ${s.name}` : `${s.name} belum memiliki akun`" @click="resetPassword(s)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                                <rect x="5" y="10" width="14" height="10" rx="2" stroke-width="2" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10V7a4 4 0 118 0v3" />
                                            </svg>
                                            <span class="sr-only">Reset password</span>
                                        </button>
                                        <button v-if="can_manage" type="button" class="student-row-action student-row-action-delete" :aria-label="`Hapus data ${s.name}`" :title="`Hapus data ${s.name}`" @click="deleteStudent(s)">
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
                                <p>NIS {{ s.nis }} · {{ s.enrollments?.[0]?.class_group?.name ?? 'Belum ada rombel' }}</p>
                            </div>
                        </div>
                        <div class="student-mobile-details">
                            <span v-if="s.nisn">NISN {{ s.nisn }}</span>
                            <span>{{ s.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                            <span v-if="s.parent_name || s.parent_phone">Wali: {{ s.parent_name || '—' }}<span v-if="s.parent_phone"> · {{ s.parent_phone }}</span></span>
                        </div>
                        <p v-if="can_manage" class="student-mobile-password">Status sandi: <span class="student-account-status" :class="`is-${s.account_status ?? 'unavailable'}`">{{ accountStatusLabels[s.account_status ?? 'unavailable'] }}</span></p>
                        <div class="student-mobile-card-footer"><span class="student-mobile-status" :class="s.status === 'active' ? 'is-active' : ''">{{ statusLabels[s.status] ?? s.status }}</span><div v-if="can_manage" class="student-mobile-actions">
                            <button type="button" class="student-row-action-edit" :aria-label="`Edit data ${s.name}`" :title="`Edit data ${s.name}`" @click="openEdit(s)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" /></svg></button>
                            <button type="button" class="student-row-action-reset" :disabled="!s.user_id || !['default', 'changed'].includes(s.account_status ?? '')" :aria-label="s.user_id ? `Reset password ${s.name}` : `${s.name} belum memiliki akun`" :title="`Reset sandi ${s.name}`" @click="resetPassword(s)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2" stroke-width="2" /><path stroke-width="2" stroke-linecap="round" d="M8 10V7a4 4 0 0 1 8 0v3" /></svg></button>
                            <button type="button" class="student-mobile-delete" :aria-label="`Hapus data ${s.name}`" :title="`Hapus data ${s.name}`" @click="deleteStudent(s)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3" /></svg></button>
                        </div></div>
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
        <div v-if="showAddModal" ref="overlayRoot" role="dialog" aria-modal="true" aria-labelledby="student-add-title" class="student-overlay">
            <div class="student-drawer space-y-4">
                <div class="student-drawer-header flex items-center justify-between border-b pb-3">
                    <h3 id="student-add-title" class="text-lg font-bold text-slate-900">Tambah siswa</h3>
                    <button type="button" @click="showAddModal = false" class="modal-close-button text-slate-400 hover:text-slate-600" aria-label="Tutup formulir tambah siswa">✕</button>
                </div>

                <form @submit.prevent="submitAdd" class="space-y-3">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="student-add-nis" class="block text-xs font-semibold text-slate-700 mb-1">NIS *</label>
                            <input id="student-add-nis" v-model="form.nis" type="text" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                            <div v-if="form.errors.nis" class="text-xs text-red-600 mt-1">{{ form.errors.nis }}</div>
                        </div>
                        <div>
                            <label for="student-add-nisn" class="block text-xs font-semibold text-slate-700 mb-1">NISN</label>
                            <input id="student-add-nisn" v-model="form.nisn" type="text" class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                    </div>

                    <div>
                        <label for="student-add-name" class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                        <input id="student-add-name" v-model="form.name" type="text" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        <div v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="student-add-gender" class="block text-xs font-semibold text-slate-700 mb-1">Jenis Kelamin *</label>
                            <select id="student-add-gender" v-model="form.gender" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                        <div>
                            <label for="student-add-class" class="block text-xs font-semibold text-slate-700 mb-1">Daftarkan ke Rombel</label>
                            <select id="student-add-class" v-model="form.class_group_id" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="">(Belum Ditempatkan)</option>
                                <option v-for="c in classes" :key="c.id" :value="String(c.id)">Kelas {{ c.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div><label for="student-add-birth-place" class="block text-xs font-semibold text-slate-700 mb-1">Tempat lahir</label><input id="student-add-birth-place" v-model="form.birth_place" type="text" class="w-full px-3 py-2 text-sm border rounded-lg" /></div>
                        <div><label for="student-add-birth-date" class="block text-xs font-semibold text-slate-700 mb-1">Tanggal lahir</label><input id="student-add-birth-date" v-model="form.birth_date" type="date" class="w-full px-3 py-2 text-sm border rounded-lg" /></div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div><label for="student-add-religion" class="block text-xs font-semibold text-slate-700 mb-1">Agama</label><input id="student-add-religion" v-model="form.religion" type="text" class="w-full px-3 py-2 text-sm border rounded-lg" /></div>
                        <div><label for="student-add-address" class="block text-xs font-semibold text-slate-700 mb-1">Alamat</label><input id="student-add-address" v-model="form.address" type="text" class="w-full px-3 py-2 text-sm border rounded-lg" /></div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="student-add-parent" class="block text-xs font-semibold text-slate-700 mb-1">Nama Wali / Orang Tua</label>
                            <input id="student-add-parent" v-model="form.parent_name" type="text" class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                        <div>
                            <label for="student-add-phone" class="block text-xs font-semibold text-slate-700 mb-1">No. HP Orang Tua</label>
                            <input id="student-add-phone" v-model="form.parent_phone" type="text" class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                    </div>

                    <div class="student-drawer-footer flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="showAddModal = false" class="px-4 py-2 text-sm border rounded-lg text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="form.processing" class="primary-button disabled:opacity-50">
                            Simpan siswa
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit Siswa -->
        <div v-if="showEditModal" ref="overlayRoot" role="dialog" aria-modal="true" aria-labelledby="student-edit-title" class="student-overlay">
            <div class="student-drawer space-y-4">
                <div class="student-drawer-header flex items-center justify-between border-b pb-3">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[.12em] text-indigo-600">Perbarui data</p>
                        <h3 id="student-edit-title" class="text-lg font-bold text-slate-900">Edit data siswa</h3>
                        <p v-if="editingStudent" class="student-edit-context">{{ editingStudent.name }} · NIS {{ editingStudent.nis }}</p>
                    </div>
                    <button type="button" @click="closeEdit()" class="modal-close-button text-slate-400 hover:text-slate-600" aria-label="Tutup formulir edit siswa">✕</button>
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
                        <label class="text-xs font-semibold text-slate-700">Tempat lahir
                            <input v-model="editForm.birth_place" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                            <span v-if="editForm.errors.birth_place" class="mt-1 block text-xs font-medium text-red-600">{{ editForm.errors.birth_place }}</span>
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Tanggal lahir
                            <input v-model="editForm.birth_date" type="date" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                            <span v-if="editForm.errors.birth_date" class="mt-1 block text-xs font-medium text-red-600">{{ editForm.errors.birth_date }}</span>
                        </label>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="text-xs font-semibold text-slate-700">Agama
                            <input v-model="editForm.religion" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                            <span v-if="editForm.errors.religion" class="mt-1 block text-xs font-medium text-red-600">{{ editForm.errors.religion }}</span>
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Alamat
                            <input v-model="editForm.address" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                            <span v-if="editForm.errors.address" class="mt-1 block text-xs font-medium text-red-600">{{ editForm.errors.address }}</span>
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
                    <div class="student-drawer-footer flex justify-end gap-2 border-t pt-4">
                        <span v-if="editForm.isDirty" class="student-unsaved-note">Perubahan belum disimpan</span>
                        <button type="button" @click="closeEdit()" class="rounded-lg border px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" :disabled="editForm.processing" class="primary-button !text-white disabled:cursor-not-allowed disabled:opacity-50">Simpan perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Impor siswa -->
        <div v-if="showImportModal" ref="overlayRoot" role="dialog" aria-modal="true" aria-labelledby="student-import-title" class="student-overlay">
            <div class="student-drawer space-y-4">
                <div class="student-drawer-header flex items-center justify-between border-b pb-3">
                    <h3 id="student-import-title" class="text-lg font-bold text-slate-900">Impor Massal Siswa</h3>
                    <button type="button" @click="showImportModal = false" class="modal-close-button text-slate-400 hover:text-slate-600" aria-label="Tutup formulir impor siswa">✕</button>
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
                        <label for="student-import-file" class="block text-xs font-semibold text-slate-700 mb-1">Pilih Berkas CSV atau XLSX (maks. 5 MB) *</label>
                        <input id="student-import-file" type="file" accept=".csv,.txt,.xlsx" @change="handleFileUpload" required class="w-full text-sm border rounded-lg p-2" />
                        <p class="mt-1 text-xs text-slate-500">Maksimal 1.000 siswa per berkas. Periksa data melalui preview sebelum menyimpan.</p>
                        <div v-if="importForm.errors.csv_file" class="text-xs text-red-600 mt-1 font-medium">{{ importForm.errors.csv_file }}</div>
                    </div>

                    <div>
                        <label for="student-import-class" class="block text-xs font-semibold text-slate-700 mb-1">Kelas tujuan (wajib jika kolom Kelas terisi)</label>
                        <select id="student-import-class" v-model="importForm.enroll_class_group_id" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                            <option value="">(Hanya simpan data siswa, jangan tempatkan ke rombel)</option>
                            <option v-for="c in classes" :key="c.id" :value="String(c.id)">Kelas {{ c.name }}</option>
                        </select>
                    </div>

                    <div class="student-drawer-footer flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="showImportModal = false" class="px-4 py-2 text-sm border rounded-lg text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="importForm.processing" class="primary-button disabled:opacity-50">
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
.student-overlay { position: fixed; inset: 0; z-index: 80; display: flex; justify-content: flex-end; background: rgb(19 32 51 / 45%); backdrop-filter: blur(3px); }
.student-drawer { width: min(560px, 100%); height: 100%; padding: clamp(18px, 3vw, 30px); overflow-y: auto; overscroll-behavior: contain; background: var(--surface); color: var(--ink); box-shadow: -18px 0 40px rgb(19 32 51 / 15%); }
.student-drawer-header { position: sticky; top: calc(-1 * clamp(18px, 3vw, 30px)); z-index: 2; margin-top: calc(-1 * clamp(18px, 3vw, 30px)); padding-top: clamp(18px, 3vw, 30px); background: var(--surface); border-color: var(--line); }
.student-drawer-footer { position: sticky; bottom: calc(-1 * clamp(18px, 3vw, 30px)); z-index: 2; margin-bottom: calc(-1 * clamp(18px, 3vw, 30px)); padding-bottom: clamp(18px, 3vw, 30px); background: var(--surface); border-color: var(--line); }
.student-unsaved-note { align-self: center; margin-right: auto; color: var(--ink-soft); font-size: .75rem; }
.student-drawer :deep(label), .student-drawer :deep(code) { overflow-wrap: anywhere; }
.student-drawer :deep(input), .student-drawer :deep(select) { max-width: 100%; color: var(--ink); background: var(--surface); border-color: var(--line-strong); }
.student-drawer :deep(input:focus-visible), .student-drawer :deep(select:focus-visible), .student-drawer button:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
.student-drawer :deep(.text-slate-900), .student-drawer :deep(.text-slate-700) { color: var(--ink); }
.student-drawer :deep(.text-slate-600), .student-drawer :deep(.text-slate-500), .student-drawer :deep(.text-slate-400) { color: var(--ink-soft); }
@media (max-width: 600px) { .student-overlay { align-items: flex-end; } .student-drawer { height: min(92dvh, 900px); border-radius: 18px 18px 0 0; padding-bottom: max(24px, env(safe-area-inset-bottom)); } }
.student-notice-success { color: var(--green); background: var(--green-soft); }
.student-notice-error { color: var(--red); background: var(--red-soft); }
.student-edit-context { margin: .3rem 0 0; color: var(--ink-soft); font-size: .8rem; }
.student-mobile-count { display: none; }
.student-page-kicker { margin: 0 0 .3rem; color: var(--primary); font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.student-stat { display: grid; gap: .3rem; min-width: 0; padding: 1rem 1.2rem; border-top: 3px solid transparent; }
.student-stat span { color: var(--ink-soft); font-size: .82rem; font-weight: 600; }
.student-stat strong { color: var(--ink); font-size: 1.75rem; font-weight: 800; line-height: 1.1; }
.student-stat small { color: var(--ink-faint); font-size: .72rem; line-height: 1.4; }
.student-stat-active { border-top-color: #3269bc; background: color-mix(in srgb, #3269bc 7%, var(--surface)); }
.student-stat-graduated { border-top-color: #3d9b78; background: color-mix(in srgb, #3d9b78 7%, var(--surface)); }
.student-stat-exited { border-top-color: #ad7c2b; background: color-mix(in srgb, #ad7c2b 7%, var(--surface)); }
.student-account-status { display: inline-block; padding: .25rem .55rem; border-radius: 999px; color: var(--ink-soft); background: var(--surface-strong); font-size: .75rem; white-space: nowrap; }
.student-account-status.is-changed { color: var(--green); background: var(--green-soft); }
.student-account-status.is-default { color: var(--gold); background: var(--gold-soft); }
.student-mobile-password { margin-top: .75rem; color: var(--ink-soft); font-size: .75rem; }

.student-header-actions { display: flex; align-items: center; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
.student-action-button { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; min-height: 44px; padding: .65rem .9rem; color: var(--ink-soft); background: var(--surface); border: 1px solid var(--line-strong); border-radius: .65rem; font-size: .8rem; font-weight: 700; text-decoration: none; white-space: nowrap; transition: background .16s ease, border-color .16s ease; }
.student-action-button:hover { color: var(--primary); background: var(--primary-soft); border-color: var(--primary); }
.student-action-file.file-action-button--excel { color: var(--excel); background: var(--excel-soft); border-color: var(--excel-line); }
.student-action-file.file-action-button--pdf { color: var(--pdf); background: var(--pdf-soft); border-color: var(--pdf-line); }
.student-action-file.file-action-button--excel:hover { color: var(--excel); background: color-mix(in srgb, var(--excel-soft) 70%, var(--excel) 30%); border-color: var(--excel); }
.student-action-file.file-action-button--pdf:hover { color: var(--pdf); background: color-mix(in srgb, var(--pdf-soft) 70%, var(--pdf) 30%); border-color: var(--pdf); }
.student-action-file > svg { display: block; }
.student-action-add { color: #fff; background: var(--primary); border-color: var(--primary); }
.student-action-add:hover { color: #fff; background: var(--primary-strong); }
.student-action-import { color: var(--primary); border-color: var(--primary); }
.student-page :deep(.page-heading) { margin-bottom: 0; }
.student-page :deep(.toolbar) { gap: .75rem; }
.modal-close-button { display: grid; width: 44px; height: 44px; flex: 0 0 auto; place-items: center; border-radius: .65rem; }
.student-table { min-width: 920px; }
.student-list-heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.15rem 1.25rem; border-bottom: 1px solid var(--line); }
.student-list-heading h2 { margin: 0; color: var(--ink); font-size: 1rem; font-weight: 700; }
.student-list-heading span { padding: .25rem .65rem; color: var(--primary); background: var(--primary-soft); border-radius: 8px; font-size: .75rem; white-space: nowrap; }
.student-table thead { background: var(--surface-subtle); color: var(--ink-faint); }
.student-table thead th { border-bottom: 1px solid var(--line); font-size: .75rem; font-weight: 700; letter-spacing: .025em; line-height: 1.3; white-space: nowrap; }
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
.student-row-action-edit { color: var(--primary-strong); background: var(--primary-soft); border-color: color-mix(in srgb, var(--primary) 22%, var(--line)); }.student-row-action-edit svg { color: var(--edit); }.student-row-action-edit:hover { background: color-mix(in srgb, var(--primary-soft) 72%, var(--primary) 28%); }
.student-row-action-reset { color: #9a6508; background: #fff7dc; border-color: #ecd594; }.student-row-action-reset:hover:not(:disabled) { background: #f8e9b9; }
.student-row-action-delete { color: var(--delete); background: var(--delete-soft); border-color: var(--delete-line); }.student-row-action-delete:hover { background: color-mix(in srgb, var(--delete-soft) 70%, var(--delete) 30%); }
.student-row-action:disabled { cursor: not-allowed; opacity: .42; }
.student-mobile-list { display: none; }
@media (max-width: 720px) {
    .student-page-kicker, .student-page-summary, .student-summary-grid { display: none; }
    .student-mobile-count { display: block; margin: .4rem 0 0; color: var(--ink-soft); font-size: .8rem; }
    .student-header-actions { display: flex; justify-content: flex-start; width: 100%; }
    .student-header-actions .student-action-button { width: 44px; height: 44px; padding: 0; }
    .student-header-actions .student-action-button span { position: absolute; width: 1px; height: 1px; padding: 0; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
    .student-header-actions .student-action-button svg { display: block; width: 19px; height: 19px; }
    .student-action-add { order: 4; margin-left: auto; }
    .student-action-import { order: 3; }
    .student-action-file.file-action-button--excel { order: 1; }
    .student-action-file.file-action-button--pdf { order: 2; }
    .table-scroll { display: none; }
    .student-list-heading { display: none; }
    .student-table-card { background: transparent; border: 0; box-shadow: none; }
    .student-mobile-list { display: grid; gap: 12px; }
    .student-mobile-card { padding: 16px; background: var(--surface); border: 1px solid var(--line); border-radius: 12px; }
    .student-mobile-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
    .student-mobile-top h2 { margin: 0; color: var(--ink); font-size: 15px; font-weight: 700; line-height: 1.35; }
    .student-mobile-top p { margin: 4px 0 0; color: var(--ink-soft); font-size: 12px; line-height: 1.45; }
    .student-mobile-status { flex: 0 0 auto; padding: 4px 8px; color: var(--ink-soft); background: var(--surface-strong); border-radius: 999px; font-size: 11px; font-weight: 700; }
    .student-mobile-status.is-active { color: var(--green); background: var(--green-soft); }
    .student-mobile-details { display: flex; flex-wrap: wrap; gap: 5px 10px; margin-top: 8px; color: var(--ink-soft); font-size: 12px; line-height: 1.45; }
    .student-mobile-details > span:nth-child(3) { flex-basis: 100%; }
    .student-mobile-card-footer { display: flex; align-items: flex-end; justify-content: space-between; gap: 10px; margin-top: 14px; }
    .student-mobile-actions { display: flex; gap: 8px; margin-top: 0; }
    .student-mobile-actions button { display: grid; place-items: center; width: 44px; min-height: 44px; padding: 0; color: var(--primary); background: var(--primary-soft); border: 1px solid var(--line); border-radius: 9px; }
    .student-mobile-actions button svg { width: 19px; height: 19px; }
    .student-mobile-actions button:disabled { cursor: not-allowed; opacity: .45; }
    .student-mobile-actions button:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
    .student-mobile-actions .student-mobile-delete { color: var(--red); background: var(--red-soft); }
    .student-mobile-empty { padding: 24px 16px; color: var(--ink-soft); font-size: 13px; text-align: center; }
    .student-page :deep(.table-footer) { align-items: flex-start; flex-direction: column; gap: 10px; }
}
/* Center compact status and action columns while preserving readable identity text. */
.student-table th:nth-child(n+6), .student-table td:nth-child(n+6) { text-align: center; }
.student-table td:nth-child(n+6) > span, .student-table td:nth-child(n+6) > .student-row-actions { margin-inline: auto; }
</style>
