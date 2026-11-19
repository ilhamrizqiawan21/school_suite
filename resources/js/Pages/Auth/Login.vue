<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

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
                    <p class="welcome-kicker">Portal warga sekolah</p>
                    <h1>Semua kebutuhan belajar, dalam satu tempat.</h1>
                    <ul class="welcome-points">
                        <li>Materi &amp; tugas</li>
                        <li>Jadwal pelajaran</li>
                        <li>Nilai &amp; rapor</li>
                        <li>Pengumuman sekolah</li>
                    </ul>
                </div>

                <a class="back-home" href="/">← Kembali ke situs sekolah</a>
            </aside>

            <section class="login-panel" aria-labelledby="login-title">
                <header class="login-heading">
                    <p class="login-kicker">Akun sekolah</p>
                    <h2 id="login-title">Masuk ke portal</h2>
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

                    <AppButton variant="primary" size="lg" :loading="form.processing" type="submit" class="submit-button" :disabled="form.processing"><template #icon><svg v-if="form.processing" class="spinner" aria-hidden="true" fill="none" viewBox="0 0 24 24">
                            <circle opacity=".25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path opacity=".8" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                        </svg></template>

                        <span>{{ form.processing ? 'Memproses…' : 'Masuk' }}</span>
                    </AppButton>
                </form>
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
</style>
