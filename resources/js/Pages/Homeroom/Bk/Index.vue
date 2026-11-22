<script setup lang="ts">
import { useConfirmedDialogClose } from '@/composables/useUnsavedDialog';
import AppButton from '@/components/AppButton.vue';
import OperationsScanner from '@/components/OperationsScanner.vue';
import FormField from '@/components/FormField.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import OperationsLayout from '@/layouts/OperationsLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface BkCaseItem {
    id: number;
    student_id: number;
    student_name: string;
    student_nis: string;
    class_name: string;
    incident_date: string;
    incident_time?: string | null;
    location?: string | null;
    category: string;
    title: string;
    description: string;
    penalty_points: number;
    follow_up_action?: string | null;
    evidence_document_ids: number[];
    status: string;
    is_bk_confidential: boolean;
}

interface StudentOption {
    student_id: number;
    nis: string;
    name: string;
    class_group_name?: string | null;
}

const props = defineProps<{
    cases: BkCaseItem[];
    students: StudentOption[];
    categories: { value: string; label: string }[];
    filters: { search: string; category: string; status: string };
}>();

const searchFilter = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category || '');
const statusFilter = ref(props.filters.status || '');

const studentLookup = ref('');
const studentOptions = ref<StudentOption[]>(props.students.slice(0, 20));
const studentLookupLoading = ref(false);
const editingCase = ref<BkCaseItem | null>(null);


const caseForm = useForm({
    student_id: props.students[0]?.student_id ?? ('' as string | number),
    incident_date: new Date().toISOString().slice(0, 10),
    incident_time: new Date().toTimeString().slice(0, 5),
    location: '',
    category: props.categories[0]?.value ?? 'kedisiplinan',
    title: '',
    description: '',
    penalty_points: 5,
    follow_up_action: '',
    status: 'reported',
    evidence_files: [] as File[],
});

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (searchFilter.value) params.set('search', searchFilter.value);
    if (categoryFilter.value) params.set('category', categoryFilter.value);
    if (statusFilter.value) params.set('status', statusFilter.value);
    const qs = params.toString();
    return qs ? `/bk/kasus/ekspor?${qs}` : '/bk/kasus/ekspor';
});

const applyFilters = () => {
    router.get(
        '/bk/kasus',
        {
            search: searchFilter.value || undefined,
            category: categoryFilter.value || undefined,
            status: statusFilter.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

const searchStudentByCodeOrName = async (queryOverride?: string) => {
    const query = (queryOverride ?? studentLookup.value).trim();
    studentLookupLoading.value = true;
    try {
        const res = await fetch(`/bk/kasus/cari-siswa?q=${encodeURIComponent(query)}`, {
            headers: { Accept: 'application/json' },
        });
        if (res.ok) {
            const payload = await res.json();
            studentOptions.value = payload.students ?? [];
            if (studentOptions.value.length === 1) {
                caseForm.student_id = studentOptions.value[0].student_id;
            }
        }
    } finally {
        studentLookupLoading.value = false;
    }
};

const handleEvidenceChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    caseForm.evidence_files = target.files ? Array.from(target.files) : [];
};

const resetForm = () => {
    editingCase.value = null;
    caseForm.reset();
    caseForm.incident_date = new Date().toISOString().slice(0, 10);
    caseForm.incident_time = new Date().toTimeString().slice(0, 5);
    caseForm.category = props.categories[0]?.value ?? 'kedisiplinan';
    caseForm.penalty_points = 5;
    caseForm.status = 'reported';
    caseForm.evidence_files = [];
};

const startEdit = (item: BkCaseItem) => {
    editingCase.value = item;
    caseForm.student_id = item.student_id;
    caseForm.incident_date = item.incident_date;
    caseForm.incident_time = item.incident_time ?? '';
    caseForm.location = item.location ?? '';
    caseForm.category = item.category;
    caseForm.title = item.title;
    caseForm.description = item.description;
    caseForm.penalty_points = item.penalty_points;
    caseForm.follow_up_action = item.follow_up_action ?? '';
    caseForm.status = item.status;
    caseForm.evidence_files = [];
};

const submitCase = () => {
    if (editingCase.value) {
        caseForm
            .transform((data) => ({
                ...data,
                _method: 'PUT',
            }))
            .post(`/bk/kasus/${editingCase.value.id}`, {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => resetForm(),
            });
        return;
    }

    caseForm.post('/bk/kasus', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
};

const categoryLabel = (value: string): string =>
    props.categories.find((c) => c.value === value)?.label ?? value;

const statusTone = (status: string): 'neutral' | 'info' | 'warning' | 'success' => {
    if (status === 'resolved') return 'success';
    if (status === 'investigating') return 'warning';
    if (status === 'reported') return 'info';
    return 'neutral';
};


const showBkCaseEditor = ref(false);
const requestBkCaseEditorClose = useConfirmedDialogClose(caseForm, () => { showBkCaseEditor.value = false; caseForm.reset(); caseForm.clearErrors(); });
watch(() => caseForm.wasSuccessful, (success) => { if (success) caseForm.defaults(); });
</script>

<template>
    <OperationsLayout mode="scan" title="Catatan Kasus BK">
        <Head title="Catatan Kasus BK" />

        <OperationsScanner v-model="studentLookup" title="Scan Kartu Pelajar" description="Lapor Pelanggaran Siswa (BK & Kedisiplinan)"
            :busy="studentLookupLoading" @lookup="searchStudentByCodeOrName">
            <a href="/e-layanan">Form / Beranda →</a>
            <a href="/dashboard">Pos BK / Guru →</a>
        </OperationsScanner>
        <details class="operations-workflow" :open="studentOptions.length > 0 || !!editingCase">
            <summary>Form Kasus & Riwayat Konseling</summary>
        <div class="space-y-6">
            <header class="operations-scan-hero flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="operations-scan-label">Ruang Kerja BK · Scan QR Pelanggaran</p>
                    <h1 class="text-xl font-bold text-white">Bimbingan Konseling (BK) — Kasus Disiplin Rahasia</h1>
                    <p class="text-sm text-emerald-100">
                        Area tertutup khusus Guru BK dan Admin Sekolah. Eviden dan catatan kasus diaudit dan terisolasi dari daftar wali kelas biasa.
                    </p>
                </div>
                <AppButton icon="excel" as="a"
                    :href="exportUrl"
                    class="inline-flex items-center rounded-lg border border-[var(--line)] bg-[var(--surface)] px-3 py-2 text-sm font-semibold text-[var(--ink-soft)] hover:bg-[var(--surface-subtle)]"
                >
                    Ekspor CSV BK
                </AppButton>
            </header>

            <AppButton variant="primary" type="button" @click="showBkCaseEditor = true">Catat kasus BK</AppButton>
<Teleport to="body"><div v-if="showBkCaseEditor" class="admin-form-overlay" role="dialog" aria-modal="true" aria-label="Catat kasus BK" @click.self="requestBkCaseEditorClose" @keydown.esc.stop.prevent="requestBkCaseEditorClose"><div class="admin-form-dialog"><header class="admin-form-header"><h2>Catat kasus BK</h2><AppButton type="button" icon="close" icon-only aria-label="Tutup catat kasus bk" title="Tutup formulir" :disabled="caseForm.processing" @click="requestBkCaseEditorClose" /></header>
<form @submit.prevent="submitCase" class="rounded-xl border border-[var(--line)] bg-[var(--surface)] p-5 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">

                </div>

                <div v-if="!editingCase" class="operations-scan-panel">
                </div>

                <div v-if="!editingCase" class="grid gap-3 md:grid-cols-3">
                    <div class="md:col-span-2">
                        <FormField id="bk-student-lookup" label="Cari Siswa (Nama, NIS, atau Token Kartu QR)">
                            <div class="flex gap-2">
                                <input
                                    id="bk-student-lookup"
                                    v-model="studentLookup"
                                    type="text"
                                    placeholder="Ketik nama/NIS atau scan token QR kartu siswa"
                                    class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm"
                                    @keydown.enter.prevent="searchStudentByCodeOrName()"
                                />
                                <AppButton type="button" variant="secondary" :disabled="studentLookupLoading" @click="searchStudentByCodeOrName()">
                                    {{ studentLookupLoading ? 'Mencari...' : 'Cari' }}
                                </AppButton>
                            </div>
                        </FormField>
                    </div>
                    <FormField id="bk-student-id" label="Siswa Terpilih" :error="caseForm.errors.student_id" required>
                        <select id="bk-student-id" v-model="caseForm.student_id" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                            <option v-for="student in studentOptions" :key="student.student_id" :value="student.student_id">
                                {{ student.name }} ({{ student.nis }}){{ student.class_group_name ? ` — ${student.class_group_name}` : '' }}
                            </option>
                        </select>
                    </FormField>
                </div>

                <div class="grid gap-3 md:grid-cols-4">
                    <FormField id="bk-incident-date" label="Tanggal Kejadian" :error="caseForm.errors.incident_date" required>
                        <input id="bk-incident-date" v-model="caseForm.incident_date" type="date" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="bk-incident-time" label="Waktu Kejadian" :error="caseForm.errors.incident_time">
                        <input id="bk-incident-time" v-model="caseForm.incident_time" type="time" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="bk-location" label="Lokasi Kejadian" :error="caseForm.errors.location">
                        <input id="bk-location" v-model="caseForm.location" type="text" placeholder="Halaman Belakang" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="bk-category" label="Kategori Pelanggaran" :error="caseForm.errors.category" required>
                        <select id="bk-category" v-model="caseForm.category" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                            <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                        </select>
                    </FormField>
                </div>

                <div class="grid gap-3 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <FormField id="bk-title" label="Judul / Ringkasan Kasus" :error="caseForm.errors.title" required>
                            <input id="bk-title" v-model="caseForm.title" type="text" placeholder="Ringkasan pelanggaran atau temuan" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm" />
                        </FormField>
                    </div>
                    <FormField id="bk-penalty-points" label="Poin Pelanggaran" :error="caseForm.errors.penalty_points">
                        <input id="bk-penalty-points" v-model.number="caseForm.penalty_points" type="number" min="0" max="200" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="bk-status" label="Status Penanganan" :error="caseForm.errors.status">
                        <select id="bk-status" v-model="caseForm.status" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm">
                            <option value="reported">Dilaporkan</option>
                            <option value="investigating">Dalam Penyelidikan</option>
                            <option value="resolved">Selesai / Dibina</option>
                        </select>
                    </FormField>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <FormField id="bk-description" label="Kronologi / Uraian Kasus" :error="caseForm.errors.description" required>
                        <textarea id="bk-description" v-model="caseForm.description" rows="3" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="bk-follow-up" label="Tindak Lanjut & Pembinaan" :error="caseForm.errors.follow_up_action">
                        <textarea id="bk-follow-up" v-model="caseForm.follow_up_action" rows="3" class="w-full rounded-lg border border-[var(--line)] px-3 py-2 text-sm" />
                    </FormField>
                </div>

                <FormField id="bk-evidence-files" label="Unggah Eviden Rahasia (Foto / PDF, Maks 3 Berkas)" :error="caseForm.errors.evidence_files">
                    <input id="bk-evidence-files" type="file" multiple accept=".jpg,.jpeg,.png,.pdf" class="text-sm" @change="handleEvidenceChange" />
                </FormField>

                <div class="flex flex-wrap gap-2">
                    <AppButton type="submit" :disabled="caseForm.processing">
                        {{ caseForm.processing ? 'Menyimpan...' : editingCase ? 'Simpan Perubahan Kasus' : 'Simpan Kasus BK' }}
                    </AppButton>
                    <AppButton v-if="editingCase" type="button" variant="secondary" @click="resetForm">
                        Batal Edit
                    </AppButton>
                </div>
            </form>
</div></div></Teleport>

            <div class="rounded-xl border border-[var(--line)] bg-[var(--surface)] p-5 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-[var(--ink)]">Daftar Kasus BK</h2>
                    <div class="flex flex-wrap gap-2">
                        <input
                            v-model="searchFilter"
                            type="text"
                            placeholder="Cari nama/NIS/judul"
                            class="rounded-lg border border-[var(--line)] px-3 py-2 text-sm"
                            @keydown.enter.prevent="applyFilters"
                        />
                        <select v-model="categoryFilter" class="rounded-lg border border-[var(--line)] px-3 py-2 text-sm" @change="applyFilters">
                            <option value="">Semua Kategori</option>
                            <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                        </select>
                        <select v-model="statusFilter" class="rounded-lg border border-[var(--line)] px-3 py-2 text-sm" @change="applyFilters">
                            <option value="">Semua Status</option>
                            <option value="reported">Dilaporkan</option>
                            <option value="investigating">Dalam Penyelidikan</option>
                            <option value="resolved">Selesai / Dibina</option>
                        </select>
                        <AppButton type="button" variant="secondary" @click="applyFilters">Terapkan</AppButton>
                    </div>
                </div>

                <StateFeedback
                    v-if="cases.length === 0"
                    state="empty"
                    title="Belum ada catatan kasus BK"
                    description="Catatan kasus BK bersifat rahasia dan hanya terlihat oleh Guru BK serta Admin."
                />

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-[var(--surface-subtle)] text-center text-xs font-semibold uppercase text-[var(--ink-faint)]">
                            <tr>
                                <th class="px-3 py-2.5 text-center">Siswa</th>
                                <th class="px-3 py-2.5 text-center">Kategori & Kasus</th>
                                <th class="px-3 py-2.5 text-center">Waktu & Lokasi</th>
                                <th class="px-3 py-2.5 text-center">Eviden & Poin</th>
                                <th class="px-3 py-2.5 text-center">Status</th>
                                <th class="px-3 py-2.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in cases" :key="item.id" class="hover:bg-slate-50/70">
                                <td class="px-3 py-3">
                                    <p class="font-medium text-[var(--ink)]">{{ item.student_name }}</p>
                                    <p class="text-xs text-[var(--ink-faint)]">{{ item.student_nis }} • {{ item.class_name }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    <p class="text-xs font-semibold uppercase text-[var(--ink-faint)]">{{ categoryLabel(item.category) }}</p>
                                    <p class="font-medium text-[var(--ink)]">{{ item.title }}</p>
                                    <p class="text-xs text-[var(--ink-soft)] line-clamp-2">{{ item.description }}</p>
                                </td>
                                <td class="px-3 py-3 text-xs text-[var(--ink-soft)]">
                                    <p>{{ item.incident_date }} {{ item.incident_time || '' }}</p>
                                    <p v-if="item.location">{{ item.location }}</p>
                                </td>
                                <td class="px-3 py-3 text-xs">
                                    <p class="font-semibold text-[var(--ink)]">{{ item.penalty_points }} poin</p>
                                    <div v-if="item.evidence_document_ids.length" class="mt-1 flex flex-wrap gap-1.5">
                                        <a
                                            v-for="(docId, idx) in item.evidence_document_ids"
                                            :key="docId"
                                            :href="`/bk/kasus/${item.id}/eviden/${docId}`"
                                            class="text-indigo-700 underline"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            Eviden #{{ idx + 1 }}
                                        </a>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <StatusBadge :status="item.status" :tone="statusTone(item.status)" size="sm" />
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <button
                                        type="button"
                                        class="rounded-md border border-[var(--line)] px-2.5 py-1 text-xs font-semibold text-[var(--ink-soft)] hover:bg-[var(--surface-subtle)]"
                                        @click="startEdit(item)"
                                    >
                                        Perbarui
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </details>
    </OperationsLayout>
</template>
