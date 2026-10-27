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
const communicationBase = window.location.pathname.startsWith('/guru/') ? '/guru' : '/komunikasi';

const roomName = (room: any) => {
    const name = String(room?.name ?? '').trim();
    if (name.length > 2) return name;

    const assignment = props.assignments.find((item) => item.id === room?.teaching_assignment_id);
    const className = assignment?.class_group?.name;
    const subjectName = assignment?.subject?.name;

    return className && subjectName ? `${className} · ${subjectName}` : 'Ruang kelas';
};

const send = () => {
    if (!props.selected_room) return;

    messageForm.post(`${communicationBase}/chat/ruang/${props.selected_room.id}/pesan`, {
        preserveScroll: true,
        onSuccess: () => messageForm.reset(),
    });
};

const moderate = (id: number) => {
    const reason = prompt('Alasan moderasi');

    if (reason) {
        router.post(`${communicationBase}/chat/pesan/${id}/moderasi`, { reason }, { preserveScroll: true });
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
                    <h1>{{ can_manage ? 'Chat kelas-mapel' : 'Diskusi Kelas' }}</h1>
                    <p>{{ can_manage ? 'Ruang diskusi terisolasi sesuai penugasan mengajar dan enrollment siswa.' : 'Diskusi pembelajaran bersama guru dan teman sekelas.' }}</p>
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
                            @click="router.get(`${communicationBase}/chat`, { room_id: room.id })"
                        >
                            <span>{{ roomName(room) }}</span>
                            <small>{{ selected_room?.id === room.id ? 'Sedang dibuka' : 'Buka percakapan' }}</small>
                        </button>
                    </div>

                    <p v-else class="empty-state room-empty">Belum ada ruang chat.</p>
                </aside>

                <section class="panel chat-panel" aria-live="polite">
                    <template v-if="selected_room">
                        <header class="chat-panel-head">
                            <div>
                                <p class="eyebrow">Ruang terpilih</p>
                                <h2>{{ roomName(selected_room) }}</h2>
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
                                <div v-if="can_manage && !message.moderated_at" class="message-actions">
                                    <button class="message-moderate" type="button" @click="moderate(message.id)">
                                        Moderasi pesan
                                    </button>
                                </div>
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
                        <span>{{ can_manage ? 'Pilih salah satu ruang di sebelah kiri atau buat ruang baru.' : 'Pilih ruang kelas di sebelah kiri. Jika belum tersedia, minta guru membuat ruang diskusi.' }}</span>
                    </div>
                </section>
            </div>

            <div v-if="showRoom" class="modal-backdrop" @click.self="showRoom = false">
                <form
                    class="modal-card room-modal"
                    @submit.prevent="roomForm.post(`${communicationBase}/chat/ruang`, { onSuccess: () => (showRoom = false) })"
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
    grid-template-columns: minmax(230px, 290px) minmax(0, 1fr);
    align-items: start;
    gap: 1rem;
}

.room-list {
    display: grid;
    align-content: start;
    gap: 1rem;
    padding: 1.15rem;
    max-height: min(72vh, 760px);
    overflow: hidden;
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
    min-height: 0;
    overflow-y: auto;
    padding: .1rem .2rem .1rem 0;
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
    line-height: 1.4;
    overflow-wrap: anywhere;
}

.room-option small,
.chat-panel-head p,
.message-meta small {
    color: var(--ink-soft);
    font-size: .72rem;
}

.chat-panel {
    display: grid;
    grid-template-rows: auto minmax(0, 1fr) auto;
    height: min(72vh, 760px);
    min-height: 520px;
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
    min-height: 0;
    margin: 1rem 0;
    overflow: auto;
    padding: .1rem .35rem .25rem 0;
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

.message-meta small {
    flex: 0 0 auto;
    text-align: right;
}

.message-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: .45rem;
}

.message-moderate {
    color: var(--red);
    font-size: .72rem;
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
        height: min(68vh, 680px);
        min-height: 440px;
    }

    .room-list {
        max-height: 34vh;
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

    .message-meta {
        flex-direction: column;
        gap: .2rem;
    }

    .message-meta small {
        text-align: left;
    }
}
</style>
