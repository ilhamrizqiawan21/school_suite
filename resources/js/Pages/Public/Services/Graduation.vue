<script setup lang="ts">
import { usePublicContentText } from '@/composables/usePublicContentText';
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { useForm } from '@inertiajs/vue3';
import Head from '@/components/PublicHead.vue';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
const publicText = usePublicContentText();

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    contentBlocks?: Record<string, Record<string, any>>;
    schedule?: { released: boolean; release_at?: string | null; title?: string | null; timezone?: string; academic_year?: string | null };
    result?: {
        found: boolean;
        released: boolean;
        release_at?: string | null;
        message: string;
        record?: {
            id: number;
            batch_title: string;
            student_name: string;
            nis: string;
            nisn?: string | null;
            decision: string;
            skl_number: string;
            average_score?: number | null;
            has_skl_pdf: boolean;
            original_collected_at?: string | null;
        };
    } | null;
}>();

const themeStyle = computed(() => publicThemeStyle(props.school.theme));

const form = useForm({
    nisn: '',
    pin: '',
});

const activeTab = ref<'check' | 'schedule' | 'help'>('check');
const now = ref(Date.now());
const releaseTime = computed(() => Date.parse(props.schedule?.release_at || ''));
const portalOpen = computed(() => Boolean(props.schedule?.released) || (Number.isFinite(releaseTime.value) && now.value >= releaseTime.value));
const releaseLabel = computed(() => Number.isFinite(releaseTime.value) ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'full', timeStyle: 'short', timeZone: props.schedule?.timezone || 'Asia/Makassar' }).format(new Date(releaseTime.value)) : null);
let clock: ReturnType<typeof setInterval> | undefined;
onMounted(() => { clock = setInterval(() => { now.value = Date.now(); }, 1000); });
onBeforeUnmount(() => clearInterval(clock));
const remaining = computed(() => {
    if (!props.schedule?.release_at) return null;
    const seconds = Math.max(0, Math.floor((new Date(props.schedule.release_at).getTime() - now.value) / 1000));
    return [
        { label: publicText("copy.vue_services_graduation", "text_12001", "Hari"), value: Math.floor(seconds / 86400) },
        { label: publicText("copy.vue_services_graduation", "text_12002", "Jam"), value: Math.floor(seconds / 3600) % 24 },
        { label: publicText("copy.vue_services_graduation", "text_12003", "Menit"), value: Math.floor(seconds / 60) % 60 },
        { label: publicText("copy.vue_services_graduation", "text_12004", "Detik"), value: seconds % 60 },
    ];
});

const submit = () => {
    form.post('/layanan/kelulusan', { preserveScroll: true });
};
const countdownParts = computed(() => remaining.value ?? ['Hari', 'Jam', 'Menit', 'Detik'].map((label) => ({ label, value: null })));
</script>

<template>
    <Head :title="`Kelulusan &amp; SKL — ${school.name}`" />

    <div class="min-h-screen bg-[#f8f6f0]" data-madrasah-service="graduation" :style="themeStyle">
        <PublicHeader :school="school" />

        <main class="mx-auto max-w-3xl px-4 py-10 space-y-6">
            <div v-if="contentBlocks?.['graduation.hero']?.visible !== false" class="madrasah-service-hero">
                <p class="madrasah-service-label"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> {{ contentBlocks?.['graduation.hero']?.badge ?? 'Pengumuman Kelulusan Resmi' }}</p>
                <h1 class="text-2xl font-bold text-slate-900">{{ school.name }}</h1>
                <p class="graduation-year">{{ publicText("copy.vue_services_graduation", "text_9001", "Tahun Pelajaran") }} {{ schedule?.academic_year || (contentBlocks?.['graduation.hero']?.academic_year_label ?? '2026/2027') }}</p>
                <p class="mt-1 text-sm text-slate-600">
                    {{ contentBlocks?.['graduation.hero']?.description ?? 'Layanan resmi pengecekan status kelulusan peserta didik dan pengunduhan Surat Keterangan Lulus (SKL) digital mandiri secara online.' }}
                </p>
            </div>

            <div class="parity-graduation-tabs" role="tablist" :aria-label="publicText('copy.vue_services_graduation', 'aria_label_021', 'Portal kelulusan')">
                <button id="graduation-tab-check" role="tab" :aria-selected="activeTab === 'check'" aria-controls="graduation-panel-check" @click="activeTab = 'check'">{{ publicText("copy.vue_services_graduation", "text_001", "Cek Kelulusan Siswa") }}</button>
                <button id="graduation-tab-schedule" role="tab" :aria-selected="activeTab === 'schedule'" aria-controls="graduation-panel-schedule" @click="activeTab = 'schedule'">{{ publicText("copy.vue_services_graduation", "text_002", "Jadwal & Hitung Mundur") }}</button>
                <button id="graduation-tab-help" role="tab" :aria-selected="activeTab === 'help'" aria-controls="petunjuk-skl" @click="activeTab = 'help'">{{ publicText("copy.vue_services_graduation", "text_003", "Petunjuk & Syarat SKL") }}</button>
            </div>

            <section v-if="activeTab === 'schedule' || (activeTab === 'check' && !portalOpen)"
                :id="activeTab === 'check' ? 'graduation-panel-check' : 'graduation-panel-schedule'" role="tabpanel" :aria-labelledby="activeTab === 'check' ? 'graduation-tab-check' : 'graduation-tab-schedule'" class="parity-graduation-state">
                <span class="parity-graduation-orb" aria-hidden="true"><i :class="portalOpen ? 'fa-solid fa-lock-open' : 'fa-solid fa-lock'"></i></span>
                <h2>{{ portalOpen ? 'Portal Kelulusan Sudah Dibuka' : 'Portal Kelulusan Belum Dibuka' }}</h2>
                <p class="graduation-state-description">{{ portalOpen ? 'Pengumuman kelulusan sudah tersedia. Silakan periksa hasil melalui tab Cek Kelulusan Siswa.' : 'Akses pengecekan status kelulusan saat ini masih terkunci dan akan dibuka secara serentak pada jadwal resmi berikut:' }}</p>
                <p class="graduation-release-date"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i>{{ releaseLabel || 'Jadwal pengumuman belum ditetapkan oleh sekolah' }}</p>
                <div v-if="!portalOpen" class="graduation-countdown-panel" role="timer" :aria-label="publicText('copy.vue_services_graduation', 'aria_label_022', 'Hitung mundur pembukaan portal kelulusan')">
                    <p class="graduation-countdown-title"><span></span>{{ publicText("copy.vue_services_graduation", "text_004", "Hitung Mundur Waktu Buka Akses") }}</p>
                    <div class="parity-countdown"><div v-for="part in countdownParts" :key="part.label"><strong>{{ part.value === null ? '—' : String(part.value).padStart(2, '0') }}</strong><span>{{ part.label }}</span></div></div>
                    <p v-if="!releaseLabel" class="graduation-countdown-note">{{ publicText("copy.vue_services_graduation", "text_005", "Hitung mundur akan aktif setelah jadwal resmi diterbitkan.") }}</p>
                </div>
                <p v-if="releaseLabel && !portalOpen" class="graduation-state-note">{{ publicText("copy.vue_services_graduation", "text_006", "Formulir pencarian kelulusan akan otomatis terbuka saat hitungan mundur mencapai") }} <b>00:00:00</b> {{ publicText("copy.vue_services_graduation", "text_007", "tanpa perlu me-refresh halaman.") }}</p>
            </section>

            <form v-if="activeTab === 'check' && portalOpen" id="graduation-panel-check" role="tabpanel" aria-labelledby="graduation-tab-check" @submit.prevent="submit" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                <h2 id="graduation-check-title" class="service-form-title">{{ publicText("copy.vue_services_graduation", "text_008", "Cek Hasil Kelulusan") }}</h2>
                <div class="grid gap-3 md:grid-cols-2">
                    <FormField id="grad-nisn" :label="publicText('copy.vue_services_graduation', 'label_10001', 'NISN')" :error="form.errors.nisn" required>
                        <input id="grad-nisn" v-model="form.nisn" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="grad-pin" :label="publicText('copy.vue_services_graduation', 'label_10002', 'PIN Kelulusan')" :error="form.errors.pin">
                        <input id="grad-pin" v-model="form.pin" type="password" class="w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm" />
                    </FormField>
                </div>
                <AppButton type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Memeriksa...' : 'Cek Hasil Kelulusan' }}
                </AppButton>
            </form>

            <div v-if="result" class="rounded-2xl border p-6 text-sm space-y-3" :class="result.found && result.released ? 'border-emerald-200 bg-emerald-50 text-emerald-950' : 'border-amber-200 bg-amber-50 text-amber-950'">
                <p class="font-bold">{{ result.message }}</p>
                <div v-if="result.released && result.record" class="space-y-1 text-xs sm:text-sm">
                    <p>{{ publicText("copy.vue_services_graduation", "text_009", "Program:") }} <strong>{{ result.record.batch_title }}</strong></p>
                    <p>{{ publicText("copy.vue_services_graduation", "text_010", "Nama:") }} <strong>{{ result.record.student_name }}</strong> {{ publicText("copy.vue_services_graduation", "text_9002", "(NIS") }} {{ result.record.nis }})</p>
                    <p>{{ publicText("copy.vue_services_graduation", "text_011", "Keputusan:") }} <strong class="uppercase">{{ result.record.decision === 'graduated' ? 'LULUS' : 'TIDAK LULUS' }}</strong></p>
                    <p>{{ publicText("copy.vue_services_graduation", "text_012", "Nomor SKL:") }} <strong class="font-mono">{{ result.record.skl_number }}</strong></p>
                    <div v-if="result.record.has_skl_pdf" class="pt-2">
                        <a
                            :href="`/layanan/kelulusan/${result.record.id}/skl.pdf`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-block rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white"
                        >
                            {{ publicText("copy.vue_services_graduation", "text_013", "Unduh Dokumen SKL (PDF)") }}
                        </a>
                    </div>
                </div>
            </div>
            <section v-show="activeTab === 'help'" role="tabpanel" aria-labelledby="graduation-tab-help" id="petunjuk-skl" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-3">
                <h2 class="service-form-title">{{ publicText("copy.vue_services_graduation", "text_014", "Alur & Ketentuan Penerbitan SKL") }}</h2>
                <div class="grid gap-3 md:grid-cols-3 text-xs text-slate-600">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-1">
                        <strong class="text-slate-900 block">{{ publicText("copy.vue_services_graduation", "text_015", "1. Jadwal Rilis Resmi") }}</strong>
                        <p>{{ publicText("copy.vue_services_graduation", "text_016", "Data kelulusan terkunci sampai waktu pengumuman resmi yang ditetapkan sekolah.") }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-1">
                        <strong class="text-slate-900 block">{{ publicText("copy.vue_services_graduation", "text_017", "2. Verifikasi NISN & PIN") }}</strong>
                        <p>{{ publicText("copy.vue_services_graduation", "text_018", "Masukkan NISN dan PIN kelulusan untuk menjaga privasi hasil kelulusan masing-masing siswa.") }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-1">
                        <strong class="text-slate-900 block">{{ publicText("copy.vue_services_graduation", "text_019", "3. Unduh SKL Ber-QR") }}</strong>
                        <p>{{ publicText("copy.vue_services_graduation", "text_020", "Surat Keterangan Lulus PDF dilengkapi kode verifikasi resmi untuk memeriksa keabsahan dokumen.") }}</p>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
