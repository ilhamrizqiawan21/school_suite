<script setup lang="ts">
import QrScanPanel from '@/components/QrScanPanel.vue';

defineProps<{ title: string; description: string; busy?: boolean }>();
const code = defineModel<string>({ required: true });
const emit = defineEmits<{ lookup: [] }>();

function acceptCode(value: string) {
    code.value = value;
    emit('lookup');
}
</script>

<template>
    <a href="/" class="ops-back-circle" aria-label="Kembali ke beranda">←</a>
    <section class="operations-scanner" aria-labelledby="operations-scanner-title">
        <header>
            <h1 id="operations-scanner-title">{{ title }}</h1>
            <p>{{ description }}</p>
        </header>
        <div class="operations-scanner-body">
            <QrScanPanel @detected="acceptCode" />
            <p class="scanner-helper-text">Arahkan kamera ke <strong>QR Code / Barcode</strong> Kartu Pelajar</p>
            <p class="scanner-manual-label">Atau cari manual</p>
            <form @submit.prevent="emit('lookup')">
                <label class="sr-only" for="operations-scan-code">Kode kartu, nama, atau NIS siswa</label>
                <input id="operations-scan-code" v-model="code" placeholder="Masukkan 10 Digit NISN Siswa" autocomplete="off" />
                <button type="submit" :disabled="busy || !code.trim()">{{ busy ? 'Mencari...' : 'Cari Data Siswa' }}</button>
            </form>
            <footer><slot /></footer>
        </div>
    </section>
</template>

<style scoped>
.ops-back-circle { position: fixed; top: 18px; left: 18px; z-index: 20; width: 40px; height: 40px; border-radius: 9999px; background: #fff; color: #334155; border: 1px solid #e2e8f0; display: grid; place-items: center; font-weight: 800; text-decoration: none; box-shadow: 0 4px 12px rgb(15 23 42 / 0.08); }
.operations-scanner { width: min(380px,calc(100% - 32px)); margin: auto; border-radius: 16px; overflow: hidden; background: #fff; box-shadow: 0 12px 32px #0f172a15; }
.operations-scanner > header { padding: 18px 16px; text-align: center; color: #fff; background: linear-gradient(120deg,#065f46,#059669); }
.operations-scanner h1 { margin: 0; color: #fff; font-size: 18px; text-transform: uppercase; font-weight: 800; }
.operations-scanner header p { margin: 5px 0 0; color: #d1fae5; font-size: 12px; }
.operations-scanner-body { padding: 16px; }
.operations-scanner-body :deep(.qr-scan-panel) { justify-items: stretch; }
.operations-scanner-body :deep(.qr-viewfinder) { aspect-ratio: 1.65; min-height: 0; background: #f8fafc; border: 2px dashed #cbd5e1; color: #64748b; }
.operations-scanner-body :deep(.qr-frame) { border: 0; }
.operations-scanner-body :deep(.qr-instruction) { background: transparent; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.operations-scanner-body :deep(.ui-button) { width: 100%; background: #059669; color: #fff; border-color: #059669; font-weight: 800; text-transform: uppercase; }
.scanner-helper-text { margin: 10px 0 0; text-align: center; font-size: 11px; color: #64748b; }
.scanner-manual-label { margin: 16px 0 10px; text-align: center; color: #94a3b8; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
.operations-scanner form { display: grid; gap: 8px; }
.operations-scanner input { min-width: 0; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 7px; text-align: center; font-size: 13px; }
.operations-scanner form button { min-height: 40px; background: #1e40af; border: 0; border-radius: 6px; color: #fff; font-size: 12px; font-weight: 800; text-transform: uppercase; cursor: pointer; }
.operations-scanner button:disabled { opacity: .65; }
.operations-scanner footer { display: flex; justify-content: space-between; margin-top: 16px; color: #475569; font-size: 11px; }
</style>
