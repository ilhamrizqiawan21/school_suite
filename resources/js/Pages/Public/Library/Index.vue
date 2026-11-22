<script setup lang="ts">
import { usePublicContentText } from '@/composables/usePublicContentText';
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { router, useForm, usePage } from '@inertiajs/vue3';
import Head from '@/components/PublicHead.vue';
import { computed, ref } from 'vue';

interface CollectionItem {
    id: number;
    code: string;
    title: string;
    author?: string | null;
    category_label: string;
    format: string;
    total_copies: number;
    available_copies: number;
    shelf_location?: string | null;
    access_level: string;
    can_access_material: boolean;
    has_ebook_file: boolean;
    ebook_url?: string | null;
}

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    collections: CollectionItem[];
    sourceStatus: {
        mode: string;
        slims_available: boolean;
        source_of_truth: string;
        provider_configured: boolean;
        fallback_active: boolean;
        missing_local_files?: number;
        message: string;
    };
    categories: { value: string; label: string }[];
    filters: { search: string; category: string; format: string };
    stats: { collections_count: number; visits_today: number; active_loans: number };
    students: { student_id: number; name: string; nis: string }[];
    myClearances: { id: number; clearance_code: string; status: string; active_loan_count: number; notes?: string | null }[];
    visitReceipt?: { visitor_name: string; method: string } | null;
    clearanceResult?: { status: string; clearance_code: string; notes?: string | null; active_loan_count: number } | null;
}>();

const page = usePage();
const isLoggedIn = computed(() => Boolean((page.props.auth as { user?: unknown })?.user));
const themeStyle = computed(() => publicThemeStyle(props.school.theme));

const search = ref(props.filters.search || '');
const category = ref(props.filters.category || '');
const format = ref(props.filters.format || '');

const visitForm = useForm({
    card_token: '',
    visitor_name: '',
    visitor_identifier: '',
    visitor_role: 'student',
    purpose: 'Membaca & Belajar',
});

const procurementForm = useForm({
    title: '',
    author: '',
    isbn: '',
    reason: '',
    estimated_copies: 2,
});

const clearanceForm = useForm({
    student_id: props.students[0]?.student_id ?? ('' as string | number),
});

const applyFilter = () => {
    router.get('/perpustakaan/portal', {
        search: search.value || undefined,
        category: category.value || undefined,
        format: format.value || undefined,
    }, { preserveState: true, replace: true });
};

const submitVisit = () => {
    visitForm.post('/perpustakaan/kunjungan', {
        preserveScroll: true,
        onSuccess: () => { visitForm.card_token = ''; visitForm.visitor_name = ''; },
    });
};

const submitProcurement = () => {
    procurementForm.post('/perpustakaan/usulan', {
        preserveScroll: true,
        onSuccess: () => procurementForm.reset(),
    });
};

const submitClearance = () => {
    clearanceForm.post('/perpustakaan/bebas-pustaka', { preserveScroll: true });
};
const publicText = usePublicContentText();
</script>

<template>
    <Head :title="`Perpustakaan — ${school.name}`" />

    <div class="min-h-screen bg-[#f8f6f0]" data-madrasah-service="library" :style="themeStyle">
        <PublicHeader :school="school" />

        <main class="mx-auto max-w-6xl px-4 py-10 space-y-6">
            <div class="madrasah-service-hero grid gap-5 lg:grid-cols-12 items-center">
                <div class="parity-hero-copy lg:col-span-7 space-y-3">
                    <p class="madrasah-service-label"><i class="fa-solid fa-book-open-reader" aria-hidden="true"></i> {{ publicText("copy.vue_library_index", "text_001", "Pusat Sumber Belajar & Khazanah Literasi Digital Madrasah") }}</p>
                    <h1 class="text-2xl font-bold text-slate-900">
                        {{ publicText("copy.vue_library_index", "text_002", "Membuka Cakrawala Ilmu,") }}<br />
                        <span class="text-amber-300">{{ publicText("copy.vue_library_index", "text_003", "Menumbuhkan Karakter Qur’ani") }}</span> {{ publicText("copy.vue_library_index", "text_004", "Melalui Literasi") }}
                    </h1>
                    <p class="mt-1 text-xs text-slate-600">{{ sourceStatus.message }}</p>
                <form class="parity-hero-search" @submit.prevent="applyFilter">
                    <input v-model="search" type="search" :aria-label="publicText('copy.vue_library_index', 'aria_label_043', 'Cari koleksi')" @keydown.enter="applyFilter" :placeholder="publicText('copy.vue_library_index', 'placeholder_044', 'Ketik judul, penulis, penerbit, atau kode...')" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    <select v-model="category" :aria-label="publicText('copy.vue_library_index', 'aria_label_045', 'Kategori koleksi')" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">{{ publicText("copy.vue_library_index", "text_005", "Semua Kategori") }}</option>
                        <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                    </select>
                    <select v-model="format" :aria-label="publicText('copy.vue_library_index', 'aria_label_046', 'Format koleksi')" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">{{ publicText("copy.vue_library_index", "text_006", "Semua Format") }}</option>
                        <option value="physical">{{ publicText("copy.vue_library_index", "text_007", "Cetak / Fisik") }}</option>
                        <option value="ebook">{{ publicText("copy.vue_library_index", "text_008", "Digital (E-Book)") }}</option>
                        <option value="hybrid">{{ publicText("copy.vue_library_index", "text_009", "Cetak & E-Book") }}</option>
                    </select>
                    <AppButton type="submit">{{ publicText("copy.vue_library_index", "text_010", "Cari") }}</AppButton>
                </form>
                    <div class="flex flex-wrap items-center gap-2 pt-1 text-[11px]">
                        <span class="text-emerald-100/80 font-semibold">{{ publicText("copy.vue_library_index", "text_011", "Topik Cepat:") }}</span>
                        <button type="button" class="rounded-full bg-white/10 border border-white/20 px-3 py-1 text-white hover:bg-white/20" @click="search = 'Kurikulum'; applyFilter()">{{ publicText("copy.vue_library_index", "text_012", "#KurikulumMerdeka") }}</button>
                        <button type="button" class="rounded-full bg-white/10 border border-white/20 px-3 py-1 text-white hover:bg-white/20" @click="format = 'ebook'; applyFilter()">{{ publicText("copy.vue_library_index", "text_013", "#E-BookDigital") }}</button>
                        <button type="button" class="rounded-full bg-white/10 border border-white/20 px-3 py-1 text-white hover:bg-white/20" @click="search = 'Sains'; applyFilter()">{{ publicText("copy.vue_library_index", "text_014", "#SainsTeknologi") }}</button>
                    </div>
                </div>
                <div class="parity-hero-visual lg:col-span-5 relative">
                    <div class="relative">
                        <img :src="publicText('copy.vue_library_index', 'src_047', '/madrasah-theme/images/library.webp')" :alt="publicText('copy.vue_library_index', 'alt_048', 'Ilustrasi ruang perpustakaan')" />
                        <div class="absolute top-3 left-3 rounded-xl bg-kemenag-950/90 border border-emerald-400/30 px-3 py-1.5 text-xs">
                            <span class="block text-[9px] font-bold uppercase text-amber-300">{{ publicText("copy.vue_library_index", "text_015", "Akreditasi Pustaka") }}</span>
                            <strong class="text-white">{{ publicText("copy.vue_library_index", "text_016", "OPAC 4.0 Terakreditasi") }}</strong>
                        </div>
                    </div>
                    <div class="parity-hero-metrics">
                        <span><strong>{{ stats.collections_count }}</strong> {{ publicText("copy.vue_library_index", "text_017", "Koleksi") }}</span>
                        <span><strong>{{ stats.visits_today }}</strong> {{ publicText("copy.vue_library_index", "text_018", "Kunjungan Hari Ini") }}</span>
                        <span><strong>{{ stats.active_loans }}</strong> {{ publicText("copy.vue_library_index", "text_019", "Pinjaman Aktif") }}</span>
                    </div>
                </div>
            </div>

            <nav aria-labelledby="library-services-title">
                <h2 id="library-services-title" class="service-form-title mb-3">{{ publicText("copy.vue_library_index", "text_020", "Pusat Layanan Digital Mandiri") }}</h2>
                <div class="service-tiles">
                    <a :href="publicText('copy.vue_library_index', 'href_049', '#katalog-pustaka')"><strong>{{ publicText("copy.vue_library_index", "text_021", "Katalog Buku & E-Book") }}</strong><span>{{ publicText("copy.vue_library_index", "text_022", "Cari koleksi cetak dan e-book terpadu") }}</span></a>
                    <a :href="publicText('copy.vue_library_index', 'href_050', '#kunjungan-pustaka')"><strong>{{ publicText("copy.vue_library_index", "text_023", "Presensi Buku Tamu") }}</strong><span>{{ publicText("copy.vue_library_index", "text_024", "Catat kunjungan perpustakaan via QR/manual") }}</span></a>
                    <a :href="publicText('copy.vue_library_index', 'href_051', '#usulan-pustaka')"><strong>{{ publicText("copy.vue_library_index", "text_025", "Usul Pengadaan Buku") }}</strong><span>{{ publicText("copy.vue_library_index", "text_026", "Ajukan judul koleksi baru untuk madrasah") }}</span></a>
                    <a :href="publicText('copy.vue_library_index', 'href_052', '#bebas-pustaka')"><strong>{{ publicText("copy.vue_library_index", "text_027", "Cek Bebas Pustaka") }}</strong><span>{{ publicText("copy.vue_library_index", "text_028", "Verifikasi tanggungan pinjaman siswa") }}</span></a>
                </div>
            </nav>

            <section v-if="categories.length" aria-labelledby="library-categories-title" class="parity-library-categories space-y-3">
                <h2 id="library-categories-title" class="service-form-title">{{ publicText("copy.vue_library_index", "text_029", "Jelajahi Kategori Koleksi") }}</h2>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rounded-xl px-3.5 py-2 text-xs font-bold border transition"
                        :class="!category ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                        @click="category = ''; applyFilter()"
                    >
                        {{ publicText("copy.vue_library_index", "text_005", "Semua Kategori") }}
                    </button>
                    <button
                        v-for="c in categories"
                        :key="c.value"
                        type="button"
                        class="rounded-xl px-3.5 py-2 text-xs font-bold border transition"
                        :class="category === c.value ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                        @click="category = c.value; applyFilter()"
                    >
                        {{ c.label }}
                    </button>
                </div>
            </section>

            <div id="katalog-pustaka" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                <h2 class="text-base font-bold text-slate-900">{{ publicText("copy.vue_library_index", "text_021", "Katalog Buku & E-Book") }}</h2>


                <StateFeedback v-if="collections.length === 0" state="empty" :title="publicText('copy.vue_library_index', 'title_053', 'Koleksi tidak ditemukan')" :description="publicText('copy.vue_library_index', 'description_054', 'Ubah kata kunci pencarian atau hubungi pustakawan.')" />

                <div v-else class="grid gap-3 md:grid-cols-2">
                    <article v-for="item in collections" :key="item.id" class="rounded-xl border border-slate-200 p-4 text-sm space-y-1">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-bold text-slate-900">{{ item.title }}</p>
                                <p class="text-xs text-slate-600">{{ item.author || '-' }} • {{ item.category_label }}</p>
                            </div>
                            <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold">{{ item.available_copies }}/{{ item.total_copies }}</span>
                        </div>
                        <p class="text-xs text-slate-500">{{ publicText("copy.vue_library_index", "text_9001", "Kode:") }} {{ item.code }} {{ publicText("copy.vue_library_index", "text_9002", "• Rak:") }} {{ item.shelf_location || '-' }}</p>
                        <div v-if="item.can_access_material && (item.has_ebook_file || item.ebook_url)" class="pt-1 flex gap-2">
                            <a v-if="item.has_ebook_file" :href="`/perpustakaan/koleksi/${item.id}/unduh`" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-emerald-700 underline">
                                {{ publicText("copy.vue_library_index", "text_030", "Unduh E-Book") }}
                            </a>
                            <a v-if="item.ebook_url" :href="item.ebook_url" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-indigo-700 underline">
                                {{ publicText("copy.vue_library_index", "text_031", "Buka Tautan Baca") }}
                            </a>
                        </div>
                        <p v-else-if="item.format !== 'physical' && !item.can_access_material" class="text-xs text-amber-700">
                            {{ publicText("copy.vue_library_index", "text_032", "Masuk sebagai anggota sekolah untuk mengakses materi digital ini.") }}
                        </p>
                    </article>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <form id="kunjungan-pustaka" @submit.prevent="submitVisit" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-3">
                    <h2 class="text-base font-bold text-slate-900">{{ publicText("copy.vue_library_index", "text_033", "Presensi Kunjungan Perpustakaan") }}</h2>
                    <div v-if="visitReceipt" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-900">
                        {{ publicText("copy.vue_library_index", "text_034", "Kunjungan") }} <strong>{{ visitReceipt.visitor_name }}</strong> {{ publicText("copy.vue_library_index", "text_9003", "tercatat (") }}{{ visitReceipt.method }}).
                    </div>
                    <FormField id="lib-visit-qr" :label="publicText('copy.vue_library_index', 'label_10001', 'Scan QR / Token Kartu Siswa (Opsional)')">
                        <input id="lib-visit-qr" v-model="visitForm.card_token" type="text" :placeholder="publicText('copy.vue_library_index', 'placeholder_055', 'Tempel kode QR kartu')" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="lib-visit-name" :label="publicText('copy.vue_library_index', 'label_10002', 'Atau Input Nama Manual')">
                        <input id="lib-visit-name" v-model="visitForm.visitor_name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="lib-visit-purpose" :label="publicText('copy.vue_library_index', 'label_10003', 'Keperluan Kunjungan')">
                        <input id="lib-visit-purpose" v-model="visitForm.purpose" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <AppButton type="submit" :disabled="visitForm.processing">{{ publicText("copy.vue_library_index", "text_035", "Catat Kunjungan") }}</AppButton>
                </form>

                <div v-if="isLoggedIn" class="space-y-6">
                    <form id="bebas-pustaka" v-if="students.length" @submit.prevent="submitClearance" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-3">
                        <h2 class="text-base font-bold text-slate-900">{{ publicText("copy.vue_library_index", "text_036", "Bebas Pustaka") }}</h2>
                        <FormField id="lib-clr-student" :label="publicText('copy.vue_library_index', 'label_10004', 'Pilih Siswa')" required>
                            <select id="lib-clr-student" v-model="clearanceForm.student_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option v-for="s in students" :key="s.student_id" :value="s.student_id">{{ s.name }} ({{ s.nis }})</option>
                            </select>
                        </FormField>
                        <AppButton type="submit" :disabled="clearanceForm.processing">{{ publicText("copy.vue_library_index", "text_037", "Cek & Terbitkan Bebas Pustaka") }}</AppButton>
                        <div v-if="clearanceResult" class="rounded-lg border p-3 text-xs" :class="clearanceResult.status === 'cleared' ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-rose-200 bg-rose-50 text-rose-900'">
                            <p class="font-bold">{{ clearanceResult.clearance_code }} — {{ clearanceResult.notes }}</p>
                        </div>
                        <div v-for="c in myClearances" :key="c.id" class="text-xs flex justify-between items-center border-t pt-2">
                            <span>{{ c.clearance_code }} ({{ c.status }})</span>
                            <a v-if="c.status === 'cleared'" :href="`/perpustakaan/bebas-pustaka/${c.id}/pdf`" target="_blank" rel="noopener noreferrer" class="text-emerald-700 font-semibold underline">{{ publicText("copy.vue_library_index", "text_038", "Unduh PDF") }}</a>
                        </div>
                    </form>

                    <form id="usulan-pustaka" @submit.prevent="submitProcurement" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-3">
                        <h2 class="text-base font-bold text-slate-900">{{ publicText("copy.vue_library_index", "text_039", "Usul Pengadaan Koleksi Baru") }}</h2>
                        <FormField id="lib-proc-title" :label="publicText('copy.vue_library_index', 'label_10005', 'Judul Buku / Koleksi')" :error="procurementForm.errors.title" required>
                            <input id="lib-proc-title" v-model="procurementForm.title" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </FormField>
                        <FormField id="lib-proc-reason" :label="publicText('copy.vue_library_index', 'label_10006', 'Alasan / Kebutuhan')" :error="procurementForm.errors.reason" required>
                            <textarea id="lib-proc-reason" v-model="procurementForm.reason" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </FormField>
                        <AppButton type="submit" :disabled="procurementForm.processing">{{ publicText("copy.vue_library_index", "text_040", "Kirim Usulan Buku") }}</AppButton>
                    </form>
                </div>
            </div>

            <section class="rounded-3xl bg-gradient-to-r from-kemenag-900 to-emerald-800 p-8 text-center text-white space-y-2">
                <h2 class="text-xl font-extrabold">{{ publicText("copy.vue_library_index", "text_041", "Membangun Generasi Emas Melalui Kegemaran Membaca") }}</h2>
                <h3 class="text-sm font-semibold text-amber-300">{{ publicText("copy.vue_library_index", "text_042", "“Iqra’ — Membaca untuk Berakhlak, Berkarya, dan Memimpin”") }}</h3>
            </section>
        </main>
    </div>
</template>
