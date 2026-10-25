<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface ComponentItem {
    id: number;
    name: string;
    code: string;
    weight_percentage: number;
    passing_grade: number;
    sequence: number;
}

interface SchemeItem {
    id: number;
    name: string;
    code: string;
    version: number;
    is_active: boolean;
    rounding_rule: 'half_up' | 'floor' | 'ceil';
    decimal_places: number;
    passing_grade_default: number;
    components: ComponentItem[];
}

const props = defineProps<{
    schemes: SchemeItem[];
}>();

const isCreateSchemeOpen = ref(false);
const isCreateComponentOpen = ref(false);
const selectedSchemeIdForComp = ref<number | null>(null);

const schemeForm = useForm({
    name: '',
    code: '',
    rounding_rule: 'half_up',
    decimal_places: 2,
    passing_grade_default: 75,
});

const componentForm = useForm({
    name: '',
    code: '',
    weight_percentage: 25,
    passing_grade: 75,
    sequence: 1,
});

const submitNewScheme = () => {
    schemeForm.post('/penilaian/skema', {
        preserveScroll: true,
        onSuccess: () => {
            isCreateSchemeOpen.value = false;
            schemeForm.reset();
        },
    });
};

const openAddComponentModal = (schemeId: number) => {
    selectedSchemeIdForComp.value = schemeId;
    isCreateComponentOpen.value = true;
};

const submitNewComponent = () => {
    if (!selectedSchemeIdForComp.value) return;

    componentForm.post(`/penilaian/skema/${selectedSchemeIdForComp.value}/komponen`, {
        preserveScroll: true,
        onSuccess: () => {
            isCreateComponentOpen.value = false;
            componentForm.reset();
        },
    });
};

const totalWeight = (components: ComponentItem[]) => {
    return components.reduce((acc, c) => acc + Number(c.weight_percentage), 0);
};
</script>

<template>
    <AppLayout>
        <Head title="Skema Penilaian Berversi" />

        <div class="space-y-6">
            <!-- Header Halaman -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Skema & Bobot Penilaian</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Konfigurasi formula pembobotan dinamis, aturan pembulatan, dan batas kelulusan KKTP berversi.
                    </p>
                </div>

                <div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-xs transition-colors"
                        @click="isCreateSchemeOpen = true"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Buat Versi Skema Baru</span>
                    </button>
                </div>
            </div>

            <!-- Daftar Skema -->
            <div class="grid grid-cols-1 gap-6">
                <div
                    v-for="scheme in props.schemes"
                    :key="scheme.id"
                    class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden"
                >
                    <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4 bg-slate-50/50">
                        <div class="space-y-1">
                            <div class="flex items-center gap-3">
                                <h2 class="text-lg font-bold text-slate-900">{{ scheme.name }}</h2>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                                    Versi {{ scheme.version }}
                                </span>
                                <span
                                    v-if="scheme.is_active"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800"
                                >
                                    Aktif
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 font-mono">Kode: {{ scheme.code }} | Pembulatan: {{ scheme.rounding_rule }} ({{ scheme.decimal_places }} desimal) | KKTP Default: {{ scheme.passing_grade_default }}</p>
                        </div>

                        <div>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-slate-300 hover:bg-white text-slate-700 rounded-lg text-xs font-semibold shadow-xs transition-colors"
                                @click="openAddComponentModal(scheme.id)"
                            >
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Tambah Komponen</span>
                            </button>
                        </div>
                    </div>

                    <!-- Komponen Bobot -->
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Komponen Penilaian & Bobot Persentase
                            </h3>
                            <div class="text-xs font-bold">
                                Total Bobot:
                                <span
                                    :class="[
                                        totalWeight(scheme.components) === 100
                                            ? 'text-emerald-600'
                                            : 'text-amber-600',
                                    ]"
                                >
                                    {{ totalWeight(scheme.components) }}%
                                </span>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-slate-600">
                                <thead class="bg-slate-50 text-slate-700 text-xs uppercase font-semibold">
                                    <tr>
                                        <th class="py-2.5 px-3 w-12 text-center">Urutan</th>
                                        <th class="py-2.5 px-3 w-28">Kode</th>
                                        <th class="py-2.5 px-4">Nama Komponen</th>
                                        <th class="py-2.5 px-3 text-center w-32">Bobot (%)</th>
                                        <th class="py-2.5 px-3 text-center w-32">KKTP / Batas</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="comp in scheme.components" :key="comp.id" class="hover:bg-slate-50">
                                        <td class="py-2.5 px-3 text-center text-xs text-slate-400">{{ comp.sequence }}</td>
                                        <td class="py-2.5 px-3 font-mono text-xs text-slate-700 font-semibold">{{ comp.code }}</td>
                                        <td class="py-2.5 px-4 font-medium text-slate-900">{{ comp.name }}</td>
                                        <td class="py-2.5 px-3 text-center font-bold text-slate-800">{{ comp.weight_percentage }}%</td>
                                        <td class="py-2.5 px-3 text-center text-xs font-semibold text-emerald-700">{{ comp.passing_grade }}</td>
                                    </tr>
                                    <tr v-if="scheme.components.length === 0">
                                        <td colspan="5" class="py-6 text-center text-slate-400 text-xs">
                                            Belum ada komponen penilaian pada skema ini. Klik "Tambah Komponen" di atas.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div v-if="props.schemes.length === 0" class="bg-white p-12 rounded-xl border border-slate-200 text-center">
                    <p class="text-slate-500 text-sm">Belum ada skema penilaian yang dibuat.</p>
                </div>
            </div>

            <!-- Modal Buat Skema Baru -->
            <div
                v-if="isCreateSchemeOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            >
                <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-slate-900">Buat Skema Penilaian Baru</h2>
                        <button
                            type="button"
                            class="text-slate-400 hover:text-slate-600"
                            @click="isCreateSchemeOpen = false"
                        >
                            &times;
                        </button>
                    </div>

                    <form @submit.prevent="submitNewScheme" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Skema</label>
                            <input
                                v-model="schemeForm.name"
                                type="text"
                                required
                                placeholder="Contoh: Kurikulum Merdeka 2024"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Skema</label>
                            <input
                                v-model="schemeForm.code"
                                type="text"
                                required
                                placeholder="KM-2024"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 uppercase"
                            />
                            <p class="text-[11px] text-slate-400 mt-1">Jika kode sama sudah ada, versi akan otomatis dinaikkan (+1).</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Aturan Pembulatan</label>
                                <select
                                    v-model="schemeForm.rounding_rule"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="half_up">Half Up (Baku)</option>
                                    <option value="floor">Floor (Kebawah)</option>
                                    <option value="ceil">Ceil (Keatas)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Digit Desimal</label>
                                <input
                                    v-model.number="schemeForm.decimal_places"
                                    type="number"
                                    min="0"
                                    max="4"
                                    required
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">KKTP Default</label>
                            <input
                                v-model.number="schemeForm.passing_grade_default"
                                type="number"
                                min="0"
                                max="100"
                                step="0.1"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3">
                            <button
                                type="button"
                                class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                @click="isCreateSchemeOpen = false"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="schemeForm.processing"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-xs"
                            >
                                Simpan Skema
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Tambah Komponen -->
            <div
                v-if="isCreateComponentOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            >
                <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-slate-900">Tambah Komponen Penilaian</h2>
                        <button
                            type="button"
                            class="text-slate-400 hover:text-slate-600"
                            @click="isCreateComponentOpen = false"
                        >
                            &times;
                        </button>
                    </div>

                    <form @submit.prevent="submitNewComponent" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Komponen</label>
                            <input
                                v-model="componentForm.name"
                                type="text"
                                required
                                placeholder="Contoh: Formatif 1 / Ulangan Harian"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Komponen</label>
                                <input
                                    v-model="componentForm.code"
                                    type="text"
                                    required
                                    placeholder="F1 / UTS / UAS"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 uppercase"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Urutan</label>
                                <input
                                    v-model.number="componentForm.sequence"
                                    type="number"
                                    min="1"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Bobot Persentase (%)</label>
                                <input
                                    v-model.number="componentForm.weight_percentage"
                                    type="number"
                                    min="1"
                                    max="100"
                                    step="0.1"
                                    required
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">KKTP / Batas Tuntas</label>
                                <input
                                    v-model.number="componentForm.passing_grade"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                    required
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3">
                            <button
                                type="button"
                                class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                @click="isCreateComponentOpen = false"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="componentForm.processing"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-xs"
                            >
                                Simpan Komponen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

