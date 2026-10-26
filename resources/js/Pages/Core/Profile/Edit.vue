<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    profile: {
        name: string;
        username?: string;
        email: string;
        phone?: string;
        avatar_path?: string;
    };
}>();

const page = usePage<any>();
const form = useForm<{ name: string; email: string; phone: string; avatar: File | null }>({
    name: props.profile.name,
    email: props.profile.email,
    phone: props.profile.phone ?? '',
    avatar: null,
});

const initials = computed(() => form.name.trim().split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase());
const selectedFileName = computed(() => form.avatar?.name ?? 'Belum ada foto baru dipilih');

const selectAvatar = (event: Event) => {
    form.avatar = (event.target as HTMLInputElement).files?.[0] ?? null;
};

const submit = () => form.post('/profil', { forceFormData: true, preserveScroll: true });
</script>

<template>
    <Head title="Profil Saya" />

    <AppLayout title="Profil Saya">
        <main class="view profile-page">
            <section class="hero-card profile-header">
                <div>
                    <p class="eyebrow">Akun pribadi</p>
                    <h1>Profil saya</h1>
                    <p>Kelola foto, identitas kontak, dan keamanan akun Anda.</p>
                </div>
                <a class="secondary-button" href="/ganti-password">Ganti kata sandi</a>
            </section>

            <div v-if="page.props.flash?.message" class="notice success" role="status">
                {{ page.props.flash.message }}
            </div>

            <section class="panel profile-panel">
                <aside class="profile-identity">
                    <div class="avatar-preview">
                        <img
                            v-if="page.props.auth?.user?.avatar_url"
                            :src="page.props.auth.user.avatar_url"
                            alt="Foto profil"
                        />
                        <span v-else>{{ initials }}</span>
                    </div>

                    <div class="identity-copy">
                        <h2>{{ form.name }}</h2>
                        <p>{{ profile.email }}</p>
                    </div>

                    <label class="avatar-control">
                        <span>Foto profil</span>
                        <span class="file-picker">
                            <span class="file-button">Pilih foto</span>
                            <small>{{ selectedFileName }}</small>
                            <input type="file" accept="image/jpeg,image/png,image/webp" @change="selectAvatar" />
                        </span>
                        <small>JPG, PNG, atau WebP; maksimum 2 MB.</small>
                        <em v-if="form.errors.avatar">{{ form.errors.avatar }}</em>
                    </label>
                </aside>

                <form class="profile-form" @submit.prevent="submit">
                    <div class="form-heading">
                        <h2>Informasi akun</h2>
                        <p>Pastikan nama dan kontak yang digunakan selalu terbaru.</p>
                    </div>

                    <div class="form-grid">
                        <label>
                            <span>Nama lengkap</span>
                            <input v-model="form.name" required autocomplete="name" />
                            <em v-if="form.errors.name">{{ form.errors.name }}</em>
                        </label>

                        <label>
                            <span>Email</span>
                            <input v-model="form.email" type="email" required autocomplete="email" />
                            <em v-if="form.errors.email">{{ form.errors.email }}</em>
                        </label>

                        <label>
                            <span>Username</span>
                            <input :value="profile.username" disabled />
                            <small>Username dikelola administrator.</small>
                        </label>

                        <label>
                            <span>Nomor kontak</span>
                            <input v-model="form.phone" type="tel" autocomplete="tel" placeholder="Contoh: 0812 3456 7890" />
                            <em v-if="form.errors.phone">{{ form.errors.phone }}</em>
                        </label>
                    </div>

                    <div class="form-actions">
                        <span v-if="form.isDirty" class="change-indicator">Ada perubahan yang belum disimpan</span>
                        <button class="primary-button" type="submit" :disabled="form.processing || !form.isDirty">
                            {{ form.processing ? 'Menyimpan…' : 'Simpan profil' }}
                        </button>
                    </div>
                </form>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.profile-page {
    display: grid;
    gap: 1.5rem;
    max-width: 1480px;
    margin: 0 auto;
}

.profile-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
}

.profile-panel {
    display: grid;
    grid-template-columns: minmax(240px, 30%) minmax(0, 1fr);
    padding: 0;
    overflow: hidden;
}

.profile-identity {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.25rem;
    padding: clamp(1.5rem, 3vw, 2.25rem);
    border-right: 1px solid var(--line);
    background: var(--surface-subtle);
    text-align: center;
}

.avatar-preview {
    display: grid;
    width: 112px;
    height: 112px;
    place-items: center;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--primary) 20%, var(--line));
    border-radius: 30px;
    background: var(--primary-soft);
    color: var(--primary);
    font-size: 1.65rem;
    font-weight: 900;
}

.avatar-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.identity-copy h2,
.form-heading h2 {
    margin: 0;
    color: var(--ink);
    font-size: 1rem;
    font-weight: 850;
}

.identity-copy p,
.form-heading p {
    margin: .3rem 0 0;
    color: var(--ink-soft);
    font-size: .78rem;
    line-height: 1.5;
}

.avatar-control {
    display: grid;
    width: 100%;
    gap: .45rem;
    color: var(--ink);
    font-size: .78rem;
    font-weight: 800;
    text-align: left;
}

.file-picker {
    position: relative;
    display: grid;
    gap: .45rem;
    padding: .65rem;
    border: 1px dashed var(--line-strong, var(--line));
    border-radius: .7rem;
    background: var(--surface);
}

.file-picker input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
    opacity: 0;
}

.file-button {
    width: fit-content;
    padding: .42rem .65rem;
    border-radius: .5rem;
    background: var(--primary-soft);
    color: var(--primary);
    font-size: .75rem;
    font-weight: 800;
}

.avatar-control small,
.profile-form small,
.file-picker small {
    color: var(--ink-soft);
    font-size: .7rem;
    font-weight: 500;
    overflow-wrap: anywhere;
}

.avatar-control em,
.profile-form em {
    color: var(--red);
    font-size: .72rem;
    font-style: normal;
}

.profile-form {
    display: grid;
    align-content: start;
    gap: 1.5rem;
    padding: clamp(1.5rem, 3vw, 2.25rem);
}

.form-heading {
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--line);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1.1rem 1.25rem;
}

.profile-form label {
    display: grid;
    align-content: start;
    gap: .4rem;
    color: var(--ink);
    font-size: .78rem;
    font-weight: 800;
}

.profile-form input {
    min-height: 44px;
    width: 100%;
    border: 1px solid var(--line);
    border-radius: .7rem;
    background: var(--surface);
    color: var(--ink);
    padding: .7rem .8rem;
}

.profile-form input:disabled {
    background: var(--surface-subtle);
    color: var(--ink-soft);
    cursor: not-allowed;
}

.profile-form input:focus-visible,
.file-picker:focus-within {
    outline: 3px solid color-mix(in srgb, var(--primary) 30%, transparent);
    outline-offset: 2px;
}

.form-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 1.25rem;
    border-top: 1px solid var(--line);
}

.change-indicator {
    color: var(--ink-soft);
    font-size: .75rem;
}

@media (max-width: 900px) {
    .profile-panel {
        grid-template-columns: 1fr;
    }

    .profile-identity {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        border-right: 0;
        border-bottom: 1px solid var(--line);
        text-align: left;
    }

    .avatar-control {
        grid-column: 1 / -1;
    }
}

@media (max-width: 640px) {
    .profile-page {
        gap: 1rem;
    }

    .profile-header {
        align-items: stretch;
        flex-direction: column;
    }

    .profile-header .secondary-button {
        width: 100%;
        justify-content: center;
    }

    .profile-identity {
        grid-template-columns: 72px minmax(0, 1fr);
        padding: 1.1rem;
    }

    .avatar-preview {
        width: 72px;
        height: 72px;
        border-radius: 20px;
        font-size: 1.2rem;
    }

    .profile-form {
        padding: 1.1rem;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-actions {
        align-items: stretch;
        flex-direction: column;
    }

    .form-actions .primary-button {
        width: 100%;
        justify-content: center;
    }
}
</style>
