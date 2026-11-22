<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { whiteTextContrastLabel } from '@/lib/colorContrast';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
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

interface ContentRegistry {
    revision: number;
    published: Record<string, Record<string, any>>;
    draft: Record<string, Record<string, any>>;
    revisions: Array<{ version: number; published_at: string; actor: string; blocks: Record<string, Record<string, any>> }>;
}

const props = defineProps<{
    school: SchoolData;
    canUpdate: boolean;
    accreditationOptions: string[];
    timezoneOptions?: string[];
    publicThemePresets: { value: string; label: string; description: string }[];
    publicHeroVariants: { value: string; label: string }[];
    publicContentRegistry?: ContentRegistry;
}>();

const page = usePage<SharedProps>();
const timezones = computed(() => props.timezoneOptions ?? ['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura']);
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
    theme_primary: props.school.settings?.theme_primary ?? '#3157a4',
    reset_theme_defaults: false,
    public_theme_preset: props.school.settings?.public_theme?.preset ?? 'editorial',
    public_hero_variant: props.school.settings?.public_theme?.hero_variant ?? 'collage',
    public_hero_title: props.school.settings?.public_theme?.hero_title ?? '',
    public_hero_accent: props.school.settings?.public_theme?.hero_accent ?? '',
    public_hero_primary_label: props.school.settings?.public_theme?.hero_primary_label ?? '',
    public_hero_secondary_label: props.school.settings?.public_theme?.hero_secondary_label ?? '',
    public_show_explore: props.school.settings?.public_theme?.show_explore ?? true,
    public_show_news: props.school.settings?.public_theme?.show_news ?? true,
    public_show_gallery: props.school.settings?.public_theme?.show_gallery ?? true,
    public_content_mode: null as 'draft' | 'publish' | 'restore' | null,
    public_content_expected_revision: props.publicContentRegistry?.revision ?? 0,
    public_content_restore_version: null as number | null,
    public_content_blocks: initialDraftBlocks(),
    logo: null as File | null,
    favicon: null as File | null,
});

const showResetThemeConfirm = ref(false);
const activeContrastLabel = computed(() => whiteTextContrastLabel(form.theme_primary));

const confirmResetTheme = () => {
    form.theme_primary = '#3157a4';
    form.public_theme_preset = 'editorial';
    form.public_hero_variant = 'collage';
    form.reset_theme_defaults = true;
    showResetThemeConfirm.value = false;
    submit();
};

const saveContentMode = (mode: 'draft' | 'publish' | 'restore', restoreVersion: number | null = null) => {
    form.public_content_mode = mode;
    form.public_content_restore_version = restoreVersion;
    submit();
};

const applyThemePreview = (primary?: string) => {
    if (!primary) return;

    document.documentElement.style.setProperty('--school-primary', primary);
    document.documentElement.style.setProperty('--primary', primary);
};

const resetThemePreview = () => {
    applyThemePreview(page.props.school?.theme_primary ?? props.school.settings?.theme_primary ?? '#3157a4');
};

watch(() => form.theme_primary, applyThemePreview, { immediate: true });
onBeforeUnmount(resetThemePreview);

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
    form.clearErrors(kind);
    uploads.value[kind] = form[kind] ? URL.createObjectURL(form[kind]!) : '';
};
const clearImage = (kind: 'logo' | 'favicon') => {
    if (uploads.value[kind]) URL.revokeObjectURL(uploads.value[kind]);
    uploads.value[kind] = '';
    form[kind] = null;
    uploadVersion.value++;
};
const preview = (kind: 'logo' | 'favicon') => uploads.value[kind] || page.props.school?.[`${kind}_url`] || '';
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
const submit = () => {
    form.transform((data) => {
        const { public_content_mode, public_content_expected_revision, public_content_restore_version, public_content_blocks, ...profile } = data;
        const contentChanged = JSON.stringify(public_content_blocks) !== JSON.stringify(initialDraftBlocks());
        if (public_content_mode || contentChanged) {
            return { ...profile, public_content_mode: public_content_mode ?? 'draft', public_content_expected_revision, public_content_restore_version, public_content_blocks };
        }
        return profile;
    }).post('/pengaturan/sekolah', {
        forceFormData: true, preserveScroll: true,
        onSuccess: () => { clearUploads(); form.logo = null; form.favicon = null; uploadVersion.value++; form.public_content_mode = null; form.public_content_restore_version = null; form.public_content_blocks = initialDraftBlocks(); form.public_content_expected_revision = props.publicContentRegistry?.revision ?? 0; form.defaults(); },
        onFinish: () => { form.public_content_mode = null; form.public_content_restore_version = null; },
    });
};
</script>

<template>
    <Head title="Profil Sekolah" />
    <AppLayout title="Profil Sekolah">
        <div class="view school-page">
            <header class="school-heading">
                <div>
                <p class="eyebrow">Pengaturan sekolah</p>
                <h1>Identitas & profil sekolah</h1>
                <p>Lengkapi identitas dan kontak yang digunakan pada kop surat, rapor, dan dokumen sekolah.</p>
                </div>
            </header>
            <div v-if="!canUpdate" class="notice" role="status">Anda dapat melihat profil sekolah. Perubahan dikelola administrator yang memiliki akses pengaturan sekolah.</div>
            <form class="school-form" @submit.prevent="submit">
                <div v-if="hasErrors" class="notice" role="alert">Profil sekolah belum disimpan. Periksa isian yang ditandai di bawah.</div>
                <fieldset :disabled="!canUpdate || form.processing">
                    <section class="panel settings-section">
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
                    <section class="panel settings-section">
                        <div class="section-heading"><h2>Jam layanan, peta & kanal komunikasi publik</h2><p>Digunakan pada header, halaman kontak, buku tamu, dan tautan media sosial portal publik.</p></div>
                        <div class="fields">
                            <label><span>Jam layanan operasional</span><input v-model="form.service_hours" maxlength="255" placeholder="Contoh: Senin – Jumat, 07.30 – 15.00 WITA" /><em v-if="form.errors.service_hours">{{ form.errors.service_hours }}</em></label>
                            <label><span>Kontak darurat / bantuan</span><input v-model="form.emergency_contact" maxlength="120" placeholder="Contoh: 0812-3456-7890 (PTSP)" /><em v-if="form.errors.emergency_contact">{{ form.errors.emergency_contact }}</em></label>
                            <label class="wide"><span>Informasi hari libur / penutupan layanan</span><input v-model="form.holiday_notice" maxlength="500" placeholder="Contoh: Layanan tatap muka tutup pada hari libur nasional" /><em v-if="form.errors.holiday_notice">{{ form.errors.holiday_notice }}</em></label>
                            <label><span>Tautan peta lokasi (URL)</span><input v-model="form.map_url" type="url" maxlength="500" placeholder="https://maps.google.com/..." /><em v-if="form.errors.map_url">{{ form.errors.map_url }}</em></label>
                            <label><span>Kanal WhatsApp bantuan (URL)</span><input v-model="form.whatsapp_url" type="url" maxlength="255" placeholder="https://wa.me/628..." /><em v-if="form.errors.whatsapp_url">{{ form.errors.whatsapp_url }}</em></label>
                            <label><span>Facebook resmi (URL)</span><input v-model="form.social_facebook" type="url" maxlength="255" placeholder="https://facebook.com/..." /><em v-if="form.errors.social_facebook">{{ form.errors.social_facebook }}</em></label>
                            <label><span>Instagram resmi (URL)</span><input v-model="form.social_instagram" type="url" maxlength="255" placeholder="https://instagram.com/..." /><em v-if="form.errors.social_instagram">{{ form.errors.social_instagram }}</em></label>
                            <label><span>YouTube resmi (URL)</span><input v-model="form.social_youtube" type="url" maxlength="255" placeholder="https://youtube.com/..." /><em v-if="form.errors.social_youtube">{{ form.errors.social_youtube }}</em></label>
                            <label><span>TikTok resmi (URL)</span><input v-model="form.social_tiktok" type="url" maxlength="255" placeholder="https://tiktok.com/..." /><em v-if="form.errors.social_tiktok">{{ form.errors.social_tiktok }}</em></label>
                        </div>
                    </section>
                    <section class="panel settings-section">
                        <div class="section-heading"><h2>Wilayah & jadwal salat sekolah</h2><p>Digunakan pada widget jadwal salat publik (`/jadwal-salat` dan modal beranda) sesuai zona waktu sekolah.</p></div>
                        <div class="fields">
                            <label class="wide"><span>Kota / wilayah jadwal salat</span><input v-model="form.prayer_city" maxlength="120" placeholder="Contoh: Kabupaten Banjar, Kalimantan Selatan" /><em v-if="form.errors.prayer_city">{{ form.errors.prayer_city }}</em></label>
                            <label v-for="(slot, idx) in form.prayer_times" :key="slot.name">
                                <span>Waktu {{ slot.name }} (JJ:MM)</span>
                                <input v-model="form.prayer_times[idx].time" type="time" required />
                            </label>
                        </div>
                    </section>
                    <section class="panel settings-section">
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
                    <section class="panel settings-section">
                        <div class="section-heading"><h2>Parameter operasional izin siswa &amp; kios presensi</h2><p>Atur batas jam pengajuan izin hari yang sama, rentang hari pengajuan terencana, dan jeda deduplikasi scan kios presensi.</p></div>
                        <div class="fields">
                            <label><span>Batas jam izin hari yang sama (JJ:MM)</span><input v-model="form.permit_same_day_cutoff" type="time" required /><em v-if="form.errors.permit_same_day_cutoff">{{ form.errors.permit_same_day_cutoff }}</em></label>
                            <label><span>Rentang maksimal izin terencana (hari)</span><input v-model="form.permit_max_advance_days" type="number" min="1" max="90" required /><em v-if="form.errors.permit_max_advance_days">{{ form.errors.permit_max_advance_days }}</em></label>
                            <label><span>Jeda deduplikasi scan kios RFID/QR (detik)</span><input v-model="form.kiosk_dedup_seconds" type="number" min="10" max="600" required /><em v-if="form.errors.kiosk_dedup_seconds">{{ form.errors.kiosk_dedup_seconds }}</em></label>
                        </div>
                    </section>
                    <section class="panel settings-section">
                        <div class="section-heading"><h2>Konten profil sekolah</h2><p>Konten ini menjadi bahan utama halaman profil publik sekolah dan dapat disempurnakan sebelum landing page diterbitkan.</p></div>
                        <label><span>Sekilas tentang sekolah</span><textarea v-model="form.about" rows="5" maxlength="10000" placeholder="Ceritakan sejarah singkat, karakter, dan keunggulan sekolah."></textarea><em v-if="form.errors.about">{{ form.errors.about }}</em></label>
                        <label><span>Sambutan kepala sekolah</span><textarea v-model="form.principal_message" rows="5" maxlength="10000" placeholder="Tuliskan sambutan singkat kepala sekolah."></textarea><em v-if="form.errors.principal_message">{{ form.errors.principal_message }}</em></label>
                    </section>
                    <section class="panel settings-section">
                        <div class="section-heading"><h2>Kop surat resmi</h2><p>Data ini dipakai pada PDF persuratan. Kosongkan instansi pembina bila tidak tercantum pada kop sekolah.</p></div>
                        <div class="fields">
                            <label class="wide"><span>Instansi pembina / yayasan</span><input v-model="form.letterhead_parent_institution" maxlength="255" placeholder="Contoh: Pemerintah Kabupaten … / Yayasan …" /><em v-if="form.errors.letterhead_parent_institution">{{ form.errors.letterhead_parent_institution }}</em></label>
                            <label><span>Kota/kabupaten penerbit</span><input v-model="form.letterhead_city" maxlength="100" placeholder="Contoh: Jakarta" /><em v-if="form.errors.letterhead_city">{{ form.errors.letterhead_city }}</em></label>
                            <label><span>Kode pos</span><input v-model="form.letterhead_postal_code" maxlength="15" inputmode="numeric" /><em v-if="form.errors.letterhead_postal_code">{{ form.errors.letterhead_postal_code }}</em></label>
                        </div>
                    </section>
                    <section class="panel settings-section">
                        <div class="section-heading"><h2>Logo & tampilan aplikasi</h2><p>Logo digunakan sebagai identitas sekolah. Ikon tab tampil di sudut tab peramban.</p></div>
                        <div class="fields">
                            <label v-for="kind in (['logo', 'favicon'] as const)" :key="kind" class="upload-field">
                                <span>{{ kind === 'logo' ? 'Logo sekolah' : 'Ikon tab peramban' }}</span>
                                <div class="image-preview"><img v-if="preview(kind)" :src="preview(kind)" :alt="kind === 'logo' ? 'Pratinjau logo sekolah' : 'Pratinjau favicon'" /><small v-else>Belum ada gambar</small></div>
                                <input :key="`${kind}-${uploadVersion}`" type="file" :accept="kind === 'logo' ? 'image/jpeg,image/png,image/webp' : 'image/png'" @change="selectImage(kind, $event)" />
                                <small>{{ kind === 'logo' ? 'JPG, PNG, atau WebP; maksimal 2 MB.' : 'PNG persegi; maksimal 512 × 512 piksel dan 512 KB.' }} Mengunggah file baru akan mengganti gambar lama setelah disimpan.</small>
                                <em v-if="form.errors[kind]">{{ form.errors[kind] }}</em>
                                <AppButton variant="secondary" v-if="form[kind]" type="button" class="secondary-button" @click="clearImage(kind)">Batalkan gambar baru</AppButton>
                            </label>
                        </div>
                        <div class="color-picker">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span>Warna utama aplikasi · Kontras teks putih: <strong>{{ activeContrastLabel }}</strong></span>
                                <AppButton v-if="canUpdate" variant="secondary" size="sm" type="button" @click="showResetThemeConfirm = true">Reset tema default</AppButton>
                            </div>
                            <div class="theme-options"><label v-for="color in ['#3157a4','#277450','#7255ae','#b87818','#b64a42']" :key="color" class="theme-choice"><input v-model="form.theme_primary" type="radio" :value="color" @change="form.reset_theme_defaults = false"><i :style="{background: color}"></i><span>{{ color }}</span></label></div>
                            <em v-if="form.errors.theme_primary">{{ form.errors.theme_primary }}</em>
                        </div>
                    </section>
                    <section class="panel settings-section">
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
                    <section class="panel settings-section">
                        <div class="section-heading"><h2>Visi & misi</h2><p>Jelaskan arah dan tujuan pendidikan sekolah.</p></div>
                        <label><span>Visi</span><textarea v-model="form.vision" rows="3" maxlength="5000"></textarea><em v-if="form.errors.vision">{{ form.errors.vision }}</em></label>
                        <label><span>Misi</span><textarea v-model="form.mission" rows="5" maxlength="10000" placeholder="Tuliskan setiap butir misi pada baris terpisah"></textarea><em v-if="form.errors.mission">{{ form.errors.mission }}</em></label>
                    </section>
                    <section v-if="form.public_content_blocks?.['home.hero']" class="panel settings-section">
                        <div class="section-heading">
                            <h2>Blok konten halaman publik (Draf & Publikasi)</h2>
                            <p>Kelola blok konten terstruktur dengan ID tetap (`home.hero`, `profile.values`, `profile.pillars`, `shell.footer`) tanpa mengubah tata letak halaman publik.</p>
                        </div>
                        <div class="fields">
                            <label><span>Badge utama hero (`home.hero`)</span><input v-model="form.public_content_blocks['home.hero'].badge_primary" maxlength="160" /></label>
                            <label><span>Badge kedua hero (`home.hero`)</span><input v-model="form.public_content_blocks['home.hero'].badge_secondary" maxlength="120" /></label>
                            <label><span>Awalan judul hero</span><input v-model="form.public_content_blocks['home.hero'].headline_prefix" maxlength="120" /></label>
                            <label><span>Sorotan judul hero</span><input v-model="form.public_content_blocks['home.hero'].headline_highlight" maxlength="120" /></label>
                            <label class="wide"><span>Deskripsi pembuka beranda</span><textarea v-model="form.public_content_blocks['home.hero'].description" rows="2" maxlength="500"></textarea></label>
                            <label v-if="form.public_content_blocks['profile.values']"><span>Judul seksi nilai budaya (`profile.values`)</span><input v-model="form.public_content_blocks['profile.values'].title" maxlength="160" /></label>
                            <label v-if="form.public_content_blocks['profile.pillars']"><span>Judul seksi pilar keunggulan (`profile.pillars`)</span><input v-model="form.public_content_blocks['profile.pillars'].title" maxlength="160" /></label>
                            <label v-if="form.public_content_blocks['home.welcome']" class="wide"><span>Judul sambutan beranda (`home.welcome`)</span><input v-model="form.public_content_blocks['home.welcome'].title" maxlength="250" /></label>
                            <label v-if="form.public_content_blocks['shell.footer']" class="wide"><span>Tagline footer publik (`shell.footer`)</span><input v-model="form.public_content_blocks['shell.footer'].tagline" maxlength="300" /></label>
                            <label v-if="form.public_content_blocks['ptsp.hero']"><span>Label hero PTSP (`ptsp.hero`)</span><input v-model="form.public_content_blocks['ptsp.hero'].label" maxlength="160" /></label>
                            <label v-if="form.public_content_blocks['ptsp.hero']"><span>Subjudul hero PTSP (`ptsp.hero`)</span><input v-model="form.public_content_blocks['ptsp.hero'].subtitle" maxlength="300" /></label>
                            <label v-if="form.public_content_blocks['external_letter.hero']"><span>Judul hero Surat Dinas (`external_letter.hero`)</span><input v-model="form.public_content_blocks['external_letter.hero'].title" maxlength="160" /></label>
                            <label v-if="form.public_content_blocks['external_letter.hero']"><span>Sorotan judul Surat Dinas (`external_letter.hero`)</span><input v-model="form.public_content_blocks['external_letter.hero'].highlight" maxlength="160" /></label>
                            <label v-if="form.public_content_blocks['guestbook.hero']"><span>Salam pembuka Buku Tamu (`guestbook.hero`)</span><input v-model="form.public_content_blocks['guestbook.hero'].greeting" maxlength="120" /></label>
                            <label v-if="form.public_content_blocks['guestbook.hero']"><span>Jam resepsionis Buku Tamu (`guestbook.hero`)</span><input v-model="form.public_content_blocks['guestbook.hero'].reception_hours" maxlength="120" /></label>
                            <label v-if="form.public_content_blocks['student_permit.hero']"><span>Judul hero Izin Siswa (`student_permit.hero`)</span><input v-model="form.public_content_blocks['student_permit.hero'].title" maxlength="160" /></label>
                            <label v-if="form.public_content_blocks['student_permit.hero']"><span>Label tahun ajaran Izin Siswa (`student_permit.hero`)</span><input v-model="form.public_content_blocks['student_permit.hero'].academic_year_label" maxlength="60" /></label>
                        </div>
                        <div class="section-toggles" aria-label="Visibilitas seksi halaman publik">
                            <label v-if="form.public_content_blocks['home.hero']"><input v-model="form.public_content_blocks['home.hero'].visible" type="checkbox" /> Tampilkan Hero Beranda</label>
                            <label v-if="form.public_content_blocks['home.welcome']"><input v-model="form.public_content_blocks['home.welcome'].visible" type="checkbox" /> Tampilkan Sambutan</label>
                            <label v-if="form.public_content_blocks['home.zi']"><input v-model="form.public_content_blocks['home.zi'].visible" type="checkbox" /> Tampilkan Zona Integritas</label>
                            <label v-if="form.public_content_blocks['home.services']"><input v-model="form.public_content_blocks['home.services'].visible" type="checkbox" /> Tampilkan Seksi PTSP</label>
                            <label v-if="form.public_content_blocks['profile.values']"><input v-model="form.public_content_blocks['profile.values'].visible" type="checkbox" /> Tampilkan 5 Nilai Budaya</label>
                            <label v-if="form.public_content_blocks['profile.pillars']"><input v-model="form.public_content_blocks['profile.pillars'].visible" type="checkbox" /> Tampilkan 4 Pilar Keunggulan</label>
                            <label v-if="form.public_content_blocks['shell.options']"><input v-model="form.public_content_blocks['shell.options'].hide_demo_fallbacks" type="checkbox" /> Sembunyikan konten contoh/demo saat data kosong</label>
                        </div>
                        <div v-if="canUpdate" class="flex flex-wrap items-center justify-between gap-2 pt-2">
                            <div class="flex flex-wrap gap-2">
                                <AppButton variant="secondary" size="sm" type="button" :disabled="form.processing" @click="saveContentMode('draft')">Simpan sebagai draf</AppButton>
                                <AppButton variant="primary" size="sm" type="button" :disabled="form.processing" @click="saveContentMode('publish')">Terbitkan konten publik</AppButton>
                                <AppButton variant="ghost" size="sm" as="a" href="/?preview_draft=1" target="_blank" rel="noreferrer">Pratinjau draf privat ↗</AppButton>
                            </div>
                            <div v-if="publicContentRegistry?.revisions?.length" class="flex items-center gap-2 text-xs">
                                <span>Riwayat: v{{ publicContentRegistry.revisions.at(-1)?.version }} ({{ publicContentRegistry.revisions.at(-1)?.actor }})</span>
                                <AppButton variant="ghost" size="sm" type="button" :disabled="form.processing" @click="saveContentMode('restore', publicContentRegistry.revisions.at(-1)?.version ?? null)">Pulihkan versi terakhir</AppButton>
                            </div>
                        </div>
                    </section>
                    <div v-if="canUpdate" class="panel save-bar"><span>{{ form.isDirty ? 'Ada perubahan yang belum disimpan' : 'Profil sekolah sudah tersimpan' }}</span><AppButton variant="primary" type="submit" class="primary-button" :disabled="form.processing || !form.isDirty">{{ form.processing ? 'Menyimpan…' : 'Simpan profil sekolah' }}</AppButton></div>
                </fieldset>
            </form>
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
.save-bar { position: sticky; bottom: 1rem; z-index: 10; background: var(--surface); border: 1px solid var(--line); border-radius: 14px; box-shadow: var(--shadow-md); }
.theme-choice:has(input:checked) { border-color: var(--primary); background: var(--primary-soft); }
@media (max-width: 640px) { .save-bar { bottom: max(.5rem, env(safe-area-inset-bottom)); } }
</style>
