<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    categories: { value: string; label: string }[];
    transparency?: {
        totals: { total: number; in_progress: number; resolved: number };
        published_summaries: { id: number; category_label: string; status: string; summary: string; resolved_at?: string | null }[];
    };
    issuedReceipt?: {
        report_code: string;
        ticket_code?: string | null;
        verification_secret: string;
        is_anonymous: boolean;
    } | null;
}>();

const quickTab = ref<'write' | 'track'>('write');

const themeStyle = computed(() => publicThemeStyle(props.school.theme));

const form = useForm({
    category: props.categories[0]?.value ?? 'akademik',
    is_anonymous: false,
    reporter_name: '',
    reporter_contact: '',
    reporter_role: 'siswa',
    title: '',
    description: '',
    incident_date: new Date().toISOString().slice(0, 10),
    location: '',
    idempotency_key: `adu-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
    evidence_files: [] as File[],
});

const pickFiles = (event: Event) => {
    const files = (event.target as HTMLInputElement).files;
    form.evidence_files = files ? Array.from(files) : [];
};

const focusFullComplaint = () => {
    document.getElementById('adu-description')?.focus();
};

const submit = () => {
    form.post('/layanan/lapor', { forceFormData: true, preserveScroll: true });
};
</script>

<template>
    <Head :title="`Siap Lapor — ${school.name}`" />

    <div class="min-h-screen bg-[#f8f6f0]" data-madrasah-service="complaint" :style="themeStyle">
        <PublicHeader :school="school" />

        <main class="mx-auto max-w-5xl px-4 py-10 space-y-6">
            <div class="madrasah-service-hero grid gap-5 lg:grid-cols-12 items-center">
                <div class="lg:col-span-8 space-y-3">
                    <p class="madrasah-service-label"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i> Siap Lapor · Portal Pengaduan &amp; Aspirasi Terpadu</p>
                    <h1 class="text-2xl font-bold text-slate-900">Suara Anda Lindungi Madrasah. Laporkan Kendala &amp; Sampaikan Ide.</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Sampaikan laporan bernama atau anonim. Identitas pelapor anonim tidak disimpan dan laporan diproses khusus oleh Satgas berwenang.
                    </p>
                    <div v-if="transparency" class="parity-complaint-stats">
                        <h2>Statistik Penanganan Madrasah</h2>
                        <div>
                            <span><strong>{{ transparency.totals.total }}</strong>Total Laporan</span>
                            <span><strong>{{ transparency.totals.in_progress }}</strong>Diproses</span>
                            <span><strong>{{ transparency.totals.resolved }}</strong>Tuntas</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-1">
                        <a href="#form-lapor" class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow hover:bg-rose-700">Tulis Pengaduan</a>
                        <Link href="/lacak?category=aduan" class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white">Cek Tiket Laporan</Link>
                        <Link href="/lapor/transparansi" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700">Transparansi Publik</Link>
                    </div>
                </div>
                <div class="parity-complaint-quick">
                    <div class="parity-complaint-tabs">
                        <button type="button" :aria-pressed="quickTab === 'write'" @click="quickTab = 'write'">Tulis Pengaduan</button>
                        <button type="button" :aria-pressed="quickTab === 'track'" @click="quickTab = 'track'">Cek Tiket Laporan</button>
                    </div>
                    <form v-if="quickTab === 'write'" @submit.prevent="focusFullComplaint">
                        <label for="quick-complaint-category">Pilih Kategori Permasalahan</label>
                        <select id="quick-complaint-category" v-model="form.category">
                            <option v-for="item in categories" :key="item.value" :value="item.value">{{ item.label }}</option>
                        </select>
                        <label for="quick-complaint-description">Pesan Masalah / Keluhan</label>
                        <textarea id="quick-complaint-description" v-model="form.description" rows="5" placeholder="Tulis kronologi singkat laporan..." />
                        <button type="submit">Lanjutkan ke Formulir Lengkap</button>
                    </form>
                    <form v-else action="/lacak" method="get">
                        <input type="hidden" name="category" value="aduan" />
                        <label for="quick-complaint-ticket">Nomor Tiket Laporan</label>
                        <input id="quick-complaint-ticket" name="code" required placeholder="Masukkan nomor tiket laporan" />
                        <button type="submit">Lacak Status Tindak Lanjut</button>
                    </form>
                    <p>Identitas dan bukti laporan dilindungi.</p>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <span class="text-xs font-extrabold text-rose-700">01 · Tulis Kronologi</span>
                    <p class="mt-1 text-xs text-slate-600">Pilih kategori masalah dan jelaskan kejadian atau aspirasi Anda.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <span class="text-xs font-extrabold text-rose-700">02 · Telaah Satgas</span>
                    <p class="mt-1 text-xs text-slate-600">Satgas berwenang menelaah laporan secara rahasia dan teraudit.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <span class="text-xs font-extrabold text-rose-700">03 · Tindak Lanjut</span>
                    <p class="mt-1 text-xs text-slate-600">Penanganan lapangan oleh tim terkait sesuai SOP sekolah.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <span class="text-xs font-extrabold text-rose-700">04 · Laporan Balik</span>
                    <p class="mt-1 text-xs text-slate-600">Pantau balasan dan status penyelesaian lewat halaman Lacak Tiket.</p>
                </div>
            </div>

            <div v-if="issuedReceipt" class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-950 space-y-1">
                <p class="font-bold">Laporan Pengaduan Diterima!</p>
                <p>Kode Aduan: <strong class="font-mono">{{ issuedReceipt.report_code }}</strong> | Tiket Pelacakan (/lacak): <strong class="font-mono">{{ issuedReceipt.ticket_code }}</strong> | PIN Rahasia: <strong class="font-mono">{{ issuedReceipt.verification_secret }}</strong></p>
                <p class="text-xs">{{ issuedReceipt.is_anonymous ? 'Mode Anonim Aktif — Identitas pelapor tidak disimpan di sistem.' : 'Laporan Bernama — Gunakan tiket dan PIN untuk memantau balasan Satgas.' }}</p>
            </div>

            <fieldset class="service-choice-group">
                <legend class="service-form-title">Pilih Kategori Masalah</legend>
                <div class="service-tiles">
                    <label v-for="item in categories" :key="item.value" class="service-choice">
                        <input v-model="form.category" type="radio" name="complaint-category" :value="item.value" />
                        <strong>{{ item.label }}</strong>
                    </label>
                </div>
                <p v-if="form.errors.category" class="text-sm text-rose-700" role="alert">{{ form.errors.category }}</p>
            </fieldset>

            <form id="form-lapor" @submit.prevent="submit" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                <h2 class="service-form-title">Formulir Pengaduan</h2>
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <input v-model="form.is_anonymous" type="checkbox" />
                        Kirim Sebagai Pelapor Anonim (Identitas &amp; kontak dirahasiakan penuh)
                    </label>
                </div>

                <div v-if="!form.is_anonymous" class="grid gap-3 md:grid-cols-3">
                    <FormField id="adu-reporter-name" label="Nama Pelapor" :error="form.errors.reporter_name" required>
                        <input id="adu-reporter-name" v-model="form.reporter_name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="adu-reporter-contact" label="Kontak / WA / Email">
                        <input id="adu-reporter-contact" v-model="form.reporter_contact" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="adu-reporter-role" label="Status Pelapor">
                        <select id="adu-reporter-role" v-model="form.reporter_role" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="siswa">Siswa</option>
                            <option value="wali">Orang Tua / Wali</option>
                            <option value="guru">Guru / Staf</option>
                            <option value="umum">Masyarakat Umum</option>
                        </select>
                    </FormField>
                </div>

                <div class="grid gap-3 md:grid-cols-3">
                    <FormField id="adu-date" label="Tanggal Kejadian">
                        <input id="adu-date" v-model="form.incident_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="adu-location" label="Lokasi Kejadian">
                        <input id="adu-location" v-model="form.location" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                </div>

                <FormField id="adu-title" label="Judul Singkat Laporan" :error="form.errors.title" required>
                    <input id="adu-title" v-model="form.title" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </FormField>

                <FormField id="adu-description" label="Kronologi Kejadian Lengkap" :error="form.errors.description" required>
                    <textarea id="adu-description" v-model="form.description" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </FormField>

                <FormField id="adu-evidence" label="Unggah Foto / Bukti Pendukung (Akses Terbatas Satgas)">
                    <input id="adu-evidence" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" capture="environment" class="text-sm" @change="pickFiles" />
                </FormField>

                <AppButton type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Mengirim Laporan...' : 'Kirim Laporan Sekarang' }}
                </AppButton>
            </form>
            <section v-if="transparency" class="parity-public-complaints">
                <h2>Laporan &amp; Aspirasi Terbaru</h2>
                <p>Ringkasan tindak lanjut yang telah dimoderasi untuk publik.</p>
                <div v-if="transparency.published_summaries.length" class="service-tiles">
                    <article v-for="item in transparency.published_summaries" :key="item.id">
                        <span>{{ item.category_label }}</span><h3>{{ item.summary }}</h3>
                        <p>{{ item.status }} · {{ item.resolved_at || 'Dalam penanganan' }}</p>
                    </article>
                </div>
                <p v-else>Belum ada ringkasan laporan yang dipublikasikan.</p>
                <Link href="/lapor/transparansi">Daftar Pengaduan Terbuka →</Link>
            </section>
        </main>
    </div>
</template>
