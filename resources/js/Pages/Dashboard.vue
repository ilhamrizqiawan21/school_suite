<script setup lang="ts">
import AppLayout from '../layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    stats?: {
        modules_ready: number;
        active_period: string;
        framework_version: string;
    };
}>();

const page = usePage<{ auth?: { user?: { name: string; role_label?: string } }; activePeriod?: { label?: string }; [key: string]: unknown }>();
const userName = computed(() => page.props.auth?.user?.name ?? 'Rekan Sekolah');
const greeting = computed(() => {
    const firstName = userName.value.split(' ')[0];
    return `Selamat datang, ${firstName}`;
});

const workflows = [
    { title: 'Data siswa', description: 'Kelola identitas peserta didik, penempatan kelas, dan impor data.', href: '/akademik/siswa', icon: 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m6-10a4 4 0 100-8 4 4 0 000 8zm14 10v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75' },
    { title: 'Presensi siswa', description: 'Catat kehadiran pertemuan dan tinjau rekap kelas.', href: '/akademik/presensi', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0a2 2 0 104 0 2 2 0 00-4 0zm-1 9 2 2 4-4' },
    { title: 'Input nilai', description: 'Isi capaian belajar dan remedial sesuai skema penilaian aktif.', href: '/penilaian/skor', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-2-2a2 2 0 00-2 2v2h6V5a2 2 0 00-2-2h-2zm-1 9h4m-4 4h4' },
    { title: 'Ruang wali kelas', description: 'Pantau kehadiran, observasi, prestasi, dan tindak lanjut siswa.', href: '/wali-kelas', icon: 'M17 20h5v-2a3 3 0 00-5.36-1.86M17 20H7m10 0v-2c0-.66-.13-1.28-.36-1.86m0 0a5 5 0 00-9.28 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
];
</script>

<template>
    <AppLayout title="Beranda">
        <section class="view">
            <div class="page-heading">
                <div>
                    <p class="eyebrow">Ruang kerja sekolah <span v-if="page.props.activePeriod?.label">· {{ page.props.activePeriod.label }}</span></p>
                    <h1>{{ greeting }}</h1>
                    <p>Semua pekerjaan akademik dan administrasi sekolah dimulai dari sini.</p>
                </div>
            </div>

            <div class="attention-strip">
                <span class="pulse-dot" />
                <strong>Periode aktif: {{ page.props.activePeriod?.label ?? 'Belum ada periode akademik aktif' }}</strong>
                <Link href="/akademik/kelas" class="text-button">Lihat kelas <span aria-hidden="true">›</span></Link>
            </div>

            <div class="dashboard-grid workflow-grid">
                <Link v-for="(workflow, index) in workflows" :key="workflow.href" :href="workflow.href" class="panel workflow-card">
                    <span class="metric-icon" :class="['', 'amber', 'green', 'violet'][index]">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path :d="workflow.icon" /></svg>
                    </span>
                    <div class="workflow-copy">
                        <h2>{{ workflow.title }}</h2>
                        <p>{{ workflow.description }}</p>
                    </div>
                    <svg class="workflow-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                </Link>
            </div>

            <div class="panel dashboard-note">
                <div class="note-mark" aria-hidden="true">S</div>
                <div>
                    <h2>Ruang kerja terpadu sekolah</h2>
                    <p>Data siswa, pembelajaran, presensi, penilaian, dan rapor terhubung dalam satu alur kerja. Informasi pada halaman ini mengikuti hak akses akun yang sedang masuk.</p>
                </div>
            </div>
        </section>
    </AppLayout>
</template>

<style scoped>
.workflow-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.workflow-card { min-height: 142px; display: flex; align-items: flex-start; gap: 15px; padding: 22px; color: inherit; text-decoration: none; transition: transform .18s var(--ease), border-color .18s, box-shadow .18s; }
.workflow-card:hover { transform: translateY(-2px); border-color: var(--line-strong); box-shadow: var(--shadow-md); }
.workflow-copy { flex: 1; }
.workflow-copy h2, .dashboard-note h2 { margin: 1px 0 5px; font-size: 15px; }
.workflow-copy p, .dashboard-note p { margin: 0; color: var(--ink-soft); font-size: 11px; line-height: 1.7; }
.workflow-arrow { width: 18px; height: 18px; color: var(--ink-faint); }
.dashboard-note { display: flex; align-items: center; gap: 15px; margin-top: 16px; padding: 20px; }
.note-mark { display: grid; place-items: center; width: 42px; height: 42px; flex: 0 0 auto; color: #1d2b43; background: #f2bc55; border-radius: 14px 14px 14px 5px; font: 800 23px Georgia, serif; }
@media (max-width: 720px) { .workflow-grid { grid-template-columns: 1fr; } }
</style>
