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
const quickType = ref<'pengaduan' | 'aspirasi' | 'info'>('pengaduan');

const completionRate = computed(() => {
    const total = props.transparency?.totals.total ?? 0;
    const resolved = props.transparency?.totals.resolved ?? 0;
    return total > 0 ? Math.round((resolved / total) * 100) : 0;
});

const newCount = computed(() => {
    const total = props.transparency?.totals.total ?? 0;
    const inProgress = props.transparency?.totals.in_progress ?? 0;
    const resolved = props.transparency?.totals.resolved ?? 0;
    return Math.max(0, total - inProgress - resolved);
});

const chooseCategoryCard = (value: string) => {
    form.category = value;
    document.getElementById('form-lapor')?.scrollIntoView({ behavior: 'smooth' });
};

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
                <div class="lg:col-span-7 space-y-3">
                    <p class="madrasah-service-label"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i> Whistleblowing &amp; Layanan Aspirasi Terpadu | 100% Bebas Intimidasi</p>
                    <h1 class="text-2xl font-bold text-slate-900">
                        Suara Anda Lindungi Madrasah.<br />
                        <span class="bg-gradient-to-r from-rose-600 to-amber-600 bg-clip-text text-transparent">Laporkan Kendala &amp; Sampaikan Ide.</span>
                    </h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Saluran pengaduan resmi {{ school.name }}. Tangani perundungan, sarana belajar, ketertiban, dan layanan madrasah secara aman dengan opsi <strong>Kirim Sebagai Anonim</strong>.
                    </p>
                    <div v-if="transparency" class="rounded-2xl border border-slate-200 bg-white p-4 space-y-3 shadow-sm">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-700">Statistik Penanganan Madrasah</h2>
                            <span class="rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700">Tingkat Selesai: {{ completionRate }}%</span>
                        </div>
                        <div class="grid grid-cols-4 gap-2 text-center">
                            <div class="rounded-xl bg-slate-50 border border-slate-100 p-2.5"><span class="block text-[10px] font-bold text-slate-500">TOTAL MASUK</span><strong class="text-xl font-black text-slate-900">{{ transparency.totals.total }}</strong></div>
                            <div class="rounded-xl bg-sky-50 border border-sky-100 p-2.5"><span class="block text-[10px] font-bold text-sky-700">BARU MASUK</span><strong class="text-xl font-black text-sky-700">{{ newCount }}</strong></div>
                            <div class="rounded-xl bg-amber-50 border border-amber-100 p-2.5"><span class="block text-[10px] font-bold text-amber-700">DIPROSES</span><strong class="text-xl font-black text-amber-700">{{ transparency.totals.in_progress }}</strong></div>
                            <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-2.5"><span class="block text-[10px] font-bold text-emerald-700">TUNTAS</span><strong class="text-xl font-black text-emerald-700">{{ transparency.totals.resolved }}</strong></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 pt-1">
                        <div class="rounded-xl border border-slate-200 bg-white p-3 text-xs"><strong class="block text-slate-900">100% Anonim</strong><span class="text-[11px] text-slate-500">Identitas tersimpan rahasia</span></div>
                        <div class="rounded-xl border border-slate-200 bg-white p-3 text-xs"><strong class="block text-slate-900">Respon Cepat</strong><span class="text-[11px] text-slate-500">Maks. 3×24 jam kerja</span></div>
                        <div class="rounded-xl border border-slate-200 bg-white p-3 text-xs"><strong class="block text-slate-900">Lacak Tiket</strong><span class="text-[11px] text-slate-500">Pantau progres real-time</span></div>
                    </div>
                </div>
                <div class="parity-complaint-quick lg:col-span-5">
                    <div class="parity-complaint-tabs">
                        <button type="button" :aria-pressed="quickTab === 'write'" @click="quickTab = 'write'">Tulis Pengaduan</button>
                        <button type="button" :aria-pressed="quickTab === 'track'" @click="quickTab = 'track'">Cek Tiket Laporan</button>
                    </div>
                    <form v-if="quickTab === 'write'" @submit.prevent="focusFullComplaint">
                        <div class="flex gap-1.5 mb-2">
                            <button type="button" class="rounded-full px-3 py-1 text-[11px] font-bold border" :class="quickType === 'pengaduan' ? 'bg-rose-600 text-white border-rose-600' : 'bg-slate-100 text-slate-600 border-slate-200'" @click="quickType = 'pengaduan'">Pengaduan</button>
                            <button type="button" class="rounded-full px-3 py-1 text-[11px] font-bold border" :class="quickType === 'aspirasi' ? 'bg-rose-600 text-white border-rose-600' : 'bg-slate-100 text-slate-600 border-slate-200'" @click="quickType = 'aspirasi'">Aspirasi</button>
                            <button type="button" class="rounded-full px-3 py-1 text-[11px] font-bold border" :class="quickType === 'info' ? 'bg-rose-600 text-white border-rose-600' : 'bg-slate-100 text-slate-600 border-slate-200'" @click="quickType = 'info'">Info</button>
                        </div>
                        <label for="quick-complaint-category">Pilih Kategori Permasalahan</label>
                        <select id="quick-complaint-category" v-model="form.category">
                            <option v-for="item in categories" :key="item.value" :value="item.value">{{ item.label }}</option>
                        </select>
                        <label for="quick-complaint-description">Pokok Masalah / Keluhan</label>
                        <textarea id="quick-complaint-description" v-model="form.description" rows="4" placeholder="Ketik ringkasan kejadian..." />
                        <button type="submit">Lanjutkan ke Formulir Lengkap</button>
                    </form>
                    <form v-else action="/lacak" method="get">
                        <input type="hidden" name="category" value="aduan" />
                        <label for="quick-complaint-ticket">Nomor Tiket Laporan</label>
                        <input id="quick-complaint-ticket" name="code" required placeholder="Masukkan nomor tiket laporan" />
                        <button type="submit">Lacak Status Tindak Lanjut</button>
                    </form>
                    <p>Identitas dan bukti laporan dilindungi. Tersedia Opsi Anonim.</p>
                </div>
            </div>

            <section id="kategori-kasus" class="space-y-4">
                <div class="text-center">
                    <h2 class="text-xl font-extrabold text-slate-900">Pilih Kategori Masalah</h2>
                    <p class="text-xs text-slate-500 mt-1">Klik kategori di bawah untuk langsung mengisi formulir laporan</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <button
                        v-for="item in categories"
                        :key="item.value"
                        type="button"
                        class="text-left rounded-2xl border p-4 transition bg-white hover:border-rose-400"
                        :class="form.category === item.value ? 'border-rose-500 ring-2 ring-rose-500/20' : 'border-slate-200'"
                        @click="chooseCategoryCard(item.value)"
                    >
                        <h3 class="text-sm font-extrabold text-slate-900">{{ item.label }}</h3>
                        <p class="text-xs text-slate-500 mt-1">Laporkan kendala terkait {{ item.label.toLowerCase() }} secara aman.</p>
                    </button>
                </div>
            </section>

            <section id="alur-jaminan" class="grid gap-5 lg:grid-cols-12">
                <div class="lg:col-span-7 rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                    <h3 class="text-lg font-extrabold text-slate-900">Alur Penyelesaian Laporan</h3>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3.5"><span class="text-xs font-extrabold text-rose-700">01 · Tulis Kronologi</span><p class="mt-1 text-xs text-slate-600">Pilih kategori masalah dan jelaskan kejadian atau aspirasi Anda.</p></div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3.5"><span class="text-xs font-extrabold text-rose-700">02 · Telaah Satgas</span><p class="mt-1 text-xs text-slate-600">Satgas berwenang menelaah laporan secara rahasia dan teraudit.</p></div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3.5"><span class="text-xs font-extrabold text-rose-700">03 · Tindak Lanjut</span><p class="mt-1 text-xs text-slate-600">Penanganan lapangan oleh tim terkait sesuai SOP sekolah.</p></div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3.5"><span class="text-xs font-extrabold text-rose-700">04 · Laporan Balik</span><p class="mt-1 text-xs text-slate-600">Pantau balasan dan status penyelesaian lewat halaman Lacak Tiket.</p></div>
                    </div>
                </div>
                <aside class="lg:col-span-5 rounded-2xl bg-slate-900 text-white p-6 space-y-3">
                    <h3 class="text-lg font-extrabold text-white">Kerahasiaan Anda Terlindungi Penuh</h3>
                    <p class="text-xs text-slate-300 leading-relaxed">Mode laporan anonim tidak menyimpan nama, akun, maupun kontak pelapor. Seluruh bukti unggahan hanya dapat diakses oleh Satgas TPPK dan BK yang berwenang.</p>
                    <Link href="/lapor/transparansi" class="inline-block rounded-xl bg-rose-600 hover:bg-rose-500 px-4 py-2 text-xs font-bold text-white">Lihat Transparansi Publik →</Link>
                </aside>
            </section>

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
