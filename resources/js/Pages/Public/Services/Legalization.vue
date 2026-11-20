<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import LegalizationProcedure from '@/components/LegalizationProcedure.vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface LegalizationItem {
    id: number;
    legalization_code: string;
    document_type: string;
    certificate_number: string;
    copy_count: number;
    delivery_method: string;
    status: string;
    legalization_document_id?: number | null;
    picked_up_at?: string | null;
    picked_up_by_name?: string | null;
}

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    myRequests: LegalizationItem[];
    issuedReceipt?: { id: number; legalization_code: string; ticket_code?: string | null; verification_secret: string } | null;
}>();

const themeStyle = computed(() => publicThemeStyle(props.school.theme));

const form = useForm({
    applicant_name: '',
    applicant_nis: '',
    applicant_nisn: '',
    graduation_year: new Date().getFullYear(),
    phone: '',
    email: '',
    document_type: 'ijazah',
    certificate_number: '',
    copy_count: 3,
    purpose: '',
    delivery_method: 'pickup_direct',
    proxy_name: '',
    proxy_nik: '',
    proxy_relation: '',
    proxy_phone: '',
    signature_data: 'data:text/plain;base64,VFREX0FMVU1OSQ==',
    idempotency_key: `leg-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
    attachments: [] as File[],
});

const pickFiles = (event: Event) => {
    const files = (event.target as HTMLInputElement).files;
    form.attachments = files ? Array.from(files) : [];
};

const submit = () => {
    form.post('/layanan/legalisir', { forceFormData: true, preserveScroll: true });
};

const statusTone = (status: string): 'neutral' | 'info' | 'warning' | 'success' | 'danger' => {
    if (status === 'completed' || status === 'approved') return 'success';
    if (status === 'needs_revision') return 'warning';
    if (status === 'rejected') return 'danger';
    return 'info';
};
</script>

<template>
    <Head :title="`Legalisir Ijazah — ${school.name}`" />

    <div class="min-h-screen bg-[#f8f6f0]" data-madrasah-service="legalization" :style="themeStyle">
        <PublicHeader :school="school" />

        <main class="mx-auto max-w-4xl px-4 py-10 space-y-6">
            <div class="madrasah-service-hero flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="madrasah-service-label"><i class="fa-solid fa-certificate" aria-hidden="true"></i> Layanan Administrasi Alumni</p>
                    <h1 class="text-2xl font-bold text-slate-900">Legalisir Ijazah &amp; Rapor</h1>
                    <p class="mt-1 text-sm text-slate-600">Pengajuan legalisir alumni dengan pengambilan langsung atau melalui kuasa resmi.</p>
                </div>

            </div>

                <nav class="parity-document-nav" aria-label="Navigasi layanan">
                    <a href="#alur-legalisir">Alur &amp; Ketentuan</a>
                    <a href="#legalization-form-title" class="rounded-lg bg-amber-400 px-3 py-2 text-xs font-extrabold text-slate-950">Formulir Pengajuan</a>
                    <Link
                        href="/layanan/surat-kuasa"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cetak Surat Kuasa / SPTJM
                    </Link>
                    <Link
                        href="/lacak?category=legalisir"
                        class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white"
                    >
                        Cek Status Pengajuan
                    </Link>
                </nav>

            <div id="alur-legalisir" class="parity-legalization-layout">
                <LegalizationProcedure />
                <aside class="parity-requirements">
                    <h2>Ringkasan Persyaratan</h2>
                    <h3>Dokumen Ijazah Asli atau Salinan</h3>
                    <p>Siapkan dokumen yang jelas untuk pemeriksaan petugas Tata Usaha.</p>
                    <h3>Pengambilan Langsung atau Kuasa</h3>
                    <p>Pengambilan melalui perwakilan memerlukan Surat Kuasa dan SPTJM.</p>
                    <Link href="/layanan/surat-kuasa">Buat Surat Kuasa / SPTJM →</Link>
                    <h3>Status Permohonan</h3>
                    <p>Simpan nomor tiket dan PIN agar status pengajuan dapat dipantau.</p>
                    <Link href="/lacak?category=legalisir">Cek Status Pengajuan →</Link>
                </aside>
            </div>

            <div v-if="issuedReceipt" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-950 space-y-1">
                <p class="font-bold">Permohonan Legalisir Diterima!</p>
                <p>Kode Legalisir: <strong class="font-mono">{{ issuedReceipt.legalization_code }}</strong> | Tiket Lacak: <strong class="font-mono">{{ issuedReceipt.ticket_code }}</strong> | PIN: <strong class="font-mono">{{ issuedReceipt.verification_secret }}</strong></p>
                <p class="text-xs">
                    Jika diwakilkan, lanjutkan pembuatan
                    <Link :href="`/layanan/surat-kuasa?kode=${issuedReceipt.legalization_code}`" class="font-semibold underline">Surat Kuasa / SPTJM</Link>.
                </p>
            </div>

            <ol class="service-tiles" aria-label="Alur legalisir">
                <li><strong>Lengkapi data alumni</strong><span>Isi identitas dan dokumen yang akan dilegalisir.</span></li>
                <li><strong>Pilih pengambilan</strong><span>Ambil langsung atau gunakan surat kuasa.</span></li>
                <li><strong>Lacak permohonan</strong><span>Pantau verifikasi menggunakan tiket dan PIN.</span></li>
            </ol>

            <form aria-labelledby="legalization-form-title" @submit.prevent="submit" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                <h2 id="legalization-form-title" class="service-form-title">Formulir Legalisir</h2>
                <div class="grid gap-3 md:grid-cols-3">
                    <FormField id="leg-name" label="Nama Lengkap Alumni" :error="form.errors.applicant_name" required>
                        <input id="leg-name" v-model="form.applicant_name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="leg-year" label="Tahun Lulus" :error="form.errors.graduation_year" required>
                        <input id="leg-year" v-model.number="form.graduation_year" type="number" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="leg-phone" label="No. HP / WhatsApp" :error="form.errors.phone" required>
                        <input id="leg-phone" v-model="form.phone" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                </div>

                <div class="grid gap-3 md:grid-cols-4">
                    <FormField id="leg-doc-type" label="Jenis Dokumen" required>
                        <select id="leg-doc-type" v-model="form.document_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="ijazah">Ijazah</option>
                            <option value="skl">SKL</option>
                            <option value="transkrip">Transkrip Nilai</option>
                            <option value="rapor">Rapor</option>
                        </select>
                    </FormField>
                    <FormField id="leg-cert-no" label="Nomor Dokumen / Seri Ijazah" :error="form.errors.certificate_number" required>
                        <input id="leg-cert-no" v-model="form.certificate_number" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="leg-copies" label="Jumlah Lembar" :error="form.errors.copy_count" required>
                        <input id="leg-copies" v-model.number="form.copy_count" type="number" min="1" max="20" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="leg-delivery" label="Metode Pengambilan" required>
                        <select id="leg-delivery" v-model="form.delivery_method" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="pickup_direct">Ambil Langsung</option>
                            <option value="pickup_proxy">Diambil Kuasa Resmi</option>
                        </select>
                    </FormField>
                </div>

                <div v-if="form.delivery_method === 'pickup_proxy'" class="grid gap-3 md:grid-cols-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <FormField id="leg-proxy-name" label="Nama Penerima Kuasa" :error="form.errors.proxy_name" required>
                        <input id="leg-proxy-name" v-model="form.proxy_name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="leg-proxy-nik" label="NIK Penerima Kuasa">
                        <input id="leg-proxy-nik" v-model="form.proxy_nik" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="leg-proxy-rel" label="Hubungan Keluarga">
                        <input id="leg-proxy-rel" v-model="form.proxy_relation" type="text" placeholder="Orang Tua / Saudara" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="leg-proxy-phone" label="No. HP Kuasa">
                        <input id="leg-proxy-phone" v-model="form.proxy_phone" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                </div>

                <FormField id="leg-purpose" label="Keperluan Legalisir" :error="form.errors.purpose" required>
                    <input id="leg-purpose" v-model="form.purpose" type="text" placeholder="Contoh: Pendaftaran PTN / Melamar CPNS" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </FormField>

                <FormField id="leg-attachments" label="Unggah Pindaian Dokumen (Ijazah / KTP / Syarat)">
                    <input id="leg-attachments" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" class="text-sm" @change="pickFiles" />
                </FormField>

                <AppButton type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Mengirim...' : 'Ajukan Legalisir' }}
                </AppButton>
            </form>

            <div v-if="myRequests.length" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-3">
                <h2 class="text-base font-semibold text-slate-900">Permohonan Legalisir Anda</h2>
                <div v-for="req in myRequests" :key="req.id" class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 p-4 text-sm">
                    <div>
                        <p class="font-semibold">{{ req.legalization_code }} — {{ req.document_type.toUpperCase() }} ({{ req.copy_count }} lembar)</p>
                        <p class="text-xs text-slate-600">No. {{ req.certificate_number }} • {{ req.picked_up_at ? `Diambil: ${req.picked_up_by_name}` : req.delivery_method }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <StatusBadge :status="req.status" :tone="statusTone(req.status)" size="sm" />
                        <a
                            v-if="req.legalization_document_id"
                            :href="`/layanan/legalisir/dokumen/${req.legalization_document_id}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-xs font-semibold text-emerald-700 underline"
                        >
                            Unduh Lembar Pengesahan
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
