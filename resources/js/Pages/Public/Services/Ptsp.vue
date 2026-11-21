<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

interface RequiredDocRule {
    key: string;
    label: string;
    required: boolean;
}

interface PtspCatalogItem {
    id: number;
    code: string;
    name: string;
    category: string;
    description?: string | null;
    requirements: string[];
    required_documents: RequiredDocRule[];
    estimated_duration: string;
    service_hours?: string | null;
    delivery_mode: string;
    requires_verification_secret: boolean;
    is_active: boolean;
}

interface PtspRequestItem {
    id: number;
    ptsp_service_id: number;
    ticket_code?: string | null;
    service_snapshot: {
        name?: string;
        code?: string;
        estimated_duration?: string;
        requirements?: string[];
        required_documents?: RequiredDocRule[];
    };
    applicant_type: string;
    applicant_name: string;
    applicant_phone?: string | null;
    applicant_email?: string | null;
    applicant_organization?: string | null;
    purpose: string;
    notes?: string | null;
    attachment_document_ids: { requirement_key: string; document_id: number; original_name: string }[];
    status: string;
    public_summary?: string | null;
    revision_note?: string | null;
    updated_at?: string | null;
}

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    catalog: PtspCatalogItem[];
    categories: { value: string; label: string }[];
    applicant: {
        id: number;
        applicant_type: string;
        full_name: string;
        phone?: string | null;
        email?: string | null;
        organization_name?: string | null;
    } | null;
    authUser: { name: string; email?: string | null; phone?: string | null; role?: string | null } | null;
    myRequests: PtspRequestItem[];
    activeDraft: PtspRequestItem | null;
    selectedServiceId: number | null;
    applicantTypes: { value: string; label: string }[];
}>();

const page = usePage();
const flashMessage = computed(() => (page.props.flash as { message?: string } | undefined)?.message);
const themeStyle = computed(() => publicThemeStyle(props.school.theme));

const selectedCategory = ref('all');
const showReviewStep = ref(false);
const showCatalog = ref(props.catalog.length > 0 || Boolean(props.activeDraft) || props.myRequests.length > 0);
const fileInputs = ref<Record<string, File | null>>({});

const filteredCatalog = computed(() => {
    if (selectedCategory.value === 'all') return props.catalog;
    return props.catalog.filter((item) => item.category === selectedCategory.value);
});

const defaultServiceId = computed(() => {
    if (props.activeDraft) return props.activeDraft.ptsp_service_id;
    if (props.selectedServiceId) return props.selectedServiceId;
    return props.catalog.find((item) => item.is_active)?.id ?? props.catalog[0]?.id ?? null;
});

const form = useForm({
    request_id: props.activeDraft?.id ?? (null as number | null),
    ptsp_service_id: defaultServiceId.value as number | null,
    applicant_type: props.activeDraft?.applicant_type ?? props.applicant?.applicant_type ?? (props.authUser ? 'student' : 'general'),
    applicant_name: props.activeDraft?.applicant_name ?? props.applicant?.full_name ?? props.authUser?.name ?? '',
    applicant_phone: props.activeDraft?.applicant_phone ?? props.applicant?.phone ?? props.authUser?.phone ?? '',
    applicant_email: props.activeDraft?.applicant_email ?? props.applicant?.email ?? props.authUser?.email ?? '',
    applicant_organization: props.activeDraft?.applicant_organization ?? props.applicant?.organization_name ?? '',
    purpose: props.activeDraft?.purpose ?? '',
    notes: props.activeDraft?.notes ?? '',
    verification_secret: '',
    agreement_accepted: false,
    idempotency_key: `ptsp-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
});

const selectedService = computed(() =>
    props.catalog.find((item) => item.id === form.ptsp_service_id) ?? null,
);

const currentRequiredDocs = computed<RequiredDocRule[]>(() => {
    if (props.activeDraft && props.activeDraft.ptsp_service_id === form.ptsp_service_id) {
        return props.activeDraft.service_snapshot.required_documents ?? [];
    }
    return selectedService.value?.required_documents ?? [];
});

const currentRequirements = computed<string[]>(() => {
    if (props.activeDraft && props.activeDraft.ptsp_service_id === form.ptsp_service_id) {
        return props.activeDraft.service_snapshot.requirements ?? [];
    }
    return selectedService.value?.requirements ?? [];
});

const draftUploadedMap = computed(() => {
    const map: Record<string, { document_id: number; original_name: string }> = {};
    for (const item of props.activeDraft?.attachment_document_ids ?? []) {
        map[item.requirement_key] = item;
    }
    return map;
});

watch(() => props.activeDraft, (draft) => {
    if (!draft) return;
    form.request_id = draft.id;
    form.ptsp_service_id = draft.ptsp_service_id;
    form.applicant_type = draft.applicant_type;
    form.applicant_name = draft.applicant_name;
    form.applicant_phone = draft.applicant_phone ?? '';
    form.applicant_email = draft.applicant_email ?? '';
    form.applicant_organization = draft.applicant_organization ?? '';
    form.purpose = draft.purpose;
    form.notes = draft.notes ?? '';
});

const selectService = (item: PtspCatalogItem) => {
    if (!item.is_active) return;
    form.ptsp_service_id = item.id;
    showReviewStep.value = false;
};

const onFileChange = (key: string, event: Event) => {
    const input = event.target as HTMLInputElement;
    fileInputs.value[key] = input.files?.[0] ?? null;
};

const attachmentError = (key: string): string | undefined => {
    const errors = form.errors as Record<string, string | undefined>;
    return errors[`attachments.${key}`] || errors.attachments;
};

const buildFormDataPayload = () => {
    const attachments: Record<string, File> = {};
    for (const [k, file] of Object.entries(fileInputs.value)) {
        if (file) attachments[k] = file;
    }
    return {
        ...form.data(),
        attachments,
    };
};

const saveDraft = () => {
    form.transform(() => buildFormDataPayload()).post('/layanan/ptsp/draf', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            fileInputs.value = {};
        },
    });
};

const submitFinal = () => {
    form.transform(() => buildFormDataPayload()).post('/layanan/ptsp/ajukan', {
        forceFormData: true,
        preserveScroll: true,
    });
};

const quickLoginForm = useForm({
    email: '',
    password: '',
});

const submitQuickLogin = () => {
    quickLoginForm.post('/layanan/ptsp/akun/masuk', {
        onFinish: () => quickLoginForm.reset('password'),
    });
};

const removeDraftAttachment = (requestId: number, documentId: number) => {
    router.delete(`/layanan/ptsp/draf/${requestId}/lampiran/${documentId}`, { preserveScroll: true });
};
</script>

<template>
    <Head :title="`PTSP Online — ${school.name}`" />
    <main class="public-site public-portal-page" data-madrasah-service="ptsp" :style="themeStyle">
        <PublicHeader :school="school" />

        <section class="portal-container">
            <header class="portal-hero madrasah-service-hero parity-ptsp-hero">
                <div>
                    <p class="madrasah-service-label"><i class="fa-solid fa-building-columns" aria-hidden="true"></i> Pelayanan Terpadu Satu Pintu (PTSP)</p>
                    <img class="parity-ptsp-logo" :src="school.logo_url || '/madrasah-theme/images/logo-kemenag.png'" alt="" />
                    <h1>Layanan Online Terintegrasi<br />{{ school.name }}<br /><span>(PTSP Online)</span></h1>
                    <p>Layanan Permohonan, Informasi, Konsultasi, Pengaduan dan Legalisir.</p>
                </div>
                <div class="hero-actions">
                    <a :href="selectedService ? '#form-ptsp' : '#katalog-ptsp'" class="btn-ajukan-hero parity-ptsp-btn-apply" @click="showCatalog = true">
                        AJUKAN PERMOHONAN <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
                    </a>

                    <Link href="/lacak?category=ptsp" class="btn-cek-hero parity-ptsp-btn-check">
                        Cek Permohonan <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    </Link>
                </div>
            </header>

            <div v-if="flashMessage" class="alert-banner parity-ptsp-container mt-4" role="status">{{ flashMessage }}</div>

            <section id="tentang-ptsp" class="parity-ptsp-about-band" aria-labelledby="ptsp-about-title">
                <div class="parity-ptsp-container parity-ptsp-about">
                    <div class="parity-ptsp-about__media">
                        <img src="/madrasah-theme/images/logo-cek.png" alt="PTSP Online" />
                    </div>
                    <div class="parity-ptsp-about__copy">
                        <span class="parity-ptsp-pill"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Pelayanan Publik Digital Resmi</span>
                        <h2 id="ptsp-about-title">PTSP Online</h2>
                        <p>
                            <em>Pelayanan Terpadu dalam Satu Pintu Online (PTSP Online)</em> adalah aplikasi website resmi yang dibangun untuk memberikan pelayanan administrasi permohonan surat dan perizinan kepada masyarakat, siswa, alumni, dan orang tua wali secara terpadu dan efisien.
                            <a href="#katalog-ptsp" class="parity-ptsp-inline-cta" @click="showCatalog = true">Buka Layanan <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                        </p>
                        <ul class="parity-ptsp-checklist">
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Proses Aman &amp; Tervalidasi</li>
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Respon Cepat Terjadwal</li>
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Alur Mudah &amp; Transparan</li>
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Hemat Waktu Tanpa Antre Fisik</li>
                        </ul>
                    </div>
                </div>
            </section>

            <section class="parity-ptsp-benefits-band" aria-labelledby="ptsp-benefits-title">
                <div class="parity-ptsp-container">
                    <div class="parity-ptsp-benefits__head">
                        <span class="parity-ptsp-pill">KEUNGGULAN SISTEM</span>
                        <h2 id="ptsp-benefits-title">Keunggulan Layanan PTSP Online</h2>
                        <p class="parity-ptsp-benefits__desc">Pelayanan Terpadu dalam Satu Pintu Online memberikan kemudahan administrasi bagi guru, siswa, alumni, dan masyarakat umum dalam pengajuan permohonan berbagai macam kebutuhan sekolah dalam satu portal resmi.</p>
                    </div>
                    <div class="parity-ptsp-benefit-grid">
                        <article class="parity-feature-box">
                            <span class="parity-feature-box__icon"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></span>
                            <div>
                                <h3>Hemat Waktu</h3>
                                <p>Sistem online memudahkan pengurusan perizinan lebih cepat dan menghemat waktu tanpa perlu datang antre langsung.</p>
                            </div>
                        </article>
                        <article class="parity-feature-box">
                            <span class="parity-feature-box__icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span>
                            <div>
                                <h3>Proses Aman</h3>
                                <p>Sistem PTSP ONLINE menerapkan nomor resi dan verifikasi berkas berstandar digital guna menjamin keaslian dokumen.</p>
                            </div>
                        </article>
                        <article class="parity-feature-box">
                            <span class="parity-feature-box__icon"><i class="fa-solid fa-envelope-circle-check" aria-hidden="true"></i></span>
                            <div>
                                <h3>Respon Cepat</h3>
                                <p>Petugas langsung memproses dan memvalidasi berkas yang masuk dengan notifikasi status berkala.</p>
                            </div>
                        </article>
                        <article class="parity-feature-box">
                            <span class="parity-feature-box__icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
                            <div>
                                <h3>Mudah &amp; Transparan</h3>
                                <p>Kemudahan menyeluruh dari pendaftaran, pengunggahan berkas syarat, pemantauan resi, hingga unduh dokumen jadi.</p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="parity-ptsp-procedure-band" aria-labelledby="ptsp-procedure">
                <div class="parity-ptsp-container">
                    <div class="parity-ptsp-procedure__head">
                        <span class="parity-ptsp-pill">PANDUAN ALUR</span>
                        <h2 id="ptsp-procedure">Prosedur Pengajuan PTSP Online</h2>
                        <p>Ikuti tata cara pengajuan berikut untuk memastikan berkas Anda diproses dengan lancar.</p>
                    </div>
                    <div class="parity-ptsp-procedure__grid">
                        <ol class="parity-ptsp-steps">
                            <li><span>1</span><div><strong>Pendaftaran Akun</strong><p>Lakukan pendaftaran untuk mendapatkan akun pemohon dengan mengisi formulir identitas NIK &amp; No. WhatsApp aktif.</p></div></li>
                            <li><span>2</span><div><strong>Login Pemohon</strong><p>Setelah terdaftar, masuk ke aplikasi menggunakan NIK dan No. WhatsApp/Password yang telah Anda daftarkan.</p></div></li>
                            <li><span>3</span><div><strong>Pilih Layanan &amp; Unggah Berkas</strong><p>Pilih jenis surat/layanan yang Anda butuhkan dan unggah dokumen persyaratan yang diminta (PDF/JPG/ZIP).</p></div></li>
                            <li><span>4</span><div><strong>Verifikasi Petugas</strong><p>Petugas PTSP madrasah akan mengecek keabsahan syarat administrasi dan kelengkapan dokumen permohonan Anda.</p></div></li>
                            <li><span>5</span><div><strong>Cek Status Resi</strong><p>Simpan nomor resi yang terbit. Buka menu Cek Permohonan untuk memantau apakah permohonan sedang diproses atau butuh revisi.</p></div></li>
                            <li><span>6</span><div><strong>Penyelesaian Dokumen</strong><p>Setelah ditandatangani pejabat berwenang, status tiket berubah menjadi Selesai.</p></div></li>
                            <li><span>7</span><div><strong>Unduh / Pengambilan Surat</strong><p>Unduh file hasil surat jadi langsung dari aplikasi atau ambil berkas fisik di loket PTSP madrasah.</p></div></li>
                        </ol>
                        <aside class="parity-ptsp-login-box">
                            <div class="parity-ptsp-login-box__head">
                                <span class="parity-ptsp-login-box__icon"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i></span>
                                <h3>LOGIN PTSP ONLINE</h3>
                                <p>Masuk untuk mengajukan atau memantau permohonan Anda</p>
                            </div>
                            <form v-if="!applicant" class="parity-ptsp-login-form" autocomplete="off" @submit.prevent="submitQuickLogin">
                                <div class="parity-ptsp-login-field">
                                    <label for="ptsp_home_login_email">Email / NIK</label>
                                    <input id="ptsp_home_login_email" v-model="quickLoginForm.email" name="ptsp_home_login_nik" type="text" autocomplete="off" placeholder="Masukkan Email atau NIK" required />
                                    <p v-if="quickLoginForm.errors.email" class="mt-1 text-xs font-semibold text-rose-600" role="alert">{{ quickLoginForm.errors.email }}</p>
                                </div>
                                <div class="parity-ptsp-login-field">
                                    <label for="ptsp_home_login_pass">Password / No. HP</label>
                                    <input id="ptsp_home_login_pass" v-model="quickLoginForm.password" name="ptsp_home_login_pass" type="password" autocomplete="new-password" placeholder="Masukkan Password atau No. HP" required />
                                    <p v-if="quickLoginForm.errors.password" class="mt-1 text-xs font-semibold text-rose-600" role="alert">{{ quickLoginForm.errors.password }}</p>
                                </div>
                                <div class="parity-ptsp-login-box__actions">
                                    <button type="submit" class="parity-ptsp-login-submit" :disabled="quickLoginForm.processing">
                                        <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Masuk Aplikasi
                                    </button>
                                    <Link href="/layanan/ptsp/akun" class="parity-ptsp-login-register">
                                        <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Buat Akun Baru
                                    </Link>
                                </div>
                                <div class="parity-ptsp-login-help">
                                    <Link href="/kontak"><i class="fa-solid fa-comments" aria-hidden="true"></i> Bantuan / Lupa Password? Hubungi Live Chat</Link>
                                </div>
                            </form>
                            <div v-else class="parity-ptsp-login-box__actions">
                                <Link href="/layanan/ptsp/akun" class="parity-ptsp-login-submit">
                                    <i class="fa-solid fa-user-check" aria-hidden="true"></i> Akun: {{ applicant.full_name }}
                                </Link>
                                <a href="#katalog-ptsp" class="parity-ptsp-login-register" @click="showCatalog = true">Buka Katalog</a>
                            </div>
                            <hr class="parity-ptsp-login-divider" />
                            <p class="parity-ptsp-login-box__note">
                                Dengan mengklik Buat Akun Baru, Anda menyetujui Ketentuan Layanan dan Kebijakan Privasi dari {{ school.name }}.
                            </p>
                        </aside>
                    </div>
                </div>
            </section>

            <section v-if="showCatalog" id="katalog-ptsp" class="portal-card">
                    <Link href="/layanan/ptsp/akun" class="ui-button ui-button--secondary ui-button--md">
                        {{ applicant ? `Akun: ${applicant.full_name}` : 'Masuk / Daftar Akun Pemohon' }}
                    </Link>
                <div class="section-head">
                    <h2>Katalog Layanan PTSP</h2>
                    <div class="chip-row" role="group" aria-label="Filter kategori layanan PTSP">
                        <button type="button" class="chip-btn" :class="{ 'is-active': selectedCategory === 'all' }" @click="selectedCategory = 'all'">Semua</button>
                        <button v-for="cat in categories" :key="cat.value" type="button" class="chip-btn" :class="{ 'is-active': selectedCategory === cat.value }" @click="selectedCategory = cat.value">
                            {{ cat.label }}
                        </button>
                    </div>
                </div>

                <div v-if="filteredCatalog.length" class="catalog-grid">
                    <button
                        v-for="item in filteredCatalog"
                        :key="item.id"
                        type="button"
                        class="service-card"
                        :class="{ 'is-selected': form.ptsp_service_id === item.id, 'is-closed': !item.is_active }"
                        :disabled="!item.is_active"
                        @click="selectService(item)"
                    >
                        <div class="service-card__top">
                            <span class="service-code">{{ item.code }}</span>
                            <StatusBadge :status="item.is_active ? 'active' : 'inactive'" :label="item.is_active ? 'Buka' : 'Tutup'" size="sm" />
                        </div>
                        <strong>{{ item.name }}</strong>
                        <p v-if="item.description">{{ item.description }}</p>
                        <div class="service-meta">
                            <span>Estimasi: {{ item.estimated_duration }}</span>
                            <span v-if="item.service_hours">Jam: {{ item.service_hours }}</span>
                        </div>
                    </button>
                </div>

                <StateFeedback
                    v-else
                    state="empty"
                    title="Belum ada layanan tersedia"
                    description="Tidak ada layanan PTSP pada kategori ini saat ini."
                />
            </section>

            <section v-if="selectedService" id="form-ptsp" class="portal-card">
                <div class="section-head">
                    <div>
                        <h2>Formulir Pengajuan — {{ selectedService.name }}</h2>
                        <p class="muted">
                            Estimasi selesai: {{ selectedService.estimated_duration }}
                            <template v-if="selectedService.service_hours"> · Jam layanan: {{ selectedService.service_hours }}</template>
                        </p>
                    </div>
                    <StatusBadge v-if="activeDraft" status="draft" label="Mengedit Draf" />
                </div>

                <div v-if="currentRequirements.length" class="requirements-box">
                    <strong>Persyaratan Layanan:</strong>
                    <ul>
                        <li v-for="(req, idx) in currentRequirements" :key="idx">{{ req }}</li>
                    </ul>
                </div>

                <form class="form-grid" @submit.prevent="submitFinal">
                    <FormField id="ptsp-applicant-type" label="Kategori Pemohon" required :error="form.errors.applicant_type" v-slot="{ inputId, describedBy, invalid }">
                        <select :id="inputId" v-model="form.applicant_type" :aria-describedby="describedBy" :aria-invalid="invalid">
                            <option v-for="t in applicantTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </FormField>

                    <FormField id="ptsp-applicant-name" label="Nama Lengkap Pemohon" required :error="form.errors.applicant_name" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.applicant_name" type="text" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="ptsp-applicant-phone" label="Nomor HP / WhatsApp" :error="form.errors.applicant_phone" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.applicant_phone" type="text" placeholder="08xxxxxxxxxx" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="ptsp-applicant-email" label="Email" :error="form.errors.applicant_email" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.applicant_email" type="email" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="ptsp-applicant-org" label="Kelas / Angkatan / Instansi Asal" :error="form.errors.applicant_organization" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.applicant_organization" type="text" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="ptsp-secret" label="PIN Verifikasi Resi (Opsional)" hint="Kosongkan agar sistem membuat PIN otomatis 6 digit." :error="form.errors.verification_secret" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.verification_secret" type="text" minlength="4" maxlength="24" placeholder="Contoh: 123456" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <div class="full-span">
                        <FormField id="ptsp-purpose" label="Keperluan / Judul Permohonan" required :error="form.errors.purpose" v-slot="{ inputId, describedBy, invalid }">
                            <input :id="inputId" v-model="form.purpose" type="text" required placeholder="Contoh: Pengajuan Surat Keterangan Aktif untuk Beasiswa" :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </FormField>
                    </div>

                    <div class="full-span">
                        <FormField id="ptsp-notes" label="Catatan Tambahan" :error="form.errors.notes" v-slot="{ inputId, describedBy, invalid }">
                            <textarea :id="inputId" v-model="form.notes" rows="3" :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </FormField>
                    </div>

                    <div v-if="currentRequiredDocs.length" class="full-span docs-section">
                        <h3>Lampiran Persyaratan (PDF/JPG/PNG/WEBP, maks. 5 MB)</h3>
                        <div class="docs-grid">
                            <FormField
                                v-for="docRule in currentRequiredDocs"
                                :key="docRule.key"
                                :id="`doc-${docRule.key}`"
                                :label="docRule.label"
                                :required="docRule.required"
                                :error="attachmentError(docRule.key)"
                                v-slot="{ inputId, describedBy, invalid }"
                            >
                                <input :id="inputId" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" :aria-describedby="describedBy" :aria-invalid="invalid" @change="onFileChange(docRule.key, $event)" />
                                <div v-if="draftUploadedMap[docRule.key]" class="draft-file-chip">
                                    <span>Tersimpan: {{ draftUploadedMap[docRule.key].original_name }}</span>
                                    <button v-if="activeDraft" type="button" class="link-danger" @click="removeDraftAttachment(activeDraft.id, draftUploadedMap[docRule.key].document_id)">Hapus</button>
                                </div>
                            </FormField>
                        </div>
                    </div>

                    <div v-if="showReviewStep" class="full-span summary-box">
                        <h3>Ringkasan Pengajuan</h3>
                        <p><strong>Layanan:</strong> {{ selectedService.name }}</p>
                        <p><strong>Pemohon:</strong> {{ form.applicant_name || '-' }} ({{ form.applicant_phone || form.applicant_email || '-' }})</p>
                        <p><strong>Keperluan:</strong> {{ form.purpose || '-' }}</p>
                    </div>

                    <div class="full-span agreement-row">
                        <label class="checkbox-label">
                            <input v-model="form.agreement_accepted" type="checkbox" />
                            <span>Saya menyatakan data dan berkas yang diajukan adalah benar dan siap diverifikasi oleh Tata Usaha.</span>
                        </label>
                        <p v-if="form.errors.agreement_accepted" class="field-error" role="alert">{{ form.errors.agreement_accepted }}</p>
                    </div>

                    <div class="full-span action-row">
                        <button type="button" class="ui-button ui-button--secondary ui-button--md" :disabled="form.processing" @click="saveDraft">
                            Simpan Draf
                        </button>
                        <button type="button" class="ui-button ui-button--secondary ui-button--md" @click="showReviewStep = !showReviewStep">
                            {{ showReviewStep ? 'Tutup Ringkasan' : 'Lihat Ringkasan' }}
                        </button>
                        <button type="submit" class="ui-button ui-button--primary ui-button--md" :disabled="form.processing">
                            Kirim &amp; Terbitkan Resi
                        </button>
                    </div>
                </form>
            </section>

            <section v-if="myRequests.length" class="portal-card">
                <h2>Riwayat &amp; Draf Permohonan Saya</h2>
                <div class="history-list">
                    <article v-for="req in myRequests" :key="req.id" class="history-item">
                        <div>
                            <div class="history-head">
                                <strong>{{ req.ticket_code || 'DRAF BELUM DIAJUKAN' }}</strong>
                                <StatusBadge :status="req.status" size="sm" />
                            </div>
                            <p class="history-title">{{ req.service_snapshot.name }} — {{ req.purpose }}</p>
                            <p v-if="req.revision_note && req.status === 'needs_revision'" class="revision-callout">
                                Catatan Perbaikan: {{ req.revision_note }}
                            </p>
                        </div>
                        <div class="history-actions">
                            <Link v-if="req.status === 'draft'" :href="`/layanan/ptsp?draf=${req.id}`" class="ui-button ui-button--secondary ui-button--sm">Lanjutkan Draf</Link>
                            <Link v-if="req.ticket_code" :href="`/layanan/ptsp/resi/${req.ticket_code}`" class="ui-button ui-button--secondary ui-button--sm">Lihat Resi</Link>
                            <Link v-if="req.ticket_code && req.status === 'needs_revision'" :href="`/layanan/ptsp/${req.ticket_code}/perbaiki`" class="ui-button ui-button--primary ui-button--sm">Perbaiki</Link>
                        </div>
                    </article>
                </div>
            </section>
        </section>
    </main>
</template>

<style scoped>
.public-portal-page { min-height: 100vh; background: var(--public-surface, #f5f8fb); color: var(--public-ink, #173044); }
.portal-container { width: min(1140px, calc(100% - 2rem)); margin-inline: auto; padding: 2.25rem 0 4rem; display: grid; gap: 1.5rem; }
.portal-hero { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 1rem; }
.portal-hero h1 { margin: .35rem 0 .45rem; font-size: clamp(1.75rem, 3.6vw, 2.5rem); line-height: 1.15; }
.portal-hero p { margin: 0; color: var(--public-muted, #526673); max-width: 65ch; }
.hero-actions { display: flex; flex-wrap: wrap; gap: .75rem; }
.portal-card { background: #fff; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 16px; padding: 1.5rem; display: grid; gap: 1.25rem; }
.section-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .85rem; }
.section-head h2 { margin: 0; font-size: 1.2rem; }
.muted { margin: .25rem 0 0; color: var(--public-muted, #526673); font-size: .88rem; }
.chip-row { display: flex; flex-wrap: wrap; gap: .45rem; }
.chip-btn { min-height: 40px; padding: .35rem .85rem; border-radius: 999px; border: 1px solid var(--public-panel-border, #d8e2ee); background: #fff; font: inherit; font-weight: 600; cursor: pointer; }
.chip-btn.is-active { background: var(--public-primary, #3157a4); border-color: var(--public-primary, #3157a4); color: #fff; }
.catalog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: .9rem; }
.service-card { text-align: left; display: grid; gap: .45rem; padding: 1rem; border-radius: 12px; border: 1px solid var(--public-panel-border, #d8e2ee); background: #fff; font: inherit; color: inherit; cursor: pointer; }
.service-card.is-selected { border-color: var(--public-primary, #3157a4); box-shadow: 0 0 0 3px color-mix(in srgb, var(--public-primary, #3157a4) 14%, transparent); }
.service-card.is-closed { opacity: .65; cursor: not-allowed; background: #f8fafc; }
.service-card__top { display: flex; justify-content: space-between; align-items: center; gap: .5rem; }
.service-code { font-size: .75rem; font-weight: 700; color: var(--public-primary, #3157a4); }
.service-card p { margin: 0; font-size: .85rem; color: var(--public-muted, #526673); }
.service-meta { display: flex; flex-wrap: wrap; gap: .75rem; font-size: .78rem; color: var(--public-muted, #526673); }
.requirements-box { background: #f8fafc; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 12px; padding: 1rem; }
.requirements-box ul { margin: .5rem 0 0 1.25rem; padding: 0; display: grid; gap: .25rem; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; }
.full-span { grid-column: 1 / -1; }
.docs-section { display: grid; gap: .85rem; }
.docs-section h3 { margin: 0; font-size: 1rem; }
.docs-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
.draft-file-chip { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-top: .35rem; padding: .35rem .6rem; background: #eef4ff; border-radius: 8px; font-size: .8rem; }
.link-danger { border: 0; background: none; color: #b13f38; font-weight: 600; cursor: pointer; }
.summary-box { padding: 1rem; border-radius: 12px; background: #f8fafc; border: 1px solid var(--public-panel-border, #d8e2ee); display: grid; gap: .3rem; }
.summary-box h3, .summary-box p { margin: 0; }
.checkbox-label { display: flex; align-items: flex-start; gap: .6rem; font-size: .9rem; cursor: pointer; }
.checkbox-label input { width: 20px; height: 20px; margin-top: .1rem; }
.field-error { margin: .35rem 0 0; color: #b13f38; font-size: .8rem; font-weight: 600; }
.action-row { display: flex; flex-wrap: wrap; gap: .75rem; }
.history-list { display: grid; gap: .85rem; }
.history-item { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; padding: 1rem; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 12px; }
.history-head { display: flex; align-items: center; gap: .6rem; }
.history-title { margin: .3rem 0 0; font-size: .9rem; color: var(--public-muted, #526673); }
.revision-callout { margin: .35rem 0 0; font-size: .85rem; color: #80500d; font-weight: 600; }
.history-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
.alert-banner { padding: .85rem 1rem; border-radius: 12px; background: #e1f2e8; color: #1b5c3e; border: 1px solid #b6dec9; font-weight: 600; }
.benefit-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: .85rem; }
.benefit-card { padding: 1rem; border: 1px solid #dce7e3; border-radius: 14px; background: #f8fafc; display: grid; gap: .35rem; align-content: start; }
.benefit-card h3 { margin: 0; font-size: .95rem; font-weight: 800; color: #0f172a; }
.benefit-card p { margin: 0; font-size: .82rem; color: #475569; line-height: 1.5; }
.benefit-icon { width: 2.1rem; height: 2.1rem; border-radius: 10px; background: #d1fae5; color: #047857; display: grid; place-items: center; font-size: .9rem; }
</style>
