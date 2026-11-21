<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, ref } from 'vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    categories: { value: string; label: string }[];
    hosts: { id: number; name: string }[];
    confirmationSlip?: {
        visit_code: string;
        visitor_name: string;
        host_name: string;
        checked_in_at: string;
    } | null;
}>();

const page = usePage();
const flashMessage = computed(() => (page.props.flash as { message?: string } | undefined)?.message);
const themeStyle = computed(() => publicThemeStyle(props.school.theme));

const form = useForm({
    visitor_name: '',
    visitor_organization: '',
    visitor_phone: '',
    visitor_email: '',
    category: 'tamu_dinas',
    purpose: '',
    host_teacher_id: '' as string | number,
    host_name: '',
    host_unit: '',
    notes: '',
    photo: null as File | null,
});

const camera = ref<HTMLVideoElement>();
const cameraActive = ref(false);
const cameraBusy = ref(false);
const cameraError = ref('');
const photoPreview = ref('');
let media: MediaStream | undefined;
let cameraSession = 0;

function stopCamera() {
    cameraSession++;
    media?.getTracks().forEach((track) => track.stop());
    media = undefined;
    cameraActive.value = false;
    cameraBusy.value = false;
}

function clearPhoto() {
    if (photoPreview.value) URL.revokeObjectURL(photoPreview.value);
    photoPreview.value = '';
    form.photo = null;
}

async function startCamera() {
    cameraError.value = '';
    if (!navigator.mediaDevices?.getUserMedia) {
        cameraError.value = 'Kamera tidak tersedia. Anda dapat memilih foto JPEG atau melanjutkan tanpa foto.';
        return;
    }
    const session = ++cameraSession;
    cameraBusy.value = true;
    try {
        const acquired = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: 640, height: 480 } });
        if (session !== cameraSession) {
            acquired.getTracks().forEach((track) => track.stop());
            return;
        }
        media = acquired;
        cameraActive.value = true;
        await nextTick();
        if (camera.value && session === cameraSession) {
            camera.value.srcObject = acquired;
            await camera.value.play();
        }
    } catch {
        if (session === cameraSession) {
            stopCamera();
            cameraError.value = 'Kamera tidak dapat dibuka. Periksa izin kamera atau pilih foto JPEG.';
        }
    } finally {
        if (session === cameraSession) cameraBusy.value = false;
    }
}

async function capturePhoto() {
    const preview = camera.value;
    if (!preview?.videoWidth) return;
    const canvas = document.createElement('canvas');
    canvas.width = 640;
    canvas.height = Math.round(640 * preview.videoHeight / preview.videoWidth);
    const context = canvas.getContext('2d');
    if (!context) return;
    context.drawImage(preview, 0, 0, canvas.width, canvas.height);
    const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/jpeg', .82));
    if (!blob) {
        cameraError.value = 'Foto belum dapat diambil. Coba sekali lagi.';
        return;
    }
    clearPhoto();
    form.photo = new File([blob], 'foto-kunjungan.jpg', { type: 'image/jpeg' });
    photoPreview.value = URL.createObjectURL(blob);
    stopCamera();
}

function choosePhoto(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    if (file.type !== 'image/jpeg' || file.size > 524288) {
        cameraError.value = 'Pilih foto JPEG dengan ukuran maksimal 512 KB.';
        input.value = '';
        return;
    }
    clearPhoto();
    stopCamera();
    cameraError.value = '';
    form.photo = file;
    photoPreview.value = URL.createObjectURL(file);
}

onBeforeUnmount(() => { stopCamera(); clearPhoto(); });

const selectedHostOption = ref<string | number>('');

function onHostSelectChange() {
    if (selectedHostOption.value === 'lainnya') {
        form.host_teacher_id = '';
    } else {
        form.host_teacher_id = selectedHostOption.value;
        form.host_name = '';
    }
}

const submit = () => {
    form.post('/layanan/buku-tamu', {
        forceFormData: true,
        onSuccess: () => {
            form.reset('visitor_name', 'visitor_organization', 'visitor_phone', 'visitor_email', 'purpose', 'host_teacher_id', 'host_name', 'host_unit', 'notes');
            selectedHostOption.value = '';
            clearPhoto();
            stopCamera();
        },
    });
};
</script>

<template>
    <Head :title="`Buku Tamu Digital — ${school.name}`" />
    <main class="public-site public-portal-page" data-madrasah-service="guestbook" :style="themeStyle">
        <PublicHeader :school="school" />
        <div class="parity-latar-bottom" aria-hidden="true">
            <img src="/madrasah-theme/images/guestbook/login-backg1.png" alt="" />
        </div>

        <section class="portal-container guestbook-layout">
            <header class="portal-hero madrasah-service-hero guestbook-welcome">
                <p class="madrasah-service-label"><i class="fa-solid fa-book-open" aria-hidden="true"></i> Resepsionis &amp; Penerimaan Tamu</p>
                <h2 class="parity-guest-greeting">Selamat datang</h2>
                <h1>BUKU TAMU</h1>
                <h3 class="guestbook-school"><b>{{ school.name.toUpperCase() }}</b></h3>
                <div class="parity-guest-emblem" aria-hidden="true">
                    <img src="/madrasah-theme/images/guestbook/LOGO2.png" alt="Electronic Guestbook" />
                </div>
                <p>Silakan isi data kunjungan Anda untuk dicatat oleh resepsionis. Data pengunjung bersifat privat dan hanya dapat dilihat oleh petugas berwenang.</p>
                <ul class="guestbook-highlights">
                    <li><i class="fa-solid fa-clock" aria-hidden="true"></i> Jam Resepsionis: Senin–Jumat, 07.30–15.00</li>
                    <li><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Data kunjungan terjaga kerahasiaannya</li>
                    <li><i class="fa-solid fa-ticket" aria-hidden="true"></i> Dapatkan bukti registrasi kunjungan instan</li>
                </ul>
            </header>

            <div v-if="flashMessage" class="alert-banner" role="status">{{ flashMessage }}</div>

            <section v-if="confirmationSlip" class="slip-card" role="status">
                <strong>Bukti Registrasi Tamu</strong>
                <div class="slip-code">{{ confirmationSlip.visit_code }}</div>
                <p>Pengunjung: <strong>{{ confirmationSlip.visitor_name }}</strong> · Tujuan: <strong>{{ confirmationSlip.host_name }}</strong></p>
            </section>

            <section class="portal-card guestbook-register" aria-labelledby="guestbook-register-title">
                <div class="parity-heading-register">
                    <h2 id="guestbook-register-title" class="service-form-title parity-register-badge">REGISTER TAMU</h2>
                </div>
                <div class="parity-form-register-inner">
                    <div class="parity-guestbook-split">
                        <form class="form-grid" @submit.prevent="submit">
                            <FormField id="guest-name" label="Nama Pengunjung" required :error="form.errors.visitor_name" v-slot="{ inputId, describedBy, invalid }">
                                <input :id="inputId" v-model="form.visitor_name" type="text" placeholder="Nama" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                            </FormField>

                            <FormField id="guest-phone" label="Nomor HP / Kontak" required :error="form.errors.visitor_phone" v-slot="{ inputId, describedBy, invalid }">
                                <input :id="inputId" v-model="form.visitor_phone" type="text" placeholder="No Telpon" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                            </FormField>

                            <FormField id="guest-org" label="Instansi / Asal / Hubungan" :error="form.errors.visitor_organization" v-slot="{ inputId, describedBy, invalid }">
                                <input :id="inputId" v-model="form.visitor_organization" type="text" placeholder="Asal Instansi" :aria-describedby="describedBy" :aria-invalid="invalid" />
                            </FormField>

                            <FormField id="guest-category" label="Kategori Tamu" required :error="form.errors.category" v-slot="{ inputId, describedBy, invalid }">
                                <select :id="inputId" v-model="form.category" :aria-describedby="describedBy" :aria-invalid="invalid">
                                    <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                                </select>
                            </FormField>

                            <div class="full-span">
                                <FormField id="guest-notes" label="Alamat / Catatan Tambahan" :error="form.errors.notes" v-slot="{ inputId, describedBy, invalid }">
                                    <input :id="inputId" v-model="form.notes" type="text" placeholder="Alamat / Catatan (Opsional)" :aria-describedby="describedBy" :aria-invalid="invalid" />
                                </FormField>
                            </div>

                            <div class="full-span parity-isian-row">
                                <div class="parity-isian-label">
                                    <label for="guest-host-select">Bertemu</label>
                                </div>
                                <div class="parity-isian-field">
                                    <FormField id="guest-host-select" label="Pegawai / Guru Tujuan" :error="form.errors.host_teacher_id" v-slot="{ inputId, describedBy, invalid }">
                                        <select :id="inputId" v-model="selectedHostOption" :aria-describedby="describedBy" :aria-invalid="invalid" @change="onHostSelectChange">
                                            <option value="">-- Pilih Pejabat / Pegawai yang Dituju --</option>
                                            <option v-for="h in hosts" :key="h.id" :value="h.id">{{ h.name }}</option>
                                            <option value="lainnya">-- Lainnya / Input Manual --</option>
                                        </select>
                                    </FormField>
                                </div>
                            </div>

                            <div v-if="selectedHostOption === 'lainnya'" class="full-span parity-isian-row parity-isian-row--manual">
                                <div class="parity-isian-label parity-isian-label--manual">
                                    <label for="guest-host-manual">Nama Pegawai</label>
                                </div>
                                <div class="parity-isian-field">
                                    <FormField id="guest-host-manual" label="Nama Tujuan Lain / Unit" :error="form.errors.host_name" v-slot="{ inputId, describedBy, invalid }">
                                        <input :id="inputId" v-model="form.host_name" type="text" placeholder="Ketik Nama Pegawai / Tujuan" :aria-describedby="describedBy" :aria-invalid="invalid" />
                                    </FormField>
                                </div>
                            </div>

                            <div class="full-span parity-isian-row">
                                <div class="parity-isian-label">
                                    <label for="guest-purpose">Keperluan</label>
                                </div>
                                <div class="parity-isian-field">
                                    <FormField id="guest-purpose" label="Keperluan Kunjungan" required :error="form.errors.purpose" v-slot="{ inputId, describedBy, invalid }">
                                        <input :id="inputId" v-model="form.purpose" type="text" placeholder="Ketik Keperluan Anda" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                                    </FormField>
                                </div>
                            </div>

                            <div class="full-span parity-submit-row">
                                <button type="submit" class="ui-button ui-button--primary ui-button--md" :disabled="form.processing">
                                    Register
                                </button>
                            </div>
                        </form>

                        <aside class="parity-guest-photo" aria-labelledby="guest-photo-title">
                            <div class="parity-ambil-badge">
                                <h2 id="guest-photo-title">Ambil Foto</h2>
                            </div>
                            <video v-if="cameraActive" ref="camera" class="parity-photo-video" muted playsinline aria-label="Pratinjau kamera foto kunjungan" />
                            <div v-else class="parity-photo-frame">
                                <img v-if="photoPreview" :src="photoPreview" alt="Foto kunjungan yang dipilih" />
                                <div v-else class="parity-photo-placeholder">
                                    <i class="fa-solid fa-camera" aria-hidden="true"></i>
                                </div>
                            </div>
                            <div class="parity-camera-actions">
                                <button v-if="cameraActive" type="button" class="parity-ambilfoto-btn" title="Ambil Foto" aria-label="Ambil Foto" @click="capturePhoto">
                                    <i class="fa-solid fa-camera" aria-hidden="true"></i>
                                </button>
                                <button v-else type="button" class="parity-ambilfoto-btn" :disabled="cameraBusy" :title="cameraBusy ? 'Membuka Kamera...' : 'Buka Kamera'" :aria-label="cameraBusy ? 'Membuka Kamera...' : 'Buka Kamera'" @click="startCamera">
                                    <i class="fa-solid fa-camera" aria-hidden="true"></i>
                                </button>
                                <button v-if="cameraActive" type="button" class="parity-camera-secondary" @click="stopCamera">Batal</button>
                                <label for="guest-photo-file" class="parity-photo-file">Pilih foto JPEG
                                    <input id="guest-photo-file" type="file" accept="image/jpeg" @change="choosePhoto" />
                                </label>
                                <button v-if="photoPreview" type="button" class="parity-camera-secondary" @click="clearPhoto">Hapus Foto</button>
                            </div>
                            <p v-if="cameraError || form.errors.photo" class="parity-camera-error" role="alert">{{ cameraError || form.errors.photo }}</p>
                        </aside>
                    </div>
                </div>
            </section>
        </section>
    </main>
</template>

<style scoped>
.public-portal-page { min-height: 100vh; background: var(--public-surface, #f5f8fb); color: var(--public-ink, #173044); }
.parity-latar-bottom { position: fixed; bottom: 0; left: 0; right: 0; z-index: 0; opacity: 0.8; mix-blend-mode: overlay; pointer-events: none; }
.parity-latar-bottom img { display: block; width: 100%; height: auto; }
.portal-container { width: min(920px, calc(100% - 2rem)); margin-inline: auto; padding: 2.25rem 0 4rem; display: grid; gap: 1.5rem; }
.portal-hero h1 { margin: .35rem 0 .45rem; font-size: clamp(1.75rem, 3.5vw, 2.5rem); line-height: 1.15; }
.portal-hero p { margin: 0; color: var(--public-muted, #526673); max-width: 65ch; }
.portal-card { background: #fff; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 16px; padding: 1.5rem; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; }
.full-span { grid-column: 1 / -1; }
.alert-banner { padding: .85rem 1rem; border-radius: 12px; background: #e1f2e8; color: #1b5c3e; border: 1px solid #b6dec9; font-weight: 600; }
.slip-card { background: #eef4ff; border: 1px solid #bfdbfe; border-radius: 14px; padding: 1.1rem 1.25rem; display: grid; gap: .3rem; }
.slip-code { font-size: 1.35rem; font-weight: 800; color: #1e3a8a; letter-spacing: .06em; }
.slip-card p { margin: 0; }
.guestbook-school { font-weight: 700; color: #fde68a !important; }
.guestbook-highlights { list-style: none; margin: 1rem 0 0; padding: 0; display: grid; gap: .5rem; text-align: left; font-size: .85rem; }
.guestbook-highlights li { display: flex; align-items: center; gap: .5rem; color: #d1fae5; }
</style>
