<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { computed, ref, watch } from 'vue';

defineOptions({ name: 'PublicContentFields' });
const model = defineModel<Record<string, any>>({ required: true });
const props = withDefaults(defineProps<{ path?: string; labels?: Record<string, string>; media?: { label: string; url: string }[]; query?: string; templates?: Record<string, any>; icons?: string[] }>(), { path: '', labels: () => ({}), media: () => [], templates: () => ({}), icons: () => [] });
const searchTerm = ref('');
const openGroups = ref<Record<string, boolean>>({});
const effectiveQuery = computed(() => props.query ?? searchTerm.value);
const toggleGroup = (key: string, event: Event) => { openGroups.value[key] = (event.target as HTMLDetailsElement).open; };
const pendingDelete = ref<{ key: string; index: number } | null>(null);
const names: Record<string, string> = {
    visible: 'Tampilkan bagian ini', title: 'Judul', subtitle: 'Subjudul', description: 'Deskripsi', text: 'Teks', label: 'Label',
    badge: 'Lencana', badge_primary: 'Lencana utama', badge_secondary: 'Lencana tambahan', headline_prefix: 'Awal judul',
    headline_highlight: 'Sorotan judul', headline_suffix: 'Akhir judul', highlight: 'Sorotan', lead: 'Pengantar',
    tagline: 'Slogan', copyright_notice: 'Keterangan hak cipta', greeting: 'Salam pembuka', reception_hours: 'Jam resepsionis',
    academic_year_label: 'Label tahun pelajaran', about_title: 'Judul pengantar', about_description: 'Isi pengantar',
    cta_ppdb_label: 'Tombol pendaftaran', cta_ptsp_label: 'Tombol PTSP', cta_lapor_label: 'Tombol pengaduan',
    cta_lacak_label: 'Tombol pelacakan', ptsp_ticker_text: 'Pengumuman layanan berjalan',
    cta_ppdb_icon: 'Ikon pendaftaran', cta_ptsp_icon: 'Ikon PTSP', cta_lapor_icon: 'Ikon pengaduan', cta_lacak_icon: 'Ikon pelacakan',
    hide_demo_fallbacks: 'Sembunyikan konten demo saat data kosong', items: 'Daftar isi', url: 'Tautan',
    image: 'Gambar', image_url: 'Tautan gambar', alt: 'Deskripsi gambar', caption: 'Keterangan gambar',
    name: 'Nama', icon: 'Ikon', sort_order: 'Urutan tampil', link: 'Tautan tujuan', date: 'Tanggal',
    home: 'Beranda', profile: 'Profil sekolah', shell: 'Header dan footer', hero: 'Bagian pembuka', welcome: 'Sambutan',
    zi: 'Zona integritas', services: 'Layanan', programs: 'Program', facilities: 'Fasilitas', news: 'Berita',
    achievements: 'Prestasi', gallery: 'Galeri', ppdb: 'Pendaftaran', contact: 'Kontak', values: 'Nilai budaya',
    pillars: 'Pilar keunggulan', options: 'Pilihan tampilan', footer: 'Footer', ptsp: 'PTSP', external_letter: 'Surat dinas',
    guestbook: 'Buku tamu', student_permit: 'Izin siswa', mutation: 'Mutasi', graduation: 'Kelulusan',
    legalization: 'Legalisir', complaint: 'Pengaduan', alumni: 'Alumni', admissions: 'SPMB', tracking: 'Pelacakan',
    library: 'Perpustakaan', school: 'Halaman sekolah', student_life: 'Kehidupan siswa', notice: 'Pengumuman',
    message: 'Pesan', variant: 'Tampilan', dismissible: 'Dapat ditutup', starts_at: 'Mulai tayang', ends_at: 'Berakhir tayang',
    link_label: 'Label tautan', link_url: 'Tautan tujuan', show_student_names: 'Tampilkan nama siswa',
};
const labelFor = (key: string) => props.labels[`${props.path}.${key}`] || props.labels[key] || names[key] || key.split(/[._]/).map((part) => names[part] || part).join(' — ');
const filteredModel = computed(() => {
    const query = effectiveQuery.value.trim().toLocaleLowerCase('id-ID');
    if (!query) return model.value;
    return Object.fromEntries(Object.entries(model.value).filter(([key, value]) => `${labelFor(key)} ${JSON.stringify(value)}`.toLocaleLowerCase('id-ID').includes(query)));
});
const isObject = (value: unknown) => typeof value === 'object' && value !== null && !Array.isArray(value);
const addItem = (key: string) => {
    const list = model.value[key] as any[];
    const sample = list[0] ?? props.templates[key]?.[0];
    if (isObject(sample)) {
        list.push(Object.fromEntries(Object.entries(sample).map(([field, value]) => [field, field === 'id' ? crypto.randomUUID() : typeof value === 'boolean' ? true : typeof value === 'number' ? 0 : Array.isArray(value) ? [] : ''])));
    } else list.push('');
};
const moveItem = (key: string, index: number, direction: number) => {
    const list = model.value[key] as unknown[];
    const next = index + direction;
    if (next < 0 || next >= list.length) return;
    [list[index], list[next]] = [list[next], list[index]];
};
const removeItem = () => {
    if (pendingDelete.value) model.value[pendingDelete.value.key].splice(pendingDelete.value.index, 1);
    pendingDelete.value = null;
};
const inputType = (key: string) => /(?:^|_)(?:date|publish_at|starts_at|ends_at)$/.test(key) ? 'date'
    : /(?:^|_)(?:color)$/.test(key) ? 'color' : 'text';
const isMedia = (key: string) => /^(src_|image|photo|poster|og_image)/.test(key);
const fieldLimit = (key: string) => key === 'title' && props.path.startsWith('seo.') ? 160 : key === 'description' && props.path.startsWith('seo.') ? 320 : 5000;
const sectionDefinitions = [
    { id: 'home', label: 'Beranda publik', description: 'Judul pembuka, sambutan, layanan unggulan, dan pengumuman.', matches: (key: string) => key.startsWith('home.') },
    { id: 'profile', label: 'Profil sekolah', description: 'Nilai, program, fasilitas, dan kehidupan siswa.', matches: (key: string) => key.startsWith('profile.') || key.startsWith('school.') },
    { id: 'services', label: 'Layanan publik', description: 'Teks pengantar dan instruksi setiap layanan sekolah.', matches: (key: string) => /^(ptsp|external_letter|guestbook|student_permit|mutation|graduation|legalization|complaint|admissions|tracking|library)\./.test(key) },
    { id: 'community', label: 'Publikasi & komunitas', description: 'Berita, galeri, prestasi, alumni, dan halaman informasi.', matches: (key: string) => /^(news|gallery|achievements|alumni|organization|integrity|downloads|verification|prayer)\./.test(key) },
    { id: 'shell', label: 'Navigasi & footer', description: 'Pesan singkat yang tampil berulang di seluruh portal.', matches: (key: string) => key.startsWith('shell.') },
] as const;
const rootEntries = computed(() => Object.entries(model.value).filter(([key]) => key !== 'id' && !key.startsWith('seo.')));
const contentSections = computed(() => sectionDefinitions.map((section) => ({ ...section, entries: rootEntries.value.filter(([key]) => section.matches(key)) })).filter((section) => section.entries.length));
const activeSectionId = ref(contentSections.value[0]?.id ?? 'home');
watch(contentSections, (sections) => {
    if (!sections.some((section) => section.id === activeSectionId.value)) activeSectionId.value = sections[0]?.id ?? 'home';
}, { immediate: true });
watch(searchTerm, (query) => {
    const normalized = query.trim().toLocaleLowerCase('id-ID');
    if (!normalized) return;
    const match = contentSections.value.find((section) => section.entries.some(([key]) => `${labelFor(key)} ${JSON.stringify(model.value[key])}`.toLocaleLowerCase('id-ID').includes(normalized)));
    if (match) activeSectionId.value = match.id;
});
const activeSection = computed(() => contentSections.value.find((section) => section.id === activeSectionId.value) ?? contentSections.value[0]);
</script>

<template>
    <div class="public-content-fields">
        <template v-if="!path">
            <div class="content-editor-intro">
                <div><strong>Atur isi portal berdasarkan tujuan</strong><span>Pilih area yang ingin diperbarui. Pengaturan teknis dan metadata tidak ditampilkan di sini.</span></div>
                <label class="content-search"><span>Cari isi portal</span><input v-model="searchTerm" type="search" placeholder="Cari judul, layanan, atau teks" /></label>
            </div>
            <div class="content-section-tabs" role="tablist" aria-label="Area konten portal">
                <button v-for="section in contentSections" :key="section.id" type="button" role="tab" :aria-selected="activeSection?.id === section.id" :class="{ 'is-active': activeSection?.id === section.id }" @click="activeSectionId = section.id">
                    <strong>{{ section.label }}</strong><small>{{ section.entries.length }} bagian</small>
                </button>
            </div>
            <section v-if="activeSection" class="content-section-panel" :aria-labelledby="`content-section-${activeSection.id}`">
                <header><div><h3 :id="`content-section-${activeSection.id}`">{{ activeSection.label }}</h3><p>{{ activeSection.description }}</p></div><span class="content-section-count">{{ activeSection.entries.length }} bagian</span></header>
                <div class="content-section-list">
                    <details v-for="([key, value]) in activeSection.entries.filter(([entryKey]) => !effectiveQuery || `${labelFor(entryKey)} ${JSON.stringify(model[entryKey])}`.toLocaleLowerCase('id-ID').includes(effectiveQuery.toLocaleLowerCase('id-ID')))" :key="key" class="content-group" open>
                        <summary>{{ labelFor(key) }}<small>Ubah teks dan pilihan yang tampil di bagian ini</small></summary>
                        <PublicContentFields v-model="model[key]" :path="key" :labels="labels" :media="media" :templates="templates[key]" :icons="icons" />
                    </details>
                </div>
                <p v-if="!activeSection.entries.some(([entryKey]) => !effectiveQuery || `${labelFor(entryKey)} ${JSON.stringify(model[entryKey])}`.toLocaleLowerCase('id-ID').includes(effectiveQuery.toLocaleLowerCase('id-ID')))" class="content-empty">Tidak ada bagian yang cocok dengan pencarian.</p>
            </section>
        </template>
        <template v-else>
        <template v-for="(value, key) in filteredModel" :key="key">
            <template v-if="key !== 'id'">
                <details v-if="isObject(value)" class="content-group" :open="Boolean(effectiveQuery)" @toggle="toggleGroup(String(key), $event)">
                    <summary>{{ labelFor(String(key)) }}</summary>
                    <PublicContentFields v-if="effectiveQuery || openGroups[String(key)]" :query="labelFor(String(key)).toLowerCase().includes(effectiveQuery.toLowerCase()) ? '' : effectiveQuery" v-model="model[key]" :path="path ? `${path}.${key}` : String(key)" :labels="labels" :media="media" :templates="templates[key]" :icons="icons" />
                </details>
                <section v-else-if="Array.isArray(value)" class="content-list">
                    <h3>{{ labelFor(String(key)) }}</h3>
                    <article v-for="(_, index) in value" :key="value[index]?.id || index" class="content-list-item">
                        <PublicContentFields v-if="isObject(value[index])" v-model="model[key][index]" :path="`${path}.${key}`" :labels="labels" :media="media" :templates="templates[key]?.[0]" :icons="icons" />
                        <label v-else><span>{{ labelFor(String(key)) }} {{ index + 1 }}</span><input v-model="model[key][index]" maxlength="1000" /></label>
                        <div class="content-item-actions">
                            <AppButton type="button" variant="ghost" size="sm" :disabled="index === 0" @click="moveItem(String(key), index, -1)" aria-label="Pindah ke atas">↑</AppButton>
                            <AppButton type="button" variant="ghost" size="sm" :disabled="index === value.length - 1" @click="moveItem(String(key), index, 1)" aria-label="Pindah ke bawah">↓</AppButton>
                            <AppButton type="button" variant="danger" size="sm" @click="pendingDelete = { key: String(key), index }">Hapus</AppButton>
                        </div>
                    </article>
                    <AppButton type="button" variant="secondary" size="sm" :disabled="value.length >= 50" @click="addItem(String(key))">Tambah {{ labelFor(String(key)) }}</AppButton>
                </section>
                <label v-else-if="typeof value === 'boolean'" class="content-checkbox"><input v-model="model[key]" type="checkbox" /><span>{{ labelFor(String(key)) }}</span></label>
                <label v-else>
                    <span>{{ labelFor(String(key)) }}</span>
                    <select v-if="String(key) === 'icon' || String(key).endsWith('_icon')" v-model="model[key]"><option value="">Tanpa ikon</option><option v-for="icon in icons" :key="icon" :value="icon">{{ icon.replace('fa-solid fa-', '').replaceAll('-', ' ') }}</option></select>
                    <input v-else-if="typeof value === 'number'" v-model.number="model[key]" type="number" min="0" max="1000000" :step="Number.isInteger(value) ? 1 : 'any'" />
                    <textarea v-else-if="/description|text|lead|message|tagline|content/.test(String(key))" v-model="model[key]" rows="3" :maxlength="fieldLimit(String(key))"></textarea>
                    <template v-else>
                        <input v-model="model[key]" :type="inputType(String(key))" :maxlength="fieldLimit(String(key))" />
                        <img v-if="value && isMedia(String(key))" :src="String(value)" alt="Pratinjau gambar terpilih" class="content-media-preview" />
                        <select v-if="media.length && isMedia(String(key))" v-model="model[key]" aria-label="Pilih gambar dari galeri terbit"><option :value="value">Gunakan gambar saat ini</option><option v-for="asset in media" :key="asset.url" :value="asset.url">{{ asset.label }}</option></select>
                    </template>
                </label>
            </template>
        </template>
        </template>
        <ConfirmDialog :open="pendingDelete !== null" title="Hapus item konten?" message="Item akan dihapus dari draf. Terbitkan untuk menerapkan perubahan ke publik." confirm-label="Hapus item" @confirm="removeItem" @cancel="pendingDelete = null" />
    </div>
</template>

<style scoped>
.public-content-fields { display: grid; gap: 1rem; min-width: 0; }
.content-editor-intro { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1rem; border: 1px solid color-mix(in srgb, var(--primary) 22%, var(--line)); border-radius: 10px; background: color-mix(in srgb, var(--primary-soft) 42%, var(--surface)); }
.content-editor-intro > div { display: grid; gap: .25rem; }
.content-editor-intro strong { color: var(--ink); font-size: .95rem; }
.content-editor-intro span { color: var(--ink-soft); font-size: .8rem; line-height: 1.5; }
.content-search { width: min(340px, 100%); }
.content-search span { color: var(--ink-soft); font-size: .75rem; font-weight: 600; }
.content-section-tabs { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .6rem; }
.content-section-tabs button { display: grid; gap: .25rem; min-height: 70px; padding: .75rem; color: var(--ink-soft); background: var(--surface); border: 1px solid var(--line); border-radius: 8px; text-align: left; cursor: pointer; }
.content-section-tabs button:hover, .content-section-tabs button:focus-visible { border-color: color-mix(in srgb, var(--primary) 45%, var(--line)); }
.content-section-tabs button.is-active { color: var(--primary-strong); background: var(--primary-soft); border-color: var(--primary); box-shadow: inset 3px 0 0 var(--primary); }
.content-section-tabs small { color: inherit; font-size: .72rem; }
.content-section-panel { display: grid; gap: 1rem; padding: 1rem; border: 1px solid var(--line); border-radius: 10px; background: var(--surface-subtle); }
.content-section-panel > header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding-bottom: .75rem; border-bottom: 1px solid var(--line); }
.content-section-panel h3 { margin: 0; color: var(--ink); font-size: 1rem; }
.content-section-panel header p { margin: .25rem 0 0; color: var(--ink-soft); font-size: .8rem; line-height: 1.5; }
.content-section-count { flex: 0 0 auto; padding: .3rem .55rem; color: var(--primary-strong); background: var(--primary-soft); border-radius: 999px; font-size: .72rem; font-weight: 700; }
.content-section-list { display: grid; gap: .65rem; }
.content-section-list > .content-group { background: var(--surface); }
.content-section-list > .content-group > summary { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.content-section-list > .content-group > summary small { color: var(--ink-soft); font-size: .72rem; font-weight: 400; }
.content-empty { margin: 0; padding: 1rem; color: var(--ink-soft); font-size: .85rem; text-align: center; }
.content-group, .content-list, .content-list-item { padding: 1rem; border: 1px solid var(--line); border-radius: 8px; background: var(--surface); }
.content-group > summary { cursor: pointer; font-weight: 600; }
.content-group[open] > summary { margin-bottom: 1rem; }
label { display: grid; gap: .4rem; font-size: .875rem; color: var(--text); }
input:not([type="checkbox"]), textarea { width: 100%; min-width: 0; padding: .65rem .75rem; border: 1px solid var(--line); border-radius: 6px; background: var(--surface); color: var(--text); }
.content-checkbox { display: flex; align-items: center; gap: .6rem; }
.content-list { display: grid; gap: 1rem; }
.content-item-actions { display: flex; gap: .5rem; justify-content: flex-end; margin-top: .75rem; }
.content-media-preview { max-width: 240px; max-height: 160px; object-fit: contain; }
@media (max-width: 760px) {
    .content-editor-intro { align-items: stretch; flex-direction: column; }
    .content-search { width: 100%; }
    .content-section-tabs { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .content-section-panel > header, .content-section-list > .content-group > summary { align-items: flex-start; flex-direction: column; }
}
</style>
