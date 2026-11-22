<script setup lang="ts">
import { usePublicContentText } from '@/composables/usePublicContentText';
import { router } from '@inertiajs/vue3';
import Head from '@/components/PublicHead.vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
const publicText = usePublicContentText();

interface AlumniEntry {
    id: number;
    full_name: string;
    graduation_year: number;
    major_or_class?: string | null;
    current_status?: string;
    status_label: string;
    institution_or_company?: string | null;
    position_or_program?: string | null;
    scholarship_info?: string | null;
    testimonial?: string | null;
    contact_email?: string | null;
    contact_phone?: string | null;
}

interface Opportunity {
    id: number;
    title: string;
    excerpt?: string | null;
    url: string;
}

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    summary: { total: number; studying: number; working: number; entrepreneur: number };
    statistics: { statuses: { status: string; label: string; total: number }[]; campuses: { name: string; total: number }[] };
    directory: {
        data: AlumniEntry[];
        total: number;
        current_page: number;
        last_page: number;
        per_page: number | 'all';
        years: { graduation_year: number; total: number }[];
        majors: string[];
    };
    spotlight: AlumniEntry[];
    filters: { search?: string; graduation_year?: string | number; current_status?: string; major_or_class?: string };
    statusOptions: { value: string; label: string }[];
    opportunities: { careers: Opportunity[]; scholarships: Opportunity[] };
    section?: string;
    contentBlocks?: Record<string, Record<string, any>>;
}>();

const heroBlock = computed(() => props.contentBlocks?.['alumni.hero'] ?? {});

const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const query = ref(props.filters.search || '');
const heroQuery = ref(props.filters.search || '');
const year = ref(String(props.filters.graduation_year || ''));
const status = ref(props.filters.current_status || '');
const major = ref(props.filters.major_or_class || '');
const pageSize = ref(props.directory.per_page);
const view = ref<'gallery' | 'grid' | 'table'>('gallery');
const activeSection = ref(props.section || 'directory');
const searching = ref(false);
const careerSearch = ref('');
const scholarshipSearch = ref('');
const ekartuQuery = ref('');
const ekartuMatched = ref<{ id: number; full_name: string; graduation_year: number; major_or_class?: string | null; has_ekta?: boolean } | null>(null);
const ekartuSearched = ref(false);
const ekartuLoading = ref(false);
const selectedAlumnus = ref<AlumniEntry | null>(null);
const showStatsModal = ref(false);

const handleCustomTab = (event: Event) => {
    const detail = (event as CustomEvent<{ tab?: string }>).detail;
    if (detail?.tab) {
        selectTab(detail.tab);
        document.getElementById('ika-portal-tabs')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const handleCustomModal = (event: Event) => {
    const detail = (event as CustomEvent<{ modal?: string }>).detail;
    if (detail?.modal === 'stats') {
        showStatsModal.value = true;
    }
};

onMounted(() => {
    window.addEventListener('ika:select-tab', handleCustomTab);
    window.addEventListener('ika:open-modal', handleCustomModal);
    try {
        const saved = localStorage.getItem('alumni_theme_preference');
        if (saved && ['blue', 'red', 'green'].includes(saved)) {
            document.body.dataset.ikaTheme = saved;
            document.querySelectorAll('[data-ika-theme]').forEach((btn) => {
                btn.setAttribute('aria-pressed', String((btn as HTMLElement).dataset.ikaTheme === saved));
            });
        }
    } catch (_) {}
});

onUnmounted(() => {
    window.removeEventListener('ika:select-tab', handleCustomTab);
    window.removeEventListener('ika:open-modal', handleCustomModal);
});

const portalTabs = computed(() => [
    { id: 'directory', title: publicText("copy.vue_alumni_home", "text_12001", "Direktori Alumni"), icon: 'users', tone: 'text-blue-600' },
    { id: 'tracer', title: publicText("copy.vue_alumni_home", "text_12002", "Tracer Study Mandiri"), icon: 'pen-to-square', tone: 'text-amber-500' },
    { id: 'careers', title: `Bursa Karir (${props.opportunities.careers.length})`, icon: 'briefcase', tone: 'text-emerald-600' },
    { id: 'scholarships', title: `Pusat Beasiswa (${props.opportunities.scholarships.length})`, icon: 'graduation-cap', tone: 'text-purple-600' },
    { id: 'kta', title: publicText("copy.vue_alumni_home", "text_12003", "E-KTA Digital"), icon: 'id-card', tone: 'text-amber-500' },
]);

const services = computed(() => [
    {
        title: publicText("copy.vue_alumni_home", "text_12004", "Direktori Temu Alumni"),
        description: publicText("copy.vue_alumni_home", "text_12005", "Pencarian interaktif teman seangkatan, jaringan profesional, kontak lintas jurusan, dan persebaran domisili di seluruh Indonesia."),
        href: '/alumni#direktori-alumni',
        tab: 'directory',
        icon: 'users',
        iconBox: 'bg-blue-50 text-blue-600',
        linkColor: 'text-blue-700 hover:text-blue-800',
        action: 'Jelajahi Direktori',
    },
    {
        title: publicText("copy.vue_alumni_home", "text_12006", "Tracer Study Terintegrasi"),
        description: publicText("copy.vue_alumni_home", "text_12007", "Pendataan resmi kelanjutan studi, karir instansi, dan wirausaha untuk peningkatan akreditasi madrasah serta evaluasi kurikulum."),
        href: '/layanan/tracer-alumni',
        icon: 'chart-line',
        iconBox: 'bg-emerald-50 text-emerald-600',
        linkColor: 'text-emerald-600 hover:text-emerald-700',
        action: 'Isi Tracer Study',
    },
    {
        title: publicText("copy.vue_alumni_home", "text_12008", "Bursa Karir & Info Magang"),
        description: publicText("copy.vue_alumni_home", "text_12009", "Peluang kerja aktif dari BUMN, swasta terpercaya, dan jejaring bisnis alumni untuk lulusan baru maupun tenaga profesional."),
        href: '/alumni?section=careers#ika-portal-tabs',
        tab: 'careers',
        icon: 'briefcase',
        iconBox: 'bg-amber-50 text-amber-600',
        linkColor: 'text-amber-600 hover:text-amber-700',
        action: `Lihat Lowongan (${props.opportunities.careers.length})`,
    },
    {
        title: publicText("copy.vue_alumni_home", "text_12010", "Pusat Info Beasiswa"),
        description: publicText("copy.vue_alumni_home", "text_12011", "Informasi lengkap beasiswa KIP Kuliah, beasiswa santri Kemenag, beasiswa prestasi PTN/PTS, serta program beasiswa unggulan S1/S2."),
        href: '/alumni?section=scholarships#ika-portal-tabs',
        tab: 'scholarships',
        icon: 'graduation-cap',
        iconBox: 'bg-purple-50 text-purple-600',
        linkColor: 'text-purple-600 hover:text-purple-700',
        action: `Peluang Beasiswa (${props.opportunities.scholarships.length})`,
    },
    {
        title: publicText("copy.vue_alumni_home", "text_12012", "KTA Digital & Legalisir Online"),
        description: publicText("copy.vue_alumni_home", "text_12013", "Cetak Kartu Tanda Anggota (KTA) resmi ber-QR Code secara instan dan nikmati kemudahan pengajuan legalisir ijazah online tanpa antre."),
        href: '/layanan/tracer-alumni#alumni-kta',
        tab: 'kta',
        icon: 'id-card',
        iconBox: 'bg-rose-50 text-rose-600',
        linkColor: 'text-rose-600 hover:text-rose-700',
        action: 'Cetak KTA',
    },
    {
        title: publicText("copy.vue_alumni_home", "text_12014", "Mentorship & Almamater Care"),
        description: publicText("copy.vue_alumni_home", "text_12015", "Ruang kontribusi alumni untuk adik-adik kelas melalui webinar persiapan masuk PTN, pelatihan skill kerja, dan kegiatan sosial madrasah."),
        href: '/kontak',
        icon: 'handshake-angle',
        iconBox: 'bg-teal-50 text-teal-600',
        linkColor: 'text-teal-600 hover:text-teal-700',
        action: 'Bangun Kolaborasi',
    },
]);

const shortcuts = computed(() => [
    { title: publicText("copy.vue_alumni_home", "text_12004", "Direktori Temu Alumni"), subtitle: publicText("copy.vue_alumni_home", "text_12016", "Pencarian Teman Seangkatan"), href: '/alumni#direktori-alumni', tab: 'directory', icon: 'users', tone: 'text-amber-400' },
    { title: publicText("copy.vue_alumni_home", "text_12002", "Tracer Study Mandiri"), subtitle: publicText("copy.vue_alumni_home", "text_12017", "Pembaruan Status & Karir"), href: '/layanan/tracer-alumni', icon: 'pen-to-square', tone: 'text-amber-400' },
    { title: publicText("copy.vue_alumni_home", "text_12018", "Bursa Karir & Magang"), subtitle: `${props.opportunities.careers.length} Lowongan Terverifikasi`, href: '/alumni?section=careers#ika-portal-tabs', tab: 'careers', icon: 'briefcase', tone: 'text-emerald-400' },
    { title: publicText("copy.vue_alumni_home", "text_12019", "Pusat Beasiswa"), subtitle: `${props.opportunities.scholarships.length} Peluang Aktif`, href: '/alumni?section=scholarships#ika-portal-tabs', tab: 'scholarships', icon: 'graduation-cap', tone: 'text-purple-400' },
    { title: publicText("copy.vue_alumni_home", "text_12020", "KTA Digital Resmi"), subtitle: publicText("copy.vue_alumni_home", "text_12021", "Kartu Anggota Ber-QR Code"), href: '/layanan/tracer-alumni#alumni-kta', tab: 'kta', icon: 'id-card', tone: 'text-amber-500' },
    { title: publicText("copy.vue_alumni_home", "text_12022", "Legalisir Ijazah Online"), subtitle: publicText("copy.vue_alumni_home", "text_12023", "Layanan Legalisir Tanpa Antre"), href: '/layanan/legalisir', icon: 'certificate', tone: 'text-rose-400' },
    { title: publicText("copy.vue_alumni_home", "text_12024", "Statistik & Capaian"), subtitle: publicText("copy.vue_alumni_home", "text_12025", "Sebaran Kampus & Karir"), href: '#statistik-alumni', modal: 'stats', icon: 'chart-pie', tone: 'text-sky-400' },
]);

const groupedByYear = computed(() => {
    const groups = new Map<number, { year: number; total: number; items: AlumniEntry[] }>();
    for (const y of props.directory.years) {
        groups.set(y.graduation_year, { year: y.graduation_year, total: y.total, items: [] });
    }
    for (const entry of props.directory.data) {
        if (!groups.has(entry.graduation_year)) {
            groups.set(entry.graduation_year, { year: entry.graduation_year, total: 1, items: [] });
        }
        groups.get(entry.graduation_year)!.items.push(entry);
    }
    return [...groups.values()].filter((g) => g.items.length > 0);
});

const filteredCareers = computed(() => {
    const q = careerSearch.value.trim().toLowerCase();
    if (!q) return props.opportunities.careers;
    return props.opportunities.careers.filter((item) => `${item.title} ${item.excerpt || ''}`.toLowerCase().includes(q));
});

const filteredScholarships = computed(() => {
    const q = scholarshipSearch.value.trim().toLowerCase();
    if (!q) return props.opportunities.scholarships;
    return props.opportunities.scholarships.filter((item) => `${item.title} ${item.excerpt || ''}`.toLowerCase().includes(q));
});

const statusSegments = computed(() => {
    const colors: Record<string, string> = { studying: '#f59e0b', working: '#10b981', civil_servant: '#3b82f6', job_seeking: '#8b5cf6', entrepreneur: '#f97316', freelance: '#06b6d4', homemaker: '#ec4899', retired: '#64748b', training: '#84cc16', other: '#14b8a6' };
    const total = props.statistics.statuses.reduce((sum, item) => sum + item.total, 0);
    let offset = 0;
    return props.statistics.statuses.map((item) => {
        const size = total ? item.total / total * 100 : 0;
        const segment = { ...item, color: colors[item.status] || '#10b981', size, offset };
        offset += size;
        return segment;
    });
});
const campusScale = computed(() => Math.max(1, ...props.statistics.campuses.map((item) => item.total)));
const campusTicks = computed(() => Array.from({ length: Math.min(4, campusScale.value) + 1 }, (_, index) => {
    const steps = Math.min(4, campusScale.value);
    return Math.round(index * campusScale.value / steps);
}));

function statusBadgeClass(entry: AlumniEntry): string {
    const code = entry.current_status || '';
    if (code === 'studying') return 'badge-status-kuliah';
    if (code === 'working') return 'badge-status-bekerja';
    if (code === 'civil_servant') return 'badge-status-asn';
    if (code === 'entrepreneur') return 'badge-status-wirausaha';
    if (code === 'freelance') return 'badge-status-freelance';
    if (code === 'homemaker') return 'badge-status-irt';
    if (code === 'training') return 'badge-status-pelatihan';
    return 'badge-status-lainnya';
}

function statusIconClass(entry: AlumniEntry): string {
    const code = entry.current_status || '';
    if (code === 'studying') return 'fa-graduation-cap';
    if (code === 'working') return 'fa-briefcase';
    if (code === 'civil_servant') return 'fa-building-columns';
    if (code === 'entrepreneur') return 'fa-rocket';
    if (code === 'job_seeking') return 'fa-hourglass-half';
    return 'fa-user';
}

function filterDirectory(page = 1) {
    searching.value = true;
    router.get(
        '/alumni',
        {
            search: query.value || undefined,
            graduation_year: year.value || undefined,
            current_status: status.value || undefined,
            major_or_class: major.value || undefined,
            per_page: pageSize.value,
            page,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['directory', 'filters'],
            onFinish: () => {
                searching.value = false;
            },
        },
    );
}

function submitHeroSearch() {
    query.value = heroQuery.value;
    activeSection.value = 'directory';
    filterDirectory(1);
    document.getElementById('direktori-alumni')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function applyQuickStatus(statusValue: string) {
    status.value = statusValue;
    activeSection.value = 'directory';
    filterDirectory(1);
    document.getElementById('direktori-alumni')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function openYearGrid(selectedYear: number) {
    year.value = String(selectedYear);
    view.value = 'grid';
    filterDirectory(1);
}

function resetDirectory() {
    query.value = '';
    heroQuery.value = '';
    year.value = '';
    status.value = '';
    major.value = '';
    filterDirectory(1);
}

function selectTab(section: string) {
    activeSection.value = section;
}

function handleShortcutClick(event: MouseEvent, item: { tab?: string; modal?: string }) {
    if (item.modal === 'stats') {
        event.preventDefault();
        showStatsModal.value = true;
        return;
    }
    if (item.tab) {
        event.preventDefault();
        selectTab(item.tab);
        const target = item.tab === 'directory' ? 'direktori-alumni' : 'ika-portal-tabs';
        document.getElementById(target)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

async function checkEkartu() {
    const q = ekartuQuery.value.trim();
    ekartuSearched.value = true;
    if (q.length < 2) {
        ekartuMatched.value = null;
        return;
    }
    ekartuLoading.value = true;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch('/alumni/cek-ekta', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
            body: JSON.stringify({ query: q }),
        });
        if (res.ok) {
            const data = await res.json();
            ekartuMatched.value = data.matched || null;
        } else {
            ekartuMatched.value = null;
        }
    } finally {
        ekartuLoading.value = false;
    }
}

const initials = (name: string) =>
    name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
</script>

<template>
    <Head :title="`PORTAL ALUMNI (IKA) - ${school.name}`" />
    <div class="ika-home-page" :style="themeStyle">
        <PublicHeader :school="school" />

        <!-- ================= 4. LUXURY HERO SECTION (12-KOLOM) ================= -->
        <section v-if="contentBlocks?.['alumni.hero']?.visible !== false" class="ika-home-hero" aria-labelledby="ika-home-title">
            <div class="ika-hero-glow ika-hero-glow--top" aria-hidden="true"></div>
            <div class="ika-hero-glow ika-hero-glow--bottom" aria-hidden="true"></div>

            <div class="ika-home-container ika-hero-grid">
                <!-- Left 7 Columns -->
                <div class="ika-hero-left">
                    <p class="ika-home-badge">
                        <span class="animate-pulse"></span>
                        <span>{{ heroBlock.badge || 'OFFICIAL ALUMNI NETWORK & CAREER HUB' }}</span>
                    </p>

                    <h1 id="ika-home-title">
                        {{ heroBlock.title || 'Temu Silaturahmi,' }} <span>{{ heroBlock.highlight || 'Keluarga Besar Alumni' }}</span> {{ publicText("copy.vue_alumni_home", "text_001", "& Karir") }}
                    </h1>

                    <p class="ika-home-description">
                        <template v-if="heroBlock.description">{{ heroBlock.description }}</template>
                        <template v-else>{{ publicText("copy.vue_alumni_home", "text_002", "Wadah resmi penelusuran lulusan (") }}<em>{{ publicText("copy.vue_alumni_home", "text_003", "tracer study") }}</em>{{ publicText("copy.vue_alumni_home", "text_9001", "), jejaring karir profesional, peluang beasiswa perguruan tinggi, bursa kerja aktif, dan kontribusi nyata bagi almamater tercinta") }} {{ school.name }}.</template>
                    </p>

                    <form action="/alumni#direktori-alumni" method="get" class="ika-home-search" @submit.prevent="submitHeroSearch">
                        <label for="ika-alumni-search" class="sr-only">{{ publicText("copy.vue_alumni_home", "text_004", "Cari alumni") }}</label>
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input
                            id="ika-alumni-search"
                            v-model="heroQuery"
                            name="search"
                            type="search"
                            maxlength="150"
                            :placeholder="publicText('copy.vue_alumni_home', 'placeholder_101', 'Cari nama alumni, kampus (PTN/PTS), instansi, atau profesi...')"
                        />
                        <button type="submit">{{ publicText("copy.vue_alumni_home", "text_005", "Cari Alumni") }}</button>
                    </form>

                    <div class="ika-home-filters">
                        <strong><i class="fa-solid fa-bolt" aria-hidden="true"></i> {{ publicText("copy.vue_alumni_home", "text_006", "Filter Cepat:") }}</strong>
                        <a :href="publicText('copy.vue_alumni_home', 'href_102', '/alumni?current_status=studying#direktori-alumni')" @click.prevent="applyQuickStatus('studying')">{{ publicText("copy.vue_alumni_home", "text_007", "🎓 Kuliah") }}</a>
                        <a :href="publicText('copy.vue_alumni_home', 'href_103', '/alumni?current_status=working#direktori-alumni')" @click.prevent="applyQuickStatus('working')">{{ publicText("copy.vue_alumni_home", "text_008", "💼 Bekerja") }}</a>
                        <a :href="publicText('copy.vue_alumni_home', 'href_104', '/alumni?current_status=civil_servant#direktori-alumni')" @click.prevent="applyQuickStatus('civil_servant')">{{ publicText("copy.vue_alumni_home", "text_009", "🏛️ ASN/TNI/Polri") }}</a>
                        <a :href="publicText('copy.vue_alumni_home', 'href_105', '/alumni?current_status=entrepreneur#direktori-alumni')" @click.prevent="applyQuickStatus('entrepreneur')">{{ publicText("copy.vue_alumni_home", "text_010", "🚀 Bisnis Mandiri") }}</a>
                    </div>

                    <div class="ika-home-actions">
                        <a :href="publicText('copy.vue_alumni_home', 'href_106', '#direktori-alumni')" @click="selectTab('directory')">
                            <i class="fa-solid fa-users text-blue-700" aria-hidden="true"></i>
                            <span>{{ publicText("copy.vue_alumni_home", "text_011", "Jelajahi Direktori Alumni") }}</span>
                        </a>
                        <a :href="publicText('copy.vue_alumni_home', 'href_107', '/layanan/tracer-alumni#tracer-form')">
                            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                            <span>{{ publicText("copy.vue_alumni_home", "text_012", "Isi Tracer Study") }}</span>
                        </a>
                        <a :href="publicText('copy.vue_alumni_home', 'href_108', '/alumni?section=careers#ika-portal-tabs')" @click.prevent="selectTab('careers')">
                            <i class="fa-solid fa-briefcase text-amber-400" aria-hidden="true"></i>
                            <span>{{ publicText("copy.vue_alumni_home", "text_9002", "Bursa Karir (") }}{{ opportunities.careers.length }})</span>
                        </a>
                    </div>

                    <div id="statistik-alumni" class="ika-home-stats">
                        <div role="button" tabindex="0" @click="selectTab('directory'); resetDirectory()">
                            <strong>{{ summary.total }}</strong>
                            <span>{{ publicText("copy.vue_alumni_home", "text_013", "Total Alumni Terdata") }}</span>
                        </div>
                        <div role="button" tabindex="0" @click="applyQuickStatus('studying')">
                            <strong>{{ summary.studying }}</strong>
                            <span>{{ publicText("copy.vue_alumni_home", "text_014", "Kuliah Kampus") }}</span>
                        </div>
                        <div role="button" tabindex="0" @click="applyQuickStatus('working')">
                            <strong>{{ summary.working }}</strong>
                            <span>{{ publicText("copy.vue_alumni_home", "text_015", "Karir & Instansi") }}</span>
                        </div>
                        <div role="button" tabindex="0" @click="applyQuickStatus('entrepreneur')">
                            <strong>{{ summary.entrepreneur }}</strong>
                            <span>{{ publicText("copy.vue_alumni_home", "text_016", "Wirausaha / Bisnis") }}</span>
                        </div>
                    </div>
                </div>

                <!-- Right 5 Columns: Frame Gambar & Floating Badges -->
                <div class="ika-hero-right">
                    <figure class="ika-home-visual group">
                        <img :src="publicText('copy.vue_alumni_home', 'src_109', '/madrasah-theme/images/library.webp')" :alt="publicText('copy.vue_alumni_home', 'alt_110', 'Komunitas Alumni Berprestasi')" />
                        <div class="ika-visual-overlay" aria-hidden="true"></div>

                        <!-- Floating Badge 1 (Top Left) -->
                        <div class="ika-floating-badge ika-floating-badge--top">
                            <div class="ika-badge-icon ika-badge-icon--gold">
                                <i class="fa-solid fa-medal" aria-hidden="true"></i>
                            </div>
                            <div>
                                <span class="ika-badge-eyebrow text-amber-400">{{ publicText("copy.vue_alumni_home", "text_017", "JEJARING KUAT") }}</span>
                                <strong class="ika-badge-title">{{ publicText("copy.vue_alumni_home", "text_018", "Lintas Angkatan Nusantara") }}</strong>
                            </div>
                        </div>

                        <!-- Floating Badge 2 (Bottom Right) -->
                        <div class="ika-floating-badge ika-floating-badge--right">
                            <div class="ika-badge-icon ika-badge-icon--emerald">
                                <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
                            </div>
                            <div>
                                <span class="ika-badge-eyebrow text-emerald-300">{{ publicText("copy.vue_alumni_home", "text_019", "TRACER STUDY TERINTEGRASI") }}</span>
                                <strong class="ika-badge-title">{{ publicText("copy.vue_alumni_home", "text_020", "Akreditasi & Validasi Resmi") }}</strong>
                            </div>
                        </div>

                        <figcaption class="ika-visual-caption">
                            <span class="ika-caption-pill">{{ publicText("copy.vue_alumni_home", "text_021", "SILATURAHMI & SINERGI") }}</span>
                            <h3>{{ publicText("copy.vue_alumni_home", "text_022", "Membangun Kolaborasi Lulusan Berkelanjutan") }}</h3>
                            <p>{{ publicText("copy.vue_alumni_home", "text_023", "Menghubungkan alumni sukses dengan almamater madrasah tercinta.") }}</p>
                        </figcaption>
                    </figure>
                </div>
            </div>
        </section>

        <!-- ================= 5. RUNNING QUICK CARDS MARQUEE ================= -->
        <section class="ika-quick-strip" :aria-label="publicText('copy.vue_alumni_home', 'aria_label_111', 'Pintasan layanan alumni')">
            <div class="ika-quick-inner">
                <div class="ika-quick-fade ika-quick-fade--left" aria-hidden="true"></div>
                <div class="ika-quick-fade ika-quick-fade--right" aria-hidden="true"></div>

                <div class="ika-quick-viewport">
                    <div class="ika-quick-track">
                        <nav class="ika-shortcuts">
                            <a
                                v-for="service in shortcuts"
                                :key="service.title"
                                :href="service.href"
                                @click="handleShortcutClick($event, service)"
                            >
                                <i :class="`fa-solid fa-${service.icon} ${service.tone}`" aria-hidden="true"></i>
                                <div>
                                    <strong>{{ service.title }}</strong>
                                    <span>{{ service.subtitle }}</span>
                                </div>
                            </a>
                        </nav>
                        <nav class="ika-shortcuts" aria-hidden="true">
                            <a
                                v-for="service in shortcuts"
                                :key="`dup-${service.title}`"
                                :href="service.href"
                                tabindex="-1"
                                @click="handleShortcutClick($event, service)"
                            >
                                <i :class="`fa-solid fa-${service.icon} ${service.tone}`" aria-hidden="true"></i>
                                <div>
                                    <strong>{{ service.title }}</strong>
                                    <span>{{ service.subtitle }}</span>
                                </div>
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================= 6. MAIN CONTENT AREA ================= -->
        <main class="ika-content">
            <!-- TAB SWITCHER NAVIGASI HORIZONTAL + UPDATE STATUS CTA -->
            <div id="ika-portal-tabs" class="ika-tabs-bar">
                <div class="ika-tabs" role="tablist" :aria-label="publicText('copy.vue_alumni_home', 'aria_label_112', 'Portal alumni')">
                    <button
                        v-for="tab in portalTabs"
                        :id="`tab-${tab.id}`"
                        :key="tab.id"
                        type="button"
                        role="tab"
                        :aria-selected="activeSection === tab.id"
                        aria-controls="ika-tab-panel"
                        @click="selectTab(tab.id)"
                    >
                        <i :class="`fa-solid fa-${tab.icon} ${activeSection === tab.id ? '' : tab.tone}`" aria-hidden="true"></i>
                        <span>{{ tab.title }}</span>
                    </button>
                </div>

                <a :href="publicText('copy.vue_alumni_home', 'href_107', '/layanan/tracer-alumni#tracer-form')" class="ika-update-cta">
                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                    <span>{{ publicText("copy.vue_alumni_home", "text_024", "Update Status Anda") }}</span>
                </a>
            </div>

            <div id="ika-tab-panel" role="tabpanel" :aria-labelledby="`tab-${activeSection}`" class="ika-tab-content">
                <!-- TAB 1: DIREKTORI ALUMNI -->
                <template v-if="activeSection === 'directory'">
                    <!-- HALL OF FAME -->
                    <section class="ika-spotlight ika-panel">
                        <div class="ika-section-heading">
                            <div>
                                <span class="ika-eyebrow"><i class="fa-solid fa-star text-amber-500 mr-1" aria-hidden="true"></i> {{ publicText("copy.vue_alumni_home", "text_025", "HALL OF FAME") }}</span>
                                <h2>{{ publicText("copy.vue_alumni_home", "text_026", "Sorotan Tokoh & Kisah Alumni Pilihan") }}</h2>
                                <p>{{ publicText("copy.vue_alumni_home", "text_027", "Inspirasi karir, dedikasi profesional, dan jejak prestasi alumni kebanggaan madrasah.") }}</p>
                            </div>
                            <a class="ika-outline-link" :href="publicText('copy.vue_alumni_home', 'href_106', '#direktori-alumni')" @click="resetDirectory">
                                {{ publicText("copy.vue_alumni_home", "text_028", "Lihat Semua Alumni") }} <i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i>
                            </a>
                        </div>

                        <div v-if="spotlight.length" class="ika-spotlight-grid">
                            <article
                                v-for="alumnus in spotlight"
                                :key="alumnus.id"
                                class="ika-person"
                                role="button"
                                tabindex="0"
                                @click="selectedAlumnus = alumnus"
                            >
                                <span class="ika-avatar" aria-hidden="true">{{ initials(alumnus.full_name) }}</span>
                                <div class="min-w-0">
                                    <h3 class="truncate">{{ alumnus.full_name }}</h3>
                                    <p>
                                        {{ publicText("copy.vue_alumni_home", "text_9003", "Angkatan") }} {{ alumnus.graduation_year }}
                                        <template v-if="alumnus.major_or_class"> • {{ alumnus.major_or_class }}</template>
                                    </p>
                                    <span class="ika-status">
                                        <i class="fa-solid fa-circle-check mr-1" aria-hidden="true"></i>
                                        {{ alumnus.position_or_program || alumnus.status_label }}
                                    </span>
                                </div>
                            </article>
                        </div>
                        <p v-else class="ika-empty">{{ publicText("copy.vue_alumni_home", "text_029", "Sorotan alumni akan tampil setelah profil alumni disetujui untuk publikasi.") }}</p>
                    </section>

                    <!-- DIREKTORI TEMU ALUMNI -->
                    <section id="direktori-alumni" class="ika-panel" :aria-busy="searching">
                        <div class="ika-section-heading">
                            <div>
                                <h2>
                                    <i class="fa-solid fa-users" aria-hidden="true"></i>
                                    {{ publicText("copy.vue_alumni_home", "text_030", "Direktori Temu Alumni & Jejaring Almamater") }}
                                </h2>
                                <p>{{ publicText("copy.vue_alumni_home", "text_031", "Cari teman seangkatan, jaringan profesional lintas jurusan, dan kontak domisili seluruh Indonesia.") }}</p>
                            </div>
                            <button type="button" class="ika-reset" @click="resetDirectory">
                                <i class="fa-solid fa-arrow-rotate-right mr-1" aria-hidden="true"></i> {{ publicText("copy.vue_alumni_home", "text_032", "Reset Filter") }}
                            </button>
                        </div>

                        <div class="ika-toolbar-row">
                            <div class="ika-year-chips">
                                <strong><i class="fa-solid fa-graduation-cap text-blue-700 mr-1" aria-hidden="true"></i> {{ publicText("copy.vue_alumni_home", "text_033", "Pilih Angkatan:") }}</strong>
                                <button type="button" :class="{ selected: !year }" @click="year = ''; filterDirectory()">
                                    {{ publicText("copy.vue_alumni_home", "text_9004", "Semua Angkatan (") }}{{ summary.total }})
                                </button>
                                <button
                                    v-for="item in directory.years"
                                    :key="item.graduation_year"
                                    type="button"
                                    :class="{ selected: year === String(item.graduation_year) }"
                                    @click="year = String(item.graduation_year); filterDirectory()"
                                >
                                    {{ publicText("copy.vue_alumni_home", "text_9003", "Angkatan") }} {{ item.graduation_year }} <span>({{ item.total }})</span>
                                </button>
                            </div>

                            <div class="ika-directory-toolbar">
                                <div class="ika-view-buttons" :aria-label="publicText('copy.vue_alumni_home', 'aria_label_113', 'Mode tampilan')">
                                    <button
                                        v-for="mode in ([
                                            { id: 'gallery', title: 'Foto Berjalan', icon: 'fa-film' },
                                            { id: 'grid', title: 'Grid Kartu', icon: 'fa-table-cells-large' },
                                            { id: 'table', title: 'Tabel Rapi', icon: 'fa-table-list' },
                                        ] as const)"
                                        :key="mode.id"
                                        type="button"
                                        :aria-pressed="view === mode.id"
                                        @click="view = mode.id"
                                    >
                                        <i :class="`fa-solid ${mode.icon} mr-1`" aria-hidden="true"></i>
                                        {{ mode.title }}
                                    </button>
                                </div>
                                <label>
                                    {{ publicText("copy.vue_alumni_home", "text_034", "Tampilkan:") }}
                                    <select v-model="pageSize" @change="filterDirectory()">
                                        <option :value="24">{{ publicText("copy.vue_alumni_home", "text_035", "24 per halaman") }}</option>
                                        <option :value="48">{{ publicText("copy.vue_alumni_home", "text_036", "48 per halaman") }}</option>
                                        <option :value="100">{{ publicText("copy.vue_alumni_home", "text_037", "100 per halaman") }}</option>
                                        <option value="all">{{ publicText("copy.vue_alumni_home", "text_038", "Tampilkan Semua Sekaligus") }}</option>
                                    </select>
                                </label>
                            </div>
                        </div>

                        <form class="ika-directory-filters" @submit.prevent="filterDirectory()">
                            <label class="ika-filter-col-3">
                                <span><i class="fa-solid fa-calendar-check text-blue-700 mr-1" aria-hidden="true"></i> {{ publicText("copy.vue_alumni_home", "text_039", "Tahun Kelulusan:") }}</span>
                                <select v-model="year" @change="filterDirectory()">
                                    <option value="">{{ publicText("copy.vue_alumni_home", "text_040", "🎓 Semua Angkatan") }}</option>
                                    <option v-for="item in directory.years" :key="item.graduation_year" :value="String(item.graduation_year)">
                                        {{ publicText("copy.vue_alumni_home", "text_9005", "Lulusan") }} {{ item.graduation_year }}
                                    </option>
                                </select>
                            </label>
                            <label class="ika-filter-col-3">
                                <span><i class="fa-solid fa-briefcase text-emerald-600 mr-1" aria-hidden="true"></i> {{ publicText("copy.vue_alumni_home", "text_041", "Status Aktivitas:") }}</span>
                                <select v-model="status" @change="filterDirectory()">
                                    <option value="">{{ publicText("copy.vue_alumni_home", "text_042", "🌟 Semua Status") }}</option>
                                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </label>
                            <label class="ika-filter-col-2">
                                <span><i class="fa-solid fa-graduation-cap text-amber-500 mr-1" aria-hidden="true"></i> {{ publicText("copy.vue_alumni_home", "text_043", "Jurusan:") }}</span>
                                <select v-model="major" @change="filterDirectory()">
                                    <option value="">{{ publicText("copy.vue_alumni_home", "text_044", "📚 Semua Jurusan") }}</option>
                                    <option v-for="item in directory.majors" :key="item" :value="item">{{ item }}</option>
                                </select>
                            </label>
                            <label class="ika-filter-col-4">
                                <span><i class="fa-solid fa-magnifying-glass text-blue-600 mr-1" aria-hidden="true"></i> {{ publicText("copy.vue_alumni_home", "text_045", "Cari Alumni / Kampus / Kantor:") }}</span>
                                <div class="ika-filter-search">
                                    <input v-model="query" type="search" :placeholder="publicText('copy.vue_alumni_home', 'placeholder_114', 'Nama, kampus, tempat kerja...')" maxlength="150" />
                                    <button type="submit" :disabled="searching">{{ publicText("copy.vue_alumni_home", "text_046", "Cari") }}</button>
                                </div>
                            </label>
                        </form>

                        <!-- Summary Bar -->
                        <div class="ika-summary-bar">
                            <p class="ika-result-count">
                                <i class="fa-solid fa-circle-check text-emerald-600 mr-1" aria-hidden="true"></i>
                                {{ publicText("copy.vue_alumni_home", "text_047", "Menampilkan") }} <strong>{{ directory.total }} {{ publicText("copy.vue_alumni_home", "text_9006", "alumni ditemukan") }}</strong>
                                <template v-if="groupedByYear.length"> ({{ groupedByYear.length }} {{ publicText("copy.vue_alumni_home", "text_9007", "Angkatan)") }}</template>
                            </p>
                            <p class="ika-summary-hint">
                                <i class="fa-solid fa-hand-pointer text-blue-600 mr-1" aria-hidden="true"></i>
                                {{ publicText("copy.vue_alumni_home", "text_048", "Sentuh/Arahkan kursor untuk jeda, klik kartu untuk melihat profil lengkap") }}
                            </p>
                        </div>

                        <p v-if="!directory.data.length" class="ika-empty">
                            {{ publicText("copy.vue_alumni_home", "text_049", "Belum ada alumni yang sesuai dengan pencarian. Profil tampil setelah disetujui dan alumni mengizinkan publikasi.") }}
                        </p>

                        <!-- Mode 3: Tabel Rapi -->
                        <div v-else-if="view === 'table'" class="ika-table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th>{{ publicText("copy.vue_alumni_home", "text_050", "Profil") }}</th>
                                        <th>{{ publicText("copy.vue_alumni_home", "text_051", "Nama Alumni") }}</th>
                                        <th>{{ publicText("copy.vue_alumni_home", "text_052", "Angkatan / Jurusan") }}</th>
                                        <th>{{ publicText("copy.vue_alumni_home", "text_053", "Status") }}</th>
                                        <th>{{ publicText("copy.vue_alumni_home", "text_054", "Kampus / Instansi") }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="alumnus in directory.data" :key="alumnus.id" class="cursor-pointer hover:bg-slate-50" @click="selectedAlumnus = alumnus">
                                        <td>
                                            <span class="alumni-table-thumb">{{ initials(alumnus.full_name) }}</span>
                                        </td>
                                        <td class="font-bold text-slate-900">{{ alumnus.full_name }}</td>
                                        <td>{{ alumnus.graduation_year }} / {{ alumnus.major_or_class || 'Madrasah Aliyah' }}</td>
                                        <td>
                                            <span :class="`alumni-status-pill ${statusBadgeClass(alumnus)}`">
                                                <i :class="`fa-solid ${statusIconClass(alumnus)}`" aria-hidden="true"></i>
                                                <span>{{ alumnus.status_label }}</span>
                                            </span>
                                        </td>
                                        <td>{{ alumnus.institution_or_company || '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mode 2: Grid Kartu 6-Kolom -->
                        <div v-else-if="view === 'grid'" class="alumni-grid-wrapper" :aria-label="publicText('copy.vue_alumni_home', 'aria_label_115', 'Daftar alumni')">
                            <article
                                v-for="alumnus in directory.data"
                                :key="alumnus.id"
                                class="alumni-grid-card"
                                @click="selectedAlumnus = alumnus"
                            >
                                <div>
                                    <div class="alumni-grid-photo-box">
                                        <img
                                            :src="publicText('copy.vue_alumni_home', 'src_116', '/madrasah-theme/images/kiosk/avatar-1.png')"
                                            :alt="`Foto ${alumnus.full_name}`"
                                            class="alumni-photo-img"
                                            loading="lazy"
                                        />
                                        <span class="alumni-card-year-badge">{{ alumnus.graduation_year }}</span>
                                    </div>
                                    <h3 class="alumni-card-name" :title="alumnus.full_name">{{ alumnus.full_name }}</h3>
                                    <p class="alumni-card-jurusan">{{ alumnus.major_or_class || 'Madrasah Aliyah' }}</p>
                                    <div v-if="alumnus.institution_or_company" class="alumni-activity-box">
                                        <span class="alumni-activity-title">{{ alumnus.institution_or_company }}</span>
                                        <span v-if="alumnus.position_or_program" class="alumni-activity-sub">{{ alumnus.position_or_program }}</span>
                                    </div>
                                </div>
                                <div>
                                    <span :class="`alumni-status-pill ${statusBadgeClass(alumnus)}`">
                                        <i :class="`fa-solid ${statusIconClass(alumnus)}`" aria-hidden="true"></i>
                                        <span>{{ alumnus.status_label }}</span>
                                    </span>
                                    <button type="button" class="btn-profile-card">{{ publicText("copy.vue_alumni_home", "text_055", "Lihat Profil") }}</button>
                                </div>
                            </article>
                        </div>

                        <!-- Mode 1: Foto Berjalan Per Angkatan (Default seperti Referensi) -->
                        <div v-else class="space-y-5" :aria-label="publicText('copy.vue_alumni_home', 'aria_label_117', 'Daftar alumni per angkatan')">
                            <div v-for="group in groupedByYear" :key="group.year" class="alumni-angkatan-box">
                                <div class="alumni-angkatan-header">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="badge-angkatan-pill">
                                            <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
                                            {{ publicText("copy.vue_alumni_home", "text_9003", "Angkatan") }} {{ group.year }}
                                        </span>
                                        <span class="badge-count-pill">{{ group.total }} {{ publicText("copy.vue_alumni_home", "text_9008", "Alumni Terdata") }}</span>
                                    </div>
                                    <button type="button" class="btn-link-grid" @click="openYearGrid(group.year)">
                                        <span>{{ publicText("copy.vue_alumni_home", "text_9009", "Lihat Semua Angkatan") }} {{ group.year }} {{ publicText("copy.vue_alumni_home", "text_9010", "(Grid)") }}</span>
                                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </button>
                                </div>

                                <div class="alumni-marquee-container">
                                    <div class="alumni-marquee-track" :class="{ 'is-static': group.items.length < 5 }">
                                        <article
                                            v-for="(alumnus, idx) in (group.items.length >= 4 ? [...group.items, ...group.items] : group.items)"
                                            :key="`${group.year}-${alumnus.id}-${idx}`"
                                            class="alumni-marquee-card"
                                            @click="selectedAlumnus = alumnus"
                                        >
                                            <div>
                                                <div class="alumni-card-photo-box">
                                                    <img
                                                        :src="publicText('copy.vue_alumni_home', 'src_116', '/madrasah-theme/images/kiosk/avatar-1.png')"
                                                        :alt="`Foto ${alumnus.full_name}`"
                                                        class="alumni-photo-img"
                                                        loading="lazy"
                                                    />
                                                    <span class="alumni-card-year-badge">{{ alumnus.graduation_year }}</span>
                                                </div>
                                                <h3 class="alumni-card-name" :title="alumnus.full_name">{{ alumnus.full_name }}</h3>
                                                <p class="alumni-card-jurusan">{{ alumnus.major_or_class || 'Madrasah Aliyah' }}</p>
                                            </div>
                                            <span :class="`alumni-status-pill ${statusBadgeClass(alumnus)}`">
                                                <i :class="`fa-solid ${statusIconClass(alumnus)}`" aria-hidden="true"></i>
                                                <span>{{ alumnus.status_label }}</span>
                                            </span>
                                        </article>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <nav v-if="directory.last_page > 1" class="ika-pagination" :aria-label="publicText('copy.vue_alumni_home', 'aria_label_118', 'Halaman direktori')">
                            <button type="button" :disabled="searching || directory.current_page <= 1" @click="filterDirectory(directory.current_page - 1)">
                                {{ publicText("copy.vue_alumni_home", "text_056", "← Sebelumnya") }}
                            </button>
                            <span>{{ publicText("copy.vue_alumni_home", "text_9011", "Halaman") }} {{ directory.current_page }} {{ publicText("copy.vue_alumni_home", "text_9012", "dari") }} {{ directory.last_page }}</span>
                            <button
                                type="button"
                                :disabled="searching || directory.current_page >= directory.last_page"
                                @click="filterDirectory(directory.current_page + 1)"
                            >
                                {{ publicText("copy.vue_alumni_home", "text_057", "Berikutnya →") }}
                            </button>
                        </nav>
                    </section>
                </template>

                <!-- TAB 2: TRACER STUDY MANDIRI -->
                <section v-else-if="activeSection === 'tracer'" class="ika-tracer-box">
                    <div class="ika-tracer-banner">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <span class="ika-tracer-badge">
                                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> {{ publicText("copy.vue_alumni_home", "text_058", "FORMULIR RESMI MANDIRI") }}
                                </span>
                                <h2>{{ publicText("copy.vue_alumni_home", "text_059", "Pendaftaran & Pembaruan Status Alumni") }}</h2>
                                <p>{{ publicText("copy.vue_alumni_home", "text_060", "Alumni angkatan lama maupun baru dapat memperbarui aktivitas perkuliahan atau karir secara mandiri.") }}</p>
                            </div>
                            <span class="ika-tracer-db-pill">
                                <i class="fa-solid fa-circle-check text-emerald-400" aria-hidden="true"></i>
                                {{ publicText("copy.vue_alumni_home", "text_061", "Terintegrasi Database Madrasah") }}
                            </span>
                        </div>

                        <div class="ika-tracer-steps">
                            <div><span>1</span><strong>{{ publicText("copy.vue_alumni_home", "text_062", "Cari / Isi Data Diri") }}</strong></div>
                            <div><span>2</span><strong>{{ publicText("copy.vue_alumni_home", "text_063", "Pilih Status Karir/Kuliah") }}</strong></div>
                            <div><span>3</span><strong>{{ publicText("copy.vue_alumni_home", "text_064", "Foto Profil & Simpan") }}</strong></div>
                        </div>
                    </div>

                    <div class="ika-tracer-body">
                        <div class="ika-tracer-step1-box">
                            <div class="flex items-center gap-2.5 text-amber-950 font-black text-sm sm:text-base mb-1.5">
                                <span class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs shrink-0">
                                    <i class="fa-solid fa-bolt" aria-hidden="true"></i>
                                </span>
                                <span>{{ publicText("copy.vue_alumni_home", "text_065", "Langkah 1: Pembaruan Profil & Tracer Study Terverifikasi") }}</span>
                            </div>
                            <p class="text-xs sm:text-sm text-amber-900/80 mb-4">
                                {{ publicText("copy.vue_alumni_home", "text_066", "Demi keamanan data pribadi dan kontrol persetujuan publikasi direktori (") }}<em>{{ publicText("copy.vue_alumni_home", "text_067", "opt-in") }}</em>{{ publicText("copy.vue_alumni_home", "text_068", "), pengisian serta pembaruan data tracer study dilakukan melalui Portal Tracer Alumni terautentikasi.") }}
                            </p>
                            <div class="flex flex-wrap items-center gap-3">
                                <a class="ika-primary-link" :href="publicText('copy.vue_alumni_home', 'href_107', '/layanan/tracer-alumni#tracer-form')">
                                    <i class="fa-solid fa-pen-to-square mr-1.5" aria-hidden="true"></i>
                                    {{ publicText("copy.vue_alumni_home", "text_069", "Buka Formulir Tracer Study →") }}
                                </a>
                                <a class="ika-outline-link" :href="publicText('copy.vue_alumni_home', 'href_119', '/layanan/ptsp/akun?return_to=/layanan/tracer-alumni')">
                                    <i class="fa-solid fa-user-lock mr-1" aria-hidden="true"></i>
                                    {{ publicText("copy.vue_alumni_home", "text_070", "Masuk / Daftar Akun Alumni") }}
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- TAB 3 & 4: BURSA KARIR & PUSAT BEASISWA -->
                <section v-else-if="activeSection === 'careers' || activeSection === 'scholarships'" class="ika-panel">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <h2 class="flex items-center gap-2.5">
                                <span
                                    class="w-9 h-9 rounded-xl flex items-center justify-center text-base shrink-0"
                                    :class="activeSection === 'careers' ? 'bg-emerald-100 text-emerald-700' : 'bg-purple-100 text-purple-700'"
                                >
                                    <i :class="`fa-solid ${activeSection === 'careers' ? 'fa-briefcase' : 'fa-graduation-cap'}`" aria-hidden="true"></i>
                                </span>
                                <span>{{ activeSection === 'careers' ? 'Bursa Karir & Peluang Kerja Alumni' : 'Pusat Informasi Beasiswa Alumni' }}</span>
                            </h2>
                            <p class="mt-1">
                                {{
                                    activeSection === 'careers'
                                        ? 'Lowongan kerja resmi dari instansi pemerintah, BUMN, dan mitra korporat almamater.'
                                        : 'Program beasiswa S1, S2, santri berprestasi Kemenag, dan beasiswa kemitraan perguruan tinggi.'
                                }}
                            </p>
                        </div>
                        <div class="relative w-full sm:w-72">
                            <input
                                v-if="activeSection === 'careers'"
                                v-model="careerSearch"
                                type="search"
                                :placeholder="publicText('copy.vue_alumni_home', 'placeholder_120', 'Cari posisi, instansi, kota...')"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs sm:text-sm"
                            />
                            <input
                                v-else
                                v-model="scholarshipSearch"
                                type="search"
                                :placeholder="publicText('copy.vue_alumni_home', 'placeholder_121', 'Cari nama beasiswa, kampus...')"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs sm:text-sm"
                            />
                        </div>
                    </div>

                    <div v-if="(activeSection === 'careers' ? filteredCareers : filteredScholarships).length" class="ika-opportunities">
                        <article v-for="item in activeSection === 'careers' ? filteredCareers : filteredScholarships" :key="item.id">
                            <h3>{{ item.title }}</h3>
                            <p>{{ item.excerpt }}</p>
                            <a :href="item.url">{{ publicText("copy.vue_alumni_home", "text_071", "Lihat Informasi →") }}</a>
                        </article>
                    </div>
                    <p v-else class="ika-empty">{{ publicText("copy.vue_alumni_home", "text_072", "Belum ada informasi yang dipublikasikan.") }}</p>
                </section>

                <!-- TAB 5: CETAK E-KTA DIGITAL -->
                <section v-else class="ika-panel ika-ekartu-box">
                    <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center text-2xl mx-auto mb-4">
                        <i class="fa-solid fa-id-card" aria-hidden="true"></i>
                    </div>
                    <h2>{{ publicText("copy.vue_alumni_home", "text_073", "Cetak Kartu Tanda Anggota (E-KTA) Digital") }}</h2>
                    <p class="max-w-xl mx-auto mt-1 mb-6">
                        {{ publicText("copy.vue_alumni_home", "text_074", "Kartu identitas resmi alumni ber-QR Code untuk verifikasi keanggotaan dan akses layanan almamater.") }}
                    </p>

                    <form class="flex items-center gap-2 max-w-md mx-auto mb-6" @submit.prevent="checkEkartu">
                        <input
                            v-model="ekartuQuery"
                            type="search"
                            :placeholder="publicText('copy.vue_alumni_home', 'placeholder_122', 'Masukkan Nama Alumni...')"
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm"
                        />
                        <button type="submit" class="ika-primary-link shrink-0 flex items-center gap-1.5">
                            <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                            <span>{{ publicText("copy.vue_alumni_home", "text_075", "Cek KTA") }}</span>
                        </button>
                    </form>

                    <div v-if="ekartuSearched && ekartuMatched" class="max-w-md mx-auto mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-left text-xs space-y-1">
                        <p class="font-extrabold text-emerald-950 text-sm">{{ ekartuMatched.full_name }}</p>
                        <p class="text-emerald-800">{{ publicText("copy.vue_alumni_home", "text_9003", "Angkatan") }} {{ ekartuMatched.graduation_year }} • {{ ekartuMatched.major_or_class || 'Alumni Terverifikasi' }}</p>
                        <p class="text-emerald-700">{{ publicText("copy.vue_alumni_home", "text_076", "Profil ditemukan di direktori. Silakan masuk ke akun alumni Anda untuk mengunduh PDF E-KTA resmi ber-QR.") }}</p>
                    </div>
                    <div v-else-if="ekartuSearched" class="max-w-md mx-auto mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900">
                        {{ publicText("copy.vue_alumni_home", "text_077", "Untuk mengunduh file PDF E-KTA resmi ber-QR Code, silakan masuk ke akun alumni dan pastikan profil telah disetujui.") }}
                    </div>

                    <a class="ika-primary-link" :href="publicText('copy.vue_alumni_home', 'href_123', '/layanan/tracer-alumni#alumni-kta')">
                        {{ publicText("copy.vue_alumni_home", "text_078", "Buka Profil & Unduh E-KTA Digital →") }}
                    </a>
                </section>
            </div>

            <!-- ================= PILAR PROGRAM & LAYANAN UNGGULAN ================= -->
            <section id="program-ika" class="ika-program">
                <div class="ika-program-header">
                    <span class="ika-program-badge">{{ publicText("copy.vue_alumni_home", "text_079", "PROGRAM & LAYANAN UNGGULAN") }}</span>
                    <h2>{{ publicText("copy.vue_alumni_home", "text_080", "Membangun Jejaring, Memberdayakan Alumni") }}</h2>
                    <p>{{ publicText("copy.vue_alumni_home", "text_081", "Ekosistem terintegrasi yang dirancang untuk mendukung perjalanan karir, silaturahmi, dan kontribusi nyata bagi almamater madrasah tercinta.") }}</p>
                </div>

                <div class="ika-program-grid">
                    <article v-for="service in services" :key="service.title">
                        <div>
                            <div :class="`w-12 h-12 rounded-xl flex items-center justify-center text-xl mb-4 ${service.iconBox}`">
                                <i :class="`fa-solid fa-${service.icon}`" aria-hidden="true"></i>
                            </div>
                            <h3>{{ service.title }}</h3>
                            <p>{{ service.description }}</p>
                        </div>
                        <div class="flex items-center gap-3 mt-auto pt-2">
                            <a
                                :href="service.href"
                                :class="service.linkColor"
                                @click="service.tab ? handleShortcutClick($event, { tab: service.tab }) : undefined"
                            >
                                {{ service.action }} <i v-if="service.icon !== 'id-card'" class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i>
                            </a>
                            <template v-if="service.icon === 'id-card'">
                                <span class="text-slate-300">•</span>
                                <a :href="publicText('copy.vue_alumni_home', 'href_124', '/layanan/legalisir')" class="text-rose-600 hover:text-rose-700">{{ publicText("copy.vue_alumni_home", "text_082", "Legalisir ↗") }}</a>
                            </template>
                        </div>
                    </article>
                </div>
            </section>

            <!-- ================= FAQ ALUMNI ================= -->
            <section id="faq-alumni" class="alumni-panel ika-faq">
                <h2>{{ publicText("copy.vue_alumni_home", "text_083", "Pertanyaan Umum") }}</h2>
                <details>
                    <summary>{{ publicText("copy.vue_alumni_home", "text_084", "Bagaimana memperbarui data alumni?") }}</summary>
                    <p>{{ publicText("copy.vue_alumni_home", "text_085", "Masuk dengan akun alumni, lengkapi formulir tracer study, lalu simpan pembaruan. Pengelola akan memeriksa data sebelum ditampilkan di direktori.") }}</p>
                </details>
                <details>
                    <summary>{{ publicText("copy.vue_alumni_home", "text_086", "Apakah kontak saya ditampilkan ke publik?") }}</summary>
                    <p>{{ publicText("copy.vue_alumni_home", "text_087", "Kontak hanya ditampilkan sesuai pilihan persetujuan pada profil Anda. Anda dapat memperbarui persetujuan melalui formulir tracer study.") }}</p>
                </details>
                <details>
                    <summary>{{ publicText("copy.vue_alumni_home", "text_088", "Bagaimana mendapatkan KTA Digital?") }}</summary>
                    <p>{{ publicText("copy.vue_alumni_home", "text_089", "Setelah profil disetujui dan kartu diterbitkan, tombol unduh E-KTA tersedia pada profil alumni Anda.") }}</p>
                </details>
            </section>
        </main>

        <!-- Modal Detail Profil Alumni -->
        <div
            v-if="selectedAlumnus"
            role="dialog"
            aria-modal="true"
            :aria-label="publicText('copy.vue_alumni_home', 'aria_label_125', 'Detail profil alumni')"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
            @click.self="selectedAlumnus = null"
        >
            <div class="relative w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">
                <button
                    type="button"
                    :aria-label="publicText('copy.vue_alumni_home', 'aria_label_126', 'Tutup detail alumni')"
                    class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200"
                    @click="selectedAlumnus = null"
                >
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>

                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                    <div class="w-24 h-32 rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 shrink-0">
                        <img :src="publicText('copy.vue_alumni_home', 'src_116', '/madrasah-theme/images/kiosk/avatar-1.png')" :alt="selectedAlumnus.full_name" class="w-full h-full object-cover" />
                    </div>
                    <div class="space-y-2 text-center sm:text-left">
                        <span class="inline-block rounded-full bg-blue-50 px-3 py-0.5 text-xs font-extrabold text-blue-700">
                            {{ publicText("copy.vue_alumni_home", "text_9003", "Angkatan") }} {{ selectedAlumnus.graduation_year }}
                        </span>
                        <h3 class="text-lg font-extrabold text-slate-900">{{ selectedAlumnus.full_name }}</h3>
                        <p class="text-xs font-semibold text-slate-500">{{ selectedAlumnus.major_or_class || 'Madrasah Aliyah' }}</p>
                        <span :class="`alumni-status-pill ${statusBadgeClass(selectedAlumnus)}`">
                            <i :class="`fa-solid ${statusIconClass(selectedAlumnus)}`" aria-hidden="true"></i>
                            <span>{{ selectedAlumnus.status_label }}</span>
                        </span>
                    </div>
                </div>

                <div class="mt-5 space-y-2.5 border-t border-slate-100 pt-4 text-xs text-slate-700">
                    <p v-if="selectedAlumnus.institution_or_company">
                        <strong>{{ publicText("copy.vue_alumni_home", "text_090", "Kampus / Instansi:") }}</strong> {{ selectedAlumnus.institution_or_company }}
                    </p>
                    <p v-if="selectedAlumnus.position_or_program">
                        <strong>{{ publicText("copy.vue_alumni_home", "text_091", "Program Studi / Jabatan:") }}</strong> {{ selectedAlumnus.position_or_program }}
                    </p>
                    <p v-if="selectedAlumnus.scholarship_info">
                        <strong>{{ publicText("copy.vue_alumni_home", "text_092", "Beasiswa / Prestasi:") }}</strong> {{ selectedAlumnus.scholarship_info }}
                    </p>
                    <blockquote v-if="selectedAlumnus.testimonial" class="rounded-xl bg-slate-50 p-3 italic text-slate-600">
                        “{{ selectedAlumnus.testimonial }}”
                    </blockquote>
                    <p v-if="selectedAlumnus.contact_email">
                        <strong>{{ publicText("copy.vue_alumni_home", "text_093", "Kontak Profesional:") }}</strong> {{ selectedAlumnus.contact_email }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Modal Statistik Sebaran Alumni -->
        <div
            v-if="showStatsModal"
            role="dialog"
            aria-modal="true"
            :aria-label="publicText('copy.vue_alumni_home', 'aria_label_127', 'Statistik Sebaran Alumni')"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
            @click.self="showStatsModal = false"
        >
            <div class="ika-statistics-modal relative w-full rounded-3xl bg-white p-6 sm:p-8 shadow-2xl">
                <button
                    type="button"
                    :aria-label="publicText('copy.vue_alumni_home', 'aria_label_128', 'Tutup statistik')"
                    class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200"
                    @click="showStatsModal = false"
                >
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
                <div class="text-center mb-6">
                    <span class="inline-block bg-blue-100 text-blue-900 text-[10px] font-bold px-3 py-1 rounded-full uppercase">{{ publicText("copy.vue_alumni_home", "text_094", "DATA REALTIME") }}</span>
                    <h3 class="text-xl font-extrabold text-slate-900 mt-1">{{ publicText("copy.vue_alumni_home", "text_095", "Statistik Sebaran Alumni & Tracer Study") }}</h3>
                </div>
                <div class="ika-statistics-charts">
                    <section class="ika-statistics-chart">
                        <h4>{{ publicText("copy.vue_alumni_home", "text_096", "DISTRIBUSI STATUS KARIR") }}</h4>
                        <svg v-if="statusSegments.length" class="ika-status-donut" viewBox="0 0 200 200" role="img" aria-labelledby="ika-status-chart-title">
                            <title id="ika-status-chart-title">{{ publicText("copy.vue_alumni_home", "text_9013", "Distribusi") }} {{ summary.total }} {{ publicText("copy.vue_alumni_home", "text_9014", "alumni berdasarkan status aktivitas") }}</title>
                            <circle v-for="segment in statusSegments" :key="segment.status" cx="100" cy="100" r="72" fill="none" :stroke="segment.color" stroke-width="36" pathLength="100" :stroke-dasharray="`${Math.max(0, segment.size - .4)} ${100 - Math.max(0, segment.size - .4)}`" :stroke-dashoffset="-segment.offset" transform="rotate(-90 100 100)">
                                <title>{{ segment.label }}: {{ segment.total }} {{ publicText("copy.vue_alumni_home", "text_9015", "alumni") }}</title>
                            </circle>
                        </svg>
                        <p v-else class="ika-chart-empty">{{ publicText("copy.vue_alumni_home", "text_097", "Belum ada data alumni yang dipublikasikan.") }}</p>
                        <ul class="ika-chart-legend"><li v-for="segment in statusSegments" :key="segment.status"><span :style="{ background: segment.color }"></span>{{ segment.label }} ({{ segment.total }})</li></ul>
                    </section>
                    <section class="ika-statistics-chart">
                        <h4>{{ publicText("copy.vue_alumni_home", "text_098", "TOP KAMPUS / PERGURUAN TINGGI") }}</h4>
                        <svg v-if="statistics.campuses.length" class="ika-campus-bars" viewBox="0 0 320 260" role="img" aria-labelledby="ika-campus-chart-title">
                            <title id="ika-campus-chart-title">{{ publicText("copy.vue_alumni_home", "text_099", "Lima kampus terbanyak dari alumni yang mengizinkan publikasi karir") }}</title>
                            <g v-for="tick in campusTicks" :key="tick" class="ika-chart-grid"><line x1="42" x2="306" :y1="150 - tick / campusScale * 125" :y2="150 - tick / campusScale * 125" /><text x="32" :y="154 - tick / campusScale * 125" text-anchor="end">{{ tick }}</text></g>
                            <g v-for="(campus, index) in statistics.campuses" :key="campus.name">
                                <rect :x="50 + index * 51" :y="150 - campus.total / campusScale * 125" width="28" :height="campus.total / campusScale * 125" rx="9" fill="#f59e0b"><title>{{ campus.name }}: {{ campus.total }} {{ publicText("copy.vue_alumni_home", "text_9015", "alumni") }}</title></rect>
                                <text :transform="`translate(${72 + index * 51} 170) rotate(-25)`" text-anchor="end" class="ika-campus-label">{{ campus.name.length > 25 ? campus.name.slice(0, 24) + '…' : campus.name }}<title>{{ campus.name }}</title></text>
                            </g>
                        </svg>
                        <p v-else class="ika-chart-empty">{{ publicText("copy.vue_alumni_home", "text_100", "Belum ada data kampus yang diizinkan untuk publikasi.") }}</p>
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ika-statistics-modal { max-width: 768px; max-height: 90dvh; overflow-y: auto; }
.ika-statistics-charts { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 24px; }
.ika-statistics-chart { min-width: 0; border: 1px solid #dce4ef; border-radius: 16px; background: #f8fafc; padding: 16px; text-align: center; }
.ika-statistics-chart h4 { font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 10px; }
.ika-status-donut { display: block; width: 184px; height: 184px; margin: 0 auto 4px; }
.ika-chart-legend { display: flex; flex-wrap: wrap; justify-content: center; gap: 5px 10px; margin: 0; padding: 0; list-style: none; }
.ika-chart-legend li { display: inline-flex; align-items: center; gap: 4px; font-size: 9px; color: #62666c; font-weight: 600; }
.ika-chart-legend span { width: 9px; height: 9px; }
.ika-status-donut circle { stroke-linecap: butt; }
.ika-campus-bars { display: block; width: 100%; height: auto; aspect-ratio: 320 / 260; }
.ika-campus-bars rect { stroke: none; }
.ika-campus-bars text { stroke: none; font-weight: 400; }
.ika-chart-grid line { stroke: #d4d8df; stroke-width: 1; }
.ika-chart-grid text,.ika-campus-label { fill: #666; font: 11px Arial,sans-serif; }
.ika-chart-empty { color: #64748b; padding: 56px 12px; font-size: 13px; }
@media(max-width:600px) { .ika-statistics-charts { grid-template-columns: 1fr; gap: 16px; } }
.ika-home-page {
    background: #f8fafc;
    color: #1e293b;
}

/* ================= HERO 12-COLUMN LUXURY ================= */
.ika-home-hero {
    position: relative;
    overflow: hidden;
    color: white;
    background:
        radial-gradient(circle, rgba(255, 255, 255, 0.09) 1px, transparent 1px),
        linear-gradient(135deg, var(--ika-start, #0f172a) 0%, var(--ika-mid, #1e3a8a) 50%, var(--ika-end, #060a17) 100%);
    background-size: 16px 16px, auto;
    padding: 56px 32px 64px;
}
.ika-hero-glow {
    position: absolute;
    width: 384px;
    height: 384px;
    border-radius: 9999px;
    filter: blur(64px);
    pointer-events: none;
}
.ika-hero-glow--top {
    right: -80px;
    top: -80px;
    background: rgba(255, 255, 255, 0.1);
}
.ika-hero-glow--bottom {
    left: -80px;
    bottom: -80px;
    background: rgba(245, 158, 11, 0.2);
}
.ika-home-container {
    position: relative;
    max-width: 1536px;
    margin-inline: auto;
}
.ika-hero-grid {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 40px;
    align-items: center;
}
.ika-hero-left {
    grid-column: span 7 / span 7;
}
.ika-hero-right {
    grid-column: span 5 / span 5;
}
.ika-home-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.15);
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 600;
    backdrop-filter: blur(4px);
}
.ika-home-badge span:first-child {
    width: 8px;
    height: 8px;
    background: #fbbf24;
    border-radius: 50%;
}
.ika-home-hero h1 {
    font-size: clamp(30px, 3.8vw, 54px);
    font-weight: 800;
    line-height: 1.15;
    letter-spacing: -0.025em;
    margin: 20px 0;
    color: white;
}
.ika-home-hero h1 span {
    background: linear-gradient(90deg, #fbbf24, #fef08a, #fde68a);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.ika-home-description {
    font-size: 15.5px;
    line-height: 1.625;
    max-width: 672px;
    color: rgba(255, 255, 255, 0.9);
    margin: 0 0 24px;
}
.ika-home-search {
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: 576px;
    padding: 6px 6px 6px 16px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(12px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
}
.ika-home-search i {
    color: #fbbf24;
    font-size: 14px;
}
.ika-home-search input {
    flex: 1;
    min-width: 0;
    color: white;
    border: 0;
    background: transparent;
    outline: 0;
    font-size: 13.5px;
    padding: 8px 4px;
}
.ika-home-search input::placeholder {
    color: rgba(255, 255, 255, 0.7);
}
.ika-home-search button {
    padding: 10px 18px;
    border-radius: 12px;
    color: #020617;
    font-size: 12px;
    font-weight: 800;
    background: #f59e0b;
    white-space: nowrap;
    transition: background 0.15s ease;
}
.ika-home-search button:hover {
    background: #fbbf24;
}
.ika-home-filters {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
    font-size: 11px;
}
.ika-home-filters strong {
    color: #fbbf24;
}
.ika-home-filters a {
    padding: 3px 10px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.85);
    transition: background 0.15s ease;
}
.ika-home-filters a:hover {
    background: rgba(255, 255, 255, 0.2);
}
.ika-home-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 24px;
    padding-bottom: 24px;
}
.ika-home-actions a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 800;
    background: white;
    color: #0f172a;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.15);
    transition: transform 0.15s ease;
}
.ika-home-actions a:hover {
    transform: scale(1.03);
}
.ika-home-actions a:nth-child(2) {
    background: #f59e0b;
    color: #020617;
}
.ika-home-actions a:nth-child(3) {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    font-weight: 700;
}
.ika-home-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    padding-top: 24px;
}
.ika-home-stats > div {
    display: grid;
    gap: 4px;
    justify-items: center;
    padding: 12px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(4px);
    cursor: pointer;
    transition: background 0.15s ease;
}
.ika-home-stats > div:hover {
    background: rgba(255, 255, 255, 0.16);
}
.ika-home-stats strong {
    font-size: 28px;
    font-weight: 800;
    line-height: 1.1;
}
.ika-home-stats > div:nth-child(even) strong {
    color: #fbbf24;
}
.ika-home-stats span {
    text-transform: uppercase;
    font-size: 11px;
    color: rgba(255, 255, 255, 0.8);
    text-align: center;
    font-weight: 500;
}

/* Right Hero Image Frame */
.ika-home-visual {
    position: relative;
    border-radius: 24px;
    overflow: hidden;
    border: 4px solid rgba(255, 255, 255, 0.2);
    background: var(--ika-theme-900, #0f172a);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
}
.ika-home-visual img {
    width: 100%;
    height: 384px;
    object-fit: cover;
    transition: transform 0.7s ease;
}
.ika-home-visual:hover img {
    transform: scale(1.05);
}
.ika-visual-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, #020617 0%, rgba(2, 6, 23, 0.25) 55%, transparent 100%);
}
.ika-floating-badge {
    position: absolute;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 14px;
    border-radius: 16px;
    background: rgba(2, 6, 23, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(12px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.35);
}
.ika-floating-badge--top {
    top: 16px;
    left: 16px;
}
.ika-floating-badge--right {
    bottom: 84px;
    right: 16px;
}
.ika-badge-icon {
    width: 32px;
    height: 32px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
}
.ika-badge-icon--gold {
    background: #f59e0b;
    color: #020617;
}
.ika-badge-icon--emerald {
    background: #10b981;
    color: white;
}
.ika-badge-eyebrow {
    display: block;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
}
.ika-badge-title {
    display: block;
    font-size: 12px;
    font-weight: 800;
    color: white;
}
.ika-visual-caption {
    position: absolute;
    bottom: 16px;
    left: 16px;
    right: 16px;
    z-index: 2;
    color: white;
}
.ika-caption-pill {
    display: inline-block;
    background: #f59e0b;
    color: #0f172a;
    font-size: 10px;
    font-weight: 800;
    padding: 2px 10px;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.ika-visual-caption h3 {
    font-size: 16px;
    font-weight: 800;
    margin-top: 4px;
    color: white;
}
.ika-visual-caption p {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.8);
}

/* ================= 5. RUNNING QUICK CARDS STRIP ================= */
.ika-quick-strip {
    position: relative;
    background: var(--ika-theme-950, #060a17);
    padding: 14px 0;
    border-bottom: 1px solid rgba(30, 58, 138, 0.4);
    overflow: hidden;
}
.ika-quick-inner {
    position: relative;
    max-width: 1600px;
    margin-inline: auto;
    padding-inline: 32px;
}
.ika-quick-viewport {
    overflow: hidden;
    border-radius: 16px;
}
.ika-quick-track {
    display: flex;
    width: max-content;
    animation: ikaQuickMarquee 50s linear infinite;
}
.ika-quick-track:hover {
    animation-play-state: paused;
}
@keyframes ikaQuickMarquee {
    0% {
        transform: translateX(0%);
    }
    100% {
        transform: translateX(-50%);
    }
}
.ika-shortcuts {
    display: flex;
    align-items: center;
    gap: 14px;
    padding-inline: 7px;
}
.ika-shortcuts a {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 8px 14px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: white;
    white-space: nowrap;
    transition: border-color 0.15s ease;
}
.ika-shortcuts a:hover {
    border-color: #fbbf24;
}
.ika-shortcuts i {
    font-size: 15px;
}
.ika-shortcuts strong {
    display: block;
    font-size: 12px;
    font-weight: 700;
}
.ika-shortcuts span {
    display: block;
    font-size: 10px;
    color: rgba(255, 255, 255, 0.7);
}

/* ================= 6. MAIN CONTENT & TABS ================= */
.ika-content {
    max-width: 1536px;
    width: calc(100% - 64px);
    margin: 36px auto 64px;
    display: grid;
    gap: 32px;
}
.ika-content section,
.ika-tabs-bar,
#statistik-alumni {
    scroll-margin-top: 164px;
}
.ika-tabs-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e2e8f0;
}
.ika-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.ika-tabs button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 16px;
    border-radius: 12px;
    background: white;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-size: 13px;
    font-weight: 700;
    transition: all 0.15s ease;
}
.ika-tabs button:hover {
    background: #f1f5f9;
}
.ika-tabs button[aria-selected='true'] {
    background: var(--ika-theme-600, #1e40af);
    border-color: var(--ika-theme-600, #1e40af);
    color: white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.ika-update-cta {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 16px;
    border-radius: 12px;
    background: linear-gradient(90deg, #f59e0b, #f97316);
    color: white;
    font-size: 13px;
    font-weight: 700;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.ika-tab-content {
    display: grid;
    gap: 32px;
}
.ika-panel {
    padding: 28px 32px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 24px;
    min-width: 0;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}
.ika-panel h2,
.ika-program h2 {
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -0.025em;
    line-height: 1.3;
    color: #0f172a;
}
.ika-panel p,
.ika-program p {
    font-size: 13.5px;
    color: #64748b;
    line-height: 1.6;
}
.ika-section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
}
.ika-section-heading h2 i {
    color: var(--ika-theme-600, #1e40af);
    margin-right: 6px;
}
.ika-outline-link,
.ika-reset {
    flex-shrink: 0;
    font-size: 12px;
    font-weight: 700;
    padding: 8px 14px;
    border-radius: 9999px;
    border: 1px solid #e2e8f0;
    color: var(--ika-theme-700, #1e3a8a);
    background: white;
}
.ika-reset {
    background: #f1f5f9;
    border: 0;
    color: #475569;
    border-radius: 12px;
}

/* HALL OF FAME */
.ika-spotlight {
    background: linear-gradient(90deg, rgba(255, 251, 235, 0.7), rgba(255, 247, 237, 0.4), #f8fafc);
    border-color: rgba(253, 230, 138, 0.8);
}
.ika-eyebrow {
    display: inline-flex;
    align-items: center;
    font-size: 10px;
    font-weight: 800;
    color: #78350f;
    background: #fef3c7;
    border-radius: 9999px;
    padding: 3px 10px;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.ika-spotlight-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
}
.ika-person {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: white;
    border: 1px solid #fef3c7;
    border-radius: 16px;
    min-width: 0;
    cursor: pointer;
    transition: all 0.2s ease;
}
.ika-person:hover {
    border-color: #fbbf24;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
}
.ika-avatar {
    display: grid;
    place-items: center;
    width: 56px;
    height: 56px;
    flex-shrink: 0;
    background: #e0e7ff;
    border: 2px solid #fbbf24;
    border-radius: 50%;
    color: #4f46e5;
    font-size: 18px;
    font-weight: 800;
}
.ika-person h3 {
    font-size: 13.5px;
    font-weight: 800;
    color: #0f172a;
}
.ika-person p {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
}
.ika-status {
    display: inline-flex;
    align-items: center;
    margin-top: 4px;
    padding: 2px 8px;
    border-radius: 6px;
    background: #ecfdf5;
    color: #047857;
    font-size: 10px;
    font-weight: 700;
}

/* DIRECTORY FILTERS & TOOLBAR */
.ika-toolbar-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 16px;
    margin-bottom: 18px;
    border-bottom: 1px solid #f1f5f9;
}
.ika-year-chips {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
}
.ika-year-chips strong {
    color: #64748b;
    margin-right: 4px;
}
.ika-year-chips button {
    padding: 4px 12px;
    border-radius: 9999px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-weight: 700;
    font-size: 12px;
}
.ika-year-chips button.selected {
    color: white;
    background: var(--ika-theme-600, #1e40af);
    border-color: var(--ika-theme-600, #1e40af);
}
.ika-directory-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
}
.ika-view-buttons {
    display: flex;
    padding: 2px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #f1f5f9;
}
.ika-view-buttons button {
    padding: 5px 10px;
    font-weight: 700;
    font-size: 12px;
    border-radius: 8px;
    color: #475569;
}
.ika-view-buttons button[aria-pressed='true'] {
    background: white;
    color: var(--ika-theme-700, #1e3a8a);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.ika-directory-toolbar select {
    margin-left: 6px;
    padding: 5px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #f8fafc;
    font-weight: 700;
    color: #334155;
}
.ika-directory-filters {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.ika-filter-col-3 {
    grid-column: span 3 / span 3;
}
.ika-filter-col-2 {
    grid-column: span 2 / span 2;
}
.ika-filter-col-4 {
    grid-column: span 4 / span 4;
}
.ika-directory-filters label {
    display: grid;
    gap: 6px;
    font-size: 12px;
    color: #334155;
    font-weight: 700;
    min-width: 0;
}
.ika-directory-filters select,
.ika-directory-filters input {
    min-width: 0;
    width: 100%;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #f8fafc;
    padding: 9px 12px;
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
}
.ika-filter-search {
    display: flex;
    gap: 6px;
}
.ika-filter-search button,
.ika-primary-link {
    background: var(--ika-theme-600, #1e40af);
    color: white;
    padding: 9px 16px;
    border-radius: 10px;
    font-size: 12.5px;
    font-weight: 700;
}
.ika-summary-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 16px;
    font-size: 12.5px;
    color: #64748b;
}
.ika-result-count,
.ika-summary-hint {
    font-size: 12.5px !important;
    margin: 0;
}

/* ================= MARQUEE & GRID CARDS (EXACT REFERENCE CSS) ================= */
.alumni-photo-img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    object-position: center top !important;
    display: block !important;
    border-radius: inherit;
    transition: transform 0.35s ease;
}
.alumni-angkatan-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 16px 20px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
}
.alumni-angkatan-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 14px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
}
.badge-angkatan-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--ika-theme-50, #eff6ff);
    color: var(--ika-theme-700, #1d4ed8);
    border: 1px solid var(--ika-theme-200, #bfdbfe);
    font-weight: 800;
    font-size: 13px;
    padding: 4px 14px;
    border-radius: 9999px;
}
.badge-count-pill {
    display: inline-flex;
    align-items: center;
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
    font-weight: 700;
    font-size: 11.5px;
    padding: 4px 12px;
    border-radius: 9999px;
}
.btn-link-grid {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 700;
    color: var(--ika-theme-600, #1e40af);
    background: var(--ika-theme-50, #eff6ff);
    border: 1px solid var(--ika-theme-200, #bfdbfe);
    padding: 5px 14px;
    border-radius: 12px;
    transition: all 0.2s ease;
}
.btn-link-grid:hover {
    background: var(--ika-theme-600, #1e40af);
    color: #ffffff;
}
.alumni-marquee-container {
    position: relative;
    width: 100%;
    overflow: hidden;
    padding: 6px 0 12px;
}
.alumni-marquee-track {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: stretch;
    gap: 16px;
    width: max-content;
    animation: alumniMarqueeSlide 42s linear infinite;
}
.alumni-marquee-track.is-static {
    animation: none;
    flex-wrap: wrap;
}
.alumni-marquee-container:hover .alumni-marquee-track {
    animation-play-state: paused;
}
@keyframes alumniMarqueeSlide {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}
.alumni-marquee-card {
    flex: 0 0 165px;
    width: 165px;
    max-width: 165px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 10px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.25s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}
.alumni-marquee-card:hover,
.alumni-grid-card:hover {
    transform: translateY(-5px);
    border-color: var(--ika-theme-500, #3b82f6);
    box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.12);
}
.alumni-card-photo-box {
    position: relative;
    width: 100%;
    aspect-ratio: 3 / 4;
    height: 195px;
    border-radius: 13px;
    overflow: hidden;
    background: #f1f5f9;
}
.alumni-card-year-badge {
    position: absolute;
    top: 7px;
    right: 7px;
    background: rgba(15, 23, 42, 0.88);
    color: #fbbf24;
    font-size: 10px;
    font-weight: 800;
    padding: 2px 7.5px;
    border-radius: 9999px;
    z-index: 2;
}
.alumni-card-name {
    font-size: 12.5px;
    font-weight: 800;
    color: #0f172a;
    margin-top: 9px;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.alumni-card-jurusan {
    font-size: 11px;
    color: #64748b;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 6px;
}
.alumni-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    font-weight: 700;
    padding: 2.5px 8.5px;
    border-radius: 9999px;
    white-space: nowrap;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
}
.badge-status-kuliah {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}
.badge-status-bekerja {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}
.badge-status-asn {
    background: #faf5ff;
    color: #6b21a8;
    border: 1px solid #e9d5ff;
}
.badge-status-wirausaha {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}
.badge-status-freelance {
    background: #f0fdfa;
    color: #0f766e;
    border: 1px solid #99f6e4;
}
.badge-status-irt {
    background: #fff1f2;
    color: #be123c;
    border: 1px solid #fecdd3;
}
.badge-status-pelatihan {
    background: #fdf4ff;
    color: #a21caf;
    border: 1px solid #f0abfc;
}
.badge-status-lainnya {
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
}

/* Grid 6 Kolom */
.alumni-grid-wrapper {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 16px;
}
.alumni-grid-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 10px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.25s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}
.alumni-grid-photo-box {
    position: relative;
    width: 100%;
    aspect-ratio: 3 / 4;
    min-height: 170px;
    border-radius: 13px;
    overflow: hidden;
    background: #f1f5f9;
}
.alumni-activity-box {
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 10px;
    padding: 6px 8px;
    margin-bottom: 8px;
    font-size: 10.5px;
}
.alumni-activity-title {
    display: block;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.alumni-activity-sub {
    display: block;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.btn-profile-card {
    width: 100%;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    padding: 5px 10px;
    color: var(--ika-theme-700, #1d4ed8);
    background: var(--ika-theme-50, #eff6ff);
    border: 1px solid var(--ika-theme-200, #bfdbfe);
    margin-top: 6px;
}
.alumni-table-thumb {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #e0e7ff;
    color: #1e40af;
    font-weight: 800;
    font-size: 13px;
    display: inline-grid;
    place-items: center;
}

/* TRACER TAB BOX */
.ika-tracer-box {
    background: white;
    border-radius: 24px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    max-width: 896px;
    margin-inline: auto;
    width: 100%;
    box-shadow: 0 20px 25px -5px rgba(226, 232, 240, 0.6);
}
.ika-tracer-banner {
    background: linear-gradient(90deg, var(--ika-theme-950, #060a17), var(--ika-theme-900, #0f172a), var(--ika-theme-800, #172554));
    padding: 28px 32px;
    color: white;
}
.ika-tracer-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.4);
    font-size: 11px;
    font-weight: 800;
    padding: 4px 12px;
    border-radius: 9999px;
    margin-bottom: 10px;
}
.ika-tracer-banner h2 {
    font-size: 26px;
    font-weight: 900;
    color: white;
}
.ika-tracer-banner p {
    font-size: 13.5px;
    color: #e2e8f0;
    margin-top: 4px;
}
.ika-tracer-db-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(16, 185, 129, 0.2);
    color: #6ee7b7;
    border: 1px solid rgba(52, 211, 153, 0.4);
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 700;
}
.ika-tracer-steps {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin-top: 22px;
    padding-top: 18px;
    border-top: 1px solid rgba(255, 255, 255, 0.15);
}
.ika-tracer-steps > div {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.1);
    padding: 8px 14px;
    border-radius: 12px;
    font-size: 12px;
}
.ika-tracer-steps span {
    width: 24px;
    height: 24px;
    border-radius: 9999px;
    background: #fbbf24;
    color: #020617;
    font-weight: 900;
    display: inline-grid;
    place-items: center;
}
.ika-tracer-body {
    padding: 28px 32px;
}
.ika-tracer-step1-box {
    background: linear-gradient(135deg, #fffbeb, #ffedd5);
    border: 2px solid #fcd34d;
    border-radius: 16px;
    padding: 22px;
}
.ika-ekartu-box {
    text-align: center;
    max-width: 672px;
    margin-inline: auto;
    width: 100%;
}

/* PILAR PROGRAM */
.ika-program {
    padding-top: 24px;
    border-top: 1px solid #e2e8f0;
}
.ika-program-header {
    text-align: center;
    max-width: 768px;
    margin: 0 auto 36px;
}
.ika-program-badge {
    display: inline-block;
    background: var(--ika-theme-100, #e0effe);
    color: var(--ika-theme-800, #172554);
    font-size: 10px;
    font-weight: 800;
    padding: 4px 12px;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 8px;
}
.ika-program-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 24px;
}
.ika-program-grid article,
.ika-opportunities article {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 24px;
    transition: all 0.2s ease;
}
.ika-program-grid article:hover {
    border-color: #60a5fa;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08);
}
.ika-program-grid h3,
.ika-opportunities h3 {
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 6px;
}
.ika-program-grid a,
.ika-opportunities a {
    font-size: 12px;
    font-weight: 800;
}
.ika-opportunities {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}
.ika-empty {
    padding: 32px 20px;
    text-align: center;
    border: 1px dashed #cbd5e1;
    border-radius: 16px;
    background: #f8fafc;
}
.ika-table-scroll {
    overflow: auto;
}
.ika-table-scroll table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    text-align: left;
}
.ika-table-scroll td,
.ika-table-scroll th {
    padding: 12px;
    border-bottom: 1px solid #e2e8f0;
}
.ika-table-scroll th {
    background: #f1f5f9;
    font-weight: 800;
    color: #334155;
}
.ika-pagination {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    gap: 14px;
    margin-top: 24px;
    font-size: 12px;
}
.ika-pagination button {
    padding: 8px 14px;
    border: 1px solid #dce4ef;
    border-radius: 8px;
    font-weight: 700;
}
.ika-faq {
    padding: 24px 28px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
}
.ika-faq h2 {
    font-size: 20px;
    font-weight: 800;
    margin-bottom: 10px;
}
.ika-faq details {
    border-top: 1px solid #e2e8f0;
    padding: 14px 0;
}
.ika-faq summary {
    cursor: pointer;
    font-weight: 700;
    font-size: 14px;
}
.ika-faq p {
    margin-top: 8px;
    color: #64748b;
    font-size: 13.5px;
}

@media (max-width: 1024px) {
    .ika-hero-grid {
        grid-template-columns: 1fr;
    }
    .ika-hero-left,
    .ika-hero-right {
        grid-column: span 1 / span 1;
    }
    .ika-spotlight-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .ika-directory-filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .ika-filter-col-3,
    .ika-filter-col-2,
    .ika-filter-col-4 {
        grid-column: span 1 / span 1;
    }
    .alumni-grid-wrapper {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .ika-program-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .ika-home-hero {
        padding: 32px 16px 40px;
    }
    .ika-content {
        width: calc(100% - 32px);
    }
    .ika-panel {
        padding: 20px;
    }
    .ika-home-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .ika-floating-badge--right {
        display: none;
    }
    .ika-spotlight-grid,
    .ika-directory-filters,
    .ika-program-grid,
    .ika-opportunities,
    .ika-tracer-steps {
        grid-template-columns: 1fr;
    }
    .alumni-grid-wrapper {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .ika-home-actions a {
        width: 100%;
        justify-content: center;
    }
}
</style>
