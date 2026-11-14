<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

interface QuickDetail {
    nik: string | null;
    kk_number: string | null;
    father_name: string | null;
    mother_name: string | null;
    previous_school: string | null;
    diploma_number: string | null;
    accepted_grade: string | null;
    accepted_date: string | null;
}

interface StudentRow {
    id: number;
    name: string;
    nis: string;
    nisn: string | null;
    gender: 'L' | 'P';
    status: string;
    parent_name: string | null;
    class_name: string | null;
    completeness_status?: 'complete' | 'partial' | 'empty';
    completeness_percent?: number;
    filled_checkpoints?: number;
    total_checkpoints?: number;
    quick_detail?: QuickDetail;
}

interface PageLink { url: string | null; label: string; active: boolean }
interface Paginator { data: StudentRow[]; links: PageLink[]; total: number; from: number | null; to: number | null }
interface SchoolClass { id: number; name: string; grade_level: number }

const props = defineProps<{
    students: Paginator;
    classes: SchoolClass[];
    active_term: { name: string; academic_year: string } | null;
    summary?: { total: number; complete: number; incomplete: number };
    filters: { search: string; status: string; class_group_id: string; completeness?: string };
}>();

const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    class_group_id: props.filters.class_group_id || '',
    completeness: props.filters.completeness || '',
});

const statusLabels: Record<string, string> = {
    active: 'Aktif',
    graduated: 'Lulus',
    transferred: 'Pindah',
    dropped_out: 'Keluar',
};

const completenessLabels: Record<string, string> = {
    complete: 'Lengkap',
    partial: 'Sebagian',
    empty: 'Belum diisi',
};

const showImportPanel = ref(false);
const importForm = useForm<{ file: File | null }>({ file: null });

const editingStudentId = ref<number | null>(null);
const quickForm = useForm({
    nik: '',
    kk_number: '',
    father_name: '',
    mother_name: '',
    previous_school: '',
    diploma_number: '',
    accepted_grade: '',
    accepted_date: '',
});

const exportQueryString = computed(() => {
    const params = new URLSearchParams();
    if (filters.search) params.set('search', filters.search);
    if (filters.status) params.set('status', filters.status);
    if (filters.class_group_id) params.set('class_group_id', filters.class_group_id);
    if (filters.completeness) params.set('completeness', filters.completeness);
    const qs = params.toString();
    return qs ? `?${qs}` : '';
});

function applyFilters() {
    router.get('/akademik/buku-induk', filters, { preserveState: true, preserveScroll: true, replace: true });
}

function resetFilters() {
    filters.search = '';
    filters.status = '';
    filters.class_group_id = '';
    filters.completeness = '';
    applyFilters();
}

function submitImport() {
    if (!importForm.file) return;
    importForm.post('/akademik/buku-induk/impor', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            importForm.reset('file');
            showImportPanel.value = false;
        },
    });
}

function toggleQuickEdit(student: StudentRow) {
    if (editingStudentId.value === student.id) {
        editingStudentId.value = null;
        return;
    }
    editingStudentId.value = student.id;
    quickForm.clearErrors();
    quickForm.nik = student.quick_detail?.nik || '';
    quickForm.kk_number = student.quick_detail?.kk_number || '';
    quickForm.father_name = student.quick_detail?.father_name || '';
    quickForm.mother_name = student.quick_detail?.mother_name || '';
    quickForm.previous_school = student.quick_detail?.previous_school || '';
    quickForm.diploma_number = student.quick_detail?.diploma_number || '';
    quickForm.accepted_grade = student.quick_detail?.accepted_grade || '';
    quickForm.accepted_date = student.quick_detail?.accepted_date || '';
}

function saveQuickEdit(studentId: number) {
    quickForm.put(`/akademik/buku-induk/${studentId}/detail`, {
        preserveScroll: true,
        onSuccess: () => {
            editingStudentId.value = null;
        },
    });
}
</script>

<template>
    <AppLayout title="Buku Induk Siswa">
        <Head title="Buku Induk Siswa" />
        <main class="view ledger-page space-y-5">
            <section class="page-heading compact-heading ledger-heading">
                <div>
                    <p class="ledger-kicker">Akademik · Data kesiswaan</p>
                    <h1 class="ledger-title">Buku Induk Siswa</h1>
                    <p class="ledger-summary">Kelola biodata buku induk standar, kelengkapan dokumen, impor/ekspor Excel massal, dan cetak buku induk resmi.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div v-if="summary" class="ledger-term-card">
                        <span>Kelengkapan Buku Induk</span>
                        <strong>{{ summary.complete }} lengkap · {{ summary.incomplete }} belum lengkap</strong>
                    </div>
                    <div v-if="active_term" class="ledger-term-card">
                        <span>Semester aktif</span>
                        <strong>{{ active_term.academic_year }} · {{ active_term.name }}</strong>
                    </div>
                </div>
            </section>

            <section class="panel ledger-panel">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3">
                    <div class="text-sm font-semibold text-slate-700">
                        Operasi Massal &amp; Cetakan Buku Induk
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <AppButton
                            variant="secondary"
                            as="a"
                            class="secondary-button"
                            :href="`/akademik/buku-induk/ekspor/xlsx${exportQueryString}`"
                        >
                            Ekspor / Template Excel (.xlsx)
                        </AppButton>
                        <AppButton
                            variant="secondary"
                            type="button"
                            class="secondary-button"
                            @click="showImportPanel = !showImportPanel"
                        >
                            {{ showImportPanel ? 'Tutup Impor Excel' : 'Impor / Update Massal Excel' }}
                        </AppButton>
                        <AppButton
                            v-if="students.data.length > 0"
                            variant="primary"
                            as="a"
                            class="primary-button"
                            target="_blank"
                            rel="noopener noreferrer"
                            :href="`/akademik/buku-induk/cetak-massal/pdf${exportQueryString}`"
                        >
                            Pratinjau PDF Massal (Filter Aktif)
                        </AppButton>
                    </div>
                </div>

                <form
                    v-if="showImportPanel"
                    class="border-b border-slate-200 bg-blue-50/60 p-4"
                    @submit.prevent="submitImport"
                >
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div class="min-w-[260px] flex-1">
                            <p class="text-sm font-bold text-slate-900">Impor / Perbarui Buku Induk Massal (.xlsx / .csv)</p>
                            <p class="mt-0.5 text-xs text-slate-600">
                                Unduh terlebih dahulu file dari tombol <strong>Ekspor / Template Excel (.xlsx)</strong>, lengkapi kolom NIK, No. KK, Orang Tua, Sekolah Asal, atau Ijazah, lalu unggah kembali. Pencocokan otomatis menggunakan <strong>NIS</strong>.
                            </p>
                            <input
                                type="file"
                                accept=".xlsx,.csv,.txt"
                                required
                                class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-800 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1"
                                @input="importForm.file = ($event.target as HTMLInputElement).files?.[0] || null"
                            />
                            <p v-if="importForm.errors.file" class="mt-1 text-xs font-semibold text-red-700">{{ importForm.errors.file }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <AppButton variant="primary" type="submit" :disabled="importForm.processing || !importForm.file">
                                {{ importForm.processing ? 'Memproses…' : 'Unggah & Perbarui Data' }}
                            </AppButton>
                            <AppButton variant="secondary" type="button" @click="showImportPanel = false">Batal</AppButton>
                        </div>
                    </div>
                </form>

                <form class="ledger-filterbar" @submit.prevent="applyFilters">
                    <label class="ledger-filter-field">
                        Cari siswa
                        <input v-model="filters.search" class="search-control" type="search" placeholder="Nama, NIS, atau NISN" />
                    </label>
                    <label class="ledger-filter-field">
                        Status
                        <select v-model="filters.status">
                            <option value="">Semua status</option>
                            <option value="active">Aktif</option>
                            <option value="graduated">Lulus</option>
                            <option value="transferred">Pindah</option>
                            <option value="dropped_out">Keluar</option>
                        </select>
                    </label>
                    <label class="ledger-filter-field">
                        Rombel semester aktif
                        <select v-model="filters.class_group_id" :disabled="!active_term">
                            <option value="">Semua rombel</option>
                            <option v-for="schoolClass in classes" :key="schoolClass.id" :value="String(schoolClass.id)">{{ schoolClass.name }}</option>
                        </select>
                    </label>
                    <label class="ledger-filter-field">
                        Kelengkapan data
                        <select v-model="filters.completeness">
                            <option value="">Semua data</option>
                            <option value="incomplete">Belum lengkap</option>
                            <option value="complete">Sudah lengkap</option>
                        </select>
                    </label>
                    <div class="ledger-filter-actions">
                        <AppButton variant="primary" class="primary-button" type="submit">Terapkan</AppButton>
                        <AppButton variant="secondary" class="secondary-button" type="button" @click="resetFilters">Reset filter</AppButton>
                    </div>
                </form>

                <div class="overflow-x-auto table-scroll" tabindex="0" role="region" aria-label="Tabel data">
                    <table class="app-table ledger-table min-w-[860px] w-full border-collapse text-left text-sm">
                        <thead>
                            <tr>
                                <th scope="col" class="px-5 py-3">Nama siswa</th>
                                <th scope="col" class="px-5 py-3">NIS / NISN</th>
                                <th scope="col" class="px-5 py-3">Rombel aktif</th>
                                <th scope="col" class="px-5 py-3 text-center">Status</th>
                                <th scope="col" class="px-5 py-3 text-center">Kelengkapan</th>
                                <th scope="col" class="px-5 py-3 text-center">Buku induk</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <template v-for="student in students.data" :key="student.id">
                                <tr>
                                    <td class="px-5 py-3.5">
                                        <strong class="block font-semibold text-slate-900">{{ student.name }}</strong>
                                        <span class="mt-0.5 block text-meta text-slate-600">
                                            {{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                            <span v-if="student.parent_name"> · Wali: {{ student.parent_name }}</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-sm text-slate-700">
                                        {{ student.nis }}
                                        <span class="mt-0.5 block text-meta text-slate-600">NISN {{ student.nisn || '—' }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-700">{{ student.class_name || 'Belum ada rombel aktif' }}</td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span
                                            class="rounded-full px-2.5 py-1 text-meta font-semibold"
                                            :class="student.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                                        >
                                            {{ statusLabels[student.status] || student.status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-meta font-semibold"
                                            :class="student.completeness_status === 'complete'
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : student.completeness_status === 'partial'
                                                    ? 'bg-amber-50 text-amber-800'
                                                    : 'bg-slate-100 text-slate-600'"
                                        >
                                            {{ completenessLabels[student.completeness_status || 'empty'] }}
                                            <span v-if="student.completeness_percent !== undefined">({{ student.completeness_percent }}%)</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <div class="inline-flex flex-wrap items-center justify-center gap-1.5">
                                            <Link
                                                class="ledger-profile-action ui-button ui-button--secondary ui-button--md"
                                                :href="`/akademik/buku-induk/${student.id}`"
                                            >
                                                Lihat profil
                                            </Link>
                                            <button
                                                type="button"
                                                class="ui-button ui-button--secondary ui-button--md text-xs"
                                                @click="toggleQuickEdit(student)"
                                            >
                                                {{ editingStudentId === student.id ? 'Tutup' : 'Isi cepat' }}
                                            </button>
                                            <a
                                                class="ui-button ui-button--secondary ui-button--md text-xs"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                :href="`/akademik/buku-induk/${student.id}/pdf`"
                                            >
                                                Pratinjau PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="editingStudentId === student.id" class="bg-slate-50/90">
                                    <td colspan="6" class="px-5 py-4">
                                        <form class="grid gap-3 sm:grid-cols-4" @submit.prevent="saveQuickEdit(student.id)">
                                            <div class="sm:col-span-4 flex items-center justify-between border-b border-slate-200 pb-2">
                                                <strong class="text-xs uppercase tracking-wide text-slate-700">
                                                    Isi Cepat Buku Induk · {{ student.name }} (NIS {{ student.nis }})
                                                </strong>
                                                <Link :href="`/akademik/buku-induk/${student.id}`" class="text-xs font-semibold text-blue-700 hover:underline">
                                                    Buka formulir lengkap &amp; dokumen →
                                                </Link>
                                            </div>
                                            <label class="grid gap-1 text-xs font-semibold text-slate-700">
                                                NIK Siswa (16 digit)
                                                <input v-model="quickForm.nik" maxlength="16" inputmode="numeric" class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-sm" />
                                                <span v-if="quickForm.errors.nik" class="text-xs text-red-700">{{ quickForm.errors.nik }}</span>
                                            </label>
                                            <label class="grid gap-1 text-xs font-semibold text-slate-700">
                                                Nomor KK (16 digit)
                                                <input v-model="quickForm.kk_number" maxlength="16" inputmode="numeric" class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-sm" />
                                                <span v-if="quickForm.errors.kk_number" class="text-xs text-red-700">{{ quickForm.errors.kk_number }}</span>
                                            </label>
                                            <label class="grid gap-1 text-xs font-semibold text-slate-700">
                                                Nama Ayah
                                                <input v-model="quickForm.father_name" maxlength="100" class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-sm" />
                                            </label>
                                            <label class="grid gap-1 text-xs font-semibold text-slate-700">
                                                Nama Ibu
                                                <input v-model="quickForm.mother_name" maxlength="100" class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-sm" />
                                            </label>
                                            <label class="grid gap-1 text-xs font-semibold text-slate-700">
                                                Sekolah Asal
                                                <input v-model="quickForm.previous_school" maxlength="100" class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-sm" />
                                            </label>
                                            <label class="grid gap-1 text-xs font-semibold text-slate-700">
                                                Nomor Ijazah Asal
                                                <input v-model="quickForm.diploma_number" maxlength="50" class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-sm" />
                                            </label>
                                            <label class="grid gap-1 text-xs font-semibold text-slate-700">
                                                Diterima di Kelas
                                                <input v-model="quickForm.accepted_grade" maxlength="30" placeholder="Contoh: VII / X" class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-sm" />
                                            </label>
                                            <div class="flex items-end justify-end gap-2">
                                                <AppButton variant="primary" type="submit" :disabled="quickForm.processing">
                                                    {{ quickForm.processing ? 'Menyimpan…' : 'Simpan' }}
                                                </AppButton>
                                                <AppButton variant="secondary" type="button" @click="editingStudentId = null">Batal</AppButton>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="students.data.length === 0">
                                <td colspan="6" class="px-5 py-14 text-center">
                                    <strong class="block text-sm font-semibold text-slate-800">Data siswa tidak ditemukan</strong>
                                    <span class="mt-1 block text-sm text-slate-600">Ubah kata kunci atau filter untuk melihat data lainnya.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-5 py-3.5 text-meta text-slate-600">
                    <span>Menampilkan {{ students.from ?? 0 }}–{{ students.to ?? 0 }} dari {{ students.total }} siswa</span>
                    <nav class="flex flex-wrap gap-1" aria-label="Halaman daftar buku induk">
                        <template v-for="(link, index) in students.links" :key="index">
                            <span v-if="!link.url" class="rounded-md px-2.5 py-1.5 text-slate-300" v-html="link.label" />
                            <Link v-else :href="link.url" class="rounded-md border px-2.5 py-1.5 transition" :class="link.active ? 'border-blue-700 bg-blue-700 font-semibold text-white' : 'border-slate-200 text-slate-600 hover:bg-slate-50'" v-html="link.label" />
                        </template>
                    </nav>
                </footer>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.ledger-page { min-width: 0; }
.ledger-heading { margin-bottom: 0; }
.ledger-kicker { margin: 0 0 .3rem; color: var(--primary); font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .08em; text-transform: uppercase; }
.ledger-title { margin: 0; color: var(--ink); font-size: clamp(1.55rem, 2.4vw, 2rem); font-weight: var(--weight-bold); letter-spacing: -.035em; line-height: 1.12; }
.ledger-summary { max-width: 620px; margin: .45rem 0 0; color: var(--ink-soft); font-size: var(--type-md); line-height: 1.55; }
.ledger-term-card { display: grid; gap: .3rem; min-width: 210px; padding: .8rem 1rem; border: 1px solid var(--line); border-radius: .7rem; background: var(--surface); text-align: center; }
.ledger-term-card span { color: var(--ink-faint); font-size: var(--type-meta); }
.ledger-term-card strong { color: var(--ink); font-size: var(--type-meta); }
.ledger-panel { overflow: hidden; }
.ledger-filterbar { display: grid; grid-template-columns: minmax(200px, 1fr) minmax(140px, .65fr) minmax(170px, .8fr) minmax(160px, .75fr) auto; align-items: end; gap: .75rem; padding: 1rem; border-bottom: 1px solid var(--line); background: var(--surface-subtle); }
.ledger-filter-field { display: grid; gap: .4rem; min-width: 0; color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.ledger-filter-field input, .ledger-filter-field select { width: 100%; min-height: 42px; padding: .62rem .72rem; border: 1px solid var(--line-strong); border-radius: .65rem; outline: 0; background: var(--surface); color: var(--ink); font-size: var(--type-control); font-weight: var(--weight-medium); }
.ledger-filter-field input:focus, .ledger-filter-field select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 18%, transparent); }
.ledger-filter-field select:disabled { cursor: not-allowed; opacity: .58; }
.ledger-filter-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
.ledger-table { min-width: 860px; }
.ledger-table thead { background: var(--surface-subtle); color: var(--ink-faint); }
.ledger-table thead th { border-bottom: 1px solid var(--line); font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .04em; line-height: 1.3; white-space: nowrap; }
.ledger-table tbody tr { background: var(--surface); border-bottom: 1px solid var(--line); transition: background-color .18s ease, box-shadow .18s ease; }
.ledger-table tbody tr:hover { background: color-mix(in srgb, var(--primary-soft) 48%, var(--surface)); box-shadow: inset 3px 0 0 var(--primary); }
.ledger-table td { vertical-align: middle; }
@media (max-width: 980px) { .ledger-filterbar { grid-template-columns: repeat(2, minmax(0, 1fr)); }.ledger-filter-field:first-child, .ledger-filter-actions { grid-column: 1 / -1; }.ledger-filter-actions { justify-content: flex-end; } }
@media (max-width: 640px) { .ledger-heading { align-items: stretch; flex-direction: column; gap: 1rem; }.ledger-term-card { min-width: 0; text-align: center; }.ledger-filterbar { grid-template-columns: minmax(0, 1fr); }.ledger-filter-field:first-child, .ledger-filter-actions { grid-column: auto; }.ledger-filter-actions { display: grid; grid-template-columns: 1fr 1fr; }.ledger-filter-actions button { width: 100%; } }
</style>
