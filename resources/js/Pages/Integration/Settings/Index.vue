<script setup lang="ts">
import { reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import AppCard from '@/components/AppCard.vue';
import AppButton from '@/components/AppButton.vue';
import StatusBadge, { type StatusTone } from '@/components/StatusBadge.vue';
import StateFeedback from '@/components/StateFeedback.vue';

type ConfigValue = string | number | boolean | null | Record<string, string>;
type ConfigMap = Record<string, ConfigValue>;

interface SystemItem {
    system_key: string;
    label: string;
    description: string;
    gate_note?: string;
    category_hint?: string;
    fallback_message?: string;
    mechanism: string;
    is_enabled: boolean;
    endpoint_url?: string | null;
    config: ConfigMap;
    has_secret: boolean;
    secret_masked?: string | null;
    last_health_status: string;
    last_checked_at?: string | null;
    last_error?: string | null;
}

interface SyncLogItem {
    id: number;
    system_key: string;
    system_label: string;
    direction: string;
    batch_code: string;
    status: string;
    total_rows: number;
    success_rows: number;
    failed_rows: number;
    failed_details: Array<{
        row_number: number;
        source_identifier: string;
        error_message: string;
        recovery_action: string;
    }>;
    triggered_by_name?: string | null;
    created_at?: string | null;
}

interface PreviewResult {
    system_key: string;
    total_rows: number;
    valid_rows: number;
    failed_rows: number;
    failed_details: Array<{
        row_number: number;
        source_identifier: string;
        error_message: string;
        recovery_action: string;
    }>;
}

const props = defineProps<{
    workspace: {
        academic_systems: SystemItem[];
        channels: SystemItem[];
        sync_logs: SyncLogItem[];
    };
    previewResult?: PreviewResult | null;
    healthResult?: { system_key: string; status: string; fallback_active: boolean; message: string } | null;
}>();

const activeTab = ref<'reconciliation' | 'channels'>('reconciliation');
const selectedSystem = ref<string>('emis');
const importCsvText = ref<string>("nisn,full_name,class_name,status\n0081234567,Alya Putri,XI IPA 1,active");

const academicDrafts = reactive<Record<string, { mechanism: string; is_enabled: boolean; endpoint_url: string; secret_token: string }>>(
    Object.fromEntries(
        props.workspace.academic_systems.map((sys) => [
            sys.system_key,
            {
                mechanism: sys.mechanism,
                is_enabled: sys.is_enabled,
                endpoint_url: sys.endpoint_url ?? '',
                secret_token: '',
            },
        ]),
    ),
);

const channelDrafts = reactive<Record<string, { is_enabled: boolean; endpoint_url: string; secret_token: string; config: ConfigMap }>>(
    Object.fromEntries(
        props.workspace.channels.map((ch) => [
            ch.system_key,
            {
                is_enabled: ch.is_enabled,
                endpoint_url: ch.endpoint_url ?? '',
                secret_token: '',
                config: { ...ch.config },
            },
        ]),
    ),
);

const configurationLabels: Record<string, string> = {
    configuration_validated: 'Konfigurasi lokal tervalidasi',
    configuration_unverified: 'Konfigurasi belum diperiksa',
    offline: 'Simulasi gangguan',
};

const importLabels: Record<string, string> = {
    completed: 'Semua baris valid',
    partial_failed: 'Sebagian baris gagal validasi',
    failed: 'Validasi gagal',
};

const healthTone = (status: string): StatusTone => {
    if (status === 'completed') return 'success';
    if (status === 'degraded' || status === 'partial_failed') return 'warning';
    if (status === 'offline' || status === 'failed') return 'danger';
    return 'neutral';
};

const saveAcademic = (systemKey: string) => {
    const draft = academicDrafts[systemKey];
    const sys = props.workspace.academic_systems.find((s) => s.system_key === systemKey);
    router.put(`/pengaturan/integrasi/koneksi/${systemKey}`, {
        mechanism: draft.mechanism,
        is_enabled: draft.is_enabled,
        endpoint_url: draft.endpoint_url || null,
        secret_token: draft.secret_token || null,
        config: sys?.config ?? {},
    }, {
        preserveScroll: true,
        onSuccess: () => {
            draft.secret_token = '';
        },
    });
};

const saveChannel = (channelKey: string) => {
    const draft = channelDrafts[channelKey];
    router.put(`/pengaturan/integrasi/koneksi/${channelKey}`, {
        mechanism: 'provider',
        is_enabled: draft.is_enabled,
        endpoint_url: draft.endpoint_url || null,
        secret_token: draft.secret_token || null,
        config: draft.config,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            draft.secret_token = '';
        },
    });
};

const testChannel = (systemKey: string, simulateOutage = false) => {
    router.post(`/pengaturan/integrasi/koneksi/${systemKey}/uji`, {
        simulate_outage: simulateOutage,
    }, { preserveScroll: true });
};

const runPreview = () => {
    router.post(`/pengaturan/integrasi/sinkronisasi/${selectedSystem.value}/pratinjau`, {
        payload_text: importCsvText.value,
    }, { preserveScroll: true });
};

const runCommit = () => {
    router.post(`/pengaturan/integrasi/sinkronisasi/${selectedSystem.value}/impor`, {
        payload_text: importCsvText.value,
    }, { preserveScroll: true });
};

const retryBatch = (logId: number) => {
    router.post(`/pengaturan/integrasi/log/${logId}/ulang`, {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Integrasi & Kanal Eksternal" />

    <AppLayout title="Integrasi & Kanal Eksternal">
        <div class="space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Integrasi Sistem & Kanal Eksternal</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Pengaturan konektivitas EMIS, RDM, SLiMS, rekonsiliasi berkas CSV/JSON, serta konfigurasi aman penyedia kanal eksternal.
                    </p>
                </div>

                <div class="flex rounded-lg border border-slate-300 bg-white p-1">
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-xs font-semibold transition"
                        :class="activeTab === 'reconciliation' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900'"
                        @click="activeTab = 'reconciliation'"
                    >
                        EMIS, RDM & SLiMS
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-xs font-semibold transition"
                        :class="activeTab === 'channels' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900'"
                        @click="activeTab = 'channels'"
                    >
                        Kanal Eksternal & Perangkat
                    </button>
                </div>
            </div>

            <div v-if="healthResult" class="rounded-lg border px-4 py-3 text-xs" :class="healthResult.fallback_active ? 'border-amber-200 bg-amber-50 text-amber-950' : 'border-emerald-200 bg-emerald-50 text-emerald-950'">
                <strong>{{ healthResult.system_key.toUpperCase() }} ({{ configurationLabels[healthResult.status] ?? healthResult.status }}):</strong> {{ healthResult.message }}
            </div>

            <template v-if="activeTab === 'reconciliation'">
                <div class="grid gap-4 lg:grid-cols-3">
                    <AppCard v-for="sys in workspace.academic_systems" :key="sys.system_key">
                        <div class="p-4 space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h2 class="text-base font-semibold text-slate-900">{{ sys.label }}</h2>
                                    <p class="text-xs text-slate-500">{{ sys.description }}</p>
                                </div>
                                <StatusBadge :status="configurationLabels[sys.last_health_status] ?? sys.last_health_status" :tone="healthTone(sys.last_health_status)" size="sm" />
                            </div>

                            <p v-if="sys.gate_note" class="rounded bg-slate-50 px-2.5 py-1.5 text-[11px] text-slate-600">
                                {{ sys.gate_note }}
                            </p>

                            <div class="space-y-2 text-xs">
                                <label class="block">
                                    <span class="font-medium text-slate-700">Mekanisme Integrasi</span>
                                    <select v-model="academicDrafts[sys.system_key].mechanism" class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm">
                                        <option value="file_exchange">Ekspor / Validasi Berkas CSV/JSON</option>
                                        <option value="link">Tautan Portal Resmi</option>
                                        <option value="api_sync">API Sync (Menunggu K-05)</option>
                                    </select>
                                </label>

                                <label class="block">
                                    <span class="font-medium text-slate-700">Endpoint / URL Portal</span>
                                    <input v-model="academicDrafts[sys.system_key].endpoint_url" type="url" placeholder="https://..." class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm">
                                </label>

                                <label class="block">
                                    <span class="font-medium text-slate-700">
                                        Token / API Key Baru
                                        <span v-if="sys.has_secret" class="text-emerald-700">(Tersimpan: {{ sys.secret_masked }})</span>
                                    </span>
                                    <input v-model="academicDrafts[sys.system_key].secret_token" type="password" placeholder="Kosongkan jika tidak diubah" class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm">
                                </label>

                                <label class="inline-flex items-center gap-2 pt-1">
                                    <input v-model="academicDrafts[sys.system_key].is_enabled" type="checkbox" class="rounded border-slate-300">
                                    <span class="text-slate-700 font-medium">Aktifkan koneksi sistem ini</span>
                                </label>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3">
                                <a
                                    :href="`/pengaturan/integrasi/sinkronisasi/${sys.system_key}/ekspor`"
                                    class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    Ekspor CSV
                                </a>
                                <div class="flex gap-2">
                                    <AppButton variant="ghost" size="sm" @click="testChannel(sys.system_key)">Uji</AppButton>
                                    <AppButton variant="primary" size="sm" @click="saveAcademic(sys.system_key)">Simpan</AppButton>
                                </div>
                            </div>
                        </div>
                    </AppCard>
                </div>

                <AppCard>
                    <div class="p-5 space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">Validasi Berkas untuk Rekonsiliasi</h2>
                                <p class="text-xs text-slate-500">Periksa validitas baris dan simpan hasil rekonsiliasi. Data tujuan tetap memerlukan proses impor terpisah.</p>
                            </div>
                            <select v-model="selectedSystem" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                                <option v-for="sys in workspace.academic_systems" :key="sys.system_key" :value="sys.system_key">{{ sys.label }}</option>
                            </select>
                        </div>

                        <textarea
                            v-model="importCsvText"
                            rows="5"
                            class="w-full rounded-lg border border-slate-300 p-3 font-mono text-xs"
                            placeholder="Tempel isi CSV atau JSON baris data di sini..."
                        />

                        <div class="flex flex-wrap justify-end gap-2">
                            <AppButton variant="secondary" size="sm" @click="runPreview">Pratinjau Validasi (Dry-Run)</AppButton>
                            <AppButton variant="primary" size="sm" @click="runCommit">Validasi dan Simpan Hasil</AppButton>
                        </div>

                        <div v-if="previewResult" class="rounded-lg border border-slate-200 bg-slate-50 p-4 space-y-2 text-xs">
                            <p class="font-semibold text-slate-900">
                                Pratinjau {{ previewResult.system_key.toUpperCase() }}: Total {{ previewResult.total_rows }} baris · Valid {{ previewResult.valid_rows }} · Gagal {{ previewResult.failed_rows }}
                            </p>
                            <ul v-if="previewResult.failed_details.length > 0" class="list-disc pl-5 text-rose-700 space-y-1">
                                <li v-for="(err, idx) in previewResult.failed_details" :key="idx">
                                    Baris #{{ err.row_number }} ({{ err.source_identifier }}): {{ err.error_message }} — <em>{{ err.recovery_action }}</em>
                                </li>
                            </ul>
                        </div>
                    </div>
                </AppCard>

                <AppCard>
                    <div class="p-5 space-y-3">
                        <h2 class="text-base font-semibold text-slate-900">Riwayat Ekspor dan Validasi</h2>

                        <StateFeedback
                            v-if="workspace.sync_logs.length === 0"
                            state="empty"
                            title="Belum ada log rekonsiliasi"
                            description="Jalankan ekspor CSV atau simpan hasil validasi untuk mencatat batch pertama."
                        />

                        <div v-else class="space-y-3">
                            <div
                                v-for="log in workspace.sync_logs"
                                :key="log.id"
                                class="rounded-lg border border-slate-200 p-3 space-y-2"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono text-xs font-semibold text-slate-700">{{ log.batch_code }}</span>
                                        <span class="text-xs font-medium text-slate-600">{{ log.system_label }} ({{ log.direction }})</span>
                                        <StatusBadge :status="log.direction === 'import' ? (importLabels[log.status] ?? log.status) : log.status" :tone="healthTone(log.status)" size="sm" />
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-slate-600">
                                        <span>{{ log.success_rows }}/{{ log.total_rows }} {{ log.direction === 'import' ? 'valid' : 'diekspor' }} ({{ log.failed_rows }} gagal)</span>
                                        <AppButton
                                            v-if="log.status === 'partial_failed' || log.status === 'failed'"
                                            variant="warning"
                                            size="sm"
                                            @click="retryBatch(log.id)"
                                        >
                                            Validasi Ulang
                                        </AppButton>
                                    </div>
                                </div>

                                <ul v-if="log.failed_details.length > 0" class="list-disc pl-5 text-xs text-rose-700 space-y-0.5">
                                    <li v-for="(err, idx) in log.failed_details" :key="idx">
                                        Baris #{{ err.row_number }} ({{ err.source_identifier }}): {{ err.error_message }} · Pemulihan: {{ err.recovery_action }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </AppCard>
            </template>

            <template v-else>
                <div class="grid gap-4 md:grid-cols-2">
                    <AppCard v-for="ch in workspace.channels" :key="ch.system_key">
                        <div class="p-4 space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ ch.category_hint }}</span>
                                    <h2 class="text-base font-semibold text-slate-900">{{ ch.label }}</h2>
                                </div>
                                <StatusBadge :status="configurationLabels[ch.last_health_status] ?? ch.last_health_status" :tone="healthTone(ch.last_health_status)" size="sm" />
                            </div>

                            <p class="rounded bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                <strong>Fallback Layanan Inti:</strong> {{ ch.fallback_message }}
                            </p>

                            <div class="space-y-2 text-xs">
                                <label class="block">
                                    <span class="font-medium text-slate-700">Endpoint / Gateway URL</span>
                                    <input v-model="channelDrafts[ch.system_key].endpoint_url" type="url" placeholder="https://api.contoh.sch.id/..." class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm">
                                </label>

                                <label class="block">
                                    <span class="font-medium text-slate-700">
                                        Kredensial / Secret Token
                                        <span v-if="ch.has_secret" class="text-emerald-700">(Terenkripsi: {{ ch.secret_masked }})</span>
                                    </span>
                                    <input v-model="channelDrafts[ch.system_key].secret_token" type="password" placeholder="Tidak pernah ditampilkan ulang setelah disimpan" class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm">
                                </label>

                                <label class="inline-flex items-center gap-2 pt-1">
                                    <input v-model="channelDrafts[ch.system_key].is_enabled" type="checkbox" class="rounded border-slate-300">
                                    <span class="font-medium text-slate-700">Aktifkan kanal ini</span>
                                </label>
                            </div>

                            <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-3">
                                <AppButton variant="ghost" size="sm" @click="testChannel(ch.system_key, true)">Simulasi Gangguan</AppButton>
                                <AppButton variant="secondary" size="sm" @click="testChannel(ch.system_key, false)">Periksa Konfigurasi</AppButton>
                                <AppButton variant="primary" size="sm" @click="saveChannel(ch.system_key)">Simpan Kanal</AppButton>
                            </div>
                        </div>
                    </AppCard>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
