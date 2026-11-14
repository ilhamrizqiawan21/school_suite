<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

interface StudentRow {
    id: number;
    name: string;
    nis: string;
    nisn: string | null;
    gender: 'L' | 'P';
    status: string;
    parent_name: string | null;
    class_name: string | null;
}

interface PageLink { url: string | null; label: string; active: boolean }
interface Paginator { data: StudentRow[]; links: PageLink[]; total: number; from: number | null; to: number | null }
interface SchoolClass { id: number; name: string; grade_level: number }

const props = defineProps<{
    students: Paginator;
    classes: SchoolClass[];
    active_term: { name: string; academic_year: string } | null;
    filters: { search: string; status: string; class_group_id: string };
}>();

const filters = reactive({ ...props.filters });
const statusLabels: Record<string, string> = {
    active: 'Aktif',
    graduated: 'Lulus',
    transferred: 'Pindah',
    dropped_out: 'Keluar',
};

function applyFilters() {
    router.get('/akademik/buku-induk', filters, { preserveState: true, preserveScroll: true, replace: true });
}
function resetFilters() {
    filters.search = '';
    filters.status = '';
    filters.class_group_id = '';
    applyFilters();
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
                    <p class="ledger-summary">Ringkasan identitas, rombel, dan riwayat akademik siswa dalam satu daftar.</p>
                </div>
                <div v-if="active_term" class="ledger-term-card">
                    <span>Semester aktif</span>
                    <strong>{{ active_term.academic_year }} · {{ active_term.name }}</strong>
                </div>
            </section>

            <section class="panel ledger-panel">
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
                    <div class="ledger-filter-actions">
                        <AppButton variant="primary" class="primary-button" type="submit">Terapkan</AppButton>
                        <AppButton variant="secondary" class="secondary-button" type="button" @click="resetFilters">Reset filter</AppButton>
                    </div>
                </form>

                <div class="overflow-x-auto table-scroll" tabindex="0" role="region" aria-label="Tabel data">
                    <table class="app-table ledger-table min-w-[760px] w-full border-collapse text-left text-sm">
                        <thead>
                            <tr>
                                <th scope="col" class="px-5 py-3">Nama siswa</th>
                                <th scope="col" class="px-5 py-3">NIS / NISN</th>
                                <th scope="col" class="px-5 py-3">Rombel aktif</th>
                                <th scope="col" class="px-5 py-3 text-center">Status</th>
                                <th scope="col" class="px-5 py-3 text-center">Buku induk</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="student in students.data" :key="student.id">
                                <td class="px-5 py-3.5">
                                    <strong class="block font-semibold text-slate-900">{{ student.name }}</strong>
                                    <span class="mt-0.5 block text-meta text-slate-600">{{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}<span v-if="student.parent_name"> · Wali: {{ student.parent_name }}</span></span>
                                </td>
                                <td class="px-5 py-3.5 text-sm text-slate-700">{{ student.nis }}<span class="mt-0.5 block text-meta text-slate-600">NISN {{ student.nisn || '—' }}</span></td>
                                <td class="px-5 py-3.5 text-slate-700">{{ student.class_name || 'Belum ada rombel aktif' }}</td>
                                <td class="px-5 py-3.5 text-center"><span class="rounded-full px-2.5 py-1 text-meta font-semibold" :class="student.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ statusLabels[student.status] || student.status }}</span></td>
                                <td class="px-5 py-3.5 text-center"><Link class="ledger-profile-action ui-button ui-button--secondary ui-button--md" :href="`/akademik/buku-induk/${student.id}`">Lihat profil</Link></td>
                            </tr>
                            <tr v-if="students.data.length === 0">
                                <td colspan="5" class="px-5 py-14 text-center">
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
.ledger-filterbar { display: grid; grid-template-columns: minmax(220px, 1fr) minmax(150px, .72fr) minmax(190px, .9fr) auto; align-items: end; gap: .75rem; padding: 1rem; border-bottom: 1px solid var(--line); background: var(--surface-subtle); }
.ledger-filter-field { display: grid; gap: .4rem; min-width: 0; color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.ledger-filter-field input, .ledger-filter-field select { width: 100%; min-height: 42px; padding: .62rem .72rem; border: 1px solid var(--line-strong); border-radius: .65rem; outline: 0; background: var(--surface); color: var(--ink); font-size: var(--type-control); font-weight: var(--weight-medium); }
.ledger-filter-field input:focus, .ledger-filter-field select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 18%, transparent); }
.ledger-filter-field select:disabled { cursor: not-allowed; opacity: .58; }
.ledger-filter-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
.ledger-table { min-width: 780px; }
.ledger-table thead { background: var(--surface-subtle); color: var(--ink-faint); }
.ledger-table thead th { border-bottom: 1px solid var(--line); font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .04em; line-height: 1.3; white-space: nowrap; }
.ledger-table tbody tr { background: var(--surface); border-bottom: 1px solid var(--line); transition: background-color .18s ease, box-shadow .18s ease; }
.ledger-table tbody tr:hover { background: color-mix(in srgb, var(--primary-soft) 48%, var(--surface)); box-shadow: inset 3px 0 0 var(--primary); }
.ledger-table td { vertical-align: middle; }
@media (max-width: 900px) { .ledger-filterbar { grid-template-columns: repeat(2, minmax(0, 1fr)); }.ledger-filter-field:first-child, .ledger-filter-actions { grid-column: 1 / -1; }.ledger-filter-actions { justify-content: flex-end; } }
@media (max-width: 640px) { .ledger-heading { align-items: stretch; flex-direction: column; gap: 1rem; }.ledger-term-card { min-width: 0; text-align: center; }.ledger-filterbar { grid-template-columns: minmax(0, 1fr); }.ledger-filter-field:first-child, .ledger-filter-actions { grid-column: auto; }.ledger-filter-actions { display: grid; grid-template-columns: 1fr 1fr; }.ledger-filter-actions button { width: 100%; } }
</style>
