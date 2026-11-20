<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    schedule?: { released: boolean; release_at?: string | null; title?: string | null };
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
let clock: ReturnType<typeof setInterval> | undefined;
onMounted(() => { clock = setInterval(() => { now.value = Date.now(); }, 1000); });
onBeforeUnmount(() => clearInterval(clock));
const remaining = computed(() => {
    if (!props.schedule?.release_at) return null;
    const seconds = Math.max(0, Math.floor((new Date(props.schedule.release_at).getTime() - now.value) / 1000));
    return [
        { label: 'Hari', value: Math.floor(seconds / 86400) },
        { label: 'Jam', value: Math.floor(seconds / 3600) % 24 },
        { label: 'Menit', value: Math.floor(seconds / 60) % 60 },
        { label: 'Detik', value: seconds % 60 },
    ];
});

const submit = () => {
    form.post('/layanan/kelulusan', { preserveScroll: true });
};
</script>

<template>
    <Head :title="`Kelulusan &amp; SKL — ${school.name}`" />

    <div class="min-h-screen bg-[#f8f6f0]" data-madrasah-service="graduation" :style="themeStyle">
        <PublicHeader :school="school" />

        <main class="mx-auto max-w-3xl px-4 py-10 space-y-6">
            <div class="madrasah-service-hero">
                <p class="madrasah-service-label"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Informasi Akademik</p>
                <h1 class="text-2xl font-bold text-slate-900">Pengumuman Kelulusan &amp; Unduh SKL</h1>
                <p class="mt-1 text-sm text-slate-600">
                    Hasil kelulusan hanya dibuka sesuai jadwal rilis resmi sekolah bagi pemilik NISN dan PIN akses yang sah.
                </p>
            </div>

            <div class="parity-graduation-tabs" role="tablist" aria-label="Portal kelulusan">
                <button id="graduation-tab-check" role="tab" :aria-selected="activeTab === 'check'" aria-controls="graduation-panel-check" @click="activeTab = 'check'">Cek Kelulusan Siswa</button>
                <button id="graduation-tab-schedule" role="tab" :aria-selected="activeTab === 'schedule'" aria-controls="graduation-panel-schedule" @click="activeTab = 'schedule'">Jadwal &amp; Hitung Mundur</button>
                <button id="graduation-tab-help" role="tab" :aria-selected="activeTab === 'help'" aria-controls="petunjuk-skl" @click="activeTab = 'help'">Petunjuk &amp; Syarat SKL</button>
            </div>

            <section v-if="activeTab === 'schedule' || (activeTab === 'check' && !schedule?.released)"
                id="graduation-panel-schedule" role="tabpanel" aria-labelledby="graduation-tab-schedule" class="parity-graduation-state">
                <span class="parity-graduation-orb" aria-hidden="true"></span>
                <h2>{{ schedule?.released ? 'Portal Kelulusan Sudah Dibuka' : 'Portal Kelulusan Belum Dibuka' }}</h2>
                <p>{{ schedule?.title || 'Pengumuman resmi kelulusan madrasah' }}</p>
                <p v-if="schedule?.release_at">{{ new Intl.DateTimeFormat('id-ID', { dateStyle: 'full', timeStyle: 'short' }).format(new Date(schedule.release_at)) }}</p>
                <p v-else>Jadwal pengumuman akan ditampilkan setelah diterbitkan oleh sekolah.</p>
                <div v-if="remaining" class="parity-countdown">
                    <div v-for="part in remaining" :key="part.label"><strong>{{ String(part.value).padStart(2, '0') }}</strong><span>{{ part.label }}</span></div>
                </div>
            </section>

            <form v-if="activeTab === 'check' && (!schedule || schedule.released || result)" id="graduation-panel-check" role="tabpanel" aria-labelledby="graduation-tab-check" @submit.prevent="submit" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                <h2 id="graduation-check-title" class="service-form-title">Cek Hasil Kelulusan</h2>
                <div class="grid gap-3 md:grid-cols-2">
                    <FormField id="grad-nisn" label="NISN" :error="form.errors.nisn" required>
                        <input id="grad-nisn" v-model="form.nisn" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="grad-pin" label="PIN Kelulusan" :error="form.errors.pin">
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
                    <p>Program: <strong>{{ result.record.batch_title }}</strong></p>
                    <p>Nama: <strong>{{ result.record.student_name }}</strong> (NIS {{ result.record.nis }})</p>
                    <p>Keputusan: <strong class="uppercase">{{ result.record.decision === 'graduated' ? 'LULUS' : 'TIDAK LULUS' }}</strong></p>
                    <p>Nomor SKL: <strong class="font-mono">{{ result.record.skl_number }}</strong></p>
                    <div v-if="result.record.has_skl_pdf" class="pt-2">
                        <a
                            :href="`/layanan/kelulusan/${result.record.id}/skl.pdf`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-block rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white"
                        >
                            Unduh Dokumen SKL (PDF)
                        </a>
                    </div>
                </div>
            </div>
            <section v-show="activeTab === 'help'" role="tabpanel" aria-labelledby="graduation-tab-help" id="petunjuk-skl" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-3">
                <h2 class="service-form-title">Alur &amp; Ketentuan Penerbitan SKL</h2>
                <div class="grid gap-3 md:grid-cols-3 text-xs text-slate-600">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-1">
                        <strong class="text-slate-900 block">1. Jadwal Rilis Resmi</strong>
                        <p>Data kelulusan terkunci otomatis sampai waktu pengumuman resmi (`release_at`) yang ditetapkan sekolah.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-1">
                        <strong class="text-slate-900 block">2. Verifikasi NISN &amp; PIN</strong>
                        <p>Masukkan NISN dan PIN kelulusan untuk menjaga privasi hasil kelulusan masing-masing siswa.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-1">
                        <strong class="text-slate-900 block">3. Unduh SKL Ber-QR</strong>
                        <p>Surat Keterangan Lulus PDF dilengkapi kode verifikasi resmi yang dapat dicek pada halaman `/verifikasi`.</p>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
