<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../layouts/AppLayout.vue';

interface ClassItem {
    id: number;
    name: string;
}

interface JournalItem {
    id: number;
    topic: string;
    learning_objectives?: string | null;
    notes?: string | null;
    attendance_summary?: {
        present: number;
        sick: number;
        permitted: number;
        absent: number;
    } | null;
    created_at: string;
    lesson_session: {
        id: number;
        date: string;
        meeting_number: number;
        class_group: { name: string };
        subject: { name: string; code: string };
        teacher: { name: string };
    };
}

const props = defineProps<{
    classes: ClassItem[];
    journals: {
        data: JournalItem[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    };
    filters: {
        class_group_id?: string;
    };
}>();

const selectedClass = ref(props.filters.class_group_id || '');

const applyFilters = () => {
    router.get(
        '/akademik/jurnal',
        {
            class_group_id: selectedClass.value || undefined,
        },
        { preserveState: true }
    );
};
</script>

<template>
    <AppLayout title="Jurnal Mengajar Guru">
        <Head title="Jurnal Mengajar — School Suite" />

        <div class="space-y-6">
            <!-- Header bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Jurnal Mengajar Guru</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Rekam materi pembelajaran, ketercapaian tujuan, dan snapshot kehadiran peserta didik per pertemuan.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <select
                        v-model="selectedClass"
                        @change="applyFilters"
                        class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Semua Rombel</option>
                        <option v-for="c in classes" :key="c.id" :value="String(c.id)">
                            Kelas {{ c.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Journals List -->
            <div class="space-y-4">
                <div
                    v-for="j in journals.data"
                    :key="j.id"
                    class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-3"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100">
                                Pertemuan {{ j.lesson_session.meeting_number }}
                            </span>
                            <span class="font-semibold text-slate-900 text-sm">
                                {{ j.lesson_session.subject.name }} (Kelas {{ j.lesson_session.class_group.name }})
                            </span>
                        </div>

                        <div class="text-xs text-slate-500 flex items-center gap-2">
                            <span>{{ j.lesson_session.date }}</span>
                            <span>•</span>
                            <span class="font-medium text-slate-700">{{ j.lesson_session.teacher.name }}</span>
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Materi Pokok / Topik:</div>
                        <div class="text-base font-bold text-slate-900 mt-0.5">{{ j.topic }}</div>
                    </div>

                    <div v-if="j.learning_objectives" class="text-xs text-slate-600 bg-slate-50 p-3 rounded-lg border border-slate-100">
                        <div class="font-semibold text-slate-700 mb-0.5">Tujuan Pembelajaran:</div>
                        <div>{{ j.learning_objectives }}</div>
                    </div>

                    <!-- Attendance Snapshot -->
                    <div v-if="j.attendance_summary" class="flex items-center gap-4 pt-2 text-xs">
                        <span class="font-semibold text-slate-500">Presensi Sesi:</span>
                        <span class="text-emerald-700 font-medium">Hadir: {{ j.attendance_summary.present }}</span>
                        <span class="text-amber-700 font-medium">Sakit: {{ j.attendance_summary.sick }}</span>
                        <span class="text-blue-700 font-medium">Izin: {{ j.attendance_summary.permitted }}</span>
                        <span class="text-rose-700 font-medium">Alpa: {{ j.attendance_summary.absent }}</span>
                    </div>
                </div>

                <div v-if="journals.data.length === 0" class="bg-white rounded-xl border border-dashed border-slate-300 p-12 text-center text-slate-400">
                    Belum ada riwayat jurnal mengajar untuk rombel yang dipilih.
                </div>
            </div>

            <!-- Pagination footer -->
            <div class="px-5 py-3.5 bg-white rounded-xl border border-slate-200 flex items-center justify-between text-xs text-slate-500 shadow-xs">
                <div>Total {{ journals.total }} jurnal</div>
                <div class="flex gap-1">
                    <template v-for="(link, i) in journals.links" :key="i">
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
    </AppLayout>
</template>
