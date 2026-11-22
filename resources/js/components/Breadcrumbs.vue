<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface BreadcrumbProps {
    title?: string;
}

interface BreadcrumbItem {
    label: string;
    href?: string;
    current: boolean;
}

interface PageProps {
    auth?: { user?: { role?: string | null } };
    assignment?: { title?: string | null };
    exam?: { title?: string | null };
    report?: { student?: { name?: string | null } };
    resource?: { name?: string | null };
    session?: { title?: string | null };
    share?: { resource?: { name?: string | null } };
    student?: { name?: string | null };
    [key: string]: unknown;
}

const props = defineProps<BreadcrumbProps>();
const page = usePage<PageProps>();
const currentRole = computed(() => page.props.auth?.user?.role ?? null);

const segmentLabels: Record<string, string> = {
    akademik: 'Akademik',
    administrasi: 'Tata Usaha',
    analisis: 'Analisis',
    absensi: 'Absensi Siswa',
    'absensi-guru': 'Absensi Guru',
    arsip: 'Arsip',
    'bank-soal': 'Bank Soal',
    'berbagi-soal': 'Berbagi Soal',
    'buku-induk': 'Buku Induk',
    chat: 'Diskusi Chat',
    catatan: 'Catatan Observasi & BK',
    'kelas-daring': 'Kelas Daring',
    kelas: 'Rombel / Kelas',
    kalender: 'Kalender',
    komunikasi: 'Komunikasi',
    'dokumen': 'Dokumen Perencanaan',
    'guru': 'Data Guru',
    'izin-guru': 'Izin & Cuti Guru',
    'jurnal': 'Jurnal Guru',
    'kedisiplinan': 'Kedisiplinan & Prestasi',
    'kepsek': 'Dashboard Kepala Sekolah',
    'laporan': 'Pusat Laporan',
    ledger: 'Ledger Nilai',
    materi: 'Materi',
    nilai: 'Rapor Siswa',
    'paket-soal': 'Paket Soal',
    pelajaran: 'Pelajaran',
    pembelajaran: 'Pembelajaran',
    penilaian: 'Penilaian & Rapor',
    pengaturan: 'Pengaturan',
    pengumpulan: 'Pengumpulan Tugas',
    'pengumuman': 'Pengumuman',
    pengguna: 'Pengguna',
    perfil: 'Profil Saya',
    'performa-guru': 'Kinerja Guru',
    persuratan: 'Persuratan',
    ptsp: 'Pelayanan PTSP',
    proposal: 'Proposal & LPJ',
    integrasi: 'Integrasi & Kanal Eksternal',
    'kartu-siswa': 'Kartu RFID / QR Siswa',
    'kios-presensi': 'Kios Presensi',
    izin: 'Verifikasi Izin Siswa',
    piket: 'Piket',
    'izin-keluar': 'Izin Keluar Siswa',
    'pos-jaga': 'Pos Jaga Satpam',
    bk: 'Bimbingan Konseling',
    kasus: 'Kasus Disiplin BK',
    satgas: 'Satgas Sekolah',
    aduan: 'Antrean Aduan',
    perpustakaan: 'Perpustakaan',
    kelola: 'Kelola Perpustakaan',
    'buku-tamu': 'Buku Tamu Digital',
    profil: 'Profil Saya',
    progress: 'Progress Belajar',
    presensi: 'Absensi Siswa',
    rapor: 'Rapor Siswa',
    rekap: 'Rekap Absensi',
    'rekap-absensi': 'Rekap Absensi',
    'sarana-prasarana': 'Sarana & Prasarana',
    sekolah: 'Profil Sekolah',
    sikap: 'Penilaian Sikap',
    'siklus-siswa': 'Siklus Siswa',
    mutasi: 'Mutasi Siswa',
    kelulusan: 'Kelulusan & SKL',
    alumni: 'Alumni & Tracer',
    legalisir: 'Legalisir Ijazah',
    siswa: 'Data Siswa',
    skema: 'Skema Penilaian',
    skor: 'Input Nilai',
    spmb: 'SPMB',
    statistik: 'Statistik',
    'surat-keluar': 'Surat Keluar',
    'surat-masuk': 'Surat Masuk',
    tugas: 'Tugas',
    ujian: 'Ujian / CBT',
    wali: 'Ruang Wali Kelas',
    'wali-kelas': 'Ruang Wali Kelas',
    whatsapp: 'WhatsApp',
    keamanan: 'Keamanan',
    berita: 'Berita Sekolah',
    galeri: 'Galeri Sekolah',
    'struktur-organisasi': 'Struktur Organisasi',
    'pusat-unduh': 'Pusat Unduh',
    'zona-integritas': 'Zona Integritas & IKM',
};

const rootLinks: Record<string, string> = {
    akademik: '/akademik/siswa',
    administrasi: '/administrasi/persuratan',
    komunikasi: '/komunikasi',
    kepsek: '/dashboard',
    laporan: '/laporan',
    pembelajaran: '/pembelajaran',
    penilaian: '/penilaian/rapor',
    pengaturan: '/pengaturan/pengguna',
    spmb: '/spmb',
    tugas: '/pembelajaran/tugas',
    ujian: '/ujian',
    'wali-kelas': '/wali-kelas',
};

const linkOverrides: Record<string, string> = {
    '/akademik': '/akademik/siswa',
    '/administrasi': '/administrasi/persuratan',
    '/kepsek/laporan': '/laporan',
    '/penilaian': '/penilaian/rapor',
    '/pengaturan': '/pengaturan/pengguna',
    '/siswa': '/siswa/materi',
};

const normalizedPath = computed(() => {
    const rawPath = page.url.split(/[?#]/, 1)[0] || '/';
    if (rawPath === '/') return '/';
    return rawPath.replace(/\/+$/, '');
});

const pathSegments = computed(() => normalizedPath.value.split('/').filter(Boolean));
const isDashboard = computed(() => normalizedPath.value === '/dashboard' || normalizedPath.value === '/kepsek/dashboard');

const isDynamicSegment = (segment: string) => /^\d+$/.test(segment)
    || /^[0-9a-f]{8}-[0-9a-f-]{27,}$/i.test(segment);

const asRecord = (value: unknown): Record<string, unknown> | null => (
    typeof value === 'object' && value !== null ? value as Record<string, unknown> : null
);

const nestedString = (value: unknown, key: string) => {
    const record = asRecord(value);
    const result = record?.[key];
    return typeof result === 'string' && result.trim() ? result.trim() : null;
};

const dynamicLeafTitle = computed(() => {
    const path = normalizedPath.value;
    const propsRecord = page.props as Record<string, unknown>;
    const assignmentTitle = nestedString(propsRecord.assignment, 'title');
    const examTitle = nestedString(propsRecord.exam, 'title');
    const sessionTitle = nestedString(propsRecord.session, 'title');
    const studentName = nestedString(propsRecord.student, 'name')
        ?? nestedString(asRecord(propsRecord.report)?.student, 'name');
    const resourceName = nestedString(propsRecord.resource, 'name')
        ?? nestedString(asRecord(propsRecord.share)?.resource, 'name');

    if ((path.startsWith('/pembelajaran/tugas/') || path.startsWith('/siswa/tugas/')) && assignmentTitle) {
        return path.endsWith('/pengumpulan') ? `Pengumpulan · ${assignmentTitle}` : assignmentTitle;
    }
    if (path.startsWith('/akademik/buku-induk/') && studentName) return `Buku Induk · ${studentName}`;
    if (path.startsWith('/penilaian/rapor/') && studentName) return `Rapor · ${studentName}`;
    if (path.startsWith('/ujian/') && examTitle) {
        if (path.endsWith('/monitor')) return `Monitor · ${examTitle}`;
        if (path.endsWith('/hasil')) return `Hasil · ${examTitle}`;
        return examTitle;
    }
    if (path.startsWith('/akademik/kelas-daring/') && sessionTitle) return `Presensi Daring · ${sessionTitle}`;
    if (path.startsWith('/penilaian/berbagi-soal/') && resourceName) return `Detail berbagi · ${resourceName}`;

    return props.title?.trim() || '';
});

const labelForSegment = (segment: string, index: number) => {
    const segments = pathSegments.value;
    if (segments[0] === 'siswa' && index === 0) return 'Pembelajaran';
    if (segments[0] === 'siswa' && segment === 'progress') return 'Progress Belajar';
    if (segments[0] === 'kepsek' && index === 0) return 'Dashboard Kepala Sekolah';

    return segmentLabels[segment] ?? decodeURIComponent(segment)
        .replace(/[-_]+/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
};

const hrefFor = (prefix: string, segment: string, index: number) => {
    if (currentRole.value === 'student' && (prefix === '/akademik' || (index === 0 && segment === 'akademik'))) {
        return '/akademik/jadwal';
    }
    if (index === 0 && rootLinks[segment]) return rootLinks[segment];
    return linkOverrides[prefix] ?? prefix;
};

const breadcrumbs = computed<BreadcrumbItem[]>(() => {
    if (isDashboard.value) return [];

    const segments = pathSegments.value;
    const visibleSegments = segments
        .map((segment, index) => ({ segment, index }))
        .filter(({ segment }) => !isDynamicSegment(segment));
    const items: BreadcrumbItem[] = [{ label: 'Beranda', href: '/dashboard', current: false }];
    let prefix = '';

    visibleSegments.forEach(({ segment, index }) => {
        prefix += `/${segment}`;
        items.push({
            label: labelForSegment(segment, index),
            href: hrefFor(prefix, segment, index),
            current: false,
        });
    });

    const currentTitle = dynamicLeafTitle.value;
    const lastSegment = segments.at(-1);
    if (currentTitle && lastSegment && isDynamicSegment(lastSegment)) {
        items.push({ label: currentTitle, current: true });
    } else if (items.length > 1) {
        const current = items.at(-1);
        if (current) {
            current.label = currentTitle || current.label;
            current.current = true;
            delete current.href;
        }
    }

    return items;
});
</script>

<template>
    <nav v-if="breadcrumbs.length" class="breadcrumbs" aria-label="Breadcrumb">
        <ol>
            <li v-for="(item, index) in breadcrumbs" :key="`${item.label}-${index}`">
                <Link v-if="item.href && !item.current" :href="item.href">{{ item.label }}</Link>
                <span v-else :aria-current="item.current ? 'page' : undefined">{{ item.label }}</span>
            </li>
        </ol>
    </nav>
</template>
