<script setup lang="ts">
import AppLayout from '../layouts/AppLayout.vue';

defineProps<{
    stats?: {
        modules_ready: number;
        active_period: string;
        framework_version: string;
    };
}>();

const workAreas = [
    { title: 'Akademik', desc: 'Master data siswa, guru, kelas, enrollment, dan kalender pendidikan.', status: 'Fondasi Siap', tagColor: 'bg-blue-50 text-blue-700' },
    { title: 'Pembelajaran', desc: 'Jadwal mengajar, materi, tugas kelas, dan presensi pertemuan.', status: 'Tahap S04', tagColor: 'bg-amber-50 text-amber-700' },
    { title: 'Wali Kelas', desc: 'Rekap kehadiran kelas perwalian, catatan perkembangan, dan tindak lanjut.', status: 'Tahap S05', tagColor: 'bg-indigo-50 text-indigo-700' },
    { title: 'Penilaian & Rapor', desc: 'Skema bobot nilai, input capaian, remedial, dan snapshot rapor.', status: 'Tahap S06', tagColor: 'bg-purple-50 text-purple-700' },
    { title: 'Administrasi', desc: 'SPMB, tata usaha, persuratan, dan inventaris sarana prasarana.', status: 'Tahap S08', tagColor: 'bg-slate-100 text-slate-700' },
    { title: 'Laporan & Audit', desc: 'Ekspor laporan periode, log audit aktivitas, dan pencatatan perubahan.', status: 'Terintegrasi', tagColor: 'bg-emerald-50 text-emerald-700' },
];
</script>

<template>
    <AppLayout title="Beranda">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-900 tracking-tight">Ruang Kerja Terpadu Sekolah</h1>
                        <p class="text-sm text-slate-500 mt-1">
                            Satu sumber data siswa untuk administrasi, pembelajaran, dan penilaian tanpa pengulangan input.
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                            ● Fondasi S01 Aktif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Work Areas Grid -->
            <div>
                <h2 class="text-base font-semibold text-slate-900 mb-3">Area Kerja Utama</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="area in workAreas"
                        :key="area.title"
                        class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs hover:border-slate-300 transition-colors flex flex-col justify-between"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-sm font-semibold text-slate-900">{{ area.title }}</h3>
                                <span :class="['text-xs px-2 py-0.5 rounded-md font-medium', area.tagColor]">
                                    {{ area.status }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                {{ area.desc }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

