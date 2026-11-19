<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    categories: { value: string; label: string }[];
    issuedReceipt?: {
        report_code: string;
        ticket_code?: string | null;
        verification_secret: string;
        is_anonymous: boolean;
    } | null;
}>();

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

const submit = () => {
    form.post('/layanan/lapor', { forceFormData: true, preserveScroll: true });
};
</script>

<template>
    <Head :title="`Siap Lapor — ${school.name}`" />

    <div class="min-h-screen bg-[#f8f6f0]" :style="themeStyle">
        <PublicHeader :school="school" />

        <main class="mx-auto max-w-4xl px-4 py-10 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Siap Lapor — Kanal Pengaduan Aman</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Sampaikan laporan bernama atau anonim. Identitas pelapor anonim tidak disimpan dan laporan diproses khusus oleh Satgas berwenang.
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link href="/lapor/transparansi" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
                        Transparansi Publik
                    </Link>
                    <Link href="/lacak" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white">
                        Lacak Tiket Aduan
                    </Link>
                </div>
            </div>

            <div v-if="issuedReceipt" class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-950 space-y-1">
                <p class="font-bold">Laporan Pengaduan Diterima!</p>
                <p>Kode Aduan: <strong class="font-mono">{{ issuedReceipt.report_code }}</strong> | Tiket Pelacakan (/lacak): <strong class="font-mono">{{ issuedReceipt.ticket_code }}</strong> | PIN Rahasia: <strong class="font-mono">{{ issuedReceipt.verification_secret }}</strong></p>
                <p class="text-xs">{{ issuedReceipt.is_anonymous ? 'Mode Anonim Aktif — Identitas pelapor tidak disimpan di sistem.' : 'Laporan Bernama — Gunakan tiket dan PIN untuk memantau balasan Satgas.' }}</p>
            </div>

            <form @submit.prevent="submit" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
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
                    <FormField id="adu-category" label="Kategori Pengaduan" :error="form.errors.category" required>
                        <select id="adu-category" v-model="form.category" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                        </select>
                    </FormField>
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
        </main>
    </div>
</template>
