<script setup lang="ts">
import { usePublicContentText } from '@/composables/usePublicContentText';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { Link } from '@inertiajs/vue3';
import Head from '@/components/PublicHead.vue';
import { computed, ref, watch } from 'vue';

interface ClassSummary {
    id: number;
    name: string;
    students_count: number;
}

interface GradeLevelSummary {
    grade_level: number;
    students_count: number;
    classes: ClassSummary[];
}

const props = defineProps<{
    showDemoDirectory?: boolean;
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    summary: {
        academic_year: string;
        term_name: string;
        active_students_count: number;
        male_students_count: number;
        female_students_count: number;
        active_classes_count: number;
        grade_levels: GradeLevelSummary[];
    };
    studentResult?: {
        found: boolean;
        status_code?: string;
        status_label?: string;
        student_name?: string;
        class_or_level?: string;
        academic_year?: string;
        message: string;
    } | null;
}>();

const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const selectedGrade = ref<number | null>(null);
const classQuery = ref('');
const demoClass = ref('');
const demoStudents = [
    { name: 'Aruna Pradipta', gender: 'Laki-laki', initials: 'AP', className: 'X-A' },
    { name: 'Binar Kinasih', gender: 'Perempuan', initials: 'BK', className: 'X-B' },
    { name: 'Cakra Wisesa', gender: 'Laki-laki', initials: 'CW', className: 'XI-A' },
    { name: 'Dara Paramesti', gender: 'Perempuan', initials: 'DP', className: 'XI-A' },
    { name: 'Embun Anindita', gender: 'Perempuan', initials: 'EA', className: 'XII-A' },
    { name: 'Fajar Samudra', gender: 'Laki-laki', initials: 'FS', className: 'XII-A' },
];
const demoClasses = [...new Set(demoStudents.map((student) => student.className))];
const visibleDemoStudents = computed(() => demoStudents.filter((student) =>
    (!demoClass.value || student.className === demoClass.value)
    && student.name.toLocaleLowerCase('id').includes(classQuery.value.trim().toLocaleLowerCase('id')),
));
const demoHeaders = ['No', 'Profil', 'Nama Lengkap Siswa', 'Jenis Kelamin', 'Tempat Lahir & Umur', 'NIS', 'NISN', 'Kelas'];
const pageSize = ref(25);
const directoryPage = ref(1);
const pageCount = computed(() => Math.max(1, Math.ceil(visibleDemoStudents.value.length / pageSize.value)));
const pageOffset = computed(() => (directoryPage.value - 1) * pageSize.value);
const pageStudents = computed(() => visibleDemoStudents.value.slice(pageOffset.value, pageOffset.value + pageSize.value));
watch([demoClass, classQuery, pageSize], () => { directoryPage.value = 1; });
const printDirectory = () => window.print();
const visibleClasses = computed(() => {
    const query = classQuery.value.trim().toLocaleLowerCase('id');
    return props.summary.grade_levels
        .filter((level) => selectedGrade.value === null || level.grade_level === selectedGrade.value)
        .flatMap((level) => level.classes.map((classGroup) => ({ ...classGroup, grade: level.grade_level })))
        .filter((classGroup) => classGroup.name.toLocaleLowerCase('id').includes(query));
});

const publicText = usePublicContentText();
</script>

<template>
    <Head :title="`Data Siswa — ${school.name}`" />

    <div class="students-page" data-madrasah-service="students" :style="themeStyle">
        <PublicHeader :school="school" />

        <section class="students-hero-banner">
            <div class="students-container students-hero-inner">
                <div class="students-hero-pill">
                    <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
                    <span>{{ publicText("copy.vue_students_index", "text_001", "Direktori Resmi Sekolah") }}</span>
                </div>
                <h1>{{ publicText("copy.vue_students_index", "text_002", "Database Siswa Aktif") }}</h1>
                <p>
                    {{ publicText("copy.vue_students_index", "text_9001", "Rekapitulasi peserta didik aktif Tahun Ajaran") }} {{ summary.academic_year }} — {{ school.name }}
                </p>
            </div>
        </section>

        <main class="students-container students-main">
            <section class="students-stats-grid" :aria-label="publicText('copy.vue_students_index', 'aria_label_026', 'Ringkasan statistik peserta didik')">
                <div><strong>{{ summary.active_students_count }}</strong><span>{{ publicText("copy.vue_students_index", "text_003", "Total Siswa Aktif") }}</span></div>
                <div><strong>{{ summary.male_students_count }}</strong><span>{{ publicText("copy.vue_students_index", "text_004", "Laki-laki") }}</span></div>
                <div><strong>{{ summary.female_students_count }}</strong><span>{{ publicText("copy.vue_students_index", "text_005", "Perempuan") }}</span></div>
                <div><strong>{{ summary.active_classes_count }}</strong><span>{{ publicText("copy.vue_students_index", "text_006", "Rombel / Kelas") }}</span></div>
            </section>

            <section class="card-custom students-card mb-6" aria-labelledby="rekap-rombel-heading">
                <div class="students-filters">
                    <FormField v-if="showDemoDirectory" id="student-class-filter" :label="publicText('copy.vue_students_index', 'label_10001', 'Filter Berdasarkan Kelas / Rombel')">
                        <select id="student-class-filter" v-model="demoClass" class="w-full rounded-xl border border-slate-300 px-3 py-3">
                            <option value="">{{ publicText("copy.vue_students_index", "text_007", "Tampilkan Semua Kelas") }}</option>
                            <option v-for="className in demoClasses" :key="className">{{ className }}</option>
                        </select>
                    </FormField>
                    <FormField v-else id="student-grade-filter" :label="publicText('copy.vue_students_index', 'label_10002', 'Filter Berdasarkan Tingkat')">
                        <select id="student-grade-filter" v-model="selectedGrade" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option :value="null">{{ publicText("copy.vue_students_index", "text_008", "Semua tingkat") }}</option>
                            <option v-for="level in summary.grade_levels" :key="level.grade_level" :value="level.grade_level">
                                {{ publicText("copy.vue_students_index", "text_024", "Tingkat") }} {{ level.grade_level }}
                            </option>
                        </select>
                    </FormField>
                    <FormField id="student-class-search" :label="showDemoDirectory ? 'Cari Nama / Data Cepat' : 'Cari Kelas / Rombel'">
                        <input id="student-class-search" v-model="classQuery" type="search" :placeholder="showDemoDirectory ? 'Ketik nama siswa…' : 'Ketik nama kelas…'" class="w-full rounded-xl border border-slate-300 px-3 py-3" />
                    </FormField>
                    <div class="students-filter-actions">
                        <button type="button" @click="selectedGrade = null; demoClass = ''; classQuery = ''">{{ publicText("copy.vue_students_index", "text_009", "Reset") }}</button>
                        <Link href="/login?portal=siswa">{{ publicText("copy.vue_students_index", "text_010", "Login Siswa") }}</Link>
                    </div>
                </div>
            </section>

            <section v-if="showDemoDirectory" class="card-custom students-card mb-6" aria-labelledby="demo-directory-heading">
                <h2 id="demo-directory-heading" class="students-table-title"><i class="fa-solid fa-address-book text-blue-600 mr-2" aria-hidden="true"></i>{{ publicText("copy.vue_students_index", "text_011", "Daftar Seluruh Siswa Aktif") }}</h2>
                <p class="mb-3 text-xs text-slate-600">{{ publicText("copy.vue_students_index", "text_012", "Pratinjau lokal dengan data siswa sintetis.") }}</p>
                <div class="mb-7 flex flex-wrap items-center justify-between gap-3">
                    <button type="button" class="rounded-full border border-blue-500 px-4 py-2 text-xs font-bold text-blue-700" @click="printDirectory">{{ publicText("copy.vue_students_index", "text_013", "Cetak") }}</button>
                    <label class="text-xs">{{ publicText("copy.vue_students_index", "text_014", "Tampil") }}
                        <select v-model.number="pageSize" class="rounded border border-slate-300 px-1 py-0.5">
                            <option v-for="size in [10, 25, 50, 100]" :key="size" :value="size">{{ size }}</option>
                        </select>
                        {{ publicText("copy.vue_students_index", "text_015", "data") }}
                    </label>
                </div>
                <div class="overflow-x-auto">
                    <table class="demo-student-table">
                        <thead><tr><th v-for="heading in demoHeaders" :key="heading" scope="col">{{ heading }}</th></tr></thead>
                        <tbody>
                            <tr v-for="(student, index) in pageStudents" :key="student.name">
                                <td>{{ pageOffset + index + 1 }}</td>
                                <td><span class="demo-student-avatar">{{ student.initials }}</span></td>
                                <th scope="row">{{ student.name }}</th>
                                <td><span class="demo-student-gender">{{ student.gender }}</span></td>
                                <td>{{ publicText("copy.vue_students_index", "text_016", "Martapura · 16 thn") }}</td>
                                <td>{{ publicText("copy.vue_students_index", "text_017", "DEMO***") }}</td>
                                <td>{{ publicText("copy.vue_students_index", "text_018", "DEMO****") }}</td>
                                <td><span class="demo-student-class">{{ student.className }}</span></td>
                            </tr>
                            <tr v-if="!visibleDemoStudents.length"><td colspan="8">{{ publicText("copy.vue_students_index", "text_019", "Tidak ada siswa demo yang cocok.") }}</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-5 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600">
                    <p>{{ publicText("copy.vue_students_index", "text_9002", "Menampilkan") }} {{ pageStudents.length ? pageOffset + 1 : 0 }} {{ publicText("copy.vue_students_index", "text_9003", "s/d") }} {{ pageOffset + pageStudents.length }} {{ publicText("copy.vue_students_index", "text_9004", "dari") }} {{ visibleDemoStudents.length }} {{ publicText("copy.vue_students_index", "text_9005", "siswa") }}</p>
                    <nav class="flex" :aria-label="publicText('copy.vue_students_index', 'aria_label_027', 'Halaman daftar siswa')">
                        <button type="button" class="border border-slate-200 px-3 py-2 disabled:opacity-50" :disabled="directoryPage === 1" :aria-label="publicText('copy.vue_students_index', 'aria_label_028', 'Halaman sebelumnya')" @click="directoryPage--">‹</button>
                        <button v-for="number in pageCount" :key="number" type="button" class="border border-slate-200 px-3 py-2" :class="{ 'bg-blue-600 text-white': directoryPage === number }" :aria-current="directoryPage === number ? 'page' : undefined" @click="directoryPage = number">{{ number }}</button>
                        <button type="button" class="border border-slate-200 px-3 py-2 disabled:opacity-50" :disabled="directoryPage === pageCount" :aria-label="publicText('copy.vue_students_index', 'aria_label_029', 'Halaman berikutnya')" @click="directoryPage++">›</button>
                    </nav>
                </div>
            </section>

            <section v-else class="card-custom students-card mb-6" aria-labelledby="rekap-rombel-heading">
                <h2 id="rekap-rombel-heading" class="students-table-title">{{ publicText("copy.vue_students_index", "text_020", "Daftar Rombongan Belajar Aktif") }}</h2>
                <p class="mb-5 text-xs text-slate-600">{{ publicText("copy.vue_students_index", "text_021", "Rekap jumlah siswa aktif per rombongan belajar.") }}</p>
                <div class="overflow-x-auto">
                    <table v-if="visibleClasses.length" class="w-full border-collapse text-sm">
                        <thead class="bg-slate-100 text-slate-700">
                            <tr>
                                <th scope="col" class="px-3 py-3 text-left">{{ publicText("copy.vue_students_index", "text_022", "No.") }}</th>
                                <th scope="col" class="px-3 py-3 text-left">{{ publicText("copy.vue_students_index", "text_023", "Kelas") }}</th>
                                <th scope="col" class="px-3 py-3 text-left">{{ publicText("copy.vue_students_index", "text_024", "Tingkat") }}</th>
                                <th scope="col" class="px-3 py-3 text-right">{{ publicText("copy.vue_students_index", "text_025", "Siswa Aktif") }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(classGroup, index) in visibleClasses" :key="classGroup.id" class="border-b border-slate-200 even:bg-slate-50">
                                <td class="px-3 py-3">{{ index + 1 }}</td>
                                <th scope="row" class="px-3 py-3 text-left">{{ classGroup.name }}</th>
                                <td class="px-3 py-3">{{ classGroup.grade }}</td>
                                <td class="px-3 py-3 text-right">{{ classGroup.students_count }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="py-4 text-sm text-slate-600" role="status">
                        {{ summary.grade_levels.length ? 'Tidak ada kelas yang cocok dengan filter.' : 'Data rombongan belajar aktif belum tersedia untuk periode ini.' }}
                    </p>
                </div>
            </section>

        </main>
    </div>
</template>

<style scoped>
.students-page {
    background-color: #f8fafc;
    color: #1e293b;
}

.students-container {
    width: 100%;
    max-width: 1230px;
    margin: 0 auto;
    padding-inline: 15px;
}

.students-hero-banner {
    background: radial-gradient(ellipse at right, #233d7a, transparent 46%), linear-gradient(120deg, #121720, #151e34);
    color: #ffffff;
    padding: 45px 0 41px;
    border-radius: 0 0 32px 32px;
    box-shadow: 0 16px 32px #121a3040;
    text-align: center;
}

.students-hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    padding: 7px 18px;
    border-radius: 9999px;
    background: #a26b262e;
    border: 1px solid #a26b2699;
    font-size: 12px;
    font-weight: 700;
    color: #fef08a;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.students-hero-banner h1 {
    margin: 0 0 6px;
    font-weight: 800;
    font-size: 26px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: #ffffff;
    line-height: 1.25;
}

.students-hero-banner h4 {
    margin: 0 0 10px;
    font-weight: 600;
    font-size: 16px;
    opacity: 0.95;
    color: #93c5fd;
    line-height: 1.3;
}

.students-hero-banner p {
    font-size: 14.5px;
    opacity: 0.9;
    max-width: 920px;
    margin: 0 auto;
    line-height: 1.6;
    color: #93c5fd;
}

.students-main {
    position: relative;
    margin-top: -28px;
    width: min(100%, calc(90% + 30px));
    padding-bottom: 48px;
}

.students-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    margin-bottom: 20px;
    padding: 20px 10px;
    background: white;
    border: 1px solid #dce5f0;
    border-radius: 22px;
    box-shadow: 0 10px 28px #1729440f;
    text-align: center;
}

.card-custom {
    background: #ffffff;
    border: 1px solid #dce5f0;
    border-radius: 22px;
    box-shadow: 0 10px 28px #1729440f;
}

.students-stats-grid > div { padding: 6px 8px 10px; }
.students-stats-grid > div + div { border-left: 1px solid #edf2f7; }
.students-stats-grid strong { display: block; color: #007bff; font-size: 25px; font-weight: 900; }
.students-stats-grid > div:nth-child(2) strong { color: #159eb3; }
.students-stats-grid > div:nth-child(3) strong { color: #dc3545; }
.students-stats-grid > div:nth-child(4) strong { color: #28a745; }
.students-stats-grid span { color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; }
.students-filters { display: grid; grid-template-columns: 1.25fr 1fr auto; gap: 24px; align-items: end; }
.students-filter-actions { display: flex; flex-wrap: wrap; gap: 6px; justify-content: flex-end; }
.students-filter-actions :is(button, a) { border: 1px solid #007bff; border-radius: 4px; padding: 8px 12px; color: #007bff; font-size: 13px; font-weight: 700; }
.students-filter-actions a { background: #007bff; color: white; }
.students-table-title { font-size: 20px; font-weight: 700; }
.demo-student-table { width: 100%; min-width: 770px; border-collapse: separate; border-spacing: 0 6px; font-size: 13px; }
.demo-student-table :is(th, td) { padding: 16px 12px; text-align: left; }
.demo-student-table thead th { background: #eef3f8; color: #475569; text-transform: uppercase; }
.demo-student-table tbody :is(th, td) { border-block: 1px solid #edf2f7; }
.demo-student-table tr > :first-child { border-radius: 12px 0 0 12px; }
.demo-student-table tr > :last-child { border-radius: 0 12px 12px 0; }
.demo-student-avatar { display: grid; place-items: center; width: 38px; height: 38px; border-radius: 50%; background: #e0ebff; color: #164dab; font-weight: 800; }
.demo-student-gender, .demo-student-class { display: inline-block; border-radius: 999px; padding: 6px 10px; font-weight: 700; }
.demo-student-gender { background: #e0f2fe; color: #0369a1; }
.demo-student-class { background: #2656cb; color: white; white-space: nowrap; }

.students-content-grid {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
    gap: 24px;
    align-items: start;
}

.students-primary-col {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.students-card {
    padding: 24px;
}

.form-section-header {
    background: #eef5ff;
    padding: 10px 15px;
    border-left: 4px solid #007bff;
    border-radius: 4px;
    font-weight: 700;
    color: #0056b3;
    margin-bottom: 16px;
    font-size: 15px;
}

.students-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.sidebar-card-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 18px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.sidebar-card-body {
    padding: 16px 18px;
}

.sidebar-action-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #334155;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
}

.sidebar-action-link:hover {
    background: #eff6ff;
    border-color: #93c5fd;
    color: #1d4ed8;
}

.sidebar-action-link.is-highlight {
    background: #0f4c81;
    border-color: #0f4c81;
    color: #ffffff;
}

.sidebar-action-link.is-highlight:hover {
    background: #1b85df;
    border-color: #1b85df;
    color: #ffffff;
}

@media (max-width: 991.98px) {
    .students-content-grid {
        grid-template-columns: minmax(0, 1fr);
    }
}

@media (max-width: 767.98px) {
    .students-hero-banner {
        padding: 32px 16px 48px;
    }

    .students-hero-banner h1 {
        font-size: 22px;
    }

    .students-stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .students-filters {
        grid-template-columns: minmax(0, 1fr);
        gap: 12px;
    }

    .students-card {
        padding: 18px;
    }
}
</style>
