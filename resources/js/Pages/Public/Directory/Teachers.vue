<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    teachers: { id: number; name: string }[];
    filters: { q?: string };
}>();

const search = ref(props.filters.q ?? '');
const themeStyle = computed(() => publicThemeStyle(props.school.theme));

const submitSearch = () => {
    router.get('/direktori-gtk', { q: search.value || undefined }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head :title="`Direktori GTK — ${school.name}`" />
    <main class="public-site public-portal-page" :style="themeStyle">
        <PublicHeader :school="school" active="profile" />
        <section class="portal-container">
            <header class="madrasah-profile-hero portal-hero">
                <p class="eyebrow">Direktori Guru &amp; Tenaga Kependidikan</p>
                <h1>Direktori GTK {{ school.name }}</h1>
                <p>Daftar pendidik dan tenaga kependidikan aktif. Hanya menampilkan atribut publik yang disetujui.</p>
            </header>

            <form class="portal-search-form" @submit.prevent="submitSearch">
                <label class="sr-only" for="gtk-search">Cari nama GTK</label>
                <input id="gtk-search" v-model="search" type="search" class="portal-search-input" placeholder="Cari nama guru atau tenaga kependidikan..." />
                <button type="submit" class="ui-button ui-button--primary ui-button--md">Cari</button>
            </form>

            <div v-if="teachers.length" class="gtk-grid">
                <article v-for="teacher in teachers" :key="teacher.id" class="gtk-card">
                    <span class="gtk-avatar" aria-hidden="true">{{ teacher.name.charAt(0).toUpperCase() }}</span>
                    <div>
                        <strong>{{ teacher.name }}</strong>
                        <small>Pendidik / Tenaga Kependidikan Aktif</small>
                    </div>
                </article>
            </div>

            <StateFeedback v-else state="empty" title="Data GTK belum ditemukan" description="Coba ubah kata kunci pencarian Anda." />
        </section>
    </main>
</template>

<style scoped>
.public-portal-page { min-height: 100vh; background: var(--public-surface, #f5f8fb); color: var(--public-ink, #173044); }
.portal-container { width: min(1180px, calc(100% - 2rem)); margin-inline: auto; padding: 2.5rem 0 4rem; display: grid; gap: 1.5rem; }
.portal-hero h1 { margin: .4rem 0 .65rem; font-size: clamp(2rem, 4vw, 3rem); }
.portal-hero p { margin: 0; color: var(--public-muted, #526673); }
.portal-search-form { display: flex; gap: .65rem; flex-wrap: wrap; }
.portal-search-input { flex: 1 1 260px; min-height: 44px; padding: .6rem .9rem; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 10px; background: #fff; font: inherit; }
.gtk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; }
.gtk-card { display: flex; align-items: center; gap: .85rem; padding: 1rem; background: #fff; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 14px; }
.gtk-avatar { display: grid; place-items: center; width: 44px; height: 44px; border-radius: 999px; background: color-mix(in srgb, var(--public-primary, #3157a4) 14%, transparent); color: var(--public-primary, #3157a4); font-weight: 700; flex: 0 0 auto; }
.gtk-card strong { display: block; font-size: .95rem; }
.gtk-card small { color: var(--public-muted, #526673); }
</style>
