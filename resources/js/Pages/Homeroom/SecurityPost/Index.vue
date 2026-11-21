<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import QrScanPanel from '@/components/QrScanPanel.vue';
import FormField from '@/components/FormField.vue';
import StateFeedback from '@/components/StateFeedback.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import OperationsLayout from '@/layouts/OperationsLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface GatePassSummary {
    id: number;
    pass_code: string;
    student_id: number;
    student_name: string;
    student_nis: string;
    class_name: string;
    exit_type: string;
    reason: string;
    destination?: string | null;
    valid_from: string;
    valid_until: string;
    status: string;
    exit_recorded_at?: string | null;
    return_recorded_at?: string | null;
    handover_notes?: string | null;
}

const props = defineProps<{
    unlocked: boolean;
    hasPin: boolean;
    canConfigurePin: boolean;
    passes: GatePassSummary[];
}>();

const pinForm = useForm({
    pin: '',
});

const configurePinForm = useForm({
    pin: '',
});

const scanCode = ref('');
const scanMode = ref<'auto' | 'exit' | 'return'>('auto');
const handoverNotes = ref('');
const scanning = ref(false);
const scanFeedback = ref<{ status: string; code: string; message: string; pass?: GatePassSummary | null } | null>(null);
const livePasses = ref<GatePassSummary[]>([...props.passes]);

const csrfToken = (): string =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const appendDigit = (digit: string) => {
    if (pinForm.pin.length < 8) {
        pinForm.pin += digit;
    }
};

const backspacePin = () => {
    pinForm.pin = pinForm.pin.slice(0, -1);
};

const clearPin = () => {
    pinForm.pin = '';
};

const submitPinUnlock = () => {
    pinForm.post('/pos-jaga/buka-akses', {
        preserveScroll: true,
        onSuccess: () => {
            pinForm.pin = '';
        },
    });
};

const lockPost = () => {
    router.post('/pos-jaga/kunci', {}, { preserveScroll: true });
};

const savePinSetting = () => {
    configurePinForm.post('/pos-jaga/atur-pin', {
        preserveScroll: true,
        onSuccess: () => {
            configurePinForm.pin = '';
        },
    });
};

const submitScan = async () => {
    const code = scanCode.value.trim();
    if (!code || scanning.value) return;

    scanning.value = true;
    try {
        const res = await fetch('/pos-jaga/scan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({
                code,
                mode: scanMode.value,
                handover_notes: handoverNotes.value || null,
            }),
        });

        const payload = await res.json();
        scanFeedback.value = payload;

        if (payload.pass) {
            const idx = livePasses.value.findIndex((p) => p.id === payload.pass.id);
            if (idx >= 0) {
                livePasses.value[idx] = payload.pass;
            } else {
                livePasses.value.unshift(payload.pass);
            }
            scanCode.value = '';
            handoverNotes.value = '';
        }
    } finally {
        scanning.value = false;
    }
};

const statusTone = (status: string): 'neutral' | 'info' | 'warning' | 'success' | 'danger' => {
    if (status === 'active') return 'warning';
    if (status === 'out' || status === 'exited') return 'info';
    if (status === 'returned') return 'success';
    if (status === 'rejected' || status === 'cancelled' || status === 'expired') return 'danger';
    return 'neutral';
};
</script>

<template>
    <OperationsLayout mode="gate" title="Pos Jaga Gerbang">
        <Head title="Pos Jaga Gerbang" />

        <div class="security-post-layout space-y-6" :class="{ 'is-locked': !unlocked }">
            <header class="operations-scan-hero flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="operations-scan-label">Akses Pos Jaga · Validasi Gerbang</p>
                    <h1 class="text-xl font-bold text-white">Pos Jaga &amp; Validasi Gerbang</h1>
                    <p class="text-sm text-emerald-100">
                        Validasi keluar-masuk siswa berdasarkan surat jalan atau pass yang diterbitkan guru piket.
                    </p>
                </div>
                <AppButton v-if="unlocked" type="button" variant="secondary" @click="lockPost">
                    Kunci Layar Pos Jaga
                </AppButton>
            </header>

            <details v-if="canConfigurePin" class="security-pin-configuration rounded-xl border border-slate-200 bg-white p-5">
                <summary>Konfigurasi PIN Pos Jaga</summary>
                <form @submit.prevent="savePinSetting" class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[220px] flex-1">
                        <FormField
                            id="security-post-pin-config"
                            label="Konfigurasi PIN Numerik Pos Jaga (Admin / Operator)"
                            :error="configurePinForm.errors.pin"
                            hint="Minimal 4 digit angka. Wajib dimasukkan petugas pos sebelum membuka panel validasi."
                        >
                            <input
                                id="security-post-pin-config"
                                v-model="configurePinForm.pin"
                                type="password"
                                inputmode="numeric"
                                placeholder="Contoh: 2468"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            />
                        </FormField>
                    </div>
                    <AppButton type="submit" :disabled="configurePinForm.processing">
                        {{ hasPin ? 'Perbarui PIN' : 'Simpan PIN Pos Jaga' }}
                    </AppButton>
                </form>
            </details>

            <div v-if="!unlocked" class="operations-pin-panel mx-auto max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
                <div class="text-center space-y-1">
                    <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 4 7v6c0 5 3.4 9.4 8 10 4.6-.6 8-5 8-10V7l-8-4Z"/><path d="m9.5 12 1.7 1.7 3.8-3.8"/></svg>
                    </div>
                    <h2 class="text-lg font-extrabold tracking-wide text-slate-900 uppercase">AKSES POS JAGA</h2>
                    <p class="text-xs font-semibold text-slate-500">Scan Kartu Izin Siswa di Gerbang</p>
                    <p class="text-xs text-slate-400">Masukkan PIN Keamanan Satpam</p>
                </div>

                <div
                    v-if="!hasPin"
                    class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900"
                >
                    <p class="font-semibold">PIN Pos Jaga belum dikonfigurasi</p>
                    <p class="mt-0.5">Minta Admin atau Operator sekolah mengatur PIN Pos Jaga terlebih dahulu.</p>
                </div>

                <div class="space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-center font-mono text-2xl tracking-[0.4em] text-slate-900">
                        {{ pinForm.pin ? '•'.repeat(pinForm.pin.length) : '' }}
                    </div>
                    <p v-if="pinForm.errors.pin" class="text-center text-sm font-medium text-rose-600">
                        {{ pinForm.errors.pin }}
                    </p>

                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="digit in ['1', '2', '3', '4', '5', '6', '7', '8', '9']"
                            :key="digit"
                            type="button"
                            class="rounded-xl border border-slate-200 bg-white py-3 text-lg font-bold text-slate-900 hover:bg-slate-100"
                            @click="appendDigit(digit)"
                        >
                            {{ digit }}
                        </button>
                        <button
                            type="button"
                            class="rounded-xl border border-rose-100 bg-rose-50 py-3 text-sm font-bold text-rose-700 hover:bg-rose-100"
                            aria-label="Hapus PIN"
                            @click="backspacePin"
                        >
                            ⌫
                        </button>
                        <button
                            type="button"
                            class="rounded-xl border border-slate-200 bg-white py-3 text-lg font-bold text-slate-900 hover:bg-slate-100"
                            @click="appendDigit('0')"
                        >
                            0
                        </button>
                        <button
                            type="button"
                            class="rounded-xl border border-emerald-600 bg-emerald-600 py-3 text-lg font-bold text-white hover:bg-emerald-700 disabled:opacity-50"
                            :disabled="pinForm.processing || !hasPin || pinForm.pin.length < 4"
                            aria-label="Buka Panel Pos Jaga"
                            @click="submitPinUnlock"
                        >
                            ✓
                        </button>
                    </div>

                    <div class="text-center pt-1">
                        <a href="/" class="text-xs font-medium text-slate-500 hover:text-slate-800">← Batal &amp; Kembali ke Beranda</a>
                    </div>
                </div>
            </div>

            <div v-else class="space-y-6">
                <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
                    <div class="operations-scan-panel">
                        <h2 class="text-base font-semibold text-slate-900">Pindai / Input Kode Pass Gerbang</h2>
                        <p>Validasi surat jalan keluar atau kembali ke madrasah.</p>
                        <QrScanPanel @detected="scanCode = $event" />
                    </div>

                    <div class="grid gap-3 md:grid-cols-4">
                        <div class="md:col-span-2">
                            <FormField id="pos-scan-code" label="Kode Pass atau Scan QR" required>
                                <input
                                    id="pos-scan-code"
                                    v-model="scanCode"
                                    type="text"
                                    placeholder="Contoh: GP-ABCD1234"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm uppercase"
                                    @keydown.enter.prevent="submitScan"
                                />
                            </FormField>
                        </div>
                        <FormField id="pos-scan-mode" label="Mode Validasi">
                            <select id="pos-scan-mode" v-model="scanMode" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="auto">Otomatis (Keluar / Kembali)</option>
                                <option value="exit">Catat Keluar Gerbang</option>
                                <option value="return">Catat Kembali ke Sekolah</option>
                            </select>
                        </FormField>
                        <FormField id="pos-handover-notes" label="Catatan Serah Terima (Opsional)">
                            <input
                                id="pos-handover-notes"
                                v-model="handoverNotes"
                                type="text"
                                placeholder="Nama penjemput / catatan"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            />
                        </FormField>
                    </div>

                    <div>
                        <AppButton type="button" :disabled="scanning || !scanCode.trim()" @click="submitScan">
                            {{ scanning ? 'Memvalidasi...' : 'Validasi Pass Sekarang' }}
                        </AppButton>
                    </div>

                    <div
                        v-if="scanFeedback"
                        class="rounded-xl border p-4"
                        :class="
                            scanFeedback.status === 'rejected'
                                ? 'border-rose-200 bg-rose-50 text-rose-900'
                                : 'border-emerald-200 bg-emerald-50 text-emerald-900'
                        "
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-semibold">{{ scanFeedback.message }}</p>
                            <StatusBadge :status="scanFeedback.status" :tone="statusTone(scanFeedback.status)" size="sm" />
                        </div>
                        <p v-if="scanFeedback.pass" class="mt-1 text-sm">
                            {{ scanFeedback.pass.student_name }} ({{ scanFeedback.pass.class_name }}) — {{ scanFeedback.pass.reason }}
                        </p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
                    <h2 class="text-base font-semibold text-slate-900">Antrean Pass Aktif & Terbaru</h2>

                    <StateFeedback
                        v-if="livePasses.length === 0"
                        state="empty"
                        title="Belum ada pass izin keluar"
                        description="Pass yang diterbitkan guru piket akan muncul di sini."
                    />

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                <tr>
                                    <th class="px-3 py-2.5">Kode Pass</th>
                                    <th class="px-3 py-2.5">Siswa</th>
                                    <th class="px-3 py-2.5">Keperluan</th>
                                    <th class="px-3 py-2.5">Waktu</th>
                                    <th class="px-3 py-2.5">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="pass in livePasses" :key="pass.id" class="hover:bg-slate-50/70">
                                    <td class="px-3 py-3 font-mono text-xs font-bold text-slate-900">{{ pass.pass_code }}</td>
                                    <td class="px-3 py-3">
                                        <p class="font-medium text-slate-900">{{ pass.student_name }}</p>
                                        <p class="text-xs text-slate-500">{{ pass.student_nis }} • {{ pass.class_name }}</p>
                                    </td>
                                    <td class="px-3 py-3">
                                        <p class="text-slate-800">{{ pass.reason }}</p>
                                        <p v-if="pass.handover_notes" class="text-xs text-slate-500">Serah terima: {{ pass.handover_notes }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-xs text-slate-600">
                                        <p>Berlaku: {{ pass.valid_from }} — {{ pass.valid_until }}</p>
                                        <p v-if="pass.exit_recorded_at">Keluar: {{ pass.exit_recorded_at }}</p>
                                        <p v-if="pass.return_recorded_at" class="text-emerald-700">Kembali: {{ pass.return_recorded_at }}</p>
                                    </td>
                                    <td class="px-3 py-3">
                                        <StatusBadge :status="pass.status" :tone="statusTone(pass.status)" size="sm" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </OperationsLayout>
</template>
