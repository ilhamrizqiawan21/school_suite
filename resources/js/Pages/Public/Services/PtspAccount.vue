<script setup lang="ts">
import { usePublicContentText } from '@/composables/usePublicContentText';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import Head from '@/components/PublicHead.vue';
import { computed, ref } from 'vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

interface Applicant {
    id: number;
    applicant_type: string;
    full_name: string;
    phone?: string | null;
    email?: string | null;
    organization_name?: string | null;
    identifier_last4?: string | null;
}

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    applicant: Applicant | null;
    applicantTypes: { value: string; label: string }[];
    recoveryStatus?: string | null;
    returnToTracer?: boolean;
}>();

const page = usePage();
const flashMessage = computed(() => (page.props.flash as { message?: string } | undefined)?.message);
const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const activeTab = ref<'login' | 'register' | 'recovery'>('login');

const loginForm = useForm({ email: '', password: '' });
const registerForm = useForm({
    applicant_type: props.returnToTracer ? 'alumni' : 'general',
    full_name: '',
    email: '',
    phone: '',
    organization_name: '',
    raw_identifier: '',
    password: '',
    password_confirmation: '',
});
const recoveryRequestForm = useForm({ email: '', verification_hint: '' });
const recoveryResetForm = useForm({
    email: '',
    recovery_token: '',
    password: '',
    password_confirmation: '',
});
const profileForm = useForm({
    applicant_type: props.applicant?.applicant_type ?? 'general',
    full_name: props.applicant?.full_name ?? '',
    phone: props.applicant?.phone ?? '',
    organization_name: props.applicant?.organization_name ?? '',
});

const submitLogin = () => loginForm.post('/layanan/ptsp/akun/masuk', { onFinish: () => loginForm.reset('password') });
const submitRegister = () => registerForm.post('/layanan/ptsp/akun/daftar', { onFinish: () => registerForm.reset('password', 'password_confirmation') });
const submitRecoveryRequest = () => recoveryRequestForm.post('/layanan/ptsp/akun/pulihkan');
const submitRecoveryReset = () => recoveryResetForm.post('/layanan/ptsp/akun/reset-sandi', { onFinish: () => recoveryResetForm.reset('password', 'password_confirmation') });
const submitProfile = () => profileForm.patch('/layanan/ptsp/akun/profil');
const logoutApplicant = () => router.post('/layanan/ptsp/akun/keluar');
const publicText = usePublicContentText();
</script>

<template>
    <Head :title="`Akun Pemohon PTSP — ${school.name}`" />
    <main class="public-site public-portal-page" :style="themeStyle">
        <PublicHeader :school="school" />

        <section class="portal-container">
            <header class="portal-hero">
                <p class="eyebrow">{{ publicText("copy.vue_services_ptspaccount", "text_001", "Akun Pemohon PTSP") }}</p>
                <h1>{{ publicText("copy.vue_services_ptspaccount", "text_002", "Kelola Akun Pemohon Layanan") }}</h1>
                <p>{{ publicText("copy.vue_services_ptspaccount", "text_003", "Akun pemohon PTSP terpisah dari akun pegawai sekolah untuk memudahkan pengajuan, penyimpanan draf, dan pemantauan resi.") }}</p>
                <div class="hero-links">
                    <Link href="/layanan/ptsp" class="ui-button ui-button--secondary ui-button--md">{{ publicText("copy.vue_services_ptspaccount", "text_004", "Kembali ke Layanan PTSP") }}</Link>
                    <Link href="/lacak" class="ui-button ui-button--secondary ui-button--md">{{ publicText("copy.vue_services_ptspaccount", "text_005", "Lacak Tiket Layanan") }}</Link>
                </div>
            </header>

            <div v-if="flashMessage" class="alert-banner alert-banner--success" role="status">{{ flashMessage }}</div>
            <div v-if="recoveryStatus" class="alert-banner alert-banner--info" role="status">{{ recoveryStatus }}</div>

            <section v-if="applicant" class="portal-card">
                <div class="card-head">
                    <div>
                        <h2>{{ publicText("copy.vue_services_ptspaccount", "text_006", "Profil Pemohon Aktif") }}</h2>
                        <p class="muted">{{ applicant.email }}</p>
                    </div>
                    <button type="button" class="ui-button ui-button--secondary ui-button--md" @click="logoutApplicant">
                        {{ publicText("copy.vue_services_ptspaccount", "text_007", "Keluar Akun Pemohon") }}
                    </button>
                </div>

                <form class="form-grid" @submit.prevent="submitProfile">
                    <FormField id="profile-type" :label="publicText('copy.vue_services_ptspaccount', 'label_10001', 'Kategori Pemohon')" required :error="profileForm.errors.applicant_type" v-slot="{ inputId, describedBy, invalid }">
                        <select :id="inputId" v-model="profileForm.applicant_type" :aria-describedby="describedBy" :aria-invalid="invalid">
                            <option v-for="t in applicantTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </FormField>

                    <FormField id="profile-name" :label="publicText('copy.vue_services_ptspaccount', 'label_10002', 'Nama Lengkap')" required :error="profileForm.errors.full_name" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="profileForm.full_name" type="text" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="profile-phone" :label="publicText('copy.vue_services_ptspaccount', 'label_10003', 'Nomor HP / WhatsApp')" required :error="profileForm.errors.phone" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="profileForm.phone" type="text" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="profile-org" :label="publicText('copy.vue_services_ptspaccount', 'label_10004', 'Instansi / Asal Sekolah / Keterangan')" :error="profileForm.errors.organization_name" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="profileForm.organization_name" type="text" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <div class="form-actions">
                        <button type="submit" class="ui-button ui-button--primary ui-button--md" :disabled="profileForm.processing">
                            {{ publicText("copy.vue_services_ptspaccount", "text_008", "Simpan Perubahan Profil") }}
                        </button>
                    </div>
                </form>
            </section>

            <section v-else class="portal-card">
                <div class="tab-bar" role="tablist" :aria-label="publicText('copy.vue_services_ptspaccount', 'aria_label_018', 'Menu akun pemohon')">
                    <button type="button" role="tab" class="tab-btn" :class="{ 'is-active': activeTab === 'login' }" :aria-selected="activeTab === 'login'" @click="activeTab = 'login'">
                        {{ publicText("copy.vue_services_ptspaccount", "text_009", "Masuk Pemohon") }}
                    </button>
                    <button type="button" role="tab" class="tab-btn" :class="{ 'is-active': activeTab === 'register' }" :aria-selected="activeTab === 'register'" @click="activeTab = 'register'">
                        {{ publicText("copy.vue_services_ptspaccount", "text_010", "Daftar Akun Baru") }}
                    </button>
                    <button type="button" role="tab" class="tab-btn" :class="{ 'is-active': activeTab === 'recovery' }" :aria-selected="activeTab === 'recovery'" @click="activeTab = 'recovery'">
                        {{ publicText("copy.vue_services_ptspaccount", "text_011", "Pulihkan Akun") }}
                    </button>
                </div>

                <form v-if="activeTab === 'login'" class="form-stack" @submit.prevent="submitLogin">
                    <FormField id="login-email" :label="publicText('copy.vue_services_ptspaccount', 'label_10005', 'Email Pemohon')" required :error="loginForm.errors.email" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="loginForm.email" type="email" required autocomplete="email" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="login-password" :label="publicText('copy.vue_services_ptspaccount', 'label_10006', 'Kata Sandi')" required :error="loginForm.errors.password" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="loginForm.password" type="password" required autocomplete="current-password" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <div class="form-actions">
                        <button type="submit" class="ui-button ui-button--primary ui-button--md" :disabled="loginForm.processing">
                            {{ publicText("copy.vue_services_ptspaccount", "text_012", "Masuk ke Layanan PTSP") }}
                        </button>
                    </div>
                </form>

                <form v-else-if="activeTab === 'register'" class="form-grid" @submit.prevent="submitRegister">
                    <FormField id="reg-type" :label="publicText('copy.vue_services_ptspaccount', 'label_10001', 'Kategori Pemohon')" required :error="registerForm.errors.applicant_type" v-slot="{ inputId, describedBy, invalid }">
                        <select :id="inputId" v-model="registerForm.applicant_type" :aria-describedby="describedBy" :aria-invalid="invalid">
                            <option v-for="t in applicantTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </FormField>

                    <FormField id="reg-name" :label="publicText('copy.vue_services_ptspaccount', 'label_10002', 'Nama Lengkap')" required :error="registerForm.errors.full_name" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="registerForm.full_name" type="text" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="reg-email" :label="publicText('copy.vue_services_ptspaccount', 'label_10007', 'Alamat Email')" required :error="registerForm.errors.email" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="registerForm.email" type="email" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="reg-phone" :label="publicText('copy.vue_services_ptspaccount', 'label_10003', 'Nomor HP / WhatsApp')" required :error="registerForm.errors.phone" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="registerForm.phone" type="text" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="reg-org" :label="publicText('copy.vue_services_ptspaccount', 'label_10008', 'Instansi / Unit / Keterangan (Opsional)')" :error="registerForm.errors.organization_name" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="registerForm.organization_name" type="text" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="reg-ident" :label="publicText('copy.vue_services_ptspaccount', 'label_10009', 'Nomor Identitas / NISN / NIK (Opsional)')" :hint="publicText('copy.vue_services_ptspaccount', 'hint_10010', 'Hanya 4 digit terakhir yang disimpan sebagai petunjuk pemulihan.')" :error="registerForm.errors.raw_identifier" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="registerForm.raw_identifier" type="text" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="reg-pass" :label="publicText('copy.vue_services_ptspaccount', 'label_10011', 'Kata Sandi (min. 8 karakter)')" required :error="registerForm.errors.password" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="registerForm.password" type="password" required minlength="8" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="reg-pass-confirm" :label="publicText('copy.vue_services_ptspaccount', 'label_10012', 'Ulangi Kata Sandi')" required :error="registerForm.errors.password_confirmation" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="registerForm.password_confirmation" type="password" required minlength="8" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <div class="form-actions">
                        <button type="submit" class="ui-button ui-button--primary ui-button--md" :disabled="registerForm.processing">
                            {{ publicText("copy.vue_services_ptspaccount", "text_013", "Buat Akun Pemohon") }}
                        </button>
                    </div>
                </form>

                <div v-else class="recovery-grid">
                    <form class="form-stack" @submit.prevent="submitRecoveryRequest">
                        <h3>{{ publicText("copy.vue_services_ptspaccount", "text_014", "1. Minta Kode Pemulihan") }}</h3>
                        <FormField id="rec-email" :label="publicText('copy.vue_services_ptspaccount', 'label_10013', 'Email Terdaftar')" required :error="recoveryRequestForm.errors.email" v-slot="{ inputId, describedBy, invalid }">
                            <input :id="inputId" v-model="recoveryRequestForm.email" type="email" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </FormField>
                        <FormField id="rec-hint" :label="publicText('copy.vue_services_ptspaccount', 'label_10014', '4 Digit Terakhir Nomor HP atau Identitas')" required :error="recoveryRequestForm.errors.verification_hint" v-slot="{ inputId, describedBy, invalid }">
                            <input :id="inputId" v-model="recoveryRequestForm.verification_hint" type="text" required minlength="4" :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </FormField>
                        <button type="submit" class="ui-button ui-button--secondary ui-button--md" :disabled="recoveryRequestForm.processing">
                            {{ publicText("copy.vue_services_ptspaccount", "text_015", "Kirim Permintaan Kode Pemulihan") }}
                        </button>
                    </form>

                    <form class="form-stack" @submit.prevent="submitRecoveryReset">
                        <h3>{{ publicText("copy.vue_services_ptspaccount", "text_016", "2. Atur Ulang Kata Sandi") }}</h3>
                        <FormField id="reset-email" :label="publicText('copy.vue_services_ptspaccount', 'label_10013', 'Email Terdaftar')" required :error="recoveryResetForm.errors.email" v-slot="{ inputId, describedBy, invalid }">
                            <input :id="inputId" v-model="recoveryResetForm.email" type="email" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </FormField>
                        <FormField id="reset-token" :label="publicText('copy.vue_services_ptspaccount', 'label_10015', 'Kode Pemulihan')" required :error="recoveryResetForm.errors.recovery_token" v-slot="{ inputId, describedBy, invalid }">
                            <input :id="inputId" v-model="recoveryResetForm.recovery_token" type="text" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </FormField>
                        <FormField id="reset-pass" :label="publicText('copy.vue_services_ptspaccount', 'label_10016', 'Kata Sandi Baru')" required :error="recoveryResetForm.errors.password" v-slot="{ inputId, describedBy, invalid }">
                            <input :id="inputId" v-model="recoveryResetForm.password" type="password" required minlength="8" :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </FormField>
                        <FormField id="reset-pass-confirm" :label="publicText('copy.vue_services_ptspaccount', 'label_10017', 'Konfirmasi Kata Sandi Baru')" required :error="recoveryResetForm.errors.password_confirmation" v-slot="{ inputId, describedBy, invalid }">
                            <input :id="inputId" v-model="recoveryResetForm.password_confirmation" type="password" required minlength="8" :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </FormField>
                        <button type="submit" class="ui-button ui-button--primary ui-button--md" :disabled="recoveryResetForm.processing">
                            {{ publicText("copy.vue_services_ptspaccount", "text_017", "Simpan Kata Sandi Baru") }}
                        </button>
                    </form>
                </div>
            </section>
        </section>
    </main>
</template>

<style scoped>
.public-portal-page { min-height: 100vh; background: var(--public-surface, #f5f8fb); color: var(--public-ink, #173044); }
.portal-container { width: min(980px, calc(100% - 2rem)); margin-inline: auto; padding: 2.25rem 0 4rem; display: grid; gap: 1.5rem; }
.portal-hero h1 { margin: .35rem 0 .5rem; font-size: clamp(1.75rem, 3.5vw, 2.5rem); line-height: 1.15; }
.portal-hero p { margin: 0; color: var(--public-muted, #526673); max-width: 65ch; }
.hero-links { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1rem; }
.portal-card { background: #fff; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 16px; padding: 1.5rem; display: grid; gap: 1.25rem; }
.card-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; }
.muted { margin: .2rem 0 0; color: var(--public-muted, #526673); font-size: .9rem; }
.tab-bar { display: flex; flex-wrap: wrap; gap: .5rem; border-bottom: 1px solid var(--public-panel-border, #d8e2ee); padding-bottom: .75rem; }
.tab-btn { min-height: 44px; padding: .5rem 1rem; border-radius: 10px; border: 1px solid var(--public-panel-border, #d8e2ee); background: #fff; font: inherit; font-weight: 600; cursor: pointer; color: inherit; }
.tab-btn.is-active { background: var(--public-primary, #3157a4); border-color: var(--public-primary, #3157a4); color: #fff; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; }
.form-stack { display: grid; gap: 1rem; max-width: 480px; }
.form-actions { grid-column: 1 / -1; display: flex; justify-content: flex-start; }
.recovery-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; }
.alert-banner { padding: .85rem 1rem; border-radius: 12px; font-weight: 600; font-size: .9rem; }
.alert-banner--success { background: #e1f2e8; color: #1b5c3e; border: 1px solid #b6dec9; }
.alert-banner--info { background: #e6effd; color: #1f4b8f; border: 1px solid #bfd6fb; }
</style>
