<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import { nextTick, onBeforeUnmount, ref } from 'vue';

type Detector = { detect: (video: HTMLVideoElement) => Promise<{ rawValue: string }[]> };
const emit = defineEmits<{ detected: [value: string] }>();
const video = ref<HTMLVideoElement>();
const active = ref(false);
const error = ref('');
let stream: MediaStream | undefined;
let timer: number | undefined;
let session = 0;

function stop() {
    session++;
    active.value = false;
    window.clearTimeout(timer);
    stream?.getTracks().forEach((track) => track.stop());
    stream = undefined;
}

async function start() {
    error.value = '';
    const BrowserDetector = (window as Window & { BarcodeDetector?: new (options: { formats: string[] }) => Detector }).BarcodeDetector;
    if (!BrowserDetector || !navigator.mediaDevices?.getUserMedia) {
        error.value = 'Pemindaian kamera tidak tersedia di peramban ini. Masukkan kode secara manual.';
        return;
    }

    const current = ++session;
    active.value = true;
    try {
        const detector = new BrowserDetector({ formats: ['qr_code'] });
        const acquired = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        if (current !== session) {
            acquired.getTracks().forEach((track) => track.stop());
            return;
        }

        stream = acquired;
        await nextTick();
        const preview = video.value;
        if (current !== session || !preview) return;
        preview.srcObject = acquired;
        await preview.play();

        const scan = async () => {
            if (current !== session) return;

            const codes = await detector.detect(preview).catch(() => []);
            if (current !== session) return;

            const value = codes[0]?.rawValue?.trim();
            if (value) {
                stop();
                emit('detected', value);
                return;
            }

            timer = window.setTimeout(scan, 350);
        };

        void scan();
    } catch {
        if (current !== session) return;
        stop();
        error.value = 'Kamera tidak dapat dibuka. Periksa izin kamera atau masukkan kode secara manual.';
    }
}

onBeforeUnmount(stop);
</script>

<template>
    <div class="qr-scan-panel">
        <div class="qr-viewfinder">
            <video v-if="active" ref="video" muted playsinline aria-label="Pratinjau kamera pemindai QR" />
            <span class="qr-frame" aria-hidden="true"></span>
            <span v-if="!active" class="qr-instruction">Arahkan kode QR ke dalam bingkai</span>
        </div>
        <AppButton v-if="active" type="button" variant="secondary" @click="stop">Tutup Kamera</AppButton>
        <AppButton v-else type="button" variant="secondary" @click="start">Mulai Scan Kamera</AppButton>
        <p v-if="error" role="alert">{{ error }}</p>
    </div>
</template>

<style scoped>
.qr-scan-panel { display: grid; justify-items: start; gap: .65rem; }
.qr-viewfinder {
    position: relative;
    display: grid;
    place-items: center;
    width: min(100%, 28rem);
    min-height: 10rem;
    aspect-ratio: 16 / 9;
    overflow: hidden;
    border-radius: .75rem;
    background: #0f172a;
    color: #ecfdf5;
}
video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.qr-frame { position: absolute; width: min(45%, 10rem); height: 65%; border: 2px solid #6ee7b7; border-radius: .6rem; }
.qr-instruction { z-index: 1; padding: .2rem .45rem; background: #0f172a; font-size: .8rem; text-align: center; }
.qr-scan-panel p { margin: 0; color: #9f1239; font-size: .875rem; }
</style>
