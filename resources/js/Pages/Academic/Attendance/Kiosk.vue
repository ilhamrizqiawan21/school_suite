<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
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
const videoRef = ref<HTMLVideoElement | null>(null);

const scanMode = ref<'rfid' | 'qr'>('rfid');
const selectedDeviceId = ref<number | null>(props.devices[0]?.id ?? null);
const codeInput = ref('');
const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);
const isFullscreen = ref(false);
const cameraActive = ref(false);
const cameraError = ref('');
const gateMessage = ref('');
const lastResult = ref<{ status: string; message: string; student?: { name: string; class_name: string } | null } | null>(null);
const feed = ref<ScanFeedItem[]>([...props.recentScans]);
const offlineQueue = ref<QueuedScanEvent[]>([]);
let mediaStream: MediaStream | null = null;

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

const toggleCamera = async () => {
    cameraError.value = '';
    if (cameraActive.value) {
        mediaStream?.getTracks().forEach((t) => t.stop());
        mediaStream = null;
        cameraActive.value = false;
        return;
    }
    if (!navigator.mediaDevices?.getUserMedia) {
        cameraError.value = 'Peramban tidak mendukung akses kamera langsung; gunakan input scanner/manual.';
        return;
    }
    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        if (videoRef.value) {
            videoRef.value.srcObject = mediaStream;
        }
        cameraActive.value = true;
    } catch {
        cameraError.value = 'Izin kamera ditolak atau kamera tidak tersedia.';
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

const onOnline = () => { isOnline.value = true; syncOfflineQueue(); };
const onOffline = () => { isOnline.value = false; };

onMounted(() => {
    loadQueue();
    window.addEventListener('online', onOnline);
    window.addEventListener('offline', onOffline);
});

onBeforeUnmount(() => {
    mediaStream?.getTracks().forEach((t) => t.stop());
    window.removeEventListener('online', onOnline);
    window.removeEventListener('offline', onOffline);
});
</script>

<template>
    <Head title="Kios Presensi RFID & QR" />
    <AppLayout title="Kios Presensi">
        <div ref="kioskRootRef" class="kiosk-shell">
            <header class="kiosk-head">
                <div>
                    <h1>Kios Presensi — {{ school.name }}</h1>
                    <p>Mode tap kartu RFID atau pindai QR. Jendela deduplikasi: {{ operations.kiosk_dedup_seconds }} detik.</p>
                </div>
                <div class="kiosk-controls">
                    <StatusBadge :status="isOnline ? 'active' : 'warning'" :label="isOnline ? 'Online' : 'Offline'" />
                    <AppButton variant="secondary" size="sm" @click="scanMode = 'rfid'; inputRef?.focus()">Tap Kartu RFID</AppButton>
                    <AppButton variant="secondary" size="sm" @click="scanMode = 'qr'; toggleCamera()">QR Kamera</AppButton>
                    <AppButton variant="secondary" size="sm" @click="triggerGate">Gate</AppButton>
                    <AppButton variant="secondary" size="sm" @click="toggleFullscreen">{{ isFullscreen ? 'Keluar Layar Penuh' : 'Layar Penuh' }}</AppButton>
                </div>
            </header>

            <div v-if="gateMessage" class="gate-banner" role="status">{{ gateMessage }}</div>
            <div v-if="offlineQueue.length" class="offline-banner" role="status">
                <span>{{ offlineQueue.length }} scan tersimpan di antrean offline.</span>
                <AppButton size="sm" @click="syncOfflineQueue">Sinkronkan Sekarang</AppButton>
            </div>

            <section class="scanner-card">
                <form class="scan-form" @submit.prevent="submitScan">
                    <input
                        ref="inputRef"
                        v-model="codeInput"
                        type="text"
                        autofocus
                        :placeholder="scanMode === 'rfid' ? 'Tempelkan kartu RFID pada reader atau ketik UID...' : 'Arahkan QR ke kamera atau ketik token QR...'"
                        aria-label="Input kartu RFID atau token QR"
                    />
                    <AppButton type="submit">Catat Scan</AppButton>
                </form>

                <div v-if="cameraActive" class="camera-box">
                    <video ref="videoRef" autoplay playsinline muted />
                </div>
                <p v-if="cameraError" class="cam-error" role="alert">{{ cameraError }}</p>

                <div v-if="lastResult" class="result-banner" :class="`is-${lastResult.status}`" role="status">
                    <strong v-if="lastResult.student">{{ lastResult.student.name }} · {{ lastResult.student.class_name }}</strong>
                    <span>{{ lastResult.message }}</span>
                </div>
            </section>

            <section class="panel-card">
                <h2>Log Pemindaian Terbaru (Data Minimal)</h2>
                <ul v-if="feed.length" class="feed-list">
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
                <p v-else class="sub-text">Belum ada pemindaian pada sesi ini.</p>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.kiosk-shell { display: grid; gap: 1.25rem; background: var(--bg, #f5f7fb); }
.kiosk-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; }
.kiosk-head h1 { margin: 0; font-size: 1.45rem; }
.kiosk-head p { margin: .2rem 0 0; color: var(--ink-soft, #536174); }
.kiosk-controls { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
.scanner-card, .panel-card { background: var(--surface, #fff); border: 1px solid var(--line, #dee4ee); border-radius: 14px; padding: 1.25rem; display: grid; gap: 1rem; }
.scan-form { display: flex; flex-wrap: wrap; gap: .6rem; }
.scan-form input { flex: 1 1 280px; min-height: 48px; padding: .6rem .9rem; border: 2px solid var(--primary, #3157a4); border-radius: 10px; font-size: 1rem; }
.camera-box video { width: min(420px, 100%); border-radius: 12px; border: 1px solid var(--line, #dee4ee); }
.cam-error { margin: 0; color: #b13f38; font-weight: 600; }
.result-banner { padding: 1rem; border-radius: 12px; display: grid; gap: .25rem; font-weight: 600; }
.result-banner.is-recorded { background: #e1f2e8; color: #1b5c3e; }
.result-banner.is-duplicate, .result-banner.is-queued { background: #fff1d5; color: #80500d; }
.result-banner.is-rejected { background: #fbe9e7; color: #b13f38; }
.gate-banner, .offline-banner { padding: .8rem 1rem; border-radius: 12px; background: #eef4ff; color: #1e3a8a; display: flex; justify-content: space-between; align-items: center; gap: .75rem; }
.feed-list { list-style: none; margin: 0; padding: 0; display: grid; gap: .55rem; }
.feed-item { display: flex; justify-content: space-between; align-items: center; gap: .75rem; padding: .65rem .8rem; border: 1px solid var(--line, #dee4ee); border-radius: 10px; }
.sub-text { color: var(--ink-soft, #536174); font-size: .85rem; }
</style>
