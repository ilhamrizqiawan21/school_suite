<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

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
    contentBlocks?: Record<string, Record<string, any>>;
}>();

const heroBlock = computed(() => props.contentBlocks?.['complaint.hero'] ?? {});

const quickTab = ref<'write' | 'track'>('write');
const quickType = ref<'pengaduan' | 'aspirasi' | 'info'>('pengaduan');
const reportModalOpen = ref(false);

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

const resolvedPercent = computed(() => {
    const total = props.transparency?.totals.total ?? 0;
    const resolved = props.transparency?.totals.resolved ?? 0;
    return total > 0 ? Math.round((resolved / total) * 100) : 0;
});

const inProgressPercent = computed(() => {
    const total = props.transparency?.totals.total ?? 0;
    const inProgress = props.transparency?.totals.in_progress ?? 0;
    return total > 0 ? Math.round((inProgress / total) * 100) : 0;
});

const newPercent = computed(() => {
    const total = props.transparency?.totals.total ?? 0;
    return total > 0 ? Math.max(0, 100 - resolvedPercent.value - inProgressPercent.value) : 0;
});

const categoryStyles = [
    { tone: 'sky', icon: 'fa-solid fa-graduation-cap' },
    { tone: 'rose', icon: 'fa-solid fa-shield-halved' },
    { tone: 'emerald', icon: 'fa-solid fa-building-columns' },
    { tone: 'purple', icon: 'fa-solid fa-users' },
    { tone: 'amber', icon: 'fa-solid fa-triangle-exclamation' },
    { tone: 'teal', icon: 'fa-solid fa-comment-dots' },
];

const categoryTheme = (index: number) => categoryStyles[index % categoryStyles.length];

const openReportModal = (categoryValue?: string) => {
    if (categoryValue) {
        form.category = categoryValue;
    }
    reportModalOpen.value = true;
    nextTick(() => document.getElementById('adu-title')?.focus());
};

const closeReportModal = () => {
    reportModalOpen.value = false;
};

const chooseCategoryCard = (value: string) => {
    openReportModal(value);
};

const handleDocumentClick = (event: MouseEvent) => {
    const target = (event.target as HTMLElement | null)?.closest('a[href="#form-lapor"], a[href="#tulis-laporan"]');
    if (target) {
        event.preventDefault();
        openReportModal();
    }
};

const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && reportModalOpen.value) {
        closeReportModal();
    }
};

onMounted(() => {
    document.addEventListener('click', handleDocumentClick);
    document.addEventListener('keydown', handleKeydown);
    if (window.location.hash === '#form-lapor' || window.location.hash === '#tulis-laporan') {
        openReportModal();
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleDocumentClick);
    document.removeEventListener('keydown', handleKeydown);
});

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
    openReportModal();
};

const submit = () => {
    form.post('/layanan/lapor', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            reportModalOpen.value = false;
            form.reset('title', 'description', 'location', 'evidence_files');
        },
    });
};
</script>

<template>
    <Head :title="`Siap Lapor — ${school.name}`" />

    <div class="parity-lapor-page min-h-screen" data-madrasah-service="complaint" :style="themeStyle">
        <PublicHeader :school="school" />

        <main class="parity-lapor-main">
            <section class="parity-lapor-hero-mesh">
                <div class="parity-lapor-container parity-lapor-hero-grid">
                    <div class="parity-lapor-hero-copy">
                        <div class="parity-lapor-trust-pill">
                            <span class="parity-lapor-pill-dot" aria-hidden="true"></span>
                            <span>{{ heroBlock.pill_label || 'Whistleblowing & Layanan Aspirasi Terpadu' }}</span>
                            <span class="parity-lapor-pill-sep">|</span>
                            <span class="parity-lapor-pill-accent">{{ heroBlock.pill_accent || '100% Bebas Intimidasi' }}</span>
                        </div>

                        <div>
                            <h1 class="parity-lapor-h1">
                                {{ heroBlock.headline || 'Suara Anda Lindungi Madrasah.' }}<br />
                                <span class="parity-lapor-gradient-text">{{ heroBlock.headline_highlight || 'Laporkan Kendala & Sampaikan Ide.' }}</span>
                            </h1>
                            <p class="parity-lapor-lead">
                                <template v-if="heroBlock.lead">{{ heroBlock.lead }}</template>
                                <template v-else>Saluran pengaduan resmi {{ school.name }}. Tangani perundungan, sarana belajar, ketertiban, dan layanan madrasah secara aman dengan opsi <strong>Kirim Sebagai Anonim</strong>.</template>
                            </p>
                        </div>

                        <div v-if="transparency" class="parity-lapor-stats-card">
                            <div class="parity-lapor-stats-head">
                                <span class="parity-lapor-stats-title">
                                    <i class="fa-solid fa-chart-simple" aria-hidden="true"></i>
                                    STATISTIK PENANGANAN MADRASAH
                                </span>
                                <div class="parity-lapor-stats-rate">
                                    <span>Tingkat Selesai:</span>
                                    <span class="parity-lapor-rate-badge">{{ completionRate }}%</span>
                                </div>
                            </div>

                            <div class="parity-lapor-metrics">
                                <div class="parity-lapor-metric parity-lapor-metric--slate">
                                    <span>TOTAL MASUK</span>
                                    <strong>{{ transparency.totals.total }}</strong>
                                </div>
                                <div class="parity-lapor-metric parity-lapor-metric--sky">
                                    <span>BARU MASUK</span>
                                    <strong>{{ newCount }}</strong>
                                </div>
                                <div class="parity-lapor-metric parity-lapor-metric--amber">
                                    <span>DIPROSES</span>
                                    <strong>{{ transparency.totals.in_progress }}</strong>
                                </div>
                                <div class="parity-lapor-metric parity-lapor-metric--emerald">
                                    <span>TUNTAS</span>
                                    <strong>{{ transparency.totals.resolved }}</strong>
                                </div>
                            </div>

                            <div class="parity-lapor-progress-wrap">
                                <div class="parity-lapor-progress-bar">
                                    <div class="parity-lapor-bar-emerald" :style="{ width: `${resolvedPercent}%` }"></div>
                                    <div class="parity-lapor-bar-amber" :style="{ width: `${inProgressPercent}%` }"></div>
                                    <div class="parity-lapor-bar-sky" :style="{ width: `${newPercent}%` }"></div>
                                </div>
                                <div class="parity-lapor-progress-legend">
                                    <span><i class="parity-dot-emerald"></i> Selesai</span>
                                    <span><i class="parity-dot-amber"></i> Ditangani Satgas</span>
                                    <span><i class="parity-dot-sky"></i> Verifikasi Awal</span>
                                </div>
                            </div>
                        </div>

                        <div class="parity-lapor-pillars">
                            <div class="parity-lapor-pillar">
                                <span class="parity-lapor-pillar-icon parity-lapor-pillar-icon--rose">
                                    <i class="fa-solid fa-user-secret" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <strong>100% Anonim</strong>
                                    <span>Identitas tersimpan rahasia</span>
                                </div>
                            </div>
                            <div class="parity-lapor-pillar">
                                <span class="parity-lapor-pillar-icon parity-lapor-pillar-icon--amber">
                                    <i class="fa-solid fa-bolt" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <strong>Respon Cepat</strong>
                                    <span>Maks. 3×24 jam kerja</span>
                                </div>
                            </div>
                            <div class="parity-lapor-pillar">
                                <span class="parity-lapor-pillar-icon parity-lapor-pillar-icon--emerald">
                                    <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <strong>Lacak Tiket</strong>
                                    <span>Pantau progres real-time</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="parity-lapor-hero-console">
                        <div class="parity-complaint-quick">
                            <div class="parity-complaint-tabs">
                                <button type="button" :aria-pressed="quickTab === 'write'" @click="quickTab = 'write'">
                                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    <span>Tulis Pengaduan</span>
                                </button>
                                <button type="button" :aria-pressed="quickTab === 'track'" @click="quickTab = 'track'">
                                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                    <span>Cek Tiket Laporan</span>
                                </button>
                            </div>
                            <form v-if="quickTab === 'write'" @submit.prevent="focusFullComplaint">
                                <div class="parity-lapor-type-pills">
                                    <button type="button" :class="{ 'is-active': quickType === 'pengaduan' }" @click="quickType = 'pengaduan'">
                                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Pengaduan
                                    </button>
                                    <button type="button" :class="{ 'is-active': quickType === 'aspirasi' }" @click="quickType = 'aspirasi'">
                                        <i class="fa-solid fa-lightbulb" aria-hidden="true"></i> Aspirasi
                                    </button>
                                    <button type="button" :class="{ 'is-active': quickType === 'info' }" @click="quickType = 'info'">
                                        <i class="fa-solid fa-circle-question" aria-hidden="true"></i> Info
                                    </button>
                                </div>
                                <label for="quick-complaint-category">Pilih Kategori Permasalahan</label>
                                <select id="quick-complaint-category" v-model="form.category">
                                    <option v-for="item in categories" :key="item.value" :value="item.value">{{ item.label }}</option>
                                </select>
                                <label for="quick-complaint-description">Pokok Masalah / Keluhan</label>
                                <textarea id="quick-complaint-description" v-model="form.description" rows="3" placeholder="Ketik ringkasan kejadian (misal: AC kelas XI-A rusak / Kejadian intimidasi di lorong barat)..." />
                                <button type="submit" class="parity-lapor-quick-submit">
                                    <span>Lanjutkan ke Formulir Lengkap</span>
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </form>
                            <form v-else action="/lacak" method="get" class="parity-lapor-track-form">
                                <input type="hidden" name="category" value="aduan" />
                                <div class="parity-lapor-track-banner">
                                    <span class="parity-lapor-track-icon"><i class="fa-solid fa-ticket" aria-hidden="true"></i></span>
                                    <strong>Lacak Status Tindak Lanjut</strong>
                                    <span>Masukkan nomor tiket resmi pengaduan yang Anda dapatkan saat melapor.</span>
                                </div>
                                <label for="quick-complaint-ticket">Nomor Tiket Laporan</label>
                                <div class="parity-lapor-ticket-input-wrap">
                                    <input id="quick-complaint-ticket" name="code" required placeholder="CONTOH: LPR-2026-XXXX" />
                                    <button type="submit" class="parity-lapor-ticket-btn">Cek Status</button>
                                </div>
                                <p class="parity-lapor-ticket-hint">Kode tiket peka huruf kapital. Jika lupa nomor tiket, hubungi satgas madrasah.</p>
                            </form>
                            <div class="parity-lapor-console-foot">
                                <span><i class="fa-solid fa-camera" aria-hidden="true"></i> Lampirkan foto/bukti di formulir</span>
                                <span class="parity-lapor-console-anon"><i class="fa-solid fa-lock" aria-hidden="true"></i> Tersedia Opsi Anonim</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="kategori-kasus" class="parity-lapor-section-white">
                <div class="parity-lapor-container">
                    <div class="parity-lapor-section-head">
                        <div>
                            <span class="parity-lapor-badge-rose">KLASIFIKASI PENGADUAN</span>
                            <h2>Pilih Kategori Masalah</h2>
                            <p>Setiap kategori ditangani langsung oleh divisi satgas berwenang (Guru BK, Sarpras, Kurikulum, atau Kepala Madrasah).</p>
                        </div>
                        <a href="#form-lapor" class="parity-lapor-head-link">
                            <span>Buka Formulir Bebas</span>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>

                    <div class="parity-lapor-cat-grid">
                        <button
                            v-for="(item, idx) in categories"
                            :key="item.value"
                            type="button"
                            class="parity-lapor-cat-card"
                            :class="[`parity-lapor-cat-card--${categoryTheme(idx).tone}`, { 'is-selected': form.category === item.value }]"
                            @click="chooseCategoryCard(item.value)"
                        >
                            <div>
                                <div class="parity-lapor-cat-top">
                                    <span class="parity-lapor-cat-icon">
                                        <i :class="categoryTheme(idx).icon" aria-hidden="true"></i>
                                    </span>
                                    <span class="parity-lapor-cat-status">AKTIF</span>
                                </div>
                                <h3>{{ item.label }}</h3>
                                <p>Laporkan kendala atau masukan terkait {{ item.label.toLowerCase() }} secara aman.</p>
                            </div>
                            <div class="parity-lapor-cat-foot">
                                <span>Pilih &amp; Laporkan</span>
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </div>
                        </button>
                    </div>
                </div>
            </section>

            <section id="alur-jaminan" class="parity-lapor-section-gray">
                <div class="parity-lapor-container parity-lapor-guide-grid">
                    <div class="parity-lapor-steps-card">
                        <div>
                            <span class="parity-lapor-badge-rose">STANDAR OPERASIONAL</span>
                            <h3>Alur Penyelesaian Laporan</h3>
                            <p class="parity-lapor-steps-sub">Setiap pengaduan tercatat digital dan dapat dipantau perkembangannya.</p>

                            <div class="parity-lapor-steps-list">
                                <div class="parity-lapor-step parity-lapor-step--slate">
                                    <span class="parity-lapor-step-num">01</span>
                                    <div>
                                        <h4>Tulis Kronologi</h4>
                                        <p>Isi data, lokasi, foto bukti &amp; tentukan mode anonim.</p>
                                    </div>
                                </div>
                                <div class="parity-lapor-step parity-lapor-step--amber">
                                    <span class="parity-lapor-step-num">02</span>
                                    <div>
                                        <h4>Telaah Satgas</h4>
                                        <p>Tim satgas madrasah memverifikasi keabsahan laporan.</p>
                                    </div>
                                </div>
                                <div class="parity-lapor-step parity-lapor-step--sky">
                                    <span class="parity-lapor-step-num">03</span>
                                    <div>
                                        <h4>Tindak Lanjut Lapangan</h4>
                                        <p>Mediasi Guru BK, perbaikan sarana, atau tindakan disiplin.</p>
                                    </div>
                                </div>
                                <div class="parity-lapor-step parity-lapor-step--emerald">
                                    <span class="parity-lapor-step-num">04</span>
                                    <div>
                                        <h4>Tuntas &amp; Laporan Balik</h4>
                                        <p>Pelapor dapat melihat bukti penyelesaian via kode tiket.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="parity-lapor-steps-foot">
                            <span><i class="fa-solid fa-clock" aria-hidden="true"></i> Standar respon awal: <strong>&lt; 24 Jam</strong></span>
                            <a href="#form-lapor">Mulai Lapor →</a>
                        </div>
                    </div>

                    <aside class="parity-lapor-security-card">
                        <div>
                            <span class="parity-lapor-security-badge">
                                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                                <span>Jaminan Whistleblower &amp; Ramah Anak</span>
                            </span>
                            <h3>Kerahasiaan Anda Terlindungi Penuh</h3>
                            <p>Mode laporan anonim tidak menyimpan nama, akun, maupun kontak pelapor. Seluruh bukti unggahan hanya dapat diakses oleh Satgas TPPK dan BK yang berwenang.</p>
                            <ul class="parity-lapor-security-list">
                                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Opsi Anonim menyamarkan nama dari pandangan publik.</li>
                                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Data hanya dapat diakses Tim Satgas &amp; Kepala Madrasah.</li>
                                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Pendampingan konseling khusus dari Guru BK terlatih.</li>
                            </ul>
                        </div>
                        <div class="parity-lapor-security-foot">
                            <div class="parity-lapor-security-org">
                                <span class="parity-lapor-security-icon"><i class="fa-solid fa-user-shield" aria-hidden="true"></i></span>
                                <div>
                                    <small>KANAL RESMI MADRASAH</small>
                                    <strong>Bebas Pelacakan Identitas</strong>
                                </div>
                            </div>
                            <button type="button" class="parity-lapor-security-btn" @click="openReportModal()">
                                <i class="fa-solid fa-lock" aria-hidden="true"></i> Buat Laporan Rahasia
                            </button>
                        </div>
                    </aside>
                </div>
            </section>

            <section v-if="issuedReceipt" class="parity-lapor-section-white">
                <div class="parity-lapor-container">
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-950 space-y-1" role="status">
                        <p class="font-bold">Laporan Pengaduan Diterima!</p>
                        <p>Kode Aduan: <strong class="font-mono">{{ issuedReceipt.report_code }}</strong> | Tiket Pelacakan (/lacak): <strong class="font-mono">{{ issuedReceipt.ticket_code }}</strong> | PIN Rahasia: <strong class="font-mono">{{ issuedReceipt.verification_secret }}</strong></p>
                        <p class="text-xs">{{ issuedReceipt.is_anonymous ? 'Mode Anonim Aktif — Identitas pelapor tidak disimpan di sistem.' : 'Laporan Bernama — Gunakan tiket dan PIN untuk memantau balasan Satgas.' }}</p>
                    </div>
                </div>
            </section>
        </main>

        <div v-if="reportModalOpen" class="parity-lapor-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modal-lapor-title" @click.self="closeReportModal">
            <div class="parity-lapor-modal">
                <div class="parity-lapor-modal-head">
                    <div class="parity-lapor-modal-title-wrap">
                        <span class="parity-lapor-modal-icon"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></span>
                        <div>
                            <h2 id="modal-lapor-title">Formulir Pengaduan &amp; Aspirasi</h2>
                            <p>Sampaikan laporan Anda dengan uraian kronologi yang jelas</p>
                        </div>
                    </div>
                    <button type="button" class="parity-lapor-modal-close" aria-label="Tutup formulir" @click="closeReportModal">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <form id="form-lapor" @submit.prevent="submit" class="parity-lapor-modal-body">
                    <div class="parity-lapor-anon-row">
                        <label class="parity-lapor-anon-toggle">
                            <input v-model="form.is_anonymous" type="checkbox" />
                            <span>Kirim Sebagai Pelapor Anonim (Identitas &amp; kontak dirahasiakan penuh)</span>
                        </label>
                    </div>

                    <div v-if="!form.is_anonymous" class="grid gap-3 sm:grid-cols-3">
                        <FormField id="adu-reporter-name" label="Nama Pelapor" :error="form.errors.reporter_name" required>
                            <input id="adu-reporter-name" v-model="form.reporter_name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs" />
                        </FormField>
                        <FormField id="adu-reporter-contact" label="Kontak / WA / Email">
                            <input id="adu-reporter-contact" v-model="form.reporter_contact" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs" />
                        </FormField>
                        <FormField id="adu-reporter-role" label="Status Pelapor">
                            <select id="adu-reporter-role" v-model="form.reporter_role" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                <option value="siswa">Siswa</option>
                                <option value="wali">Orang Tua / Wali</option>
                                <option value="guru">Guru / Staf</option>
                                <option value="umum">Masyarakat Umum</option>
                            </select>
                        </FormField>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <FormField id="adu-category" label="Kategori Masalah" :error="form.errors.category" required>
                            <select id="adu-category" v-model="form.category" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                <option v-for="item in categories" :key="item.value" :value="item.value">{{ item.label }}</option>
                            </select>
                        </FormField>
                        <FormField id="adu-date" label="Tanggal Kejadian">
                            <input id="adu-date" v-model="form.incident_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs" />
                        </FormField>
                        <FormField id="adu-location" label="Lokasi Kejadian">
                            <input id="adu-location" v-model="form.location" type="text" placeholder="Contoh: Gedung B" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs" />
                        </FormField>
                    </div>

                    <FormField id="adu-title" label="Judul Singkat Laporan" :error="form.errors.title" required>
                        <input id="adu-title" v-model="form.title" type="text" placeholder="Contoh: Lampu proyektor di Lab mati" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs" />
                    </FormField>

                    <FormField id="adu-description" label="Kronologi Kejadian Lengkap" :error="form.errors.description" required>
                        <textarea id="adu-description" v-model="form.description" rows="4" placeholder="Ceritakan kejadian, waktu kejadian, dan kronologi selengkap mungkin..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs" />
                    </FormField>

                    <FormField id="adu-evidence" label="Unggah Foto / Bukti Pendukung (Akses Terbatas Satgas)">
                        <input id="adu-evidence" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" capture="environment" class="text-xs" @change="pickFiles" />
                    </FormField>

                    <div class="parity-lapor-modal-actions">
                        <button type="button" class="parity-lapor-modal-cancel" @click="closeReportModal">Batal</button>
                        <AppButton type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Mengirim Laporan...' : 'Kirim Laporan Sekarang' }}
                        </AppButton>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
