<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { Head, Link, useForm } from '@inertiajs/vue3';
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

const form = useForm({
    full_name: props.profile?.full_name ?? props.accountName ?? '',
    nis: props.profile?.nis ?? '',
    nisn: props.profile?.nisn ?? '',
    graduation_year: props.profile?.graduation_year ?? new Date().getFullYear(),
    major_or_class: props.profile?.major_or_class ?? '',
    current_status: props.profile?.current_status ?? 'studying',
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
});

const pickProof = (event: Event) => {
    form.proof_file = (event.target as HTMLInputElement).files?.[0] ?? null;
};

const submit = () => {
    form.post('/alumni', { forceFormData: true, preserveScroll: true });
};
</script>

<template>
    <Head :title="`Portal &amp; Tracer Alumni — ${school.name}`" />

    <div class="min-h-screen bg-[#f8f6f0]" data-madrasah-service="alumni" :style="themeStyle">
        <PublicHeader :school="school" />

        <main class="mx-auto max-w-4xl px-4 py-10 space-y-6">
            <div class="madrasah-service-hero grid gap-5 lg:grid-cols-12 items-center">
                <div class="lg:col-span-8 space-y-3">
                    <p class="madrasah-service-label"><i class="fa-solid fa-user-graduate" aria-hidden="true"></i> Ikatan Keluarga Alumni (IKA)</p>
                    <h1 class="text-2xl font-bold text-slate-900">Temu Silaturahmi, Keluarga Besar Alumni &amp; Karir</h1>
                    <p class="mt-1 text-sm text-slate-600">Pembaruan karier, studi lanjut, beasiswa, testimoni, serta kontrol persetujuan publikasi direktori.</p>
                    <form action="/alumni/direktori" method="get" class="parity-hero-search">
                        <input name="search" type="search" aria-label="Cari alumni" placeholder="Cari nama alumni, kampus, atau instansi..." />
                        <button type="submit">Cari Alumni</button>
                    </form>
                    <div class="flex flex-wrap gap-2 pt-1">
                        <a href="#tracer-alumni" class="rounded-xl bg-amber-400 px-4 py-2 text-xs font-extrabold text-slate-950">Isi / Update Data Alumni</a>
                        <Link href="/alumni/direktori" class="rounded-xl bg-white px-4 py-2 text-xs font-bold text-slate-800">
                            Lihat Direktori Alumni
                        </Link>
                    </div>
                </div>
                <div class="parity-alumni-visual lg:col-span-4 rounded-2xl border border-white/20 bg-white/10 p-4 text-xs text-emerald-50 space-y-2">
                    <img src="/madrasah-theme/images/classroom.webp" alt="Ilustrasi jejaring alumni" class="parity-alumni-image" />
                    <p class="font-extrabold uppercase tracking-wider text-amber-300">Jejaring Almamater</p>
                    <div class="flex flex-wrap gap-1.5">
                        <Link href="/alumni/direktori?current_status=studying" class="rounded-lg bg-white/15 px-2.5 py-1 hover:bg-white/25">🎓 Kuliah</Link>
                        <Link href="/alumni/direktori?current_status=working" class="rounded-lg bg-white/15 px-2.5 py-1 hover:bg-white/25">💼 Bekerja</Link>
                        <Link href="/alumni/direktori?current_status=entrepreneur" class="rounded-lg bg-white/15 px-2.5 py-1 hover:bg-white/25">🚀 Bisnis Mandiri</Link>
                    </div>
                </div>
            </div>

            <nav aria-labelledby="alumni-services-title">
                <h2 id="alumni-services-title" class="service-form-title mb-3">Membangun Kolaborasi Lulusan Berkelanjutan</h2>
                <div class="service-tiles">
                    <Link href="/alumni/direktori"><strong>Direktori Alumni</strong><span>Temukan alumni yang membuka profilnya untuk publik</span></Link>
                    <a href="#tracer-alumni"><strong>Tracer Study Mandiri</strong><span>Perbarui riwayat studi lanjut, pekerjaan, dan usaha</span></a>
                    <Link href="/alumni/direktori"><strong>Bursa Karir &amp; Beasiswa</strong><span>Info peluang karier dan beasiswa jejaring alumni</span></Link>
                    <a href="#tracer-alumni"><strong>E-KTA Digital</strong><span>Unduh kartu tanda anggota alumni terverifikasi</span></a>
                </div>
            </nav>

            <div id="tracer-alumni" v-if="!authenticatedAlumni" class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-950 space-y-2">
                <p class="font-bold">Masuk Sebagai Akun Alumni Terverifikasi</p>
                <p>Daftar atau masuk memakai tipe pemohon Alumni terlebih dahulu agar perubahan tracer dan penerbitan E-KTA hanya dilakukan oleh pemiliknya.</p>
                <Link href="/layanan/ptsp/akun" class="inline-block rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white">
                    Buka Akun Pemohon / Alumni
                </Link>
            </div>

            <div id="tracer-alumni" v-else class="space-y-6">
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

                <form @submit.prevent="submit" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                    <div class="grid gap-3 md:grid-cols-3">
                        <FormField id="al-name" label="Nama Lengkap" :error="form.errors.full_name" required>
                            <input id="al-name" v-model="form.full_name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </FormField>
                        <FormField id="al-year" label="Tahun Lulus" :error="form.errors.graduation_year" required>
                            <input id="al-year" v-model.number="form.graduation_year" type="number" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </FormField>
                        <FormField id="al-major" label="Jurusan / Kelas Akhir">
                            <input id="al-major" v-model="form.major_or_class" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </FormField>
                    </div>

                    <div class="grid gap-3 md:grid-cols-3">
                        <FormField id="al-status" label="Status Saat Ini" required>
                            <select id="al-status" v-model="form.current_status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option v-for="s in statusOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                        </FormField>
                        <FormField id="al-inst" label="Kampus / Perusahaan / Usaha">
                            <input id="al-inst" v-model="form.institution_or_company" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </FormField>
                        <FormField id="al-pos" label="Program Studi / Jabatan">
                            <input id="al-pos" v-model="form.position_or_program" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </FormField>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <FormField id="al-scholarship" label="Info Beasiswa / Prestasi Alumni">
                            <input id="al-scholarship" v-model="form.scholarship_info" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </FormField>
                        <FormField id="al-email" label="Email Kontak">
                            <input id="al-email" v-model="form.email" type="email" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </FormField>
                    </div>

                    <FormField id="al-testimonial" label="Testimoni untuk Madrasah / Sekolah">
                        <textarea id="al-testimonial" v-model="form.testimonial" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2 text-sm">
                        <p class="font-semibold text-slate-800">Preferensi Privasi &amp; Persetujuan Publikasi Direktori</p>
                        <label class="flex items-center gap-2"><input v-model="form.opt_in_directory" type="checkbox" /> Tampilkan profil saya di Direktori Alumni (setelah moderasi admin)</label>
                        <label class="flex items-center gap-2"><input v-model="form.opt_in_career" type="checkbox" /> Tampilkan informasi instansi/karier/beasiswa</label>
                        <label class="flex items-center gap-2"><input v-model="form.opt_in_testimonial" type="checkbox" /> Tampilkan testimoni saya</label>
                        <label class="flex items-center gap-2"><input v-model="form.opt_in_contact" type="checkbox" /> Izinkan publikasi email/kontak profesional</label>
                    </div>

                    <FormField id="al-proof" label="Berkas Pembuktian (KTM / Surat Diterima / Bukti Kerja)">
                        <input id="al-proof" type="file" accept=".pdf,.jpg,.jpeg,.png" class="text-sm" @change="pickProof" />
                    </FormField>

                    <AppButton type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Profil Alumni' }}
                    </AppButton>
                </form>
            </div>
        </main>
    </div>
</template>
