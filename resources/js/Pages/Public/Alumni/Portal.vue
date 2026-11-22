<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface AlumniProfileItem {
    id: number;
    full_name: string;
    nis?: string | null;
    nisn?: string | null;
    graduation_year: number;
    major_or_class?: string | null;
    current_status: string;
    institution_or_company?: string | null;
    position_or_program?: string | null;
    scholarship_info?: string | null;
    testimonial?: string | null;
    phone?: string | null;
    email?: string | null;
    diploma_serial?: string | null;
    activity_location?: string | null;
    residence_city?: string | null;
    linkedin_url?: string | null;
    instagram_handle?: string | null;
    photo_document_id?: number | null;
    opt_in_directory: boolean;
    opt_in_contact: boolean;
    opt_in_career: boolean;
    opt_in_testimonial: boolean;
    moderation_status: string;
    ekta_number?: string | null;
    ekta_document_id?: number | null;
    ekta_verification_token?: string | null;
}

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    authenticatedAlumni: boolean;
    accountName?: string | null;
    accountEmail?: string | null;
    statusOptions: { value: string; label: string }[];
    profile?: AlumniProfileItem | null;
}>();

const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const page = usePage();
const flashMessage = computed(() => (page.props.flash as { message?: string } | undefined)?.message);

const form = useForm({
    full_name: props.profile?.full_name ?? props.accountName ?? '',
    nis: props.profile?.nis ?? '',
    nisn: props.profile?.nisn ?? '',
    graduation_year: props.profile?.graduation_year ?? '',
    major_or_class: props.profile?.major_or_class ?? '',
    current_status: props.profile?.current_status ?? '',
    institution_or_company: props.profile?.institution_or_company ?? '',
    position_or_program: props.profile?.position_or_program ?? '',
    scholarship_info: props.profile?.scholarship_info ?? '',
    testimonial: props.profile?.testimonial ?? '',
    phone: props.profile?.phone ?? '',
    email: props.profile?.email ?? props.accountEmail ?? '',
    opt_in_directory: props.profile?.opt_in_directory ?? false,
    opt_in_contact: props.profile?.opt_in_contact ?? false,
    opt_in_career: props.profile?.opt_in_career ?? false,
    opt_in_testimonial: props.profile?.opt_in_testimonial ?? false,
    proof_file: null as File | null,
    photo_file: null as File | null,
    diploma_serial: props.profile?.diploma_serial ?? '',
    activity_location: props.profile?.activity_location ?? '',
    residence_city: props.profile?.residence_city ?? '',
    linkedin_url: props.profile?.linkedin_url ?? '',
    instagram_handle: props.profile?.instagram_handle ?? '',
});

const latestGraduationYear = Math.max(new Date().getFullYear(), props.profile?.graduation_year ?? 0);
const graduationYears = Array.from({ length: latestGraduationYear - 1979 }, (_, index) => latestGraduationYear - index);

const pickProof = (event: Event) => {
    form.proof_file = (event.target as HTMLInputElement).files?.[0] ?? null;
};

const pickPhoto = (event: Event) => {
    form.photo_file = (event.target as HTMLInputElement).files?.[0] ?? null;
};

const submit = () => {
    if (!props.authenticatedAlumni) return;
    form.post('/layanan/tracer-alumni', { forceFormData: true, preserveScroll: true });
};
</script>

<template>
    <Head :title="`Portal &amp; Tracer Alumni — ${school.name}`" />

    <div class="bg-slate-50" :style="themeStyle">
        <PublicHeader :school="school" />

        <section class="alumni-hero">
            <p class="alumni-hero-badge">Pusat Database Alumni &amp; Tracer Study</p>
            <h1>{{ school.name }}</h1>
            <p class="alumni-hero-description">Penelusuran Data Kelulusan, Studi Lanjut, Karir &amp; Pendataan Mandiri Siswa Alumni</p>
        </section>
        <main id="tracer-alumni" class="alumni-container">
            <section class="alumni-ika"><div><span>Website Resmi IKA Alumni</span><h2>Ingin Akses Komunitas &amp; KTA Digital Alumni?</h2><p>Kunjungi website Ikatan Keluarga Alumni untuk direktori, jejaring, dan informasi komunitas.</p></div><a href="/alumni">Buka Web Alumni (IKA) →</a></section>
            <section id="tracer-form" class="alumni-panel">
                <p v-if="flashMessage" role="status" class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ flashMessage }}</p>
                <header class="alumni-form-heading">
                    <div>
                        <h2><i class="fa-solid fa-id-card text-blue-600" aria-hidden="true"></i> Formulir Pendaftaran &amp; Pembaruan Status Alumni</h2>
                        <p>Alumni angkatan lama maupun baru dapat mendaftarkan diri secara mandiri dan memperbarui aktivitas terkini.</p>
                    </div>
                    <span class="alumni-integration"><i class="fa-solid fa-shield text-green-600" aria-hidden="true"></i> Terintegrasi Database Madrasah</span>
                </header>
                <form action="/alumni/direktori" method="get" class="alumni-lookup">
                    <span class="alumni-lookup-icon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <div>
                        <label for="alumni-directory-search">Sudah pernah terdaftar? Cari di Direktori Alumni:</label>
                        <div class="alumni-lookup-controls">
                            <input id="alumni-directory-search" name="search" type="search" placeholder="Ketik Nama Lengkap Anda…" />
                            <button type="submit"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Cari Data</button>
                        </div>
                    </div>
                </form>
                <div id="alumni-kta" class="ika-kta-info"><h3>KTA Digital Alumni</h3><p>Kartu alumni tersedia setelah data Anda diverifikasi dan disetujui oleh pengelola.</p></div>
                <div v-if="profile" class="rounded-2xl border border-slate-200 bg-white p-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Status Moderasi Direktori &amp; E-KTA</p>
                        <p class="text-xs text-slate-600">
                            E-KTA: <strong class="font-mono">{{ profile.ekta_number || 'Menunggu persetujuan moderasi' }}</strong>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <StatusBadge :status="profile.moderation_status" :tone="profile.moderation_status === 'approved' ? 'success' : 'warning'" size="sm" />
                        <a
                            v-if="profile.ekta_document_id"
                            :href="`/alumni/${profile.id}/ekta.pdf`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white"
                        >
                            Unduh E-KTA (PDF)
                        </a>
                    </div>
                </div>

                <form @submit.prevent="submit">
                    <fieldset :disabled="!authenticatedAlumni" class="space-y-4">
                    <legend class="alumni-legend"><i class="fa-solid fa-circle-user text-blue-600" aria-hidden="true"></i> 1. Identitas Alumni</legend>
                    <div class="alumni-fields">
                        <FormField id="al-nisn" label="NISN / Nomor Induk" :error="form.errors.nisn" hint="Jika belum memiliki NISN atau lupa nomornya, silakan dikosongkan.">
                            <input id="al-nisn" v-model="form.nisn" type="text" inputmode="numeric" placeholder="Contoh: 0051234567" />
                        </FormField>
                        <FormField id="al-name" label="Nama Lengkap Siswa / Alumni" :error="form.errors.full_name" required>
                            <input id="al-name" v-model="form.full_name" type="text" placeholder="Nama lengkap sesuai ijazah" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </FormField>
                        <FormField id="al-year" label="Tahun Kelulusan" :error="form.errors.graduation_year" required>
                            <select id="al-year" v-model="form.graduation_year" required>
                                <option value="">-- Pilih Tahun Lulus --</option>
                                <option v-for="year in graduationYears" :key="year" :value="year">Lulusan {{ year }}</option>
                            </select>
                        </FormField>
                        <FormField id="al-major" label="Jurusan / Peminatan">
                            <input id="al-major" v-model="form.major_or_class" type="text" placeholder="Contoh: MIPA / IPS / Keagamaan" />
                        </FormField>
                        <FormField id="al-phone" label="Nomor WhatsApp Aktif" :error="form.errors.phone">
                            <input id="al-phone" v-model="form.phone" type="tel" placeholder="Contoh: 081234567890" />
                        </FormField>
                        <FormField id="al-email" label="Alamat Email" :error="form.errors.email">
                            <input id="al-email" v-model="form.email" type="email" placeholder="email@gmail.com" />
                        </FormField>
                        <FormField id="al-diploma" label="Nomor Seri Ijazah" :error="form.errors.diploma_serial">
                            <input id="al-diploma" v-model="form.diploma_serial" type="text" maxlength="100" placeholder="Contoh: DN-01/MA/2026/001234" />
                        </FormField>
                        <FormField id="al-proof" label="Scan Ijazah / SKL Asli" :error="form.errors.proof_file" hint="PDF / JPG / PNG / WebP, maks. 5 MB">
                            <input id="al-proof" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" @change="pickProof" />
                        </FormField>
                    </div>

                    <h3 class="alumni-legend"><i class="fa-solid fa-briefcase text-green-600" aria-hidden="true"></i> 2. Aktivitas &amp; Status Terkini</h3>
                    <div class="alumni-fields">
                        <FormField id="al-status" label="Status Saat Ini" :error="form.errors.current_status" required>
                            <select id="al-status" v-model="form.current_status" required>
                                <option value="">-- Pilih Status --</option>
                                <option v-for="s in statusOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                        </FormField>
                        <FormField id="al-inst" label="Nama Perusahaan / Kampus / Nama Usaha">
                            <input id="al-inst" v-model="form.institution_or_company" type="text" placeholder="Contoh: Universitas Lambung Mangkurat / PT. Telkom" />
                        </FormField>
                        <FormField id="al-pos" label="Jabatan / Posisi / Program Studi">
                            <input id="al-pos" v-model="form.position_or_program" type="text" placeholder="Contoh: Staff IT / S1 Teknik Informatika" />
                        </FormField>
                        <FormField id="al-location" label="Kota / Lokasi Tempat Bekerja atau Kuliah" :error="form.errors.activity_location">
                            <input id="al-location" v-model="form.activity_location" type="text" maxlength="150" placeholder="Contoh: Banjarmasin / Jakarta Selatan" />
                        </FormField>
                    </div>

                    <h3 class="alumni-legend"><i class="fa-solid fa-share-nodes text-cyan-600" aria-hidden="true"></i> 3. Kontak Tambahan &amp; Media Sosial</h3>
                    <div class="alumni-fields">
                        <FormField class="alumni-field-wide" id="al-residence" label="Kota Domisili / Tempat Tinggal Sekarang" :error="form.errors.residence_city">
                            <input id="al-residence" v-model="form.residence_city" type="text" maxlength="150" placeholder="Contoh: Banjarmasin, Kalimantan Selatan" />
                        </FormField>
                        <FormField id="al-linkedin" label="Profil LinkedIn" :error="form.errors.linkedin_url">
                            <input id="al-linkedin" v-model="form.linkedin_url" type="url" maxlength="255" placeholder="https://linkedin.com/in/username" />
                        </FormField>
                        <FormField id="al-instagram" label="Akun Instagram" :error="form.errors.instagram_handle">
                            <input id="al-instagram" v-model="form.instagram_handle" type="text" maxlength="50" placeholder="@username" />
                        </FormField>
                    </div>

                    <h3 class="alumni-legend"><i class="fa-solid fa-camera text-amber-500" aria-hidden="true"></i> 4. Foto Profil &amp; Testimoni Almamater</h3>
                    <div class="alumni-fields">
                    <FormField id="al-photo" label="Foto Profil Alumni" :error="form.errors.photo_file" :hint="profile?.photo_document_id ? 'Foto profil tersimpan.' : 'JPG / PNG / WebP, maks. 5 MB'">
                        <input id="al-photo" type="file" accept="image/jpeg,image/png,image/webp" @change="pickPhoto" />
                    </FormField>
                    <FormField id="al-testimonial" label="Testimoni untuk Almamater">
                        <textarea id="al-testimonial" v-model="form.testimonial" rows="3" placeholder="Tuliskan pengalaman atau pesan motivasi bagi adik kelas…" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2 text-sm">
                        <p class="font-semibold text-slate-800">Preferensi Privasi &amp; Persetujuan Publikasi Direktori</p>
                        <label class="flex items-center gap-2"><input v-model="form.opt_in_directory" type="checkbox" /> Tampilkan profil saya di Direktori Alumni (setelah moderasi admin)</label>
                        <label class="flex items-center gap-2"><input v-model="form.opt_in_career" type="checkbox" /> Tampilkan informasi instansi/karier/beasiswa</label>
                        <label class="flex items-center gap-2"><input v-model="form.opt_in_testimonial" type="checkbox" /> Tampilkan testimoni saya</label>
                        <label class="flex items-center gap-2"><input v-model="form.opt_in_contact" type="checkbox" /> Izinkan publikasi email/kontak profesional</label>
                    </div>

                    <AppButton v-if="authenticatedAlumni" type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Profil Alumni' }}
                    </AppButton>
                    </fieldset>
                    <a v-if="!authenticatedAlumni" href="/layanan/ptsp/akun?return_to=/layanan/tracer-alumni" class="mt-5 inline-block rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Masuk sebagai Alumni untuk mengisi formulir</a>
                </form>
            </section>

        </main>
    </div>
</template>

<style scoped>
.alumni-hero {
    padding: 45px 16px 50px;
    border-radius: 0 0 32px 32px;
    background: linear-gradient(120deg, #121720, #151e34 55%, #223775);
    box-shadow: 0 15px 28px #121a3040;
    text-align: center;
    color: white;
}
.alumni-hero-badge { display: inline-block; border: 1px solid #9e6926; border-radius: 999px; background: #a26b263b; padding: 5px 16px; color: #fde68a; font-size: 11px; font-weight: 800; text-transform: uppercase; }
.alumni-hero h1 { margin: 10px 0 4px; color: white; font-size: clamp(22px, 3vw, 28px); font-weight: 800; text-transform: uppercase; }
.alumni-hero-description { color: #93c5fd; font-size: 14px; }
.alumni-container { width: 90%; max-width: 1200px; margin: 25px auto 0; padding-bottom: 40px; }
.ika-kta-info { margin-block: 20px; padding: 16px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; }
.ika-kta-info h3 { font-weight: 700; }
.ika-kta-info p { color: #64748b; font-size: 14px; }
.alumni-ika, .alumni-tabs, .alumni-panel { border: 1px solid #dce5f0; box-shadow: 0 10px 28px #1729440f; }
.alumni-ika { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; border-radius: 20px; background: linear-gradient(120deg, #211e44, #141a2d 55%, #223775); padding: 24px 28px; color: white; }
.alumni-ika span, .alumni-ika a { border-radius: 4px; background: #ffc107; color: #252027; }
.alumni-ika span { padding: 2px 12px; text-transform: uppercase; font-size: 10px; font-weight: 700; }
.alumni-ika h2, .alumni-panel h2 { font-size: 20px; font-weight: 600; }
.alumni-ika p, .alumni-panel header p { font-size: 11px; color: #64748b; }
.alumni-ika p { color: #bac4da; }
.alumni-ika a { padding: 9px 16px; font-size: 13px; }
.alumni-tabs { display: flex; flex-wrap: wrap; margin: 25px 0 35px; border-radius: 30px; padding: 6px; background: white; }
.alumni-tabs a { flex: 1 1 max-content; border-radius: 24px; padding: 12px 14px; text-align: center; font-size: 14px; font-weight: 700; }
.alumni-tabs a[aria-current] { background: linear-gradient(120deg, #2342aa, #3b7df1); color: white; }
.alumni-panel { border-radius: 24px; background: white; padding: clamp(18px, 3.5vw, 35px); }
.alumni-form-heading { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px 16px; }
.alumni-integration { display: inline-flex; align-items: center; gap: 5px; border: 1px solid #d7dce2; border-radius: 3px; background: #f7f8fa; padding: 4px 12px; color: #65717d; font-size: 10px; font-weight: 700; }
.alumni-lookup { display: grid; grid-template-columns: auto minmax(0, 1fr); align-items: center; gap: 13px; margin: 20px 0 36px; padding: 14px 13px 13px 20px; border: 1px solid #bfd8ff; border-radius: 16px; background: linear-gradient(105deg, #eaf2ff, #dce8fa); }
.alumni-lookup-icon { display: grid; width: 48px; height: 48px; place-items: center; border-radius: 12px; background: #397df1; color: white; font-size: 20px; }
.alumni-lookup label { display: block; margin-bottom: 4px; font-size: 13px; font-weight: 700; }
.alumni-lookup-controls { display: flex; }
.alumni-lookup input { flex: 1; min-width: 0; border: 1px solid #c4d1e1; border-radius: 12px 0 0 12px; background: white; padding: 12px 15px; font-size: 14px; }
.alumni-lookup button { border-radius: 0 10px 10px 0; background: #007bff; padding: 12px 16px; color: white; font-size: 13px; font-weight: 700; }
.alumni-legend { display: flex; align-items: center; gap: 8px; width: 100%; margin-bottom: 20px; padding: 0 0 12px; border-bottom: 1px solid #e2e8f0; font-weight: 800; }
h3.alumni-legend { margin-top: 30px; }
.alumni-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px 30px; }
.alumni-fields :is(input, select) { width: 100%; min-height: 48px; border: 1px solid #c4d1e1; border-radius: 12px; background: white; padding: 10px 15px; font-size: 14px; }
.alumni-fields input[type="file"] { padding: 4px; }
.alumni-field-wide { grid-column: 1 / -1; }
@media (max-width: 560px) {
    .alumni-fields { grid-template-columns: minmax(0, 1fr); gap: 12px; }
    .alumni-lookup { grid-template-columns: minmax(0, 1fr); padding: 12px; }
    .alumni-lookup-icon { display: none; }
    .alumni-lookup button { padding-inline: 10px; }
}
</style>
