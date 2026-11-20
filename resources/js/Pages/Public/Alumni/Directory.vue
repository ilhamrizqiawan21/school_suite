<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface DirectoryEntry {
    id: number;
    full_name: string;
    graduation_year: number;
    major_or_class?: string | null;
    status_label: string;
    institution_or_company?: string | null;
    position_or_program?: string | null;
    scholarship_info?: string | null;
    testimonial?: string | null;
    contact_email?: string | null;
}

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    alumni: DirectoryEntry[];
    statusOptions: { value: string; label: string }[];
    filters: { search: string; graduation_year: string; current_status: string };
}>();

const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const search = ref(props.filters.search);
const graduationYear = ref(props.filters.graduation_year);
const currentStatus = ref(props.filters.current_status);

const applyFilter = () => {
    router.get('/alumni/direktori', {
        search: search.value || undefined,
        graduation_year: graduationYear.value || undefined,
        current_status: currentStatus.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head :title="`Direktori Alumni — ${school.name}`" />

    <div class="min-h-screen bg-[#f8f6f0]" data-madrasah-service="alumni" :style="themeStyle">
        <PublicHeader :school="school" />

        <main class="mx-auto max-w-5xl px-4 py-10 space-y-6">
            <div class="madrasah-service-hero flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="madrasah-service-label"><i class="fa-solid fa-users" aria-hidden="true"></i> Ikatan Keluarga Alumni</p>
                    <h1 class="text-2xl font-bold text-slate-900">Direktori Temu Alumni &amp; Jejaring Almamater</h1>
                    <p class="mt-1 text-sm text-slate-600">Hanya menampilkan alumni yang telah dimoderasi dan menyetujui publikasi atribut profil.</p>
                </div>
                <Link href="/alumni" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white">
                    Perbarui Data Tracer / E-KTA
                </Link>
            </div>

            <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-4">
                <input v-model="search" type="text" placeholder="Cari nama, kampus, perusahaan..." class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                <input v-model="graduationYear" type="number" placeholder="Angkatan (tahun lulus)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                <select v-model="currentStatus" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Semua Status</option>
                    <option v-for="s in statusOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <AppButton type="button" @click="applyFilter">Cari Direktori</AppButton>
            </div>

            <StateFeedback v-if="alumni.length === 0" state="empty" title="Belum ada alumni terpublikasi" description="Alumni akan muncul setelah profil dimoderasi dan disetujui untuk publikasi." />

            <div v-else class="grid gap-4 md:grid-cols-2">
                <article v-for="entry in alumni" :key="entry.id" class="rounded-xl border border-slate-200 bg-white p-5 space-y-2 text-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h2 class="font-bold text-slate-900">{{ entry.full_name }}</h2>
                            <p class="text-xs text-slate-500">Angkatan {{ entry.graduation_year }} {{ entry.major_or_class ? `• ${entry.major_or_class}` : '' }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">{{ entry.status_label }}</span>
                    </div>
                    <p v-if="entry.institution_or_company" class="text-xs text-slate-700">
                        {{ entry.position_or_program ? `${entry.position_or_program} di ` : '' }}<strong>{{ entry.institution_or_company }}</strong>
                    </p>
                    <p v-if="entry.scholarship_info" class="text-xs text-emerald-700">Beasiswa/Prestasi: {{ entry.scholarship_info }}</p>
                    <p v-if="entry.testimonial" class="text-xs italic text-slate-600">"{{ entry.testimonial }}"</p>
                    <p v-if="entry.contact_email" class="text-xs text-slate-500">Kontak: {{ entry.contact_email }}</p>
                </article>
            </div>
        </main>
    </div>
</template>
