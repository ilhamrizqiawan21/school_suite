<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type Question = {
    id: number;
    type: string;
    prompt: string;
    options?: string[] | null;
    matching_pairs?: { left: string }[] | null;
    matching_options?: string[] | null;
    points: number;
    answer?: string | null;
};

type SaveState = 'idle' | 'pending' | 'saved' | 'error';

const props = defineProps<{ attempt: any; exam: any; questions: Question[] }>();
const localKey = `cbt:attempt:${props.attempt.id}:answers`;
const reviewKey = `cbt:attempt:${props.attempt.id}:review`;
const answers = ref<Record<number, string>>(Object.fromEntries(props.questions.map((question) => [question.id, question.answer ?? ''])));
const matchingAnswers = ref<Record<number, Record<string, string>>>(Object.fromEntries(props.questions.filter((question) => question.type === 'matching').map((question) => {
    try { return [question.id, JSON.parse(question.answer || '{}')]; } catch { return [question.id, {}]; }
})));
const markedForReview = ref<Record<number, boolean>>({});
const activeIndex = ref(0);
const now = ref(Date.now());
const saveStates = ref<Record<number, SaveState>>({});
const submitted = ref(false);
const pendingSubmit = ref(false);
const isSubmitting = ref(false);
const hasAutoSubmitted = ref(false);
const isOnline = ref(typeof navigator === 'undefined' ? true : navigator.onLine);
const isFullscreen = ref(false);
const showFullscreenPrompt = ref(false);
const textScale = ref(1);
const pendingAnswers = new Map<number, string>();
const inFlight = new Set<number>();
const saveTimers = new Map<number, number>();
let timer: number | undefined;
let heartbeat: number | undefined;
let lastViolationAt = 0;

const remaining = computed(() => Math.max(0, Math.floor((new Date(props.attempt.expires_at).getTime() - now.value) / 1000)));
const clock = computed(() => `${Math.floor(remaining.value / 60)}:${String(remaining.value % 60).padStart(2, '0')}`);
const activeQuestion = computed(() => props.questions[activeIndex.value]);
const isEssay = (question?: Question) => question?.type === 'essay';
const hasAnswer = (question: Question) => !isEssay(question) && (answers.value[question.id] ?? '').trim() !== '';
const answeredCount = computed(() => props.questions.filter(hasAnswer).length);
const progress = computed(() => props.questions.length ? Math.round((answeredCount.value / props.questions.length) * 100) : 0);
const currentState = computed(() => activeQuestion.value ? (saveStates.value[activeQuestion.value.id] ?? 'idle') : 'idle');
const isUrgent = computed(() => remaining.value <= 300 && remaining.value > 0);
const statusLabel = computed(() => props.attempt.status === 'locked' ? 'Terkunci oleh pengawas' : props.attempt.status === 'in_progress' ? 'Sedang dikerjakan' : 'Pengerjaan selesai');

function setSaveState(id: number, state: SaveState) { saveStates.value[id] = state; }
function readLocal<T>(key: string, fallback: T): T { try { const raw = window.localStorage.getItem(key); return raw ? JSON.parse(raw) as T : fallback; } catch { return fallback; } }
function writeLocal(key: string, value: unknown) { try { window.localStorage.setItem(key, JSON.stringify(value)); } catch { /* Storage penuh atau diblokir browser. Server tetap menjadi sumber kebenaran. */ } }
function persistAnswers() { writeLocal(localKey, answers.value); }
function persistReview() { writeLocal(reviewKey, markedForReview.value); }

function flushQuestion(id: number) {
    if (! isOnline.value || inFlight.has(id) || ! pendingAnswers.has(id)) return;
    const answer = pendingAnswers.get(id) ?? '';
    inFlight.add(id);
    router.put(`/ujian/attempt/${props.attempt.id}/jawaban`, { question_id: id, answer }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => { pendingAnswers.delete(id); setSaveState(id, 'saved'); },
        onError: () => { pendingAnswers.set(id, answer); setSaveState(id, 'error'); },
        onFinish: () => { inFlight.delete(id); if (pendingAnswers.has(id)) window.setTimeout(() => flushQuestion(id), 1500); finishSubmitWhenReady(); },
    });
}
function queueSave(id: number) {
    pendingAnswers.set(id, answers.value[id] ?? '');
    persistAnswers();
    setSaveState(id, 'pending');
    const previous = saveTimers.get(id);
    if (previous) window.clearTimeout(previous);
    saveTimers.set(id, window.setTimeout(() => { saveTimers.delete(id); flushQuestion(id); }, 300));
}
function flushAll() {
    saveTimers.forEach((timeout) => window.clearTimeout(timeout));
    saveTimers.clear();
    [...pendingAnswers.keys()].forEach(flushQuestion);
}
function finishSubmitWhenReady() {
    if (pendingSubmit.value && isOnline.value && pendingAnswers.size === 0 && inFlight.size === 0) {
        pendingSubmit.value = false;
        isSubmitting.value = true;
        router.post(`/ujian/attempt/${props.attempt.id}/kirim`, {}, { onSuccess: () => { try { window.localStorage.removeItem(localKey); window.localStorage.removeItem(reviewKey); } catch { /* noop */ } }, onFinish: () => { isSubmitting.value = false; } });
    }
}
function submitExam(force = false) {
    if (submitted.value || isSubmitting.value || props.attempt.status !== 'in_progress') return;
    if (! force && ! window.confirm(`Kirim jawaban sekarang? ${props.questions.length - answeredCount.value} soal pilihan belum dijawab.`)) return;
    if (! isOnline.value) { setSaveState(activeQuestion.value?.id ?? 0, 'error'); return; }
    submitted.value = true;
    pendingSubmit.value = true;
    flushAll();
    finishSubmitWhenReady();
}
function saveMatching(id: number, left: string, value: string) {
    matchingAnswers.value[id] = { ...matchingAnswers.value[id], [left]: value };
    answers.value[id] = JSON.stringify(matchingAnswers.value[id]);
    queueSave(id);
}
function goTo(index: number) { if (index >= 0 && index < props.questions.length) activeIndex.value = index; }
function toggleReview(id: number) { markedForReview.value[id] = ! markedForReview.value[id]; persistReview(); }
function isArabic(prompt: string) { return /[\u0600-\u06ff]/.test(prompt); }
function enterFullscreen() {
    const request = document.documentElement.requestFullscreen?.();
    if (! request) { showFullscreenPrompt.value = true; return; }
    request.then(() => { showFullscreenPrompt.value = false; }).catch(() => { showFullscreenPrompt.value = true; });
}
function reportViolation(type: 'tab_hidden' | 'window_blur' | 'fullscreen_exit') {
    if (props.attempt.status !== 'in_progress' || Date.now() - lastViolationAt < 2500) return;
    lastViolationAt = Date.now();
    router.post(`/ujian/attempt/${props.attempt.id}/pelanggaran`, { type }, { preserveScroll: true, preserveState: true });
}
function visibilityChanged() { if (document.hidden) reportViolation('tab_hidden'); }
function windowBlurred() { reportViolation('window_blur'); }
function fullscreenChanged() { isFullscreen.value = Boolean(document.fullscreenElement); if (! isFullscreen.value && props.attempt.status === 'in_progress') { showFullscreenPrompt.value = true; reportViolation('fullscreen_exit'); } }
function connectionChanged() { isOnline.value = navigator.onLine; if (isOnline.value) flushAll(); }

onMounted(() => {
    const localAnswers = readLocal<Record<number, string>>(localKey, {});
    Object.entries(localAnswers).forEach(([id, answer]) => { if (answer !== '') answers.value[Number(id)] = answer; });
    markedForReview.value = readLocal<Record<number, boolean>>(reviewKey, {});
    if (props.attempt.status !== 'in_progress') return;
    Object.entries(localAnswers).forEach(([id, answer]) => { if (answer !== '') pendingAnswers.set(Number(id), answer); });
    flushAll();
    timer = window.setInterval(() => { now.value = Date.now(); if (remaining.value === 0 && ! hasAutoSubmitted.value) { hasAutoSubmitted.value = true; if (timer) window.clearInterval(timer); submitExam(true); } }, 1000);
    heartbeat = window.setInterval(() => { if (isOnline.value) router.post(`/ujian/attempt/${props.attempt.id}/heartbeat`, {}, { preserveState: true, preserveScroll: true }); }, 30000);
    document.addEventListener('visibilitychange', visibilityChanged);
    window.addEventListener('blur', windowBlurred);
    document.addEventListener('fullscreenchange', fullscreenChanged);
    window.addEventListener('online', connectionChanged);
    window.addEventListener('offline', connectionChanged);
});

onBeforeUnmount(() => {
    if (timer) window.clearInterval(timer);
    if (heartbeat) window.clearInterval(heartbeat);
    saveTimers.forEach((timeout) => window.clearTimeout(timeout));
    document.removeEventListener('visibilitychange', visibilityChanged);
    window.removeEventListener('blur', windowBlurred);
    document.removeEventListener('fullscreenchange', fullscreenChanged);
    window.removeEventListener('online', connectionChanged);
    window.removeEventListener('offline', connectionChanged);
});
</script>

<template>
    <Head :title="exam.title" />
    <main class="exam-shell">
        <header class="exam-header">
            <div class="exam-title"><p>UJIAN CBT</p><h1>{{ exam.title }}</h1><span v-if="exam.instructions">{{ exam.instructions }}</span></div>
            <div class="exam-toolbar"><span class="connection" :class="{ offline: !isOnline }"><i />{{ isOnline ? 'Tersambung' : 'Offline · tersimpan lokal' }}</span><button class="toolbar-button" type="button" @click="textScale = textScale >= 1.2 ? 1 : textScale + .1">Aa {{ Math.round(textScale * 100) }}%</button><button class="toolbar-button" type="button" @click="enterFullscreen">⛶ Layar penuh</button><div class="timer" :class="{ urgent: isUrgent }" aria-live="polite">{{ clock }}</div></div>
        </header>
        <div class="exam-status" :class="{ locked: attempt.status === 'locked', done: attempt.status !== 'in_progress' }"><strong>{{ statusLabel }}</strong><span v-if="attempt.status === 'in_progress'">Progres tersimpan otomatis setiap perubahan.</span><span v-else>Jawaban yang tersimpan tidak dapat diubah.</span></div>
        <div v-if="showFullscreenPrompt && attempt.status === 'in_progress'" class="fullscreen-prompt"><div><strong>Mode layar penuh diperlukan</strong><p>Mode ini membantu menjaga fokus dan mencatat jika layar ditinggalkan.</p></div><button class="primary-button" type="button" @click="enterFullscreen">Masuk layar penuh</button></div>
        <div class="exam-layout">
            <aside class="question-nav" aria-label="Navigasi soal"><div class="progress-copy"><strong>{{ answeredCount }}/{{ questions.length }}</strong><span>soal pilihan terjawab</span></div><div class="progress-track"><span :style="{ width: `${progress}%` }" /></div><div class="question-grid"><button v-for="(question, index) in questions" :key="question.id" type="button" :class="{ active: index === activeIndex, answered: hasAnswer(question), review: markedForReview[question.id] }" :aria-label="`Soal ${index + 1}${markedForReview[question.id] ? ', ditandai ragu' : ''}`" @click="goTo(index)">{{ index + 1 }}<i v-if="markedForReview[question.id]" /></button></div><p class="legend"><span class="legend-item"><b class="dot answered-dot" />Terjawab</span><span class="legend-item"><b class="dot review-dot" />Ragu-ragu</span><span class="legend-item"><b class="dot" />Belum diisi</span></p></aside>
            <section class="question-panel" :style="{ '--text-scale': textScale }"><article v-if="activeQuestion" class="question-card"><div class="question-heading"><div><span class="question-number">Soal {{ activeIndex + 1 }} dari {{ questions.length }}</span><span class="question-type">{{ activeQuestion.type === 'multiple_choice' ? 'Pilihan ganda' : activeQuestion.type === 'true_false' ? 'Benar / salah' : activeQuestion.type === 'matching' ? 'Menjodohkan' : 'Esai di lembar jawaban' }}</span></div><div class="question-tools"><span class="points">{{ activeQuestion.points }} poin</span><button class="review-button" :class="{ active: markedForReview[activeQuestion.id] }" type="button" @click="toggleReview(activeQuestion.id)">⚑ {{ markedForReview[activeQuestion.id] ? 'Hapus tanda ragu' : 'Tandai ragu' }}</button></div></div><h2 :class="{ 'arabic-copy': isArabic(activeQuestion.prompt) }">{{ activeQuestion.prompt }}</h2><div v-if="activeQuestion.type === 'multiple_choice'" class="options"><label v-for="(option, optionIndex) in activeQuestion.options ?? []" :key="`${activeQuestion.id}-${optionIndex}`" :class="{ selected: answers[activeQuestion.id] === option }"><input v-model="answers[activeQuestion.id]" type="radio" :value="option" :disabled="attempt.status !== 'in_progress'" @change="queueSave(activeQuestion.id)" /><span>{{ String.fromCharCode(65 + optionIndex) }}</span><b>{{ option }}</b></label></div><div v-else-if="activeQuestion.type === 'true_false'" class="options"><label :class="{ selected: answers[activeQuestion.id] === 'true' }"><input v-model="answers[activeQuestion.id]" type="radio" value="true" :disabled="attempt.status !== 'in_progress'" @change="queueSave(activeQuestion.id)" /><span>✓</span><b>Benar</b></label><label :class="{ selected: answers[activeQuestion.id] === 'false' }"><input v-model="answers[activeQuestion.id]" type="radio" value="false" :disabled="attempt.status !== 'in_progress'" @change="queueSave(activeQuestion.id)" /><span>×</span><b>Salah</b></label></div><div v-else-if="activeQuestion.type === 'matching'" class="matches"><label v-for="(pair, pairIndex) in activeQuestion.matching_pairs ?? []" :key="`${activeQuestion.id}-${pairIndex}`"><span>{{ pair.left }}</span><select :value="matchingAnswers[activeQuestion.id]?.[pair.left] ?? ''" :disabled="attempt.status !== 'in_progress'" @change="saveMatching(activeQuestion.id, pair.left, ($event.target as HTMLSelectElement).value)"><option value="">Pilih pasangan</option><option v-for="(option, optionIndex) in activeQuestion.matching_options ?? []" :key="`${activeQuestion.id}-match-${optionIndex}`" :value="option">{{ option }}</option></select></label></div><div v-else class="essay-notice"><strong>Jawaban esai dikerjakan di lembar jawaban.</strong><p>Catat nomor soal ini di kertas. Guru akan memasukkan nilainya setelah ujian selesai.</p><div v-if="answers[activeQuestion.id]" class="essay-answer">Catatan tersimpan: {{ answers[activeQuestion.id] }}</div></div><div class="save-status" aria-live="polite"><span v-if="currentState === 'pending'">Menyimpan jawaban…</span><span v-else-if="currentState === 'saved'" class="success">Jawaban tersimpan di server</span><span v-else-if="currentState === 'error'" class="error">Belum tersinkron. Jawaban tetap tersimpan di perangkat ini.</span><span v-else-if="!isOnline">Mode offline: perubahan akan disinkronkan saat koneksi kembali.</span></div></article><footer class="question-actions"><button type="button" class="secondary-button" :disabled="activeIndex === 0" @click="goTo(activeIndex - 1)">← Sebelumnya</button><button type="button" class="review-button mobile-review" :class="{ active: markedForReview[activeQuestion?.id ?? 0] }" @click="activeQuestion && toggleReview(activeQuestion.id)">⚑ {{ markedForReview[activeQuestion?.id ?? 0] ? 'Ragu' : 'Tandai ragu' }}</button><button v-if="activeIndex < questions.length - 1" type="button" class="primary-button" @click="goTo(activeIndex + 1)">Berikutnya →</button><button v-else-if="attempt.status === 'in_progress'" type="button" class="primary-button submit-button" :disabled="submitted || isSubmitting || !isOnline" @click="submitExam()">{{ isSubmitting ? 'Mengirim…' : 'Kirim jawaban' }}</button><a v-else class="secondary-button" href="/ujian">Kembali ke ujian</a></footer><p class="violation-copy">Pelanggaran: {{ attempt.violation_count }}/{{ exam.max_violations }} · Jangan berpindah tab atau menutup layar ujian.</p></section>
        </div>
    </main>
</template>

<style scoped>
.exam-shell{min-height:100vh;background:#f4f7fb;color:#172844}.exam-header{position:sticky;top:0;z-index:5;display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:.9rem clamp(1rem,5vw,4rem);background:#fff;border-bottom:1px solid #dbe4f0}.exam-title{min-width:0}.exam-title p{margin:0;color:#2563eb;font-size:.7rem;font-weight:850;letter-spacing:.1em}.exam-title h1{margin:.2rem 0;font-size:clamp(1.05rem,2vw,1.45rem);font-weight:850;overflow-wrap:anywhere}.exam-title span{display:block;max-width:72ch;color:#64748b;font-size:.8rem;overflow-wrap:anywhere}.exam-toolbar{display:flex;align-items:center;justify-content:flex-end;gap:.45rem;flex-wrap:wrap}.toolbar-button{min-height:34px;padding:.42rem .6rem;border:1px solid #cbd5e1;border-radius:.5rem;background:#fff;color:#334155;font-size:.72rem;font-weight:750;cursor:pointer}.connection{display:inline-flex;align-items:center;gap:.3rem;color:#15803d;font-size:.72rem;font-weight:750}.connection i{width:7px;height:7px;border-radius:50%;background:currentColor}.connection.offline{color:#a16207}.timer{min-width:5.7rem;padding:.5rem .7rem;border:1px solid #bfdbfe;border-radius:.65rem;background:#eff6ff;color:#1d4ed8;font-size:1.25rem;font-weight:900;text-align:center;font-variant-numeric:tabular-nums}.timer.urgent{border-color:#fecaca;background:#fef2f2;color:#dc2626}.exam-status,.fullscreen-prompt{max-width:1100px;margin:1rem auto 0;padding:.7rem 1rem;border:1px solid #bfdbfe;border-radius:.65rem;background:#eff6ff;color:#1e40af;font-size:.8rem}.exam-status{display:flex;justify-content:space-between;gap:1rem}.exam-status strong{font-weight:850}.exam-status.locked{border-color:#fecaca;background:#fef2f2;color:#b91c1c}.exam-status.done{border-color:#cbd5e1;background:#f8fafc;color:#475569}.fullscreen-prompt{display:flex;align-items:center;justify-content:space-between;gap:1rem;border-color:#fde68a;background:#fffbeb;color:#92400e}.fullscreen-prompt p{margin:.2rem 0 0}.exam-layout{display:grid;grid-template-columns:230px minmax(0,850px);gap:1.2rem;max-width:1100px;margin:0 auto;padding:1.2rem 1rem 6rem}.question-nav{align-self:start;position:sticky;top:94px;padding:1rem;border:1px solid #dbe4f0;border-radius:.9rem;background:#fff}.progress-copy{display:flex;align-items:baseline;justify-content:space-between;gap:.4rem}.progress-copy strong{font-size:1.25rem}.progress-copy span,.legend{color:#64748b;font-size:.75rem}.progress-track{height:7px;margin:.75rem 0 1rem;overflow:hidden;border-radius:999px;background:#e2e8f0}.progress-track span{display:block;height:100%;border-radius:inherit;background:#2563eb;transition:width .2s ease}.question-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:.4rem}.question-grid button{position:relative;height:36px;border:1px solid #dbe4f0;border-radius:.45rem;background:#fff;color:#475569;font-weight:800;cursor:pointer}.question-grid button.answered{border-color:#93c5fd;background:#eff6ff;color:#1d4ed8}.question-grid button.review{border-color:#fbbf24;background:#fffbeb;color:#92400e}.question-grid button.active{outline:2px solid #2563eb;outline-offset:1px}.question-grid button i{position:absolute;right:4px;top:4px;width:5px;height:5px;border-radius:50%;background:#f59e0b}.legend{display:flex;align-items:center;gap:.55rem;flex-wrap:wrap;margin:.85rem 0 0}.legend-item{display:inline-flex;align-items:center;gap:.25rem}.dot{width:7px;height:7px;border-radius:50%;background:#cbd5e1}.answered-dot{background:#2563eb}.review-dot{background:#f59e0b}.question-panel{min-width:0}.question-card{padding:clamp(1rem,3vw,1.7rem);border:1px solid #dbe4f0;border-radius:1rem;background:#fff;box-shadow:0 8px 25px #1728440d}.question-heading{display:flex;justify-content:space-between;gap:1rem;margin-bottom:1.1rem}.question-heading>div{display:flex;align-items:center;gap:.55rem;flex-wrap:wrap}.question-number{color:#2563eb;font-size:.8rem;font-weight:850}.question-type{padding:.18rem .45rem;border-radius:999px;background:#f1f5f9;color:#64748b;font-size:.68rem;font-weight:750}.question-tools{justify-content:flex-end}.points{white-space:nowrap;color:#64748b;font-size:.8rem}.review-button{border:1px solid #fbbf24;border-radius:.5rem;padding:.4rem .55rem;background:#fff;color:#a16207;font-size:.72rem;font-weight:750;cursor:pointer}.review-button.active{background:#fffbeb}.question-card h2{margin:0;font-size:calc(clamp(1.05rem,2vw,1.35rem) * var(--text-scale));line-height:1.6;white-space:pre-wrap;overflow-wrap:anywhere}.arabic-copy{direction:rtl;text-align:right;font-family:Tahoma,Arial,sans-serif;line-height:1.9}.options,.matches{display:grid;gap:.65rem;margin-top:1.5rem}.options label,.matches label{display:flex;align-items:flex-start;gap:.7rem;padding:.85rem;border:1px solid #dbe4f0;border-radius:.65rem;cursor:pointer;line-height:1.5;transition:border-color .15s ease,background .15s ease}.options label.selected{border-color:#60a5fa;background:#eff6ff}.options input{margin-top:.28rem;flex:0 0 auto}.options label>span{display:grid;place-items:center;min-width:1.4rem;height:1.4rem;border-radius:.35rem;background:#f1f5f9;color:#2563eb;font-size:.72rem;font-weight:850}.options b{font-weight:650;overflow-wrap:anywhere}.matches label{display:grid;grid-template-columns:minmax(0,1fr) minmax(180px,1fr);align-items:center}.matches select,textarea{width:100%;border:1px solid #cbd5e1;border-radius:.6rem;padding:.7rem;background:#fff;color:#172844}.essay-notice{display:grid;gap:.3rem;margin-top:1.5rem;padding:1rem;border:1px solid #fde68a;border-radius:.65rem;background:#fffbeb;color:#92400e}.essay-notice p{margin:0;font-size:.82rem;line-height:1.55}.essay-answer{padding:.55rem;border-radius:.45rem;background:#fff;color:#475569;font-size:.8rem}.save-status{min-height:1.25rem;margin-top:1rem;color:#64748b;font-size:.78rem}.save-status .success{color:#15803d}.save-status .error{color:#b91c1c}.question-actions{display:flex;align-items:center;justify-content:space-between;gap:.7rem;margin-top:1rem}.question-actions button,.question-actions a{min-width:9.5rem;text-align:center}.mobile-review{display:none}.submit-button{background:#15803d}.violation-copy{margin:.8rem 0 0;color:#64748b;font-size:.75rem;text-align:right}@media(max-width:800px){.exam-header{align-items:flex-start;flex-direction:column}.exam-toolbar{justify-content:flex-start}.exam-status,.fullscreen-prompt{margin-inline:1rem;align-items:flex-start;flex-direction:column}.exam-layout{display:block;padding-top:.9rem}.question-nav{position:static;margin-bottom:1rem}.question-grid{grid-template-columns:repeat(8,1fr)}.question-actions{position:sticky;bottom:0;z-index:4;margin:1rem -1rem 0;padding:1rem;background:#fff;border-top:1px solid #dbe4f0}.question-actions button,.question-actions a{min-width:0;flex:1}.mobile-review{display:block}.question-tools .review-button{display:none}.violation-copy{text-align:center}}@media(max-width:520px){.question-grid{grid-template-columns:repeat(6,1fr)}.question-heading{align-items:flex-start;flex-direction:column}.question-tools{width:100%;justify-content:space-between}.matches label{grid-template-columns:1fr}.timer{min-width:5.2rem;font-size:1.1rem}}
</style>
