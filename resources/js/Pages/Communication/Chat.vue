<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    assignments: any[];
    rooms: any[];
    selected_room: any;
    messages: any[];
    can_manage: boolean;
}>();

const roomForm = useForm({
    teaching_assignment_id: props.assignments[0]?.id ?? '',
    name: '',
});
const messageForm = useForm({ body: '' });
const showRoom = ref(false);

const send = () => {
    if (!props.selected_room) return;

    messageForm.post(`/komunikasi/chat/ruang/${props.selected_room.id}/pesan`, {
        preserveScroll: true,
        onSuccess: () => messageForm.reset(),
    });
};

const moderate = (id: number) => {
    const reason = prompt('Alasan moderasi');

    if (reason) {
        router.post(`/komunikasi/chat/pesan/${id}/moderasi`, { reason }, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Chat Kelas" />

    <AppLayout>
        <main class="view space-y-6 chat-page">
            <section class="hero-card chat-hero">
                <div>
                    <p class="eyebrow">Komunikasi kelas</p>
                    <h1>Chat kelas-mapel</h1>
                    <p>Ruang diskusi terisolasi sesuai penugasan mengajar dan enrollment siswa.</p>
                </div>

                <div class="hero-actions">
                    <span class="chat-room-count">{{ rooms.length }} ruang aktif</span>
                    <button v-if="can_manage" class="btn btn-primary" type="button" @click="showRoom = true">
                        + Buat ruang
                    </button>
                </div>
            </section>

            <div class="chat-layout">
                <aside class="panel room-list" aria-label="Daftar ruang chat">
                    <div class="room-list-head">
                        <div>
                            <p class="eyebrow">Ruang diskusi</p>
                            <h2>Ruang chat</h2>
                        </div>
                        <span class="room-list-count">{{ rooms.length }}</span>
                    </div>

                    <div v-if="rooms.length" class="room-options">
                        <button
                            v-for="room in rooms"
                            :key="room.id"
                            class="room-option"
                            :class="{ active: selected_room?.id === room.id }"
                            type="button"
                            :aria-pressed="selected_room?.id === room.id"
                            @click="router.get('/komunikasi/chat', { room_id: room.id })"
                        >
                            <span>{{ room.name }}</span>
                            <small>Masuk ke ruang</small>
                        </button>
                    </div>

                    <p v-else class="empty-state room-empty">Belum ada ruang chat.</p>
                </aside>

                <section class="panel chat-panel" aria-live="polite">
                    <template v-if="selected_room">
                        <header class="chat-panel-head">
                            <div>
                                <p class="eyebrow">Ruang terpilih</p>
                                <h2>{{ selected_room.name }}</h2>
                                <p>Gunakan ruang ini untuk koordinasi pembelajaran bersama kelas.</p>
                            </div>
                            <span class="chat-status">Aktif</span>
                        </header>

                        <div v-if="messages.length" class="messages">
                            <article v-for="message in messages" :key="message.id" class="message-card">
                                <div class="message-meta">
                                    <strong>{{ message.user?.name }}</strong>
                                    <small>{{ new Date(message.created_at).toLocaleString('id-ID') }}</small>
                                </div>
                                <p>{{ message.body }}</p>
                                <button
                                    v-if="can_manage && !message.moderated_at"
                                    class="message-moderate"
                                    type="button"
                                    @click="moderate(message.id)"
                                >
                                    Moderasi
                                </button>
                            </article>
                        </div>
                        <div v-else class="empty-state messages-empty">
                            Belum ada pesan. Mulai percakapan dari ruang ini.
                        </div>

                        <form class="send-box" @submit.prevent="send">
                            <textarea
                                v-model="messageForm.body"
                                required
                                maxlength="3000"
                                placeholder="Tulis pesan untuk ruang ini..."
                                aria-label="Isi pesan"
                            />
                            <button class="btn btn-primary" type="submit" :disabled="messageForm.processing">
                                {{ messageForm.processing ? 'Mengirim…' : 'Kirim' }}
                            </button>
                        </form>
                    </template>

                    <div v-else class="empty-state chat-empty">
                        <strong>Pilih ruang untuk mulai</strong>
                        <span>Pilih salah satu ruang chat di sebelah kiri atau buat ruang baru.</span>
                    </div>
                </section>
            </div>

            <div v-if="showRoom" class="modal-backdrop" @click.self="showRoom = false">
                <form
                    class="modal-card room-modal"
                    @submit.prevent="roomForm.post('/komunikasi/chat/ruang', { onSuccess: () => (showRoom = false) })"
                >
                    <div class="modal-header">
                        <div>
                            <p class="eyebrow">Ruang baru</p>
                            <h2>Buat ruang chat</h2>
                        </div>
                        <button class="close-button" type="button" aria-label="Tutup" @click="showRoom = false">×</button>
                    </div>

                    <label>
                        Penugasan
                        <select v-model="roomForm.teaching_assignment_id">
                            <option v-for="assignment in assignments" :key="assignment.id" :value="assignment.id">
                                {{ assignment.subject?.name }} · {{ assignment.class_group?.name }}
                            </option>
                        </select>
                    </label>

                    <label>
                        Nama ruang
                        <input v-model="roomForm.name" required placeholder="Contoh: Diskusi Matematika VII-A" />
                    </label>

                    <div class="modal-actions">
                        <button class="btn btn-secondary" type="button" @click="showRoom = false">Batal</button>
                        <button class="btn btn-primary" type="submit" :disabled="roomForm.processing">
                            {{ roomForm.processing ? 'Menyimpan…' : 'Buat ruang' }}
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </AppLayout>
</template>

<style scoped>
.chat-hero {
    align-items: flex-end;
}

.chat-room-count,
.chat-status,
.room-list-count {
    display: inline-flex;
    align-items: center;
    min-height: 32px;
    padding: .35rem .7rem;
    border: 1px solid var(--line);
    border-radius: 999px;
    color: var(--ink-soft);
    background: var(--surface-subtle);
    font-size: .72rem;
    font-weight: 800;
    white-space: nowrap;
}

.chat-layout {
    display: grid;
    grid-template-columns: minmax(220px, 280px) minmax(0, 1fr);
    gap: 1rem;
}

.room-list {
    display: grid;
    align-content: start;
    gap: 1rem;
    padding: 1.15rem;
}

.room-list-head,
.chat-panel-head,
.message-meta {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
}

.room-list-head h2,
.chat-panel-head h2 {
    margin: .2rem 0 0;
    color: var(--ink);
    font-size: 1.05rem;
}

.room-options {
    display: grid;
    gap: .55rem;
}

.room-option {
    display: grid;
    gap: .25rem;
    width: 100%;
    padding: .8rem .85rem;
    border: 1px solid var(--line);
    border-radius: 10px;
    color: var(--ink);
    background: var(--surface-subtle);
    text-align: left;
    transition: border-color .16s var(--ease), background .16s var(--ease), transform .16s var(--ease);
}

.room-option:hover,
.room-option.active {
    border-color: var(--primary);
    color: var(--primary-strong);
    background: var(--primary-soft);
    transform: translateY(-1px);
}

.room-option span {
    font-size: .82rem;
    font-weight: 800;
}

.room-option small,
.chat-panel-head p,
.message-meta small {
    color: var(--ink-soft);
    font-size: .72rem;
}

.chat-panel {
    display: grid;
    grid-template-rows: auto minmax(260px, 1fr) auto;
    min-height: 60vh;
    padding: 1.25rem;
}

.chat-panel-head {
    align-items: center;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--line);
}

.chat-panel-head p {
    margin: .35rem 0 0;
}

.chat-status {
    color: var(--green);
    border-color: color-mix(in srgb, var(--green) 28%, var(--line));
    background: color-mix(in srgb, var(--green) 10%, var(--surface));
}

.messages {
    display: grid;
    align-content: start;
    gap: .75rem;
    max-height: 55vh;
    margin: 1rem 0;
    overflow: auto;
    padding: .1rem .15rem .25rem 0;
}

.message-card {
    position: relative;
    padding: .85rem;
    border: 1px solid var(--line);
    border-radius: 11px;
    background: var(--surface-subtle);
}

.message-card p {
    margin: .35rem 0 0;
    color: var(--ink);
    line-height: 1.55;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.message-meta strong {
    color: var(--ink);
    font-size: .8rem;
}

.message-moderate {
    position: absolute;
    top: .55rem;
    right: .6rem;
    color: var(--red);
    font-size: .7rem;
    font-weight: 800;
}

.send-box {
    display: flex;
    align-items: flex-end;
    gap: .75rem;
    padding-top: 1rem;
    border-top: 1px solid var(--line);
}

.send-box textarea {
    flex: 1;
    min-height: 44px;
    resize: vertical;
}

.chat-empty,
.messages-empty,
.room-empty {
    min-height: 160px;
}

.room-modal {
    width: min(520px, 92vw);
}

@media (max-width: 760px) {
    .chat-hero {
        align-items: stretch;
    }

    .chat-layout {
        grid-template-columns: 1fr;
    }

    .chat-panel {
        min-height: 55vh;
    }

    .messages {
        max-height: 45vh;
    }
}

@media (max-width: 520px) {
    .send-box {
        align-items: stretch;
        flex-direction: column;
    }

    .send-box .btn {
        width: 100%;
    }
}
</style>
