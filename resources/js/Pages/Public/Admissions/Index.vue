<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

interface Requirement {
    label: string;
    is_required: boolean;
}

interface Track {
    id: number;
    name: string;
    code: string;
    quota: number;
    requirements: Requirement[];
}

interface Period {
    id: number;
    name: string;
    academic_year: string;
    level?: string | null;
    status: string;
    registration_start?: string | null;
    registration_end?: string | null;
    verification_start?: string | null;
    verification_end?: string | null;
    selection_start?: string | null;
    selection_end?: string | null;
    announcement_at?: string | null;
    re_registration_start?: string | null;
    re_registration_end?: string | null;
    tracks: Track[];
}

const props = defineProps<{
    school: { name: string; level?: string | null; address?: string | null; phone?: string | null; email?: string | null; logo_url?: string | null; theme?: { primary?: string | null } };
    periods: Period[];
}>();

const initial = computed(() => props.school.name.trim().charAt(0).toUpperCase() || 'S');
const dateFormatter = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
const formatDate = (value?: string | null) => value ? dateFormatter.format(new Date(`${value}T00:00:00`)) : 'Menunggu jadwal';
const statusLabel = (status: string) => ({
    registration_open: 'Pendaftaran dibuka',
    verification: 'Verifikasi berkas',
    selection: 'Tahap seleksi',
    announcement: 'Pengumuman',
    re_registration: 'Daftar ulang',
}[status] ?? 'Periode berjalan');
const statusTone = (status: string) => status === 'registration_open' ? 'is-open' : 'is-progress';
const totalQuota = (period: Period) => period.tracks.reduce((total, track) => total + Number(track.quota || 0), 0);
const hasContact = computed(() => Boolean(props.school.email || props.school.phone));
const registrationOpen = computed(() => props.periods.some((period) => period.status === 'registration_open'));
const currentPeriod = computed(() => props.periods[0] ?? null);
const statusAnnouncement = computed(() => registrationOpen.value
    ? 'Pendaftaran tersedia' + (currentPeriod.value?.registration_end ? ' sampai ' + formatDate(currentPeriod.value.registration_end) : '') + '.'
    : currentPeriod.value
        ? 'Periode ' + currentPeriod.value.academic_year + ' sedang berada pada tahap ' + statusLabel(currentPeriod.value.status).toLowerCase() + '.'
        : 'Saat ini sekolah belum membuka periode penerimaan siswa baru.');
const themeStyle = computed(() => publicThemeStyle(props.school.theme));
</script>

<template>
    <Head :title="`SPMB — ${school.name}`" />
    <main class="public-site public-admissions" :style="themeStyle">
        <PublicHeader :school="school" active="admissions" />

        <section class="admissions-status" aria-labelledby="admissions-title">
            <div class="status-copy">
                <span class="status-badge" :class="{ 'is-open': registrationOpen }">
                    {{ registrationOpen ? 'Pendaftaran dibuka' : 'Pendaftaran belum dibuka' }}
                </span>
                <h1 id="admissions-title">
                    {{ registrationOpen ? 'Penerimaan siswa baru sedang dibuka.' : 'Penerimaan siswa baru belum dibuka.' }}
                </h1>
                <p>{{ statusAnnouncement }}</p>
                <div v-if="currentPeriod" class="status-meta">
                    <span>{{ currentPeriod.name }}</span>
                    <span>{{ currentPeriod.level || school.level || 'Jenjang sekolah' }}</span>
                </div>
                <AppButton variant="primary" as="a" v-if="hasContact" class="primary-button" href="#kontak">Hubungi panitia</AppButton>
            </div>
            <aside class="status-panel" aria-label="Status penerimaan siswa baru">
                <span>Status SPMB</span>
                <strong>{{ registrationOpen ? 'Dibuka' : 'Belum dibuka' }}</strong>
                <small>{{ currentPeriod ? 'Tahun ajaran ' + currentPeriod.academic_year : 'Belum ada periode aktif' }}</small>
            </aside>
            <div class="school-building-art" aria-hidden="true">
                <img src="/images/school-building-hero.png" alt="" />
            </div>
        </section>

        <section id="periode" class="content-section" aria-labelledby="period-title">
            <div class="section-heading">
                <div><p class="eyebrow">Informasi SPMB</p><h2 id="period-title">Periode dan jalur penerimaan.</h2></div>
                <p v-if="periods.length">{{ periods.length }} periode tersedia.</p>
                <p v-else>Belum ada periode aktif.</p>
            </div>

            <div v-if="periods.length" class="period-grid">
                <article v-for="period in periods" :key="period.id" class="period-card">
                    <div class="period-card-head">
                        <div><span class="period-status" :class="statusTone(period.status)">{{ statusLabel(period.status) }}</span><h3>{{ period.name }}</h3><p>{{ period.level || school.level || 'Jenjang sekolah' }} · Tahun ajaran {{ period.academic_year }}</p></div>
                        <strong>{{ totalQuota(period) }}<small>kuota</small></strong>
                    </div>
                    <div class="date-grid">
                        <div><span>Pendaftaran</span><b>{{ formatDate(period.registration_start) }} — {{ formatDate(period.registration_end) }}</b></div>
                        <div><span>Verifikasi</span><b>{{ formatDate(period.verification_start) }} — {{ formatDate(period.verification_end) }}</b></div>
                        <div><span>Pengumuman</span><b>{{ formatDate(period.announcement_at) }}</b></div>
                        <div><span>Daftar ulang</span><b>{{ formatDate(period.re_registration_start) }} — {{ formatDate(period.re_registration_end) }}</b></div>
                    </div>
                    <div class="track-summary"><span v-for="track in period.tracks" :key="track.id">{{ track.name }} · {{ track.quota }} kuota</span></div>
                </article>
            </div>
            <div v-else class="empty-state"><strong>Penerimaan siswa baru belum dibuka.</strong><p>Jadwal, jalur, dan kuota akan ditampilkan setelah sekolah menerbitkan periode penerimaan.</p></div>
        </section>

        <section id="alur" class="content-section admissions-flow" aria-labelledby="flow-title">
            <div class="section-heading"><div><p class="eyebrow">Tahapan pendaftaran</p><h2 id="flow-title">Ikuti proses penerimaan siswa baru.</h2></div></div>
            <div class="flow-grid">
                <article><span>01</span><h3>Siapkan berkas</h3><p>Periksa dokumen yang diminta sesuai jalur pendaftaran.</p></article>
                <article><span>02</span><h3>Daftarkan diri</h3><p>Ikuti arahan panitia sekolah selama periode pendaftaran.</p></article>
                <article><span>03</span><h3>Verifikasi & seleksi</h3><p>Berkas dan kriteria diperiksa sesuai juknis yang berlaku.</p></article>
                <article><span>04</span><h3>Pengumuman & daftar ulang</h3></article>
            </div>
        </section>

        <footer id="kontak" class="public-footer">
            <div><p class="eyebrow">Kontak SPMB</p><h2>Hubungi panitia penerimaan.</h2></div>
            <div class="contact-list"><p v-if="school.address">{{ school.address }}</p><a v-if="school.phone" :href="`tel:${school.phone}`">{{ school.phone }}</a><a v-if="school.email" :href="`mailto:${school.email}`">{{ school.email }}</a><p v-if="!hasContact">Hubungi sekretariat sekolah untuk informasi pendaftaran.</p></div>
        </footer>
    </main>
</template>

<style scoped>
:global(body) { margin: 0; background: #fffdf8; }
.public-admissions { min-height: 100vh; padding: 1.4rem clamp(1rem, 4vw, 3rem); color: #173044; background: #fffdf8; }
.public-header, .admissions-hero, .content-section, .public-footer { width: min(1120px, 100%); margin-inline: auto; }
.admissions-hero { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(240px, .8fr); gap: 3rem; align-items: end; padding: clamp(4rem, 9vw, 7rem) 0 4rem; }
.admissions-hero h1 { max-width: 760px; margin: .9rem 0 1.2rem; font-family: var(--font-ui); font-size: clamp(2.8rem, 6vw, 5.7rem); font-weight: var(--weight-regular); letter-spacing: -.065em; line-height: .98; }
.hero-actions { display: flex; align-items: center; gap: 1.1rem; margin-top: 2rem; }
.hero-note { display: grid; gap: .45rem; padding: 1.4rem; color: #f5efe2; background: #123247; border-radius: 1rem 1rem 1rem 4.5rem; box-shadow: 0 16px 35px #183b5d1c; }
.hero-note span, .period-status { color: #d4a74b; font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .08em; text-transform: uppercase; }
.hero-note strong { font-family: var(--font-ui); font-size: 2.1rem; font-weight: var(--weight-regular); line-height: 1; }
.hero-note small { color: #c7d5d9; font-size: var(--type-md); }
.content-section { padding: 4rem 0 1rem; }
.section-heading { display: flex; align-items: end; justify-content: space-between; gap: 2rem; margin-bottom: 1.8rem; }
.section-heading h2, .public-footer h2 { max-width: 650px; margin: .6rem 0 0; font-family: var(--font-ui); font-size: clamp(2rem, 4vw, 3.5rem); font-weight: var(--weight-regular); letter-spacing: -.05em; line-height: 1.03; }
.section-heading > p { margin: 0; }
.period-grid { display: grid; gap: 1rem; }
.period-card { padding: 1.3rem; background: #fff; border: 1px solid #ded7c9; border-radius: .85rem; box-shadow: 0 12px 30px #183b5d0a; }
.period-card-head { display: flex; align-items: start; justify-content: space-between; gap: 1rem; }
.period-card h3 { margin: .4rem 0 .2rem; font-family: var(--font-ui); font-size: 1.55rem; font-weight: var(--weight-regular); }
.period-card-head p { margin: 0; color: #526673; font-size: var(--type-md); }
.period-card-head > strong { display: grid; color: #123247; font-family: var(--font-ui); font-size: 2.4rem; font-weight: var(--weight-regular); line-height: .9; text-align: right; }
.period-card-head > strong small { margin-top: .3rem; color: #526673; font-family: var(--font-ui); font-size: var(--type-meta); font-weight: var(--weight-bold); }
.period-status { display: inline-flex; padding: .3rem .55rem; color: #80601f; background: #f7ecd0; border-radius: 999px; font-size: var(--type-meta); letter-spacing: .04em; }
.period-status.is-open { color: #176c4b; background: #e6f5eb; }
.date-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .7rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e5ded2; }
.date-grid div { display: grid; gap: .25rem; }
.date-grid span { color: #9a7329; font-size: var(--type-meta); font-weight: var(--weight-bold); }
.date-grid b { color: #405762; font-size: var(--type-sm); font-weight: var(--weight-semibold); line-height: 1.4; }
.track-summary { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }
.track-summary span { padding: .4rem .6rem; color: #405762; background: #f4f0e7; border-radius: .45rem; font-size: var(--type-meta); font-weight: var(--weight-bold); }
.empty-state { padding: 3rem 1rem; color: #526673; border: 1px dashed #cfc8ba; border-radius: .85rem; text-align: center; }
.empty-state strong { display: block; color: #173044; font-family: var(--font-ui); font-size: 1.45rem; font-weight: var(--weight-regular); }
.empty-state p { margin: .45rem 0 0; font-size: var(--type-md); }
.flow-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
.flow-grid article { min-height: 180px; padding: 1.1rem; background: #f8f4ec; border: 1px solid #ded7c9; border-radius: .75rem; }
.flow-grid span { color: #9a7329; font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .08em; }
.flow-grid h3 { margin: 1.5rem 0 .4rem; font-family: var(--font-ui); font-size: 1.3rem; font-weight: var(--weight-regular); }
.flow-grid p { margin: 0; color: #526673; font-size: var(--type-md); line-height: 1.6; }
.public-footer { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-top: 5rem; padding: 3.5rem 0 4rem; border-top: 1px solid #cfc8ba; }
.contact-list { display: grid; align-content: start; gap: .45rem; }
.contact-list p { margin: 0; }
.contact-list a { color: #526673; text-decoration: none; }
.contact-list a:hover { color: #173044; text-decoration: underline; }
@media (max-width: 800px) { .admissions-hero { grid-template-columns: 1fr; gap: 2rem; }.date-grid, .flow-grid { grid-template-columns: repeat(2, 1fr); }.public-footer { grid-template-columns: 1fr; gap: 1.5rem; } }
@media (max-width: 560px) { .public-admissions { padding-inline: 1rem; }.public-header { align-items: flex-start; flex-direction: column; }.public-header nav { flex-wrap: wrap; }.admissions-hero { padding-top: 4rem; }.hero-actions { align-items: flex-start; flex-direction: column; }.period-card-head { display: grid; }.period-card-head > strong { text-align: left; }.date-grid, .flow-grid { grid-template-columns: 1fr; }.flow-grid article { min-height: 0; }.public-footer { margin-top: 3rem; padding-bottom: 3rem; } }
</style>

<style scoped>
.public-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
    min-height: 0;
    margin-top: .35rem;
    padding: .75rem 1rem;
    background: #fffdf8cc;
    border: 1px solid #e1dacc;
    border-radius: .9rem;
    box-shadow: 0 8px 22px #183b5d0a;
}

.brand {
    display: flex;
    align-items: center;
    gap: .7rem;
    color: #173044;
    font-size: var(--type-md);
    font-weight: var(--weight-bold);
    text-decoration: none;
}

.brand-mark {
    display: grid;
    place-items: center;
    width: 2.4rem;
    height: 2.4rem;
    overflow: hidden;
    color: #123247;
    background: #d4a74b;
    border-radius: .75rem .75rem .75rem .2rem;
    font-family: var(--font-display);
    font-size: 1.2rem;
}

.brand-mark img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: #fff;
}

.public-header nav {
    display: flex;
    align-items: center;
    gap: 1.1rem;
    font-size: var(--type-meta);
}

.public-header nav a {
    color: #6d7c82;
    text-decoration: none;
}

.public-header nav a:hover,
.public-header nav a:focus-visible {
    color: #173044;
}

.public-header nav .header-action {
    padding: .65rem .9rem;
    color: #f4f0e7;
    background: #123247;
    border-radius: .45rem;
    font-weight: var(--weight-bold);
}

.public-site nav a.header-action[aria-current='page'] {
    color: #fff !important;
}

.admissions-status {
    position: relative;
    display: grid;
    grid-template-columns: minmax(0, 1.25fr) minmax(250px, .75fr);
    gap: 4rem;
    align-items: end;
    min-height: 470px;
    width: min(1120px, 100%);
    margin-inline: auto;
    margin-top: 1rem;
    overflow: hidden;
    padding: clamp(4.5rem, 10vw, 7.5rem) 0 4rem;
    isolation: isolate;
    border-radius: 1.25rem;
    background: linear-gradient(115deg, #fbf8f1 0%, #f5f0e6 56%, #eee6d7 100%);
}

.admissions-status::after {
    position: absolute;
    z-index: 0;
    inset: 0;
    background: linear-gradient(90deg, #fbf8f1f2 0%, #fbf8f1b8 42%, #fbf8f100 78%);
    content: '';
    pointer-events: none;
}

.status-copy,
.status-panel {
    position: relative;
    z-index: 2;
}

.school-building-art {
    position: absolute;
    z-index: 1;
    right: 0;
    bottom: 0;
    left: 0;
    height: 100%;
    overflow: hidden;
    pointer-events: none;
}

.school-building-art img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center bottom;
    opacity: .34;
    mix-blend-mode: multiply;
}

.status-copy h1 {
    max-width: 760px;
    margin: 1rem 0 1.2rem;
    font-family: var(--font-ui);
    font-size: clamp(2.8rem, 6vw, 5.7rem);
    font-weight: var(--weight-regular);
    letter-spacing: -.065em;
    line-height: .98;
}

.status-copy > p {
    max-width: 600px;
    margin: 0;
    color: #526673;
    font-size: 1.05rem;
    line-height: 1.7;
}

.status-badge {
    display: inline-flex;
    padding: .38rem .65rem;
    color: #80601f;
    background: #f7ecd0;
    border-radius: 999px;
    font-size: var(--type-meta);
    font-weight: var(--weight-bold);
    letter-spacing: .06em;
    text-transform: uppercase;
}

.status-badge.is-open {
    color: #176c4b;
    background: #e6f5eb;
}

.status-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin: 1.5rem 0;
}

.status-meta span {
    padding: .45rem .65rem;
    color: #405762;
    background: #f4f0e7;
    border-radius: .45rem;
    font-size: var(--type-meta);
    font-weight: var(--weight-semibold);
}

.status-copy .primary-button {
    margin-top: .25rem;
}

.status-panel {
    display: grid;
    gap: .55rem;
    padding: 1.6rem;
    color: #f5efe2;
    background: #123247;
    border-radius: 1rem 1rem 1rem 4.5rem;
    box-shadow: 0 16px 35px #183b5d1c;
}

.status-panel span {
    color: #d4a74b;
    font-size: var(--type-meta);
    font-weight: var(--weight-bold);
    letter-spacing: .08em;
    text-transform: uppercase;
}

.status-panel strong {
    font-family: var(--font-ui);
    font-size: 2.5rem;
    font-weight: var(--weight-regular);
    line-height: 1;
}

.status-panel small {
    color: #c7d5d9;
    font-size: var(--type-md);
}

@media (max-width: 800px) {
    .admissions-status {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
}

@media (max-width: 560px) {
    .public-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .public-header nav {
        flex-wrap: wrap;
        justify-content: flex-start;
        gap: .8rem;
    }

    .admissions-status {
        padding-top: 4rem;
    }

    .status-copy h1 {
        font-size: clamp(2.7rem, 12vw, 4rem);
    }

    .school-building-art {
        height: 56%;
    }

    .school-building-art img {
        object-position: 68% bottom;
        opacity: .25;
    }
}
</style>
