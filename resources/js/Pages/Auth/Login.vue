<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const props=defineProps<{status?:string;branding?:{school_name:string;logo_url?:string|null;favicon_url?:string|null;theme_primary?:string}|null}>();

const form = useForm({
    identity: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const schoolName = computed(() => props.branding?.school_name || 'Portal Sekolah');
const schoolInitial = computed(() => schoolName.value.trim().charAt(0).toUpperCase() || 'S');
onMounted(() => {
    // Keep the branded login surface light; the saved preference is restored by AppLayout after sign-in.
    document.documentElement.dataset.theme = 'light';

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
    <main class="login-page" :style="{ '--login-primary': branding?.theme_primary || '#3157a4' }">
        <Head :title="`Masuk — ${schoolName}`" />
        <section class="login-layout" aria-label="Login portal sekolah">
            <aside class="welcome-panel">
                <div class="school-brand">
                    <img v-if="branding?.logo_url" :src="branding.logo_url" :alt="`Logo ${schoolName}`" />
                    <span v-else class="brand-fallback" aria-hidden="true">{{ schoolInitial }}</span>
                    <span class="brand-copy"><strong>{{ schoolName }}</strong><small>School Suite</small></span>
                </div>
                <div class="welcome-copy">
                    <p class="welcome-kicker">Portal sekolah</p>
                    <h1>Selamat datang kembali.</h1>
                    <p>Masuk untuk melanjutkan aktivitas dan layanan sekolah Anda.</p>
                </div>
                <p class="welcome-footnote">Akses resmi untuk warga sekolah</p>
                <span class="welcome-orbit orbit-one" aria-hidden="true"></span>
                <span class="welcome-orbit orbit-two" aria-hidden="true"></span>
            </aside>

            <section class="login-panel" aria-labelledby="login-title">
                <header class="login-heading">
                    <p class="login-kicker">Akun sekolah</p>
                    <h2 id="login-title">Masuk ke portal</h2>
                    <p>Gunakan email atau nama pengguna Anda.</p>
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
                            placeholder="nama@sekolah.sch.id"
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

                    <button type="submit" class="submit-button" :disabled="form.processing">
                        <svg v-if="form.processing" class="spinner" aria-hidden="true" fill="none" viewBox="0 0 24 24">
                            <circle opacity=".25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path opacity=".8" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                        </svg>
                        <span>{{ form.processing ? 'Memproses…' : 'Masuk' }}</span>
                    </button>
                </form>
                <p class="system-note">Layanan digital {{ schoolName }}</p>
            </section>
        </section>
    </main>
</template>

<style scoped>
.login-page {
    --login-ink: #173044;
    --login-muted: #526673;
    --login-paper: #f4f0e7;
    min-height: 100vh;
    min-height: 100svh;
    display: grid;
    place-items: center;
    padding: clamp(1rem, 4vw, 3rem);
    color: var(--login-ink);
    background: var(--login-paper);
    color-scheme: light;
    font-family: var(--font-ui);
}

.login-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.02fr) minmax(340px, .88fr);
    width: min(100%, 1080px);
    min-height: 620px;
    overflow: hidden;
    background: #fffdf8;
    border: 1px solid #ded8cc;
    border-radius: 1.5rem;
    box-shadow: 0 24px 60px rgb(23 48 68 / 9%);
}

.welcome-panel {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-width: 0;
    overflow: hidden;
    padding: clamp(2rem, 5vw, 4rem);
    color: #fffdf8;
    background: var(--login-primary);
}

.school-brand { position: relative; z-index: 1; display: flex; align-items: center; gap: .85rem; min-width: 0; }
.school-brand img, .brand-fallback { display: grid; place-items: center; width: 3.25rem; height: 3.25rem; flex: 0 0 auto; padding: .35rem; object-fit: contain; color: var(--login-ink); background: #fffdf8; border-radius: 1rem 1rem 1rem .35rem; }
.brand-fallback { font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; }
.brand-copy { display: grid; gap: .2rem; min-width: 0; }
.brand-copy strong { overflow-wrap: anywhere; font-size: .95rem; line-height: 1.35; }
.brand-copy small { color: rgb(255 253 248 / 75%); font-size: .75rem; }
.welcome-copy { position: relative; z-index: 1; max-width: 470px; margin: 4rem 0; }
.welcome-kicker, .login-kicker { margin: 0; color: #f0d28a; font-size: .75rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.welcome-copy h1 { max-width: 9ch; margin: 1rem 0 1.2rem; font-family: var(--font-display); font-size: clamp(3rem, 5.5vw, 5.4rem); font-weight: 400; letter-spacing: -.065em; line-height: .98; }
.welcome-copy > p:last-child { max-width: 330px; margin: 0; color: rgb(255 253 248 / 82%); font-size: 1rem; line-height: 1.75; }
.welcome-footnote { position: relative; z-index: 1; margin: 0; color: rgb(255 253 248 / 76%); font-size: .78rem; }
.welcome-orbit { position: absolute; right: -11rem; bottom: -13rem; width: 29rem; height: 29rem; border: 1px solid rgb(255 253 248 / 20%); border-radius: 50%; pointer-events: none; }
.orbit-two { right: -6rem; bottom: -17rem; width: 25rem; height: 25rem; border-color: rgb(240 210 138 / 35%); }

.login-panel { align-self: center; width: 100%; max-width: 470px; margin-inline: auto; padding: clamp(2rem, 5vw, 4rem); }
.login-heading { margin-bottom: 2rem; }
.login-kicker { color: var(--login-primary); }
.login-heading h2 { margin: .65rem 0 .45rem; font-family: var(--font-display); font-size: clamp(2rem, 3vw, 2.8rem); font-weight: 400; letter-spacing: -.045em; line-height: 1.08; }
.login-heading > p:last-child { margin: 0; color: var(--login-muted); font-size: .92rem; line-height: 1.6; }
.status-message { margin-bottom: 1.25rem; padding: .85rem 1rem; color: #17613c; background: #edf8f1; border: 1px solid #b9e3c8; border-radius: .7rem; font-size: .85rem; line-height: 1.5; }
.login-form { display: grid; gap: 1.25rem; }
.form-field { display: grid; gap: .5rem; min-width: 0; }
.form-field label { color: var(--login-ink); font-size: .85rem; font-weight: 700; }
.form-field input { width: 100%; min-height: 3.15rem; padding: .8rem .9rem; color: var(--login-ink); background: #fff; border: 1px solid #c9d1d6; border-radius: .7rem; font: inherit; font-size: .92rem; transition: border-color .15s ease, box-shadow .15s ease; }
.form-field input::placeholder { color: #778891; }
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
.password-toggle { position: absolute; top: 50%; right: .4rem; display: grid; place-items: center; width: 2.5rem; height: 2.5rem; padding: 0; color: #526673; background: transparent; border: 0; border-radius: .55rem; cursor: pointer; transform: translateY(-50%); }
.password-toggle:hover { color: var(--login-primary); background: #edf2fb; }
.eye-icon { width: 1.25rem; height: 1.25rem; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.8; }
.password-toggle:focus-visible, .remember-option input:focus-visible, .submit-button:focus-visible { outline: 3px solid color-mix(in srgb, var(--login-primary) 35%, transparent); outline-offset: 3px; }
.field-error { margin: 0; color: #a32118; font-size: .8rem; line-height: 1.5; }
.remember-option { display: inline-flex; align-items: center; gap: .65rem; width: fit-content; min-height: 44px; color: var(--login-muted); font-size: .84rem; cursor: pointer; }
.remember-option input { width: 1.1rem; height: 1.1rem; accent-color: var(--login-primary); }
.submit-button { display: flex; align-items: center; justify-content: center; gap: .6rem; width: 100%; min-height: 3.2rem; margin-top: .15rem; padding: .8rem 1rem; color: #fff; background: var(--login-primary); border: 1px solid var(--login-primary); border-radius: .7rem; font: inherit; font-size: .95rem; font-weight: 800; cursor: pointer; transition: filter .15s ease, transform .15s ease; }
.submit-button:hover:not(:disabled) { filter: brightness(.92); transform: translateY(-1px); }
.submit-button:disabled { opacity: .65; cursor: wait; }
.spinner { width: 1.1rem; height: 1.1rem; animation: spin 1s linear infinite; }
.system-note { margin: 1.8rem 0 0; padding-top: 1rem; color: #72818a; border-top: 1px solid #e6e1d8; font-size: .76rem; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 760px) {
    .login-page { align-items: start; padding: clamp(.75rem, 4vw, 1.5rem); }
    .login-layout { grid-template-columns: 1fr; min-height: 0; }
    .welcome-panel { min-height: 250px; padding: 1.5rem; }
    .school-brand img, .brand-fallback { width: 2.75rem; height: 2.75rem; }
    .welcome-copy { margin: 2.4rem 0 1.25rem; }
    .welcome-copy h1 { max-width: 12ch; margin: .65rem 0 .55rem; font-size: clamp(2.5rem, 11vw, 3.5rem); }
    .welcome-copy > p:last-child { max-width: 34ch; font-size: .9rem; }
    .welcome-footnote { font-size: .72rem; }
    .welcome-orbit { right: -14rem; bottom: -19rem; }
    .orbit-two { right: -11rem; bottom: -22rem; }
    .login-panel { max-width: none; padding: 1.75rem 1.5rem 1.5rem; }
    .login-heading { margin-bottom: 1.5rem; }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
}
</style>

