<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import { publicThemeStyle } from '@/composables/publicTheme';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
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
    sourceStatus: { mode: string; slims_available: boolean; message: string };
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
    router.get('/perpustakaan', {
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
</script>

<template>
    <Head :title="`Perpustakaan — ${school.name}`" />

    <div class="min-h-screen bg-[#f8f6f0]" :style="themeStyle">
        <PublicHeader :school="school" />

        <main class="mx-auto max-w-5xl px-4 py-10 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Portal Perpustakaan &amp; Layanan Pustaka</h1>
                    <p class="mt-1 text-xs text-slate-600">{{ sourceStatus.message }}</p>
                </div>
                <div class="flex gap-3 text-xs font-semibold text-slate-700">
                    <span class="rounded-lg bg-white px-3 py-2 border border-slate-200">Koleksi: {{ stats.collections_count }}</span>
                    <span class="rounded-lg bg-white px-3 py-2 border border-slate-200">Kunjungan Hari Ini: {{ stats.visits_today }}</span>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                <h2 class="text-base font-bold text-slate-900">Katalog Buku &amp; E-Book</h2>
                <div class="grid gap-3 md:grid-cols-4">
                    <input v-model="search" type="text" placeholder="Cari judul, pengarang, kode..." class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    <select v-model="category" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Semua Kategori</option>
                        <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                    </select>
                    <select v-model="format" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Semua Format</option>
                        <option value="physical">Cetak / Fisik</option>
                        <option value="ebook">Digital (E-Book)</option>
                        <option value="hybrid">Cetak &amp; E-Book</option>
                    </select>
                    <AppButton type="button" @click="applyFilter">Cari Koleksi</AppButton>
                </div>

                <StateFeedback v-if="collections.length === 0" state="empty" title="Koleksi tidak ditemukan" description="Ubah kata kunci pencarian atau hubungi pustakawan." />

                <div v-else class="grid gap-3 md:grid-cols-2">
                    <article v-for="item in collections" :key="item.id" class="rounded-xl border border-slate-200 p-4 text-sm space-y-1">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-bold text-slate-900">{{ item.title }}</p>
                                <p class="text-xs text-slate-600">{{ item.author || '-' }} • {{ item.category_label }}</p>
                            </div>
                            <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold">{{ item.available_copies }}/{{ item.total_copies }}</span>
                        </div>
                        <p class="text-xs text-slate-500">Kode: {{ item.code }} • Rak: {{ item.shelf_location || '-' }}</p>
                        <div v-if="item.can_access_material && (item.has_ebook_file || item.ebook_url)" class="pt-1 flex gap-2">
                            <a v-if="item.has_ebook_file" :href="`/perpustakaan/koleksi/${item.id}/unduh`" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-emerald-700 underline">
                                Unduh E-Book
                            </a>
                            <a v-if="item.ebook_url" :href="item.ebook_url" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-indigo-700 underline">
                                Buka Tautan Baca
                            </a>
                        </div>
                        <p v-else-if="item.format !== 'physical' && !item.can_access_material" class="text-xs text-amber-700">
                            Masuk sebagai anggota sekolah untuk mengakses materi digital ini.
                        </p>
                    </article>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <form @submit.prevent="submitVisit" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-3">
                    <h2 class="text-base font-bold text-slate-900">Presensi Kunjungan Perpustakaan</h2>
                    <div v-if="visitReceipt" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-900">
                        Kunjungan <strong>{{ visitReceipt.visitor_name }}</strong> tercatat ({{ visitReceipt.method }}).
                    </div>
                    <FormField id="lib-visit-qr" label="Scan QR / Token Kartu Siswa (Opsional)">
                        <input id="lib-visit-qr" v-model="visitForm.card_token" type="text" placeholder="Tempel kode QR kartu" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="lib-visit-name" label="Atau Input Nama Manual">
                        <input id="lib-visit-name" v-model="visitForm.visitor_name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <FormField id="lib-visit-purpose" label="Keperluan Kunjungan">
                        <input id="lib-visit-purpose" v-model="visitForm.purpose" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </FormField>
                    <AppButton type="submit" :disabled="visitForm.processing">Catat Kunjungan</AppButton>
                </form>

                <div v-if="isLoggedIn" class="space-y-6">
                    <form v-if="students.length" @submit.prevent="submitClearance" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-3">
                        <h2 class="text-base font-bold text-slate-900">Bebas Pustaka</h2>
                        <FormField id="lib-clr-student" label="Pilih Siswa" required>
                            <select id="lib-clr-student" v-model="clearanceForm.student_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option v-for="s in students" :key="s.student_id" :value="s.student_id">{{ s.name }} ({{ s.nis }})</option>
                            </select>
                        </FormField>
                        <AppButton type="submit" :disabled="clearanceForm.processing">Cek &amp; Terbitkan Bebas Pustaka</AppButton>
                        <div v-if="clearanceResult" class="rounded-lg border p-3 text-xs" :class="clearanceResult.status === 'cleared' ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-rose-200 bg-rose-50 text-rose-900'">
                            <p class="font-bold">{{ clearanceResult.clearance_code }} — {{ clearanceResult.notes }}</p>
                        </div>
                        <div v-for="c in myClearances" :key="c.id" class="text-xs flex justify-between items-center border-t pt-2">
                            <span>{{ c.clearance_code }} ({{ c.status }})</span>
                            <a v-if="c.status === 'cleared'" :href="`/perpustakaan/bebas-pustaka/${c.id}/pdf`" target="_blank" rel="noopener noreferrer" class="text-emerald-700 font-semibold underline">Unduh PDF</a>
                        </div>
                    </form>

                    <form @submit.prevent="submitProcurement" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-3">
                        <h2 class="text-base font-bold text-slate-900">Usul Pengadaan Koleksi Baru</h2>
                        <FormField id="lib-proc-title" label="Judul Buku / Koleksi" :error="procurementForm.errors.title" required>
                            <input id="lib-proc-title" v-model="procurementForm.title" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </FormField>
                        <FormField id="lib-proc-reason" label="Alasan / Kebutuhan" :error="procurementForm.errors.reason" required>
                            <textarea id="lib-proc-reason" v-model="procurementForm.reason" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </FormField>
                        <AppButton type="submit" :disabled="procurementForm.processing">Kirim Usulan Buku</AppButton>
                    </form>
                </div>
            </div>
        </main>
    </div>
</template>
