<script setup lang="ts">
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
</script>

<template>
    <AppLayout title="Buku Induk Siswa">
        <Head title="Buku Induk Siswa" />
        <main class="view space-y-5">
            <section class="hero-card">
                <div>
                    <p class="eyebrow">Data kesiswaan</p>
                    <h1>Buku Induk Siswa</h1>
                    <p>Ringkasan identitas dan riwayat kelas dari data akademik sekolah.</p>
                </div>
                <div v-if="active_term" class="rounded-xl border border-slate-200 bg-white/80 px-4 py-3 text-right shadow-sm">
                    <span class="block text-xs font-medium text-slate-500">Semester aktif</span>
                    <strong class="mt-1 block text-sm text-slate-800">{{ active_term.academic_year }} · {{ active_term.name }}</strong>
                </div>
            </section>

            <section class="panel overflow-hidden">
                <form class="grid gap-3 border-b border-slate-200 bg-slate-50/70 p-4 sm:grid-cols-[minmax(220px,1fr)_180px_220px_auto] sm:items-end" @submit.prevent="applyFilters">
                    <label class="grid gap-1.5 text-xs font-semibold text-slate-600">
                        Cari siswa
                        <input v-model="filters.search" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm font-normal text-slate-800 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100" placeholder="Nama, NIS, atau NISN" />
                    </label>
                    <label class="grid gap-1.5 text-xs font-semibold text-slate-600">
                        Status
                        <select v-model="filters.status" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm font-normal text-slate-800 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            <option value="">Semua status</option>
                            <option value="active">Aktif</option>
                            <option value="graduated">Lulus</option>
                            <option value="transferred">Pindah</option>
                            <option value="dropped_out">Keluar</option>
                        </select>
                    </label>
                    <label class="grid gap-1.5 text-xs font-semibold text-slate-600">
                        Rombel semester aktif
                        <select v-model="filters.class_group_id" :disabled="!active_term" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm font-normal text-slate-800 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100">
                            <option value="">Semua rombel</option>
                            <option v-for="schoolClass in classes" :key="schoolClass.id" :value="String(schoolClass.id)">{{ schoolClass.name }}</option>
                        </select>
                    </label>
                    <button class="primary-button min-h-10" type="submit">Terapkan</button>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-[760px] w-full border-collapse text-left text-sm">
                        <thead class="bg-slate-800 text-white">
                            <tr>
                                <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide">Nama siswa</th>
                                <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide">NIS / NISN</th>
                                <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide">Rombel aktif</th>
                                <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide">Status</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide">Buku induk</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="student in students.data" :key="student.id" class="bg-white transition hover:bg-slate-50">
                                <td class="px-5 py-3.5">
                                    <strong class="block font-semibold text-slate-900">{{ student.name }}</strong>
                                    <span class="mt-0.5 block text-xs text-slate-500">{{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}<span v-if="student.parent_name"> · Wali: {{ student.parent_name }}</span></span>
                                </td>
                                <td class="px-5 py-3.5 text-sm text-slate-700">{{ student.nis }}<span class="mt-0.5 block text-xs text-slate-500">NISN {{ student.nisn || '—' }}</span></td>
                                <td class="px-5 py-3.5 text-slate-700">{{ student.class_name || 'Belum ada rombel aktif' }}</td>
                                <td class="px-5 py-3.5"><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="student.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ statusLabels[student.status] || student.status }}</span></td>
                                <td class="px-5 py-3.5 text-right"><Link class="inline-flex min-h-9 items-center rounded-lg border border-blue-200 bg-blue-50 px-3 text-xs font-semibold text-blue-800 transition hover:bg-blue-100" :href="`/akademik/buku-induk/${student.id}`">Lihat profil</Link></td>
                            </tr>
                            <tr v-if="students.data.length === 0">
                                <td colspan="5" class="px-5 py-14 text-center">
                                    <strong class="block text-sm font-semibold text-slate-800">Data siswa tidak ditemukan</strong>
                                    <span class="mt-1 block text-sm text-slate-500">Ubah kata kunci atau filter untuk melihat data lainnya.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-5 py-3.5 text-xs text-slate-500">
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
