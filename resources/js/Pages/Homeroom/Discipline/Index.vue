<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface ClassItem {
    id: number;
    name: string;
}

interface StudentItem {
    id: number;
    name: string;
    nis: string;
}

interface TermItem {
    id: number;
    name: string;
}

interface IncidentItem {
    id: number;
    student_id: number;
    title: string;
    description: string;
    category: string;
    penalty_points: number;
    follow_up_action?: string | null;
    incident_date: string;
    recorded_by_user?: { name: string };
}

interface AchievementItem {
    id: number;
    student_id: number;
    title: string;
    achievement_type: string;
    level: string;
    rank: string;
    event_date: string;
    organizer?: string | null;
}

interface AssistanceItem {
    id: number;
    student_id: number;
    date: string;
    type: string;
    summary: string;
    agreed_solution?: string | null;
    facilitator?: { name: string };
}

interface DispensationItem {
    id: number;
    student_id: number;
    start_date: string;
    end_date: string;
    type: string;
    title: string;
    reason: string;
    status: string;
    notes?: string | null;
    submitted_by_user?: { name: string };
}

const props = defineProps<{
    classes: ClassItem[];
    students: StudentItem[];
    incidents: { data: IncidentItem[]; total: number };
    achievements: { data: AchievementItem[]; total: number };
    assistances: { data: AssistanceItem[]; total: number };
    dispensations?: { data: DispensationItem[]; total: number };
    active_term?: TermItem | null;
    filters: {
        class_group_id?: string;
    };
}>();

const activeTab = ref<'incidents' | 'achievements' | 'assistances' | 'dispensations'>('incidents');
const selectedClass = ref(props.filters.class_group_id || '');

const showIncidentModal = ref(false);
const showAchievementModal = ref(false);
const showAssistanceModal = ref(false);
const showDispensationModal = ref(false);

const applyFilters = () => {
    router.get(
        '/wali-kelas/kedisiplinan',
        { class_group_id: selectedClass.value || undefined },
        { preserveState: true }
    );
};

const incidentForm = useForm({
    student_id: '',
    term_id: props.active_term?.id ? String(props.active_term.id) : '',
    class_group_id: selectedClass.value || '',
    incident_date: new Date().toISOString().substring(0, 10),
    title: '',
    description: '',
    category: 'Keterlambatan',
    penalty_points: 5,
    follow_up_action: '',
});

const submitIncident = () => {
    incidentForm.class_group_id = selectedClass.value;
    incidentForm.post('/wali-kelas/kedisiplinan/insiden', {
        onSuccess: () => {
            showIncidentModal.value = false;
            incidentForm.reset('title', 'description', 'follow_up_action');
        },
    });
};

const achievementForm = useForm({
    student_id: '',
    term_id: props.active_term?.id ? String(props.active_term.id) : '',
    title: '',
    achievement_type: 'academic',
    level: 'city',
    rank: 'Juara 1',
    event_date: new Date().toISOString().substring(0, 10),
    organizer: '',
    notes: '',
});

const submitAchievement = () => {
    achievementForm.post('/wali-kelas/kedisiplinan/prestasi', {
        onSuccess: () => {
            showAchievementModal.value = false;
            achievementForm.reset('title', 'organizer', 'notes');
        },
    });
};

const assistanceForm = useForm({
    student_id: '',
    term_id: props.active_term?.id ? String(props.active_term.id) : '',
    date: new Date().toISOString().substring(0, 10),
    type: 'counseling',
    summary: '',
    agreed_solution: '',
    access_level: 'confidential',
});

const submitAssistance = () => {
    assistanceForm.post('/wali-kelas/kedisiplinan/bantuan', {
        onSuccess: () => {
            showAssistanceModal.value = false;
            assistanceForm.reset('summary', 'agreed_solution');
        },
    });
};

const dispensationForm = useForm({
    student_id: '',
    term_id: props.active_term?.id ? String(props.active_term.id) : '',
    start_date: new Date().toISOString().substring(0, 10),
    end_date: new Date().toISOString().substring(0, 10),
    type: 'sick',
    title: '',
    reason: '',
    notes: '',
});

const submitDispensation = () => {
    dispensationForm.post('/wali-kelas/kedisiplinan/dispensasi', {
        onSuccess: () => {
            showDispensationModal.value = false;
            dispensationForm.reset('title', 'reason', 'notes');
        },
    });
};
</script>

<template>
    <AppLayout title="Kedisiplinan & Prestasi Siswa">
        <Head title="Kedisiplinan & Prestasi — School Suite" />

        <div class="space-y-6">
            <!-- Header bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Kedisiplinan, Prestasi, & Dispensasi Siswa</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Pencatatan insiden tata tertib, rekam prestasi kejuaraan, surat dispensasi/izin, dan tindak lanjut pendampingan siswa.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        v-if="activeTab === 'incidents'"
                        type="button"
                        @click="showIncidentModal = true"
                        class="px-4 py-2 text-sm font-semibold text-white bg-rose-600 rounded-lg hover:bg-rose-700 transition shadow-xs"
                    >
                        + Catat Insiden
                    </button>
                    <button
                        v-if="activeTab === 'achievements'"
                        type="button"
                        @click="showAchievementModal = true"
                        class="px-4 py-2 text-sm font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition shadow-xs"
                    >
                        + Catat Prestasi
                    </button>
                    <button
                        v-if="activeTab === 'assistances'"
                        type="button"
                        @click="showAssistanceModal = true"
                        class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition shadow-xs"
                    >
                        + Catat Tindak Lanjut
                    </button>
                    <button
                        v-if="activeTab === 'dispensations'"
                        type="button"
                        @click="showDispensationModal = true"
                        class="px-4 py-2 text-sm font-semibold text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition shadow-xs"
                    >
                        + Catat Dispensasi / Izin
                    </button>
                </div>
            </div>

            <!-- Tab Headers & Filter -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200">
                <div class="flex gap-4">
                    <button
                        type="button"
                        @click="activeTab = 'incidents'"
                        :class="activeTab === 'incidents' ? 'border-rose-600 text-rose-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-800'"
                        class="pb-3 border-b-2 text-sm transition cursor-pointer"
                    >
                        Pelanggaran & Insiden ({{ incidents.total }})
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'achievements'"
                        :class="activeTab === 'achievements' ? 'border-emerald-600 text-emerald-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-800'"
                        class="pb-3 border-b-2 text-sm transition cursor-pointer"
                    >
                        Prestasi Siswa ({{ achievements.total }})
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'assistances'"
                        :class="activeTab === 'assistances' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-800'"
                        class="pb-3 border-b-2 text-sm transition cursor-pointer"
                    >
                        Tindak Lanjut & Konseling ({{ assistances.total }})
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'dispensations'"
                        :class="activeTab === 'dispensations' ? 'border-amber-600 text-amber-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-800'"
                        class="pb-3 border-b-2 text-sm transition cursor-pointer"
                    >
                        Dispensasi & Izin ({{ dispensations?.total || 0 }})
                    </button>
                </div>

                <div class="pb-2">
                    <select
                        v-model="selectedClass"
                        @change="applyFilters"
                        class="px-3 py-1.5 text-xs border border-slate-300 rounded-lg bg-white"
                    >
                        <option value="">Semua Rombel</option>
                        <option v-for="c in classes" :key="c.id" :value="String(c.id)">Kelas {{ c.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Content Tab 1: Insiden & Pelanggaran -->
            <div v-if="activeTab === 'incidents'" class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <table class="w-full text-left text-sm border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Nama Siswa</th>
                            <th class="px-4 py-3">Bentuk Pelanggaran</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3 text-center">Poin</th>
                            <th class="px-4 py-3">Tindak Lanjut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="incidents.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada rekam catatan insiden/pelanggaran.</td>
                        </tr>
                        <tr v-for="inc in incidents.data" :key="inc.id" class="hover:bg-slate-50/50">
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ inc.incident_date }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ students.find(s => s.id === inc.student_id)?.name || 'Siswa #' + inc.student_id }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-800">{{ inc.title }}</div>
                                <div class="text-xs text-slate-500 line-clamp-1">{{ inc.description }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ inc.category }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 rounded-full">
                                    +{{ inc.penalty_points }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ inc.follow_up_action || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Content Tab 2: Prestasi Siswa -->
            <div v-if="activeTab === 'achievements'" class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <table class="w-full text-left text-sm border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Nama Siswa</th>
                            <th class="px-4 py-3">Nama Prestasi / Kejuaraan</th>
                            <th class="px-4 py-3">Bidang</th>
                            <th class="px-4 py-3">Tingkat</th>
                            <th class="px-4 py-3">Capaian / Peringkat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="achievements.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada rekam prestasi siswa tercatat.</td>
                        </tr>
                        <tr v-for="ach in achievements.data" :key="ach.id" class="hover:bg-slate-50/50">
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ ach.event_date }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ students.find(s => s.id === ach.student_id)?.name || 'Siswa #' + ach.student_id }}
                            </td>
                            <td class="px-4 py-3 font-bold text-slate-800">{{ ach.title }}</td>
                            <td class="px-4 py-3 text-xs capitalize text-slate-600">{{ ach.achievement_type }}</td>
                            <td class="px-4 py-3 text-xs capitalize text-slate-600">{{ ach.level }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full">
                                    {{ ach.rank }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Content Tab 3: Tindak Lanjut & Konseling -->
            <div v-if="activeTab === 'assistances'" class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <table class="w-full text-left text-sm border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Nama Siswa</th>
                            <th class="px-4 py-3">Bentuk Layanan</th>
                            <th class="px-4 py-3">Ringkasan Pembahasan</th>
                            <th class="px-4 py-3">Solusi / Kesepakatan</th>
                            <th class="px-4 py-3">Fasilitator</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="assistances.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada catatan tindak lanjut / konseling.</td>
                        </tr>
                        <tr v-for="ast in assistances.data" :key="ast.id" class="hover:bg-slate-50/50">
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ ast.date }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ students.find(s => s.id === ast.student_id)?.name || 'Siswa #' + ast.student_id }}
                            </td>
                            <td class="px-4 py-3 text-xs font-semibold capitalize text-indigo-700">{{ ast.type.replace('_', ' ') }}</td>
                            <td class="px-4 py-3 text-xs text-slate-700 max-w-xs">{{ ast.summary }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ ast.agreed_solution || '—' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ ast.facilitator?.name || 'Petugas' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Content Tab 4: Dispensasi & Surat Izin -->
            <div v-if="activeTab === 'dispensations'" class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <table class="w-full text-left text-sm border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Periode Tanggal</th>
                            <th class="px-4 py-3">Nama Siswa</th>
                            <th class="px-4 py-3">Jenis Dispensasi</th>
                            <th class="px-4 py-3">Keterangan / Alasan</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Dicatat Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="!dispensations || dispensations.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada data surat dispensasi / izin tercatat.</td>
                        </tr>
                        <tr v-for="dsp in (dispensations?.data || [])" :key="dsp.id" class="hover:bg-slate-50/50">
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">
                                {{ dsp.start_date }} <span v-if="dsp.start_date !== dsp.end_date">s/d {{ dsp.end_date }}</span>
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ students.find(s => s.id === dsp.student_id)?.name || 'Siswa #' + dsp.student_id }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 text-xs font-bold rounded-full capitalize"
                                    :class="dsp.type === 'sick' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-200'">
                                    {{ dsp.type === 'sick' ? 'Surat Sakit' : (dsp.type === 'event' ? 'Dispensasi Lomba' : dsp.type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-700 max-w-xs">
                                <div class="font-bold">{{ dsp.title }}</div>
                                <div class="text-slate-500">{{ dsp.reason }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    {{ dsp.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ dsp.submitted_by_user?.name || 'Wali Kelas' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Tambah Insiden -->
        <div v-if="showIncidentModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-slate-900">Catat Insiden Pelanggaran Tata Tertib</h3>
                    <button type="button" @click="showIncidentModal = false" class="text-slate-400">✕</button>
                </div>
                <form @submit.prevent="submitIncident" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Peserta Didik *</label>
                        <select v-model="incidentForm.student_id" required class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                            <option value="">Pilih siswa...</option>
                            <option v-for="s in students" :key="s.id" :value="String(s.id)">{{ s.name }} (NIS: {{ s.nis }})</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal *</label>
                            <input v-model="incidentForm.incident_date" type="date" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori *</label>
                            <input v-model="incidentForm.category" type="text" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Insiden *</label>
                        <input v-model="incidentForm.title" type="text" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Kejadian *</label>
                        <textarea v-model="incidentForm.description" rows="3" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Poin Pelanggaran</label>
                            <input v-model="incidentForm.penalty_points" type="number" min="0" max="100" class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tindak Lanjut Langsung</label>
                            <input v-model="incidentForm.follow_up_action" type="text" class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="showIncidentModal = false" class="px-4 py-2 text-sm border rounded-lg text-slate-600">Batal</button>
                        <button type="submit" :disabled="incidentForm.processing" class="px-4 py-2 text-sm font-semibold bg-rose-600 text-white rounded-lg">Simpan Insiden</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Tambah Prestasi -->
        <div v-if="showAchievementModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-slate-900">Catat Prestasi & Capaian Siswa</h3>
                    <button type="button" @click="showAchievementModal = false" class="text-slate-400">✕</button>
                </div>
                <form @submit.prevent="submitAchievement" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Peserta Didik *</label>
                        <select v-model="achievementForm.student_id" required class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                            <option value="">Pilih siswa...</option>
                            <option v-for="s in students" :key="s.id" :value="String(s.id)">{{ s.name }} (NIS: {{ s.nis }})</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Prestasi / Kejuaraan *</label>
                        <input v-model="achievementForm.title" type="text" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Bidang *</label>
                            <select v-model="achievementForm.achievement_type" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="academic">Akademik</option>
                                <option value="sports">Olahraga</option>
                                <option value="arts">Seni & Budaya</option>
                                <option value="religious">Keagamaan</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tingkat *</label>
                            <select v-model="achievementForm.level" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="school">Sekolah</option>
                                <option value="district">Kecamatan</option>
                                <option value="city">Kota/Kabupaten</option>
                                <option value="province">Provinsi</option>
                                <option value="national">Nasional</option>
                                <option value="international">Internasional</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Peringkat *</label>
                            <input v-model="achievementForm.rank" type="text" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Event *</label>
                            <input v-model="achievementForm.event_date" type="date" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Penyelenggara</label>
                            <input v-model="achievementForm.organizer" type="text" class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="showAchievementModal = false" class="px-4 py-2 text-sm border rounded-lg text-slate-600">Batal</button>
                        <button type="submit" :disabled="achievementForm.processing" class="px-4 py-2 text-sm font-semibold bg-emerald-600 text-white rounded-lg">Simpan Prestasi</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Tambah Bantuan / Konseling -->
        <div v-if="showAssistanceModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-slate-900">Catat Tindak Lanjut & Konseling</h3>
                    <button type="button" @click="showAssistanceModal = false" class="text-slate-400">✕</button>
                </div>
                <form @submit.prevent="submitAssistance" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Peserta Didik *</label>
                        <select v-model="assistanceForm.student_id" required class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                            <option value="">Pilih siswa...</option>
                            <option v-for="s in students" :key="s.id" :value="String(s.id)">{{ s.name }} (NIS: {{ s.nis }})</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Bentuk Layanan *</label>
                            <select v-model="assistanceForm.type" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="counseling">Konseling Individu</option>
                                <option value="home_visit">Kunjungan Rumah (Home Visit)</option>
                                <option value="parent_conference">Konferensi Orang Tua</option>
                                <option value="financial_aid">Bantuan Perlengkapan / Finansial</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tingkat Akses</label>
                            <select v-model="assistanceForm.access_level" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="confidential">🔒 Rahasia (Wali/BK Saja)</option>
                                <option value="internal">Internal Dewan Guru</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Ringkasan Pembahasan *</label>
                        <textarea v-model="assistanceForm.summary" rows="3" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kesepakatan Solusi / Tindak Lanjut</label>
                        <input v-model="assistanceForm.agreed_solution" type="text" class="w-full px-3 py-2 text-sm border rounded-lg" />
                    </div>
                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="showAssistanceModal = false" class="px-4 py-2 text-sm border rounded-lg text-slate-600">Batal</button>
                        <button type="submit" :disabled="assistanceForm.processing" class="px-4 py-2 text-sm font-semibold bg-indigo-600 text-white rounded-lg">Simpan Tindak Lanjut</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Tambah Dispensasi / Surat Izin -->
        <div v-if="showDispensationModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-slate-900">Catat Surat Dispensasi / Izin Siswa</h3>
                    <button type="button" @click="showDispensationModal = false" class="text-slate-400">✕</button>
                </div>
                <form @submit.prevent="submitDispensation" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Peserta Didik *</label>
                        <select v-model="dispensationForm.student_id" required class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                            <option value="">Pilih siswa...</option>
                            <option v-for="s in students" :key="s.id" :value="String(s.id)">{{ s.name }} (NIS: {{ s.nis }})</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Mulai *</label>
                            <input v-model="dispensationForm.start_date" type="date" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Selesai *</label>
                            <input v-model="dispensationForm.end_date" type="date" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Dispensasi *</label>
                            <select v-model="dispensationForm.type" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="sick">Surat Keterangan Dokter / Sakit</option>
                                <option value="event">Dispensasi Mengikuti Lomba / Kegiatan</option>
                                <option value="personal">Izin Kepentingan Keluarga</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan / Keperluan *</label>
                            <input v-model="dispensationForm.title" type="text" placeholder="misal: Demam Berdarah / O2SN" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Lengkap *</label>
                        <textarea v-model="dispensationForm.reason" rows="2" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                    </div>
                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="showDispensationModal = false" class="px-4 py-2 text-sm border rounded-lg text-slate-600">Batal</button>
                        <button type="submit" :disabled="dispensationForm.processing" class="px-4 py-2 text-sm font-semibold bg-amber-600 text-white rounded-lg">Simpan Dispensasi</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
