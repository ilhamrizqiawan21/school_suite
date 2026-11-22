<script setup lang="ts">
import { usePublicContentText } from '@/composables/usePublicContentText';
import { Link, router, useForm } from '@inertiajs/vue3';
import Head from '@/components/PublicHead.vue';
import { computed, ref } from 'vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    contentBlocks?: Record<string, Record<string, any>>;
    urgencyOptions: { value: string; label: string }[];
}>();

const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const quickTrackCode = ref('');
const quickTrackError = ref('');

const form = useForm({
    agency_name: '',
    contact_person: '',
    contact_position: '',
    contact_phone: '',
    contact_email: '',
    letter_number: '',
    letter_date: '',
    urgency: 'ordinary',
    classification: 'Umum',
    subject: '',
    summary: '',
    primary_pdf: null as File | null,
    supporting_files: [] as File[],
    verification_secret: '',
    statement_accepted: false,
    idempotency_key: `srt-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
});

const onPrimaryPdfChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    form.primary_pdf = input.files?.[0] ?? null;
};

const onSupportingChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    form.supporting_files = Array.from(input.files ?? []);
};

const submit = () => {
    form.post('/layanan/surat-dinas', { forceFormData: true });
};

const submitQuickTrack = () => {
    const code = quickTrackCode.value.trim();
    if (!code) {
        quickTrackError.value = 'Masukkan kode tiket atau nomor surat terlebih dahulu.';
        return;
    }

    quickTrackError.value = '';
    router.get('/lacak', {
        code,
        category: 'surat_eksternal',
    });
};
const publicText = usePublicContentText();
</script>

<template>
    <Head :title="`Kirim Surat Dinas Eksternal — ${school.name}`" />
    <main class="public-site public-portal-page" data-madrasah-service="external-letter" :style="themeStyle">
        <PublicHeader :school="school" />

        <header v-if="contentBlocks?.['external_letter.hero']?.visible !== false" class="surat-hero-mesh parity-letter-hero">
            <div class="hero-dot-grid" aria-hidden="true" />
            <div class="hero-inner">
                <div class="parity-letter-copy">
                    <p class="surat-service-label">
                        <span class="pulse-dot" aria-hidden="true" />
                        <span>{{ publicText("copy.vue_services_externalletter", "text_001", "KANAL DINAS RESMI TERVERIFIKASI") }}</span>
                        <span aria-hidden="true">•</span>
                        <span class="label-sub">{{ publicText("copy.vue_services_externalletter", "text_002", "Persuratan Eksternal") }}</span>
                    </p>
                    <h1>
                        {{ contentBlocks?.['external_letter.hero']?.title ?? 'Penerimaan & Agenda Digital' }}
                        <span class="hero-title-highlight">{{ contentBlocks?.['external_letter.hero']?.highlight ?? 'Surat Kedinasan Eksternal' }}</span>
                    </h1>
                    <p class="hero-lead">
                        {{ contentBlocks?.['external_letter.hero']?.lead ?? `Fasilitas resmi bagi instansi pemerintah, lembaga pendidikan, dan organisasi mitra untuk mengirimkan surat dinas secara langsung ke meja Tata Usaha ${school.name}. Teragendakan otomatis, aman, dan dapat dilacak secara real-time.` }}
                    </p>

                    <div class="workflow-steps" :aria-label="publicText('copy.vue_services_externalletter', 'aria_label_038', 'Alur layanan surat dinas')">
                        <div class="workflow-step">
                            <span class="workflow-step-label">{{ publicText("copy.vue_services_externalletter", "text_003", "Tahap 1") }}</span>
                            <p><i class="fa-solid fa-file-pdf step-icon--amber" aria-hidden="true"></i> {{ publicText("copy.vue_services_externalletter", "text_004", "Unggah Scan PDF") }}</p>
                        </div>
                        <div class="workflow-step">
                            <span class="workflow-step-label">{{ publicText("copy.vue_services_externalletter", "text_005", "Tahap 2") }}</span>
                            <p><i class="fa-solid fa-qrcode step-icon--emerald" aria-hidden="true"></i> {{ publicText("copy.vue_services_externalletter", "text_006", "Resi & QR Tiket") }}</p>
                        </div>
                        <div class="workflow-step">
                            <span class="workflow-step-label">{{ publicText("copy.vue_services_externalletter", "text_007", "Tahap 3") }}</span>
                            <p><i class="fa-solid fa-stamp step-icon--blue" aria-hidden="true"></i> {{ publicText("copy.vue_services_externalletter", "text_008", "Disposisi Pimpinan") }}</p>
                        </div>
                        <div class="workflow-step">
                            <span class="workflow-step-label">{{ publicText("copy.vue_services_externalletter", "text_009", "Tahap 4") }}</span>
                            <p><i class="fa-solid fa-magnifying-glass-chart step-icon--emerald" aria-hidden="true"></i> {{ publicText("copy.vue_services_externalletter", "text_010", "Lacak Real-Time") }}</p>
                        </div>
                    </div>

                    <div class="hero-links">
                        <a :href="publicText('copy.vue_services_externalletter', 'href_039', '#external-letter-form-title')" class="hero-btn hero-btn--primary">
                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                            <span>{{ publicText("copy.vue_services_externalletter", "text_011", "Buat Pengiriman Surat Baru") }}</span>
                        </a>
                        <Link href="/lacak?category=surat_eksternal" class="hero-btn hero-btn--secondary">
                            <i class="fa-solid fa-magnifying-glass-chart" aria-hidden="true"></i>
                            <span>{{ publicText("copy.vue_services_externalletter", "text_012", "Lacak Status Surat (Tracking)") }}</span>
                        </Link>
                    </div>
                </div>

                <aside class="letter-sidebar">
                    <article class="glass-sidebar-card">
                        <div class="sidebar-card-head">
                            <div class="sidebar-card-title-wrap">
                                <i class="fa-solid fa-shield-halved sidebar-shield-icon" aria-hidden="true"></i>
                                <h2 class="sidebar-title">{{ publicText("copy.vue_services_externalletter", "text_013", "Standar Layanan Administrasi") }}</h2>
                            </div>
                            <span class="sidebar-badge">{{ publicText("copy.vue_services_externalletter", "text_014", "ISO 9001 Terpadu") }}</span>
                        </div>

                        <ul class="sidebar-list">
                            <li>
                                <span class="sidebar-icon sidebar-icon--emerald"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></span>
                                <div>
                                    <strong>{{ publicText("copy.vue_services_externalletter", "text_015", "Pencatatan Agenda Otomatis") }}</strong>
                                    <span>{{ publicText("copy.vue_services_externalletter", "text_016", "Setiap surat resmi langsung memperoleh nomor agenda Tata Usaha & resi digital.") }}</span>
                                </div>
                            </li>
                            <li>
                                <span class="sidebar-icon sidebar-icon--amber"><i class="fa-solid fa-fingerprint" aria-hidden="true"></i></span>
                                <div>
                                    <strong>{{ publicText("copy.vue_services_externalletter", "text_017", "Otoritas Tanda Terima Digital") }}</strong>
                                    <span>{{ publicText("copy.vue_services_externalletter", "text_018", "Pengirim memperoleh Tanda Terima Elektronik resmi dengan Kode Tiket & PIN pelacakan.") }}</span>
                                </div>
                            </li>
                            <li>
                                <span class="sidebar-icon sidebar-icon--blue"><i class="fa-solid fa-book-bookmark" aria-hidden="true"></i></span>
                                <div>
                                    <strong>{{ publicText("copy.vue_services_externalletter", "text_019", "Tanpa Biaya (Rp 0,-)") }}</strong>
                                    <span>{{ publicText("copy.vue_services_externalletter", "text_020", "Seluruh layanan penerimaan surat dinas bebas biaya dan diproses oleh Tata Usaha.") }}</span>
                                </div>
                            </li>
                        </ul>

                        <form class="quick-track-box" @submit.prevent="submitQuickTrack">
                            <label for="quick-track-input" class="quick-track-label">
                                <i class="fa-solid fa-bolt" aria-hidden="true"></i>
                                <span>{{ publicText("copy.vue_services_externalletter", "text_021", "Cek Cepat Status Surat:") }}</span>
                            </label>
                            <div class="quick-track-row">
                                <input
                                    id="quick-track-input"
                                    v-model="quickTrackCode"
                                    type="text"
                                    :placeholder="publicText('copy.vue_services_externalletter', 'placeholder_040', 'Ketik Kode Tiket (SRT-...)...')"
                                />
                                <button type="submit" :aria-label="publicText('copy.vue_services_externalletter', 'aria_label_041', 'Lacak surat')">
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </div>
                            <p v-if="quickTrackError" class="quick-track-error" role="alert">{{ quickTrackError }}</p>
                        </form>

                        <div class="sidebar-footer-row">
                            <span>{{ publicText("copy.vue_services_externalletter", "text_022", "Butuh bantuan langsung?") }}</span>
                            <Link href="/kontak" class="sidebar-contact-link">{{ publicText("copy.vue_services_externalletter", "text_023", "Hubungi Tata Usaha →") }}</Link>
                        </div>
                    </article>
                </aside>
            </div>
        </header>

        <section class="portal-container parity-letter-main">
            <div class="letter-layout parity-letter-form">
                <form class="bento-form-grid" @submit.prevent="submit">
                    <section class="portal-card bento-card bento-card--agency" aria-labelledby="external-letter-form-title">
                        <div class="bento-card-header">
                            <div class="bento-icon bento-icon--emerald">
                                <i class="fa-solid fa-landmark-dome" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h2 id="external-letter-form-title" class="service-form-title">{{ publicText("copy.vue_services_externalletter", "text_024", "1. Lembaga Pengirim") }}</h2>
                                <p class="bento-subtitle">{{ publicText("copy.vue_services_externalletter", "text_025", "Data instansi asal & narahubung resmi") }}</p>
                            </div>
                        </div>

                        <div class="agency-fields">
                            <FormField id="ext-agency" :label="publicText('copy.vue_services_externalletter', 'label_10001', 'Nama Instansi / Lembaga Pengirim')" required :error="form.errors.agency_name" v-slot="{ inputId, describedBy, invalid }">
                                <input :id="inputId" v-model="form.agency_name" type="text" required :placeholder="publicText('copy.vue_services_externalletter', 'placeholder_042', 'Contoh: Dinas Pendidikan / Kementerian Agama')" :aria-describedby="describedBy" :aria-invalid="invalid" />
                            </FormField>

                            <FormField id="ext-contact-person" :label="publicText('copy.vue_services_externalletter', 'label_10002', 'Nama Narahubung')" required :error="form.errors.contact_person" v-slot="{ inputId, describedBy, invalid }">
                                <input :id="inputId" v-model="form.contact_person" type="text" required :placeholder="publicText('copy.vue_services_externalletter', 'placeholder_043', 'Nama lengkap narahubung / PIC')" :aria-describedby="describedBy" :aria-invalid="invalid" />
                            </FormField>

                            <FormField id="ext-contact-pos" :label="publicText('copy.vue_services_externalletter', 'label_10003', 'Jabatan Narahubung')" required :error="form.errors.contact_position" v-slot="{ inputId, describedBy, invalid }">
                                <input :id="inputId" v-model="form.contact_position" type="text" required :placeholder="publicText('copy.vue_services_externalletter', 'placeholder_044', 'Contoh: Kasubbag Tata Usaha / Staf')" :aria-describedby="describedBy" :aria-invalid="invalid" />
                            </FormField>

                            <FormField id="ext-contact-phone" :label="publicText('copy.vue_services_externalletter', 'label_10004', 'Nomor Telepon / WhatsApp Narahubung')" required :error="form.errors.contact_phone" v-slot="{ inputId, describedBy, invalid }">
                                <input :id="inputId" v-model="form.contact_phone" type="text" required :placeholder="publicText('copy.vue_services_externalletter', 'placeholder_045', '08xxxxxxxxxx')" :aria-describedby="describedBy" :aria-invalid="invalid" />
                            </FormField>

                            <FormField id="ext-contact-email" :label="publicText('copy.vue_services_externalletter', 'label_10005', 'Email Narahubung (Opsional)')" :error="form.errors.contact_email" v-slot="{ inputId, describedBy, invalid }">
                                <input :id="inputId" v-model="form.contact_email" type="email" :placeholder="publicText('copy.vue_services_externalletter', 'placeholder_10006', 'persuratan@instansi.go.id')" :aria-describedby="describedBy" :aria-invalid="invalid" />
                            </FormField>
                        </div>
                    </section>

                    <section class="portal-card bento-card bento-card--letter" aria-labelledby="external-letter-spec-title">
                        <div class="bento-card-header">
                            <div class="bento-icon bento-icon--amber">
                                <i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h2 id="external-letter-spec-title" class="service-form-title">{{ publicText("copy.vue_services_externalletter", "text_026", "2. Spesifikasi Surat Dinas") }}</h2>
                                <p class="bento-subtitle">{{ publicText("copy.vue_services_externalletter", "text_027", "Nomor, perihal, dan derajat kepentingan surat") }}</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="full-span">
                                <FormField id="ext-letter-number" :label="publicText('copy.vue_services_externalletter', 'label_10007', 'Nomor Surat Dinas')" required :error="form.errors.letter_number" v-slot="{ inputId, describedBy, invalid }">
                                    <input :id="inputId" v-model="form.letter_number" type="text" required :placeholder="publicText('copy.vue_services_externalletter', 'placeholder_046', 'Contoh: 421/104/DISDIK/2026')" :aria-describedby="describedBy" :aria-invalid="invalid" />
                                </FormField>
                            </div>

                            <FormField id="ext-letter-date" :label="publicText('copy.vue_services_externalletter', 'label_10008', 'Tanggal Surat')" required :error="form.errors.letter_date" v-slot="{ inputId, describedBy, invalid }">
                                <input :id="inputId" v-model="form.letter_date" type="date" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                            </FormField>

                            <FormField id="ext-urgency" :label="publicText('copy.vue_services_externalletter', 'label_10009', 'Sifat / Urgensi Surat')" required :error="form.errors.urgency" v-slot="{ inputId, describedBy, invalid }">
                                <select :id="inputId" v-model="form.urgency" :aria-describedby="describedBy" :aria-invalid="invalid">
                                    <option v-for="opt in urgencyOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                            </FormField>

                            <FormField id="ext-classification" :label="publicText('copy.vue_services_externalletter', 'label_10010', 'Klasifikasi Surat')" :error="form.errors.classification" v-slot="{ inputId, describedBy, invalid }">
                                <input :id="inputId" v-model="form.classification" type="text" :aria-describedby="describedBy" :aria-invalid="invalid" />
                            </FormField>

                            <FormField id="ext-secret" :label="publicText('copy.vue_services_externalletter', 'label_10011', 'PIN Verifikasi Tanda Terima (Opsional)')" :hint="publicText('copy.vue_services_externalletter', 'hint_10012', 'Kosongkan untuk PIN otomatis 6 digit.')" :error="form.errors.verification_secret" v-slot="{ inputId, describedBy, invalid }">
                                <input :id="inputId" v-model="form.verification_secret" type="text" minlength="4" maxlength="24" :placeholder="publicText('copy.vue_services_externalletter', 'placeholder_047', '4–24 karakter')" :aria-describedby="describedBy" :aria-invalid="invalid" />
                            </FormField>

                            <div class="full-span">
                                <FormField id="ext-subject" :label="publicText('copy.vue_services_externalletter', 'label_10013', 'Perihal Surat')" required :error="form.errors.subject" v-slot="{ inputId, describedBy, invalid }">
                                    <input :id="inputId" v-model="form.subject" type="text" required :placeholder="publicText('copy.vue_services_externalletter', 'placeholder_048', 'Contoh: Undangan Rapat Koordinasi / Permohonan Narasumber')" :aria-describedby="describedBy" :aria-invalid="invalid" />
                                </FormField>
                            </div>

                            <div class="full-span">
                                <FormField id="ext-summary" :label="publicText('copy.vue_services_externalletter', 'label_10014', 'Ringkasan Isi Surat')" required :error="form.errors.summary" v-slot="{ inputId, describedBy, invalid }">
                                    <textarea :id="inputId" v-model="form.summary" rows="3" required :placeholder="publicText('copy.vue_services_externalletter', 'placeholder_049', 'Tuliskan intisari isi surat atau jadwal pelaksanaan kegiatan...')" :aria-describedby="describedBy" :aria-invalid="invalid" />
                                </FormField>
                            </div>
                        </div>
                    </section>

                    <section class="portal-card bento-card bento-card--files full-span" aria-labelledby="external-letter-files-title">
                        <div class="bento-card-header bento-card-header--split">
                            <div class="bento-card-title-group">
                                <div class="bento-icon bento-icon--blue">
                                    <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <h2 id="external-letter-files-title" class="service-form-title">{{ publicText("copy.vue_services_externalletter", "text_028", "3. Berkas Dokumen Resmi") }}</h2>
                                    <p class="bento-subtitle">{{ publicText("copy.vue_services_externalletter", "text_029", "Unggah file pindaian resmi (Scan) bertanda tangan & stempel sah") }}</p>
                                </div>
                            </div>
                            <span class="security-pill">
                                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> {{ publicText("copy.vue_services_externalletter", "text_030", "Enkripsi Data Aman") }}
                            </span>
                        </div>

                        <div class="dropzone-grid">
                            <div class="dropzone-box dropzone-box--primary">
                                <div class="dropzone-icon dropzone-icon--pdf">
                                    <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                                </div>
                                <FormField id="ext-primary-pdf" class="dropzone-field dropzone-field--primary" :label="publicText('copy.vue_services_externalletter', 'label_10015', 'Unggah Berkas Surat Utama (PDF)')" :hint="publicText('copy.vue_services_externalletter', 'hint_10016', 'Wajib PDF pindaian resmi bertanda tangan & stempel basah (Maks. 5 MB)')" required :error="form.errors.primary_pdf" v-slot="{ inputId, describedBy, invalid }">
                                    <input :id="inputId" type="file" accept=".pdf" required :aria-describedby="describedBy" :aria-invalid="invalid" @change="onPrimaryPdfChange" />
                                </FormField>
                                <p v-if="form.primary_pdf" class="file-preview-pill file-preview-pill--emerald">
                                    <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                                    <span>{{ publicText("copy.vue_services_externalletter", "text_9001", "Terpilih:") }} {{ form.primary_pdf.name }}</span>
                                </p>
                            </div>

                            <div class="dropzone-box dropzone-box--secondary">
                                <div class="dropzone-icon dropzone-icon--attach">
                                    <i class="fa-solid fa-paperclip" aria-hidden="true"></i>
                                </div>
                                <FormField id="ext-supporting" class="dropzone-field dropzone-field--secondary" :label="publicText('copy.vue_services_externalletter', 'label_10017', 'Lampiran Pendukung (Opsional)')" :hint="publicText('copy.vue_services_externalletter', 'hint_10018', 'Berkas pelengkap maks. 5 berkas (PDF, JPG, PNG, WEBP)')" :error="form.errors.supporting_files" v-slot="{ inputId, describedBy, invalid }">
                                    <input :id="inputId" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" :aria-describedby="describedBy" :aria-invalid="invalid" @change="onSupportingChange" />
                                </FormField>
                                <p v-if="form.supporting_files.length > 0" class="file-preview-pill">
                                    <i class="fa-solid fa-paperclip" aria-hidden="true"></i>
                                    <span>{{ publicText("copy.vue_services_externalletter", "text_9002", "Terpilih (") }}{{ form.supporting_files.length }}): {{ form.supporting_files.map((f) => f.name).join(', ') }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="statement-box">
                            <label class="checkbox-label">
                                <input v-model="form.statement_accepted" type="checkbox" />
                                <span><strong>{{ publicText("copy.vue_services_externalletter", "text_031", "Pernyataan Keabsahan:") }}</strong> {{ publicText("copy.vue_services_externalletter", "text_9003", "Saya menyatakan surat dinas beserta lampiran yang dikirimkan resmi dan sah dari instansi pengirim, serta bersedia dikonfirmasi oleh Tata Usaha") }} {{ school.name }}.</span>
                            </label>
                            <p v-if="form.errors.statement_accepted" class="field-error" role="alert">{{ form.errors.statement_accepted }}</p>
                        </div>

                        <div class="submit-footer-row">
                            <p class="submit-note">
                                <i class="fa-solid fa-certificate" aria-hidden="true"></i>
                                <span>{{ publicText("copy.vue_services_externalletter", "text_032", "Resi digital bertanda terima resmi terbit seketika setelah surat dikirim.") }}</span>
                            </p>
                            <button type="submit" class="submit-btn-gradient" :disabled="form.processing">
                                <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                                <span>{{ publicText("copy.vue_services_externalletter", "text_033", "Kirim Surat & Terbitkan Tanda Terima") }}</span>
                            </button>
                        </div>
                    </section>
                </form>
            </div>
        </section>

        <footer class="surat-footer">
            <div class="surat-footer-inner">
                <div class="surat-footer-brand">
                    <img v-if="school.logo_url" :src="school.logo_url" :alt="school.name" class="surat-footer-logo" />
                    <div>
                        <p class="surat-footer-title">{{ publicText("copy.vue_services_externalletter", "text_9004", "© 2026 Tata Usaha") }} {{ school.name }}</p>
                        <p class="surat-footer-sub">{{ publicText("copy.vue_services_externalletter", "text_034", "Sistem Informasi Akademik & Persuratan Terintegrasi") }}</p>
                    </div>
                </div>
                <nav class="surat-footer-links" :aria-label="publicText('copy.vue_services_externalletter', 'aria_label_050', 'Tautan cepat persuratan')">
                    <Link href="/">{{ publicText("copy.vue_services_externalletter", "text_035", "Beranda") }}</Link>
                    <Link href="/layanan/ptsp">{{ publicText("copy.vue_services_externalletter", "text_036", "Layanan PTSP") }}</Link>
                    <Link href="/login" class="surat-footer-login">{{ publicText("copy.vue_services_externalletter", "text_037", "Login Pegawai") }}</Link>
                </nav>
            </div>
        </footer>
    </main>
</template>

<style scoped>
.public-portal-page { min-height: 100vh; background: #f8fafc; color: #1e293b; }

.parity-letter-hero {
    position: relative;
    overflow: hidden;
    padding: 2.5rem 1rem 4rem;
    border-radius: 0;
    background-color: #064e3b;
    background-image:
        radial-gradient(at 10% 20%, rgba(16, 185, 129, 0.28) 0px, transparent 50%),
        radial-gradient(at 90% 10%, rgba(234, 179, 8, 0.22) 0px, transparent 50%),
        radial-gradient(at 50% 80%, rgba(4, 120, 87, 0.4) 0px, transparent 60%);
    color: #fff;
}

.hero-dot-grid {
    position: absolute;
    inset: 0;
    opacity: .1;
    background-image: radial-gradient(#fde047 1px, transparent 1px);
    background-size: 20px 20px;
    pointer-events: none;
}

.hero-inner {
    position: relative;
    z-index: 1;
    width: min(1280px, 100%);
    margin-inline: auto;
    padding-inline: .5rem;
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 2rem;
    align-items: center;
}

@media (min-width: 1024px) {
    .hero-inner {
        grid-template-columns: minmax(0, 7fr) minmax(0, 5fr);
    }
}

.parity-letter-copy {
    display: grid;
    gap: 1rem;
    text-align: left;
}

.surat-service-label {
    width: fit-content;
    display: inline-flex;
    align-items: center;
    gap: .625rem;
    margin: 0;
    padding: .375rem .875rem;
    border-radius: 999px;
    background: rgba(2, 44, 34, 0.7);
    border: 1px solid rgba(251, 191, 36, 0.4);
    color: #fcd34d;
    font-size: .75rem;
    font-weight: 700;
    backdrop-filter: blur(12px);
}

.surat-service-label .label-sub {
    color: #a7f3d0;
    font-weight: 500;
}

.pulse-dot {
    width: .5rem;
    height: .5rem;
    border-radius: 999px;
    background: #34d399;
    box-shadow: 0 0 0 4px rgba(52, 211, 153, 0.22);
}

.parity-letter-copy h1 {
    margin: 0;
    color: #fff;
    font-size: clamp(1.875rem, 4vw, 3rem);
    font-weight: 900;
    line-height: 1.15;
    letter-spacing: -.025em;
}

.hero-title-highlight {
    display: block;
    background: linear-gradient(90deg, #fcd34d, #a7f3d0 55%, #ccfbf1);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.hero-lead {
    margin: 0;
    color: rgba(209, 250, 229, 0.9);
    max-width: 42rem;
    font-size: .95rem;
    line-height: 1.625;
}

.workflow-steps {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .625rem;
    padding-top: .5rem;
}

@media (min-width: 640px) {
    .workflow-steps {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

.workflow-step {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: .75rem;
    padding: .625rem;
}

.workflow-step-label {
    display: block;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    color: #fcd34d;
}

.workflow-step p {
    margin: .125rem 0 0;
    color: #fff;
    font-size: .75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: .35rem;
}

.step-icon--amber { color: #fbbf24; }
.step-icon--emerald { color: #6ee7b7; }
.step-icon--blue { color: #93c5fd; }

.hero-links {
    display: flex;
    flex-wrap: wrap;
    gap: .625rem;
    padding-top: .75rem;
}

.hero-btn {
    display: inline-flex;
    align-items: center;
    gap: .625rem;
    padding: .75rem 1.25rem;
    border-radius: .75rem;
    font-size: .875rem;
    font-weight: 800;
    text-decoration: none;
    transition: transform .15s ease, background-color .15s ease;
}

.hero-btn--primary {
    background: #fff;
    color: #064e3b;
    border: 2px solid #fff;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
}

.hero-btn--primary i { color: #059669; }
.hero-btn--primary:hover { transform: scale(1.02); }

.hero-btn--secondary {
    background: rgba(2, 44, 34, 0.7);
    color: #a7f3d0;
    border: 2px solid rgba(5, 150, 105, 0.4);
    backdrop-filter: blur(12px);
}

.hero-btn--secondary i { color: #fcd34d; }
.hero-btn--secondary:hover { background: #064e3b; color: #fff; }

.glass-sidebar-card {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.16), rgba(255, 255, 255, 0.06));
    backdrop-filter: blur(18px);
    border: 1px solid rgba(255, 255, 255, 0.22);
    border-radius: 1.5rem;
    padding: 1.4rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
    display: grid;
    gap: 1rem;
}

.sidebar-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding-bottom: .75rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.16);
}

.sidebar-card-title-wrap {
    display: flex;
    align-items: center;
    gap: .5rem;
}

.sidebar-shield-icon { color: #fde047; font-size: 1.05rem; }

.sidebar-title {
    margin: 0;
    font-size: .92rem;
    font-weight: 800;
    color: #fff;
}

.sidebar-badge {
    font-size: .66rem;
    font-weight: 800;
    padding: .2rem .55rem;
    border-radius: 999px;
    background: rgba(16, 185, 129, 0.22);
    color: #a7f3d0;
    border: 1px solid rgba(52, 211, 153, 0.35);
}

.sidebar-list {
    margin: 0;
    padding: 0;
    list-style: none;
    display: grid;
    gap: .75rem;
}

.sidebar-list li {
    display: flex;
    align-items: flex-start;
    gap: .7rem;
    font-size: .78rem;
    line-height: 1.45;
}

.sidebar-list strong {
    display: block;
    color: #fff;
    font-weight: 700;
}

.sidebar-list span:not(.sidebar-icon) {
    color: rgba(209, 250, 229, 0.85);
}

.sidebar-icon {
    width: 1.85rem;
    height: 1.85rem;
    border-radius: .55rem;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    margin-top: .1rem;
}

.sidebar-icon--emerald { background: rgba(16, 185, 129, 0.22); color: #6ee7b7; }
.sidebar-icon--amber { background: rgba(245, 158, 11, 0.22); color: #fcd34d; }
.sidebar-icon--blue { background: rgba(59, 130, 246, 0.22); color: #93c5fd; }

.quick-track-box {
    background: rgba(0, 0, 0, 0.26);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 1rem;
    padding: .85rem;
    display: grid;
    gap: .45rem;
}

.quick-track-label {
    font-size: .72rem;
    font-weight: 700;
    color: #fde047;
    display: flex;
    align-items: center;
    gap: .4rem;
}

.quick-track-row {
    display: flex;
    gap: .45rem;
}

.quick-track-row input {
    flex: 1;
    min-width: 0;
    padding: .55rem .8rem;
    border-radius: .7rem;
    border: 0;
    background: rgba(255, 255, 255, 0.94);
    color: #0f172a;
    font-size: .78rem;
}

.quick-track-row button {
    padding: .55rem .9rem;
    border-radius: .7rem;
    border: 0;
    background: #fbbf24;
    color: #0f172a;
    font-weight: 800;
    cursor: pointer;
    flex-shrink: 0;
}

.quick-track-row button:hover { background: #fcd34d; }
.quick-track-error { margin: 0; font-size: .72rem; color: #fecaca; font-weight: 600; }

.sidebar-footer-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    font-size: .74rem;
    color: rgba(209, 250, 229, 0.9);
}

.sidebar-contact-link {
    padding: .4rem .75rem;
    border-radius: .7rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(252, 211, 77, 0.45);
    color: #fde047;
    font-weight: 700;
    text-decoration: none;
}

.sidebar-contact-link:hover { background: rgba(255, 255, 255, 0.18); }

.parity-letter-main {
    width: min(1240px, calc(100% - 2rem));
    margin: -2rem auto 0;
    padding: 0 0 4rem;
    position: relative;
    z-index: 20;
}

.bento-form-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 1.25rem;
    align-items: start;
}

@media (min-width: 960px) {
    .bento-form-grid {
        grid-template-columns: repeat(12, minmax(0, 1fr));
    }
    .bento-card--agency { grid-column: span 5; }
    .bento-card--letter { grid-column: span 7; }
    .bento-card--files { grid-column: span 12; }
}

.portal-card.bento-card {
    background: rgba(255, 255, 255, 0.98);
    border: 1px solid rgba(226, 232, 240, 0.95);
    border-radius: 1.5rem;
    padding: 1.5rem 1.65rem;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04), 0 20px 25px -5px rgba(6, 78, 59, 0.05);
    display: grid;
    gap: 1.25rem;
}

.bento-card-header {
    display: flex;
    align-items: center;
    gap: .85rem;
    padding-bottom: .9rem;
    border-bottom: 1px solid #f1f5f9;
}

.bento-card-header--split {
    flex-wrap: wrap;
    justify-content: space-between;
}

.bento-card-title-group {
    display: flex;
    align-items: center;
    gap: .85rem;
}

.bento-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 1rem;
    display: grid;
    place-items: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.bento-icon--emerald { background: #ecfdf5; color: #047857; }
.bento-icon--amber { background: #fffbeb; color: #b45309; }
.bento-icon--blue { background: #eff6ff; color: #1d4ed8; }

.bento-subtitle {
    margin: .15rem 0 0;
    font-size: .76rem;
    color: #64748b;
}

.security-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .3rem .75rem;
    border-radius: 999px;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #047857;
    font-size: .74rem;
    font-weight: 700;
}

.agency-fields {
    display: grid;
    gap: .95rem;
}

.bento-card :deep(.ss-form-field__label) {
    font-size: .75rem;
    font-weight: 700;
    color: #334155;
}

.bento-card :deep(.ss-form-field__control input:not([type='file']):not([type='checkbox'])),
.bento-card :deep(.ss-form-field__control select),
.bento-card :deep(.ss-form-field__control textarea) {
    border-radius: .75rem;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    padding: .625rem .875rem;
    font-size: .875rem;
    color: #0f172a;
    transition: all .2s cubic-bezier(0.4, 0, 0.2, 1);
}

.bento-card :deep(.ss-form-field__control input:not([type='file']):not([type='checkbox']):focus),
.bento-card :deep(.ss-form-field__control select:focus),
.bento-card :deep(.ss-form-field__control textarea:focus) {
    border-color: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
    background-color: #ffffff;
    outline: none;
}

.dropzone-field {
    width: 100%;
    justify-items: center;
    text-align: center;
}

.dropzone-field :deep(.ss-form-field__label) {
    justify-content: center;
    font-size: .875rem;
    font-weight: 800;
    color: #0f172a;
}

.dropzone-field :deep(.ss-form-field__hint) {
    font-size: 11px;
    color: #64748b;
}

.dropzone-field :deep(input[type='file']) {
    border: 0 !important;
    background: transparent !important;
    padding: .25rem 0 !important;
    min-height: auto !important;
    font-size: .75rem !important;
    color: #475569 !important;
    text-align: center;
    cursor: pointer;
}

.dropzone-field--primary :deep(input[type='file']::file-selector-button) {
    margin-right: .75rem;
    padding: .5rem 1rem;
    border-radius: .75rem;
    border: 0;
    background: #059669;
    color: #fff;
    font-size: .75rem;
    font-weight: 700;
    cursor: pointer;
}

.dropzone-field--primary :deep(input[type='file']::file-selector-button:hover) {
    background: #047857;
}

.dropzone-field--secondary :deep(input[type='file']::file-selector-button) {
    margin-right: .75rem;
    padding: .5rem 1rem;
    border-radius: .75rem;
    border: 0;
    background: #334155;
    color: #fff;
    font-size: .75rem;
    font-weight: 700;
    cursor: pointer;
}

.dropzone-field--secondary :deep(input[type='file']::file-selector-button:hover) {
    background: #1e293b;
}

.surat-footer {
    background: #0f172a;
    color: #94a3b8;
    padding: 2rem 1rem;
    font-size: .75rem;
    border-top: 1px solid #1e293b;
}

.surat-footer-inner {
    width: min(1280px, 100%);
    margin-inline: auto;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.surat-footer-brand {
    display: flex;
    align-items: center;
    gap: .75rem;
}

.surat-footer-logo {
    width: 2rem;
    height: 2rem;
    object-fit: contain;
}

.surat-footer-title {
    margin: 0;
    font-weight: 700;
    color: #e2e8f0;
}

.surat-footer-sub {
    margin: .1rem 0 0;
    font-size: 11px;
    color: #64748b;
}

.surat-footer-links {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.surat-footer-links a {
    color: #94a3b8;
    text-decoration: none;
    transition: color .15s ease;
}

.surat-footer-links a:hover { color: #34d399; }
.surat-footer-links a.surat-footer-login:hover { color: #fbbf24; }

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
}

.full-span { grid-column: 1 / -1; }

.dropzone-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 1.15rem;
}

@media (min-width: 768px) {
    .dropzone-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

.dropzone-box {
    border-radius: 1rem;
    padding: 1.35rem;
    border: 2px dashed #cbd5e1;
    background: #f8fafc;
    display: grid;
    gap: .75rem;
    justify-items: center;
    text-align: center;
}

.dropzone-box--primary {
    border-color: rgba(52, 211, 153, 0.85);
    background: rgba(236, 253, 245, 0.45);
}

.dropzone-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 1rem;
    background: #fff;
    display: grid;
    place-items: center;
    font-size: 1.25rem;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.06);
}

.dropzone-icon--pdf { color: #e11d48; }
.dropzone-icon--attach { color: #64748b; }

.file-preview-pill {
    margin: 0;
    padding: .35rem .75rem;
    border-radius: .6rem;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid #e2e8f0;
    color: #334155;
    font-size: .76rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    max-width: 100%;
    word-break: break-word;
}

.file-preview-pill--emerald {
    border-color: #a7f3d0;
    color: #065f46;
}

.statement-box {
    background: rgba(255, 251, 235, 0.75);
    border: 1px solid rgba(253, 230, 138, 0.9);
    border-radius: 1rem;
    padding: 1rem 1.1rem;
}

.checkbox-label { display: flex; align-items: flex-start; gap: .65rem; font-size: .85rem; line-height: 1.5; color: #334155; cursor: pointer; }
.checkbox-label input { width: 18px; height: 18px; margin-top: .15rem; flex-shrink: 0; }
.field-error { margin: .35rem 0 0; color: #b13f38; font-size: .8rem; font-weight: 600; }

.submit-footer-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding-top: .35rem;
}

.submit-note {
    margin: 0;
    font-size: .8rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: .45rem;
}

.submit-note i { color: #059669; }

.submit-btn-gradient {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .6rem;
    padding: .9rem 1.65rem;
    border: 0;
    border-radius: 1rem;
    background: linear-gradient(90deg, #059669, #047857, #064e3b);
    color: #fff;
    font-size: .9rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 14px 26px -10px rgba(6, 78, 59, 0.45);
    transition: transform .15s ease, opacity .15s ease;
}

.submit-btn-gradient i { color: #fde047; }
.submit-btn-gradient:hover:not(:disabled) { transform: translateY(-1px); }
.submit-btn-gradient:disabled { opacity: .65; cursor: not-allowed; }
</style>
