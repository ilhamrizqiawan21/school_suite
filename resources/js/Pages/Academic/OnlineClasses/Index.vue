<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{ term: any; classes: any[]; assignments: any[]; sessions: any[] }>();
const open = ref(false);
const form = useForm({ class_group_id: '', teaching_assignment_id: '', title: '', provider: 'google_meet', meeting_url: '', starts_at: '', ends_at: '' });
const submit = () => form.post('/akademik/kelas-daring', { onSuccess: () => { open.value = false; form.reset(); } });
const setStatus = (id: number, status: string) => useForm({ status }).put(`/akademik/kelas-daring/${id}/status`);
</script>

<template>
    <Head title="Kelas Daring" />
    <AppLayout>
        <div class="page-heading"><div><p class="eyebrow">Operasi akademik</p><h1>Kelas & presensi daring</h1><p>Jadwalkan pertemuan, kelola tautan, status, dan kehadiran siswa.</p></div><button class="btn btn-primary" @click="open = true">+ Pertemuan daring</button></div>
        <div class="card overflow-hidden"><table class="data-table"><thead><tr><th>Pertemuan</th><th>Kelas</th><th>Waktu</th><th>Status</th><th>Presensi</th><th></th></tr></thead><tbody>
            <tr v-for="item in sessions" :key="item.id"><td><strong>{{ item.title }}</strong><br><a :href="item.meeting_url" target="_blank" rel="noopener noreferrer">Buka tautan</a></td><td>{{ item.class_group?.name }}</td><td>{{ new Date(item.starts_at).toLocaleString('id-ID') }}</td><td><select :value="item.status" @change="setStatus(item.id, ($event.target as HTMLSelectElement).value)"><option value="scheduled">Terjadwal</option><option value="live">Berlangsung</option><option value="completed">Selesai</option><option value="cancelled">Dibatalkan</option></select></td><td>{{ item.attendances_count }} tercatat</td><td><Link class="btn btn-secondary" :href="`/akademik/kelas-daring/${item.id}/presensi`">Isi presensi</Link></td></tr>
            <tr v-if="!sessions.length"><td colspan="6" class="empty-state">Belum ada pertemuan daring.</td></tr>
        </tbody></table></div>

        <div v-if="open" class="modal-backdrop" @click.self="open = false"><form class="modal-card" @submit.prevent="submit"><div class="modal-header"><h2>Jadwalkan pertemuan</h2><button type="button" @click="open = false">×</button></div><div class="form-grid">
            <label class="field span-2">Judul<input v-model="form.title" required></label><label class="field">Kelas<select v-model="form.class_group_id" required><option value="">Pilih kelas</option><option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option></select></label><label class="field">Mata pelajaran<select v-model="form.teaching_assignment_id"><option value="">Umum</option><option v-for="a in assignments.filter(x => String(x.class_group_id) === String(form.class_group_id))" :key="a.id" :value="a.id">{{ a.subject?.name }} — {{ a.teacher?.name }}</option></select></label>
            <label class="field">Platform<select v-model="form.provider"><option value="google_meet">Google Meet</option><option value="zoom">Zoom</option><option value="microsoft_teams">Microsoft Teams</option><option value="other">Lainnya</option></select></label><label class="field">Tautan<input v-model="form.meeting_url" type="url" required></label><label class="field">Mulai<input v-model="form.starts_at" type="datetime-local" required></label><label class="field">Selesai<input v-model="form.ends_at" type="datetime-local"></label>
        </div><p v-if="Object.keys(form.errors).length" class="form-error">Periksa kembali data pertemuan.</p><div class="modal-actions"><button type="button" class="btn btn-secondary" @click="open = false">Batal</button><button class="btn btn-primary" :disabled="form.processing">Simpan</button></div></form></div>
    </AppLayout>
</template>
