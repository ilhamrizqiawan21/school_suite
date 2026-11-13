<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';
import type { Teacher, SharedProps } from '../../../types';

const props = defineProps<{
    teachers: {
        data: Teacher[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    };
    can_manage: boolean;
    can_link_accounts: boolean;
    account_candidates: Array<{
        id: number;
        name: string;
        email?: string | null;
        role?: string | null;
        role_label?: string | null;
        linked_teacher_id?: number | null;
    }>;
    stats: { total: number; active: number; inactive: number };
    retirement_stats: { upcoming: number; reached: number; missing: number };
    employment_statuses: string[];
    filters: {
        search?: string;
        employment_status?: string;
        is_active?: string;
        retirement?: string;
    };
}>();

const page = usePage<SharedProps>();
const temporaryCredential = computed(() => page.props.temporary_credential);
const hideTemporaryCredential = ref(false);
const activeFilter = ref(props.filters.is_active ?? '');
const editingId = ref<number | null>(null);
const accountLabels = { no_account: 'Belum ada akun', default: 'Wajib ganti sandi', changed: 'Sudah diubah', unavailable: 'Akun tidak tersedia' };
const employmentOptions = [...new Set(['PNS', 'PPPK', 'GTT', ...props.employment_statuses])];
const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.employment_status || '');
const selectedRetirement = ref(props.filters.retirement || '');
const retirementLabels = {
    missing: 'Tanggal lahir belum diisi',
    not_due: 'Belum mendekati pensiun',
    upcoming: 'Pensiun dalam 1 tahun',
    reached: 'Sudah mencapai 60 tahun',
} as const;
const retirementClasses = {
    missing: 'text-slate-600 bg-slate-100',
    not_due: 'text-emerald-700 bg-emerald-50',
    upcoming: 'text-amber-700 bg-amber-50',
    reached: 'text-rose-700 bg-rose-50',
} as const;
const dateLabel = (value?: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(`${value}T00:00:00`))
    : '-';

const showAddModal = ref(false);
const showImportModal = ref(false);
const activeOverlay = computed(() => showAddModal.value || showImportModal.value);
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
const handleOverlayKeydown = (event: KeyboardEvent) => {
    if (!activeOverlay.value) return;
    if (event.key === 'Escape') { showAddModal.value = false; showImportModal.value = false; }
    if (event.key !== 'Tab' || !overlayRoot.value) return;
    const focusable = Array.from(overlayRoot.value.querySelectorAll<HTMLElement>('button:not([disabled]), input:not([disabled]), select:not([disabled]), a[href]'));
    const first = focusable[0];
    const last = focusable.at(-1);
    if (first && last && ((event.shiftKey && document.activeElement === first) || (!event.shiftKey && document.activeElement === last))) {
        event.preventDefault();
        (event.shiftKey ? last : first).focus();
    }
};
onMounted(() => document.addEventListener('keydown', handleOverlayKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', handleOverlayKeydown));

const applyFilters = () => {
    router.get(
        '/akademik/guru',
        {
            search: search.value || undefined,
            is_active: activeFilter.value === '' ? undefined : activeFilter.value,
            employment_status: selectedStatus.value || undefined,
            retirement: selectedRetirement.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const form = useForm({
    nip: '',
    name: '',
    gender: 'L',
    phone: '',
    email: '',
    birth_date: '',
    employment_status: 'PNS',
    is_active: true,
    user_id: null as number | null,
});

const openAdd = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    showAddModal.value = true;
};
const openEdit = (teacher: Teacher) => {
    editingId.value = teacher.id;
    form.clearErrors();
    form.nip = teacher.nip ?? '';
    form.name = teacher.name;
    form.gender = teacher.gender;
    form.phone = teacher.phone ?? '';
    form.email = teacher.email ?? '';
    form.birth_date = teacher.birth_date ? teacher.birth_date.slice(0, 10) : '';
    form.employment_status = teacher.employment_status ?? '';
    form.is_active = teacher.is_active;
    form.user_id = teacher.user_id ?? null;
    showAddModal.value = true;
};
const submitAdd = () => {
    const options = { preserveScroll: true, onSuccess: () => { showAddModal.value = false; form.reset(); } };
    if (editingId.value) form.put(`/akademik/guru/${editingId.value}`, options);
    else form.post('/akademik/guru', options);
};
const resetFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    activeFilter.value = '';
    selectedRetirement.value = '';
    applyFilters();
};
const exportUrl = () => '/akademik/guru/export/excel?' + new URLSearchParams({ search: search.value, employment_status: selectedStatus.value, is_active: activeFilter.value, retirement: selectedRetirement.value });
const resetPassword = (teacher: Teacher) => {
    if (!['default', 'changed'].includes(teacher.account_status ?? '') || !window.confirm(`Buat sandi sementara baru untuk ${teacher.name}? Sampaikan sandi secara pribadi kepada guru.`)) return;
    router.post(`/akademik/guru/${teacher.id}/reset-password`, {}, { preserveScroll: true });
};
const deleteTeacher = (teacher: Teacher) => {
    if (!window.confirm(`Hapus data guru ${teacher.name}? Penghapusan ditolak jika ada penugasan atau riwayat akademik. Akun pengguna tetap tersedia.`)) return;
    router.delete(`/akademik/guru/${teacher.id}`, { preserveScroll: true });
};

const importForm = useForm({
    csv_file: null as File | null,
});

const handleFileUpload = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        importForm.csv_file = target.files[0];
    }
};

const submitImport = () => {
    importForm.post('/akademik/guru/import/preview', {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};
</script>

<template>
    <AppLayout title="Data Guru & Tenaga Kependidikan">
        <Head title="Data Guru — School Suite" />

        <div class="view teacher-page space-y-5">
            <div class="page-heading compact-heading">
                <div>
                    <p class="teacher-page-kicker">Akademik · Sumber daya sekolah</p>
                    <h1 class="teacher-page-title">Data guru</h1>
                    <p class="teacher-page-summary">
                        Kelola profil pendidik, status kepegawaian, akun login, dan penugasan wali kelas.
                    </p>
                </div>
                <div class="teacher-header-actions">
                    <button v-if="can_manage" type="button" class="teacher-action-button teacher-action-add" @click="openAdd">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" /></svg>
                        Tambah guru
                    </button>
                    <button v-if="can_manage" type="button" @click="showImportModal = true" class="teacher-action-button teacher-action-import">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Impor data
                    </button>
                    <a :href="exportUrl()" class="teacher-action-button teacher-action-excel" aria-label="Ekspor Excel" title="Ekspor Excel"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 3h10l4 4v14H5zM15 3v5h4M8 13h8m-8 4h8" /></svg><span>Ekspor Excel</span></a>
                    <a v-if="can_manage" href="/akademik/guru/template?format=xlsx" class="teacher-action-button">Unduh template</a>
                </div>
            </div>

            <div v-if="page.props.flash?.message" class="panel p-4" role="status">{{ page.props.flash.message }}</div>
            <div v-if="temporaryCredential && !hideTemporaryCredential" class="panel border-amber-200 bg-amber-50 p-4 text-amber-950" role="alert">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <strong>Sandi sementara untuk {{ temporaryCredential.subject }}</strong>
                        <p class="mt-1 text-sm">Tampilan ini hanya muncul sekali selama 5 menit. Sampaikan melalui kanal aman, lalu tutup panel ini.</p>
                        <code class="mt-3 inline-block rounded bg-white px-3 py-2 text-base font-semibold tracking-wide">{{ temporaryCredential.password }}</code>
                    </div>
                    <button type="button" class="text-sm font-semibold underline" @click="hideTemporaryCredential = true">Tutup</button>
                </div>
            </div>
            <div v-if="page.props.errors?.teacher" class="panel p-4 text-red-700" role="alert">{{ page.props.errors.teacher }}</div>
            <div class="teacher-summary-grid" aria-label="Ringkasan data guru">
                <div class="panel teacher-stat teacher-stat-total"><span>Total guru & tenaga kependidikan</span><strong>{{ stats.total }}</strong><small>seluruh data sekolah</small></div>
                <div class="panel teacher-stat teacher-stat-active"><span>Guru aktif</span><strong>{{ stats.active }}</strong><small>siap menerima penugasan</small></div>
                <div class="panel teacher-stat teacher-stat-inactive"><span>Nonaktif</span><strong>{{ stats.inactive }}</strong><small>perlu ditinjau bila diperlukan</small></div>
            </div>
            <div class="teacher-retirement-summary" aria-label="Ringkasan masa pensiun">
                <div><span>Perlu perhatian masa pensiun</span><strong>{{ retirement_stats.upcoming + retirement_stats.reached + retirement_stats.missing }}</strong></div>
                <div><span>Pensiun dalam 1 tahun</span><strong class="is-warning">{{ retirement_stats.upcoming }}</strong></div>
                <div><span>Tanggal lahir belum diisi</span><strong>{{ retirement_stats.missing }}</strong></div>
            </div>

            <div class="toolbar panel teacher-filterbar">
                <label class="table-search">
                    <input
                        aria-label="Cari nama, NIP, atau email" v-model="search"
                        @keyup.enter="applyFilters"
                        type="search"
                        placeholder="Cari nama, NIP, atau email..."
                        class="w-full"
                    />
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </label>
                <div class="teacher-filter-controls">
                    <select
                        aria-label="Status kepegawaian" v-model="selectedStatus"
                        @change="applyFilters"
                        class="teacher-filter-select"
                    >
                        <option value="">Semua status kepegawaian</option>
                        <option v-for="status in employmentOptions" :key="status" :value="status">{{ status }}</option>
                    </select>

                    <select v-model="activeFilter" aria-label="Status aktif" class="teacher-filter-select" @change="applyFilters"><option value="">Semua status aktif</option><option value="1">Aktif</option><option value="0">Nonaktif</option></select>
                    <select v-model="selectedRetirement" aria-label="Status masa pensiun" class="teacher-filter-select" @change="applyFilters">
                        <option value="">Semua status masa pensiun</option>
                        <option value="upcoming">Pensiun dalam 1 tahun</option>
                        <option value="reached">Sudah mencapai 60 tahun</option>
                        <option value="missing">Tanggal lahir belum diisi</option>
                    </select>
                    <button type="button" class="secondary-button" @click="resetFilters">Reset filter</button>
                    <button type="button" class="secondary-button teacher-filter-submit" @click="applyFilters">Terapkan</button>
                </div>
            </div>

            <div class="table-card panel teacher-table-card">
                <div class="teacher-list-heading"><h2>Daftar pendidik</h2><span>{{ teachers.total }} guru</span></div>
                <div class="overflow-x-auto table-scroll" tabindex="0" role="region" aria-label="Tabel data">
                    <table class="app-table teacher-table w-full text-left border-collapse text-sm">
                        <thead>
                            <tr>
                                <th scope="col" class="px-5 py-3.5">Nama & NIP</th>
                                <th scope="col" class="px-5 py-3.5">L/P</th>
                                <th scope="col" class="px-5 py-3.5">Masa Pensiun</th>
                                <th scope="col" class="px-5 py-3.5">Kontak</th>
                                <th scope="col" class="px-5 py-3.5">Status Pegawai</th>
                                <th scope="col" class="px-5 py-3.5">Wali Kelas</th>
                                <th scope="col" class="px-5 py-3.5">Status</th>
                                <th scope="col" v-if="can_manage" class="teacher-actions-heading px-5 py-3.5">Akun & aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="t in teachers.data" :key="t.id" class="teacher-data-row">
                                <td class="px-5 py-3.5 font-medium text-slate-900">
                                    <div>{{ t.name }}</div>
                                    <div class="text-xs text-slate-400 font-normal">{{ t.nip || '-' }}</div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span :class="t.gender === 'L' ? 'text-blue-700 bg-blue-50 px-2 py-0.5 rounded-sm font-semibold' : 'text-pink-700 bg-pink-50 px-2 py-0.5 rounded-sm font-semibold'">
                                        {{ t.gender }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-xs">
                                    <div class="font-medium text-slate-700">{{ dateLabel(t.retirement_date) }}</div>
                                    <span :class="`inline-flex mt-1 items-center px-2 py-0.5 rounded-full font-medium ${retirementClasses[t.retirement_status ?? 'missing']}`">
                                        {{ retirementLabels[t.retirement_status ?? 'missing'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-xs text-slate-500">
                                    <div>{{ t.email || '-' }}</div>
                                    <div class="text-slate-400">{{ t.phone || '' }}</div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ t.employment_status || '-' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span v-if="t.homeroom_class_groups && t.homeroom_class_groups.length > 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        Kelas {{ t.homeroom_class_groups.map(group => group.name).join(', ') }}
                                    </span>
                                    <span v-else class="text-xs text-slate-400">
                                        -
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span :class="t.is_active ? 'text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full text-xs font-medium' : 'text-rose-700 bg-rose-50 px-2.5 py-1 rounded-full text-xs'">
                                        {{ t.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td v-if="can_manage" class="teacher-actions-cell px-5 py-3.5">
                                    <span class="teacher-account-status" :class="`is-${t.account_status ?? 'unavailable'}`">{{ accountLabels[t.account_status ?? 'unavailable'] }}</span>
                                    <div class="teacher-row-actions">
                                        <button type="button" class="teacher-row-action teacher-row-action-edit" :aria-label="`Ubah ${t.name}`" title="Ubah data guru" @click="openEdit(t)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" /></svg><span class="sr-only">Ubah</span>
                                        </button>
                                        <button type="button" class="teacher-row-action teacher-row-action-reset" :disabled="!['default', 'changed'].includes(t.account_status ?? '')" :aria-label="`Reset sandi ${t.name}`" title="Reset sandi" @click="resetPassword(t)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2" stroke-width="2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10V7a4 4 0 118 0v3" /></svg><span class="sr-only">Reset sandi</span>
                                        </button>
                                        <button type="button" class="teacher-row-action teacher-row-action-delete" :aria-label="`Hapus ${t.name}`" title="Hapus data guru" @click="deleteTeacher(t)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3" /></svg><span class="sr-only">Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="teachers.data.length === 0">
                                <td :colspan="can_manage ? 8 : 7" class="px-5 py-12 text-center text-slate-400">
                                    Tidak ada data guru yang cocok dengan filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="teacher-mobile-list" aria-label="Daftar guru">
                    <article v-for="t in teachers.data" :key="t.id" class="teacher-mobile-card">
                        <div class="teacher-mobile-top">
                            <div><h2>{{ t.name }}</h2><p>{{ t.nip || 'NIP belum diisi' }} · {{ t.employment_status || 'Status belum diisi' }}</p></div>
                            <span class="teacher-mobile-status" :class="t.is_active ? 'is-active' : ''">{{ t.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>
                        <div class="teacher-mobile-details">
                            <span>{{ t.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                            <span>{{ t.homeroom_class_groups?.length ? `Wali ${t.homeroom_class_groups.map(group => group.name).join(', ')}` : 'Belum menjadi wali kelas' }}</span>
                            <span>{{ t.email || t.phone || 'Kontak belum diisi' }}</span>
                        </div>
                        <p v-if="can_manage" class="teacher-mobile-password">Status akun: <span class="teacher-account-status" :class="`is-${t.account_status ?? 'unavailable'}`">{{ accountLabels[t.account_status ?? 'unavailable'] }}</span></p>
                        <div v-if="can_manage" class="teacher-mobile-actions">
                            <button type="button" :aria-label="`Ubah data ${t.name}`" :title="`Ubah data ${t.name}`" @click="openEdit(t)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" /></svg></button>
                            <button type="button" :disabled="!['default', 'changed'].includes(t.account_status ?? '')" :aria-label="`Reset sandi ${t.name}`" :title="`Reset sandi ${t.name}`" @click="resetPassword(t)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2" stroke-width="2" /><path stroke-width="2" stroke-linecap="round" d="M8 10V7a4 4 0 0 1 8 0v3" /></svg></button>
                            <button type="button" class="teacher-mobile-delete" :aria-label="`Hapus data ${t.name}`" :title="`Hapus data ${t.name}`" @click="deleteTeacher(t)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3" /></svg></button>
                        </div>
                    </article>
                    <p v-if="teachers.data.length === 0" class="teacher-mobile-empty">Tidak ada data guru yang cocok dengan filter.</p>
                </div>

                <div class="px-5 py-3.5 border-t border-slate-100 flex flex-wrap gap-3 items-center justify-between text-xs text-slate-500">
                    <div>Total {{ teachers.total }} guru</div>
                    <div class="flex flex-wrap gap-1">
                        <template v-for="(link, i) in teachers.links" :key="i">
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

        <!-- Modal Tambah Guru -->
        <div v-if="can_manage && showAddModal" ref="overlayRoot" role="dialog" aria-modal="true" aria-labelledby="teacher-form-title" class="teacher-overlay">
            <div class="teacher-drawer space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 id="teacher-form-title" class="text-lg font-bold text-slate-900">{{ editingId ? 'Ubah Data Guru' : 'Tambah Guru Baru' }}</h3>
                    <button type="button" @click="showAddModal = false" class="teacher-close text-slate-400 hover:text-slate-600" aria-label="Tutup formulir guru">✕</button>
                </div>

                <form @submit.prevent="submitAdd" class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="teacher-form-nip" class="block text-xs font-semibold text-slate-700 mb-1">NIP</label>
                            <input id="teacher-form-nip" v-model="form.nip" type="text" class="w-full px-3 py-2 text-sm border rounded-lg" />
                            <div v-if="form.errors.nip" class="text-xs text-red-600 mt-1">{{ form.errors.nip }}</div>
                        </div>
                        <div>
                            <label for="teacher-form-gender" class="block text-xs font-semibold text-slate-700 mb-1">Jenis kelamin *</label>
                            <select id="teacher-form-gender" v-model="form.gender" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="teacher-form-name" class="block text-xs font-semibold text-slate-700 mb-1">Nama lengkap & gelar *</label>
                        <input id="teacher-form-name" v-model="form.name" type="text" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        <div v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label for="teacher-form-birth-date" class="block text-xs font-semibold text-slate-700 mb-1">Tanggal lahir</label>
                        <input id="teacher-form-birth-date" v-model="form.birth_date" type="date" class="w-full px-3 py-2 text-sm border rounded-lg" />
                        <p class="mt-1 text-xs text-slate-500">Tanggal pensiun dihitung pada ulang tahun ke-60.</p>
                        <div v-if="form.errors.birth_date" class="text-xs text-red-600 mt-1">{{ form.errors.birth_date }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="teacher-form-email" class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                            <input id="teacher-form-email" v-model="form.email" type="email" class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                        <div>
                            <label for="teacher-form-phone" class="block text-xs font-semibold text-slate-700 mb-1">No. HP / WhatsApp</label>
                            <input id="teacher-form-phone" v-model="form.phone" type="text" class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                    </div>

                    <div>
                        <label for="teacher-form-employment" class="block text-xs font-semibold text-slate-700 mb-1">Status kepegawaian</label>
                        <select id="teacher-form-employment" v-model="form.employment_status" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                            <option value="">Belum diisi</option>
                            <option v-for="status in employmentOptions" :key="status" :value="status">{{ status }}</option>
                        </select>
                    </div>

                    <label class="flex items-center gap-2 text-sm"><input v-model="form.is_active" type="checkbox" /> Data guru aktif</label>
                    <div v-if="can_link_accounts" class="space-y-1">
                        <label for="teacher-form-account" class="block text-xs font-semibold text-slate-700">Akun login (opsional)</label>
                        <select id="teacher-form-account" v-model="form.user_id" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                            <option :value="null">Tidak ditautkan</option>
                            <option
                                v-for="account in account_candidates"
                                :key="account.id"
                                :value="account.id"
                                :disabled="Boolean(account.linked_teacher_id && account.linked_teacher_id !== editingId)"
                            >
                                {{ account.name }} · {{ account.role_label || account.role }}{{ account.linked_teacher_id && account.linked_teacher_id !== editingId ? ' · sudah ditautkan' : '' }}
                            </option>
                        </select>
                        <p class="text-xs text-slate-500">Staf TU/operator yang ditautkan tetap mempertahankan akses TU dan memperoleh akses guru.</p>
                        <div v-if="form.errors.user_id" class="text-xs text-red-600 mt-1">{{ form.errors.user_id }}</div>
                    </div>
                    <p v-else class="text-xs text-slate-500">Status ini berlaku untuk data guru. Penautan akun login hanya dapat dilakukan admin.</p>
                    <ul v-if="Object.keys(form.errors).length" class="text-sm text-red-700" role="alert"><li v-for="(error, field) in form.errors" :key="field">{{ error }}</li></ul>
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

        <!-- Modal Impor Excel / CSV Guru -->
        <div v-if="can_manage && showImportModal" ref="overlayRoot" role="dialog" aria-modal="true" aria-labelledby="teacher-import-title" class="teacher-overlay">
            <div class="teacher-drawer space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 id="teacher-import-title" class="text-lg font-bold text-slate-900">Impor Massal Pendidik</h3>
                    <button type="button" @click="showImportModal = false" class="teacher-close text-slate-400 hover:text-slate-600" aria-label="Tutup formulir impor guru">✕</button>
                </div>

                <div class="p-3 bg-indigo-50 border border-indigo-100 rounded-lg text-xs text-indigo-800 space-y-1">
                    <div class="font-semibold">Format Kolom Header:</div>
                    <code class="block font-mono text-[11px] text-indigo-900">nip,name,gender,birth_date,phone,email,employment_status</code>
                    <p>Maksimal 5 MB dan 1.000 guru per berkas. Data diperiksa sebelum disimpan. Impor tidak membuat akun login.</p>
                    <a href="/akademik/guru/template?format=xlsx" class="inline-block mt-2 font-medium text-indigo-600 underline">Template Excel</a>
                    <a href="/akademik/guru/template" class="inline-block mt-2 font-medium text-indigo-600 underline">Unduh Berkas Template CSV</a>
                </div>

                <form @submit.prevent="submitImport" class="space-y-4">
                    <div>
                        <label for="teacher-import-file" class="block text-xs font-semibold text-slate-700 mb-1">Pilih berkas Excel / CSV *</label>
                        <input id="teacher-import-file" type="file" accept=".csv,.txt,.xlsx" @change="handleFileUpload" required class="w-full text-sm border rounded-lg p-2" />
                        <div v-if="importForm.errors.csv_file" class="text-xs text-red-600 mt-1 font-medium">{{ importForm.errors.csv_file }}</div>
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
.teacher-overlay { position: fixed; inset: 0; z-index: 80; display: flex; justify-content: flex-end; background: rgb(19 32 51 / 45%); backdrop-filter: blur(3px); }
.teacher-drawer { width: min(560px, 100%); height: 100%; padding: clamp(18px, 3vw, 30px); overflow-y: auto; overscroll-behavior: contain; background: var(--surface); color: var(--ink); box-shadow: -18px 0 40px rgb(19 32 51 / 15%); }
.teacher-drawer input:not([type="checkbox"]), .teacher-drawer select { max-width: 100%; color: var(--ink); background: var(--surface); border-color: var(--line-strong); }
.teacher-drawer .text-slate-900, .teacher-drawer .text-slate-700 { color: var(--ink); }
.teacher-drawer .text-slate-600, .teacher-drawer .text-slate-500, .teacher-drawer .text-slate-400 { color: var(--ink-soft); }
.teacher-drawer button:focus-visible, .teacher-drawer input:focus-visible, .teacher-drawer select:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
.teacher-close { display: grid; place-items: center; width: 44px; height: 44px; flex: 0 0 auto; border-radius: 9px; }
.teacher-list-heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.15rem 1.25rem; border-bottom: 1px solid var(--line); }
.teacher-list-heading h2 { margin: 0; color: var(--ink); font-size: 1rem; font-weight: 700; }
.teacher-list-heading span { padding: .25rem .65rem; color: var(--primary); background: var(--primary-soft); border-radius: 8px; font-size: .75rem; }
.teacher-action-excel { color: var(--green); background: var(--green-soft); }
.teacher-action-excel svg { width: 17px; height: 17px; }
.teacher-page { min-width: 0; }
.teacher-page :deep(.page-heading) { margin-bottom: 0; }
.teacher-page-kicker { margin: 0 0 .3rem; color: var(--primary); font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.teacher-page-title { margin: 0; color: var(--ink); font-size: clamp(1.55rem, 2.4vw, 2rem); font-weight: 800; letter-spacing: -.035em; line-height: 1.12; }
.teacher-page-summary { max-width: 620px; margin: .45rem 0 0; color: var(--ink-soft); font-size: .88rem; line-height: 1.55; }
.teacher-header-actions { display: flex; align-items: center; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
.teacher-action-button { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; gap: .45rem; padding: .65rem .9rem; color: var(--ink-soft); background: var(--surface); border: 1px solid var(--line-strong); border-radius: .65rem; font-size: .8rem; font-weight: 700; text-decoration: none; white-space: nowrap; transition: background .16s ease, border-color .16s ease, color .16s ease; }
.teacher-action-button:hover { color: var(--primary); background: var(--primary-soft); border-color: var(--primary); }
.teacher-action-add { color: #fff; background: var(--primary); border-color: var(--primary); box-shadow: 0 5px 12px rgb(50 105 188 / 16%); }
.teacher-action-add:hover { color: #fff; background: var(--primary-strong); }
.teacher-action-import { color: var(--primary); border-color: var(--primary); }
.teacher-summary-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; }
.teacher-stat { display: grid; gap: .3rem; min-width: 0; padding: 1rem 1.2rem; border-top: 3px solid transparent; }
.teacher-stat span { color: var(--ink-soft); font-size: .82rem; font-weight: 600; }
.teacher-stat strong { color: var(--ink); font-size: 1.75rem; font-weight: 800; line-height: 1.1; }
.teacher-stat small { color: var(--ink-faint); font-size: .72rem; line-height: 1.4; }
.teacher-stat-total { border-top-color: #3269bc; background: color-mix(in srgb, #3269bc 7%, var(--surface)); }
.teacher-stat-active { border-top-color: #3d9b78; background: color-mix(in srgb, #3d9b78 7%, var(--surface)); }
.teacher-stat-inactive { border-top-color: #ad7c2b; background: color-mix(in srgb, #ad7c2b 7%, var(--surface)); }
.teacher-retirement-summary { display: grid; grid-template-columns: 1.4fr 1fr 1fr; gap: 1px; overflow: hidden; border: 1px solid var(--line); border-radius: var(--radius); background: var(--line); }
.teacher-retirement-summary > div { display: grid; gap: .2rem; padding: .78rem 1rem; background: var(--surface); }
.teacher-retirement-summary span { color: var(--ink-soft); font-size: .76rem; }
.teacher-retirement-summary strong { color: var(--ink); font-size: 1.15rem; line-height: 1.1; }
.teacher-retirement-summary strong.is-warning { color: #9a6508; }
.teacher-filterbar { gap: .75rem; }
.teacher-filterbar :deep(.table-search) { flex: 1 1 280px; min-width: 230px; }
.teacher-filter-controls { display: flex; flex: 1 1 auto; flex-wrap: wrap; gap: .5rem; }
.teacher-filter-select { min-height: 44px; flex: 1 1 160px; min-width: 150px; padding: .65rem .75rem; border: 1px solid var(--line-strong); border-radius: .65rem; background: var(--surface); color: var(--ink); font-size: .8rem; }
.teacher-filter-submit { color: #fff; background: var(--primary); border-color: var(--primary); }
.teacher-filter-submit:hover { color: #fff; background: var(--primary-strong); }
.teacher-table { min-width: 1160px; }
.teacher-table thead { background: var(--surface-subtle); color: var(--ink-faint); }
.teacher-table thead th { border-bottom: 1px solid var(--line); font-size: .72rem; font-weight: 800; letter-spacing: .04em; line-height: 1.3; white-space: nowrap; }
.teacher-table tbody tr { background: var(--surface); border-bottom: 1px solid var(--line); transition: background-color .18s ease, box-shadow .18s ease; }
.teacher-table tbody tr:hover { background: color-mix(in srgb, var(--primary-soft) 48%, var(--surface)); box-shadow: inset 3px 0 0 var(--primary); }
.teacher-table td { vertical-align: middle; }
.teacher-actions-heading, .teacher-actions-cell { position: sticky; right: 0; width: 174px; min-width: 174px; }
.teacher-actions-heading { z-index: 3; background: var(--surface-subtle); box-shadow: -8px 0 14px rgb(17 38 64 / 12%); }
.teacher-actions-cell { z-index: 2; background: var(--surface); box-shadow: -8px 0 14px rgb(24 48 78 / 8%); }
.teacher-table tbody tr:hover .teacher-actions-cell { background: var(--surface-subtle) !important; }
.teacher-account-status { display: inline-block; padding: .25rem .55rem; border-radius: 999px; color: var(--ink-soft); background: var(--surface-strong); font-size: .72rem; line-height: 1.25; white-space: nowrap; }
.teacher-account-status.is-changed { color: var(--green); background: var(--green-soft); }
.teacher-account-status.is-default { color: #9a6508; background: #fff7dc; }
.teacher-account-status.is-no_account, .teacher-account-status.is-unavailable { color: var(--ink-soft); background: var(--surface-strong); }
.teacher-row-actions { display: flex; align-items: center; gap: .4rem; margin-top: .55rem; white-space: nowrap; }
.teacher-row-action { display: inline-grid; width: 36px; height: 36px; place-items: center; border: 1px solid transparent; border-radius: .55rem; transition: background .16s ease, box-shadow .16s ease, transform .16s ease; }
.teacher-row-action svg { width: 15px; height: 15px; stroke-width: 2; }
.teacher-row-action:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 5px 10px rgb(24 48 78 / 12%); }
.teacher-row-action:disabled { cursor: not-allowed; opacity: .42; }
.teacher-row-action:focus-visible, .teacher-action-button:focus-visible { outline: 3px solid rgb(50 105 188 / 28%); outline-offset: 2px; }
.teacher-row-action-edit { color: var(--primary-strong); background: var(--primary-soft); border-color: color-mix(in srgb, var(--primary) 22%, var(--line)); }.teacher-row-action-edit svg { color: var(--edit); }
.teacher-row-action-reset { color: #9a6508; background: #fff7dc; border-color: #ecd594; }
.teacher-row-action-delete { color: var(--delete); background: var(--delete-soft); border-color: var(--delete-line); }
.teacher-mobile-list { display: none; }
@media (max-width: 1100px) { .teacher-table { min-width: 1040px; } .teacher-filter-controls { flex-basis: 100%; } }
@media (max-width: 720px) {
    .teacher-header-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); width: 100%; }
    .teacher-action-add { grid-column: 1 / -1; }
    .teacher-action-button { width: 100%; }
    .teacher-retirement-summary { grid-template-columns: 1fr 1fr; }
    .teacher-retirement-summary > div:first-child { grid-column: 1 / -1; }
    .teacher-filterbar { align-items: stretch; }
    .teacher-filterbar :deep(.table-search) { min-width: 0; }
    .teacher-filter-controls { display: grid; grid-template-columns: 1fr 1fr; }
    .teacher-filter-select, .teacher-filter-controls .secondary-button { width: 100%; min-width: 0; }
    .teacher-filter-controls .teacher-filter-select:last-of-type, .teacher-filter-submit { grid-column: 1 / -1; }
    .teacher-filter-controls .secondary-button { justify-content: center; }
    .teacher-filter-controls .secondary-button:first-of-type { grid-column: 1; }
    .teacher-filter-controls .teacher-filter-submit { grid-column: 2; }
    .teacher-table-card :deep(.table-scroll) { display: none; }
    .teacher-list-heading { display: none; }
    .teacher-table-card { background: transparent; border: 0; box-shadow: none; }
    .teacher-mobile-list { display: grid; gap: 12px; }
    .teacher-mobile-card { padding: 1rem; background: var(--surface); border: 1px solid var(--line); border-radius: 12px; }
    .teacher-mobile-top { display: flex; align-items: flex-start; justify-content: space-between; gap: .7rem; }
    .teacher-mobile-top h2 { margin: 0; color: var(--ink); font-size: .92rem; font-weight: 800; line-height: 1.35; }
    .teacher-mobile-top p { margin: .25rem 0 0; color: var(--ink-soft); font-size: .72rem; line-height: 1.45; }
    .teacher-mobile-status { flex: 0 0 auto; padding: .25rem .5rem; color: var(--ink-soft); background: var(--surface-strong); border-radius: 999px; font-size: .68rem; font-weight: 700; }
    .teacher-mobile-status.is-active { color: var(--green); background: var(--green-soft); }
    .teacher-mobile-details { display: flex; flex-wrap: wrap; gap: .3rem .75rem; margin-top: .75rem; color: var(--ink-soft); font-size: .72rem; line-height: 1.45; }
    .teacher-mobile-details > span:last-child { flex-basis: 100%; }
    .teacher-mobile-password { margin: .7rem 0 0; color: var(--ink-soft); font-size: .72rem; }
    .teacher-mobile-actions { display: flex; gap: .5rem; margin-top: .8rem; }
    .teacher-mobile-actions button { display: grid; place-items: center; width: 44px; min-height: 44px; padding: 0; color: var(--primary); background: var(--primary-soft); border: 1px solid var(--line); border-radius: .55rem; }
    .teacher-mobile-actions svg { width: 19px; height: 19px; }
    .teacher-mobile-actions button:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
    .teacher-mobile-actions button:disabled { cursor: not-allowed; opacity: .45; }
    .teacher-mobile-actions .teacher-mobile-delete { margin-left: auto; color: var(--delete); background: var(--delete-soft); }
    .teacher-mobile-empty { padding: 1.5rem 1rem; color: var(--ink-soft); font-size: .8rem; text-align: center; }
}
@media (max-width: 640px) {
    .teacher-page :deep(.page-heading) { align-items: stretch; flex-direction: column; gap: 1rem; }
    .teacher-summary-grid { grid-template-columns: 1fr 1fr; }
    .teacher-filter-controls { grid-template-columns: 1fr; }
    .teacher-filter-controls .teacher-filter-select:last-of-type, .teacher-filter-controls .secondary-button:first-of-type, .teacher-filter-submit { grid-column: 1; }
}
@media (max-width: 600px) { .teacher-overlay { align-items: flex-end; }.teacher-drawer { height: min(92dvh, 900px); border-radius: 18px 18px 0 0; padding-bottom: max(24px, env(safe-area-inset-bottom)); }.teacher-drawer .grid-cols-2 { grid-template-columns: 1fr; } }
</style>
