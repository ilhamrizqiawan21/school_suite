<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

interface StatusOption { value: string; label: string }
interface CorrespondenceRecord {
    id: number;
    letter_type: 'incoming' | 'outgoing';
    agenda_number: string | null;
    letter_number: string | null;
    letter_date: string | null;
    received_at: string | null;
    sender: string | null;
    recipient: string | null;
    subject: string;
    summary: string | null;
    disposition: string | null;
    status: string;
    attachment_name: string | null;
    has_attachment: boolean;
    archived_at: string | null;
}

const props = defineProps<{
    records: CorrespondenceRecord[];
    filter: 'all' | 'incoming' | 'outgoing' | 'archived';
    status_options: { incoming: StatusOption[]; outgoing: StatusOption[] };
}>();

const page = usePage();
const flashMessage = computed(() => (page.props.flash as { message?: string }).message);
const form = useForm({
    letter_type: 'incoming' as 'incoming' | 'outgoing',
    letter_number: '',
    letter_date: '',
    received_at: '',
    sender: '',
    recipient: '',
    subject: '',
    summary: '',
    attachment: null as File | null,
});

const targets = reactive<Record<number, string>>({});
const dispositions = reactive<Record<number, string>>({});
const statusErrors = reactive<Record<number, string>>({});

const tabs = [
    { value: 'all', label: 'Semua surat' },
    { value: 'incoming', label: 'Surat masuk' },
    { value: 'outgoing', label: 'Surat keluar' },
    { value: 'archived', label: 'Arsip' },
] as const;

function nextStatuses(record: CorrespondenceRecord): StatusOption[] {
    const statuses = props.status_options[record.letter_type];
    const currentIndex = statuses.findIndex((item) => item.value === record.status);
    return currentIndex >= 0 ? statuses.slice(currentIndex + 1, currentIndex + 2) : [];
}

function labelForStatus(record: CorrespondenceRecord): string {
    return props.status_options[record.letter_type].find((item) => item.value === record.status)?.label ?? record.status;
}

function saveRecord() {
    form.post('/administrasi/persuratan/surat', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.letter_type = 'incoming';
        },
    });
}

function setAttachment(event: Event) {
    form.attachment = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function updateStatus(record: CorrespondenceRecord) {
    const status = targets[record.id] || nextStatuses(record)[0]?.value;
    if (!status) return;

    router.patch(`/administrasi/persuratan/surat/${record.id}/status`, {
        status,
        disposition: dispositions[record.id] ?? '',
    }, {
        preserveScroll: true,
        onSuccess: () => { delete statusErrors[record.id]; },
        onError: (errors) => { statusErrors[record.id] = errors.status || errors.disposition || 'Status surat gagal diperbarui.'; },
    });
}
</script>

<template>
    <Head title="Persuratan & Arsip" />
    <AppLayout title="Persuratan & Arsip">
        <main class="view space-y-6">
            <section class="hero-card">
                <div>
                    <p class="eyebrow">Administrasi sekolah</p>
                    <h1>Persuratan &amp; arsip</h1>
                    <p>Catat surat masuk dan keluar, teruskan disposisi, lalu simpan surat selesai ke arsip sekolah.</p>
                </div>
                <div class="grade-summary correspondence-summary">
                    <div><span>Surat masuk</span><strong>{{ records.filter((item) => item.letter_type === 'incoming').length }}</strong></div>
                    <div><span>Surat keluar</span><strong>{{ records.filter((item) => item.letter_type === 'outgoing').length }}</strong></div>
                    <div><span>Diarsipkan</span><strong>{{ records.filter((item) => item.status === 'archived').length }}</strong></div>
                </div>
            </section>

            <p v-if="flashMessage" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                {{ flashMessage }}
            </p>

            <section class="panel correspondence-panel">
                <div class="table-title"><div><h2>Catat surat</h2><p>Nomor surat keluar diisi sesuai penomoran yang berlaku di sekolah.</p></div></div>
                <form class="correspondence-form" enctype="multipart/form-data" @submit.prevent="saveRecord">
                    <label>Jenis surat
                        <select v-model="form.letter_type">
                            <option value="incoming">Surat masuk</option>
                            <option value="outgoing">Surat keluar</option>
                        </select>
                        <span v-if="form.errors.letter_type" class="field-error">{{ form.errors.letter_type }}</span>
                    </label>
                    <label v-if="form.letter_type === 'outgoing'">Nomor surat keluar <span class="required-mark">*</span>
                        <input v-model="form.letter_number" maxlength="100" required autocomplete="off" placeholder="Contoh: 001/ADM/2026" />
                        <span v-if="form.errors.letter_number" class="field-error">{{ form.errors.letter_number }}</span>
                    </label>
                    <label v-else>Nomor surat dari pengirim
                        <input v-model="form.letter_number" maxlength="100" autocomplete="off" />
                        <span v-if="form.errors.letter_number" class="field-error">{{ form.errors.letter_number }}</span>
                    </label>
                    <label>Tanggal surat
                        <input v-model="form.letter_date" type="date" />
                        <span v-if="form.errors.letter_date" class="field-error">{{ form.errors.letter_date }}</span>
                    </label>
                    <label v-if="form.letter_type === 'incoming'">Tanggal diterima <span class="required-mark">*</span>
                        <input v-model="form.received_at" type="date" required />
                        <span v-if="form.errors.received_at" class="field-error">{{ form.errors.received_at }}</span>
                    </label>
                    <label v-if="form.letter_type === 'incoming'">Asal surat <span class="required-mark">*</span>
                        <input v-model="form.sender" maxlength="180" required autocomplete="off" />
                        <span v-if="form.errors.sender" class="field-error">{{ form.errors.sender }}</span>
                    </label>
                    <label v-else>Tujuan surat <span class="required-mark">*</span>
                        <input v-model="form.recipient" maxlength="180" required autocomplete="off" />
                        <span v-if="form.errors.recipient" class="field-error">{{ form.errors.recipient }}</span>
                    </label>
                    <label class="correspondence-wide">Perihal <span class="required-mark">*</span>
                        <input v-model="form.subject" maxlength="200" required autocomplete="off" />
                        <span v-if="form.errors.subject" class="field-error">{{ form.errors.subject }}</span>
                    </label>
                    <label class="correspondence-wide">Ringkasan
                        <textarea v-model="form.summary" rows="2" maxlength="5000" />
                        <span v-if="form.errors.summary" class="field-error">{{ form.errors.summary }}</span>
                    </label>
                    <label class="correspondence-wide">Lampiran (PDF, Office, JPG/PNG; maks. 10 MB)
                        <input type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" @change="setAttachment" />
                        <span v-if="form.errors.attachment" class="field-error">{{ form.errors.attachment }}</span>
                    </label>
                    <div class="correspondence-wide correspondence-actions">
                        <button class="primary-button" type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan…' : 'Simpan surat' }}
                        </button>
                    </div>
                </form>
            </section>

            <section class="panel correspondence-panel">
                <div class="table-title">
                    <div><h2>Daftar surat</h2><p>Surat masuk mengikuti alur diterima → disposisi → selesai → arsip.</p></div>
                </div>
                <nav class="correspondence-tabs" aria-label="Filter surat">
                    <Link v-for="tab in tabs" :key="tab.value" :href="`/administrasi/persuratan?filter=${tab.value}`" class="correspondence-tab" :class="{ 'is-active': filter === tab.value }">
                        {{ tab.label }}
                    </Link>
                </nav>

                <div v-if="records.length" class="correspondence-list">
                    <article v-for="record in records" :key="record.id" class="correspondence-card">
                        <div class="correspondence-card-heading">
                            <div>
                                <span class="correspondence-kind" :class="record.letter_type === 'incoming' ? 'kind-incoming' : 'kind-outgoing'">
                                    {{ record.letter_type === 'incoming' ? 'Surat masuk' : 'Surat keluar' }}
                                </span>
                                <h3>{{ record.subject }}</h3>
                                <p>{{ record.letter_type === 'incoming' ? `Asal: ${record.sender || '—'}` : `Tujuan: ${record.recipient || '—'}` }}</p>
                            </div>
                            <span class="status-chip">{{ labelForStatus(record) }}</span>
                        </div>
                        <dl class="correspondence-meta">
                            <div v-if="record.agenda_number"><dt>No. agenda</dt><dd>{{ record.agenda_number }}</dd></div>
                            <div v-if="record.letter_number"><dt>No. surat</dt><dd>{{ record.letter_number }}</dd></div>
                            <div v-if="record.letter_date"><dt>Tanggal surat</dt><dd>{{ record.letter_date }}</dd></div>
                            <div v-if="record.received_at"><dt>Diterima</dt><dd>{{ record.received_at }}</dd></div>
                        </dl>
                        <p v-if="record.summary" class="correspondence-summary-text">{{ record.summary }}</p>
                        <p v-if="record.disposition" class="correspondence-disposition"><strong>Disposisi:</strong> {{ record.disposition }}</p>
                        <div class="correspondence-footer">
                            <a v-if="record.has_attachment" :href="`/administrasi/persuratan/surat/${record.id}/lampiran`" class="row-action-button">
                                Unduh {{ record.attachment_name || 'lampiran' }}
                            </a>
                            <span v-else class="muted-note">Tidak ada lampiran</span>
                            <form v-if="nextStatuses(record).length" class="status-update-form" @submit.prevent="updateStatus(record)">
                                <label class="sr-only" :for="`next-status-${record.id}`">Status berikutnya</label>
                                <select :id="`next-status-${record.id}`" v-model="targets[record.id]">
                                    <option v-for="status in nextStatuses(record)" :key="status.value" :value="status.value">{{ status.label }}</option>
                                </select>
                                <input v-if="record.letter_type === 'incoming' && (targets[record.id] || nextStatuses(record)[0]?.value) === 'disposition'" v-model="dispositions[record.id]" maxlength="3000" placeholder="Isi disposisi" required />
                                <button class="secondary-button" type="submit">Perbarui status</button>
                                <span v-if="statusErrors[record.id]" class="field-error" role="alert">{{ statusErrors[record.id] }}</span>
                            </form>
                            <span v-else-if="record.status === 'archived'" class="archived-note">Tersimpan di arsip</span>
                        </div>
                    </article>
                </div>
                <p v-else class="empty-state">Belum ada surat pada filter ini. Surat yang dicatat akan muncul di sini.</p>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
.correspondence-panel { padding: 1.25rem; }
.correspondence-summary { margin: 0; min-width: min(100%, 30rem); }
.correspondence-form { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
.correspondence-form label { display: grid; gap: .4rem; color: #42526b; font-size: .85rem; font-weight: 600; }
.correspondence-form input, .correspondence-form select, .correspondence-form textarea, .status-update-form select, .status-update-form input { width: 100%; min-height: 2.7rem; border: 1px solid #cbd5e1; border-radius: .65rem; background: white; padding: .6rem .75rem; color: #172b4d; }
.correspondence-form input[type=file] { padding: .4rem; }
.correspondence-wide { grid-column: 1 / -1; }
.correspondence-actions { display: flex; justify-content: end; }
.primary-button, .secondary-button { border: 1px solid #3157a4; border-radius: .65rem; background: #3157a4; padding: .65rem 1rem; color: white; font-weight: 700; }
.secondary-button { background: white; color: #3157a4; white-space: nowrap; }
.primary-button:disabled { cursor: wait; opacity: .6; }
.required-mark, .field-error { color: #b42318; }
.field-error { font-size: .78rem; font-weight: 500; }
.correspondence-tabs { display: flex; flex-wrap: wrap; gap: .5rem; border-bottom: 1px solid #dbe3ef; padding: 0 0 .8rem; }
.correspondence-tab { border: 1px solid #dbe3ef; border-radius: 999px; padding: .45rem .85rem; color: #42526b; font-size: .85rem; font-weight: 600; text-decoration: none; }
.correspondence-tab.is-active { border-color: #3157a4; background: #eaf0ff; color: #214991; }
.correspondence-list { display: grid; gap: .85rem; padding-top: 1rem; }
.correspondence-card { border: 1px solid #dbe3ef; border-radius: .85rem; padding: 1rem; }
.correspondence-card-heading { display: flex; align-items: start; justify-content: space-between; gap: 1rem; }
.correspondence-card h3 { margin: .3rem 0 .2rem; color: #172b4d; font-size: 1.05rem; font-weight: 700; }
.correspondence-card-heading p, .muted-note, .archived-note { margin: 0; color: #64748b; font-size: .82rem; }
.correspondence-kind, .status-chip { display: inline-flex; border-radius: 999px; padding: .2rem .55rem; font-size: .75rem; font-weight: 700; }
.kind-incoming { background: #eaf0ff; color: #3157a4; }
.kind-outgoing { background: #f1f5f9; color: #475569; }
.status-chip { background: #ecfdf3; color: #067647; white-space: nowrap; }
.correspondence-meta { display: flex; flex-wrap: wrap; gap: .65rem 1.5rem; margin: .9rem 0; }
.correspondence-meta div { display: grid; gap: .1rem; }
.correspondence-meta dt { color: #64748b; font-size: .72rem; }
.correspondence-meta dd { margin: 0; color: #334155; font-size: .82rem; font-weight: 600; }
.correspondence-summary-text, .correspondence-disposition { margin: .5rem 0; color: #475569; font-size: .86rem; white-space: pre-wrap; }
.correspondence-footer { display: flex; align-items: center; justify-content: space-between; gap: .75rem; border-top: 1px solid #e2e8f0; padding-top: .8rem; }
.status-update-form { display: flex; flex-wrap: wrap; align-items: center; justify-content: end; gap: .5rem; }
.status-update-form select { width: auto; min-width: 9rem; }
.status-update-form input { width: min(22rem, 100%); }
.row-action-button { display: inline-flex; align-items: center; border: 1px solid #c9d7f3; border-radius: .55rem; background: #eff5ff; padding: .5rem .7rem; color: #3157a4; font-size: .8rem; font-weight: 700; text-decoration: none; }
.empty-state { padding: 1.25rem 0; color: #64748b; }
@media (max-width: 800px) { .correspondence-summary { width: 100%; min-width: 0; } .correspondence-form { grid-template-columns: 1fr; } .correspondence-wide { grid-column: auto; } .correspondence-footer { align-items: stretch; flex-direction: column; } .status-update-form { justify-content: stretch; } .status-update-form select, .status-update-form input, .status-update-form button { width: 100%; } .correspondence-card-heading { align-items: start; } }
</style>
