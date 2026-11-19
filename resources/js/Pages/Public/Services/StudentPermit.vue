<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FormField from '@/components/FormField.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

interface StudentOption {
    student_id: number;
    nis: string;
    name: string;
    class_group_name?: string | null;
}

interface PermitRow {
    id: number;
    student_name: string;
    class_name: string;
    ticket_code?: string | null;
    type_label: string;
    title: string;
    reason: string;
    start_date: string;
    end_date: string;
    status: string;
    status_label: string;
    decision_note?: string | null;
    attachment_document_id?: number | null;
}

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    students: StudentOption[];
    myPermits: PermitRow[];
    types: { value: string; label: string }[];
    operations: { permit_same_day_cutoff: string; permit_max_advance_days: number };
    timezone: string;
    issuedReceipt?: { id: number; ticket_code?: string | null; verification_secret: string } | null;
}>();

const page = usePage();
const flashMessage = computed(() => (page.props.flash as { message?: string } | undefined)?.message);
const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const revisingPermit = ref<PermitRow | null>(null);

const form = useForm({
    student_id: props.students[0]?.student_id ?? ('' as string | number),
    one_time_token: '',
    type: 'sick',
    start_date: '',
    end_date: '',
    title: '',
    reason: '',
    contact_name: '',
    contact_phone: '',
    signature_data: '',
    attachment: null as File | null,
    idempotency_key: `izin-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
});

const revisionForm = useForm({
    start_date: '',
    end_date: '',
    title: '',
    reason: '',
    attachment: null as File | null,
});

const openRevision = (row: PermitRow) => {
    revisingPermit.value = row;
    revisionForm.start_date = row.start_date;
    revisionForm.end_date = row.end_date;
    revisionForm.title = row.title;
    revisionForm.reason = row.reason;
};

const submitPermit = () => {
    form.post('/layanan/izin-siswa', {
        forceFormData: true,
        preserveScroll: true,
    });
};

const submitRevision = () => {
    if (!revisingPermit.value) return;
    revisionForm.post(`/layanan/izin-siswa/${revisingPermit.value.id}/perbaiki`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            revisingPermit.value = null;
        },
    });
};
</script>

<template>
    <Head :title="`Izin & Dispensasi Siswa — ${school.name}`" />
    <main class="public-site public-portal-page" :style="themeStyle">
        <PublicHeader :school="school" />

        <section class="portal-container">
            <header class="portal-hero">
                <div>
                    <p class="eyebrow">Layanan Perizinan Siswa</p>
                    <h1>Pengajuan Surat Izin &amp; Dispensasi</h1>
                    <p>
                        Batas jam izin hari yang sama: <strong>{{ operations.permit_same_day_cutoff }} ({{ timezone }})</strong> ·
                        Izin terencana maksimal <strong>{{ operations.permit_max_advance_days }} hari</strong> ke depan.
                    </p>
                </div>
                <div class="hero-actions">
                    <Link v-if="!students.length" href="/login" class="ui-button ui-button--secondary ui-button--md">Masuk Akun Wali / Siswa</Link>
                    <Link href="/lacak?category=izin" class="ui-button ui-button--secondary ui-button--md">Lacak Tiket Izin</Link>
                </div>
            </header>

            <div v-if="flashMessage" class="alert-banner alert-banner--success" role="status">{{ flashMessage }}</div>

            <div v-if="issuedReceipt" class="alert-banner alert-banner--pin" role="status">
                <strong>Pengajuan Izin Tercatat: {{ issuedReceipt.ticket_code }}</strong>
                <span>PIN Verifikasi Lacak: <strong>{{ issuedReceipt.verification_secret }}</strong></span>
                <a :href="`/layanan/izin-siswa/${issuedReceipt.id}/pdf`" target="_blank" rel="noopener" class="ui-button ui-button--secondary ui-button--sm">Cetak Bukti PDF</a>
            </div>

            <section class="portal-card">
                <h2>Formulir Pengajuan Izin</h2>
                <form class="form-grid" @submit.prevent="submitPermit">
                    <FormField v-if="students.length" id="izin-student" label="Pilih Siswa" required :error="form.errors.student_id" v-slot="{ inputId, describedBy, invalid }">
                        <select :id="inputId" v-model="form.student_id" required :aria-describedby="describedBy" :aria-invalid="invalid">
                            <option v-for="s in students" :key="s.student_id" :value="s.student_id">
                                {{ s.name }} ({{ s.nis }}) — {{ s.class_group_name || '-' }}
                            </option>
                        </select>
                    </FormField>

                    <template v-else>
                        <FormField id="izin-student-id" label="ID Siswa" required :error="form.errors.student_id" v-slot="{ inputId, describedBy, invalid }">
                            <input :id="inputId" v-model="form.student_id" type="number" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </FormField>
                        <FormField id="izin-token" label="Token Izin Sekali Pakai" required hint="Token diterbitkan oleh wali kelas atau petugas piket." :error="form.errors.one_time_token" v-slot="{ inputId, describedBy, invalid }">
                            <input :id="inputId" v-model="form.one_time_token" type="text" required placeholder="IZT-XXXXXXXXXX" :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </FormField>
                    </template>

                    <FormField id="izin-type" label="Kategori Izin" required :error="form.errors.type" v-slot="{ inputId, describedBy, invalid }">
                        <select :id="inputId" v-model="form.type" :aria-describedby="describedBy" :aria-invalid="invalid">
                            <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </FormField>

                    <FormField id="izin-start" label="Tanggal Mulai" required :error="form.errors.start_date" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.start_date" type="date" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="izin-end" label="Tanggal Selesai" required :error="form.errors.end_date" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.end_date" type="date" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="izin-contact-name" label="Nama Wali / Kontak" :error="form.errors.contact_name" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.contact_name" type="text" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <FormField id="izin-contact-phone" label="Nomor HP / WhatsApp Aktif" :error="form.errors.contact_phone" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.contact_phone" type="text" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <div class="full-span">
                        <FormField id="izin-title" label="Judul / Perihal Izin" required :error="form.errors.title" v-slot="{ inputId, describedBy, invalid }">
                            <input :id="inputId" v-model="form.title" type="text" required placeholder="Contoh: Izin Sakit Demam / Dispensasi Lomba Sains" :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </FormField>
                    </div>

                    <div class="full-span">
                        <FormField id="izin-reason" label="Alasan Rinci" required :error="form.errors.reason" v-slot="{ inputId, describedBy, invalid }">
                            <textarea :id="inputId" v-model="form.reason" rows="3" required :aria-describedby="describedBy" :aria-invalid="invalid" />
                        </FormField>
                    </div>

                    <FormField id="izin-attachment" label="Lampiran Bukti (Surat Dokter / Undangan, PDF/JPG/PNG)" :error="form.errors.attachment" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" :aria-describedby="describedBy" :aria-invalid="invalid" @change="form.attachment = ($event.target as HTMLInputElement).files?.[0] ?? null" />
                    </FormField>

                    <FormField id="izin-signature" label="Tanda Tangan Pemohon (Ketik Nama Lengkap)" required :error="form.errors.signature_data" v-slot="{ inputId, describedBy, invalid }">
                        <input :id="inputId" v-model="form.signature_data" type="text" required placeholder="Nama penandatangan izin" :aria-describedby="describedBy" :aria-invalid="invalid" />
                    </FormField>

                    <div class="full-span">
                        <button type="submit" class="ui-button ui-button--primary ui-button--md" :disabled="form.processing">
                            Kirim Permohonan Izin
                        </button>
                    </div>
                </form>
            </section>

            <section v-if="revisingPermit" class="portal-card">
                <h2>Perbaiki Permohonan Izin — {{ revisingPermit.title }}</h2>
                <p v-if="revisingPermit.decision_note" class="warning-callout">Catatan Wali Kelas: {{ revisingPermit.decision_note }}</p>
                <form class="form-grid" @submit.prevent="submitRevision">
                    <FormField id="rev-start" label="Tanggal Mulai" required v-slot="{ inputId }">
                        <input :id="inputId" v-model="revisionForm.start_date" type="date" required />
                    </FormField>
                    <FormField id="rev-end" label="Tanggal Selesai" required v-slot="{ inputId }">
                        <input :id="inputId" v-model="revisionForm.end_date" type="date" required />
                    </FormField>
                    <div class="full-span">
                        <FormField id="rev-title" label="Perihal Izin" required v-slot="{ inputId }">
                            <input :id="inputId" v-model="revisionForm.title" type="text" required />
                        </FormField>
                    </div>
                    <div class="full-span">
                        <FormField id="rev-reason" label="Alasan Rinci" required v-slot="{ inputId }">
                            <textarea :id="inputId" v-model="revisionForm.reason" rows="3" required />
                        </FormField>
                    </div>
                    <div class="full-span">
                        <FormField id="rev-att" label="Unggah Ulang Lampiran (Opsional)" v-slot="{ inputId }">
                            <input :id="inputId" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" @change="revisionForm.attachment = ($event.target as HTMLInputElement).files?.[0] ?? null" />
                        </FormField>
                    </div>
                    <div class="full-span action-row">
                        <button type="submit" class="ui-button ui-button--primary ui-button--md" :disabled="revisionForm.processing">Kirim Ulang Perbaikan</button>
                        <button type="button" class="ui-button ui-button--secondary ui-button--md" @click="revisingPermit = null">Batal</button>
                    </div>
                </form>
            </section>

            <section v-if="myPermits.length" class="portal-card">
                <h2>Riwayat Permohonan Izin</h2>
                <div class="permit-list">
                    <article v-for="row in myPermits" :key="row.id" class="permit-item">
                        <div>
                            <div class="permit-head">
                                <strong>{{ row.title }}</strong>
                                <StatusBadge :status="row.status" :label="row.status_label" size="sm" />
                            </div>
                            <p class="muted">{{ row.student_name }} ({{ row.class_name }}) · {{ row.start_date }} s.d. {{ row.end_date }}</p>
                            <p v-if="row.decision_note" class="muted">Catatan: {{ row.decision_note }}</p>
                        </div>
                        <div class="action-row">
                            <a :href="`/layanan/izin-siswa/${row.id}/pdf`" target="_blank" rel="noopener" class="ui-button ui-button--secondary ui-button--sm">Bukti PDF</a>
                            <button v-if="row.status === 'needs_revision'" type="button" class="ui-button ui-button--primary ui-button--sm" @click="openRevision(row)">Perbaiki</button>
                        </div>
                    </article>
                </div>
            </section>
        </section>
    </main>
</template>

<style scoped>
.public-portal-page { min-height: 100vh; background: var(--public-surface, #f5f8fb); color: var(--public-ink, #173044); }
.portal-container { width: min(1040px, calc(100% - 2rem)); margin-inline: auto; padding: 2.25rem 0 4rem; display: grid; gap: 1.5rem; }
.portal-hero { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 1rem; }
.portal-hero h1 { margin: .35rem 0 .45rem; font-size: clamp(1.75rem, 3.5vw, 2.4rem); line-height: 1.15; }
.portal-hero p { margin: 0; color: var(--public-muted, #526673); }
.hero-actions, .action-row { display: flex; flex-wrap: wrap; gap: .6rem; }
.portal-card { background: #fff; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 16px; padding: 1.5rem; display: grid; gap: 1rem; }
.portal-card h2 { margin: 0; font-size: 1.2rem; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1rem; }
.full-span { grid-column: 1 / -1; }
.alert-banner { padding: .9rem 1rem; border-radius: 12px; display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; justify-content: space-between; }
.alert-banner--success { background: #e1f2e8; color: #1b5c3e; border: 1px solid #b6dec9; font-weight: 600; }
.alert-banner--pin { background: #eef4ff; color: #1e3a8a; border: 1px solid #bfdbfe; }
.warning-callout { margin: 0; padding: .85rem 1rem; border-radius: 10px; background: #fff1d5; color: #80500d; font-weight: 600; }
.permit-list { display: grid; gap: .8rem; }
.permit-item { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; padding: .95rem; border: 1px solid var(--public-panel-border, #d8e2ee); border-radius: 12px; }
.permit-head { display: flex; align-items: center; gap: .5rem; }
.muted { margin: .25rem 0 0; color: var(--public-muted, #526673); font-size: .88rem; }
</style>
