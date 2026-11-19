<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import FormField from '@/components/FormField.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    cards: {
        id: number;
        student_id: number;
        student_name: string;
        student_nis: string;
        class_name: string;
        card_uid?: string | null;
        qr_token: string;
        status: string;
        revocation_reason?: string | null;
    }[];
    students: { student_id: number; name: string; nis: string; class_group_name?: string | null }[];
    devices: { id: number; name: string; location?: string | null; gate_enabled: boolean; is_active: boolean }[];
    filters: { search: string };
}>();

const search = ref(props.filters.search || '');
const revokingCardId = ref<number | null>(null);

const cardForm = useForm({
    student_id: props.students[0]?.student_id ?? ('' as string | number),
    card_uid: '',
    qr_token: '',
});

const deviceForm = useForm({
    name: '',
    location: '',
    gate_enabled: false,
});

const revokeForm = useForm({
    reason: '',
});

const applySearch = () => {
    router.get('/akademik/kartu-siswa', { search: search.value || undefined }, { preserveState: true, replace: true });
};

const submitCard = () => {
    cardForm.post('/akademik/kartu-siswa', {
        preserveScroll: true,
        onSuccess: () => cardForm.reset('card_uid', 'qr_token'),
    });
};

const submitDevice = () => {
    deviceForm.post('/akademik/kartu-siswa/perangkat', {
        preserveScroll: true,
        onSuccess: () => deviceForm.reset(),
    });
};

const submitRevoke = () => {
    if (!revokingCardId.value) return;
    revokeForm.post(`/akademik/kartu-siswa/${revokingCardId.value}/cabut`, {
        preserveScroll: true,
        onSuccess: () => {
            revokingCardId.value = null;
            revokeForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Kartu RFID & QR Siswa — Akademik" />
    <AppLayout title="Kartu RFID / QR Siswa">
        <div class="cards-workspace">
            <header class="workspace-head">
                <div>
                    <h1>Manajemen Kartu RFID &amp; QR Siswa</h1>
                    <p>Daftarkan kartu RFID/QR untuk kios presensi, cabut kartu yang hilang, dan kelola perangkat kios sekolah.</p>
                </div>
                <Link href="/kios-presensi" class="ui-button ui-button--primary ui-button--md">Buka Kios Presensi</Link>
            </header>

            <div class="grid-two">
                <section class="panel-card">
                    <h2>Daftarkan Kartu Baru</h2>
                    <form class="form-stack" @submit.prevent="submitCard">
                        <FormField id="card-student" label="Siswa" required :error="cardForm.errors.student_id" v-slot="{ inputId }">
                            <select :id="inputId" v-model="cardForm.student_id" required>
                                <option v-for="s in students" :key="s.student_id" :value="s.student_id">
                                    {{ s.name }} ({{ s.nis }}) — {{ s.class_group_name || '-' }}
                                </option>
                            </select>
                        </FormField>
                        <FormField id="card-uid" label="UID Kartu RFID (Opsional)" :error="cardForm.errors.card_uid" v-slot="{ inputId }">
                            <input :id="inputId" v-model="cardForm.card_uid" type="text" placeholder="Tap kartu RFID atau ketik UID" />
                        </FormField>
                        <FormField id="card-qr" label="Token QR (Kosongkan untuk generate otomatis)" :error="cardForm.errors.qr_token" v-slot="{ inputId }">
                            <input :id="inputId" v-model="cardForm.qr_token" type="text" placeholder="Otomatis jika dikosongkan" />
                        </FormField>
                        <AppButton type="submit" :disabled="cardForm.processing">Terbitkan Kartu</AppButton>
                    </form>
                </section>

                <section class="panel-card">
                    <h2>Daftarkan Perangkat Kios</h2>
                    <form class="form-stack" @submit.prevent="submitDevice">
                        <FormField id="dev-name" label="Nama Perangkat / Lokasi Pos" required :error="deviceForm.errors.name" v-slot="{ inputId }">
                            <input :id="inputId" v-model="deviceForm.name" type="text" required placeholder="Kios Gerbang Utama" />
                        </FormField>
                        <FormField id="dev-loc" label="Lokasi" v-slot="{ inputId }">
                            <input :id="inputId" v-model="deviceForm.location" type="text" placeholder="Lobi Depan" />
                        </FormField>
                        <label class="inline-check">
                            <input v-model="deviceForm.gate_enabled" type="checkbox" />
                            <span>Aktifkan tombol kontrol Gate (K-04)</span>
                        </label>
                        <AppButton type="submit" variant="secondary" :disabled="deviceForm.processing">Simpan Perangkat</AppButton>
                    </form>
                </section>
            </div>

            <section class="panel-card">
                <form class="filter-bar" @submit.prevent="applySearch">
                    <input v-model="search" type="search" placeholder="Cari nama siswa, NIS, UID, atau token QR..." aria-label="Cari kartu" />
                    <AppButton type="submit" variant="secondary">Cari</AppButton>
                </form>

                <div v-if="cards.length" class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Siswa &amp; Rombel</th>
                                <th>UID RFID</th>
                                <th>Token QR</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in cards" :key="c.id">
                                <td>
                                    <strong>{{ c.student_name }}</strong>
                                    <div class="sub-text">{{ c.student_nis }} · {{ c.class_name }}</div>
                                </td>
                                <td><code>{{ c.card_uid || '-' }}</code></td>
                                <td><code>{{ c.qr_token }}</code></td>
                                <td>
                                    <StatusBadge :status="c.status === 'active' ? 'active' : 'cancelled'" :label="c.status === 'active' ? 'Aktif' : 'Dicabut'" size="sm" />
                                    <div v-if="c.revocation_reason" class="sub-text">{{ c.revocation_reason }}</div>
                                </td>
                                <td>
                                    <AppButton v-if="c.status === 'active'" size="sm" variant="danger" @click="revokingCardId = c.id">Cabut</AppButton>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <StateFeedback v-else state="empty" title="Belum ada kartu terdaftar" description="Daftarkan kartu RFID/QR untuk siswa pada formulir di atas." />
            </section>

            <div v-if="revokingCardId" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="rev-card-title">
                <div class="modal-card">
                    <header class="modal-head">
                        <h2 id="rev-card-title">Cabut Kartu Siswa</h2>
                        <button type="button" class="close-btn" @click="revokingCardId = null" aria-label="Tutup">×</button>
                    </header>
                    <form class="form-stack" @submit.prevent="submitRevoke">
                        <FormField id="rev-card-reason" label="Alasan Pencabutan" required :error="revokeForm.errors.reason" v-slot="{ inputId }">
                            <input :id="inputId" v-model="revokeForm.reason" type="text" required placeholder="Kartu hilang / rusak" />
                        </FormField>
                        <div class="modal-footer">
                            <AppButton type="submit" variant="danger" :disabled="revokeForm.processing">Cabut Kartu</AppButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.cards-workspace { display: grid; gap: 1.25rem; }
.workspace-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 1rem; }
.workspace-head h1 { margin: 0; font-size: 1.5rem; }
.workspace-head p { margin: .25rem 0 0; color: var(--ink-soft, #536174); }
.grid-two { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; }
.panel-card { background: var(--surface, #fff); border: 1px solid var(--line, #dee4ee); border-radius: 14px; padding: 1.15rem; display: grid; gap: 1rem; }
.panel-card h2 { margin: 0; font-size: 1.05rem; }
.form-stack { display: grid; gap: .85rem; }
.inline-check { display: flex; align-items: center; gap: .5rem; font-size: .9rem; }
.filter-bar { display: flex; flex-wrap: wrap; gap: .6rem; }
.filter-bar input { flex: 1 1 260px; min-height: 40px; padding: .4rem .75rem; border: 1px solid var(--line-strong, #c8d1df); border-radius: 8px; }
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
.data-table th, .data-table td { padding: .7rem .65rem; border-bottom: 1px solid var(--line, #dee4ee); text-align: left; }
.sub-text { font-size: .78rem; color: var(--ink-soft, #536174); margin-top: .15rem; }
.modal-backdrop { position: fixed; inset: 0; background: rgb(15 23 42 / 55%); display: grid; place-items: center; padding: 1rem; z-index: 60; }
.modal-card { width: min(460px, 100%); background: var(--surface, #fff); border-radius: 16px; padding: 1.35rem; display: grid; gap: 1rem; }
.modal-head { display: flex; justify-content: space-between; align-items: center; }
.modal-head h2 { margin: 0; font-size: 1.1rem; }
.close-btn { min-width: 40px; min-height: 40px; border: 0; background: transparent; font-size: 1.5rem; cursor: pointer; }
.modal-footer { display: flex; justify-content: flex-end; }
</style>
