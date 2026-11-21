<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

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
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    summary: {
        academic_year: string;
        term_name: string;
        active_students_count: number;
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

const form = useForm({
    nisn: '',
    name: '',
});

const submit = () => {
    form.post('/data-siswa/cek', { preserveScroll: true });
};

const statusTone = (statusCode?: string): 'success' | 'info' | 'warning' | 'danger' | 'neutral' => {
    if (statusCode === 'active') return 'success';
    if (statusCode === 'graduated') return 'info';
    if (statusCode === 'transferred') return 'warning';
    if (statusCode === 'dropped_out') return 'danger';
    return 'neutral';
};
</script>

<template>
    <Head :title="`Data Siswa — ${school.name}`" />

    <div class="students-page" data-madrasah-service="students" :style="themeStyle">
        <PublicHeader :school="school" />

        <section class="students-hero-banner">
            <div class="students-container students-hero-inner">
                <div class="students-hero-pill">
                    <i class="fa-solid fa-users" aria-hidden="true"></i>
                    <span>Aplikasi Database Siswa</span>
                </div>
                <h1>{{ school.name.toUpperCase() }}</h1>
                <h4>Tahun Pelajaran {{ summary.academic_year }}</h4>
                <p>
                    Informasi agregat peserta didik, rekapitulasi rombongan belajar aktif, serta layanan verifikasi keaktifan siswa berbasis NISN.
                </p>
            </div>
        </section>

        <main class="students-container students-main">
            <section class="students-stats-grid" aria-label="Ringkasan statistik peserta didik">
                <article class="card-custom stat-card">
                    <div class="stat-icon stat-icon-blue">
                        <i class="fa-solid fa-user-graduate" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="stat-label">Total Siswa Aktif</p>
                        <p class="stat-value">{{ summary.active_students_count }}</p>
                    </div>
                </article>
                <article class="card-custom stat-card">
                    <div class="stat-icon stat-icon-emerald">
                        <i class="fa-solid fa-chalkboard" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="stat-label">Rombel / Kelas Aktif</p>
                        <p class="stat-value">{{ summary.active_classes_count }}</p>
                    </div>
                </article>
                <article class="card-custom stat-card">
                    <div class="stat-icon stat-icon-amber">
                        <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="stat-label">Periode Berjalan</p>
                        <p class="stat-value stat-value-sm">{{ summary.term_name }}</p>
                    </div>
                </article>
            </section>

            <div class="students-content-grid">
                <div class="students-primary-col">
                    <section class="card-custom students-card" aria-labelledby="cek-keaktifan-heading">
                        <div class="form-section-header" id="cek-keaktifan-heading">
                            <i class="fa-solid fa-magnifying-glass mr-2" aria-hidden="true"></i>Cek Keaktifan &amp; Status Siswa
                        </div>
                        <p class="text-sm text-slate-600 mb-4">
                            Masukkan <strong>NISN</strong> dan <strong>Nama Siswa</strong> untuk mengonfirmasi status keaktifan peserta didik, kelulusan, atau mutasi secara aman.
                        </p>

                        <form id="form-cek-siswa" @submit.prevent="submit" class="grid gap-4 md:grid-cols-2">
                            <FormField id="student-check-nisn" label="NISN Siswa" :error="form.errors.nisn" required>
                                <input
                                    id="student-check-nisn"
                                    v-model="form.nisn"
                                    type="text"
                                    placeholder="Contoh: 0081234567"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                />
                            </FormField>
                            <FormField id="student-check-name" label="Nama Siswa (Min. 3 Huruf)" :error="form.errors.name" required>
                                <input
                                    id="student-check-name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Masukkan nama lengkap siswa"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                />
                            </FormField>
                            <div class="md:col-span-2">
                                <AppButton type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Memeriksa...' : 'Cek Status Siswa' }}
                                </AppButton>
                            </div>
                        </form>

                        <div
                            v-if="studentResult"
                            class="mt-5 rounded-xl border p-4 text-sm space-y-2"
                            :class="
                                studentResult.found
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-950'
                                    : 'border-rose-200 bg-rose-50 text-rose-950'
                            "
                            role="status"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-semibold">{{ studentResult.message }}</p>
                                <StatusBadge
                                    v-if="studentResult.found && studentResult.status_label"
                                    :status="studentResult.status_label"
                                    :tone="statusTone(studentResult.status_code)"
                                    size="sm"
                                />
                            </div>
                            <div v-if="studentResult.found" class="grid gap-1 text-xs sm:grid-cols-2 pt-1">
                                <p>Nama Siswa: <strong>{{ studentResult.student_name }}</strong></p>
                                <p>Status: <strong>{{ studentResult.status_label }}</strong></p>
                                <p>Kelas / Tingkat: <strong>{{ studentResult.class_or_level }}</strong></p>
                                <p>Tahun Pelajaran: <strong>{{ studentResult.academic_year }}</strong></p>
                            </div>
                        </div>
                    </section>

                    <section class="card-custom students-card" aria-labelledby="rekap-rombel-heading">
                        <div class="form-section-header" id="rekap-rombel-heading">
                            <i class="fa-solid fa-layer-group mr-2" aria-hidden="true"></i>Rekapitulasi Rombel per Tingkat
                        </div>

                        <div v-if="summary.grade_levels.length" class="space-y-4">
                            <div
                                v-for="level in summary.grade_levels"
                                :key="level.grade_level"
                                class="rounded-xl border border-slate-200 p-4"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                    <h2 class="text-sm font-bold text-slate-900">
                                        Tingkat {{ level.grade_level }}
                                    </h2>
                                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 border border-blue-200">
                                        {{ level.students_count }} Siswa
                                    </span>
                                </div>
                                <div class="grid gap-2 sm:grid-cols-2 md:grid-cols-3">
                                    <div
                                        v-for="cg in level.classes"
                                        :key="cg.id"
                                        class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-xs border border-slate-200"
                                    >
                                        <span class="font-semibold text-slate-800">{{ cg.name }}</span>
                                        <span class="font-bold text-slate-600">{{ cg.students_count }} siswa</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-slate-500">
                            Data rombongan belajar aktif belum tersedia untuk periode ini.
                        </p>
                    </section>
                </div>

                <aside class="students-sidebar">
                    <div class="card-custom">
                        <div class="sidebar-card-header">
                            <i class="fa-solid fa-compass text-primary" aria-hidden="true"></i>
                            <span>Layanan Terkait</span>
                        </div>
                        <div class="sidebar-card-body space-y-2">
                            <Link href="/alumni" class="sidebar-action-link">
                                <i class="fa-solid fa-user-graduate" aria-hidden="true"></i>
                                <span>Data Alumni &amp; Tracer</span>
                            </Link>
                            <Link href="/layanan/legalisir" class="sidebar-action-link">
                                <i class="fa-solid fa-certificate" aria-hidden="true"></i>
                                <span>Legalisir Ijazah Online</span>
                            </Link>
                            <Link href="/layanan/mutasi" class="sidebar-action-link">
                                <i class="fa-solid fa-people-arrows" aria-hidden="true"></i>
                                <span>Mutasi Siswa Online</span>
                            </Link>
                            <Link href="/layanan/kelulusan" class="sidebar-action-link">
                                <i class="fa-solid fa-award" aria-hidden="true"></i>
                                <span>Cek Kelulusan &amp; SKL</span>
                            </Link>
                            <Link href="/verifikasi" class="sidebar-action-link">
                                <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                                <span>Verifikasi Dokumen / QR</span>
                            </Link>
                            <a href="/login?portal=siswa" class="sidebar-action-link is-highlight">
                                <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                                <span>Login Portal Siswa / Wali</span>
                            </a>
                        </div>
                    </div>

                    <div class="card-custom">
                        <div class="sidebar-card-header">
                            <i class="fa-solid fa-shield-halved text-emerald-600" aria-hidden="true"></i>
                            <span>Perlindungan Data</span>
                        </div>
                        <div class="sidebar-card-body text-xs text-slate-600 leading-relaxed space-y-2">
                            <p>
                                Halaman publik ini hanya menampilkan statistik agregat peserta didik dan konfirmasi status berbasis kecocokan <strong>NISN + Nama</strong>.
                            </p>
                            <p>
                                Data pribadi lengkap (NIK, alamat, kontak wali, nilai, dan presensi) hanya dapat diakses melalui <strong>Portal Siswa / Wali</strong> atau akun petugas resmi setelah login.
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        </main>
    </div>
</template>

<style scoped>
.students-page {
    min-height: 100vh;
    background-color: #f8fafc;
    color: #1e293b;
}

.students-container {
    width: 100%;
    max-width: 1140px;
    margin: 0 auto;
    padding-inline: 15px;
}

.students-hero-banner {
    background: linear-gradient(135deg, #0f4c81 0%, #1b85df 100%);
    color: #ffffff;
    padding: 40px 0;
    margin-bottom: 28px;
    border-radius: 0 0 20px 20px;
    box-shadow: 0 10px 25px rgba(15, 76, 129, 0.14);
    text-align: center;
}

.students-hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    padding: 4px 12px;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
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
    max-width: 680px;
    margin: 0 auto;
    line-height: 1.6;
    color: #ffffff;
}

.students-main {
    padding-bottom: 48px;
}

.students-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.card-custom {
    background: #ffffff;
    border: none;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 20px;
}

.stat-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.stat-icon-blue {
    background: #eff6ff;
    color: #1d4ed8;
}

.stat-icon-emerald {
    background: #ecfdf5;
    color: #059669;
}

.stat-icon-amber {
    background: #fffbeb;
    color: #d97706;
}

.stat-label {
    margin: 0;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.stat-value {
    margin: 2px 0 0;
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
}

.stat-value-sm {
    font-size: 17px;
}

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
        padding: 32px 16px;
        margin-bottom: 20px;
    }

    .students-hero-banner h1 {
        font-size: 22px;
    }

    .students-stats-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .students-card {
        padding: 18px;
    }
}
</style>
