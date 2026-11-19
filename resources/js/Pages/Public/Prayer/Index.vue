<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    prayerSchedule: {
        timezone: string;
        date: string;
        location: string;
        source: string;
        is_fallback: boolean;
        next_prayer: { name: string; time: string };
        times: { name: string; time: string }[];
    };
    visitStats: { today: number; month: number; total: number };
}>();

const themeStyle = computed(() => publicThemeStyle(props.school.theme));
</script>

<template>
    <Head :title="`Jadwal Salat & Statistik — ${school.name}`" />
    <main class="public-site public-portal-page" :style="themeStyle">
        <PublicHeader :school="school" />
        <section class="portal-container">
            <header class="portal-hero">
                <p class="eyebrow">Jadwal Salat &amp; Statistik Portal</p>
                <h1>Jadwal Salat Wilayah {{ prayerSchedule.location }}</h1>
                <p>Menggunakan zona waktu sekolah ({{ prayerSchedule.timezone }}) untuk tanggal {{ prayerSchedule.date }}.</p>
            </header>

            <article class="prayer-card">
                <div class="prayer-card__head">
                    <div>
                        <small>Waktu berikutnya</small>
                        <h2>{{ prayerSchedule.next_prayer.name }} · {{ prayerSchedule.next_prayer.time }}</h2>
                    </div>
                    <StatusBadge
                        :status="prayerSchedule.is_fallback ? 'warning' : 'active'"
                        :label="prayerSchedule.is_fallback ? 'Jadwal cadangan lokal' : 'Jadwal sekolah aktif'"
                    />
                </div>

                <div class="prayer-grid">
                    <div v-for="slot in prayerSchedule.times" :key="slot.name" class="prayer-slot">
                        <span>{{ slot.name }}</span>
                        <strong>{{ slot.time }}</strong>
                    </div>
                </div>
            </article>

            <article class="prayer-card">
                <h2>Statistik Kunjungan Anonim</h2>
                <p class="stats-note">Penghitung agregat tanpa menyimpan data pribadi atau identitas pengunjung.</p>
                <div class="stats-grid">
                    <div class="prayer-slot"><span>Hari Ini</span><strong>{{ visitStats.today }}</strong></div>
                    <div class="prayer-slot"><span>Bulan Ini</span><strong>{{ visitStats.month }}</strong></div>
                    <div class="prayer-slot"><span>Total Kunjungan</span><strong>{{ visitStats.total }}</strong></div>
                </div>
            </article>
        </section>
    </main>
</template>

<style scoped>
.public-portal-page { min-height: 100vh; background: var(--public-surface, #f5f8fb); color: var(--public-ink, #173044); }
.portal-container { width: min(1180px, calc(100% - 2rem)); margin-inline: auto; padding: 2.5rem 0 4rem; display: grid; gap: 1.5rem; }
.portal-hero h1 { margin: .4rem 0 .65rem; font-size: clamp(2rem, 4vw, 3rem); }
.portal-hero p { margin: 0; color: var(--public-muted, #526673); }
.prayer-card { display: grid; gap: 1rem; padding: 1.35rem; background: #fff; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 16px; }
.prayer-card__head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .75rem; }
.prayer-card__head h2 { margin: .15rem 0 0; }
.prayer-grid, .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: .85rem; }
.prayer-slot { padding: .9rem; background: var(--public-surface, #f5f8fb); border-radius: 12px; display: grid; gap: .25rem; }
.prayer-slot span { font-size: .8rem; color: var(--public-muted, #526673); }
.prayer-slot strong { font-size: 1.25rem; color: var(--public-primary, #3157a4); }
.stats-note { margin: 0; color: var(--public-muted, #526673); font-size: .875rem; }
</style>
