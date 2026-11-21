<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import QrScanPanel from '@/components/QrScanPanel.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import OperationsLayout from '@/layouts/OperationsLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';

interface ScanFeedItem {
    id?: number;
    student_name: string;
    class_name: string;
    scan_method: string;
    scan_result: string;
    scanned_at?: string | null;
}

interface QueuedScanEvent {
    code: string;
    scan_method: 'rfid' | 'qr';
    client_event_id: string;
    scanned_at: string;
}

const props = defineProps<{
    school: { id: number; name: string };
    devices: { id: number; name: string; location?: string | null; gate_enabled: boolean }[];
    recentScans: ScanFeedItem[];
    operations: { kiosk_dedup_seconds: number };
}>();

const QUEUE_KEY = `ss_kiosk_queue_${props.school.id}`;
const kioskRootRef = ref<HTMLElement | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);

const scanMode = ref<'rfid' | 'qr'>('rfid');
const selectedDeviceId = ref<number | null>(props.devices[0]?.id ?? null);
const codeInput = ref('');
const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);
const isFullscreen = ref(false);
const gateMessage = ref('');
const lastResult = ref<{ status: string; message: string; student?: { name: string; class_name: string } | null } | null>(null);
const feed = ref<ScanFeedItem[]>([...props.recentScans]);
const offlineQueue = ref<QueuedScanEvent[]>([]);

const loadQueue = () => {
    try {
        const raw = localStorage.getItem(QUEUE_KEY);
        offlineQueue.value = raw ? JSON.parse(raw) : [];
    } catch {
        offlineQueue.value = [];
    }
};

const saveQueue = () => {
    try {
        localStorage.setItem(QUEUE_KEY, JSON.stringify(offlineQueue.value));
    } catch {
        // Ignore quota errors
    }
};

const csrfToken = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

const submitScan = async () => {
    const code = codeInput.value.trim();
    if (!code) return;
    codeInput.value = '';

    const eventId = `kiosk-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
    const nowIso = new Date().toISOString();

    if (!isOnline.value) {
        offlineQueue.value.push({ code, scan_method: scanMode.value, client_event_id: eventId, scanned_at: nowIso });
        saveQueue();
        lastResult.value = { status: 'queued', message: 'Jaringan terputus. Scan disimpan di antrean lokal untuk sinkronisasi otomatis.' };
        return;
    }

    try {
        const res = await fetch('/kios-presensi/scan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({
                code,
                scan_method: scanMode.value,
                device_id: selectedDeviceId.value,
                client_event_id: eventId,
                scanned_at: nowIso,
            }),
        });
        const data = await res.json();
        lastResult.value = data;
        if (data.student) {
            feed.value.unshift({
                student_name: data.student.name,
                class_name: data.student.class_name,
                scan_method: scanMode.value,
                scan_result: data.status,
                scanned_at: data.scanned_at,
            });
            feed.value = feed.value.slice(0, 15);
        }
    } catch {
        offlineQueue.value.push({ code, scan_method: scanMode.value, client_event_id: eventId, scanned_at: nowIso });
        saveQueue();
        lastResult.value = { status: 'queued', message: 'Koneksi ke server gagal. Scan disimpan ke antrean offline.' };
    } finally {
        inputRef.value?.focus();
    }
};

const syncOfflineQueue = async () => {
    if (!offlineQueue.value.length || !isOnline.value) return;

    try {
        const res = await fetch('/kios-presensi/sinkron', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({
                device_id: selectedDeviceId.value,
                events: offlineQueue.value.slice(0, 50),
            }),
        });
        if (res.ok) {
            offlineQueue.value = offlineQueue.value.slice(50);
            saveQueue();
            lastResult.value = { status: 'recorded', message: 'Antrean offline berhasil disinkronkan.' };
        }
    } catch {
        // Keep queue for next retry
    }
};

const toggleFullscreen = async () => {
    if (!document.fullscreenElement && kioskRootRef.value?.requestFullscreen) {
        await kioskRootRef.value.requestFullscreen();
        isFullscreen.value = true;
    } else if (document.exitFullscreen) {
        await document.exitFullscreen();
        isFullscreen.value = false;
    }
};

const triggerGate = async () => {
    const res = await fetch('/kios-presensi/gate', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
    });
    const data = await res.json();
    gateMessage.value = data.message || 'Gate diperiksa.';
};

const currentClock = ref('');
let clockTimer: ReturnType<typeof setInterval> | null = null;

const updateClock = () => {
    currentClock.value = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(new Date());
};

const onOnline = () => { isOnline.value = true; syncOfflineQueue(); };
const onOffline = () => { isOnline.value = false; };

onMounted(() => {
    loadQueue();
    updateClock();
    clockTimer = setInterval(updateClock, 1000);
    window.addEventListener('online', onOnline);
    window.addEventListener('offline', onOffline);
});

onBeforeUnmount(() => {
    if (clockTimer) clearInterval(clockTimer);
    window.removeEventListener('online', onOnline);
    window.removeEventListener('offline', onOffline);
});
</script>

<template>
    <Head title="Kios Presensi RFID & QR" />
    <OperationsLayout mode="kiosk" title="Kios Presensi">
        <div ref="kioskRootRef" class="kiosk-shell kiosk-display">
            <header class="kiosk-head">
                <div class="kiosk-brand">
                    <span class="kiosk-logo-dot" aria-hidden="true"></span>
                    <div>
                        <h1>{{ school.name }}</h1>
                        <p><span class="kiosk-dot">●</span> KIOS PRESENSI SISWA <span class="kiosk-gate-pill">Gate Utama</span></p>
                    </div>
                </div>
                <div class="kiosk-clock-box">
                    <strong v-if="currentClock" class="kiosk-clock-time">{{ currentClock }}</strong>
                    <span class="kiosk-clock-date">{{ new Intl.DateTimeFormat('id-ID', { dateStyle: 'full' }).format(new Date()) }}</span>
                </div>
                <div class="kiosk-controls">
                    <StatusBadge :status="isOnline ? 'active' : 'warning'" :label="isOnline ? 'Online' : 'Offline'" />
                    <AppButton variant="secondary" size="sm" @click="scanMode = 'rfid'; inputRef?.focus()">Tap Kartu</AppButton>
                    <AppButton variant="secondary" size="sm" @click="scanMode = 'qr'">QR Kamera</AppButton>
                    <AppButton variant="secondary" size="sm" @click="triggerGate">Gate</AppButton>
                    <AppButton variant="secondary" size="sm" @click="toggleFullscreen">{{ isFullscreen ? 'Keluar' : 'Fullscreen' }}</AppButton>
                    <a href="/dashboard" class="kiosk-dash-link">Dashboard</a>
                </div>
            </header>

            <div v-if="gateMessage" class="gate-banner" role="status">{{ gateMessage }}</div>
            <div v-if="offlineQueue.length" class="offline-banner" role="status">
                <span>{{ offlineQueue.length }} scan tersimpan di antrean offline.</span>
                <AppButton size="sm" @click="syncOfflineQueue">Sinkronkan Sekarang</AppButton>
            </div>

            <section class="scanner-card" aria-labelledby="kiosk-scanner-title">
                <div class="kiosk-scan-prompt">
                    <h2 id="kiosk-scanner-title">{{ scanMode === 'rfid' ? 'Tempelkan Kartu Pelajar' : 'Pindai Kode QR' }}</h2>
                    <p>{{ scanMode === 'rfid' ? 'Dekatkan kartu RFID atau arahkan Barcode kartu ke alat scanner untuk presensi mandiri.' : 'Arahkan kamera ke kode QR kartu pelajar.' }}</p>
                </div>
                <form class="scan-form" @submit.prevent="submitScan">
                    <input
                        ref="inputRef"
                        v-model="codeInput"
                        type="text"
                        autofocus
                        :placeholder="scanMode === 'rfid' ? 'Scanner Aktif & Siap Menerima Tap (atau ketik UID)...' : 'Arahkan QR ke kamera atau ketik token QR...'"
                        aria-label="Input kartu RFID atau token QR"
                    />
                    <AppButton type="submit">Catat Scan</AppButton>
                </form>

                <QrScanPanel v-if="scanMode === 'qr'" @detected="codeInput = $event; submitScan()" />

                <div v-if="lastResult" class="result-banner" :class="`is-${lastResult.status}`" role="status">
                    <strong v-if="lastResult.student">{{ lastResult.student.name }} · {{ lastResult.student.class_name }}</strong>
                    <span>{{ lastResult.message }}</span>
                </div>
            </section>

            <section class="panel-card">
                <div class="kiosk-footer-bar">
                    <div class="kiosk-stats-pills">
                        <span>Tercatat Sesi: <strong>{{ feed.length }}</strong></span>
                        <span>Deduplikasi: <strong>{{ operations.kiosk_dedup_seconds }}s</strong></span>
                    </div>
                    <span class="sub-text">Mode USB Keyboard RFID / Barcode Auto-Focus</span>
                </div>
                <ul v-if="feed.length" class="feed-list mt-2">
                    <li v-for="(item, idx) in feed" :key="idx" class="feed-item">
                        <div>
                            <strong>{{ item.student_name }}</strong>
                            <span class="sub-text"> · {{ item.class_name }} · {{ item.scan_method.toUpperCase() }}</span>
                        </div>
                        <StatusBadge
                            :status="item.scan_result === 'recorded' ? 'completed' : (item.scan_result === 'duplicate' ? 'warning' : 'rejected')"
                            :label="item.scan_result === 'recorded' ? 'Tercatat' : (item.scan_result === 'duplicate' ? 'Duplikat' : 'Ditolak')"
                            size="sm"
                        />
                    </li>
                </ul>
            </section>
        </div>
    </OperationsLayout>
</template>

<style scoped>
.kiosk-shell { display: grid; gap: 1.25rem; background: var(--bg, #f5f7fb); }
.kiosk-display {
    padding: clamp(1rem, 3vw, 2.25rem);
    border-radius: 1rem;
    color: #fff;
    background: radial-gradient(ellipse at top, #064e3b, #0f172a 62%);
}
.kiosk-display .kiosk-head h1 { color: #fff; }
.kiosk-display .kiosk-head p { color: #d1fae5; }
.kiosk-display .scanner-card, .kiosk-display .panel-card { color: #e2e8f0; background: #0f172a; }
.kiosk-scan-prompt {
    padding: clamp(1rem, 4vw, 2rem);
    border-radius: .85rem;
    background: #ecfdf5;
    color: #064e3b;
    text-align: center;
}
.kiosk-scan-prompt h2 { margin: 0 0 .35rem; font-size: clamp(1.35rem, 4vw, 2.2rem); font-weight: 800; }
.kiosk-scan-prompt p { margin: 0; }
.scan-form input { min-width: 0; }
.kiosk-head { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 1rem; }
.kiosk-brand { display: flex; align-items: center; gap: .75rem; }
.kiosk-dot { color: #34d399; }
.kiosk-gate-pill { margin-left: .4rem; padding: .15rem .55rem; border-radius: 999px; background: rgb(16 185 129 / 15%); border: 1px solid rgb(52 211 153 / 30%); color: #6ee7b7; font-size: .68rem; font-weight: 700; }
.kiosk-clock-box { text-align: center; }
.kiosk-clock-time { display: block; font-family: ui-monospace, monospace; font-size: 2rem; font-weight: 900; letter-spacing: .04em; color: #fff; line-height: 1; }
.kiosk-clock-date { display: block; font-size: .72rem; color: #94a3b8; margin-top: .2rem; }
.kiosk-dash-link { padding: .35rem .75rem; border-radius: .5rem; background: #1e293b; border: 1px solid #334155; color: #e2e8f0; font-size: .75rem; font-weight: 700; text-decoration: none; }
.kiosk-footer-bar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .75rem; }
.kiosk-stats-pills { display: flex; flex-wrap: wrap; gap: .5rem; }
.kiosk-stats-pills span { padding: .25rem .7rem; border-radius: .5rem; background: #1e293b; border: 1px solid #334155; font-size: .75rem; color: #cbd5e1; }
.kiosk-head h1 { margin: 0; font-size: 1.15rem; font-weight: 800; }
.kiosk-head p { margin: .15rem 0 0; color: #34d399; font-size: .75rem; font-weight: 700; }
.kiosk-controls { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; justify-content: flex-end; }
@media (max-width: 900px) { .kiosk-head { grid-template-columns: 1fr; text-align: center; } .kiosk-controls { justify-content: center; } }
.scanner-card, .panel-card { background: var(--surface, #fff); border: 1px solid var(--line, #dee4ee); border-radius: 14px; padding: 1.25rem; display: grid; gap: 1rem; }
.scan-form { display: flex; flex-wrap: wrap; gap: .6rem; }
.scan-form input { flex: 1 1 280px; min-height: 48px; padding: .6rem .9rem; border: 2px solid var(--primary, #3157a4); border-radius: 10px; font-size: 1rem; }
.result-banner { padding: 1rem; border-radius: 12px; display: grid; gap: .25rem; font-weight: 600; }
.result-banner.is-recorded { background: #e1f2e8; color: #1b5c3e; }
.result-banner.is-duplicate, .result-banner.is-queued { background: #fff1d5; color: #80500d; }
.result-banner.is-rejected { background: #fbe9e7; color: #b13f38; }
.gate-banner, .offline-banner { padding: .8rem 1rem; border-radius: 12px; background: #eef4ff; color: #1e3a8a; display: flex; justify-content: space-between; align-items: center; gap: .75rem; }
.feed-list { list-style: none; margin: 0; padding: 0; display: grid; gap: .55rem; }
.feed-item { display: flex; justify-content: space-between; align-items: center; gap: .75rem; padding: .65rem .8rem; border: 1px solid var(--line, #dee4ee); border-radius: 10px; }
.sub-text { color: var(--ink-soft, #536174); font-size: .85rem; }
</style>
