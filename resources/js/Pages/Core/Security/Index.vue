<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
interface Block { id: number; ip_address: string; reason: string; expires_at?: string | null; is_active: boolean }
interface Login { id: number; status: string; reason?: string | null; ip_address?: string | null; created_at: string; user?: { name: string } | null }
interface ErrorEvent { id: number; exception_class: string; request_method?: string | null; request_path?: string | null; occurrences: number; last_seen_at: string; resolved_at?: string | null }
interface Audit { id: number; event_type: string; target_type?: string | null; target_id?: string | null; actor_name?: string | null; created_at: string }
type Tab = 'ips' | 'login' | 'errors' | 'audit';
interface Pages { current: number; last: number; total: number; previous: string | null; next: string | null }
const props = defineProps<{ blocked_ips: Block[]; login_logs: Login[]; errors: ErrorEvent[]; audit_events: Audit[]; can_manage: boolean; pagination: Record<Tab, Pages | null> }>();
const tab = ref<Tab>('ips');
const show = ref(false);
const pending = ref(false);
const actionError = ref('');
const form = useForm({ ip_address: '', reason: '', expires_at: '' });
const tabs = computed(() => [ { key: 'ips' as Tab, label: 'Blokir IP' }, { key: 'login' as Tab, label: 'Riwayat login' }, { key: 'errors' as Tab, label: 'Error aplikasi' }, ...(props.can_manage ? [{ key: 'audit' as Tab, label: 'Audit aktivitas' }] : []) ]);
const pages = computed(() => props.pagination[tab.value]);
const help = computed(() => ({ ips: 'Periksa alamat dan masa berlaku sebelum memblokir. Blokir IP berlaku pada akses aplikasi dari jaringan tersebut.', login: 'Tinjau percobaan masuk untuk mengenali aktivitas yang tidak biasa.', errors: 'Tandai selesai setelah penyebab error sudah ditangani. Tindakan ini hanya memperbarui catatan.', audit: 'Riwayat perubahan membantu menelusuri siapa yang melakukan tindakan dan kapan.' }[tab.value]));
const dt = (value: string) => new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
const expired = (item: Block) => Boolean(item.expires_at && new Date(item.expires_at).getTime() <= Date.now());
const active = (item: Block) => item.is_active && !expired(item);
function openBlock() { form.reset(); form.clearErrors(); show.value = true; }
function block() { form.post('/pengaturan/keamanan/ip', { preserveScroll: true, onSuccess: () => { form.reset(); show.value = false; } }); }
function act(url: string, method: 'post' | 'delete', confirmation: string) {
    if (pending.value || !window.confirm(confirmation)) return;
    pending.value = true;
    actionError.value = '';
    const options = {
        preserveScroll: true,
        onError: (errors: Record<string, string>) => { actionError.value = Object.values(errors)[0] ?? 'Tindakan gagal disimpan.'; },
        onFinish: () => { pending.value = false; },
    };
    if (method === 'post') router.post(url, {}, options);
    else router.delete(url, options);
}
function go(url: string | null) { if (url) router.get(url, {}, { preserveState: true, preserveScroll: true }); }
</script>

<template>
    <AppLayout title="Keamanan">
        <Head title="Keamanan & Audit" />
        <main class="view space-y-6 security-page">
            <section class="hero-card security-hero"><div><p class="eyebrow">Pengaturan sekolah</p><h1>Keamanan & audit</h1><p>Tinjau akses akun, blokir jaringan, dan riwayat aktivitas sekolah.</p></div><div class="actions"><Link href="/ganti-password" class="secondary-button">Ganti kata sandi saya</Link><button v-if="can_manage" class="primary-button" @click="openBlock">Blokir IP</button></div></section>
            <section class="panel security-panel">
                <nav class="tabs" aria-label="Kategori keamanan"><button v-for="item in tabs" :key="item.key" :class="{ active: tab === item.key }" :aria-current="tab === item.key ? 'page' : undefined" @click="tab = item.key">{{ item.label }}</button></nav>
                <p class="help">{{ help }} <span v-if="!can_manage">Anda memiliki akses untuk melihat catatan.</span></p>
                <p v-if="actionError" class="error help" role="alert">{{ actionError }}</p>
                <p class="scroll-help">Geser tabel ke samping pada layar kecil untuk melihat semua kolom.</p>
                <div class="table-scroll" tabindex="0" :aria-label="`Tabel ${tabs.find(item => item.key === tab)?.label}`">
                    <table v-if="tab === 'ips'"><thead><tr><th>Alamat IP</th><th>Alasan</th><th>Berlaku sampai</th><th>Status</th><th>Aksi</th></tr></thead><tbody><tr v-for="item in blocked_ips" :key="item.id"><td><code>{{ item.ip_address }}</code></td><td>{{ item.reason }}</td><td>{{ item.expires_at ? dt(item.expires_at) : 'Tanpa batas waktu' }}</td><td><span class="badge" :class="active(item) ? 'danger' : 'muted'">{{ !item.is_active ? 'Nonaktif' : expired(item) ? 'Kedaluwarsa' : 'Aktif' }}</span></td><td><button v-if="can_manage && active(item)" class="text-button" :disabled="pending" @click="act(`/pengaturan/keamanan/ip/${item.id}`, 'delete', `Buka blokir alamat ${item.ip_address}?`)">Buka blokir</button><span v-else>—</span></td></tr><tr v-if="!blocked_ips.length"><td colspan="5" class="empty-state">Belum ada catatan blokir IP.</td></tr></tbody></table>
                    <table v-else-if="tab === 'login'"><thead><tr><th>Waktu</th><th>Pengguna</th><th>IP</th><th>Status</th><th>Keterangan</th></tr></thead><tbody><tr v-for="item in login_logs" :key="item.id"><td>{{ dt(item.created_at) }}</td><td>{{ item.user?.name || 'Tidak dikenal' }}</td><td><code>{{ item.ip_address || '—' }}</code></td><td><span class="badge" :class="item.status === 'success' ? 'success' : 'danger'">{{ item.status === 'success' ? 'Berhasil' : item.status === 'failed' ? 'Gagal' : item.status }}</span></td><td>{{ item.reason || (item.status === 'success' ? 'Login berhasil' : 'Tidak ada keterangan') }}</td></tr><tr v-if="!login_logs.length"><td colspan="5" class="empty-state">Belum ada riwayat login.</td></tr></tbody></table>
                    <table v-else-if="tab === 'errors'"><thead><tr><th>Terakhir terjadi</th><th>Jenis error</th><th>Halaman</th><th>Kejadian</th><th>Status / aksi</th></tr></thead><tbody><tr v-for="item in errors" :key="item.id"><td>{{ dt(item.last_seen_at) }}</td><td><code>{{ item.exception_class.split('\\').pop() }}</code></td><td>{{ item.request_method }} /{{ item.request_path?.replace(/^\//, '') }}</td><td>{{ item.occurrences }}</td><td><span v-if="item.resolved_at" class="badge success">Selesai</span><button v-else-if="can_manage" class="text-button" :disabled="pending" @click="act(`/pengaturan/keamanan/error/${item.id}/selesai`, 'post', 'Penyebab error sudah ditangani? Tandai catatan ini selesai?')">Tandai selesai</button><span v-else class="badge danger">Belum selesai</span></td></tr><tr v-if="!errors.length"><td colspan="5" class="empty-state">Belum ada error aplikasi yang tercatat.</td></tr></tbody></table>
                    <table v-else><thead><tr><th>Waktu</th><th>Aktivitas</th><th>Pelaku</th><th>Target</th></tr></thead><tbody><tr v-for="item in audit_events" :key="item.id"><td>{{ dt(item.created_at) }}</td><td><code>{{ item.event_type }}</code></td><td>{{ item.actor_name || 'Pengguna tidak tersedia' }}</td><td>{{ item.target_type || '—' }}<span v-if="item.target_id"> #{{ item.target_id }}</span></td></tr><tr v-if="!audit_events.length"><td colspan="4" class="empty-state">Belum ada aktivitas audit.</td></tr></tbody></table>
                </div>
                <nav v-if="pages" class="pagination" aria-label="Halaman riwayat"><span>{{ pages.total }} catatan · halaman {{ pages.current }} / {{ pages.last }}</span><div class="actions"><button class="secondary-button" :disabled="!pages.previous" @click="go(pages.previous)">Sebelumnya</button><button class="secondary-button" :disabled="!pages.next" @click="go(pages.next)">Berikutnya</button></div></nav>
            </section>
        </main>
        <div v-if="show && can_manage" class="modal-backdrop" @click.self="!form.processing && (show = false)"><form class="modal-card" role="dialog" aria-modal="true" aria-labelledby="block-title" @submit.prevent="block"><h2 id="block-title">Blokir alamat IP</h2><p>Pastikan alamat yang dimasukkan benar. Pengguna pada jaringan tersebut akan kehilangan akses selama blokir berlaku.</p><label>Alamat IP<input v-model="form.ip_address" required maxlength="45" placeholder="Contoh: 203.0.113.10" /><small v-if="form.errors.ip_address">{{ form.errors.ip_address }}</small></label><label>Alasan<textarea v-model="form.reason" required maxlength="500" rows="3" /><small v-if="form.errors.reason">{{ form.errors.reason }}</small></label><label>Berlaku sampai (opsional)<input v-model="form.expires_at" type="datetime-local" /><small v-if="form.errors.expires_at">{{ form.errors.expires_at }}</small></label><p>Kosongkan masa berlaku untuk blokir tanpa batas waktu.</p><div class="actions"><button type="button" class="secondary-button" :disabled="form.processing" @click="show = false">Batal</button><button class="primary-button" :disabled="form.processing">{{ form.processing ? 'Menyimpan…' : 'Simpan blokir' }}</button></div></form></div>
    </AppLayout>
</template>

<style scoped>
.security-hero,.tabs,.actions,.pagination{display:flex;align-items:center;gap:.7rem;flex-wrap:wrap}.security-hero{justify-content:space-between}.security-hero>div,.security-panel{min-width:0}.security-panel{overflow:hidden}.tabs{padding:1rem;border-bottom:1px solid #e2e8f0}.tabs button{padding:.55rem .8rem;border-radius:.6rem;color:#52637c;font-size:.85rem;font-weight:700}.tabs .active{background:#eaf0ff;color:#3157a4}.help,.scroll-help{padding:.8rem 1rem 0;color:#52637c;font-size:.85rem;line-height:1.6}.scroll-help{padding-bottom:.7rem;font-size:.75rem}.table-scroll{width:100%;overflow:auto}table{width:100%;min-width:44rem;table-layout:fixed;border-collapse:collapse}th,td{padding:.8rem;border-bottom:1px solid #e2e8f0;text-align:left;font-size:.8rem;vertical-align:top;overflow-wrap:anywhere}th{background:#f8fafc;color:#52637c;font-weight:700}.badge{display:inline-flex;padding:.2rem .5rem;border-radius:999px;font-size:.75rem;font-weight:700}.danger{background:#fee2e2;color:#b91c1c}.success{background:#dcfce7;color:#15803d}.muted{background:#f1f5f9;color:#64748b}.text-button{color:#3157a4;font-weight:700}.pagination{justify-content:space-between;padding:1rem;font-size:.8rem;color:#52637c}button:disabled{opacity:.5;cursor:not-allowed}.modal-backdrop{position:fixed;inset:0;z-index:80;display:grid;place-items:center;padding:1rem;background:#0f172a99}.modal-card{width:min(500px,100%);max-height:90vh;overflow:auto;display:grid;gap:1rem;padding:1.4rem;border-radius:1rem;background:#fff}.modal-card h2{font-weight:800}.modal-card p{font-size:.8rem;color:#52637c;line-height:1.6}.modal-card label{display:grid;gap:.4rem;font-size:.85rem;font-weight:600}.modal-card input,.modal-card textarea{width:100%;padding:.65rem;border:1px solid #cbd5e1;border-radius:.6rem}.error,.modal-card small{color:#b42318}.empty-state{text-align:center;padding:2rem}
</style>
