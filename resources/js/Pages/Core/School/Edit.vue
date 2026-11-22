<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PublicContentFields from '@/components/PublicContentFields.vue';
import { applyAdminBranding, defaultAdminTheme, type AdminTheme } from '@/lib/adminBranding';
import { whiteTextContrastLabel } from '@/lib/colorContrast';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { SharedProps } from '../../../types';

interface SchoolData {
    id: number;
    name: string;
    npsn: string;
    level: string;
    timezone?: string;
    address?: string;
    phone?: string;
    email?: string;
    legal_name?: string;
    school_status?: string;
    accreditation?: string;
    principal_name?: string;
    website?: string;
    vision?: string;
    mission?: string;
    about?: string;
    principal_message?: string;
    logo_path?: string;
    favicon_path?: string;
    settings?: {
        theme_primary?: string;
        application_name?: string | null;
        admin_theme?: AdminTheme;
        service_hours?: string | null;
        holiday_notice?: string | null;
        emergency_contact?: string | null;
        map_url?: string | null;
        whatsapp_url?: string | null;
        social_links?: {
            facebook?: string | null;
            instagram?: string | null;
            youtube?: string | null;
            tiktok?: string | null;
        };
        prayer_city?: string | null;
        prayer_times?: Array<{ name: string; time: string }>;
        service_catalog?: Record<string, { available?: boolean; badge?: string | null; maintenance_message?: string | null }>;
        operations?: {
            permit_same_day_cutoff?: string | null;
            permit_max_advance_days?: number | null;
            kiosk_dedup_seconds?: number | null;
        };
        public_theme?: {
            preset?: string;
            use_identity_color?: boolean;
            hero_variant?: string;
            hero_title?: string | null;
            hero_accent?: string | null;
            hero_primary_label?: string | null;
            hero_secondary_label?: string | null;
            show_explore?: boolean;
            show_news?: boolean;
            show_gallery?: boolean;
        };
        letterhead?: { parent_institution?: string; city?: string; postal_code?: string };
    };
}

type ContentAction = 'draft' | 'publish' | 'restore' | 'unpublish' | 'archive' | 'schedule' | 'cancel_schedule';
interface ContentRegistry {
    status?: string;
    scheduled?: { publish_at: string; actor: string } | null;
    revision: number;
    published: Record<string, Record<string, any>>;
    draft: Record<string, Record<string, any>>;
    revisions: Array<{ version: number; published_at: string; actor: string; blocks: Record<string, Record<string, any>> }>;
}

const props = defineProps<{
    school: SchoolData;
    canUpdate: boolean;
    editorMode?: 'profile' | 'public';
    profileRevision: string;
    accreditationOptions: string[];
    timezoneOptions?: string[];
    publicThemePresets: { value: string; label: string; description: string }[];
    publicHeroVariants: { value: string; label: string }[];
    publicContentRegistry?: ContentRegistry;
    publicContentLabels?: Record<string, string>;
    publicIconOptions?: string[];
    publicMedia?: { label: string; url: string }[];
}>();

const page = usePage<SharedProps>();
const editorMode = props.editorMode ?? 'profile';
const isPublicEditor = editorMode === 'public';
const pageTitle = computed(() => isPublicEditor ? 'Editor Halaman Publik' : 'Profil Sekolah');
const timezones = computed(() => props.timezoneOptions ?? ['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura']);
const initialBaseColor = page.props.publicPortalTheme === 'madrasah'
    && props.school.settings?.public_theme?.use_identity_color !== true
    ? '#047857'
    : (props.school.settings?.theme_primary ?? '#3157a4');
const initialDraftBlocks = () => JSON.parse(JSON.stringify(props.publicContentRegistry?.draft ?? {
    'home.hero': {
        id: 'home.hero',
        visible: true,
        badge_primary: 'Madrasah Aliyah Negeri Riset & Adiwiyata Mandiri',
        badge_secondary: 'Zona Integritas (WBK)',
        headline_prefix: 'Mencetak Generasi',
        headline_highlight: 'Qur’ani, Unggul Riset',
        headline_suffix: ', dan Berintegritas',
        description: '',
        cta_ppdb_label: 'Daftar PPDB',
        cta_ptsp_label: 'PTSP Online',
        cta_lapor_label: 'Siap Lapor',
        cta_lacak_label: 'Lacak Berkas',
    },
    'profile.values': {
        id: 'profile.values',
        visible: true,
        title: '5 Nilai Budaya Kerja Kementerian Agama',
        subtitle: '',
    },
    'profile.pillars': {
        id: 'profile.pillars',
        visible: true,
        title: '',
    },
    'shell.footer': {
        id: 'shell.footer',
        visible: true,
        tagline: '',
        copyright_notice: 'Hak Cipta Dilindungi Undang-Undang.',
    },
    'ptsp.hero': {
        id: 'ptsp.hero',
        visible: true,
        label: 'PTSP Digital & Layanan Akademik',
        title: '',
        subtitle: '',
    },
    'external_letter.hero': {
        id: 'external_letter.hero',
        visible: true,
        badge: 'Agenda Persuratan Tata Usaha',
        title: 'Layanan Penerimaan',
        highlight: 'Surat Dinas Eksternal',
        lead: '',
    },
    'guestbook.hero': {
        id: 'guestbook.hero',
        visible: true,
        label: 'Resepsionis & Buku Tamu Digital',
        greeting: 'Ahlan wa Sahlan / Selamat Datang',
        title: '',
        description: '',
        reception_hours: '',
    },
    'student_permit.hero': {
        id: 'student_permit.hero',
        visible: true,
        badge: 'Layanan Resmi Perizinan Siswa',
        title: 'Surat Izin Siswa Online',
        academic_year_label: 'TA 2026/2027',
        description: '',
    },
}));

const form = useForm({
    profile_expected_revision: props.profileRevision,
    name: props.school.name,
    npsn: props.school.npsn,
    level: props.school.level,
    timezone: props.school.timezone ?? 'Asia/Jakarta',
    address: props.school.address ?? '',
    phone: props.school.phone ?? '',
    email: props.school.email ?? '',
    legal_name: props.school.legal_name ?? '',
    school_status: props.school.school_status ?? '',
    accreditation: props.school.accreditation ?? '',
    principal_name: props.school.principal_name ?? '',
    website: props.school.website ?? '',
    vision: props.school.vision ?? '',
    mission: props.school.mission ?? '',
    about: props.school.about ?? '',
    principal_message: props.school.principal_message ?? '',
    letterhead_parent_institution: props.school.settings?.letterhead?.parent_institution ?? '',
    letterhead_city: props.school.settings?.letterhead?.city ?? '',
    letterhead_postal_code: props.school.settings?.letterhead?.postal_code ?? '',
    service_hours: props.school.settings?.service_hours ?? '',
    holiday_notice: props.school.settings?.holiday_notice ?? '',
    emergency_contact: props.school.settings?.emergency_contact ?? '',
    map_url: props.school.settings?.map_url ?? '',
    whatsapp_url: props.school.settings?.whatsapp_url ?? '',
    social_facebook: props.school.settings?.social_links?.facebook ?? '',
    social_instagram: props.school.settings?.social_links?.instagram ?? '',
    social_youtube: props.school.settings?.social_links?.youtube ?? '',
    social_tiktok: props.school.settings?.social_links?.tiktok ?? '',
    prayer_city: props.school.settings?.prayer_city ?? props.school.settings?.letterhead?.city ?? '',
    prayer_times: (props.school.settings?.prayer_times?.length ? props.school.settings.prayer_times : [
        { name: 'Subuh', time: '04:45' },
        { name: 'Dzuhur', time: '12:10' },
        { name: 'Ashar', time: '15:25' },
        { name: 'Maghrib', time: '18:15' },
        { name: 'Isya', time: '19:25' },
    ]).map((slot) => ({ name: slot.name, time: slot.time })),
    service_catalog: {
        ptsp: {
            available: props.school.settings?.service_catalog?.ptsp?.available ?? true,
            badge: props.school.settings?.service_catalog?.ptsp?.badge ?? 'Aktif',
            maintenance_message: props.school.settings?.service_catalog?.ptsp?.maintenance_message ?? '',
        },
        legalisir: {
            available: props.school.settings?.service_catalog?.legalisir?.available ?? true,
            badge: props.school.settings?.service_catalog?.legalisir?.badge ?? 'Aktif',
            maintenance_message: props.school.settings?.service_catalog?.legalisir?.maintenance_message ?? '',
        },
        'mutasi-siswa': {
            available: props.school.settings?.service_catalog?.['mutasi-siswa']?.available ?? true,
            badge: props.school.settings?.service_catalog?.['mutasi-siswa']?.badge ?? 'Aktif',
            maintenance_message: props.school.settings?.service_catalog?.['mutasi-siswa']?.maintenance_message ?? '',
        },
        'izin-siswa': {
            available: props.school.settings?.service_catalog?.['izin-siswa']?.available ?? true,
            badge: props.school.settings?.service_catalog?.['izin-siswa']?.badge ?? 'Aktif',
            maintenance_message: props.school.settings?.service_catalog?.['izin-siswa']?.maintenance_message ?? '',
        },
        'kelulusan-skl': {
            available: props.school.settings?.service_catalog?.['kelulusan-skl']?.available ?? true,
            badge: props.school.settings?.service_catalog?.['kelulusan-skl']?.badge ?? 'Aktif',
            maintenance_message: props.school.settings?.service_catalog?.['kelulusan-skl']?.maintenance_message ?? '',
        },
        'siap-lapor': {
            available: props.school.settings?.service_catalog?.['siap-lapor']?.available ?? true,
            badge: props.school.settings?.service_catalog?.['siap-lapor']?.badge ?? 'Aktif',
            maintenance_message: props.school.settings?.service_catalog?.['siap-lapor']?.maintenance_message ?? '',
        },
    } as Record<string, { available: boolean; badge: string; maintenance_message: string }>,
    permit_same_day_cutoff: props.school.settings?.operations?.permit_same_day_cutoff ?? '09:00',
    permit_max_advance_days: props.school.settings?.operations?.permit_max_advance_days ?? 30,
    kiosk_dedup_seconds: props.school.settings?.operations?.kiosk_dedup_seconds ?? 120,
    theme_primary: initialBaseColor,
    application_name: props.school.settings?.application_name ?? '',
    admin_theme: {
        ...defaultAdminTheme,
        ...props.school.settings?.admin_theme,
        primary: initialBaseColor,
    },
    reset_theme_defaults: false,
    public_theme_preset: props.school.settings?.public_theme?.preset ?? 'editorial',
    public_use_identity_color: props.school.settings?.public_theme?.use_identity_color ?? true,
    public_hero_variant: props.school.settings?.public_theme?.hero_variant ?? 'collage',
    public_hero_title: props.school.settings?.public_theme?.hero_title ?? '',
    public_hero_accent: props.school.settings?.public_theme?.hero_accent ?? '',
    public_hero_primary_label: props.school.settings?.public_theme?.hero_primary_label ?? '',
    public_hero_secondary_label: props.school.settings?.public_theme?.hero_secondary_label ?? '',
    public_show_explore: props.school.settings?.public_theme?.show_explore ?? true,
    public_show_news: props.school.settings?.public_theme?.show_news ?? true,
    public_show_gallery: props.school.settings?.public_theme?.show_gallery ?? true,
    public_content_mode: null as ContentAction | null,
    public_content_expected_revision: props.publicContentRegistry?.revision ?? 0,
    public_content_restore_version: null as number | null,
    public_content_publish_at: null as string | null,
    public_content_blocks: initialDraftBlocks(),
    logo: null as File | null,
    favicon: null as File | null,
    remove_logo: false,
    remove_favicon: false,
});

const showResetThemeConfirm = ref(false);
const activeContrastLabel = computed(() => whiteTextContrastLabel(form.theme_primary));
const baseColorPresets = [
    { name: 'Kemenag', color: '#047857' },
    { name: 'Emerald', color: '#277450' },
    { name: 'Ocean', color: '#0369a1' },
    { name: 'Indigo', color: '#4338ca' },
    { name: 'Slate', color: '#334155' },
];
const syncBaseColor = (color: string) => {
    form.theme_primary = color;
    form.admin_theme.primary = color;
    form.public_use_identity_color = true;
    form.reset_theme_defaults = false;
};

const confirmResetTheme = () => {
    form.theme_primary = '#3157a4';
    form.public_theme_preset = 'editorial';
    form.public_use_identity_color = false;
    form.public_hero_variant = 'collage';
    form.reset_theme_defaults = true;
    form.admin_theme = { ...defaultAdminTheme };
    showResetThemeConfirm.value = false;
    submit();
};

const scheduleAt = ref('');
const restoreVersion = ref<number | null>(props.publicContentRegistry?.revisions.at(-1)?.version ?? null);
const pendingContentAction = ref<'unpublish' | 'archive' | null>(null);
const confirmContentAction = () => {
    if (!pendingContentAction.value) return;
    const mode = pendingContentAction.value;
    pendingContentAction.value = null;
    saveContentMode(mode);
};
const saveContentMode = (mode: ContentAction, restoreVersion: number | null = null) => {
    form.public_content_publish_at = mode === 'schedule' && scheduleAt.value ? scheduleAt.value : null;
    form.public_content_mode = mode;
    form.public_content_restore_version = restoreVersion;
    submit();
};


watch(() => form.admin_theme, applyAdminBranding, { deep: true, immediate: true });
onBeforeUnmount(() => applyAdminBranding(null));

const uploads = ref<Record<'logo' | 'favicon', string>>({ logo: '', favicon: '' });
const uploadVersion = ref(0);
const clearUploads = () => {
    Object.values(uploads.value).forEach((url) => { if (url) URL.revokeObjectURL(url); });
    uploads.value = { logo: '', favicon: '' };
};
onBeforeUnmount(clearUploads);
const selectImage = (kind: 'logo' | 'favicon', event: Event) => {
    if (uploads.value[kind]) URL.revokeObjectURL(uploads.value[kind]);
    form[kind] = (event.target as HTMLInputElement).files?.[0] ?? null;
    form[`remove_${kind}`] = false;
    form.clearErrors(kind);
    uploads.value[kind] = form[kind] ? URL.createObjectURL(form[kind]!) : '';
};
const clearImage = (kind: 'logo' | 'favicon') => {
    if (uploads.value[kind]) URL.revokeObjectURL(uploads.value[kind]);
    uploads.value[kind] = '';
    form[kind] = null;
    uploadVersion.value++;
};
const pendingBrandingRemoval = ref<'logo' | 'favicon' | null>(null);
const confirmBrandingRemoval = () => {
    const kind = pendingBrandingRemoval.value;
    if (kind) { clearImage(kind); form[`remove_${kind}`] = true; }
    pendingBrandingRemoval.value = null;
};
const preview = (kind: 'logo' | 'favicon') => form[`remove_${kind}`] ? '' : uploads.value[kind] || page.props.school?.[`${kind}_url`] || '';
const themeColors = [
    { key: 'accent', label: 'Aksen' },
    { key: 'success', label: 'Berhasil' }, { key: 'warning', label: 'Peringatan' }, { key: 'danger', label: 'Destruktif' },
] as const;
const fields = [
    { key: 'name', label: 'Nama resmi sekolah', required: true, max: 255 },
    { key: 'npsn', label: 'NPSN', required: true, max: 20 },
    { key: 'legal_name', label: 'Nama badan hukum / yayasan', max: 255 },
    { key: 'principal_name', label: 'Nama kepala sekolah', max: 255 },
    { key: 'phone', label: 'Nomor telepon', type: 'tel', max: 30 },
    { key: 'email', label: 'Email resmi', type: 'email', max: 255 },
    { key: 'website', label: 'Website', type: 'url', max: 255, placeholder: 'https://sekolah.sch.id' },
] as const;
const hasErrors = computed(() => Object.keys(form.errors).length > 0);
const pendingLeaveUrl = ref<string | null>(null);
const stopLeaveGuard = router.on('before', (event) => {
    if (event.detail.visit.method === 'get' && form.isDirty && event.detail.visit.url.pathname !== window.location.pathname) {
        event.preventDefault();
        pendingLeaveUrl.value = event.detail.visit.url.href;
    }
});
const confirmLeave = async () => {
    const url = pendingLeaveUrl.value;
    pendingLeaveUrl.value = null;
    form.reset();
    await nextTick();
    if (url) router.visit(url);
};
const beforeUnload = (event: BeforeUnloadEvent) => {
    if (!form.isDirty) return;
    event.preventDefault();
    event.returnValue = '';
};
onMounted(() => window.addEventListener('beforeunload', beforeUnload));
onBeforeUnmount(() => { stopLeaveGuard(); window.removeEventListener('beforeunload', beforeUnload); });
const submit = () => {
    form.transform((data) => {
        const { public_content_mode, public_content_expected_revision, public_content_restore_version, public_content_publish_at, public_content_blocks, ...profile } = data;
        const initial = initialDraftBlocks();
        const changedBlocks = Object.fromEntries(Object.entries(public_content_blocks).filter(([key, block]) => JSON.stringify(block) !== JSON.stringify(initial[key])));
        const contentChanged = Object.keys(changedBlocks).length > 0;
        if (public_content_mode || contentChanged) {
            return { ...profile, public_content_mode: public_content_mode ?? 'draft', public_content_expected_revision, public_content_restore_version, public_content_publish_at, public_content_blocks_json: JSON.stringify(changedBlocks) };
        }
        return profile;
    }).post('/pengaturan/sekolah', {
        forceFormData: Boolean(form.logo || form.favicon), preserveScroll: true,
        onSuccess: () => { clearUploads(); form.logo = null; form.favicon = null; form.remove_logo = false; form.remove_favicon = false; uploadVersion.value++; form.public_content_mode = null; form.public_content_restore_version = null; form.public_content_blocks = initialDraftBlocks(); form.public_content_expected_revision = props.publicContentRegistry?.revision ?? 0; form.profile_expected_revision = props.profileRevision; form.defaults(); },
        onFinish: () => { form.public_content_mode = null; form.public_content_restore_version = null; },
    });
};
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout :title="pageTitle">
        <div class="view school-page">
            <header class="school-heading">
                <div>
                <p class="eyebrow">{{ isPublicEditor ? 'Portal publik' : 'Pengaturan sekolah' }}</p>
                <h1>{{ isPublicEditor ? 'Editor halaman publik' : 'Profil sekolah' }}</h1>
                <p>{{ isPublicEditor ? 'Atur tampilan dan isi yang dilihat pengunjung portal sekolah.' : 'Kelola identitas, operasional, dan branding sekolah dari satu tempat.' }}</p>
                </div>
            </header>
            <div v-if="!canUpdate" class="notice" role="status">Anda dapat melihat profil sekolah. Perubahan dikelola administrator yang memiliki akses pengaturan sekolah.</div>
            <section v-if="isPublicEditor" class="panel editor-mode-banner">
                <div><span class="profile-scope-label">Editor khusus portal publik</span><p>Perubahan di sini hanya memengaruhi halaman yang dilihat masyarakat. Identitas resmi dan pengaturan operasional tetap berada di menu Profil Sekolah.</p></div>
                <AppButton as="a" href="/pengaturan/sekolah" variant="secondary" size="sm">Buka profil sekolah</AppButton>
            </section>
            <section v-else-if="canUpdate" class="panel settings-overview"><div><h2>{{ school.name }}</h2><p>NPSN {{ school.npsn }} · {{ school.level }}</p><p>{{ school.address || 'Alamat belum diisi' }}</p></div><span class="profile-scope-label">Profil sekolah aktif</span></section>
            <div :aria-labelledby="canUpdate ? 'school-editor-title' : undefined">
            <form class="school-form" @submit.prevent="submit">
                <div v-if="hasErrors" class="notice" role="alert">{{ isPublicEditor ? 'Perubahan halaman publik belum disimpan. Periksa isian yang ditandai di bawah.' : 'Profil sekolah belum disimpan. Periksa isian yang ditandai di bawah.' }}</div>
                <fieldset :disabled="!canUpdate || form.processing">
                    <section v-if="!isPublicEditor" class="panel settings-section">
                        <div class="section-heading"><h2>Identitas resmi</h2><p>* Wajib diisi.</p></div>
                        <div class="fields">
                            <label v-for="field in fields" :key="field.key">
                                <span>{{ field.label }}{{ 'required' in field && field.required ? ' *' : '' }}</span>
                                <input v-model="form[field.key]" :type="'type' in field ? field.type : 'text'" :required="'required' in field && field.required" :maxlength="field.max" :placeholder="'placeholder' in field ? field.placeholder : undefined" :aria-invalid="!!form.errors[field.key]" />
                                <em v-if="form.errors[field.key]">{{ form.errors[field.key] }}</em>
                            </label>
                            <label><span>Jenjang sekolah *</span><select v-model="form.level" required><option v-for="level in ['SD', 'SMP', 'SMA', 'SMK', 'MI', 'MTs', 'MA']" :key="level" :value="level">{{ level }}</option></select><em v-if="form.errors.level">{{ form.errors.level }}</em></label>
                            <label><span>Status sekolah</span><select v-model="form.school_status"><option value="">Belum diisi</option><option>Negeri</option><option>Swasta</option></select><em v-if="form.errors.school_status">{{ form.errors.school_status }}</em></label>
                            <label><span>Akreditasi</span><select v-model="form.accreditation" :aria-invalid="!!form.errors.accreditation"><option value="">Belum diisi</option><option v-for="option in accreditationOptions" :key="option" :value="option">{{ option }}</option></select><em v-if="form.errors.accreditation">{{ form.errors.accreditation }}</em></label>
                            <label><span>Zona waktu sekolah</span><select v-model="form.timezone"><option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option></select><em v-if="form.errors.timezone">{{ form.errors.timezone }}</em></label>
                            <label class="wide"><span>Alamat lengkap</span><textarea v-model="form.address" rows="3" placeholder="Jalan, kelurahan, kecamatan, kota/kabupaten, dan kode pos"></textarea><em v-if="form.errors.address">{{ form.errors.address }}</em></label>
                        </div>
                    </section>
                    <section v-if="!isPublicEditor" class="panel settings-section">
                        <div class="section-heading"><h2>Jam layanan, peta & kanal komunikasi publik</h2><p>Digunakan pada header, halaman kontak, buku tamu, dan tautan media sosial portal publik.</p></div>
                        <div class="fields">
                            <label><span>Jam layanan operasional</span><input v-model="form.service_hours" maxlength="255" placeholder="Senin – Jumat, 07.30 – 15.00 WITA" /><em v-if="form.errors.service_hours">{{ form.errors.service_hours }}</em></label>
                            <label><span>Kontak darurat / bantuan</span><input v-model="form.emergency_contact" maxlength="120" placeholder="0812-3456-7890 (PTSP)" /><em v-if="form.errors.emergency_contact">{{ form.errors.emergency_contact }}</em></label>
                            <label class="wide"><span>Informasi hari libur / penutupan layanan</span><input v-model="form.holiday_notice" maxlength="500" placeholder="Layanan tatap muka tutup pada hari libur nasional" /><em v-if="form.errors.holiday_notice">{{ form.errors.holiday_notice }}</em></label>
                            <label><span>Tautan peta lokasi (URL)</span><input v-model="form.map_url" type="url" maxlength="500" placeholder="https://maps.google.com/..." /><em v-if="form.errors.map_url">{{ form.errors.map_url }}</em></label>
                            <label><span>Kanal WhatsApp bantuan (URL)</span><input v-model="form.whatsapp_url" type="url" maxlength="255" placeholder="https://wa.me/628..." /><em v-if="form.errors.whatsapp_url">{{ form.errors.whatsapp_url }}</em></label>
                            <label><span>Facebook resmi (URL)</span><input v-model="form.social_facebook" type="url" maxlength="255" placeholder="https://facebook.com/..." /><em v-if="form.errors.social_facebook">{{ form.errors.social_facebook }}</em></label>
                            <label><span>Instagram resmi (URL)</span><input v-model="form.social_instagram" type="url" maxlength="255" placeholder="https://instagram.com/..." /><em v-if="form.errors.social_instagram">{{ form.errors.social_instagram }}</em></label>
                            <label><span>YouTube resmi (URL)</span><input v-model="form.social_youtube" type="url" maxlength="255" placeholder="https://youtube.com/..." /><em v-if="form.errors.social_youtube">{{ form.errors.social_youtube }}</em></label>
                            <label><span>TikTok resmi (URL)</span><input v-model="form.social_tiktok" type="url" maxlength="255" placeholder="https://tiktok.com/..." /><em v-if="form.errors.social_tiktok">{{ form.errors.social_tiktok }}</em></label>
                        </div>
                    </section>
                    <section v-if="!isPublicEditor" class="panel settings-section">
                        <div class="section-heading"><h2>Wilayah & jadwal salat sekolah</h2><p>Digunakan pada widget jadwal salat publik (`/jadwal-salat` dan modal beranda) sesuai zona waktu sekolah.</p></div>
                        <div class="fields">
                            <label class="wide"><span>Kota / wilayah jadwal salat</span><input v-model="form.prayer_city" maxlength="120" placeholder="Kabupaten Banjar, Kalimantan Selatan" /><em v-if="form.errors.prayer_city">{{ form.errors.prayer_city }}</em></label>
                            <label v-for="(slot, idx) in form.prayer_times" :key="slot.name">
                                <span>Waktu {{ slot.name }} (JJ:MM)</span>
                                <input v-model="form.prayer_times[idx].time" type="time" required />
                            </label>
                        </div>
                    </section>
                    <section v-if="!isPublicEditor" class="panel settings-section">
                        <div class="section-heading"><h2>Status operasional katalog E-Layanan (`/e-layanan`)</h2><p>Atur status buka/pemeliharaan dan badge tiap layanan publik agar pengunjung tidak menemui layanan tertutup tanpa keterangan.</p></div>
                        <div class="fields">
                            <div v-for="(cfg, serviceKey) in form.service_catalog" :key="serviceKey" class="service-status-row">
                                <label class="section-toggle-inline">
                                    <input v-model="cfg.available" type="checkbox" @change="cfg.badge = cfg.available ? 'Aktif' : 'Pemeliharaan'" />
                                    <strong>{{ serviceKey.toUpperCase() }}</strong>
                                </label>
                                <input v-model="cfg.badge" maxlength="40" placeholder="Label badge (mis. Aktif / Tutup)" aria-label="Badge layanan" />
                                <input v-model="cfg.maintenance_message" maxlength="255" placeholder="Pesan status / jadwal buka (opsional)" aria-label="Pesan status layanan" />
                            </div>
                        </div>
                    </section>
                    <section v-if="!isPublicEditor" class="panel settings-section">
                        <div class="section-heading"><h2>Parameter operasional izin siswa &amp; kios presensi</h2><p>Atur batas jam pengajuan izin hari yang sama, rentang hari pengajuan terencana, dan jeda deduplikasi scan kios presensi.</p></div>
                        <div class="fields">
                            <label><span>Batas jam izin hari yang sama (JJ:MM)</span><input v-model="form.permit_same_day_cutoff" type="time" required /><em v-if="form.errors.permit_same_day_cutoff">{{ form.errors.permit_same_day_cutoff }}</em></label>
                            <label><span>Rentang maksimal izin terencana (hari)</span><input v-model="form.permit_max_advance_days" type="number" min="1" max="90" required /><em v-if="form.errors.permit_max_advance_days">{{ form.errors.permit_max_advance_days }}</em></label>
                            <label><span>Jeda deduplikasi scan kios RFID/QR (detik)</span><input v-model="form.kiosk_dedup_seconds" type="number" min="10" max="600" required /><em v-if="form.errors.kiosk_dedup_seconds">{{ form.errors.kiosk_dedup_seconds }}</em></label>
                        </div>
                    </section>
                    <section v-if="!isPublicEditor" class="panel settings-section">
                        <div class="section-heading"><h2>Konten profil sekolah</h2><p>Konten ini menjadi bahan utama halaman profil publik sekolah dan dapat disempurnakan sebelum landing page diterbitkan.</p></div>
                        <label><span>Sekilas tentang sekolah</span><textarea v-model="form.about" rows="5" maxlength="10000" placeholder="Ceritakan sejarah singkat, karakter, dan keunggulan sekolah."></textarea><em v-if="form.errors.about">{{ form.errors.about }}</em></label>
                        <label><span>Sambutan kepala sekolah</span><textarea v-model="form.principal_message" rows="5" maxlength="10000" placeholder="Tuliskan sambutan singkat kepala sekolah."></textarea><em v-if="form.errors.principal_message">{{ form.errors.principal_message }}</em></label>
                    </section>
                    <section v-if="!isPublicEditor" class="panel settings-section">
                        <div class="section-heading"><h2>Kop surat resmi</h2><p>Data ini dipakai pada PDF persuratan. Kosongkan instansi pembina bila tidak tercantum pada kop sekolah.</p></div>
                        <div class="fields">
                            <label class="wide"><span>Instansi pembina / yayasan</span><input v-model="form.letterhead_parent_institution" maxlength="255" placeholder="Pemerintah Kabupaten … / Yayasan …" /><em v-if="form.errors.letterhead_parent_institution">{{ form.errors.letterhead_parent_institution }}</em></label>
                            <label><span>Kota/kabupaten penerbit</span><input v-model="form.letterhead_city" maxlength="100" placeholder="Jakarta" /><em v-if="form.errors.letterhead_city">{{ form.errors.letterhead_city }}</em></label>
                            <label><span>Kode pos</span><input v-model="form.letterhead_postal_code" maxlength="15" inputmode="numeric" /><em v-if="form.errors.letterhead_postal_code">{{ form.errors.letterhead_postal_code }}</em></label>
                        </div>
                    </section>
                    <section v-if="!isPublicEditor" class="panel settings-section">
                        <div class="section-heading"><h2>Logo & tampilan aplikasi</h2><p>Logo digunakan sebagai identitas sekolah. Ikon tab tampil di sudut tab peramban.</p></div>
                        <div class="fields">
                            <label v-for="kind in (['logo', 'favicon'] as const)" :key="kind" class="upload-field">
                                <span>{{ kind === 'logo' ? 'Logo sekolah' : 'Ikon tab peramban' }}</span>
                                <div class="image-preview"><img v-if="preview(kind)" :src="preview(kind)" :alt="kind === 'logo' ? 'Pratinjau logo sekolah' : 'Pratinjau favicon'" /><small v-else>Belum ada gambar</small></div>
                                <input :key="`${kind}-${uploadVersion}`" type="file" :accept="kind === 'logo' ? 'image/jpeg,image/png,image/webp' : 'image/png'" @change="selectImage(kind, $event)" />
                                <small>{{ kind === 'logo' ? 'JPG, PNG, atau WebP; maksimal 2 MB.' : 'PNG persegi; maksimal 512 × 512 piksel dan 512 KB.' }} Mengunggah file baru akan mengganti gambar lama setelah disimpan.</small>
                                <em v-if="form.errors[kind]">{{ form.errors[kind] }}</em>
                                <AppButton variant="secondary" v-if="form[kind]" type="button" class="secondary-button" @click="clearImage(kind)">Batalkan gambar baru</AppButton>
                                <AppButton v-if="canUpdate && preview(kind) && !form[kind]" variant="danger" type="button" @click="pendingBrandingRemoval = kind">Hapus gambar</AppButton>
                                <AppButton v-if="form[`remove_${kind}`]" variant="secondary" type="button" @click="form[`remove_${kind}`] = false">Batalkan penghapusan</AppButton>
                            </label>
                        </div>
                        <div class="color-picker base-color-picker">
                            <div class="base-color-heading">
                                <div><span>Warna dasar sekolah</span><small>Satu warna ini diterapkan merata pada portal publik dan panel admin.</small></div>
                                <AppButton v-if="canUpdate" variant="secondary" size="sm" type="button" @click="showResetThemeConfirm = true">Reset tema default</AppButton>
                            </div>
                            <div class="base-color-control">
                                <input :value="form.theme_primary" type="color" aria-label="Pilih warna dasar sekolah" @input="syncBaseColor(($event.target as HTMLInputElement).value)" />
                                <div><strong>{{ form.theme_primary.toUpperCase() }}</strong><small>Kontras teks putih: {{ activeContrastLabel }}</small></div>
                            </div>
                            <div class="theme-options" role="group" aria-label="Preset warna dasar sekolah">
                                <AppButton v-for="preset in baseColorPresets" :key="preset.name" variant="secondary" type="button" :aria-pressed="form.theme_primary === preset.color" @click="syncBaseColor(preset.color)"><i :style="{ background: preset.color }" aria-hidden="true"></i><span>{{ preset.name }}</span></AppButton>
                            </div>
                            <em v-if="form.errors.theme_primary">{{ form.errors.theme_primary }}</em>
                        </div>
                    </section>
                    <section v-if="!isPublicEditor" class="panel settings-section">
                        <div class="section-heading"><h2>Tampilan admin</h2><p>Warna dasar mengikuti konfigurasi di atas. Aksen status tetap dapat disesuaikan secara terpisah.</p></div>
                        <div class="admin-theme-preview" aria-label="Pratinjau tema admin"><strong>{{ form.application_name || 'Portal administrasi' }}</strong><span>{{ whiteTextContrastLabel(form.admin_theme.primary) }} · kontras teks putih</span><AppButton type="button" :style="{ background: form.admin_theme.primary, color: '#fff', borderRadius: `${form.admin_theme.radius}px` }">Pratinjau tombol</AppButton></div>
                        <div class="fields">
                            <label><span>Nama aplikasi</span><input v-model="form.application_name" maxlength="80" /><em v-if="form.errors.application_name">{{ form.errors.application_name }}</em></label>
                            <label v-for="color in themeColors" :key="color.key"><span>{{ color.label }}</span><input v-model="form.admin_theme[color.key]" type="color" @input="form.reset_theme_defaults = false" /><small>Kontras teks putih {{ whiteTextContrastLabel(form.admin_theme[color.key]) }}</small><em v-if="form.errors[`admin_theme.${color.key}`]">{{ form.errors[`admin_theme.${color.key}`] }}</em></label>
                            <label><span>Tipografi</span><select v-model="form.admin_theme.font" @change="form.reset_theme_defaults = false"><option value="system">Sistem</option><option value="serif">Serif</option></select></label>
                            <label><span>Radius sudut</span><select v-model.number="form.admin_theme.radius" @change="form.reset_theme_defaults = false"><option :value="6">6 px</option><option :value="8">8 px</option></select></label>
                            <label><span>Menu awal pada perangkat kecil</span><select v-model="form.admin_theme.navigation_default" @change="form.reset_theme_defaults = false"><option value="active">Buka kelompok halaman aktif</option><option value="expanded">Buka semua kelompok menu</option></select></label>
                            <label><span>Kepadatan panel</span><select v-model="form.admin_theme.density" @change="form.reset_theme_defaults = false"><option value="comfortable">Lapang</option><option value="compact">Ringkas</option></select></label>
                        </div>
                    </section>
                    <section v-if="isPublicEditor" class="panel settings-section">
                        <div class="section-heading"><h2>Tampilan situs publik</h2><p>Sesuaikan karakter halaman depan. Kosongkan teks sampul untuk memakai bawaan sistem.</p></div>
                        <div class="fields">
                            <label><span>Preset warna</span><select v-model="form.public_theme_preset"><option v-for="preset in publicThemePresets" :key="preset.value" :value="preset.value">{{ preset.label }}</option></select><small>{{ publicThemePresets.find((item) => item.value === form.public_theme_preset)?.description }}</small><em v-if="form.errors.public_theme_preset">{{ form.errors.public_theme_preset }}</em></label>
                            <label><span>Gaya foto sampul</span><select v-model="form.public_hero_variant"><option v-for="variant in publicHeroVariants" :key="variant.value" :value="variant.value">{{ variant.label }}</option></select><em v-if="form.errors.public_hero_variant">{{ form.errors.public_hero_variant }}</em></label>
                            <label class="wide"><span>Judul utama</span><input v-model="form.public_hero_title" maxlength="120" placeholder="Tempat belajar yang memberi ruang untuk" /><small>Bagian aksen diatur terpisah agar judul tetap mudah ditata.</small><em v-if="form.errors.public_hero_title">{{ form.errors.public_hero_title }}</em></label>
                            <label><span>Frasa aksen</span><input v-model="form.public_hero_accent" maxlength="80" placeholder="tumbuh bersama." /><em v-if="form.errors.public_hero_accent">{{ form.errors.public_hero_accent }}</em></label>
                            <label><span>Tombol utama</span><input v-model="form.public_hero_primary_label" maxlength="40" placeholder="Kenali sekolah" /><em v-if="form.errors.public_hero_primary_label">{{ form.errors.public_hero_primary_label }}</em></label>
                            <label><span>Tautan kedua</span><input v-model="form.public_hero_secondary_label" maxlength="40" placeholder="Baca kabar terbaru" /><em v-if="form.errors.public_hero_secondary_label">{{ form.errors.public_hero_secondary_label }}</em></label>
                        </div>
                        <div class="section-toggles" aria-label="Bagian halaman depan">
                            <label><input v-model="form.public_show_explore" type="checkbox" /> Jelajahi sekolah</label>
                            <label><input v-model="form.public_show_news" type="checkbox" /> Kabar terbaru</label>
                            <label><input v-model="form.public_show_gallery" type="checkbox" /> Galeri pilihan</label>
                        </div>
                        <em v-for="key in ['public_show_explore', 'public_show_news', 'public_show_gallery'] as const" :key="key">{{ form.errors[key] }}</em>
                    </section>
                    <section v-if="!isPublicEditor" class="panel settings-section">
                        <div class="section-heading"><h2>Visi & misi</h2><p>Jelaskan arah dan tujuan pendidikan sekolah.</p></div>
                        <label><span>Visi</span><textarea v-model="form.vision" rows="3" maxlength="5000"></textarea><em v-if="form.errors.vision">{{ form.errors.vision }}</em></label>
                        <label><span>Misi</span><textarea v-model="form.mission" rows="5" maxlength="10000" placeholder="Tuliskan setiap butir misi pada baris terpisah"></textarea><em v-if="form.errors.mission">{{ form.errors.mission }}</em></label>
                    </section>
                    <p v-if="form.errors.profile_expected_revision" class="field-error" role="alert">{{ form.errors.profile_expected_revision }}</p>
                    <section v-if="isPublicEditor && form.public_content_blocks?.['home.hero']" class="panel settings-section">
                        <div class="section-heading">
                            <h2>Konten halaman publik</h2>
                            <p>Ubah isi bagian yang tersedia, pratinjau draf, lalu terbitkan sesuai jadwal.</p>
                        </div>
                        <PublicContentFields v-model="form.public_content_blocks" :labels="publicContentLabels" :media="publicMedia" :icons="publicIconOptions" />
                        <p v-if="form.errors.public_content_blocks" class="field-error" role="alert">{{ form.errors.public_content_blocks }}</p>
                        <div v-if="canUpdate" class="flex flex-wrap items-center justify-between gap-2 pt-2">
                            <div class="flex flex-wrap gap-2">
                                <AppButton variant="secondary" size="sm" type="button" :disabled="form.processing" @click="saveContentMode('draft')">Simpan sebagai draf</AppButton>
                                <AppButton variant="primary" size="sm" type="button" :disabled="form.processing" @click="saveContentMode('publish')">Terbitkan konten publik</AppButton>
                                <AppButton variant="ghost" size="sm" as="a" href="/?preview_draft=1" target="_blank" rel="noreferrer">Pratinjau draf privat ↗</AppButton>
                            </div>
                            <div class="fields wide">
                                <label><span>Jadwal terbit ({{ form.timezone }})</span><input v-model="scheduleAt" type="datetime-local" /><em v-if="form.errors.public_content_publish_at">{{ form.errors.public_content_publish_at }}</em></label>
                                <AppButton type="button" variant="secondary" :disabled="form.processing || !scheduleAt" @click="saveContentMode('schedule')">Jadwalkan publikasi</AppButton>
                                <AppButton v-if="publicContentRegistry?.scheduled" type="button" variant="ghost" :disabled="form.processing" @click="saveContentMode('cancel_schedule')">Batalkan jadwal terbit</AppButton>
                                <p v-if="publicContentRegistry?.scheduled">Jadwal tersimpan: {{ new Date(publicContentRegistry.scheduled.publish_at).toLocaleString('id-ID', { timeZone: form.timezone }) }}</p>
                                <AppButton type="button" variant="secondary" :disabled="form.processing" @click="pendingContentAction = 'unpublish'">Tarik publikasi bagian konten</AppButton>
                                <AppButton type="button" variant="secondary" :disabled="form.processing" @click="pendingContentAction = 'archive'">Arsipkan bagian konten</AppButton>
                            </div>
                            <div v-if="publicContentRegistry?.revisions?.length" class="flex items-center gap-2 text-xs">
                                <label><span>Versi yang dipulihkan</span><select v-model="restoreVersion"><option v-for="revision in publicContentRegistry.revisions" :key="revision.version" :value="revision.version">v{{ revision.version }} — {{ revision.actor }} — {{ revision.published_at }}</option></select></label>
                                <AppButton variant="ghost" size="sm" type="button" :disabled="form.processing" @click="saveContentMode('restore', restoreVersion)">Pulihkan versi terakhir</AppButton>
                            </div>
                        </div>
                    </section>
                    <div v-if="canUpdate" class="panel save-bar"><span>{{ form.isDirty ? 'Ada perubahan yang belum disimpan' : (isPublicEditor ? 'Halaman publik sudah tersimpan' : 'Profil sekolah sudah tersimpan') }}</span><AppButton variant="primary" type="submit" class="primary-button" :disabled="form.processing || !form.isDirty">{{ form.processing ? 'Menyimpan…' : (isPublicEditor ? 'Simpan perubahan publik' : 'Simpan profil sekolah') }}</AppButton></div>
                </fieldset>
            </form>
            </div>
            <ConfirmDialog :open="pendingLeaveUrl !== null" title="Tinggalkan perubahan?" message="Perubahan profil dan draf yang belum disimpan akan hilang." confirm-label="Tinggalkan halaman" @confirm="confirmLeave" @cancel="pendingLeaveUrl = null" />
            <ConfirmDialog :open="pendingBrandingRemoval !== null" title="Hapus gambar sekolah?" message="Gambar akan dihapus setelah profil disimpan. Halaman akan menggunakan identitas teks bawaan." confirm-label="Hapus gambar" @confirm="confirmBrandingRemoval" @cancel="pendingBrandingRemoval = null" />
            <ConfirmDialog :open="pendingContentAction !== null" title="Sembunyikan bagian konten publik?" message="Bagian konten terkelola akan disembunyikan. Draf dan riwayat tetap tersimpan dan dapat diterbitkan kembali. Formulir layanan tetap tersedia." confirm-label="Sembunyikan konten" @confirm="confirmContentAction" @cancel="pendingContentAction = null" />
            <ConfirmDialog
                :open="showResetThemeConfirm"
                title="Reset tema dan warna ke bawaan?"
                message="Warna utama aplikasi akan dikembalikan ke biru standar (#3157a4) dan preset tampilan publik dikembalikan ke Editorial."
                confirm-label="Ya, reset tema"
                variant="warning"
                :loading="form.processing"
                @confirm="confirmResetTheme"
                @cancel="showResetThemeConfirm = false"
            />
        </div>
    </AppLayout>
</template>

<style scoped>
.admin-theme-preview { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; padding: 18px; border: 1px solid var(--line); border-radius: 8px; background: var(--surface-subtle); }
.admin-theme-preview span { color: var(--ink-soft); font-size: 12px; }
.profile-scope-label { display: inline-flex; align-items: center; min-height: 32px; padding: .35rem .65rem; border: 1px solid color-mix(in srgb, var(--primary) 24%, var(--line)); border-radius: 999px; color: var(--primary-strong); background: var(--primary-soft); font-size: var(--type-meta); font-weight: var(--weight-semibold); }
.base-color-picker { gap: .85rem; padding: 1rem; border: 1px solid color-mix(in srgb, var(--primary) 24%, var(--line)); border-radius: .75rem; background: color-mix(in srgb, var(--primary-soft) 46%, var(--surface)); }
.base-color-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.base-color-heading > div { display: grid; gap: .25rem; }
.base-color-heading small, .base-color-control small { color: var(--ink-soft); font-size: var(--type-meta); font-weight: var(--weight-regular); line-height: 1.5; }
.base-color-control { display: flex; align-items: center; gap: .75rem; }
.base-color-control input[type='color'] { width: 48px !important; min-width: 48px; height: 40px; min-height: 40px; padding: 3px; border: 1px solid var(--line-strong); border-radius: .55rem; background: var(--surface); cursor: pointer; }
.base-color-control div { display: grid; gap: .1rem; }
.theme-options button { display: inline-flex; align-items: center; gap: .45rem; min-height: 38px; }
.theme-options button[aria-pressed='true'] { border-color: var(--primary); background: var(--primary-soft); color: var(--primary-strong); }
.theme-options button i { width: 14px; height: 14px; border-radius: 50%; box-shadow: inset 0 0 0 1px rgb(15 23 42 / 16%); }
.settings-overview { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; padding: 1.25rem; }

.settings-section,.save-bar{padding:1.25rem}.section-heading h2{font-weight: var(--weight-bold)}.hero-card>div{min-width:0}.notice{padding:1rem;border:1px solid #cbd5e1;border-radius:.75rem;font-size:var(--type-sm);line-height:1.6}.notice.success{background:#ecfdf5;color:#166534}
.school-page,.school-form,fieldset{display:grid;gap:1.25rem;min-width:0}.school-page{max-width:1100px;margin:auto}fieldset{border:0;padding:0;margin:0}.settings-section{display:grid;gap:1.25rem;min-width:0}.section-heading h2{margin:0;font-size:var(--type-md)}.section-heading p{margin:.4rem 0 0;color:var(--ink-soft);font-size:var(--type-meta);line-height:1.6}.fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.settings-section label{display:grid;align-content:start;gap:.45rem;min-width:0;font-size:var(--type-meta);font-weight:var(--weight-semibold)}.settings-section input:not([type=radio]),.settings-section select,.settings-section textarea{width:100%;min-width:0;min-height:44px;padding:.7rem .8rem;border:1px solid var(--line);border-radius:.7rem;background:var(--surface);color:var(--ink);font:inherit;font-weight:var(--weight-regular)}.settings-section textarea{resize:vertical;line-height:1.6}.settings-section :is(input,select,textarea):focus-visible{outline:3px solid color-mix(in srgb,var(--primary) 30%,transparent);outline-offset:2px}.wide{grid-column:1/-1}.settings-section small{font-size:var(--type-meta);font-weight:var(--weight-regular);color:var(--ink-soft);line-height:1.6;overflow-wrap:anywhere}.settings-section em{font-size:var(--type-meta);font-style:normal;color:var(--red)}.image-preview{display:grid;place-items:center;height:110px;border:1px dashed var(--line);border-radius:.7rem;background:var(--surface-subtle)}.image-preview img{max-width:100%;max-height:90px;object-fit:contain}.color-picker{display:grid;gap:.6rem;font-size:var(--type-meta);font-weight:var(--weight-bold)}.theme-options{display:flex;flex-wrap:wrap;gap:.6rem}.settings-section .theme-choice{display:flex;align-items:center;gap:.4rem;padding:.65rem;border:1px solid var(--line);border-radius:.65rem;font-size:var(--type-meta)}.theme-choice i{width:1.1rem;height:1.1rem;border-radius:50%}.save-bar{display:flex;align-items:center;justify-content:space-between;gap:1rem}.save-bar span{font-size:var(--type-meta);color:var(--ink-soft)}button{min-height:44px}.upload-field button{width:fit-content}fieldset:disabled input,fieldset:disabled select,fieldset:disabled textarea{opacity:.7}@media(max-width:640px){.fields{grid-template-columns:1fr}.save-bar{flex-direction:column;align-items:stretch}.save-bar button{width:100%}}
.section-toggles{display:flex;flex-wrap:wrap;gap:.65rem}.settings-section .section-toggles label{display:flex;align-items:center;gap:.55rem;min-height:44px;padding:.55rem .75rem;border:1px solid var(--line);border-radius:.65rem;font-weight:var(--weight-semibold)}.section-toggles input{width:1.1rem;height:1.1rem;accent-color:var(--primary)}
.settings-section .section-toggles input[type=checkbox]{width:1.1rem;height:1.1rem;min-width:1.1rem;min-height:0;flex:0 0 1.1rem;padding:0;accent-color:var(--primary)}
.school-page { width: 100%; }
.school-heading { padding: .5rem 0 .75rem; }
.school-heading .eyebrow { margin: 0 0 .5rem; color: var(--primary); font-size: var(--type-meta); font-weight: var(--weight-semibold); letter-spacing: .04em; text-transform: none; }
.school-heading h1 { margin: 0; color: var(--ink); font-size: var(--type-3xl); font-weight: var(--weight-bold); letter-spacing: -.04em; line-height: 1.2; }
.school-heading p:not(.eyebrow) { max-width: 640px; margin: .5rem 0 0; color: var(--ink-soft); font-size: var(--type-md); line-height: 1.55; }
.settings-section { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; box-shadow: none; }
.settings-section .section-heading { display: block; margin-bottom: .25rem; padding-bottom: .75rem; border-bottom: 1px solid var(--line); }
.settings-section .section-heading h2 { color: var(--ink); font-size: 1.05rem; }
.save-bar { position: static; background: var(--surface); border: 1px solid var(--line); border-radius: 14px; box-shadow: var(--shadow-md); }
.theme-choice:has(input:checked) { border-color: var(--primary); background: var(--primary-soft); }
@media (max-width: 640px) { .save-bar { bottom: max(.5rem, env(safe-area-inset-bottom)); } .base-color-heading { flex-direction: column; } .base-color-heading button { width: 100%; } }
.editor-mode-banner { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border: 1px solid color-mix(in srgb, var(--primary) 22%, var(--line)); background: color-mix(in srgb, var(--primary-soft) 56%, var(--surface)); }
.editor-mode-banner > div { display: grid; gap: .45rem; min-width: 0; }
.editor-mode-banner p { max-width: 720px; margin: 0; color: var(--ink-soft); font-size: .84rem; line-height: 1.5; }
.editor-mode-banner .profile-scope-label { width: fit-content; }
/* This is a repeated operational form: compact controls improve scanning without shrinking touch targets. */
.school-page { max-width: 1040px; gap: 1rem; }
.school-heading { padding: .25rem 0 .5rem; }
.school-heading h1 { font-size: clamp(1.45rem, 2.3vw, 1.9rem); letter-spacing: -.025em; line-height: 1.2; }
.school-heading p:not(.eyebrow) { max-width: 680px; margin-top: .4rem; font-size: .9rem; line-height: 1.5; }
.school-form { gap: 1rem; }
.settings-section, .save-bar { padding: 1rem 1.1rem; border-radius: 12px; }
.settings-section { gap: 1rem; }
.settings-section .section-heading { margin-bottom: 0; padding-bottom: .6rem; }
.settings-section .section-heading h2 { font-size: .98rem; line-height: 1.3; }
.section-heading p, .settings-section small, .settings-section em { font-size: .78rem; line-height: 1.45; }
.fields { gap: .8rem; }
.settings-section label { gap: .35rem; font-size: .8rem; line-height: 1.35; }
.settings-section input:not([type=radio]), .settings-section select, .settings-section textarea { min-height: 40px; padding: .55rem .7rem; border-radius: .6rem; font-size: .86rem; }
.settings-section textarea { line-height: 1.5; }
.section-toggles { gap: .5rem; }
.settings-section .section-toggles label { min-height: 40px; padding: .45rem .65rem; font-size: .8rem; }
.save-bar span { font-size: .78rem; }
@media (max-width: 640px) {
    .editor-mode-banner { align-items: stretch; flex-direction: column; }
    .editor-mode-banner a { width: 100%; }
    .settings-section, .save-bar { padding: .9rem; }
}
</style>
