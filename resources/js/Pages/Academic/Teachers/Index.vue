<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';
import type { Teacher } from '../../../types';

const props = defineProps<{
    teachers: {
        data: Teacher[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    };
    filters: {
        search?: string;
        employment_status?: string;
    };
}>();

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.employment_status || '');

const showAddModal = ref(false);
const showImportModal = ref(false);

const applyFilters = () => {
    router.get(
        '/akademik/guru',
        {
            search: search.value || undefined,
            employment_status: selectedStatus.value || undefined,
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
    employment_status: 'PNS',
    is_active: true,
});

const submitAdd = () => {
    form.post('/akademik/guru', {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        },
    });
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
    importForm.post('/akademik/guru/import', {
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

        <div class="space-y-6">
            <!-- Header bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pendidik & Tenaga Kependidikan</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Kelola data guru, status kepegawaian, dan penugasan wali kelas.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="showImportModal = true"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-xs"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Impor CSV
                    </button>
                    <button
                        type="button"
                        @click="showAddModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition shadow-xs"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Guru
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex flex-col md:flex-row gap-3">
                <div class="flex-1 relative">
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Cari nama, NIP, atau email... (tekan enter)"
                        class="w-full px-3.5 py-2 pl-10 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                    />
                    <svg class="w-5 h-5 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div class="flex gap-2">
                    <select
                        v-model="selectedStatus"
                        @change="applyFilters"
                        class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white"
                    >
                        <option value="">Semua Status Kepegawaian</option>
                        <option value="PNS">PNS</option>
                        <option value="PPPK">PPPK</option>
                        <option value="GTT">GTT / Honorer</option>
                    </select>

                    <button
                        type="button"
                        @click="applyFilters"
                        class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition"
                    >
                        Filter
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-600">
                            <tr>
                                <th class="px-5 py-3.5">Nama & NIP</th>
                                <th class="px-5 py-3.5">L/P</th>
                                <th class="px-5 py-3.5">Kontak</th>
                                <th class="px-5 py-3.5">Status Pegawai</th>
                                <th class="px-5 py-3.5">Wali Kelas</th>
                                <th class="px-5 py-3.5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="t in teachers.data" :key="t.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-5 py-3.5 font-medium text-slate-900">
                                    <div>{{ t.name }}</div>
                                    <div class="text-xs text-slate-400 font-normal">{{ t.nip || '-' }}</div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span :class="t.gender === 'L' ? 'text-blue-700 bg-blue-50 px-2 py-0.5 rounded-sm font-semibold' : 'text-pink-700 bg-pink-50 px-2 py-0.5 rounded-sm font-semibold'">
                                        {{ t.gender }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-xs text-slate-500">
                                    <div>{{ t.email || '-' }}</div>
                                    <div class="text-slate-400">{{ t.phone || '' }}</div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ t.employment_status || 'GTT' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span v-if="t.homeroom_class_groups && t.homeroom_class_groups.length > 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        Kelas {{ t.homeroom_class_groups[0].name }}
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
                            </tr>
                            <tr v-if="teachers.data.length === 0">
                                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                    Tidak ada data guru yang cocok dengan filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-3.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <div>Total {{ teachers.total }} guru</div>
                    <div class="flex gap-1">
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
        <div v-if="showAddModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-slate-900">Tambah Guru Baru</h3>
                    <button type="button" @click="showAddModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form @submit.prevent="submitAdd" class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">NIP</label>
                            <input v-model="form.nip" type="text" class="w-full px-3 py-2 text-sm border rounded-lg" />
                            <div v-if="form.errors.nip" class="text-xs text-red-600 mt-1">{{ form.errors.nip }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Kelamin *</label>
                            <select v-model="form.gender" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap & Gelar *</label>
                        <input v-model="form.name" type="text" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        <div v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                            <input v-model="form.email" type="email" class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">No. HP / WhatsApp</label>
                            <input v-model="form.phone" type="text" class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Status Kepegawaian</label>
                        <select v-model="form.employment_status" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                            <option value="PNS">PNS</option>
                            <option value="PPPK">PPPK</option>
                            <option value="GTT">GTT / Honorer</option>
                        </select>
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

        <!-- Modal Impor CSV Guru -->
        <div v-if="showImportModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-slate-900">Impor Massal Pendidik (CSV)</h3>
                    <button type="button" @click="showImportModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="p-3 bg-indigo-50 border border-indigo-100 rounded-lg text-xs text-indigo-800 space-y-1">
                    <div class="font-semibold">Format Kolom Header:</div>
                    <code class="block font-mono text-[11px] text-indigo-900">nip,name,gender,phone,email,employment_status</code>
                    <a href="/akademik/guru/template" class="inline-block mt-2 font-medium text-indigo-600 underline">Unduh Berkas Template CSV</a>
                </div>

                <form @submit.prevent="submitImport" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Berkas CSV *</label>
                        <input type="file" accept=".csv,.txt" @change="handleFileUpload" required class="w-full text-sm border rounded-lg p-2" />
                        <div v-if="importForm.errors.csv_file" class="text-xs text-red-600 mt-1 font-medium">{{ importForm.errors.csv_file }}</div>
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
