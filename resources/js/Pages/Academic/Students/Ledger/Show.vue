<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface StudentProfile {
    id: number;
    name: string;
    nis: string;
    nisn: string | null;
    gender: 'L' | 'P';
    birth_place: string | null;
    birth_date: string | null;
    religion: string | null;
    address: string | null;
    parent_name: string | null;
    parent_phone: string | null;
    status: string;
}

interface AdjacentStudent { id: number; name: string; nis: string }
interface ClassHistory { id: number; academic_year: string | null; term: string | null; class_name: string | null; grade_level: number | null; roll_number: number | null; status: string }
interface LifecycleHistory { id: number; action: string; effective_date: string | null; source_class: string | null; target_class: string | null; notes: string | null }
interface ReportHistoryItem {
    id: number;
    term_id?: number;
    academic_year: string;
    term_name: string;
    class_name: string;
    published_at: string | null;
    checksum: string;
    grades: Array<{ subject_name?: string; passing_grade?: number; final_score?: number; predicate?: string }>;
    attendance: { present?: number; sick?: number; permitted?: number; absent?: number };
}

interface LedgerDetail {
    photo_url: string | null;
    nickname: string | null;
    nik: string | null;
    kk_number?: string | null;
    citizenship?: string | null;
    child_order?: number | null;
    siblings_count?: number | null;
    family_status?: string | null;
    daily_language?: string | null;
    blood_type: string | null;
    height_cm?: number | null;
    weight_kg?: number | null;
    health_history?: string | null;
    address_rt: string | null;
    address_rw: string | null;
    village: string | null;
    district: string | null;
    city?: string | null;
    postal_code?: string | null;
    living_with?: string | null;
    distance_km?: number | null;
    sports_hobby: string | null;
    arts_hobby?: string | null;
    father_name: string | null;
    father_nik?: string | null;
    father_education?: string | null;
    father_occupation: string | null;
    father_income?: string | null;
    father_phone?: string | null;
    mother_name: string | null;
    mother_nik?: string | null;
    mother_education?: string | null;
    mother_occupation: string | null;
    mother_income?: string | null;
    mother_phone?: string | null;
    guardian_relation?: string | null;
    guardian_occupation?: string | null;
    guardian_address?: string | null;
    previous_school: string | null;
    diploma_number: string | null;
    diploma_date: string | null;
    study_duration_years?: string | null;
    accepted_date?: string | null;
    accepted_grade?: string | null;
    admission_type?: string | null;
    graduation_date?: string | null;
    graduation_diploma_number?: string | null;
    continuing_to?: string | null;
    leaving_reason?: string | null;
}

interface LedgerDocument { id: number; document_type: string; original_name: string; size_bytes: number; created_at: string | null }

const props = defineProps<{
    student: StudentProfile;
    completeness?: { status: string; percent: number; filled: number; total: number };
    adjacent_students?: { previous: AdjacentStudent | null; next: AdjacentStudent | null };
    class_history: ClassHistory[];
    lifecycle_history?: LifecycleHistory[];
    report_history?: ReportHistoryItem[];
    detail: LedgerDetail | null;
    documents: LedgerDocument[];
}>();

const statusLabels: Record<string, string> = { active: 'Aktif', graduated: 'Lulus', transferred: 'Pindah', dropped_out: 'Keluar' };
const enrollmentLabels: Record<string, string> = { active: 'Aktif', promoted: 'Naik kelas', retained: 'Tinggal kelas', graduated: 'Lulus', transferred: 'Pindah', withdrawn: 'Keluar' };
const editCoreIdentity = ref(false);
const selectedReportTermId = ref('');

const visibleReports = computed(() => {
    const all = props.report_history ?? [];
    if (!selectedReportTermId.value) return all;
    return all.filter((r) => String(r.term_id ?? r.id) === selectedReportTermId.value);
});

const ledgerPdfUrl = computed(() => {
    if (!selectedReportTermId.value) return `/akademik/buku-induk/${props.student.id}/pdf`;
    return `/akademik/buku-induk/${props.student.id}/pdf?term_id=${encodeURIComponent(selectedReportTermId.value)}`;
});

const detailsForm = useForm({
    update_core_identity: false,
    redirect_student_id: null as number | null,
    name: props.student.name || '',
    nisn: props.student.nisn || '',
    gender: props.student.gender || 'L',
    birth_place: props.student.birth_place || '',
    birth_date: props.student.birth_date || '',
    religion: props.student.religion || '',
    address: props.student.address || '',
    parent_name: props.student.parent_name || '',
    parent_phone: props.student.parent_phone || '',

    nickname: props.detail?.nickname || '',
    nik: props.detail?.nik || '',
    kk_number: props.detail?.kk_number || '',
    citizenship: props.detail?.citizenship || 'WNI',
    child_order: props.detail?.child_order ?? '',
    siblings_count: props.detail?.siblings_count ?? '',
    family_status: props.detail?.family_status || '',
    daily_language: props.detail?.daily_language || '',
    blood_type: props.detail?.blood_type || '',
    height_cm: props.detail?.height_cm ?? '',
    weight_kg: props.detail?.weight_kg ?? '',
    health_history: props.detail?.health_history || '',
    address_rt: props.detail?.address_rt || '',
    address_rw: props.detail?.address_rw || '',
    village: props.detail?.village || '',
    district: props.detail?.district || '',
    city: props.detail?.city || '',
    postal_code: props.detail?.postal_code || '',
    living_with: props.detail?.living_with || '',
    distance_km: props.detail?.distance_km ?? '',
    sports_hobby: props.detail?.sports_hobby || '',
    arts_hobby: props.detail?.arts_hobby || '',
    father_name: props.detail?.father_name || '',
    father_nik: props.detail?.father_nik || '',
    father_education: props.detail?.father_education || '',
    father_occupation: props.detail?.father_occupation || '',
    father_income: props.detail?.father_income || '',
    father_phone: props.detail?.father_phone || '',
    mother_name: props.detail?.mother_name || '',
    mother_nik: props.detail?.mother_nik || '',
    mother_education: props.detail?.mother_education || '',
    mother_occupation: props.detail?.mother_occupation || '',
    mother_income: props.detail?.mother_income || '',
    mother_phone: props.detail?.mother_phone || '',
    guardian_relation: props.detail?.guardian_relation || '',
    guardian_occupation: props.detail?.guardian_occupation || '',
    guardian_address: props.detail?.guardian_address || '',
    previous_school: props.detail?.previous_school || '',
    diploma_number: props.detail?.diploma_number || '',
    diploma_date: props.detail?.diploma_date || '',
    study_duration_years: props.detail?.study_duration_years || '',
    accepted_date: props.detail?.accepted_date || '',
    accepted_grade: props.detail?.accepted_grade || '',
    admission_type: props.detail?.admission_type || '',
    graduation_date: props.detail?.graduation_date || '',
    graduation_diploma_number: props.detail?.graduation_diploma_number || '',
    continuing_to: props.detail?.continuing_to || '',
    leaving_reason: props.detail?.leaving_reason || '',
});

const documentForm = useForm<{ document_type: string; file: File | null }>({ document_type: '', file: null });
const photoForm = useForm<{ photo: File | null }>({ photo: null });

function dateLabel(value: string | null | undefined) {
    if (!value) return 'Belum diisi';
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'long', timeZone: 'UTC' }).format(new Date(`${value}T00:00:00Z`));
}

function saveDetails(nextStudentId: number | null = null) {
    detailsForm.update_core_identity = editCoreIdentity.value;
    detailsForm.redirect_student_id = nextStudentId;
    detailsForm.put(`/akademik/buku-induk/${props.student.id}/detail`, { preserveScroll: !nextStudentId });
}

function uploadDocument() {
    documentForm.post(`/akademik/buku-induk/${props.student.id}/dokumen`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => documentForm.reset('file'),
    });
}

function uploadPhoto() {
    photoForm.post(`/akademik/buku-induk/${props.student.id}/foto`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => photoForm.reset('photo'),
    });
}

function deletePhoto() {
    if (window.confirm(`Hapus foto ${props.student.name}?`)) {
        router.delete(`/akademik/buku-induk/${props.student.id}/foto`, { preserveScroll: true });
    }
}

function deleteDocument(document: LedgerDocument) {
    if (window.confirm(`Hapus dokumen “${document.original_name}”?`)) {
        router.delete(`/akademik/buku-induk/${props.student.id}/dokumen/${document.id}`, { preserveScroll: true });
    }
}

function sizeLabel(bytes: number) {
    return bytes < 1024 * 1024 ? `${Math.max(1, Math.round(bytes / 1024))} KB` : `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
</script>

<template>
    <AppLayout title="Buku Induk Siswa">
        <Head :title="`Buku Induk · ${student.name}`" />
        <main class="view space-y-5">
            <section class="hero-card">
                <div>
                    <p class="eyebrow">Data kesiswaan · Buku induk peserta didik</p>
                    <h1>{{ student.name }}</h1>
                    <p>
                        NIS {{ student.nis }}
                        <span v-if="student.nisn"> · NISN {{ student.nisn }}</span>
                        <span v-if="completeness"> · Kelengkapan {{ completeness.percent }}%</span>
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        v-if="adjacent_students?.previous"
                        :href="`/akademik/buku-induk/${adjacent_students.previous.id}`"
                        class="secondary-button ui-button ui-button--secondary ui-button--md"
                        :title="`Sebelumnya: ${adjacent_students.previous.name}`"
                    >
                        ← Sebelumnya
                    </Link>
                    <Link
                        v-if="adjacent_students?.next"
                        :href="`/akademik/buku-induk/${adjacent_students.next.id}`"
                        class="secondary-button ui-button ui-button--secondary ui-button--md"
                        :title="`Berikutnya: ${adjacent_students.next.name}`"
                    >
                        Berikutnya →
                    </Link>
                    <Link href="/akademik/buku-induk" class="secondary-button ui-button ui-button--secondary ui-button--md">Kembali ke daftar</Link>
                    <AppButton variant="secondary" icon="pdf" as="a" class="file-action-button file-action-button--pdf inline-flex items-center" target="_blank" rel="noopener noreferrer" :href="ledgerPdfUrl">Pratinjau / Cetak PDF</AppButton>
                </div>
            </section>

            <section class="panel flex flex-wrap items-center gap-5 p-5">
                <img v-if="detail?.photo_url" :src="detail.photo_url" :alt="`Foto ${student.name}`" class="h-28 w-28 rounded-xl border border-slate-200 object-cover">
                <div v-else class="flex h-28 w-28 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-sm text-slate-600">Belum ada foto</div>
                <form class="grid min-w-0 flex-1 gap-3 md:grid-cols-[1fr_auto] md:items-end" @submit.prevent="uploadPhoto">
                    <label class="field-label">Foto siswa 3x4 (JPG/PNG, maks. 2 MB)
                        <input class="field-input file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5" type="file" accept=".jpg,.jpeg,.png" required @input="photoForm.photo = ($event.target as HTMLInputElement).files?.[0] || null">
                        <span v-if="photoForm.errors.photo" class="field-error">{{ photoForm.errors.photo }}</span>
                    </label>
                    <div class="flex gap-2">
                        <AppButton variant="primary" class="primary-button" type="submit" :disabled="photoForm.processing || !photoForm.photo">{{ photoForm.processing ? 'Menyimpan…' : detail?.photo_url ? 'Ganti foto' : 'Simpan foto' }}</AppButton>
                        <AppButton variant="danger" size="sm" icon="delete" v-if="detail?.photo_url" class="danger-button" type="button" @click="deletePhoto">Hapus</AppButton>
                    </div>
                    <p class="text-meta text-slate-600 md:col-span-2">Foto disimpan privat, ditampilkan untuk petugas berwenang, dan otomatis disertakan pada cetakan PDF Buku Induk.</p>
                </form>
            </section>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(300px,.75fr)]">
                <section class="panel overflow-hidden">
                    <header class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-base font-bold text-slate-900">Identitas peserta didik</h2>
                        <p class="mt-1 text-sm text-slate-600">Ringkasan identitas utama dan buku induk siswa.</p>
                    </header>
                    <dl class="grid gap-x-8 sm:grid-cols-2">
                        <div v-for="item in [
                            { label: 'Nama lengkap', value: student.name },
                            { label: 'Nama panggilan', value: detail?.nickname || 'Belum diisi' },
                            { label: 'NIS', value: student.nis },
                            { label: 'NISN', value: student.nisn || 'Belum diisi' },
                            { label: 'NIK', value: detail?.nik || 'Belum diisi' },
                            { label: 'Nomor KK', value: detail?.kk_number || 'Belum diisi' },
                            { label: 'Jenis kelamin', value: student.gender === 'L' ? 'Laki-laki' : 'Perempuan' },
                            { label: 'Tempat lahir', value: student.birth_place || 'Belum diisi' },
                            { label: 'Tanggal lahir', value: dateLabel(student.birth_date) },
                            { label: 'Agama', value: student.religion || 'Belum diisi' },
                            { label: 'Golongan darah', value: detail?.blood_type || 'Belum diisi' },
                            { label: 'Status siswa', value: statusLabels[student.status] || student.status },
                        ]" :key="item.label" class="border-b border-slate-100 px-5 py-3.5">
                            <dt class="text-meta font-medium text-slate-600">{{ item.label }}</dt>
                            <dd class="mt-1 break-words text-sm font-semibold text-slate-800">{{ item.value }}</dd>
                        </div>
                        <div class="border-b border-slate-100 px-5 py-3.5 sm:col-span-2">
                            <dt class="text-meta font-medium text-slate-600">Alamat</dt>
                            <dd class="mt-1 whitespace-pre-wrap text-sm font-semibold text-slate-800">{{ student.address || 'Belum diisi' }}</dd>
                        </div>
                        <div v-for="item in [
                            { label: 'RT / RW', value: [detail?.address_rt, detail?.address_rw].filter(Boolean).join(' / ') || 'Belum diisi' },
                            { label: 'Desa / kelurahan', value: detail?.village || 'Belum diisi' },
                            { label: 'Kecamatan', value: detail?.district || 'Belum diisi' },
                            { label: 'Kabupaten / kota', value: detail?.city || 'Belum diisi' },
                        ]" :key="item.label" class="border-b border-slate-100 px-5 py-3.5">
                            <dt class="text-meta font-medium text-slate-600">{{ item.label }}</dt>
                            <dd class="mt-1 break-words text-sm font-semibold text-slate-800">{{ item.value }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="panel overflow-hidden">
                    <header class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-base font-bold text-slate-900">Orang tua / wali &amp; asal sekolah</h2>
                        <p class="mt-1 text-sm text-slate-600">Informasi keluarga dan riwayat penerimaan siswa.</p>
                    </header>
                    <dl class="divide-y divide-slate-100">
                        <div class="px-5 py-3.5">
                            <dt class="text-meta font-medium text-slate-600">Nama ayah / pekerjaan</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ detail?.father_name || 'Belum diisi' }} · {{ detail?.father_occupation || '—' }}</dd>
                        </div>
                        <div class="px-5 py-3.5">
                            <dt class="text-meta font-medium text-slate-600">Nama ibu / pekerjaan</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ detail?.mother_name || 'Belum diisi' }} · {{ detail?.mother_occupation || '—' }}</dd>
                        </div>
                        <div class="px-5 py-3.5">
                            <dt class="text-meta font-medium text-slate-600">Nama orang tua / wali</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ student.parent_name || 'Belum diisi' }}</dd>
                        </div>
                        <div class="px-5 py-3.5">
                            <dt class="text-meta font-medium text-slate-600">Nomor kontak wali</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ student.parent_phone || 'Belum diisi' }}</dd>
                        </div>
                        <div class="px-5 py-3.5">
                            <dt class="text-meta font-medium text-slate-600">Sekolah asal &amp; nomor ijazah</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ detail?.previous_school || 'Belum diisi' }} ({{ detail?.diploma_number || '—' }})</dd>
                        </div>
                        <div class="px-5 py-3.5">
                            <dt class="text-meta font-medium text-slate-600">Diterima di kelas / tanggal</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ detail?.accepted_grade || '—' }} · {{ dateLabel(detail?.accepted_date) }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <section class="panel overflow-hidden">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Formulir Lengkap Buku Induk (Format Hardcopy A–E)</h2>
                        <p class="mt-1 text-sm text-slate-600">Lengkapi seluruh keterangan pribadi, alamat, kesehatan, orang tua/wali, penerimaan, dan kelulusan.</p>
                    </div>
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700">
                        <input v-model="editCoreIdentity" type="checkbox" class="rounded border-slate-300" />
                        Sertakan koreksi identitas dasar (Nama, NISN, TTL, Alamat, Wali)
                    </label>
                </header>

                <form class="space-y-6 p-5" @submit.prevent="saveDetails(null)">
                    <div v-if="editCoreIdentity" class="rounded-xl border border-blue-200 bg-blue-50/40 p-4">
                        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-blue-950">Koreksi Identitas Dasar Siswa</h3>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <label class="field-label">Nama lengkap<input v-model="detailsForm.name" class="field-input" maxlength="150" required><span v-if="detailsForm.errors.name" class="field-error">{{ detailsForm.errors.name }}</span></label>
                            <label class="field-label">NISN<input v-model="detailsForm.nisn" class="field-input" maxlength="30"><span v-if="detailsForm.errors.nisn" class="field-error">{{ detailsForm.errors.nisn }}</span></label>
                            <label class="field-label">Jenis kelamin<select v-model="detailsForm.gender" class="field-input"><option value="L">Laki-laki (L)</option><option value="P">Perempuan (P)</option></select></label>
                            <label class="field-label">Tempat lahir<input v-model="detailsForm.birth_place" class="field-input" maxlength="100"></label>
                            <label class="field-label">Tanggal lahir<input v-model="detailsForm.birth_date" type="date" class="field-input"></label>
                            <label class="field-label">Agama<input v-model="detailsForm.religion" class="field-input" maxlength="30"></label>
                            <label class="field-label sm:col-span-3">Alamat jalan<input v-model="detailsForm.address" class="field-input" maxlength="1000"></label>
                            <label class="field-label">Nama wali utama<input v-model="detailsForm.parent_name" class="field-input" maxlength="150"></label>
                            <label class="field-label">Nomor kontak wali<input v-model="detailsForm.parent_phone" class="field-input" maxlength="30"></label>
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 border-b border-slate-200 pb-1.5 text-sm font-bold uppercase tracking-wide text-slate-800">A. Keterangan Pribadi Peserta Didik</h3>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <label class="field-label">Nama panggilan<input v-model="detailsForm.nickname" class="field-input" maxlength="100"><span v-if="detailsForm.errors.nickname" class="field-error">{{ detailsForm.errors.nickname }}</span></label>
                            <label class="field-label">NIK (16 digit)<input v-model="detailsForm.nik" class="field-input" inputmode="numeric" maxlength="16"><span v-if="detailsForm.errors.nik" class="field-error">{{ detailsForm.errors.nik }}</span></label>
                            <label class="field-label">Nomor Kartu Keluarga (16 digit)<input v-model="detailsForm.kk_number" class="field-input" inputmode="numeric" maxlength="16"><span v-if="detailsForm.errors.kk_number" class="field-error">{{ detailsForm.errors.kk_number }}</span></label>
                            <label class="field-label">Kewarganegaraan<input v-model="detailsForm.citizenship" class="field-input" maxlength="30" placeholder="WNI"><span v-if="detailsForm.errors.citizenship" class="field-error">{{ detailsForm.errors.citizenship }}</span></label>
                            <label class="field-label">Anak ke-berapa<input v-model="detailsForm.child_order" type="number" min="1" max="30" class="field-input"><span v-if="detailsForm.errors.child_order" class="field-error">{{ detailsForm.errors.child_order }}</span></label>
                            <label class="field-label">Jumlah saudara kandung<input v-model="detailsForm.siblings_count" type="number" min="0" max="30" class="field-input"><span v-if="detailsForm.errors.siblings_count" class="field-error">{{ detailsForm.errors.siblings_count }}</span></label>
                            <label class="field-label">Status dalam keluarga
                                <select v-model="detailsForm.family_status" class="field-input">
                                    <option value="">Pilih status</option>
                                    <option v-for="st in ['Anak Kandung', 'Anak Tiri', 'Anak Angkat']" :key="st" :value="st">{{ st }}</option>
                                </select>
                            </label>
                            <label class="field-label sm:col-span-2">Bahasa sehari-hari<input v-model="detailsForm.daily_language" class="field-input" maxlength="60" placeholder="Contoh: Bahasa Indonesia"></label>
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 border-b border-slate-200 pb-1.5 text-sm font-bold uppercase tracking-wide text-slate-800">B. Tempat Tinggal, Kesehatan &amp; Kegemaran</h3>
                        <div class="grid gap-4 sm:grid-cols-4">
                            <label class="field-label">RT<input v-model="detailsForm.address_rt" class="field-input" maxlength="5"><span v-if="detailsForm.errors.address_rt" class="field-error">{{ detailsForm.errors.address_rt }}</span></label>
                            <label class="field-label">RW<input v-model="detailsForm.address_rw" class="field-input" maxlength="5"><span v-if="detailsForm.errors.address_rw" class="field-error">{{ detailsForm.errors.address_rw }}</span></label>
                            <label class="field-label">Desa / kelurahan<input v-model="detailsForm.village" class="field-input" maxlength="100"><span v-if="detailsForm.errors.village" class="field-error">{{ detailsForm.errors.village }}</span></label>
                            <label class="field-label">Kecamatan<input v-model="detailsForm.district" class="field-input" maxlength="100"><span v-if="detailsForm.errors.district" class="field-error">{{ detailsForm.errors.district }}</span></label>
                            <label class="field-label">Kabupaten / kota<input v-model="detailsForm.city" class="field-input" maxlength="100"></label>
                            <label class="field-label">Kode pos<input v-model="detailsForm.postal_code" class="field-input" maxlength="10"></label>
                            <label class="field-label">Tinggal bersama
                                <select v-model="detailsForm.living_with" class="field-input">
                                    <option value="">Pilih</option>
                                    <option v-for="lw in ['Orang Tua', 'Wali', 'Asrama', 'Kost', 'Lainnya']" :key="lw" :value="lw">{{ lw }}</option>
                                </select>
                            </label>
                            <label class="field-label">Jarak ke sekolah (km)<input v-model="detailsForm.distance_km" type="number" step="0.1" min="0" class="field-input"></label>
                            <label class="field-label">Golongan darah
                                <select v-model="detailsForm.blood_type" class="field-input">
                                    <option value="">Belum diketahui</option>
                                    <option v-for="type in ['Tidak Tahu', 'A', 'B', 'AB', 'O']" :key="type" :value="type">{{ type }}</option>
                                </select>
                                <span v-if="detailsForm.errors.blood_type" class="field-error">{{ detailsForm.errors.blood_type }}</span>
                            </label>
                            <label class="field-label">Tinggi badan (cm)<input v-model="detailsForm.height_cm" type="number" min="30" max="250" class="field-input"></label>
                            <label class="field-label">Berat badan (kg)<input v-model="detailsForm.weight_kg" type="number" min="5" max="250" class="field-input"></label>
                            <label class="field-label">Riwayat kesehatan / khusus<input v-model="detailsForm.health_history" class="field-input" maxlength="255"></label>
                            <label class="field-label sm:col-span-2">Hobi olahraga<input v-model="detailsForm.sports_hobby" class="field-input" maxlength="100"><span v-if="detailsForm.errors.sports_hobby" class="field-error">{{ detailsForm.errors.sports_hobby }}</span></label>
                            <label class="field-label sm:col-span-2">Hobi kesenian / bakat<input v-model="detailsForm.arts_hobby" class="field-input" maxlength="100"></label>
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 border-b border-slate-200 pb-1.5 text-sm font-bold uppercase tracking-wide text-slate-800">C. Keterangan Orang Tua Kandung &amp; Wali</h3>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <label class="field-label">Nama ayah<input v-model="detailsForm.father_name" class="field-input" maxlength="100"><span v-if="detailsForm.errors.father_name" class="field-error">{{ detailsForm.errors.father_name }}</span></label>
                            <label class="field-label">NIK ayah (16 digit)<input v-model="detailsForm.father_nik" class="field-input" inputmode="numeric" maxlength="16"><span v-if="detailsForm.errors.father_nik" class="field-error">{{ detailsForm.errors.father_nik }}</span></label>
                            <label class="field-label">Pendidikan ayah<input v-model="detailsForm.father_education" class="field-input" maxlength="40"></label>
                            <label class="field-label">Pekerjaan ayah<input v-model="detailsForm.father_occupation" class="field-input" maxlength="60"><span v-if="detailsForm.errors.father_occupation" class="field-error">{{ detailsForm.errors.father_occupation }}</span></label>
                            <label class="field-label">Penghasilan ayah<input v-model="detailsForm.father_income" class="field-input" maxlength="60"></label>
                            <label class="field-label">Nomor HP ayah<input v-model="detailsForm.father_phone" class="field-input" maxlength="30"></label>

                            <label class="field-label">Nama ibu<input v-model="detailsForm.mother_name" class="field-input" maxlength="100"><span v-if="detailsForm.errors.mother_name" class="field-error">{{ detailsForm.errors.mother_name }}</span></label>
                            <label class="field-label">NIK ibu (16 digit)<input v-model="detailsForm.mother_nik" class="field-input" inputmode="numeric" maxlength="16"><span v-if="detailsForm.errors.mother_nik" class="field-error">{{ detailsForm.errors.mother_nik }}</span></label>
                            <label class="field-label">Pendidikan ibu<input v-model="detailsForm.mother_education" class="field-input" maxlength="40"></label>
                            <label class="field-label">Pekerjaan ibu<input v-model="detailsForm.mother_occupation" class="field-input" maxlength="60"><span v-if="detailsForm.errors.mother_occupation" class="field-error">{{ detailsForm.errors.mother_occupation }}</span></label>
                            <label class="field-label">Penghasilan ibu<input v-model="detailsForm.mother_income" class="field-input" maxlength="60"></label>
                            <label class="field-label">Nomor HP ibu<input v-model="detailsForm.mother_phone" class="field-input" maxlength="30"></label>

                            <label class="field-label">Hubungan wali dengan siswa<input v-model="detailsForm.guardian_relation" class="field-input" maxlength="60"></label>
                            <label class="field-label">Pekerjaan wali<input v-model="detailsForm.guardian_occupation" class="field-input" maxlength="60"></label>
                            <label class="field-label">Alamat wali<input v-model="detailsForm.guardian_address" class="field-input" maxlength="255"></label>
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 border-b border-slate-200 pb-1.5 text-sm font-bold uppercase tracking-wide text-slate-800">D. Pendidikan Sebelumnya &amp; Penerimaan</h3>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <label class="field-label">Sekolah asal<input v-model="detailsForm.previous_school" class="field-input" maxlength="100"><span v-if="detailsForm.errors.previous_school" class="field-error">{{ detailsForm.errors.previous_school }}</span></label>
                            <label class="field-label">Nomor ijazah asal<input v-model="detailsForm.diploma_number" class="field-input" maxlength="50"><span v-if="detailsForm.errors.diploma_number" class="field-error">{{ detailsForm.errors.diploma_number }}</span></label>
                            <label class="field-label">Tanggal ijazah asal<input v-model="detailsForm.diploma_date" type="date" class="field-input"><span v-if="detailsForm.errors.diploma_date" class="field-error">{{ detailsForm.errors.diploma_date }}</span></label>
                            <label class="field-label">Lama belajar asal<input v-model="detailsForm.study_duration_years" class="field-input" maxlength="30" placeholder="Contoh: 6 tahun"></label>
                            <label class="field-label">Tanggal diterima di sekolah ini<input v-model="detailsForm.accepted_date" type="date" class="field-input"></label>
                            <label class="field-label">Diterima di kelas / tingkat<input v-model="detailsForm.accepted_grade" class="field-input" maxlength="30" placeholder="Contoh: VII / X"></label>
                            <label class="field-label">Jenis pendaftaran
                                <select v-model="detailsForm.admission_type" class="field-input">
                                    <option value="">Pilih jenis</option>
                                    <option v-for="at in ['Siswa Baru', 'Pindahan']" :key="at" :value="at">{{ at }}</option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 border-b border-slate-200 pb-1.5 text-sm font-bold uppercase tracking-wide text-slate-800">E. Tamat Belajar / Meninggalkan Sekolah</h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="field-label">Tanggal tamat / keluar<input v-model="detailsForm.graduation_date" type="date" class="field-input"></label>
                            <label class="field-label">Nomor ijazah kelulusan sekolah ini<input v-model="detailsForm.graduation_diploma_number" class="field-input" maxlength="60"></label>
                            <label class="field-label">Melanjutkan ke sekolah / PT<input v-model="detailsForm.continuing_to" class="field-input" maxlength="120"></label>
                            <label class="field-label">Alasan pindah / meninggalkan sekolah<input v-model="detailsForm.leaving_reason" class="field-input" maxlength="255"></label>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 pt-4">
                        <AppButton
                            v-if="adjacent_students?.next"
                            variant="secondary"
                            type="button"
                            class="secondary-button"
                            :disabled="detailsForm.processing"
                            @click="saveDetails(adjacent_students.next.id)"
                        >
                            Simpan &amp; Lanjut ke {{ adjacent_students.next.name }} →
                        </AppButton>
                        <AppButton
                            variant="primary"
                            :loading="detailsForm.processing"
                            class="primary-button"
                            type="submit"
                            :disabled="detailsForm.processing"
                        >
                            {{ detailsForm.processing ? 'Menyimpan…' : 'Simpan data tambahan' }}
                        </AppButton>
                    </div>
                </form>
            </section>

            <section class="panel overflow-hidden">
                <header class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-bold text-slate-900">Lemari dokumen pribadi</h2>
                    <p class="mt-1 text-sm text-slate-600">Maksimal 10 dokumen PDF/JPG/PNG, masing-masing hingga 5 MB. Berkas hanya dapat diakses petugas sekolah.</p>
                </header>
                <form class="grid gap-3 border-b border-slate-100 p-5 md:grid-cols-[1fr_1.5fr_auto] md:items-end" @submit.prevent="uploadDocument">
                    <label class="field-label">Jenis dokumen<input v-model="documentForm.document_type" class="field-input" maxlength="100" placeholder="Contoh: Akta kelahiran, ijazah, KK" required><span v-if="documentForm.errors.document_type" class="field-error">{{ documentForm.errors.document_type }}</span></label>
                    <label class="field-label">Pilih berkas<input class="field-input file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5" type="file" accept=".pdf,.jpg,.jpeg,.png" required @input="documentForm.file = ($event.target as HTMLInputElement).files?.[0] || null"><span v-if="documentForm.errors.file" class="field-error">{{ documentForm.errors.file }}</span></label>
                    <AppButton variant="primary" class="primary-button" type="submit" :disabled="documentForm.processing || !documentForm.file">{{ documentForm.processing ? 'Mengunggah…' : 'Unggah dokumen' }}</AppButton>
                </form>
                <ul class="divide-y divide-slate-100">
                    <li v-for="document in documents" :key="document.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div class="min-w-0"><p class="font-semibold text-slate-800">{{ document.document_type }}</p><p class="mt-1 truncate text-sm text-slate-600">{{ document.original_name }} · {{ sizeLabel(document.size_bytes) }}</p></div>
                        <div class="flex shrink-0 gap-2"><AppButton variant="secondary" as="a" class="secondary-button" :href="`/akademik/buku-induk/${student.id}/dokumen/${document.id}`">Unduh</AppButton><AppButton variant="danger" size="sm" icon="delete" class="danger-button" type="button" @click="deleteDocument(document)">Hapus</AppButton></div>
                    </li>
                    <li v-if="documents.length === 0" class="px-5 py-10 text-center text-sm text-slate-600">Belum ada dokumen pribadi siswa.</li>
                </ul>
            </section>

            <section class="panel overflow-hidden">
                <header class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-bold text-slate-900">Riwayat kelas</h2>
                    <p class="mt-1 text-sm text-slate-600">Penempatan rombel tercatat per semester, termasuk riwayat terdahulu.</p>
                </header>
                <div class="overflow-x-auto table-scroll" tabindex="0" role="region" aria-label="Tabel data">
                    <table class="app-table min-w-[680px] w-full border-collapse text-left text-sm">
                        <thead class="bg-slate-50 text-meta uppercase tracking-wide text-slate-600">
                            <tr><th scope="col" class="px-5 py-3">Tahun ajaran</th><th scope="col" class="px-5 py-3">Semester</th><th scope="col" class="px-5 py-3">Kelas</th><th scope="col" class="px-5 py-3">No. absen</th><th scope="col" class="px-5 py-3">Status penempatan</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in class_history" :key="item.id">
                                <td class="px-5 py-3.5 text-slate-700">{{ item.academic_year || '—' }}</td>
                                <td class="px-5 py-3.5 text-slate-700">{{ item.term || '—' }}</td>
                                <td class="px-5 py-3.5 font-semibold text-slate-800">{{ item.class_name || '—' }}</td>
                                <td class="px-5 py-3.5 text-slate-700">{{ item.roll_number ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-slate-700">{{ enrollmentLabels[item.status] || item.status }}</td>
                            </tr>
                            <tr v-if="class_history.length === 0"><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-600">Belum ada riwayat penempatan kelas.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-if="report_history && report_history.length > 0" class="panel overflow-hidden">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Rekapitulasi nilai rapor per semester</h2>
                        <p class="mt-1 text-sm text-slate-600">Pilih tahun ajaran &amp; semester tertentu untuk melihat atau mencetak rapor spesifik tanpa mencetak seluruh semester sekaligus.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <select v-model="selectedReportTermId" aria-label="Pilih semester rapor" class="field-input !w-auto min-w-[220px]">
                            <option value="">Semua Semester ({{ report_history.length }})</option>
                            <option v-for="rep in report_history" :key="rep.id" :value="String(rep.term_id ?? rep.id)">
                                {{ rep.academic_year }} · {{ rep.term_name }} (Kelas {{ rep.class_name }})
                            </option>
                        </select>
                    </div>
                </header>
                <div class="divide-y divide-slate-200 p-5 space-y-4">
                    <div v-for="rep in visibleReports" :key="rep.id" class="pt-4 first:pt-0">
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <strong class="text-sm text-slate-900">{{ rep.academic_year }} · {{ rep.term_name }} · Kelas {{ rep.class_name }}</strong>
                                <span class="ml-2 text-xs text-slate-600">
                                    Hadir: {{ rep.attendance?.present ?? 0 }} · Sakit: {{ rep.attendance?.sick ?? 0 }} · Izin: {{ rep.attendance?.permitted ?? 0 }} · Alpa: {{ rep.attendance?.absent ?? 0 }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <a
                                    :href="`/penilaian/rapor/${rep.id}/pdf?report_mode=sas&cover=0&identity_only=1`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex h-7.5 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-2.5 text-xs font-semibold text-blue-700 transition hover:border-blue-500 hover:bg-blue-100"
                                    title="Cetak PDF Identitas Siswa"
                                >
                                    Identitas
                                </a>
                                <a
                                    :href="`/penilaian/rapor/${rep.id}/pdf?report_mode=sas&cover=0&grades_only=1`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex h-7.5 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-2.5 text-xs font-semibold text-amber-800 transition hover:border-amber-500 hover:bg-amber-100"
                                    title="Cetak PDF Nilai Siswa"
                                >
                                    Nilai
                                </a>
                                <a
                                    :href="`/penilaian/rapor/${rep.id}/pdf?report_mode=sts&cover=0`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex h-7.5 items-center justify-center rounded-lg border border-violet-200 bg-violet-50 px-2.5 text-xs font-semibold text-violet-700 transition hover:border-violet-500 hover:bg-violet-100"
                                    title="Cetak PDF Rapor Tengah Semester (STS)"
                                >
                                    Rapor STS
                                </a>
                                <a
                                    :href="`/penilaian/rapor/${rep.id}/pdf?report_mode=sas&cover=1`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex h-7.5 items-center justify-center rounded-lg border border-red-200 bg-red-50 px-2.5 text-xs font-semibold text-red-700 transition hover:border-red-500 hover:bg-red-100"
                                    title="Cetak PDF Rapor Akhir Semester (SAS)"
                                >
                                    Rapor SAS
                                </a>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="app-table w-full border-collapse text-left text-xs">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="px-3 py-2">Mata Pelajaran</th>
                                        <th class="px-3 py-2">KKTP</th>
                                        <th class="px-3 py-2">Nilai Akhir</th>
                                        <th class="px-3 py-2">Predikat</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="(grade, gIdx) in rep.grades" :key="gIdx">
                                        <td class="px-3 py-2 text-slate-800">{{ grade.subject_name || '—' }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ grade.passing_grade ?? '—' }}</td>
                                        <td class="px-3 py-2 font-bold text-slate-900">{{ grade.final_score ?? '—' }}</td>
                                        <td class="px-3 py-2 text-slate-700">{{ grade.predicate || '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.field-label { display: grid; gap: .4rem; color: #475569; font-size: var(--type-meta); font-weight: var(--weight-semibold); }
.field-input { min-height: 2.5rem; width: 100%; border: 1px solid #cbd5e1; border-radius: .65rem; background: white; padding: .5rem .75rem; color: #1e293b; font-size: var(--type-control); font-weight: var(--weight-regular); }
.field-error { color: #b91c1c; font-size: var(--type-meta); font-weight: var(--weight-medium); }
</style>
