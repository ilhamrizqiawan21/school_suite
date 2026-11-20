<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props=defineProps<{status?:string;branding?:{school_name:string;logo_url?:string|null;favicon_url?:string|null;theme_primary?:string}|null}>();

const form = useForm({
    identity: '',
    password: '',
    remember: false,
});

const page = usePage();
const man4 = computed(() => page.props.publicPortalTheme === 'madrasah');
const studentPortal = computed(() => man4.value && new URLSearchParams(page.url.split('?')[1]).get('portal') === 'siswa');

const showPassword = ref(false);
const schoolName = computed(() => props.branding?.school_name || 'Portal Sekolah');
const schoolInitial = computed(() => schoolName.value.trim().charAt(0).toUpperCase() || 'S');
onMounted(() => {
    document.documentElement.classList.add('login-page-active');

    if (props.branding?.theme_primary) {
        document.documentElement.style.setProperty('--primary', props.branding.theme_primary);
    }

    if (props.branding?.favicon_url) {
        let link = document.querySelector<HTMLLinkElement>('link[rel="icon"]');
        if (!link) {
            link = document.createElement('link');
            link.rel = 'icon';
            document.head.appendChild(link);
        }
        link.href = props.branding.favicon_url;
    }
});

onBeforeUnmount(() => {
    document.documentElement.classList.remove('login-page-active');
});

const submit = () => {
    // Keep the deployment prefix when the app is mounted below the domain root
    // (for example, /school_suite/login behind the shared Apache vhost).
    form.post(window.location.pathname, {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <main class="login-page" :class="{ 'is-madrasah-login': man4, 'is-student-portal': studentPortal }" :style="{ '--login-primary': branding?.theme_primary || '#3157a4' }">
        <Head :title="`Masuk — ${schoolName}`" />
        <section class="login-layout" aria-label="Login portal sekolah">
            <aside class="welcome-panel">
                <div class="school-brand">
                    <img v-if="branding?.logo_url" :src="branding.logo_url" :alt="`Logo ${schoolName}`" />
                    <span v-else class="brand-fallback" aria-hidden="true">{{ schoolInitial }}</span>
                    <span class="brand-copy"><strong>{{ schoolName }}</strong><small>School Suite</small></span>
                </div>
                <div class="welcome-copy">
                    <p class="welcome-kicker">{{ studentPortal ? 'Portal Siswa & Wali Murid' : man4 ? 'Portal Database Madrasah' : 'Portal warga sekolah' }}</p>
                    <h1>{{ studentPortal ? 'Pantau Perkembangan Akademik & Administrasi Siswa' : man4 ? 'Pusat Manajemen Database & Layanan Administrasi Digital' : 'Semua kebutuhan belajar, dalam satu tempat.' }}</h1>

                    <div v-if="man4" class="madrasah-feature-grid">
                        <template v-if="studentPortal">
                            <div class="madrasah-feature-card">
                                <strong>Biodata &amp; Profil</strong>
                                <span>Cek NISN, data kelas, jurusan, dan kartu pelajar digital.</span>
                            </div>
                            <div class="madrasah-feature-card">
                                <strong>Pengajuan Izin</strong>
                                <span>Form izin sakit atau dispensasi langsung diverifikasi wali kelas.</span>
                            </div>
                            <div class="madrasah-feature-card">
                                <strong>Perpustakaan</strong>
                                <span>Katalog buku digital, riwayat peminjaman, dan bebas pustaka.</span>
                            </div>
                            <div class="madrasah-feature-card">
                                <strong>Prestasi &amp; Catatan</strong>
                                <span>Pantau poin kedisiplinan serta catatan prestasi penghargaan.</span>
                            </div>
                        </template>
                        <template v-else>
                            <div class="madrasah-feature-card">
                                <strong>Database &amp; Siswa</strong>
                                <span>Data induk siswa, riwayat akademik, pelanggaran, dan presensi.</span>
                            </div>
                            <div class="madrasah-feature-card">
                                <strong>Tenaga Pendidik</strong>
                                <span>Jurnal mengajar, absensi guru, dan administrasi kepegawaian.</span>
                            </div>
                            <div class="madrasah-feature-card">
                                <strong>Lapor &amp; PTSP</strong>
                                <span>Pengaduan madrasah, izin siswa, dan surat menyurat digital.</span>
                            </div>
                            <div class="madrasah-feature-card">
                                <strong>Aman &amp; Terenkripsi</strong>
                                <span>Sesi terproteksi, audit log aktivitas, dan hak akses bertingkat.</span>
                            </div>
                        </template>
                    </div>

                    <ul v-else class="welcome-points">
                        <li>Materi &amp; tugas</li>
                        <li>Jadwal pelajaran</li>
                        <li>Nilai &amp; rapor</li>
                        <li>Pengumuman sekolah</li>
                    </ul>
                </div>

                <div class="welcome-footer-links">
                    <a class="back-home" href="/">← Kembali ke situs sekolah</a>
                    <div v-if="man4" class="portal-switch-links">
                        <a href="/layanan/lapor">Portal Lapor</a>
                        <a href="/layanan/ptsp">PTSP Online</a>
                        <a href="/perpustakaan">Perpustakaan</a>
                        <a :href="studentPortal ? '/login' : '/login?portal=siswa'">
                            {{ studentPortal ? 'Login Guru / Admin' : 'Login Siswa' }}
                        </a>
                    </div>
                </div>
            </aside>

            <section class="login-panel" aria-labelledby="login-title">
                <div v-if="man4" class="login-mobile-brand">
                    <img :src="branding?.logo_url || '/madrasah-theme/images/logo-kemenag.png'" alt="" />
                    <strong>{{ schoolName }}</strong>
                    <a href="/">Beranda</a>
                </div>
                <header class="login-heading">
                    <span v-if="man4" class="login-heading-mark" aria-hidden="true"></span>
                    <p v-else class="login-kicker">Akun sekolah</p>
                    <h2 id="login-title">{{ studentPortal ? 'Login Siswa' : man4 ? 'Selamat Datang' : 'Masuk ke portal' }}</h2>
                    <p>Gunakan akun yang diberikan sekolah.</p>

                </header>

                <div v-if="status" class="status-message" role="status">{{ status }}</div>

                <form autocomplete="on" @submit.prevent="submit" class="login-form">
                    <div class="form-field">
                        <label for="identity">Email atau nama pengguna</label>
                        <input
                            id="identity"
                            v-model="form.identity"
                            type="text"
                            required
                            autofocus
                            autocomplete="username"
                            :placeholder="studentPortal ? 'Username atau email akun siswa' : 'nama@sekolah.sch.id'"
                            :aria-invalid="!!form.errors.identity"
                            :aria-describedby="form.errors.identity ? 'identity-error' : undefined"
                        />
                        <p v-if="form.errors.identity" id="identity-error" class="field-error" role="alert">{{ form.errors.identity }}</p>
                    </div>

                    <div class="form-field">
                        <div class="field-heading">
                            <label for="password">Kata sandi</label>
                        </div>
                        <div class="password-input-wrap">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan kata sandi"
                                :aria-invalid="!!form.errors.password"
                                :aria-describedby="form.errors.password ? 'password-error' : undefined"
                            />
                            <button
                                type="button"
                                class="password-toggle"
                                :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                :aria-pressed="showPassword"
                                @click="showPassword = !showPassword"
                            >
                                <svg v-if="!showPassword" class="eye-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                    <path d="M2.5 12s3.4-6.25 9.5-6.25 9.5 6.25 9.5 6.25-3.4 6.25-9.5 6.25S2.5 12 2.5 12Z" />
                                    <circle cx="12" cy="12" r="2.75" />
                                </svg>
                                <svg v-else class="eye-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                    <path d="M3 3l18 18M10.6 5.9A9.8 9.8 0 0 1 12 5.75c6.1 0 9.5 6.25 9.5 6.25a15.8 15.8 0 0 1-3.1 3.75M6.2 6.35C3.8 7.9 2.5 12 2.5 12s3.4 6.25 9.5 6.25c1.4 0 2.6-.35 3.6-.85" />
                                    <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" id="password-error" class="field-error" role="alert">{{ form.errors.password }}</p>
                    </div>

                    <label class="remember-option">
                        <input v-model="form.remember" type="checkbox" />
                        <span>Ingat saya di perangkat ini</span>
                    </label>

                    <AppButton variant="primary" size="lg" :loading="form.processing" type="submit" class="submit-button" :disabled="form.processing"><template #icon><svg v-if="form.processing" class="spinner" aria-hidden="true" fill="none" viewBox="0 0 24 24">
                            <circle opacity=".25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path opacity=".8" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                        </svg></template>

                        <span>{{ form.processing ? 'Memproses…' : 'Masuk' }}</span>
                    </AppButton>
                </form>
                <p v-if="man4" class="login-portal-switch">
                    <a :href="studentPortal ? '/login' : '/login?portal=siswa'">{{ studentPortal ? 'Masuk sebagai Guru / Admin' : 'Masuk sebagai Siswa / Wali' }}</a>
                </p>
                <p class="system-note">Lupa kata sandi? Hubungi operator atau wali kelas Anda.</p>
            </section>
        </section>
    </main>
</template>

<style scoped>
/* Calm Academic login — split layout, consistent with the public site. */
:global(html.login-page-active),
:global(html.login-page-active body),
:global(html.login-page-active #app) {
    background: #f4f7fb !important;
}

.login-page {
    width: 100%;
    max-width: none;
    margin: 0;
    padding: 0;
    --login-ink: #173044;
    --login-muted: #526673;
    --login-line: #dde5ef;
    min-height: 100vh;
    min-height: 100svh;
    display: grid;
    color: var(--login-ink);
    background: #f4f7fb;
    color-scheme: light;
    font-family: var(--font-ui);
}

.login-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    min-height: 100vh;
    min-height: 100svh;
}

/* Brand side */
.welcome-panel {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 2.5rem;
    min-width: 0;
    padding: clamp(2rem, 5vw, 4.5rem);
    background: color-mix(in srgb, var(--login-primary) 9%, #fff);
    border-right: 1px solid var(--login-line);
}

.school-brand { display: flex; align-items: center; gap: .85rem; min-width: 0; }
.school-brand img,
.brand-fallback { display: grid; place-items: center; flex: 0 0 auto; width: 3rem; height: 3rem; padding: .3rem; object-fit: contain; color: #fff; background: var(--login-primary); border-radius: .85rem; }
.school-brand img { background: #fff; border: 1px solid var(--login-line); }
.brand-fallback { font-size: 1.4rem; font-weight: var(--weight-bold); }
.brand-copy { display: grid; gap: .15rem; min-width: 0; }
.brand-copy strong { overflow-wrap: anywhere; font-size: var(--type-md); line-height: 1.3; }
.brand-copy small { color: var(--login-muted); font-size: var(--type-meta); }

.welcome-copy { max-width: 30rem; }
.welcome-kicker { margin: 0; color: color-mix(in srgb, var(--login-primary) 70%, #14263c); font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .08em; text-transform: uppercase; }
.welcome-copy h1 { margin: .9rem 0 1.5rem; color: var(--login-ink); font-family: var(--font-ui); font-size: clamp(2rem, 3.4vw, 3rem); font-weight: var(--weight-bold); letter-spacing: -.04em; line-height: 1.12; }
.welcome-points { display: flex; flex-wrap: wrap; gap: .6rem; margin: 0; padding: 0; list-style: none; }
.welcome-points li { padding: .45rem .85rem; color: var(--login-ink); background: #fff; border: 1px solid var(--login-line); border-radius: 999px; font-size: var(--type-sm); font-weight: var(--weight-semibold); }

.back-home { width: fit-content; min-height: 44px; display: inline-flex; align-items: center; color: color-mix(in srgb, var(--login-primary) 80%, #14263c); font-size: var(--type-sm); font-weight: var(--weight-bold); text-decoration: none; }
.back-home:hover,
.back-home:focus-visible { text-decoration: underline; }

/* Form side */
.login-panel { align-self: center; justify-self: center; width: 100%; max-width: 27rem; padding: clamp(2rem, 5vw, 3rem) clamp(1.5rem, 4vw, 2rem); }
.login-heading { margin-bottom: 2rem; }
.login-heading h2 { margin: 0 0 .5rem; font-family: var(--font-ui); font-size: clamp(1.6rem, 2.6vw, 2rem); font-weight: var(--weight-bold); letter-spacing: -.035em; line-height: 1.2; }
.login-heading p { margin: 0; color: var(--login-muted); font-size: var(--type-md); line-height: 1.6; }

.is-madrasah-login .welcome-panel {
    background:
        radial-gradient(circle at 75% 25%, #047857 0, transparent 48%),
        linear-gradient(135deg, #022c22, #064e3b 55%, #020617);
    border-color: #065f46;
    color: white;
}
.is-madrasah-login {
    background: #020617;
}

.is-madrasah-login .login-panel {
    width: calc(100% - 2rem);
    margin-block: 1rem;
    border: 1px solid #334155;
    border-radius: 1.25rem;
    background: #0f172a;
    color: #f8fafc;
    --login-muted: #cbd5e1;
    --login-line: #334155;
}

.is-madrasah-login .login-heading h2,
.is-madrasah-login .form-field label {
    color: #fff;
}

.is-madrasah-login .field-error {
    color: #fda4af;
}

.is-madrasah-login .welcome-copy h1 { color: white; }
.is-madrasah-login .brand-copy small,
.is-madrasah-login .welcome-kicker,
.is-madrasah-login .back-home { color: #d1fae5; }
.is-madrasah-login .welcome-points li {
    color: #ecfdf5;
    background: rgb(255 255 255 / 10%);
    border-color: #34d399;
}
.madrasah-feature-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .75rem;
}
.madrasah-feature-card {
    padding: .85rem .95rem;
    border-radius: .9rem;
    background: rgb(255 255 255 / 7%);
    border: 1px solid rgb(167 243 208 / 22%);
    display: grid;
    gap: .25rem;
}
.madrasah-feature-card strong {
    font-size: .85rem;
    color: #fef08a;
}
.madrasah-feature-card span {
    font-size: .76rem;
    line-height: 1.45;
    color: #d1fae5;
}
.welcome-footer-links {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
}
.portal-switch-links {
    display: flex;
    flex-wrap: wrap;
    gap: .65rem;
    font-size: .78rem;
}
.portal-switch-links a {
    color: #a7f3d0;
    text-decoration: none;
    font-weight: 600;
}
.portal-switch-links a:hover {
    color: #fef08a;
    text-decoration: underline;
}
@media (max-width: 520px) {
    .madrasah-feature-grid { grid-template-columns: 1fr; }
}

.status-message { margin-bottom: 1.25rem; padding: .85rem 1rem; color: #17613c; background: #edf8f1; border: 1px solid #b9e3c8; border-radius: .7rem; font-size: var(--type-sm); line-height: 1.5; }
.login-form { display: grid; gap: 1.25rem; }
.form-field { display: grid; gap: .5rem; min-width: 0; }
.form-field label { color: var(--login-ink); font-size: var(--type-sm); font-weight: var(--weight-semibold); }
.form-field input { width: 100%; min-height: 3.15rem; padding: .8rem .9rem; color: var(--login-ink); background: #fff; border: 1px solid #c7d4e3; border-radius: .7rem; font: inherit; font-size: var(--type-md); transition: border-color .15s ease, box-shadow .15s ease; }
.form-field input::placeholder { color: #536174; }
.form-field input:focus-visible { outline: 3px solid color-mix(in srgb, var(--login-primary) 28%, transparent); outline-offset: 1px; border-color: var(--login-primary); }
.form-field input[aria-invalid='true'] { border-color: #b42318; }
.form-field input:-webkit-autofill,
.form-field input:-webkit-autofill:hover,
.form-field input:-webkit-autofill:focus,
.form-field input:autofill {
    -webkit-text-fill-color: var(--login-ink) !important;
    caret-color: var(--login-ink);
    -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
    box-shadow: 0 0 0 1000px #fff inset !important;
}
.field-heading { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
.password-input-wrap { position: relative; }
.password-input-wrap > input { padding-right: 3.35rem; }
.password-toggle { position: absolute; top: 50%; right: .4rem; display: grid; place-items: center; width: 2.5rem; height: 2.5rem; padding: 0; color: #60738a; background: transparent; border: 0; border-radius: .55rem; cursor: pointer; transform: translateY(-50%); }
.password-toggle:hover { color: var(--login-primary); background: color-mix(in srgb, var(--login-primary) 10%, #fff); }
.eye-icon { width: 1.25rem; height: 1.25rem; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.8; }
.password-toggle:focus-visible,
.remember-option input:focus-visible { outline: 3px solid color-mix(in srgb, var(--login-primary) 35%, transparent); outline-offset: 3px; }
.field-error { margin: 0; color: #a32118; font-size: var(--type-meta); line-height: 1.5; }
.remember-option { display: inline-flex; align-items: center; gap: .65rem; width: fit-content; min-height: 44px; color: var(--login-muted); font-size: var(--type-sm); cursor: pointer; }
.remember-option input { width: 1.1rem; height: 1.1rem; accent-color: var(--login-primary); }
.submit-button { width: 100%; margin-top: .15rem; }
.submit-button:hover:not(:disabled) { filter: brightness(.92); }
.spinner { width: 1.1rem; height: 1.1rem; animation: spin 1s linear infinite; }
.system-note { margin: 1.8rem 0 0; padding-top: 1rem; color: var(--login-muted); border-top: 1px solid var(--login-line); font-size: var(--type-meta); line-height: 1.5; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 860px) {
    .login-layout { grid-template-columns: 1fr; min-height: 0; }
    .welcome-panel { gap: 1.5rem; padding: 1.5rem; border-right: 0; border-bottom: 1px solid var(--login-line); }
    .welcome-copy h1 { margin: .6rem 0 1rem; font-size: clamp(1.6rem, 6.5vw, 2.1rem); }
    .welcome-points { display: none; }
    .back-home { order: 3; }
    .login-panel { max-width: 30rem; padding: 2rem 1.5rem 2.5rem; }
}

@media (prefers-reduced-motion: reduce) {
    .spinner { animation-duration: 2.5s; }
    .form-field input { transition: none; }
}
 .login-mobile-brand { display: none; }
.login-heading-mark { display: block; width: 48px; height: 48px; margin-bottom: 1.5rem; border-radius: 12px; background: linear-gradient(135deg,#34d399,#0d9488); }
.login-portal-switch { margin: 1.5rem 0; font-size: .8rem; color: #5eead4; }
.is-madrasah-login .form-field input { background: #1e293b; color: #f8fafc; border-color: #334155; }
.is-madrasah-login .form-field input::placeholder { color: #94a3b8; }
.is-madrasah-login .submit-button { background: linear-gradient(110deg,#059669,#0d9488); border-color: #10b981; color: #fff; }
@media (min-width: 861px) {
    .is-madrasah-login .login-layout { width: min(1280px,100%); margin: auto; grid-template-columns: 1fr 1fr; }
    .is-madrasah-login .login-panel { max-width: 480px; background: transparent; border: 0; padding: 3rem; }
    .is-madrasah-login .welcome-panel { padding: 3rem; }
    .is-madrasah-login .welcome-copy h1 { font-size: 2rem; }
}
@media (max-width: 860px) {
    .is-madrasah-login { display: block; background: #0f172a; }
    .is-madrasah-login .welcome-panel { display: none; }
    .is-madrasah-login .login-layout { display: block; min-height: 100svh; }
    .is-madrasah-login .login-panel { width: 100%; max-width: 480px; min-height: 100svh; margin: auto; padding: 1.5rem; border: 0; border-radius: 0; background: #0f172a; }
    .login-mobile-brand { display: flex; align-items: center; gap: .6rem; margin-bottom: 3rem; color: #fff; }
    .login-mobile-brand img { width: 42px; height: 42px; object-fit: contain; }
    .login-mobile-brand strong { font-size: .85rem; line-height: 1.3; }
    .login-mobile-brand a { margin-left: auto; padding: .55rem .8rem; border: 1px solid #334155; border-radius: .5rem; font-size: .75rem; color: #cbd5e1; }
}

@media (min-width: 1024px) {
    .is-madrasah-login .login-layout { grid-template-columns: 7fr 5fr; }
    .is-madrasah-login .welcome-panel { padding: 48px; justify-content: flex-start; gap: 0; background: linear-gradient(125deg,#020617,#022c22,#0f172a); }
    .is-madrasah-login .school-brand { margin-bottom: 72px; }
    .is-madrasah-login .welcome-copy h1 { font-size: 36px; line-height: 1.25; }
    .is-madrasah-login .welcome-footer-links { margin-top: 48px; }
    .is-madrasah-login .login-panel { max-width: none; width: 100%; margin: 0; padding: 176px 48px 48px; align-self: stretch; }
    .is-madrasah-login.is-student-portal .login-panel { padding-top: 139px; }
    .is-madrasah-login .login-heading h2 { font-size: 30px; }
}
@media (min-width: 1280px) {
    .is-madrasah-login .login-layout { grid-template-columns: 3fr 2fr; }
    .is-madrasah-login .welcome-panel { padding: 64px; }
    .is-madrasah-login .school-brand { margin-bottom: 88px; }
    .is-madrasah-login .login-panel { padding-inline: 56px; }
}
@media (max-width: 1023px) {
    .is-madrasah-login { display: block; background: #0f172a; }
    .is-madrasah-login .welcome-panel { display: none; }
    .is-madrasah-login .login-layout { display: block; min-height: 100svh; }
    .is-madrasah-login .login-panel { width: 100%; max-width: 480px; min-height: 100svh; margin: auto; padding: 24px; border: 0; border-radius: 0; background: #0f172a; }
    .is-madrasah-login .login-mobile-brand { display: flex; align-items: center; gap: 10px; margin-bottom: 38px; color: #fff; }
    .is-madrasah-login .login-mobile-brand img { width: 42px; height: 42px; object-fit: contain; }
    .is-madrasah-login .login-mobile-brand strong { font-size: 13px; line-height: 1.3; }
    .is-madrasah-login .login-mobile-brand a { margin-left: auto; padding: 9px 12px; border: 1px solid #334155; border-radius: 8px; font-size: 12px; color: #cbd5e1; }
    .is-madrasah-login .login-heading h2 { font-size: 24px; }
}
</style>
