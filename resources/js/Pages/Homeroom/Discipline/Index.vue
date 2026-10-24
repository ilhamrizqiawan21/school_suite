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

const props = defineProps<{
    classes: ClassItem[];
    students: StudentItem[];
    incidents: { data: IncidentItem[]; total: number };
    achievements: { data: AchievementItem[]; total: number };
    assistances: { data: AssistanceItem[]; total: number };
    active_term?: TermItem | null;
    filters: {
        class_group_id?: string;
    };
}>();

const activeTab = ref<'incidents' | 'achievements' | 'assistances'>('incidents');
const selectedClass = ref(props.filters.class_group_id || '');

const showIncidentModal = ref(false);
const showAchievementModal = ref(false);
const showAssistanceModal = ref(false);

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
</script>

<template>
    <AppLayout title="Kedisiplinan & Prestasi Siswa">
        <Head title="Kedisiplinan & Prestasi — School Suite" />

        <div class="space-y-6">
            <!-- Header bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Kedisiplinan, Prestasi, & Konseling Siswa</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Pencatatan insiden pelanggaran tata tertib, rekam prestasi kejuaraan, dan tindak lanjut pendampingan siswa.
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
                </div>
            </div>

            <!-- Tab Headers & Filter -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200">
                <div class="flex gap-4">
                    <button
                        type="button"
                        @click="activeTab = 'incidents'"
                        :class="activeTab === 'incidents' ? 'border-rose-600 text-rose-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-800'"
                        class="pb-3 border-b-2 text-sm transition"
                    >
                        Pelanggaran & Insiden ({{ incidents.total }})
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'achievements'"
                        :class="activeTab === 'achievements' ? 'border-emerald-600 text-emerald-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-800'"
                        class="pb-3 border-b-2 text-sm transition"
                    >
                        Prestasi Siswa ({{ achievements.total }})
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'assistances'"
                        :class="activeTab === 'assistances' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-800'"
                        class="pb-3 border-b-2 text-sm transition"
                    >
                        Tindak Lanjut & Konseling ({{ assistances.total }})
                    </button>
                </div>

                <div class="pb-2">
                    <select
                        v-model="selectedClass"
                        @change="applyFilters"
                        class="px-3 py-1.5 text-xs border border-slate-300 rounded-lg bg-white"
                    >
                        <option value="">Semua Rombel</option>
                        <option v-for="c in classes" :key="c.id" :value="String(c.id)">
                            Kelas {{ c.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Tab 1: Incidents -->
            <div v-if="activeTab === 'incidents'" class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
                <table class="w-full text-left border-collapse text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="px-5 py-3.5">Tanggal</th>
                            <th class="px-5 py-3.5">Judul Insiden</th>
                            <th class="px-5 py-3.5">Kategori</th>
                            <th class="px-5 py-3.5 text-center">Poin</th>
                            <th class="px-5 py-3.5">Tindak Lanjut / Sanksi</th>
                            <th class="px-5 py-3.5">Pelapor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <tr v-for="inc in incidents.data" :key="inc.id" class="hover:bg-slate-50/80">
                            <td class="px-5 py-3 text-xs text-slate-500 font-mono">{{ inc.incident_date }}</td>
                            <td class="px-5 py-3 font-bold text-slate-900">{{ inc.title }}</td>
                            <td class="px-5 py-3"><span class="px-2 py-0.5 rounded text-xs bg-slate-100 font-medium">{{ inc.category }}</span></td>
                            <td class="px-5 py-3 text-center"><span class="px-2 py-0.5 rounded font-bold text-xs bg-rose-50 text-rose-700">+{{ inc.penalty_points }}</span></td>
                            <td class="px-5 py-3 text-xs text-slate-600">{{ inc.follow_up_action || '-' }}</td>
                            <td class="px-5 py-3 text-xs text-slate-500">{{ inc.recorded_by_user?.name || '-' }}</td>
                        </tr>
                        <tr v-if="incidents.data.length === 0">
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400">Tidak ada riwayat pelanggaran tata tertib.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tab 2: Achievements -->
            <div v-if="activeTab === 'achievements'" class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
                <table class="w-full text-left border-collapse text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="px-5 py-3.5">Tanggal</th>
                            <th class="px-5 py-3.5">Kejuaraan / Prestasi</th>
                            <th class="px-5 py-3.5">Peringkat / Juara</th>
                            <th class="px-5 py-3.5">Tingkat</th>
                            <th class="px-5 py-3.5">Penyelenggara</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <tr v-for="ach in achievements.data" :key="ach.id" class="hover:bg-slate-50/80">
                            <td class="px-5 py-3 text-xs text-slate-500 font-mono">{{ ach.event_date }}</td>
                            <td class="px-5 py-3 font-bold text-slate-900">{{ ach.title }}</td>
                            <td class="px-5 py-3"><span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700">{{ ach.rank }}</span></td>
                            <td class="px-5 py-3 text-xs uppercase font-semibold text-slate-600">{{ ach.level }}</td>
                            <td class="px-5 py-3 text-xs text-slate-500">{{ ach.organizer || '-' }}</td>
                        </tr>
                        <tr v-if="achievements.data.length === 0">
                            <td colspan="5" class="px-5 py-12 text-center text-slate-400">Tidak ada rekam prestasi siswa.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tab 3: Assistances -->
            <div v-if="activeTab === 'assistances'" class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
                <table class="w-full text-left border-collapse text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="px-5 py-3.5">Tanggal</th>
                            <th class="px-5 py-3.5">Bentuk Layanan</th>
                            <th class="px-5 py-3.5">Ringkasan Pembahasan</th>
                            <th class="px-5 py-3.5">Kesepakatan Solusi</th>
                            <th class="px-5 py-3.5">Fasilitator</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <tr v-for="ast in assistances.data" :key="ast.id" class="hover:bg-slate-50/80">
                            <td class="px-5 py-3 text-xs text-slate-500 font-mono">{{ ast.date }}</td>
                            <td class="px-5 py-3"><span class="px-2 py-0.5 rounded text-xs bg-indigo-50 text-indigo-700 font-semibold uppercase">{{ ast.type }}</span></td>
                            <td class="px-5 py-3 text-xs text-slate-800 font-medium">{{ ast.summary }}</td>
                            <td class="px-5 py-3 text-xs text-emerald-700 font-medium">{{ ast.agreed_solution || '-' }}</td>
                            <td class="px-5 py-3 text-xs text-slate-500">{{ ast.facilitator?.name || '-' }}</td>
                        </tr>
                        <tr v-if="assistances.data.length === 0">
                            <td colspan="5" class="px-5 py-12 text-center text-slate-400">Tidak ada rekam tindak lanjut konseling.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Tambah Insiden -->
        <div v-if="showIncidentModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-slate-900">Catat Pelanggaran Tata Tertib</h3>
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
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Poin Pelanggaran *</label>
                            <input v-model="incidentForm.penalty_points" type="number" min="1" max="100" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Pelanggaran *</label>
                        <input v-model="incidentForm.category" type="text" placeholder="contoh: Keterlambatan, Seragam, Merokok" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Insiden *</label>
                        <input v-model="incidentForm.title" type="text" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Kejadian *</label>
                        <textarea v-model="incidentForm.description" rows="3" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tindak Lanjut / Sanksi</label>
                        <input v-model="incidentForm.follow_up_action" type="text" placeholder="contoh: Peringatan lisan & teguran wali kelas" class="w-full px-3 py-2 text-sm border rounded-lg" />
                    </div>
                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="showIncidentModal = false" class="px-4 py-2 text-sm border rounded-lg text-slate-600">Batal</button>
                        <button type="submit" :disabled="incidentForm.processing" class="px-4 py-2 text-sm font-semibold bg-rose-600 text-white rounded-lg">Simpan Pelanggaran</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Tambah Prestasi -->
        <div v-if="showAchievementModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-slate-900">Catat Prestasi Peserta Didik</h3>
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
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kejuaraan / Prestasi *</label>
                        <input v-model="achievementForm.title" type="text" placeholder="contoh: Olimpiade Sains Nasional (OSN) Matematika" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Peringkat / Juara *</label>
                            <input v-model="achievementForm.rank" type="text" placeholder="contoh: Juara 1 / Medali Emas" required class="w-full px-3 py-2 text-sm border rounded-lg" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tingkat *</label>
                            <select v-model="achievementForm.level" class="w-full px-3 py-2 text-sm border rounded-lg bg-white">
                                <option value="school">Sekolah</option>
                                <option value="district">Kecamatan</option>
                                <option value="city">Kabupaten / Kota</option>
                                <option value="province">Provinsi</option>
                                <option value="national">Nasional</option>
                                <option value="international">Internasional</option>
                            </select>
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
    </AppLayout>
</template>
