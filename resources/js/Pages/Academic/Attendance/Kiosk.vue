<script setup lang="ts">
import QrScanPanel from '@/components/QrScanPanel.vue';
import OperationsLayout from '@/layouts/OperationsLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

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

interface KioskStats {
    total_students: number;
    present: number;
    on_time: number;
    late: number;
    checked_out: number;
    absent: number;
}

const props = defineProps<{
    school: { id: number; name: string; logo_url?: string | null };
    devices: { id: number; name: string; location?: string | null; gate_enabled: boolean }[];
    recentScans: ScanFeedItem[];
    stats?: KioskStats;
    operations: { kiosk_dedup_seconds: number };
}>();

const QUEUE_KEY = `ss_kiosk_queue_${props.school.id}`;
const GATE_KEY = `ss_kiosk_gate_${props.school.id}`;

const kioskRootRef = ref<HTMLElement | null>(null);
const rfidInputRef = ref<HTMLInputElement | null>(null);

const selectedDeviceId = ref<number | null>(props.devices[0]?.id ?? null);
const currentGate = ref('Gate Utama');
const gateModalOpen = ref(false);
const gateDraft = ref('Gate Utama');
const qrModalOpen = ref(false);
const manualCode = ref('');
const codeInput = ref('');
const isProcessing = ref(false);
const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);
const isFullscreen = ref(false);
const gateMessage = ref('');
const offlineQueue = ref<QueuedScanEvent[]>([]);
const feed = ref<ScanFeedItem[]>([...props.recentScans]);
const stats = ref<KioskStats>(
    props.stats ?? {
        total_students: 0,
        present: 0,
        on_time: 0,
        late: 0,
        checked_out: 0,
        absent: 0,
    },
);

const lastResult = ref<{
    status: string;
    message: string;
    scanned_at?: string;
    student?: { name: string; class_name: string } | null;
} | null>(null);

let resetTimer: ReturnType<typeof setTimeout> | null = null;
let focusInterval: ReturnType<typeof setInterval> | null = null;
let clockInterval: ReturnType<typeof setInterval> | null = null;

const clockHm = ref('00:00');
const clockSec = ref('00');
const clockDate = ref('');

const updateClock = () => {
    const now = new Date();
    clockHm.value = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    clockSec.value = String(now.getSeconds()).padStart(2, '0');
    clockDate.value = new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(now);
};

const focusRfidInput = () => {
    if (qrModalOpen.value || gateModalOpen.value) return;
    rfidInputRef.value?.focus();
};

const loadLocalState = () => {
    try {
        const savedGate = localStorage.getItem(GATE_KEY);
        if (savedGate && savedGate.trim() !== '') {
            currentGate.value = savedGate.trim();
            gateDraft.value = currentGate.value;
        }
        const rawQueue = localStorage.getItem(QUEUE_KEY);
        offlineQueue.value = rawQueue ? JSON.parse(rawQueue) : [];
    } catch {
        offlineQueue.value = [];
    }
};

const saveQueue = () => {
    try {
        localStorage.setItem(QUEUE_KEY, JSON.stringify(offlineQueue.value));
    } catch {
        // Ignore storage quota errors
    }
};

const playTone = (kind: 'success' | 'warning' | 'error') => {
    if (typeof window === 'undefined') return;
    try {
        const Ctx = window.AudioContext || (window as unknown as { webkitAudioContext?: typeof AudioContext }).webkitAudioContext;
        if (!Ctx) return;
        const ctx = new Ctx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        if (kind === 'success') {
            osc.frequency.setValueAtTime(523.25, ctx.currentTime);
            osc.frequency.setValueAtTime(659.25, ctx.currentTime + 0.1);
            gain.gain.setValueAtTime(0.18, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);
            osc.start();
            osc.stop(ctx.currentTime + 0.35);
        } else if (kind === 'warning') {
            osc.frequency.setValueAtTime(349.23, ctx.currentTime);
            osc.frequency.setValueAtTime(293.66, ctx.currentTime + 0.15);
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.38);
            osc.start();
            osc.stop(ctx.currentTime + 0.38);
        } else {
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(180, ctx.currentTime);
            gain.gain.setValueAtTime(0.22, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.28);
            osc.start();
            osc.stop(ctx.currentTime + 0.28);
        }
    } catch {
        // Ignore audio errors
    }
};

const scheduleIdleReset = (ms = 3500) => {
    if (resetTimer) clearTimeout(resetTimer);
    resetTimer = setTimeout(() => {
        lastResult.value = null;
        focusRfidInput();
    }, ms);
};

const csrfToken = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

const submitScan = async (rawCode: string, method: 'rfid' | 'qr' = 'rfid') => {
    const code = rawCode.trim();
    if (!code || isProcessing.value) return;

    isProcessing.value = true;
    const eventId = `kiosk-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
    const nowIso = new Date().toISOString();

    if (!isOnline.value) {
        offlineQueue.value.push({ code, scan_method: method, client_event_id: eventId, scanned_at: nowIso });
        saveQueue();
        playTone('warning');
        lastResult.value = {
            status: 'queued',
            message: 'Jaringan terputus. Scan disimpan di antrean lokal untuk sinkronisasi otomatis.',
            scanned_at: nowIso,
        };
        scheduleIdleReset(3200);
        isProcessing.value = false;
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
                scan_method: method,
                device_id: selectedDeviceId.value,
                client_event_id: eventId,
                scanned_at: nowIso,
            }),
        });
        const data = await res.json();
        lastResult.value = data;
        if (data.stats) stats.value = data.stats;

        if (data.status === 'recorded') {
            playTone('success');
        } else if (data.status === 'duplicate') {
            playTone('warning');
        } else {
            playTone('error');
        }

        if (data.student) {
            feed.value.unshift({
                student_name: data.student.name,
                class_name: data.student.class_name,
                scan_method: method,
                scan_result: data.status,
                scanned_at: data.scanned_at,
            });
            feed.value = feed.value.slice(0, 15);
        }
        scheduleIdleReset(3500);
    } catch {
        offlineQueue.value.push({ code, scan_method: method, client_event_id: eventId, scanned_at: nowIso });
        saveQueue();
        playTone('warning');
        lastResult.value = {
            status: 'queued',
            message: 'Koneksi ke server gagal. Scan disimpan ke antrean offline.',
            scanned_at: nowIso,
        };
        scheduleIdleReset(3200);
    } finally {
        isProcessing.value = false;
        focusRfidInput();
    }
};

const handleRfidEnter = () => {
    const value = codeInput.value.trim();
    codeInput.value = '';
    if (value.length >= 2) {
        submitScan(value, 'rfid');
    }
};

const handleManualQrSubmit = () => {
    const value = manualCode.value.trim();
    if (!value) return;
    manualCode.value = '';
    qrModalOpen.value = false;
    submitScan(value, 'qr');
};

const handleQrDetected = (code: string) => {
    qrModalOpen.value = false;
    submitScan(code, 'qr');
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
            scheduleIdleReset(3000);
        }
    } catch {
        // Keep queue for next retry
    }
};

const saveGate = async () => {
    const clean = gateDraft.value.trim();
    if (clean !== '') {
        currentGate.value = clean;
        try {
            localStorage.setItem(GATE_KEY, clean);
        } catch {
            // Ignore storage errors
        }
    }
    gateModalOpen.value = false;

    try {
        const res = await fetch('/kios-presensi/gate', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
        });
        const data = await res.json();
        gateMessage.value = data.message || `Gate aktif diatur ke ${currentGate.value}.`;
        setTimeout(() => {
            gateMessage.value = '';
        }, 4000);
    } catch {
        // Keep updated gate label locally
    }
    focusRfidInput();
};

const toggleFullscreen = async () => {
    try {
        if (!document.fullscreenElement) {
            await (kioskRootRef.value ?? document.documentElement).requestFullscreen();
            isFullscreen.value = true;
        } else if (document.exitFullscreen) {
            await document.exitFullscreen();
            isFullscreen.value = false;
        }
    } catch {
        // Ignore fullscreen permission errors
    }
};

const resultBadgeMeta = computed(() => {
    const st = lastResult.value?.status;
    if (st === 'recorded') return { tone: 'success', icon: 'fa-solid fa-circle-check', label: 'HADIR TEPAT WAKTU' };
    if (st === 'duplicate' || st === 'queued') return { tone: 'warning', icon: 'fa-solid fa-circle-info', label: st === 'queued' ? 'TERSIMPAN OFFLINE' : 'SUDAH TERCATAT' };
    return { tone: 'error', icon: 'fa-solid fa-triangle-exclamation', label: 'GAGAL / TIDAK DIKENALI' };
});

const formattedResultTime = computed(() => {
    const iso = lastResult.value?.scanned_at;
    if (!iso) return `${clockHm.value} WIB`;
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return `${clockHm.value} WIB`;
    return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
});

const onOnline = () => {
    isOnline.value = true;
    syncOfflineQueue();
};
const onOffline = () => {
    isOnline.value = false;
};

onMounted(() => {
    loadLocalState();
    updateClock();
    clockInterval = setInterval(updateClock, 1000);
    focusRfidInput();
    focusInterval = setInterval(focusRfidInput, 1500);
    window.addEventListener('online', onOnline);
    window.addEventListener('offline', onOffline);
});

onBeforeUnmount(() => {
    if (resetTimer) clearTimeout(resetTimer);
    if (focusInterval) clearInterval(focusInterval);
    if (clockInterval) clearInterval(clockInterval);
    window.removeEventListener('online', onOnline);
    window.removeEventListener('offline', onOffline);
});
</script>

<template>
    <Head :title="`Kios Presensi Mandiri Siswa — ${school.name}`" />
    <OperationsLayout mode="kiosk" title="Kios Presensi">
        <div ref="kioskRootRef" class="kiosk-shell-man4">
            <header class="kiosk-topbar-man4">
                <div class="kiosk-brand-man4">
                    <img
                        :src="school.logo_url || '/madrasah-theme/images/logo-kemenag.png'"
                        :alt="`Logo ${school.name}`"
                        class="kiosk-logo-man4"
                    />
                    <div class="kiosk-brand-copy">
                        <h1>{{ school.name.toUpperCase() }}</h1>
                        <p>
                            <span class="kiosk-status-dot" aria-hidden="true"></span>
                            <span>KIOS PRESENSI SISWA</span>
                            <span class="kiosk-gate-badge">{{ currentGate }}</span>
                        </p>
                    </div>
                </div>

                <div class="kiosk-clock-man4">
                    <div class="kiosk-clock-time">
                        {{ clockHm }}<span class="sec">:{{ clockSec }}</span>
                    </div>
                    <div class="kiosk-clock-date">{{ clockDate }}</div>
                </div>

                <div class="kiosk-actions-man4">
                    <button type="button" class="btn-kiosk" title="Buka Kamera Scan QR Code" @click="qrModalOpen = true">
                        <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                        <span>QR Kamera</span>
                    </button>
                    <button type="button" class="btn-kiosk" title="Ganti Nama Gate / Pos" @click="gateDraft = currentGate; gateModalOpen = true">
                        <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                        <span>Gate</span>
                    </button>
                    <button type="button" class="btn-kiosk" title="Layar Penuh (F11)" @click="toggleFullscreen">
                        <i class="fa-solid fa-expand" aria-hidden="true"></i>
                        <span>{{ isFullscreen ? 'Normal' : 'Fullscreen' }}</span>
                    </button>
                    <Link href="/dashboard" class="btn-kiosk btn-kiosk-dash" title="Kembali ke Dashboard Admin">
                        <i class="fa-solid fa-gauge" aria-hidden="true"></i>
                        <span>Dashboard</span>
                    </Link>
                </div>
            </header>

            <main class="kiosk-main-man4" @click="focusRfidInput">
                <input
                    id="rfid-input"
                    ref="rfidInputRef"
                    v-model="codeInput"
                    type="text"
                    autocomplete="off"
                    spellcheck="false"
                    aria-label="Input kartu RFID atau barcode"
                    @keydown.enter.prevent="handleRfidEnter"
                />

                <div v-if="gateMessage || offlineQueue.length" class="kiosk-floating-alerts" @click.stop>
                    <div v-if="gateMessage" class="kiosk-alert-pill" role="status">{{ gateMessage }}</div>
                    <div v-if="offlineQueue.length" class="kiosk-alert-pill is-warn" role="status">
                        <span>{{ offlineQueue.length }} scan tersimpan offline.</span>
                        <button type="button" @click="syncOfflineQueue">Sinkronkan</button>
                    </div>
                </div>

                <div class="terminal-card">
                    <div v-if="!lastResult" class="idle-state">
                        <div class="nfc-icon-wrapper" aria-hidden="true">
                            <i class="fa-solid fa-id-card-clip"></i>
                        </div>
                        <h2 class="idle-title">Tempelkan Kartu Pelajar</h2>
                        <p class="idle-desc">Dekatkan kartu RFID atau arahkan Barcode kartu ke alat scanner untuk presensi mandiri.</p>
                        <button type="button" class="gate-badge-indicator" @click.stop="qrModalOpen = true">
                            <i class="fa-solid fa-bolt" aria-hidden="true"></i>
                            <span>Scanner Aktif &amp; Siap Menerima Tap</span>
                        </button>
                    </div>

                    <div v-else class="result-state" role="status">
                        <div class="result-badge-top" :class="resultBadgeMeta.tone">
                            <i :class="resultBadgeMeta.icon" aria-hidden="true"></i>
                            <span>{{ resultBadgeMeta.label }}</span>
                        </div>

                        <div class="student-profile-box">
                            <img src="/madrasah-theme/images/kiosk/avatar-1.png" alt="Avatar Siswa" class="student-avatar" />
                            <div class="student-info">
                                <h2>{{ lastResult.student?.name ?? 'Kartu Tidak Dikenali' }}</h2>
                                <div class="meta-tag">
                                    <span class="student-class-badge">{{ lastResult.student?.class_name ?? 'Tidak Terdaftar' }}</span>
                                </div>
                                <div class="meta-tag meta-status">
                                    <span>Status: <b>{{ resultBadgeMeta.label }}</b></span>
                                </div>
                            </div>
                        </div>

                        <div class="result-meta-row">
                            <div class="meta-col">
                                <div class="label">Waktu Presensi</div>
                                <div class="val">{{ formattedResultTime }}</div>
                            </div>
                            <div class="meta-col">
                                <div class="label">Pos / Gate</div>
                                <div class="val is-cyan">{{ currentGate }}</div>
                            </div>
                            <div class="meta-col">
                                <div class="label">Koneksi</div>
                                <div class="val is-emerald">{{ isOnline ? 'Online' : 'Offline' }}</div>
                            </div>
                        </div>

                        <p class="result-message-text">{{ lastResult.message }}</p>
                    </div>
                </div>
            </main>

            <footer class="kiosk-footer-man4">
                <div class="stats-pills">
                    <div class="stat-pill">
                        <span>Total Siswa:</span>
                        <span class="num">{{ stats.total_students.toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="stat-pill success">
                        <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                        <span>Hadir:</span>
                        <span class="num">{{ stats.present.toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="stat-pill success">
                        <span>Tepat Waktu:</span>
                        <span class="num">{{ stats.on_time.toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="stat-pill warning">
                        <i class="fa-solid fa-user-clock" aria-hidden="true"></i>
                        <span>Terlambat:</span>
                        <span class="num">{{ stats.late.toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="stat-pill info">
                        <i class="fa-solid fa-person-walking-arrow-right" aria-hidden="true"></i>
                        <span>Sudah Pulang:</span>
                        <span class="num">{{ stats.checked_out.toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="stat-pill danger">
                        <span>Belum Hadir:</span>
                        <span class="num">{{ stats.absent.toLocaleString('id-ID') }}</span>
                    </div>
                </div>

                <div class="kiosk-footer-mode">
                    <i class="fa-solid fa-keyboard" aria-hidden="true"></i>
                    <span>Mode USB Keyboard RFID / Barcode Auto-Focus</span>
                </div>
            </footer>

            <div v-if="qrModalOpen" class="kiosk-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="qr-modal-title" @click.self="qrModalOpen = false">
                <div class="modal-box">
                    <h3 id="qr-modal-title"><i class="fa-solid fa-camera" aria-hidden="true"></i> Scan QR Code Kartu</h3>
                    <p>Arahkan QR Code kartu pelajar ke depan kamera atau ketik kode kartu secara manual.</p>
                    <div class="qr-reader-wrap">
                        <QrScanPanel @detected="handleQrDetected" />
                    </div>
                    <form class="manual-scan-form" @submit.prevent="handleManualQrSubmit">
                        <input v-model="manualCode" type="text" placeholder="Ketik UID RFID / Token QR..." aria-label="Input manual kode kartu" />
                        <button type="submit" class="btn-kiosk btn-kiosk-primary">Catat</button>
                    </form>
                    <button type="button" class="btn-kiosk btn-kiosk-full" @click="qrModalOpen = false">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i> Tutup Kamera
                    </button>
                </div>
            </div>

            <div v-if="gateModalOpen" class="kiosk-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="gate-modal-title" @click.self="gateModalOpen = false">
                <div class="modal-box">
                    <h3 id="gate-modal-title"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Pengaturan Pos / Gate</h3>
                    <p>Masukkan nama Pos atau Gate untuk perangkat kios presensi ini.</p>
                    <form class="gate-form" @submit.prevent="saveGate">
                        <select v-if="devices.length" v-model="selectedDeviceId" aria-label="Pilih perangkat kios">
                            <option v-for="d in devices" :key="d.id" :value="d.id">{{ d.name }} {{ d.location ? `(${d.location})` : '' }}</option>
                        </select>
                        <input v-model="gateDraft" type="text" placeholder="Contoh: Gate Utama" aria-label="Nama Gate" />
                        <div class="gate-actions">
                            <button type="button" class="btn-kiosk" @click="gateModalOpen = false">Batal</button>
                            <button type="submit" class="btn-kiosk btn-kiosk-primary">Simpan Gate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </OperationsLayout>
</template>

<style scoped>
.kiosk-shell-man4 {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    color: #f8fafc;
    background-color: #090e17;
    background-image:
        radial-gradient(circle at 15% 15%, rgba(5, 150, 105, 0.15) 0%, transparent 40%),
        radial-gradient(circle at 85% 85%, rgba(6, 182, 212, 0.12) 0%, transparent 45%);
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
}

.kiosk-topbar-man4 {
    padding: 16px 28px;
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    z-index: 10;
}

.kiosk-brand-man4 {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}

.kiosk-logo-man4 {
    width: 48px;
    height: 48px;
    object-fit: contain;
    flex-shrink: 0;
    filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.4));
}

.kiosk-brand-copy h1 {
    margin: 0 0 3px;
    font-size: 18px;
    font-weight: 800;
    letter-spacing: -0.3px;
    color: #ffffff;
    line-height: 1.2;
}

.kiosk-brand-copy p {
    margin: 0;
    font-size: 12.5px;
    color: #10b981;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.kiosk-status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10b981;
    display: inline-block;
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.55);
    animation: kioskBlink 1.5s infinite;
}

@keyframes kioskBlink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.kiosk-gate-badge {
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 10.5px;
    font-weight: 700;
    border: 1px solid rgba(52, 211, 153, 0.3);
}

.kiosk-clock-man4 {
    text-align: right;
}

.kiosk-clock-time {
    font-family: 'JetBrains Mono', ui-monospace, monospace;
    font-size: 32px;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
    letter-spacing: -1px;
}

.kiosk-clock-time .sec {
    color: #10b981;
    font-size: 22px;
}

.kiosk-clock-date {
    font-size: 12.5px;
    color: #94a3b8;
    margin-top: 4px;
    font-weight: 500;
}

.kiosk-actions-man4 {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-kiosk {
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: #f8fafc;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    text-decoration: none;
}

.btn-kiosk:hover {
    background: rgba(255, 255, 255, 0.12);
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.2);
}

.btn-kiosk-primary {
    background: #059669;
    border-color: #10b981;
}

.btn-kiosk-primary:hover {
    background: #10b981;
}

.btn-kiosk-full {
    width: 100%;
    justify-content: center;
    margin-top: 12px;
}

.kiosk-main-man4 {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px;
    position: relative;
}

#rfid-input {
    position: absolute;
    top: -9999px;
    left: -9999px;
    opacity: 0;
    pointer-events: none;
}

.kiosk-floating-alerts {
    display: flex;
    gap: 10px;
    margin-bottom: 16px;
    flex-wrap: wrap;
    justify-content: center;
}

.kiosk-alert-pill {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 8px 16px;
    border-radius: 9999px;
    background: rgba(6, 182, 212, 0.15);
    border: 1px solid rgba(6, 182, 212, 0.35);
    color: #bae6fd;
    font-size: 12.5px;
    font-weight: 600;
}

.kiosk-alert-pill.is-warn {
    background: rgba(245, 158, 11, 0.15);
    border-color: rgba(245, 158, 11, 0.35);
    color: #fde68a;
}

.kiosk-alert-pill button {
    border: 0;
    border-radius: 9999px;
    padding: 4px 10px;
    background: #f59e0b;
    color: #090e17;
    font-size: 11px;
    font-weight: 800;
    cursor: pointer;
}

.terminal-card {
    background: rgba(15, 23, 42, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.05);
    border-radius: 28px;
    width: 100%;
    max-width: 680px;
    padding: 40px 36px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.nfc-icon-wrapper {
    width: 120px;
    height: 120px;
    margin: 0 auto 28px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.05) 70%, transparent 100%);
    border: 2px solid rgba(16, 185, 129, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    animation: kioskPulseRing 2.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
}

.nfc-icon-wrapper i {
    font-size: 48px;
    color: #10b981;
    filter: drop-shadow(0 0 15px rgba(16, 185, 129, 0.4));
}

@keyframes kioskPulseRing {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.5); transform: scale(0.98); }
    50% { box-shadow: 0 0 0 22px rgba(16, 185, 129, 0); transform: scale(1.02); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); transform: scale(0.98); }
}

.idle-title {
    margin: 0 0 10px;
    font-size: 26px;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: -0.5px;
}

.idle-desc {
    margin: 0 auto 24px;
    font-size: 15px;
    color: #94a3b8;
    line-height: 1.5;
    max-width: 460px;
}

.gate-badge-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    padding: 8px 18px;
    border-radius: 30px;
    font-size: 13px;
    font-weight: 600;
    color: #cbd5e1;
    cursor: pointer;
}

.gate-badge-indicator i {
    color: #06b6d4;
}

.result-badge-top {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 22px;
    border-radius: 30px;
    font-size: 15px;
    font-weight: 800;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 24px;
}

.result-badge-top.success {
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
    border: 1.5px solid rgba(52, 211, 153, 0.4);
    box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
}

.result-badge-top.warning {
    background: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
    border: 1.5px solid rgba(251, 191, 36, 0.4);
    box-shadow: 0 0 20px rgba(245, 158, 11, 0.4);
}

.result-badge-top.error {
    background: rgba(244, 63, 94, 0.2);
    color: #fb7185;
    border: 1.5px solid rgba(251, 113, 133, 0.4);
    box-shadow: 0 0 20px rgba(244, 63, 94, 0.4);
}

.student-profile-box {
    display: flex;
    align-items: center;
    gap: 24px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 20px;
    padding: 20px 24px;
    text-align: left;
    margin-bottom: 24px;
}

.student-avatar {
    width: 96px;
    height: 96px;
    border-radius: 18px;
    object-fit: cover;
    border: 2px solid rgba(255, 255, 255, 0.15);
    background: #1e293b;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
}

.student-info h2 {
    margin: 0 0 6px;
    font-size: 22px;
    font-weight: 800;
    color: #ffffff;
}

.meta-tag {
    font-size: 14px;
    color: #94a3b8;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.meta-status {
    margin-bottom: 0;
}

.meta-status b {
    color: #34d399;
}

.student-class-badge {
    background: rgba(6, 182, 212, 0.15);
    color: #38bdf8;
    border: 1px solid rgba(56, 189, 248, 0.3);
    padding: 3px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
}

.result-meta-row {
    display: flex;
    justify-content: space-around;
    padding: 16px;
    background: rgba(0, 0, 0, 0.25);
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.04);
    margin-bottom: 20px;
}

.meta-col .label {
    font-size: 11.5px;
    color: #94a3b8;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.meta-col .val {
    font-family: 'JetBrains Mono', ui-monospace, monospace;
    font-size: 18px;
    font-weight: 700;
    color: #ffffff;
}

.meta-col .val.is-cyan { color: #06b6d4; }
.meta-col .val.is-emerald { color: #34d399; }

.result-message-text {
    margin: 0;
    font-size: 14px;
    color: #94a3b8;
    font-style: italic;
}

.kiosk-footer-man4 {
    padding: 14px 28px;
    background: rgba(15, 23, 42, 0.95);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
    font-size: 13px;
    z-index: 10;
}

.stats-pills {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.stat-pill {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    padding: 5px 12px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #e2e8f0;
}

.stat-pill .num {
    font-weight: 800;
    color: #ffffff;
    font-family: 'JetBrains Mono', ui-monospace, monospace;
}

.stat-pill.success .num,
.stat-pill.success i { color: #34d399; }
.stat-pill.warning .num,
.stat-pill.warning i { color: #fbbf24; }
.stat-pill.info .num,
.stat-pill.info i { color: #38bdf8; }
.stat-pill.danger .num { color: #f87171; }

.kiosk-footer-mode {
    color: #94a3b8;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.kiosk-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(8px);
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-box {
    background: #0f172a;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    width: 100%;
    max-width: 440px;
    padding: 24px;
    text-align: center;
    color: #f8fafc;
}

.modal-box h3 {
    margin: 0 0 6px;
    font-size: 18px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.modal-box h3 i {
    color: #38bdf8;
}

.modal-box p {
    margin: 0 0 14px;
    font-size: 13px;
    color: #94a3b8;
}

.qr-reader-wrap {
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 12px;
    background: #020617;
}

.manual-scan-form,
.gate-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.manual-scan-form {
    flex-direction: row;
}

.manual-scan-form input,
.gate-form input,
.gate-form select {
    flex: 1;
    min-width: 0;
    padding: 9px 12px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    background: #1e293b;
    color: #ffffff;
    font-size: 13px;
}

.gate-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

@media (max-width: 767px) {
    .kiosk-topbar-man4 { padding: 14px 16px; }
    .terminal-card { padding: 28px 20px; }
    .student-profile-box { flex-direction: column; text-align: center; }
    .kiosk-footer-man4 { padding: 14px 16px; }
}
</style>
