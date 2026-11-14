<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface ComponentItem {
    id: number;
    name: string;
    code: string;
    weight_percentage: number;
    passing_grade: number;
    sequence: number;
    teaching_assignment_id: number | null;
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
    can_manage: boolean;
    teaching_assignments: { id: number; class_name: string; subject_name: string; term_name: string; academic_year_name: string }[];
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
    teaching_assignment_id: null as number | null,
});

const bindingComponent = ref<{ schemeId: number; component: ComponentItem } | null>(null);
const bindingForm = useForm({ teaching_assignment_id: null as number | null });
const roundingLabel = (rule: SchemeItem['rounding_rule']) => ({ half_up: 'Pembulatan normal', floor: 'Selalu ke bawah', ceil: 'Selalu ke atas' })[rule];
const assignmentLabel = (id: number | null) => {
    const assignment = props.teaching_assignments.find(item => item.id === id);
    return assignment ? `${assignment.class_name} · ${assignment.subject_name} · ${assignment.term_name} ${assignment.academic_year_name}` : 'Umum (kelas satu mapel)';
};
const openBinding = (schemeId: number, component: ComponentItem) => {
    bindingForm.reset();
    bindingForm.clearErrors();
    bindingComponent.value = { schemeId, component };
};
const submitBinding = () => {
    if (!bindingComponent.value) return;
    bindingForm.put(`/penilaian/skema/${bindingComponent.value.schemeId}/komponen/${bindingComponent.value.component.id}/penugasan`, {
        preserveScroll: true,
        onSuccess: () => { bindingComponent.value = null; },
    });
};

const submitNewScheme = () => {
    schemeForm.post('/penilaian/skema', {
        preserveScroll: true,
        onSuccess: () => {
            isCreateSchemeOpen.value = false;
            schemeForm.reset();
        },
    });
};

const openSchemeForm = () => {
    schemeForm.clearErrors();
    isCreateSchemeOpen.value = true;
};

const closeSchemeForm = () => {
    if (schemeForm.processing) return;
    schemeForm.clearErrors();
    schemeForm.reset();
    isCreateSchemeOpen.value = false;
};

const openAddComponentModal = (schemeId: number) => {
    selectedSchemeIdForComp.value = schemeId;
    componentForm.reset();
    componentForm.clearErrors();
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

const weightTotals = (components: ComponentItem[]) => {
    const totals = new Map<number | null, number>();
    for (const component of components) {
        totals.set(component.teaching_assignment_id, (totals.get(component.teaching_assignment_id) ?? 0) + Number(component.weight_percentage));
    }
    return Array.from(totals, ([assignmentId, total]) => ({ assignmentId, label: assignmentLabel(assignmentId), total: Math.round(total * 100) / 100 }));
};
const closeActiveDialog = (event: KeyboardEvent) => {
    if (event.key !== 'Escape') return;
    if (isCreateSchemeOpen.value) closeSchemeForm();
    else if (isCreateComponentOpen.value && !componentForm.processing) isCreateComponentOpen.value = false;
    else if (bindingComponent.value && !bindingForm.processing) bindingComponent.value = null;
};
onMounted(() => window.addEventListener('keydown', closeActiveDialog));
onBeforeUnmount(() => window.removeEventListener('keydown', closeActiveDialog));
</script>

<template>
    <AppLayout>
        <Head title="Skema Penilaian Berversi" />

        <div class="view schemes-page space-y-6">
            <!-- Header Halaman -->
            <header class="page-heading compact-heading schemes-heading">
                <div>
                    <p class="eyebrow">Asesmen · Formula nilai</p>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Skema & Bobot Penilaian</h1>
                    <p class="text-sm text-slate-600 mt-1">
                        Konfigurasi formula pembobotan, aturan pembulatan, dan batas kelulusan KKTP berversi.
                    </p>
                </div>

                <div v-if="props.can_manage">
                    <AppButton variant="primary"
                        type="button"
                        class="primary-button inline-flex items-center"
                        @click="openSchemeForm"
                    ><template #icon><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg></template>

                        <span>Buat Versi Skema Baru</span>
                    </AppButton>
                </div>
            </header>

            <!-- Daftar Skema -->
            <div class="grid grid-cols-1 gap-6">
                <div
                    v-for="scheme in props.schemes"
                    :key="scheme.id"
                    class="scheme-panel bg-[var(--surface)] rounded-xl border border-[var(--line)] shadow-xs overflow-hidden"
                >
                    <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4 bg-slate-50/50">
                        <div class="space-y-1">
                            <div class="flex items-center gap-3">
                                <h2 class="text-lg font-bold text-slate-900">{{ scheme.name }}</h2>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-meta font-semibold bg-indigo-100 text-indigo-800">
                                    Versi {{ scheme.version }}
                                </span>
                                <span
                                    v-if="scheme.is_active"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-meta font-medium bg-emerald-100 text-emerald-800"
                                >
                                    Aktif
                                </span>
                                <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-meta font-medium bg-slate-100 text-slate-600">Versi lama</span>
                            </div>
                            <p class="text-meta text-slate-600 font-mono">Kode: {{ scheme.code }} | {{ roundingLabel(scheme.rounding_rule) }} ({{ scheme.decimal_places }} desimal) | KKTP default: {{ scheme.passing_grade_default }}</p>
                        </div>

                        <div v-if="props.can_manage">
                            <AppButton variant="secondary" icon="plus"
                                type="button"
                                class="inline-flex items-center transition-colors"
                                @click="openAddComponentModal(scheme.id)"
                            >

                                <span>Tambah Komponen</span>
                            </AppButton>
                        </div>
                    </div>

                    <!-- Komponen Bobot -->
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-meta font-semibold uppercase tracking-wider text-slate-600">
                                Komponen Penilaian & Bobot Persentase
                            </h3>
                            <div class="space-y-1 text-meta font-bold text-right">
                                <p v-for="group in weightTotals(scheme.components)" :key="group.assignmentId ?? 'general'">
                                    {{ group.label }}:
                                    <span :class="Math.abs(group.total - 100) < 0.001 ? 'text-emerald-600' : 'text-amber-800'">{{ group.total.toLocaleString('id-ID') }}%{{ Math.abs(group.total - 100) < 0.001 ? ' · sesuai' : ' · target 100%' }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="overflow-x-auto table-scroll" tabindex="0" role="region" aria-label="Tabel data">
                            <table class="app-table w-full text-left text-sm text-slate-600">
                                <thead class="bg-slate-50 text-slate-700 text-meta uppercase font-semibold">
                                    <tr>
                                        <th scope="col" class="py-2.5 px-3 w-12 text-center">Urutan</th>
                                        <th scope="col" class="py-2.5 px-3 w-28">Kode</th>
                                        <th scope="col" class="py-2.5 px-4">Nama Komponen</th>
                                        <th scope="col" class="py-2.5 px-4">Kelas & Mata Pelajaran</th>
                                        <th scope="col" class="py-2.5 px-3 text-center w-32">Bobot (%)</th>
                                        <th scope="col" class="py-2.5 px-3 text-center w-32">KKTP / Batas</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="comp in scheme.components" :key="comp.id" class="hover:bg-slate-50">
                                        <td class="py-2.5 px-3 text-center text-meta text-slate-600">{{ comp.sequence }}</td>
                                        <td class="py-2.5 px-3 font-mono text-meta text-slate-700 font-semibold">{{ comp.code }}</td>
                                        <td class="py-2.5 px-4 font-medium text-slate-900">{{ comp.name }}</td>
                                        <td class="py-2.5 px-4">
                                            <span>{{ assignmentLabel(comp.teaching_assignment_id) }}</span>
                                            <button v-if="props.can_manage && comp.teaching_assignment_id === null" type="button" class="block text-sm font-semibold text-indigo-600 hover:text-indigo-700" @click="openBinding(scheme.id, comp)">Pilih mata pelajaran</button>
                                        </td>
                                        <td class="py-2.5 px-3 text-center font-bold text-slate-800">{{ comp.weight_percentage }}%</td>
                                        <td class="py-2.5 px-3 text-center text-meta font-semibold text-emerald-700">{{ comp.passing_grade }}</td>
                                    </tr>
                                    <tr v-if="scheme.components.length === 0">
                                        <td colspan="6" class="py-6 text-center text-slate-600 text-meta">
                                            {{ props.can_manage ? 'Belum ada komponen penilaian. Tambahkan komponen agar skema dapat digunakan untuk menyusun rapor.' : 'Belum ada komponen penilaian pada skema ini.' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div v-if="props.schemes.length === 0" class="bg-[var(--surface)] p-12 rounded-xl border border-[var(--line)] text-center">
                    <p class="text-slate-600 text-sm">Belum ada skema penilaian yang dibuat.</p>
                </div>
            </div>

            <!-- Modal Buat Skema Baru -->
            <div
                v-if="props.can_manage && isCreateSchemeOpen"
                class="scheme-backdrop fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            >
                <div class="scheme-dialog bg-[var(--surface)] rounded-xl shadow-xl max-h-[90vh] max-w-lg w-full overflow-y-auto p-6 space-y-4" role="dialog" aria-modal="true" aria-labelledby="new-scheme-title">
                    <div class="flex items-center justify-between">
                        <h2 id="new-scheme-title" class="text-lg font-bold text-slate-900">Buat Skema Penilaian Baru</h2>
                        <AppButton variant="secondary"
                            type="button"

                            :disabled="schemeForm.processing"
                            aria-label="Tutup formulir skema"
                            @click="closeSchemeForm"
                        >
                            &times;
                        </AppButton>
                    </div>
                    <p class="text-sm text-slate-600">Skema baru akan menjadi skema aktif sekolah. Versi sebelumnya tetap tersimpan untuk riwayat rapor yang sudah diterbitkan.</p>

                    <form @submit.prevent="submitNewScheme" class="space-y-4">
                        <div>
                            <label class="block text-meta font-semibold text-slate-700 mb-1">Nama Skema</label>
                            <input
                                v-model="schemeForm.name"
                                type="text"
                                required
                                placeholder="Contoh: Kurikulum Merdeka 2024"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="schemeForm.errors.name" class="mt-1 text-sm text-red-600">{{ schemeForm.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-meta font-semibold text-slate-700 mb-1">Kode Skema</label>
                            <input
                                v-model="schemeForm.code"
                                type="text"
                                required
                                placeholder="KM-2024"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 uppercase"
                            />
                            <p class="text-meta text-slate-600 mt-1">Jika kode sama sudah ada, versi akan otomatis dinaikkan (+1).</p>
                            <p v-if="schemeForm.errors.code" class="mt-1 text-sm text-red-600">{{ schemeForm.errors.code }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-meta font-semibold text-slate-700 mb-1">Aturan Pembulatan</label>
                                <select
                                    v-model="schemeForm.rounding_rule"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="half_up">Half Up (Baku)</option>
                                    <option value="floor">Floor (Kebawah)</option>
                                    <option value="ceil">Ceil (Keatas)</option>
                                </select>
                                <p v-if="schemeForm.errors.rounding_rule" class="mt-1 text-sm text-red-600">{{ schemeForm.errors.rounding_rule }}</p>
                            </div>

                            <div>
                                <label class="block text-meta font-semibold text-slate-700 mb-1">Digit Desimal</label>
                                <input
                                    v-model.number="schemeForm.decimal_places"
                                    type="number"
                                    min="0"
                                    max="4"
                                    required
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="schemeForm.errors.decimal_places" class="mt-1 text-sm text-red-600">{{ schemeForm.errors.decimal_places }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-meta font-semibold text-slate-700 mb-1">KKTP Default</label>
                            <input
                                v-model.number="schemeForm.passing_grade_default"
                                type="number"
                                min="0"
                                max="100"
                                step="0.1"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="schemeForm.errors.passing_grade_default" class="mt-1 text-sm text-red-600">{{ schemeForm.errors.passing_grade_default }}</p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3">
                            <AppButton variant="secondary"
                                type="button"

                                :disabled="schemeForm.processing"
                                @click="closeSchemeForm"
                            >
                                Batal
                            </AppButton>
                            <AppButton variant="primary" :loading="schemeForm.processing"
                                type="submit"
                                :disabled="schemeForm.processing"

                            >
                                Simpan Skema
                            </AppButton>
                        </div>
                    </form>
                </div>
            </div>

            <div v-if="props.can_manage && bindingComponent" class="scheme-backdrop fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
                <form class="scheme-dialog bg-[var(--surface)] rounded-xl shadow-xl max-h-[90vh] max-w-lg w-full overflow-y-auto p-6 space-y-4" role="dialog" aria-modal="true" aria-labelledby="binding-title" @submit.prevent="submitBinding">
                    <h2 id="binding-title" class="text-lg font-bold text-slate-900">Petakan {{ bindingComponent.component.name }}</h2>
                    <p class="text-sm text-slate-600">Pilih mata pelajaran asal nilai komponen ini. Komponen yang sudah dipetakan tidak dapat dipindah ke mata pelajaran lain.</p>
                    <label for="binding-assignment" class="block text-sm font-semibold text-slate-700">Kelas & Mata Pelajaran</label>
                    <select id="binding-assignment" v-model="bindingForm.teaching_assignment_id" required class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option :value="null" disabled>Pilih mata pelajaran</option>
                        <option v-for="assignment in props.teaching_assignments" :key="assignment.id" :value="assignment.id">{{ assignmentLabel(assignment.id) }}</option>
                    </select>
                    <p v-if="bindingForm.errors.teaching_assignment_id" class="text-sm text-red-600">{{ bindingForm.errors.teaching_assignment_id }}</p>
                    <div class="flex justify-end gap-3">
                        <AppButton variant="secondary"  type="button"  :disabled="bindingForm.processing" @click="bindingComponent = null; bindingForm.clearErrors()">Batal</AppButton>
                        <AppButton variant="primary" :loading="bindingForm.processing" type="submit" :disabled="bindingForm.processing" >Simpan Pemetaan</AppButton>
                    </div>
                </form>
            </div>

            <!-- Modal Tambah Komponen -->
            <div
                v-if="props.can_manage && isCreateComponentOpen"
                class="scheme-backdrop fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            >
                <div class="scheme-dialog bg-[var(--surface)] rounded-xl shadow-xl max-h-[90vh] max-w-lg w-full overflow-y-auto p-6 space-y-4" role="dialog" aria-modal="true" aria-labelledby="new-component-title">
                    <div class="flex items-center justify-between">
                        <h2 id="new-component-title" class="text-lg font-bold text-slate-900">Tambah Komponen Penilaian</h2>
                        <AppButton variant="secondary"
                            type="button"

                            aria-label="Tutup formulir komponen"
                            @click="isCreateComponentOpen = false"
                        >
                            &times;
                        </AppButton>
                    </div>

                    <form @submit.prevent="submitNewComponent" class="space-y-4">
                        <div>
                            <label for="component-assignment" class="block text-meta font-semibold text-slate-700 mb-1">Kelas & Mata Pelajaran</label>
                            <select id="component-assignment" v-model="componentForm.teaching_assignment_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option :value="null">Umum (hanya untuk kelas dengan satu mapel)</option>
                                <option v-for="assignment in props.teaching_assignments" :key="assignment.id" :value="assignment.id">{{ assignmentLabel(assignment.id) }}</option>
                            </select>
                            <p v-if="componentForm.errors.teaching_assignment_id" class="mt-1 text-sm text-red-600">{{ componentForm.errors.teaching_assignment_id }}</p>
                        </div>
                        <div>
                            <label class="block text-meta font-semibold text-slate-700 mb-1">Nama Komponen</label>
                            <input
                                v-model="componentForm.name"
                                type="text"
                                required
                                placeholder="Contoh: Formatif 1 / Ulangan Harian"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="componentForm.errors.name" class="mt-1 text-sm text-red-600">{{ componentForm.errors.name }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-meta font-semibold text-slate-700 mb-1">Kode Komponen</label>
                                <input
                                    v-model="componentForm.code"
                                    type="text"
                                    required
                                    placeholder="F1 / UTS / UAS"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 uppercase"
                                />
                                <p v-if="componentForm.errors.code" class="mt-1 text-sm text-red-600">{{ componentForm.errors.code }}</p>
                            </div>

                            <div>
                                <label class="block text-meta font-semibold text-slate-700 mb-1">Urutan</label>
                                <input
                                    v-model.number="componentForm.sequence"
                                    type="number"
                                    min="1"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="componentForm.errors.sequence" class="mt-1 text-sm text-red-600">{{ componentForm.errors.sequence }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-meta font-semibold text-slate-700 mb-1">Bobot Persentase (%)</label>
                                <input
                                    v-model.number="componentForm.weight_percentage"
                                    type="number"
                                    min="1"
                                    max="100"
                                    step="0.1"
                                    required
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="componentForm.errors.weight_percentage" class="mt-1 text-sm text-red-600">{{ componentForm.errors.weight_percentage }}</p>
                            </div>

                            <div>
                                <label class="block text-meta font-semibold text-slate-700 mb-1">KKTP / Batas Tuntas</label>
                                <input
                                    v-model.number="componentForm.passing_grade"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                    required
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="componentForm.errors.passing_grade" class="mt-1 text-sm text-red-600">{{ componentForm.errors.passing_grade }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3">
                            <AppButton variant="secondary"
                                type="button"

                                :disabled="componentForm.processing"
                                @click="isCreateComponentOpen = false; componentForm.clearErrors()"
                            >
                                Batal
                            </AppButton>
                            <AppButton variant="primary" :loading="componentForm.processing"
                                type="submit"
                                :disabled="componentForm.processing"

                            >
                                Simpan Komponen
                            </AppButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.schemes-page{max-width:1440px;margin-inline:auto}
.schemes-heading{align-items:flex-start;padding:0 0 .8rem;border-bottom:1px solid var(--line)}
.schemes-heading h1{margin:.25rem 0;color:var(--ink);font-size:var(--type-3xl);font-weight:var(--weight-bold);letter-spacing:-.035em}
.schemes-heading p:not(.eyebrow){color:var(--ink-soft);line-height:1.55}
.scheme-panel,.schemes-page .bg-white{background:var(--surface)}
.schemes-page .bg-slate-50\/50,.schemes-page .bg-slate-50{background:var(--surface-subtle)}
.schemes-page .border-slate-200,.schemes-page .border-slate-100{border-color:var(--line)}
.schemes-page .border-slate-300{border-color:var(--line-strong)}
.schemes-page .text-slate-900,.schemes-page .text-slate-800,.schemes-page .text-slate-700{color:var(--ink)}
.schemes-page .text-slate-600,.schemes-page .text-slate-500,.schemes-page .text-slate-400{color:var(--ink-soft)}
.schemes-page .bg-indigo-100{background:var(--primary-soft)}
.schemes-page .text-indigo-800,.schemes-page .text-indigo-600{color:var(--primary)}
.schemes-page .bg-emerald-100{background:var(--green-soft)}
.schemes-page .text-emerald-800,.schemes-page .text-emerald-700{color:var(--green)}
.schemes-page .bg-indigo-600{background:var(--primary)}
.scheme-backdrop{background:color-mix(in srgb,var(--ink) 58%,transparent)}
.scheme-dialog{background:var(--surface);color:var(--ink);border:1px solid var(--line)}
.scheme-dialog input,.scheme-dialog select{background:var(--surface);color:var(--ink);border-color:var(--line-strong)}
@media(max-width:640px){.schemes-heading{align-items:stretch;flex-direction:column}.scheme-backdrop{padding:0}.scheme-dialog{width:100vw;max-width:none;max-height:100dvh;min-height:100dvh;border-radius:0;padding:1rem}.schemes-page .table-scroll{overflow-x:auto}}
</style>

