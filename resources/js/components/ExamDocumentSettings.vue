<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
const settings = defineModel<any>({ required: true });
const fields = { mapel: 'Mata pelajaran', kelas: 'Kelas / rombel', semester: 'Semester', tahunPelajaran: 'Tahun pelajaran', tanggal: 'Tanggal', waktu: 'Waktu' };
const kopFields = { namaLembaga: 'Nama sekolah / madrasah', yayasan: 'Yayasan', keterangan: 'Keterangan / akreditasi', alamat: 'Alamat', judul: 'Judul', subjudul: 'Subjudul', petunjuk: 'Petunjuk umum' };
async function upload(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    if (!['image/png', 'image/jpeg'].includes(file.type) || file.size > 1.5 * 1024 * 1024) {
        window.alert('Gunakan gambar PNG/JPG maksimal 1,5 MB.'); input.value = ''; return;
    }
    const reader = new FileReader();
    reader.onload = () => {
        const image = new Image();
        image.onload = () => {
            const ratio = image.width / image.height;
            if (ratio < 2 || ratio > 12) { window.alert('Rasio kop harus antara 2:1 dan 12:1.'); input.value = ''; return; }
            settings.value.kop.gambar = String(reader.result);
            settings.value.kop.rasioGambar = ratio;
        };
        image.src = String(reader.result);
    };
    reader.readAsDataURL(file);
}
</script>
<template>
    <fieldset class="document-fields">
        <legend>Identitas naskah dan kertas</legend>
        <label v-for="(label, key) in fields" :key="key">{{ label }}<input v-model="settings[key]" :required="key === 'mapel' || key === 'kelas'" :type="key === 'tanggal' ? 'date' : 'text'" :maxlength="key === 'mapel' || key === 'kelas' ? 120 : 40" /></label>
        <label>Kertas<select v-model="settings.paper.ukuran" aria-label="Kertas"><option>A4</option><option>F4</option></select></label>
        <label>Orientasi<select v-model="settings.paper.orientasi" aria-label="Orientasi"><option value="portrait">Portrait</option><option value="landscape">Landscape</option></select></label>
        <label>Kolom<select v-model.number="settings.paper.columns" aria-label="Kolom"><option :value="1">1 kolom</option><option :value="2">2 kolom</option><option :value="3">3 kolom</option></select></label>
    </fieldset>
    <details class="document-kop">
        <summary>Kop dan petunjuk naskah</summary>
        <div class="document-fields">
            <label v-for="(label, key) in kopFields" :key="key">{{ label }}<textarea v-model="settings.kop[key]" :maxlength="key === 'petunjuk' ? 1000 : key === 'alamat' ? 240 : key === 'keterangan' ? 300 : 160" rows="2" /></label>
            <label>Gambar kop (PNG/JPG, maksimal 1,5 MB)<input type="file" accept="image/png,image/jpeg" @change="upload" /></label>
            <div v-if="settings.kop.gambar"><img :src="settings.kop.gambar" alt="Kop terpilih" style="max-width:100%;max-height:100px" /><AppButton variant="danger" type="button" @click="settings.kop.gambar = ''">Hapus gambar kop</AppButton></div>
        </div>
    </details>
</template>
<style scoped>
.document-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.85rem;border:1px solid var(--line);border-radius:.7rem;padding:1rem;margin:1rem 0}.document-fields legend,.document-kop summary{font-weight:var(--weight-semibold)}.document-fields label{display:grid;gap:.4rem;font-size:var(--type-sm)}.document-fields input,.document-fields select,.document-fields textarea{width:100%;padding:.6rem;border:1px solid var(--line);border-radius:.45rem;background:var(--surface);color:var(--ink)}@media(max-width:700px){.document-fields{grid-template-columns:1fr}}
</style>
