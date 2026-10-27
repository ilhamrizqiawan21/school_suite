<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

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

interface ClassHistory { id: number; academic_year: string | null; term: string | null; class_name: string | null; grade_level: number | null; roll_number: number | null; status: string }
interface LedgerDetail { photo_url: string | null; nickname: string | null; nik: string | null; blood_type: string | null; address_rt: string | null; address_rw: string | null; village: string | null; district: string | null; sports_hobby: string | null; father_name: string | null; father_occupation: string | null; mother_name: string | null; mother_occupation: string | null; previous_school: string | null; diploma_number: string | null; diploma_date: string | null }
interface LedgerDocument { id: number; document_type: string; original_name: string; size_bytes: number; created_at: string | null }

const props = defineProps<{ student: StudentProfile; class_history: ClassHistory[]; detail: LedgerDetail | null; documents: LedgerDocument[] }>();
const statusLabels: Record<string, string> = { active: 'Aktif', graduated: 'Lulus', transferred: 'Pindah', dropped_out: 'Keluar' };
const enrollmentLabels: Record<string, string> = { active: 'Aktif', promoted: 'Naik kelas', retained: 'Tinggal kelas', graduated: 'Lulus', transferred: 'Pindah', withdrawn: 'Keluar' };
const detailsForm = useForm({
    nickname: props.detail?.nickname || '', nik: props.detail?.nik || '', blood_type: props.detail?.blood_type || '',
    address_rt: props.detail?.address_rt || '', address_rw: props.detail?.address_rw || '',
    village: props.detail?.village || '', district: props.detail?.district || '', sports_hobby: props.detail?.sports_hobby || '',
    father_name: props.detail?.father_name || '', father_occupation: props.detail?.father_occupation || '',
    mother_name: props.detail?.mother_name || '', mother_occupation: props.detail?.mother_occupation || '',
    previous_school: props.detail?.previous_school || '', diploma_number: props.detail?.diploma_number || '',
    diploma_date: props.detail?.diploma_date || '',
});
const documentForm = useForm<{ document_type: string; file: File | null }>({ document_type: '', file: null });
const photoForm = useForm<{ photo: File | null }>({ photo: null });

function dateLabel(value: string | null) {
    if (!value) return 'Belum diisi';
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'long', timeZone: 'UTC' }).format(new Date(`${value}T00:00:00Z`));
}

function saveDetails() {
    detailsForm.put(`/akademik/buku-induk/${props.student.id}/detail`, { preserveScroll: true });
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
                    <p class="eyebrow">Data kesiswaan · Buku induk</p>
                    <h1>{{ student.name }}</h1>
                    <p>NIS {{ student.nis }}<span v-if="student.nisn"> · NISN {{ student.nisn }}</span></p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link href="/akademik/buku-induk" class="secondary-button">Kembali ke daftar</Link>
                    <a class="primary-button inline-flex min-h-10 items-center" :href="`/akademik/buku-induk/${student.id}/pdf`">Cetak buku induk</a>
                </div>
            </section>

            <section class="panel flex flex-wrap items-center gap-5 p-5">
                <img v-if="detail?.photo_url" :src="detail.photo_url" :alt="`Foto ${student.name}`" class="h-28 w-28 rounded-xl border border-slate-200 object-cover">
                <div v-else class="flex h-28 w-28 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-sm text-slate-400">Belum ada foto</div>
                <form class="grid min-w-0 flex-1 gap-3 md:grid-cols-[1fr_auto] md:items-end" @submit.prevent="uploadPhoto">
                    <label class="field-label">Foto siswa (JPG/PNG, maks. 2 MB)
                        <input class="field-input file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5" type="file" accept=".jpg,.jpeg,.png" required @input="photoForm.photo = ($event.target as HTMLInputElement).files?.[0] || null">
                        <span v-if="photoForm.errors.photo" class="field-error">{{ photoForm.errors.photo }}</span>
                    </label>
                    <div class="flex gap-2">
                        <button class="primary-button" type="submit" :disabled="photoForm.processing || !photoForm.photo">{{ photoForm.processing ? 'Menyimpan…' : detail?.photo_url ? 'Ganti foto' : 'Simpan foto' }}</button>
                        <button v-if="detail?.photo_url" class="danger-button" type="button" @click="deletePhoto">Hapus</button>
                    </div>
                    <p class="text-xs text-slate-500 md:col-span-2">Foto disimpan privat dan hanya ditampilkan untuk petugas sekolah yang berwenang.</p>
                </form>
            </section>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(300px,.75fr)]">
                <section class="panel overflow-hidden">
                    <header class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-base font-bold text-slate-900">Identitas peserta didik</h2>
                        <p class="mt-1 text-sm text-slate-500">Data ini bersumber dari satu profil akademik School Suite.</p>
                    </header>
                    <dl class="grid gap-x-8 sm:grid-cols-2">
                        <div v-for="item in [
                            { label: 'Nama lengkap', value: student.name },
                            { label: 'Nama panggilan', value: detail?.nickname || 'Belum diisi' },
                            { label: 'NIS', value: student.nis },
                            { label: 'NISN', value: student.nisn || 'Belum diisi' },
                            { label: 'NIK', value: detail?.nik || 'Belum diisi' },
                            { label: 'Jenis kelamin', value: student.gender === 'L' ? 'Laki-laki' : 'Perempuan' },
                            { label: 'Tempat lahir', value: student.birth_place || 'Belum diisi' },
                            { label: 'Tanggal lahir', value: dateLabel(student.birth_date) },
                            { label: 'Agama', value: student.religion || 'Belum diisi' },
                            { label: 'Golongan darah', value: detail?.blood_type || 'Belum diisi' },
                            { label: 'Hobi olahraga', value: detail?.sports_hobby || 'Belum diisi' },
                            { label: 'Status siswa', value: statusLabels[student.status] || student.status },
                        ]" :key="item.label" class="border-b border-slate-100 px-5 py-3.5">
                            <dt class="text-xs font-medium text-slate-500">{{ item.label }}</dt>
                            <dd class="mt-1 break-words text-sm font-semibold text-slate-800">{{ item.value }}</dd>
                        </div>
                        <div class="border-b border-slate-100 px-5 py-3.5 sm:col-span-2">
                            <dt class="text-xs font-medium text-slate-500">Alamat</dt>
                            <dd class="mt-1 whitespace-pre-wrap text-sm font-semibold text-slate-800">{{ student.address || 'Belum diisi' }}</dd>
                        </div>
                        <div v-for="item in [
                            { label: 'RT / RW', value: [detail?.address_rt, detail?.address_rw].filter(Boolean).join(' / ') || 'Belum diisi' },
                            { label: 'Desa / kelurahan', value: detail?.village || 'Belum diisi' },
                            { label: 'Kecamatan', value: detail?.district || 'Belum diisi' },
                        ]" :key="item.label" class="border-b border-slate-100 px-5 py-3.5">
                            <dt class="text-xs font-medium text-slate-500">{{ item.label }}</dt>
                            <dd class="mt-1 break-words text-sm font-semibold text-slate-800">{{ item.value }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="panel overflow-hidden">
                    <header class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-base font-bold text-slate-900">Orang tua / wali</h2>
                        <p class="mt-1 text-sm text-slate-500">Informasi kontak yang tersedia pada profil siswa.</p>
                    </header>
                    <dl class="divide-y divide-slate-100">
                        <div class="px-5 py-3.5">
                            <dt class="text-xs font-medium text-slate-500">Nama orang tua / wali</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ student.parent_name || 'Belum diisi' }}</dd>
                        </div>
                        <div class="px-5 py-3.5">
                            <dt class="text-xs font-medium text-slate-500">Nomor kontak</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ student.parent_phone || 'Belum diisi' }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <section class="panel overflow-hidden">
                <header class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-bold text-slate-900">Data tambahan buku induk</h2>
                    <p class="mt-1 text-sm text-slate-500">Lengkapi identitas, rincian alamat, keluarga, hobi, dan pendidikan asal.</p>
                </header>
                <form class="grid gap-4 p-5 sm:grid-cols-2" @submit.prevent="saveDetails">
                    <label class="field-label">Nama panggilan<input v-model="detailsForm.nickname" class="field-input" maxlength="100"><span v-if="detailsForm.errors.nickname" class="field-error">{{ detailsForm.errors.nickname }}</span></label>
                    <label class="field-label">NIK<input v-model="detailsForm.nik" class="field-input" inputmode="numeric" maxlength="16"><span v-if="detailsForm.errors.nik" class="field-error">{{ detailsForm.errors.nik }}</span></label>
                    <label class="field-label">Golongan darah<select v-model="detailsForm.blood_type" class="field-input"><option value="">Belum diketahui</option><option v-for="type in ['Tidak Tahu', 'A', 'B', 'AB', 'O']" :key="type" :value="type">{{ type }}</option></select><span v-if="detailsForm.errors.blood_type" class="field-error">{{ detailsForm.errors.blood_type }}</span></label>
                    <label class="field-label">Hobi olahraga<input v-model="detailsForm.sports_hobby" class="field-input" maxlength="100"><span v-if="detailsForm.errors.sports_hobby" class="field-error">{{ detailsForm.errors.sports_hobby }}</span></label>
                    <label class="field-label">RT<input v-model="detailsForm.address_rt" class="field-input" maxlength="5"><span v-if="detailsForm.errors.address_rt" class="field-error">{{ detailsForm.errors.address_rt }}</span></label>
                    <label class="field-label">RW<input v-model="detailsForm.address_rw" class="field-input" maxlength="5"><span v-if="detailsForm.errors.address_rw" class="field-error">{{ detailsForm.errors.address_rw }}</span></label>
                    <label class="field-label">Desa / kelurahan<input v-model="detailsForm.village" class="field-input" maxlength="100"><span v-if="detailsForm.errors.village" class="field-error">{{ detailsForm.errors.village }}</span></label>
                    <label class="field-label">Kecamatan<input v-model="detailsForm.district" class="field-input" maxlength="100"><span v-if="detailsForm.errors.district" class="field-error">{{ detailsForm.errors.district }}</span></label>
                    <label class="field-label">Nama ayah<input v-model="detailsForm.father_name" class="field-input" maxlength="100"><span v-if="detailsForm.errors.father_name" class="field-error">{{ detailsForm.errors.father_name }}</span></label>
                    <label class="field-label">Pekerjaan ayah<input v-model="detailsForm.father_occupation" class="field-input" maxlength="60"><span v-if="detailsForm.errors.father_occupation" class="field-error">{{ detailsForm.errors.father_occupation }}</span></label>
                    <label class="field-label">Nama ibu<input v-model="detailsForm.mother_name" class="field-input" maxlength="100"><span v-if="detailsForm.errors.mother_name" class="field-error">{{ detailsForm.errors.mother_name }}</span></label>
                    <label class="field-label">Pekerjaan ibu<input v-model="detailsForm.mother_occupation" class="field-input" maxlength="60"><span v-if="detailsForm.errors.mother_occupation" class="field-error">{{ detailsForm.errors.mother_occupation }}</span></label>
                    <label class="field-label">Sekolah asal<input v-model="detailsForm.previous_school" class="field-input" maxlength="100"><span v-if="detailsForm.errors.previous_school" class="field-error">{{ detailsForm.errors.previous_school }}</span></label>
                    <label class="field-label">Nomor ijazah asal<input v-model="detailsForm.diploma_number" class="field-input" maxlength="50"><span v-if="detailsForm.errors.diploma_number" class="field-error">{{ detailsForm.errors.diploma_number }}</span></label>
                    <label class="field-label">Tanggal ijazah asal<input v-model="detailsForm.diploma_date" type="date" class="field-input"><span v-if="detailsForm.errors.diploma_date" class="field-error">{{ detailsForm.errors.diploma_date }}</span></label>
                    <div class="flex items-end justify-end sm:col-span-2"><button class="primary-button" type="submit" :disabled="detailsForm.processing">{{ detailsForm.processing ? 'Menyimpan…' : 'Simpan data tambahan' }}</button></div>
                </form>
            </section>

            <section class="panel overflow-hidden">
                <header class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-bold text-slate-900">Lemari dokumen pribadi</h2>
                    <p class="mt-1 text-sm text-slate-500">Maksimal 10 dokumen PDF/JPG/PNG, masing-masing hingga 5 MB. Berkas hanya dapat diakses petugas sekolah.</p>
                </header>
                <form class="grid gap-3 border-b border-slate-100 p-5 md:grid-cols-[1fr_1.5fr_auto] md:items-end" @submit.prevent="uploadDocument">
                    <label class="field-label">Jenis dokumen<input v-model="documentForm.document_type" class="field-input" maxlength="100" placeholder="Contoh: Akta kelahiran, ijazah, foto siswa" required><span v-if="documentForm.errors.document_type" class="field-error">{{ documentForm.errors.document_type }}</span></label>
                    <label class="field-label">Pilih berkas<input class="field-input file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5" type="file" accept=".pdf,.jpg,.jpeg,.png" required @input="documentForm.file = ($event.target as HTMLInputElement).files?.[0] || null"><span v-if="documentForm.errors.file" class="field-error">{{ documentForm.errors.file }}</span></label>
                    <button class="primary-button" type="submit" :disabled="documentForm.processing || !documentForm.file">{{ documentForm.processing ? 'Mengunggah…' : 'Unggah dokumen' }}</button>
                </form>
                <ul class="divide-y divide-slate-100">
                    <li v-for="document in documents" :key="document.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div class="min-w-0"><p class="font-semibold text-slate-800">{{ document.document_type }}</p><p class="mt-1 truncate text-sm text-slate-500">{{ document.original_name }} · {{ sizeLabel(document.size_bytes) }}</p></div>
                        <div class="flex shrink-0 gap-2"><a class="secondary-button" :href="`/akademik/buku-induk/${student.id}/dokumen/${document.id}`">Unduh</a><button class="danger-button" type="button" @click="deleteDocument(document)">Hapus</button></div>
                    </li>
                    <li v-if="documents.length === 0" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada dokumen pribadi siswa.</li>
                </ul>
            </section>

            <section class="panel overflow-hidden">
                <header class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-bold text-slate-900">Riwayat kelas</h2>
                    <p class="mt-1 text-sm text-slate-500">Penempatan rombel tercatat per semester, termasuk riwayat terdahulu.</p>
                </header>
                <div class="overflow-x-auto">
                    <table class="min-w-[680px] w-full border-collapse text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr><th class="px-5 py-3">Tahun ajaran</th><th class="px-5 py-3">Semester</th><th class="px-5 py-3">Kelas</th><th class="px-5 py-3">No. absen</th><th class="px-5 py-3">Status penempatan</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in class_history" :key="item.id">
                                <td class="px-5 py-3.5 text-slate-700">{{ item.academic_year || '—' }}</td>
                                <td class="px-5 py-3.5 text-slate-700">{{ item.term || '—' }}</td>
                                <td class="px-5 py-3.5 font-semibold text-slate-800">{{ item.class_name || '—' }}</td>
                                <td class="px-5 py-3.5 text-slate-700">{{ item.roll_number ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-slate-700">{{ enrollmentLabels[item.status] || item.status }}</td>
                            </tr>
                            <tr v-if="class_history.length === 0"><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada riwayat penempatan kelas.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.field-label { display: grid; gap: .4rem; color: #475569; font-size: .8rem; font-weight: 600; }
.field-input { min-height: 2.5rem; width: 100%; border: 1px solid #cbd5e1; border-radius: .65rem; background: white; padding: .5rem .75rem; color: #1e293b; font-size: .875rem; font-weight: 400; }
.field-error { color: #b91c1c; font-size: .75rem; font-weight: 500; }
.danger-button { min-height: 2.5rem; border: 1px solid #fecaca; border-radius: .65rem; padding: .5rem .8rem; color: #b91c1c; font-size: .8rem; font-weight: 700; }
</style>
