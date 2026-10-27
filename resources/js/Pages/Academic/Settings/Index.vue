<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import RowActionButton from '@/components/RowActionButton.vue';
import AppLayout from '../../../layouts/AppLayout.vue';
import type { SharedProps } from '@/types';

interface Term { id:number; name:string; semester_type:'odd'|'even'; start_date:string; end_date:string; is_active:boolean }
interface Year { id:number; name:string; start_date:string; end_date:string; is_active:boolean; terms:Term[] }
interface Subject { id:number; code:string; name:string; category?:string|null; order:number; is_active:boolean }
defineProps<{ academic_years:Year[]; subjects:Subject[]; can_manage:boolean }>();
const page=usePage<SharedProps>();
const message=computed(()=>page.props.flash?.message);
const error=computed(()=>page.props.flash?.error);

const showYear=ref(false), showTerm=ref(false), showSubject=ref(false);
const selectedYear=ref<Year|null>(null), selectedSubject=ref<Subject|null>(null);
const yearForm=useForm({name:'',start_date:'',end_date:''});
const termForm=useForm({name:'Semester Ganjil',semester_type:'odd',start_date:'',end_date:''});
const subjectForm=useForm({code:'',name:'',category:'Wajib',order:0,is_active:true});

const closeYear=()=>{showYear.value=false;selectedYear.value=null;yearForm.reset();yearForm.clearErrors()};
const closeTerm=()=>{showTerm.value=false;selectedYear.value=null;termForm.reset();termForm.clearErrors()};
const closeSubject=()=>{showSubject.value=false;selectedSubject.value=null;subjectForm.reset();subjectForm.clearErrors()};
const editYear=(item:Year)=>{selectedYear.value=item;yearForm.name=item.name;yearForm.start_date=item.start_date.slice(0,10);yearForm.end_date=item.end_date.slice(0,10);yearForm.clearErrors();showYear.value=true};
const openTerm=(item:Year)=>{selectedYear.value=item;termForm.reset();termForm.clearErrors();showTerm.value=true};
const editSubject=(item?:Subject)=>{selectedSubject.value=item??null;subjectForm.code=item?.code??'';subjectForm.name=item?.name??'';subjectForm.category=item?.category??'Wajib';subjectForm.order=item?.order??0;subjectForm.is_active=item?.is_active??true;subjectForm.clearErrors();showSubject.value=true};
const saveYear=()=>{const method=selectedYear.value?'put':'post';const url=selectedYear.value?`/akademik/pengaturan/tahun-ajaran/${selectedYear.value.id}`:'/akademik/pengaturan/tahun-ajaran';yearForm[method](url,{preserveScroll:true,onSuccess:closeYear})};
const saveTerm=()=>{if(!selectedYear.value)return;termForm.post(`/akademik/pengaturan/tahun-ajaran/${selectedYear.value.id}/semester`,{preserveScroll:true,onSuccess:closeTerm})};
const saveSubject=()=>{const method=selectedSubject.value?'put':'post';const url=selectedSubject.value?`/akademik/pengaturan/mata-pelajaran/${selectedSubject.value.id}`:'/akademik/pengaturan/mata-pelajaran';subjectForm[method](url,{preserveScroll:true,onSuccess:closeSubject})};
const activateYear=(year:Year)=>{if(window.confirm(`Aktifkan tahun ajaran ${year.name}? Semester aktif saat ini akan dinonaktifkan.`))router.post(`/akademik/pengaturan/tahun-ajaran/${year.id}/aktifkan`,{}, {preserveScroll:true})};
const activateTerm=(term:Term)=>{if(window.confirm(`Jadikan ${term.name} sebagai semester aktif? Semester aktif saat ini akan diganti.`))router.post(`/akademik/pengaturan/semester/${term.id}/aktifkan`,{}, {preserveScroll:true})};
const removeSubject=(subject:Subject)=>{if(window.confirm(`Hapus mata pelajaran ${subject.name}? Tindakan ini hanya tersedia jika mapel belum pernah digunakan.`))router.delete(`/akademik/pengaturan/mata-pelajaran/${subject.id}`,{preserveScroll:true})};
</script>

<template><AppLayout title="Pengaturan Akademik"><Head title="Pengaturan Akademik"/><main class="view space-y-6">
<section class="hero-card settings-hero"><div><p class="eyebrow">Konfigurasi akademik</p><h1>Periode & mata pelajaran</h1><p>Kelola tahun ajaran, semester aktif, dan katalog mata pelajaran sekolah.</p></div><div v-if="can_manage" class="hero-actions"><button class="secondary-button" @click="selectedYear=null;yearForm.reset();yearForm.clearErrors();showYear=true">Tambah tahun ajaran</button><button class="primary-button" @click="editSubject()">Tambah mata pelajaran</button></div></section>
<div v-if="message" class="notice notice-success" role="status">{{message}}</div><div v-if="error" class="notice notice-error" role="alert">{{error}}</div>
<section class="settings-grid"><div class="panel settings-panel"><div class="panel-heading"><div><p class="eyebrow">Kalender akademik</p><h2>Tahun ajaran & semester</h2></div></div><div class="record-list"><article v-for="year in academic_years" :key="year.id" class="record-card"><div class="record-main"><div><span v-if="year.is_active" class="status-pill">Aktif</span><h3>{{year.name}}</h3><small>{{year.start_date.slice(0,10)}} — {{year.end_date.slice(0,10)}}</small></div><div v-if="can_manage" class="actions"><RowActionButton action="edit" label="Ubah" @click="editYear(year)"/><button class="text-button" @click="openTerm(year)">Tambah semester</button><button v-if="!year.is_active" class="text-button" @click="activateYear(year)">Aktifkan</button></div></div><div class="term-list"><div v-for="term in year.terms" :key="term.id"><span>{{term.name}}</span><small>{{term.semester_type==='odd'?'Ganjil':'Genap'}} · {{term.start_date.slice(0,10)}}—{{term.end_date.slice(0,10)}}</small><button v-if="can_manage && !term.is_active" @click="activateTerm(term)">Jadikan aktif</button><strong v-if="term.is_active">Semester aktif</strong></div><p v-if="!year.terms.length" class="empty-inline">Belum ada semester pada tahun ajaran ini.</p></div></article><div v-if="!academic_years.length" class="empty-state">Belum ada tahun ajaran. Tambahkan kalender akademik agar periode dapat digunakan.</div></div></div>
<div class="panel settings-panel"><div class="panel-heading"><div><p class="eyebrow">Katalog</p><h2>Mata pelajaran</h2></div></div><div class="subject-list"><article v-for="item in subjects" :key="item.id"><span class="subject-code">{{item.code}}</span><div><h3>{{item.name}}</h3><small>{{item.category||'Tanpa kategori'}} · urutan {{item.order}} · {{item.is_active?'aktif':'nonaktif'}}</small></div><div v-if="can_manage" class="actions"><RowActionButton action="edit" label="Ubah" @click="editSubject(item)"/><RowActionButton action="delete" label="Hapus" @click="removeSubject(item)"/></div></article><div v-if="!subjects.length" class="empty-state">Belum ada mata pelajaran. Tambahkan katalog untuk mulai mengatur jadwal dan penugasan.</div></div></div></section>
</main>
<div v-if="can_manage && showYear" class="modal-backdrop" @click.self="closeYear"><form class="modal-card" @submit.prevent="saveYear"><h2>{{selectedYear?'Ubah':'Tambah'}} tahun ajaran</h2><label>Nama<input v-model="yearForm.name" required maxlength="50" placeholder="2027/2028"/><small v-if="yearForm.errors.name" class="field-error">{{yearForm.errors.name}}</small></label><div class="form-grid"><label>Mulai<input v-model="yearForm.start_date" type="date" required/><small v-if="yearForm.errors.start_date" class="field-error">{{yearForm.errors.start_date}}</small></label><label>Selesai<input v-model="yearForm.end_date" type="date" required :min="yearForm.start_date || undefined"/><small v-if="yearForm.errors.end_date" class="field-error">{{yearForm.errors.end_date}}</small></label></div><div class="modal-actions"><button type="button" class="secondary-button" @click="closeYear">Batal</button><button class="primary-button" :disabled="yearForm.processing">{{yearForm.processing?'Menyimpan…':'Simpan'}}</button></div></form></div>
<div v-if="can_manage && showTerm" class="modal-backdrop" @click.self="closeTerm"><form class="modal-card" @submit.prevent="saveTerm"><h2>Tambah semester · {{selectedYear?.name}}</h2><label>Nama<input v-model="termForm.name" required maxlength="50"/><small v-if="termForm.errors.name" class="field-error">{{termForm.errors.name}}</small></label><label>Jenis<select v-model="termForm.semester_type"><option value="odd">Ganjil</option><option value="even">Genap</option></select><small v-if="termForm.errors.semester_type" class="field-error">{{termForm.errors.semester_type}}</small></label><div class="form-grid"><label>Mulai<input v-model="termForm.start_date" type="date" required :min="selectedYear?.start_date.slice(0,10)" :max="selectedYear?.end_date.slice(0,10)"/><small v-if="termForm.errors.start_date" class="field-error">{{termForm.errors.start_date}}</small></label><label>Selesai<input v-model="termForm.end_date" type="date" required :min="termForm.start_date || selectedYear?.start_date.slice(0,10)" :max="selectedYear?.end_date.slice(0,10)"/><small v-if="termForm.errors.end_date" class="field-error">{{termForm.errors.end_date}}</small></label></div><div class="modal-actions"><button type="button" class="secondary-button" @click="closeTerm">Batal</button><button class="primary-button" :disabled="termForm.processing">{{termForm.processing?'Menyimpan…':'Simpan'}}</button></div></form></div>
<div v-if="can_manage && showSubject" class="modal-backdrop" @click.self="closeSubject"><form class="modal-card" @submit.prevent="saveSubject"><h2>{{selectedSubject?'Ubah':'Tambah'}} mata pelajaran</h2><div class="form-grid"><label>Kode<input v-model="subjectForm.code" required maxlength="30"/><small v-if="subjectForm.errors.code" class="field-error">{{subjectForm.errors.code}}</small></label><label>Urutan<input v-model.number="subjectForm.order" type="number" min="0" max="999" required/><small v-if="subjectForm.errors.order" class="field-error">{{subjectForm.errors.order}}</small></label></div><label>Nama<input v-model="subjectForm.name" required maxlength="100"/><small v-if="subjectForm.errors.name" class="field-error">{{subjectForm.errors.name}}</small></label><label>Kategori<input v-model="subjectForm.category" maxlength="50"/><small v-if="subjectForm.errors.category" class="field-error">{{subjectForm.errors.category}}</small></label><label class="check"><input v-model="subjectForm.is_active" type="checkbox"/> Mata pelajaran aktif</label><div class="modal-actions"><button type="button" class="secondary-button" @click="closeSubject">Batal</button><button class="primary-button" :disabled="subjectForm.processing">{{subjectForm.processing?'Menyimpan…':'Simpan'}}</button></div></form></div>
</AppLayout></template>
<style scoped>
.settings-hero,.hero-actions,.panel-heading,.record-main,.actions,.modal-actions{display:flex;align-items:center}
.settings-hero,.panel-heading,.record-main{justify-content:space-between}
.settings-hero{gap:1.5rem}
.hero-actions,.actions,.modal-actions{gap:.7rem}
.settings-grid{display:grid;grid-template-columns:1.2fr 1fr;gap:1.25rem}
.settings-panel{overflow:hidden}
.panel-heading{padding:1.1rem 1.25rem;border-bottom:1px solid var(--line)}
.panel-heading h2,.record-card h3,.subject-list h3,.modal-card h2{font-weight:800;color:var(--ink)}
.record-list,.subject-list{display:grid;gap:.75rem;padding:1rem}
.record-card,.subject-list article{padding:1rem;border:1px solid var(--line);border-radius:.85rem;background:var(--surface)}
.record-main>div:first-child{min-width:0;flex:1 1 100%}
.record-main .actions{width:100%;justify-content:flex-start;flex-wrap:wrap;gap:.4rem}
.record-main .actions .text-button{min-height:38px;padding:.4rem .5rem;font-size:.68rem}
.status-pill{display:inline-block;padding:.15rem .45rem;border-radius:999px;background:var(--green-soft);color:var(--green);font-size:.65rem;font-weight:800}
.record-main small,.subject-list small,.term-list small{display:block;color:var(--ink-faint)}
.term-list{display:grid;gap:.45rem;margin-top:.8rem;padding-top:.8rem;border-top:1px solid var(--line)}
.term-list>div{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:.25rem .65rem;padding:.65rem .7rem;border-radius:.65rem;background:var(--surface-subtle);font-size:.78rem}
.term-list>div>span{grid-column:1;grid-row:1;color:var(--ink);font-weight:750}
.term-list small{grid-column:1;grid-row:2}
.term-list button,.term-list strong,.text-button{min-height:38px;display:inline-flex;align-items:center;justify-content:center;padding:.45rem .7rem;color:var(--primary);background:var(--primary-soft);border:1px solid color-mix(in srgb,var(--primary) 20%,var(--line));border-radius:.6rem;font-size:.72rem;font-weight:800;white-space:nowrap}
.term-list button{grid-column:2;grid-row:1 / span 2;cursor:pointer}
.term-list strong{grid-column:2;grid-row:1 / span 2}
.subject-list article{display:grid;grid-template-columns:minmax(3.8rem,auto) minmax(0,1fr);align-items:center;gap:.75rem}
.subject-code{min-width:3.8rem;padding:.55rem .45rem;border-radius:.5rem;background:var(--primary-soft);color:var(--primary-strong);font-weight:800;text-align:center;white-space:nowrap}
.subject-list article .actions{grid-column:1/-1;justify-content:flex-end;flex-wrap:wrap;padding-top:.7rem;border-top:1px solid var(--line)}
.actions :deep(.row-action-button){min-height:40px;padding:.5rem .8rem}
.actions :deep(.row-action-button:focus-visible),.term-list button:focus-visible,.text-button:focus-visible{outline:3px solid color-mix(in srgb,var(--primary) 35%,transparent);outline-offset:2px}
.notice{padding:.8rem 1rem;border-radius:.7rem;font-size:.8rem;font-weight:650}
.notice-success{color:var(--green);background:var(--green-soft);border:1px solid color-mix(in srgb,var(--green) 25%,var(--line))}
.notice-error{color:var(--red);background:var(--red-soft);border:1px solid color-mix(in srgb,var(--red) 25%,var(--line))}
.empty-inline{margin:0;padding:.65rem;color:var(--ink-faint);font-size:.75rem}
.modal-backdrop{position:fixed;inset:0;z-index:80;display:grid;place-items:center;background:rgb(15 23 42/.62);padding:1rem}
.modal-card{width:min(560px,100%);max-height:min(90vh,780px);overflow-y:auto;display:grid;gap:1rem;padding:1.3rem;border:1px solid var(--line);border-radius:1rem;background:var(--surface);box-shadow:0 24px 70px rgb(0 0 0/.3)}
.modal-card label{display:grid;gap:.35rem;color:var(--ink-soft);font-size:.8rem;font-weight:700}
.modal-card input,.modal-card select{width:100%;min-height:40px;padding:.65rem;color:var(--ink);background:var(--surface-subtle);border:1px solid var(--line-strong);border-radius:.6rem}
.modal-card input:focus,.modal-card select:focus{outline:2px solid color-mix(in srgb,var(--primary) 35%,transparent);outline-offset:1px}
.field-error{color:var(--red);font-size:.7rem;font-weight:600;line-height:1.4}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.check{display:flex!important;align-items:center;gap:.55rem!important}.check input{width:1rem;min-height:1rem;accent-color:var(--primary)}
.modal-actions{justify-content:flex-end}
.modal-actions button:disabled{cursor:wait;opacity:.65}
@media(max-width:850px){.settings-grid{grid-template-columns:1fr}.settings-hero{align-items:flex-start;flex-direction:column}.term-list>div{grid-template-columns:1fr}.record-main{align-items:flex-start;gap:.75rem;flex-direction:column}.actions{flex-wrap:wrap}}
@media(max-width:520px){.form-grid{grid-template-columns:1fr}.hero-actions{width:100%;align-items:stretch;flex-direction:column}.modal-card{padding:1rem}.subject-list article{grid-template-columns:auto 1fr}.subject-list article .actions{grid-column:1/-1;justify-content:flex-end}}
</style>
