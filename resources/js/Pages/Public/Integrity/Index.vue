<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    ikm: {
        average_rating: number;
        total_responses: number;
        approved_feedback: {
            id: number;
            respondent_name: string;
            respondent_role: string;
            service_name: string;
            rating: number;
            comment?: string | null;
        }[];
    };
}>();

const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const areas = [
    'Manajemen Perubahan',
    'Penataan Tatalaksana',
    'Penataan Manajemen SDM',
    'Penguatan Akuntabilitas Kinerja',
    'Penguatan Pengawasan',
    'Peningkatan Kualitas Pelayanan Publik',
];

const form = useForm({
    respondent_name: '',
    respondent_role: 'umum',
    service_name: 'PTSP & Tata Usaha',
    rating: 5,
    comment: '',
});

const submit = () => {
    form.post('/zona-integritas/ikm', {
        preserveScroll: true,
        onSuccess: () => form.reset('respondent_name', 'comment'),
    });
};
</script>

<template>
    <Head :title="`Zona Integritas & IKM — ${school.name}`" />
    <main class="public-site public-portal-page" :style="themeStyle">
        <PublicHeader :school="school" />
        <section class="portal-container">
            <header class="portal-hero">
                <p class="eyebrow">Zona Integritas &amp; Indeks Kepuasan Masyarakat</p>
                <h1>Zona Integritas {{ school.name }}</h1>
                <p>Maklumat pelayanan, enam area perubahan, ringkasan IKM terverifikasi, serta kanal penilaian dan pengaduan masyarakat.</p>
            </header>

            <section class="zi-grid">
                <article class="zi-card">
                    <h2>Maklumat Pelayanan</h2>
                    <p>Kami berkomitmen menyelenggarakan pelayanan pendidikan dan tata usaha yang cepat, transparan, akuntabel, bebas pungutan liar, dan berorientasi kepuasan masyarakat.</p>
                    <ul class="zi-areas">
                        <li v-for="area in areas" :key="area">{{ area }}</li>
                    </ul>
                    <div class="zi-links">
                        <Link href="/pusat-unduh?category=sop" class="ui-button ui-button--secondary ui-button--md">Unduh SOP Layanan</Link>
                        <Link href="/e-layanan?q=Aduan" class="ui-button ui-button--secondary ui-button--md">Kanal Pengaduan</Link>
                    </div>
                </article>

                <article class="zi-card">
                    <h2>Ringkasan IKM Publik</h2>
                    <div class="ikm-metrics">
                        <div>
                            <strong>{{ ikm.average_rating.toFixed(2) }} / 5.00</strong>
                            <span>Rata-rata Kepuasan</span>
                        </div>
                        <div>
                            <strong>{{ ikm.total_responses }}</strong>
                            <span>Responden Terverifikasi</span>
                        </div>
                    </div>

                    <div v-if="ikm.approved_feedback.length" class="ikm-list">
                        <div v-for="item in ikm.approved_feedback" :key="item.id" class="ikm-item">
                            <div class="ikm-item__top">
                                <strong>{{ item.respondent_name }} ({{ item.respondent_role }})</strong>
                                <StatusBadge status="approved" :label="`${item.rating}/5`" size="sm" />
                            </div>
                            <small>{{ item.service_name }}</small>
                            <p v-if="item.comment">{{ item.comment }}</p>
                        </div>
                    </div>
                    <p v-else class="ikm-empty">Penilaian yang telah dimoderasi admin akan ditampilkan di sini.</p>
                </article>
            </section>

            <section class="zi-card">
                <h2>Formulir Penilaian Layanan (IKM)</h2>
                <form class="ikm-form" @submit.prevent="submit">
                    <FormField id="ikm-name" label="Nama Responden (Opsional)" :error="form.errors.respondent_name">
                        <template #default="{ inputId, describedBy }">
                            <input :id="inputId" v-model="form.respondent_name" type="text" :aria-describedby="describedBy" placeholder="Boleh dikosongkan" />
                        </template>
                    </FormField>

                    <FormField id="ikm-role" label="Kategori Responden" required :error="form.errors.respondent_role">
                        <template #default="{ inputId, describedBy }">
                            <select :id="inputId" v-model="form.respondent_role" :aria-describedby="describedBy" required>
                                <option value="umum">Masyarakat Umum</option>
                                <option value="wali">Orang Tua / Wali</option>
                                <option value="siswa">Siswa</option>
                                <option value="alumni">Alumni</option>
                                <option value="tamu">Tamu Instansi</option>
                            </select>
                        </template>
                    </FormField>

                    <FormField id="ikm-service" label="Layanan yang Dinilai" required :error="form.errors.service_name">
                        <template #default="{ inputId, describedBy }">
                            <input :id="inputId" v-model="form.service_name" type="text" :aria-describedby="describedBy" required />
                        </template>
                    </FormField>

                    <FormField id="ikm-rating" label="Penilaian (1–5)" required :error="form.errors.rating">
                        <template #default="{ inputId, describedBy }">
                            <select :id="inputId" v-model.number="form.rating" :aria-describedby="describedBy" required>
                                <option :value="5">5 — Sangat Baik</option>
                                <option :value="4">4 — Baik</option>
                                <option :value="3">3 — Cukup</option>
                                <option :value="2">2 — Kurang</option>
                                <option :value="1">1 — Sangat Kurang</option>
                            </select>
                        </template>
                    </FormField>

                    <FormField id="ikm-comment" label="Saran / Masukan" :error="form.errors.comment" class="ikm-form__full">
                        <template #default="{ inputId, describedBy }">
                            <textarea :id="inputId" v-model="form.comment" rows="3" :aria-describedby="describedBy" />
                        </template>
                    </FormField>

                    <div class="ikm-form__full">
                        <button type="submit" class="ui-button ui-button--primary ui-button--md" :disabled="form.processing">
                            {{ form.processing ? 'Mengirim...' : 'Kirim Penilaian' }}
                        </button>
                    </div>
                </form>
            </section>
        </section>
    </main>
</template>

<style scoped>
.public-portal-page { min-height: 100vh; background: var(--public-surface, #f5f8fb); color: var(--public-ink, #173044); }
.portal-container { width: min(1180px, calc(100% - 2rem)); margin-inline: auto; padding: 2.5rem 0 4rem; display: grid; gap: 1.5rem; }
.portal-hero h1 { margin: .4rem 0 .65rem; font-size: clamp(2rem, 4vw, 3rem); }
.portal-hero p { margin: 0; color: var(--public-muted, #526673); }
.zi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem; }
.zi-card { display: grid; gap: 1rem; padding: 1.35rem; background: #fff; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 16px; }
.zi-card h2 { margin: 0; font-size: 1.2rem; }
.zi-areas { margin: 0; padding-left: 1.2rem; display: grid; gap: .35rem; color: var(--public-muted, #526673); }
.zi-links { display: flex; flex-wrap: wrap; gap: .6rem; }
.ikm-metrics { display: grid; grid-template-columns: repeat(2, 1fr); gap: .85rem; }
.ikm-metrics > div { padding: .85rem; background: var(--public-surface, #f5f8fb); border-radius: 12px; display: grid; gap: .2rem; }
.ikm-metrics strong { font-size: 1.4rem; color: var(--public-primary, #3157a4); }
.ikm-metrics span { font-size: .8rem; color: var(--public-muted, #526673); }
.ikm-list { display: grid; gap: .65rem; }
.ikm-item { padding: .75rem; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 10px; display: grid; gap: .2rem; }
.ikm-item__top { display: flex; justify-content: space-between; gap: .5rem; align-items: center; }
.ikm-item p, .ikm-empty { margin: 0; color: var(--public-muted, #526673); font-size: .875rem; }
.ikm-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
.ikm-form__full { grid-column: 1 / -1; }
</style>
