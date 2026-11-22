<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import LegalizationProcedure from '@/components/LegalizationProcedure.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

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
    school: { name: string; address?: string | null; logo_url?: string | null; theme?: { primary?: string | null } };
    myRequests: LegalizationItem[];
    issuedReceipt?: { id: number; legalization_code: string; ticket_code?: string | null; verification_secret: string } | null;
    contentBlocks?: Record<string, Record<string, any>>;
}>();

const heroBlock = computed(() => props.contentBlocks?.['legalization.hero'] ?? {});

const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const activeTab = ref<'alur' | 'form'>(props.issuedReceipt ? 'form' : 'alur');

const form = useForm({
    applicant_name: '',
    applicant_nis: '',
    applicant_nisn: '',
    graduation_year: new Date().getFullYear(),
    phone: '',
    email: '',
    document_type: 'ijazah',
    certificate_number: '',
    copy_count: 5,
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

const sigCanvasRef = ref<HTMLCanvasElement | null>(null);
const isDrawing = ref(false);

const setupSignaturePad = () => {
    const canvas = sigCanvasRef.value;
    if (!canvas) return;
    const rect = canvas.getBoundingClientRect();
    canvas.width = Math.max(320, Math.round(rect.width || 600));
    canvas.height = 160;
    const ctx = canvas.getContext('2d');
    if (ctx) {
        ctx.lineWidth = 2.2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#0f172a';
    }
};

const getCanvasPoint = (event: PointerEvent) => {
    const canvas = sigCanvasRef.value;
    if (!canvas) return { x: 0, y: 0 };
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / (rect.width || 1);
    const scaleY = canvas.height / (rect.height || 1);
    return {
        x: (event.clientX - rect.left) * scaleX,
        y: (event.clientY - rect.top) * scaleY,
    };
};

const startDraw = (event: PointerEvent) => {
    const canvas = sigCanvasRef.value;
    const ctx = canvas?.getContext('2d');
    if (!canvas || !ctx) return;
    canvas.setPointerCapture(event.pointerId);
    const { x, y } = getCanvasPoint(event);
    ctx.beginPath();
    ctx.moveTo(x, y);
    isDrawing.value = true;
};

const moveDraw = (event: PointerEvent) => {
    if (!isDrawing.value) return;
    const ctx = sigCanvasRef.value?.getContext('2d');
    if (!ctx) return;
    const { x, y } = getCanvasPoint(event);
    ctx.lineTo(x, y);
    ctx.stroke();
};

const endDraw = () => {
    if (!isDrawing.value) return;
    isDrawing.value = false;
    if (sigCanvasRef.value) {
        form.signature_data = sigCanvasRef.value.toDataURL('image/png');
    }
};

const clearSignature = () => {
    const canvas = sigCanvasRef.value;
    const ctx = canvas?.getContext('2d');
    if (canvas && ctx) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
    form.signature_data = 'data:text/plain;base64,VFREX0FMVU1OSQ==';
};

const openFormTab = () => {
    activeTab.value = 'form';
    requestAnimationFrame(() => setupSignaturePad());
};

onMounted(() => {
    setupSignaturePad();
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
    <Head :title="`LEGALISIR IJAZAH | ${school.name}`" />

    <div class="legalisir-page" data-madrasah-service="legalization" :style="themeStyle">
        <PublicHeader :school="school" />

        <section class="legalisir-hero-banner">
            <div class="legalisir-container legalisir-hero-inner">
                <div class="legalisir-hero-pill">
                    <i class="fa-solid fa-certificate" aria-hidden="true"></i>
                    <span>{{ heroBlock.badge || 'Layanan Legalisir Ijazah Online' }}</span>
                </div>
                <h1>{{ school.name.toUpperCase() }}</h1>
                <h4>{{ heroBlock.academic_year_label || 'Tahun Pelajaran 2026/2027' }}</h4>
                <p>
                    {{ heroBlock.description || 'Formulir Permohonan Legalisir, Pembuatan Surat Kuasa & SPTJM dengan Tanda Tangan Digital Langsung secara online.' }}
                </p>
            </div>
        </section>

        <div class="legalisir-container legalisir-tabs-wrap">
            <nav class="legalisir-tabs" aria-label="Navigasi layanan">
                <button type="button" class="btn-tab-menu" :class="{ active: activeTab === 'alur' }" @click="activeTab = 'alur'">
                    <i class="fa-solid fa-list-ol" aria-hidden="true"></i>
                    <span>Alur &amp; Ketentuan</span>
                </button>
                <button type="button" class="btn-tab-menu" :class="{ active: activeTab === 'form' }" @click="openFormTab">
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                    <span>Formulir Pengajuan</span>
                </button>
                <Link href="/layanan/surat-kuasa?jenis=power_of_attorney" class="btn-tab-menu">
                    <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                    <span>Cetak Surat Kuasa</span>
                </Link>
                <Link href="/layanan/surat-kuasa?jenis=sptjm" class="btn-tab-menu">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    <span>Cetak SPTJM</span>
                </Link>
                <Link href="/lacak?category=legalisir" class="btn-tab-menu">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span>Cek Status Pengajuan</span>
                </Link>
            </nav>
        </div>

        <main class="legalisir-container legalisir-main">
            <div v-show="activeTab === 'alur'" id="alur-legalisir" class="legalisir-alur-grid">
                <div>
                    <LegalizationProcedure :school-name="school.name" />
                </div>

                <aside class="legalisir-sidebar">
                    <div class="card-custom">
                        <div class="sidebar-card-header">
                            <i class="fa-solid fa-circle-check text-primary" aria-hidden="true"></i>
                            <span>Ringkasan Persyaratan</span>
                        </div>
                        <div class="sidebar-card-body">
                            <div class="check-item">
                                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                <span>Membawa <strong>Ijazah Asli</strong> untuk verifikasi fisik.</span>
                            </div>
                            <div class="check-item">
                                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                <span>Salinan <strong>Fotokopi Ijazah</strong> (kualitas cetak bersih &amp; jelas).</span>
                            </div>
                            <div class="check-item">
                                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                <span>Maksimal pengajuan disarankan <strong>5 - 10 lembar</strong> per permohonan.</span>
                            </div>
                            <div class="check-item">
                                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                <span>Jika diwakilkan: Wajib menyertakan <strong>Surat Kuasa bermaterai Rp 10.000</strong> &amp; <strong>SPTJM</strong>.</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-custom">
                        <div class="sidebar-card-header">
                            <i class="fa-regular fa-clock text-warning" aria-hidden="true"></i>
                            <span>Jadwal Pelayanan TU</span>
                        </div>
                        <div class="sidebar-card-body">
                            <table class="table-jam">
                                <tbody>
                                    <tr>
                                        <td><strong>Senin - Kamis</strong></td>
                                        <td class="text-right text-primary font-bold">08.00 - 14.00 WITA</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Jumat</strong></td>
                                        <td class="text-right text-primary font-bold">08.00 - 11.00 WITA</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Sabtu - Minggu</strong></td>
                                        <td class="text-right text-danger font-bold">Tutup / Libur</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Hari Libur Nasional</strong></td>
                                        <td class="text-right text-danger font-bold">Tutup / Libur</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-custom">
                        <div class="sidebar-card-header">
                            <i class="fa-solid fa-building-columns text-info" aria-hidden="true"></i>
                            <span>Lokasi &amp; Layanan</span>
                        </div>
                        <div class="sidebar-card-body">
                            <p class="sidebar-school-name">{{ school.name.toUpperCase() }}</p>
                            <p class="sidebar-school-address">
                                <i class="fa-solid fa-location-dot text-danger" aria-hidden="true"></i>
                                <span>{{ school.address || 'Alamat Tata Usaha Sekolah' }}</span>
                            </p>
                            <Link href="/alumni/direktori" class="sidebar-btn sidebar-btn-outline">
                                <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
                                <span>Cek Data Alumni</span>
                            </Link>
                            <Link href="/" class="sidebar-btn sidebar-btn-light">
                                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                                <span>Kembali ke Beranda</span>
                            </Link>
                        </div>
                    </div>
                </aside>
            </div>

            <div v-if="issuedReceipt" class="legalisir-receipt-alert" role="status">
                <p class="font-bold">Permohonan Legalisir Diterima!</p>
                <p>
                    Kode Legalisir: <strong class="font-mono">{{ issuedReceipt.legalization_code }}</strong> |
                    Tiket Lacak: <strong class="font-mono">{{ issuedReceipt.ticket_code }}</strong> |
                    PIN: <strong class="font-mono">{{ issuedReceipt.verification_secret }}</strong>
                </p>
                <p class="text-xs">
                    Jika diwakilkan, lanjutkan pembuatan
                    <Link :href="`/layanan/surat-kuasa?kode=${issuedReceipt.legalization_code}`" class="font-semibold underline">Surat Kuasa / SPTJM</Link>.
                </p>
            </div>

            <div v-show="activeTab === 'form' || !!issuedReceipt" class="card-custom legalisir-form-card">
                <div class="legalisir-form-head">
                    <div>
                        <h2 id="legalization-form-title">
                            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                            <span>Formulir Pengajuan Legalisir Online</span>
                        </h2>
                        <small>Isi data permohonan untuk mendapatkan nomor registrasi resmi</small>
                    </div>
                </div>

                <form aria-labelledby="legalization-form-title" class="space-y-4" @submit.prevent="submit">
                    <div class="form-section-header">
                        <i class="fa-solid fa-user" aria-hidden="true"></i>
                        <span>Data Alumni / Pemohon</span>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <FormField id="leg-name" label="Nama Lengkap Alumni" :error="form.errors.applicant_name" required>
                            <input id="leg-name" v-model="form.applicant_name" type="text" placeholder="Nama lengkap sesuai Ijazah" class="legalisir-input" />
                        </FormField>
                        <FormField id="leg-nisn" label="NISN Alumni" :error="form.errors.applicant_nisn">
                            <input id="leg-nisn" v-model="form.applicant_nisn" type="text" placeholder="Masukkan 10 digit NISN..." class="legalisir-input" />
                        </FormField>
                        <FormField id="leg-year" label="Tahun Kelulusan" :error="form.errors.graduation_year" required>
                            <input id="leg-year" v-model.number="form.graduation_year" type="number" class="legalisir-input" />
                        </FormField>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <FormField id="leg-phone" label="No. Telepon / WhatsApp Aktif" :error="form.errors.phone" required>
                            <input id="leg-phone" v-model="form.phone" type="text" placeholder="08xxxxxxxxxx" class="legalisir-input" />
                        </FormField>
                        <FormField id="leg-email" label="Email (Opsional)" :error="form.errors.email">
                            <input id="leg-email" v-model="form.email" type="email" placeholder="nama@email.com" class="legalisir-input" />
                        </FormField>
                        <FormField id="leg-cert-no" label="Nomor Seri Ijazah" :error="form.errors.certificate_number" required>
                            <input id="leg-cert-no" v-model="form.certificate_number" type="text" placeholder="Contoh: DN-01/MA/13/..." class="legalisir-input" />
                        </FormField>
                    </div>

                    <div class="form-section-header">
                        <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                        <span>Rincian Permohonan Legalisir</span>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <FormField id="leg-doc-type" label="Jenis Dokumen" required>
                            <select id="leg-doc-type" v-model="form.document_type" class="legalisir-input">
                                <option value="ijazah">Ijazah</option>
                                <option value="skl">SKL</option>
                                <option value="transkrip">Transkrip Nilai</option>
                                <option value="rapor">Rapor</option>
                            </select>
                        </FormField>
                        <FormField id="leg-copies" label="Jumlah Lembar Legalisir" :error="form.errors.copy_count" required>
                            <input id="leg-copies" v-model.number="form.copy_count" type="number" min="1" max="20" class="legalisir-input" />
                        </FormField>
                        <FormField id="leg-delivery" label="Metode Pengambilan" required>
                            <select id="leg-delivery" v-model="form.delivery_method" class="legalisir-input">
                                <option value="pickup_direct">Hadir Sendiri</option>
                                <option value="pickup_proxy">Diwakilkan (Melalui Surat Kuasa)</option>
                            </select>
                        </FormField>
                    </div>

                    <div v-if="form.delivery_method === 'pickup_proxy'" class="legalisir-proxy-box">
                        <h6><i class="fa-solid fa-users" aria-hidden="true"></i> Data Perwakilan / Penerima Kuasa:</h6>
                        <div class="grid gap-3 md:grid-cols-4">
                            <FormField id="leg-proxy-name" label="Nama Penerima Kuasa" :error="form.errors.proxy_name" required>
                                <input id="leg-proxy-name" v-model="form.proxy_name" type="text" placeholder="Nama perwakilan..." class="legalisir-input" />
                            </FormField>
                            <FormField id="leg-proxy-nik" label="NIK / No. KTP Penerima Kuasa">
                                <input id="leg-proxy-nik" v-model="form.proxy_nik" type="text" placeholder="16 digit NIK..." class="legalisir-input" />
                            </FormField>
                            <FormField id="leg-proxy-rel" label="Hubungan">
                                <input id="leg-proxy-rel" v-model="form.proxy_relation" type="text" placeholder="Ortu/Kakak/dll" class="legalisir-input" />
                            </FormField>
                            <FormField id="leg-proxy-phone" label="No. HP Kuasa">
                                <input id="leg-proxy-phone" v-model="form.proxy_phone" type="text" placeholder="08xxxxxxxxxx" class="legalisir-input" />
                            </FormField>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <FormField id="leg-purpose" label="Keperluan Pengesahan / Legalisir" :error="form.errors.purpose" required>
                            <input id="leg-purpose" v-model="form.purpose" type="text" placeholder="Contoh: Melanjutkan Kuliah / Daftar Kerja / CPNS" class="legalisir-input" />
                        </FormField>
                        <FormField id="leg-attachments" label="Unggah Pindaian Dokumen (Ijazah / KTP / Syarat)">
                            <input id="leg-attachments" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" class="legalisir-input" @change="pickFiles" />
                        </FormField>
                    </div>

                    <div class="form-section-header">
                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                        <span>Tanda Tangan Digital Pemohon</span>
                    </div>

                    <div>
                        <label class="sig-label">Goreskan Tanda Tangan Pemohon di Kotak Bawah Ini:</label>
                        <div class="sig-container">
                            <canvas
                                ref="sigCanvasRef"
                                class="sig-canvas"
                                @pointerdown="startDraw"
                                @pointermove="moveDraw"
                                @pointerup="endDraw"
                                @pointerleave="endDraw"
                            ></canvas>
                        </div>
                        <div class="sig-toolbar">
                            <small><i class="fa-solid fa-arrow-pointer" aria-hidden="true"></i> Gunakan mouse atau sentuhan layar HP/tablet.</small>
                            <button type="button" class="btn-clear-sig" @click="clearSignature">
                                <i class="fa-solid fa-eraser" aria-hidden="true"></i> Hapus / Ulangi TTD
                            </button>
                        </div>
                    </div>

                    <hr class="legalisir-divider" />

                    <div class="text-center">
                        <button type="submit" class="btn-submit-legalisir" :disabled="form.processing">
                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                            <span>{{ form.processing ? 'Mengirim...' : 'Kirim Permohonan Legalisir' }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <div v-if="myRequests.length" class="card-custom legalisir-history-card">
                <h2>Permohonan Legalisir Anda</h2>
                <div class="space-y-3">
                    <div v-for="req in myRequests" :key="req.id" class="legalisir-history-item">
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
            </div>
        </main>
    </div>
</template>

<style scoped>
.legalisir-page {
    min-height: 100vh;
    background-color: #f8fafc;
    color: #1e293b;
}

.legalisir-container {
    width: 100%;
    max-width: 1140px;
    margin: 0 auto;
    padding-inline: 15px;
}

.legalisir-hero-banner {
    background: linear-gradient(135deg, #0f4c81 0%, #1b85df 100%);
    color: #ffffff;
    padding: 40px 0 30px;
    margin-bottom: 25px;
    border-radius: 0 0 24px 24px;
    box-shadow: 0 10px 25px rgba(15, 76, 129, 0.15);
    text-align: center;
}

.legalisir-hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    padding: 4px 12px;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
    font-size: 12px;
    font-weight: 700;
    color: #fef08a;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.legalisir-hero-banner h1 {
    margin: 0 0 6px;
    font-weight: 800;
    font-size: 26px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: #ffffff;
    line-height: 1.25;
}

.legalisir-hero-banner h4 {
    margin: 0 0 10px;
    font-weight: 600;
    font-size: 16px;
    opacity: 0.95;
    color: #93c5fd;
    line-height: 1.3;
}

.legalisir-hero-banner p {
    font-size: 14.5px;
    opacity: 0.9;
    max-width: 680px;
    margin: 0 auto;
    line-height: 1.6;
    color: #ffffff;
}

.legalisir-tabs-wrap {
    margin-bottom: 24px;
    text-align: center;
}

.legalisir-tabs {
    display: inline-flex;
    flex-wrap: wrap;
    justify-content: center;
}

.btn-tab-menu {
    color: #475569;
    background-color: #f1f5f9;
    border-radius: 10px;
    font-weight: 600;
    padding: 11px 20px;
    margin: 0 5px 10px 5px;
    border: 1px solid #cbd5e1;
    cursor: pointer;
    outline: none;
    transition: all 0.2s ease-in-out;
    font-size: 14.5px;
    line-height: 1.4;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
}

.btn-tab-menu:hover {
    background-color: #e2e8f0;
    color: #0284c7;
    text-decoration: none;
}

.btn-tab-menu.active {
    color: #ffffff;
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    border-color: #0284c7;
    box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35);
}

.legalisir-main {
    padding-bottom: 48px;
}

.legalisir-alur-grid {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
    gap: 30px;
    align-items: start;
}

.legalisir-sidebar {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.card-custom {
    border: none;
    border-radius: 16px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    background: #ffffff;
    overflow: hidden;
}

.sidebar-card-header {
    padding: 15px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 700;
    font-size: 15.5px;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sidebar-card-body {
    padding: 16px 20px;
}

.text-primary { color: #0284c7; }
.text-warning { color: #f59e0b; }
.text-info { color: #06b6d4; }
.text-danger { color: #dc2626; }

.check-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 10px;
    font-size: 13.5px;
    line-height: 1.5;
    color: #334155;
}

.check-item:last-child {
    margin-bottom: 0;
}

.check-item i {
    color: #22c55e;
    margin-right: 8px;
    margin-top: 3px;
    font-size: 14px;
    flex-shrink: 0;
}

.table-jam {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

.table-jam td {
    padding: 8px 6px;
    font-size: 13.5px;
    border-top: 1px solid #f1f5f9;
    color: #334155;
}

.table-jam tr:first-child td {
    border-top: 0;
}

.text-right {
    text-align: right;
}

.sidebar-school-name {
    margin: 0 0 4px;
    font-weight: 700;
    font-size: 14.5px;
    color: #1e293b;
}

.sidebar-school-address {
    margin: 0 0 16px;
    font-size: 12.5px;
    color: #64748b;
    display: flex;
    align-items: flex-start;
    gap: 6px;
    line-height: 1.5;
}

.sidebar-school-address i {
    margin-top: 3px;
    flex-shrink: 0;
}

.sidebar-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    padding: 7px 12px;
    font-size: 12.5px;
    font-weight: 600;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.sidebar-btn-outline {
    border: 1px solid #0284c7;
    color: #0284c7;
    background: #ffffff;
    margin-bottom: 8px;
}

.sidebar-btn-outline:hover {
    background: #0284c7;
    color: #ffffff;
}

.sidebar-btn-light {
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #475569;
}

.sidebar-btn-light:hover {
    background: #e2e8f0;
}

.legalisir-receipt-alert {
    margin-bottom: 20px;
    padding: 16px;
    border-radius: 12px;
    border: 1px solid #a7f3d0;
    background: #ecfdf5;
    color: #064e3b;
    font-size: 14px;
}

.legalisir-form-card {
    padding: 24px;
}

.legalisir-form-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid #e2e8f0;
}

.legalisir-form-head h2 {
    margin: 0 0 2px;
    font-size: 20px;
    font-weight: 700;
    color: #0284c7;
    display: flex;
    align-items: center;
    gap: 8px;
}

.legalisir-form-head small {
    font-size: 13px;
    color: #64748b;
}

.form-section-header {
    background: #e0f2fe;
    padding: 10px 16px;
    border-left: 4px solid #0284c7;
    border-radius: 6px;
    font-weight: 700;
    color: #0369a1;
    margin: 8px 0 14px;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.legalisir-input {
    width: 100%;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    padding: 9px 12px;
    font-size: 14px;
    color: #1e293b;
    background: #ffffff;
}

.legalisir-proxy-box {
    padding: 16px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.legalisir-proxy-box h6 {
    margin: 0 0 12px;
    font-size: 14px;
    font-weight: 700;
    color: #d97706;
    display: flex;
    align-items: center;
    gap: 6px;
}

.sig-label {
    display: block;
    font-size: 13.5px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
}

.sig-container {
    border: 2px dashed #94a3b8;
    border-radius: 10px;
    background: #ffffff;
    position: relative;
    margin-bottom: 8px;
    touch-action: none;
    cursor: crosshair;
}

.sig-canvas {
    width: 100%;
    height: 160px;
    display: block;
    border-radius: 10px;
}

.sig-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.sig-toolbar small {
    font-size: 12.5px;
    color: #64748b;
}

.btn-clear-sig {
    padding: 5px 12px;
    border-radius: 6px;
    border: 1px solid #94a3b8;
    background: #ffffff;
    color: #475569;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
}

.legalisir-divider {
    border: 0;
    border-top: 1px solid #e2e8f0;
    margin: 20px 0;
}

.btn-submit-legalisir {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 36px;
    border-radius: 10px;
    border: 0;
    background: #0284c7;
    color: #ffffff;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3);
}

.btn-submit-legalisir:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.legalisir-history-card {
    margin-top: 24px;
    padding: 24px;
}

.legalisir-history-card h2 {
    margin: 0 0 12px;
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
}

.legalisir-history-item {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px;
    font-size: 14px;
}

@media (max-width: 991.98px) {
    .legalisir-alur-grid {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
