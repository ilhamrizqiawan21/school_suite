<script setup lang="ts">
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        schoolName?: string;
    }>(),
    {
        schoolName: 'Sekolah',
    },
);

const direction = ref<'incoming' | 'outgoing'>('incoming');
const displaySchoolName = computed(() => (props.schoolName || 'Sekolah').toUpperCase());
</script>
<template>
<section class="reference-mutasi">
<div class="sop-toggle-wrap">
<div class="sop-toggle-group" role="group" aria-label="Prosedur mutasi">
<button
    type="button"
    class="sop-flow-toggle-btn btn-masuk"
    :class="{ active: direction === 'incoming' }"
    :aria-pressed="direction === 'incoming'"
    @click="direction = 'incoming'"
>
    <i class="fa-solid fa-right-to-bracket mr-2" aria-hidden="true"></i>SOP Mutasi Masuk (Siswa Pindahan)
</button>
<button
    type="button"
    class="sop-flow-toggle-btn btn-keluar"
    :class="{ active: direction === 'outgoing' }"
    :aria-pressed="direction === 'outgoing'"
    @click="direction = 'outgoing'"
>
    <i class="fa-solid fa-right-from-bracket mr-2" aria-hidden="true"></i>Alur Mutasi Keluar (Pindah Sekolah)
</button>
</div>
</div>
<div v-show="direction === 'incoming'"><div class="sop-poster mb-4">
                    
                    <div class="sop-header-banner">
                        <div class="sop-title-badge">STANDAR OPERASIONAL PROSEDUR</div>
                        <h2 class="sop-main-title">MURID MUTASI MASUK</h2>
                        <div class="sop-quote-box">
                            <span class="quote-mark">“</span> Proses Mutasi yang Teratur, Layanan Pendidikan yang Berkualitas <span class="quote-mark">”</span>
                        </div>
                    </div>

                    
                    <div class="sop-body">
                        
                        
                        <div class="sop-step-row">
                            <div class="d-flex align-items-center sop-step-row-top">
                                <div class="sop-pill-num">01</div>
                                <div class="sop-icon-wrap">
                                    
                                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="15" y="45" width="70" height="45" rx="4" fill="#E2E8F0"></rect>
                                        <rect x="20" y="52" width="60" height="38" rx="2" fill="#F8FAFC"></rect>
                                        <path d="M10 46L50 20L90 46H10Z" fill="#0D5C3A"></path>
                                        <path d="M50 8L50 20" stroke="#D97706" stroke-width="3" stroke-linecap="round"></path>
                                        <path d="M50 8L64 13L50 18" fill="#F59E0B"></path>
                                        <circle cx="50" cy="34" r="7" fill="#FEF08A" stroke="#0D5C3A" stroke-width="2"></circle>
                                        <path d="M50 30V34H53" stroke="#0D5C3A" stroke-width="1.5" stroke-linecap="round"></path>
                                        
                                        <rect x="25" y="55" width="8" height="35" rx="1" fill="#CBD5E1"></rect>
                                        <rect x="40" y="55" width="8" height="35" rx="1" fill="#CBD5E1"></rect>
                                        <rect x="52" y="55" width="8" height="35" rx="1" fill="#CBD5E1"></rect>
                                        <rect x="67" y="55" width="8" height="35" rx="1" fill="#CBD5E1"></rect>
                                        
                                        <path d="M44 90V68C44 64.6863 46.6863 62 50 62C53.3137 62 56 64.6863 56 68V90H44Z" fill="#0D5C3A"></path>
                                        <rect x="27" y="60" width="4" height="6" rx="1" fill="#93C5FD"></rect>
                                        <rect x="69" y="60" width="4" height="6" rx="1" fill="#93C5FD"></rect>
                                        
                                        <rect x="8" y="90" width="84" height="4" rx="2" fill="#64748B"></rect>
                                    </svg>
                                </div>
                            </div>
                            <div class="sop-content-wrap">
                                <ul>
                                    <li>Murid yang dapat mengajukan mutasi adalah murid yang sedang menempuh pendidikan di <strong>kelas X semester 2 / XI semester 1 / XI semester 2</strong> pada tahun pelajaran yang dijalani, dengan akreditasi sekolah/madrasah <strong>A</strong>, <span class="text-success font-weight-bold">jika kuota masih tersedia</span>.</li>
                                    <li>Murid yang berencana mutasi <strong>bukan berasal dari madrasah swasta di kota Martapura</strong>.</li>
                                </ul>
                            </div>
                        </div>

                        
                        <div class="sop-step-row">
                            <div class="d-flex align-items-center sop-step-row-top">
                                <div class="sop-pill-num">02</div>
                                <div class="sop-icon-wrap">
                                    
                                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        
                                        <rect x="28" y="16" width="44" height="60" rx="3" fill="#FFFFFF" stroke="#CBD5E1" stroke-width="2"></rect>
                                        <line x1="36" y1="26" x2="64" y2="26" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                        <line x1="36" y1="33" x2="58" y2="33" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                        <rect x="36" y="40" width="20" height="2" fill="#10B981" rx="1"></rect>
                                        <rect x="32" y="22" width="48" height="58" rx="3" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"></rect>
                                        <line x1="40" y1="32" x2="70" y2="32" stroke="#CBD5E1" stroke-width="2" stroke-linecap="round"></line>
                                        <line x1="40" y1="39" x2="62" y2="39" stroke="#CBD5E1" stroke-width="2" stroke-linecap="round"></line>
                                        
                                        <path d="M12 40C12 36.6863 14.6863 34 18 34H40L48 42H82C85.3137 42 88 44.6863 88 48V82C88 85.3137 85.3137 88 82 88H18C14.6863 88 12 85.3137 12 82V40Z" fill="#DC2626"></path>
                                        <path d="M12 48L22 88H88L88 48H12Z" fill="#EF4444"></path>
                                        <path d="M16 48H84" stroke="#B91C1C" stroke-width="2"></path>
                                        <rect x="42" y="58" width="28" height="16" rx="2" fill="#FFFFFF" opacity="0.9"></rect>
                                        <line x1="48" y1="64" x2="64" y2="64" stroke="#DC2626" stroke-width="2" stroke-linecap="round"></line>
                                        <line x1="48" y1="69" x2="58" y2="69" stroke="#DC2626" stroke-width="2" stroke-linecap="round"></line>
                                    </svg>
                                </div>
                            </div>
                            <div class="sop-content-wrap">
                                <div class="mb-2">
                                    Murid membuat surat permohonan mutasi yang telah disediakan oleh <strong>{{ displaySchoolName }}</strong>, dan dimasukkan ke dalam <strong class="text-danger" style="background:#fee2e2; padding:2px 8px; border-radius:6px; border:1px solid #fca5a5;">MAP MERAH</strong>, dengan melampirkan:
                                </div>
                                <div class="sop-sub-item">
                                    <span class="sop-badge-num">1</span>
                                    <span>Surat keterangan berkelakuan baik dari sekolah asal</span>
                                </div>
                                <div class="sop-sub-item">
                                    <span class="sop-badge-num">2</span>
                                    <span>Fotokopi rapor semester yang telah dijalani</span>
                                </div>
                                <div class="sop-sub-item">
                                    <span class="sop-badge-num">3</span>
                                    <span>Fotokopi sertifikat prestasi (jika ada)</span>
                                </div>
                                <div class="sop-sub-item">
                                    <span class="sop-badge-num">4</span>
                                    <span>Bagi murid yang berasal dari SMA, wajib melampirkan <strong>konversi nilai</strong> dari SMA asal yang telah dilegalisir. <em>(jika telah resmi diterima)</em></span>
                                </div>
                            </div>
                        </div>

                        
                        <div class="sop-step-row">
                            <div class="d-flex align-items-center sop-step-row-top">
                                <div class="sop-pill-num">03</div>
                                <div class="sop-icon-wrap">
                                    
                                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        
                                        <rect x="14" y="16" width="72" height="50" rx="6" fill="#1E293B"></rect>
                                        <rect x="18" y="20" width="64" height="42" rx="3" fill="#0EA5E9"></rect>
                                        
                                        <rect x="24" y="26" width="52" height="20" rx="3" fill="#0284C7"></rect>
                                        <text x="50" y="40" font-family="'Plus Jakarta Sans', Arial, sans-serif" font-size="11" font-weight="900" fill="#FFFFFF" text-anchor="middle" letter-spacing="1">EMIS</text>
                                        <rect x="28" y="50" width="22" height="4" rx="2" fill="#E0F2FE"></rect>
                                        <rect x="54" y="50" width="18" height="4" rx="2" fill="#BAE6FD"></rect>
                                        
                                        <path d="M44 66H56V78H44V66Z" fill="#334155"></path>
                                        <rect x="32" y="78" width="36" height="5" rx="2" fill="#64748B"></rect>
                                        
                                        <g transform="translate(62, 56)">
                                            <circle cx="16" cy="16" r="14" fill="#F59E0B" stroke="#B45309" stroke-width="2"></circle>
                                            <circle cx="16" cy="16" r="5" fill="#FFFFFF"></circle>
                                            <path d="M16 0V4M16 28V32M0 16H4M28 16H32M4.7 4.7L7.5 7.5M24.5 24.5L27.3 27.3M4.7 27.3L7.5 24.5M24.5 7.5L27.3 4.7" stroke="#B45309" stroke-width="3" stroke-linecap="round"></path>
                                        </g>
                                    </svg>
                                </div>
                            </div>
                            <div class="sop-content-wrap">
                                <div>Pengecekan data kesiswaan oleh <strong>Operator EMIS {{ displaySchoolName }}</strong>.</div>
                            </div>
                            <div class="sop-estimasi-box">
                                <div class="sop-estimasi-head">
                                    <i class="fa-regular fa-calendar-check text-success mr-1" aria-hidden="true"></i> Estimasi Waktu
                                    <span class="sop-days">2 HARI</span>
                                </div>
                                <div class="sop-estimasi-foot">Berlaku di Hari Kerja</div>
                            </div>
                        </div>

                        
                        <div class="sop-step-row">
                            <div class="d-flex align-items-center sop-step-row-top">
                                <div class="sop-pill-num">04</div>
                                <div class="sop-icon-wrap">
                                    
                                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="50" cy="50" r="38" fill="url(#greenGrad)" stroke="#0D5C3A" stroke-width="3"></circle>
                                        <path d="M32 50L44 62L68 38" stroke="#FFFFFF" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"></path>
                                        <defs>
                                            <lineargradient id="greenGrad" x1="20" y1="20" x2="80" y2="80" gradientUnits="userSpaceOnUse">
                                                <stop stop-color="#10B981"></stop>
                                                <stop offset="1" stop-color="#059669"></stop>
                                            </lineargradient>
                                        </defs>
                                    </svg>
                                </div>
                            </div>
                            <div class="sop-content-wrap">
                                <strong class="text-success" style="font-size: 15px;">Jika data lengkap dan tidak bermasalah</strong>, proses dilanjutkan ke tahapan berikutnya.
                            </div>
                        </div>

                        
                        <div class="sop-step-row">
                            <div class="d-flex align-items-center sop-step-row-top">
                                <div class="sop-pill-num">05</div>
                                <div class="sop-icon-wrap">
                                    
                                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        
                                        <path d="M42 12H68C72.4183 12 76 15.5817 76 20V32C76 36.4183 72.4183 40 68 40H52L44 46V40H42C37.5817 40 34 36.4183 34 32V20C34 15.5817 37.5817 12 42 12Z" fill="#38BDF8"></path>
                                        <line x1="42" y1="22" x2="68" y2="22" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round"></line>
                                        <line x1="42" y1="30" x2="60" y2="30" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round"></line>
                                        
                                        <circle cx="28" cy="46" r="14" fill="#FBCFE8"></circle>
                                        <path d="M16 42C16 34 22 30 28 30C34 30 40 34 40 42H16Z" fill="#1E293B"></path>
                                        <path d="M10 88C10 74 18 68 28 68C38 68 46 74 46 88H10Z" fill="#0D5C3A"></path>
                                        <path d="M28 68L25 78L28 84L31 78L28 68Z" fill="#F59E0B"></path>
                                        
                                        <path d="M58 48C58 36 65 32 74 32C83 32 90 36 90 48C90 58 84 62 74 62C64 62 58 58 58 48Z" fill="#0284C7"></path>
                                        <circle cx="74" cy="48" r="10" fill="#FDE047"></circle>
                                        <path d="M56 88C56 74 64 68 74 68C84 68 92 74 92 88H56Z" fill="#0369A1"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="sop-content-wrap">
                                <div class="font-weight-bold mb-1">Murid mengikuti tahapan seleksi berupa:</div>
                                <ul>
                                    <li><strong>Wawancara</strong> oleh Tim Bimbingan dan Konseling (BK)</li>
                                    <li><strong>Tes BTA</strong> (Baca Tulis Al-Qur'an)</li>
                                    <li><strong>CBT Akademik</strong> (Computer Based Test)</li>
                                </ul>
                            </div>
                            <div class="sop-estimasi-box">
                                <div class="sop-estimasi-head">
                                    <i class="fa-regular fa-calendar-check text-success mr-1" aria-hidden="true"></i> Estimasi Waktu
                                    <span class="sop-days">1 HARI</span>
                                </div>
                                <div class="sop-estimasi-foot">Berlaku di Hari Kerja</div>
                            </div>
                        </div>

                        
                        <div class="sop-step-row">
                            <div class="d-flex align-items-center sop-step-row-top">
                                <div class="sop-pill-num">06</div>
                                <div class="sop-icon-wrap">
                                    
                                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        
                                        <ellipse cx="50" cy="68" rx="42" ry="16" fill="#475569"></ellipse>
                                        <ellipse cx="50" cy="64" rx="38" ry="13" fill="#64748B"></ellipse>
                                        
                                        <rect x="42" y="58" width="16" height="10" rx="1" fill="#FFFFFF"></rect>
                                        <line x1="45" y1="62" x2="55" y2="62" stroke="#CBD5E1" stroke-width="1.5"></line>
                                        
                                        
                                        <circle cx="22" cy="40" r="9" fill="#FBCFE8"></circle>
                                        <path d="M10 65C10 54 15 50 22 50C29 50 34 54 34 65H10Z" fill="#1E293B"></path>
                                        
                                        <circle cx="50" cy="34" r="11" fill="#FDE047"></circle>
                                        <path d="M35 58C35 46 41 42 50 42C59 42 65 46 65 58H35Z" fill="#0D5C3A"></path>
                                        
                                        <circle cx="78" cy="40" r="9" fill="#FBCFE8"></circle>
                                        <path d="M66 65C66 54 71 50 78 50C85 50 90 54 90 65H66Z" fill="#1E293B"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="sop-content-wrap">
                                <div><strong>Sidang Tim Mutasi</strong> untuk membahas hasil seleksi dan menentukan kelayakan penerimaan murid.</div>
                            </div>
                            <div class="sop-estimasi-box">
                                <div class="sop-estimasi-head">
                                    <i class="fa-regular fa-calendar-check text-success mr-1" aria-hidden="true"></i> Estimasi Waktu
                                    <span class="sop-days">2 HARI</span>
                                </div>
                                <div class="sop-estimasi-foot">Berlaku di Hari Kerja</div>
                            </div>
                        </div>

                        
                        <div class="sop-step-row">
                            <div class="d-flex align-items-center sop-step-row-top">
                                <div class="sop-pill-num">07</div>
                                <div class="sop-icon-wrap">
                                    
                                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        
                                        <path d="M56 26L26 40V60L56 74V26Z" fill="#0D5C3A" stroke="#064E3B" stroke-width="2"></path>
                                        <rect x="18" y="44" width="8" height="12" rx="2" fill="#D97706"></rect>
                                        
                                        <path d="M32 60V76C32 78.2091 30.2091 80 28 80C25.7909 80 24 78.2091 24 76V60H32Z" fill="#475569"></path>
                                        
                                        <ellipse cx="56" cy="50" rx="8" ry="24" fill="#10B981" stroke="#064E3B" stroke-width="2"></ellipse>
                                        
                                        <path d="M68 38C74 44 74 56 68 62" stroke="#F59E0B" stroke-width="4" stroke-linecap="round"></path>
                                        <path d="M78 30C88 40 88 60 78 70" stroke="#F59E0B" stroke-width="4" stroke-linecap="round"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="sop-content-wrap">
                                <div><strong>Penyampaian hasil sidang</strong> kepada orang tua/wali dan murid melalui surat resmi atau pemberitahuan langsung di sistem.</div>
                            </div>
                        </div>

                        
                        <div class="sop-step-row">
                            <div class="d-flex align-items-center sop-step-row-top">
                                <div class="sop-pill-num">08</div>
                                <div class="sop-icon-wrap">
                                    
                                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        
                                        <rect x="22" y="16" width="56" height="72" rx="6" fill="#F8FAFC" stroke="#CBD5E1" stroke-width="3"></rect>
                                        
                                        <rect x="38" y="10" width="24" height="12" rx="3" fill="#64748B"></rect>
                                        <circle cx="50" cy="16" r="3" fill="#FFFFFF"></circle>
                                        
                                        <line x1="32" y1="36" x2="68" y2="36" stroke="#94A3B8" stroke-width="3" stroke-linecap="round"></line>
                                        <line x1="32" y1="48" x2="68" y2="48" stroke="#94A3B8" stroke-width="3" stroke-linecap="round"></line>
                                        <line x1="32" y1="60" x2="55" y2="60" stroke="#94A3B8" stroke-width="3" stroke-linecap="round"></line>
                                        
                                        <circle cx="68" cy="72" r="16" fill="#10B981" stroke="#FFFFFF" stroke-width="3"></circle>
                                        <path d="M60 72L65 77L76 66" stroke="#FFFFFF" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="sop-content-wrap">
                                <div>Jika murid dinyatakan <strong>diterima</strong>, {{ displaySchoolName }} akan melanjutkan <strong>proses administrasi penerimaan</strong>.</div>
                            </div>
                        </div>

                        
                        <div class="sop-step-row">
                            <div class="d-flex align-items-center sop-step-row-top">
                                <div class="sop-pill-num">09</div>
                                <div class="sop-icon-wrap">
                                    
                                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        
                                        <rect x="20" y="12" width="60" height="76" rx="4" fill="#FFFFFF" stroke="#0D5C3A" stroke-width="2.5"></rect>
                                        <rect x="26" y="18" width="48" height="64" rx="2" fill="#F0FDF4" stroke="#86EFAC" stroke-width="1"></rect>
                                        
                                        <line x1="32" y1="28" x2="68" y2="28" stroke="#0D5C3A" stroke-width="3" stroke-linecap="round"></line>
                                        <line x1="32" y1="36" x2="62" y2="36" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                        <line x1="32" y1="44" x2="68" y2="44" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                        <line x1="32" y1="52" x2="56" y2="52" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                        
                                        <path d="M46 66L42 84L50 80L58 84L54 66" fill="#D97706"></path>
                                        <circle cx="50" cy="66" r="11" fill="#F59E0B" stroke="#B45309" stroke-width="2"></circle>
                                        <path d="M46 66L48 69L54 63" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="sop-content-wrap">
                                <div><strong>Penerbitan Surat Menerima Murid Mutasi</strong> resmi dari {{ displaySchoolName }}.</div>
                            </div>
                            <div class="sop-estimasi-box">
                                <div class="sop-estimasi-head">
                                    <i class="fa-regular fa-calendar-check text-success mr-1" aria-hidden="true"></i> Estimasi Waktu
                                    <span class="sop-days">1 HARI</span>
                                </div>
                                <div class="sop-estimasi-foot">Berlaku di Hari Kerja</div>
                            </div>
                        </div>

                        
                        <div class="sop-info-bar">
                            <i class="fa-regular fa-calendar-check fa-2x mr-3 text-success" aria-hidden="true"></i>
                            <div>
                                <strong>KETERANGAN PENTING:</strong>
                                <span class="d-block text-muted" style="font-size: 13px;">Estimasi waktu dihitung dalam <strong>hari kerja</strong> dan dapat berubah sesuai kondisi operasional madrasah.</span>
                            </div>
                        </div>

                        
                        <div class="sop-footer-slogan">
                            <div>
                                <div class="sop-mantab-logo">MANTAB</div>
                                <div class="sop-mantab-sub">Moderate, Agamis, Nasionalis, Terdepan, Berprestasi</div>
                            </div>

                            
                            <div class="d-none d-md-block text-center" style="opacity: 0.9;">
                                <svg width="120" height="40" viewBox="0 0 120 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M60 4C54 14 50 16 46 22V38H74V22C70 16 66 14 60 4Z" fill="#FEF08A"></path>
                                    <circle cx="60" cy="2" r="1.5" fill="#FEF08A"></circle>
                                    <rect x="36" y="16" width="6" height="22" fill="#FEF08A"></rect>
                                    <path d="M36 16L39 10L42 16H36Z" fill="#FEF08A"></path>
                                    <rect x="78" y="16" width="6" height="22" fill="#FEF08A"></rect>
                                    <path d="M78 16L81 10L84 16H78Z" fill="#FEF08A"></path>
                                    <rect x="20" y="24" width="80" height="14" rx="2" fill="#064E3B" fill-opacity="0.3"></rect>
                                </svg>
                            </div>

                            <div class="sop-footer-quote">
                                <i class="fa-solid fa-heart mr-2 text-warning" style="font-size: 20px;" aria-hidden="true"></i>
                                <span>Kami berkomitmen memberikan layanan terbaik demi pendidikan yang berkualitas dan berkarakter.</span>
                            </div>
                        </div>

                    </div>
                </div></div>
<div v-show="direction === 'outgoing'"><div class="sop-poster mb-4">
                    
                    <div class="sop-header-banner sop-header-banner-blue">
                        <div class="sop-title-badge" style="color: #bae6fd;">STANDAR OPERASIONAL PROSEDUR</div>
                        <h2 class="sop-main-title">MUTASI KELUAR SISWA</h2>
                        <div class="sop-quote-box" style="border-color: rgba(186, 230, 253, 0.4); color: #e0f2fe;">
                            <span class="quote-mark" style="color: #38bdf8;">“</span> Proses Pindah Sekolah yang Tertib, Cepat, dan Akuntabel <span class="quote-mark" style="color: #38bdf8;">”</span>
                        </div>
                    </div>

                    
                    <div class="sop-body">
                        
                        
                        <div class="sop-step-row blue">
                            <div class="d-flex align-items-center sop-step-row-top">
                                <div class="sop-pill-num blue">01</div>
                                <div class="sop-icon-wrap">
                                    
                                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        
                                        <rect x="20" y="14" width="56" height="72" rx="4" fill="#FFFFFF" stroke="#3B82F6" stroke-width="2.5"></rect>
                                        <rect x="26" y="20" width="44" height="60" rx="2" fill="#F0F9FF"></rect>
                                        
                                        <rect x="30" y="26" width="24" height="4" rx="2" fill="#0284C7"></rect>
                                        <line x1="30" y1="36" x2="62" y2="36" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                        <line x1="30" y1="43" x2="58" y2="43" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                        <line x1="30" y1="50" x2="62" y2="50" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                        <line x1="30" y1="57" x2="50" y2="57" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                        
                                        <g transform="translate(52, 42) rotate(35)">
                                            <rect x="0" y="0" width="10" height="38" rx="2" fill="#F59E0B" stroke="#B45309" stroke-width="1.5"></rect>
                                            <path d="M0 38L5 46L10 38H0Z" fill="#1E293B"></path>
                                            <rect x="0" y="6" width="10" height="4" fill="#FEF08A"></rect>
                                        </g>
                                    </svg>
                                </div>
                            </div>
                            <div class="sop-content-wrap">
                                <div class="mb-2">
                                    Orang tua / Wali siswa mengajukan permohonan pindah madrasah/sekolah melalui tab <strong>"Formulir Permohonan Mutasi"</strong> online atau menyerahkan surat permohonan ke pihak madrasah dengan melampirkan berkas:
                                </div>
                                <div class="sop-sub-item">
                                    <span class="sop-badge-num blue">1</span>
                                    <span>Surat Permohonan Pindah Resmi dari Orang Tua / Wali Siswa</span>
                                </div>
                                <div class="sop-sub-item">
                                    <span class="sop-badge-num blue">2</span>
                                    <span>Surat Persetujuan / Keterangan Bersedia Menerima dari Sekolah/Madrasah Tujuan</span>
                                </div>
                                <div class="sop-sub-item">
                                    <span class="sop-badge-num blue">3</span>
                                    <span>Fotokopi Kartu Keluarga (KK) &amp; Akta Kelahiran Siswa</span>
                                </div>
                            </div>
                        </div>

                        
                        <div class="sop-step-row blue">
                            <div class="d-flex align-items-center sop-step-row-top">
                                <div class="sop-pill-num blue">02</div>
                                <div class="sop-icon-wrap">
                                    
                                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        
                                        <rect x="18" y="16" width="50" height="68" rx="5" fill="#FFFFFF" stroke="#64748B" stroke-width="2.5"></rect>
                                        <rect x="32" y="10" width="22" height="10" rx="2" fill="#3B82F6"></rect>
                                        <line x1="26" y1="32" x2="56" y2="32" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                        <line x1="26" y1="42" x2="56" y2="42" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                        <line x1="26" y1="52" x2="48" y2="52" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                        
                                        <g transform="translate(48, 48)">
                                            <circle cx="20" cy="20" r="18" fill="#10B981" stroke="#FFFFFF" stroke-width="2.5"></circle>
                                            <path d="M12 20L17 25L28 14" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                                        </g>
                                    </svg>
                                </div>
                            </div>
                            <div class="sop-content-wrap">
                                <div>Pemeriksaan kelengkapan berkas admin kesiswaan, validasi data EMIS, konfirmasi <strong>Bebas Tanggungan SPP/Keuangan</strong>, dan surat <strong>Bebas Pinjaman Perpustakaan (Bebas Pustaka)</strong>.</div>
                            </div>
                            <div class="sop-estimasi-box blue">
                                <div class="sop-estimasi-head blue">
                                    <i class="fa-regular fa-calendar-check mr-1" aria-hidden="true"></i> Estimasi Waktu
                                    <span class="sop-days">1 HARI</span>
                                </div>
                                <div class="sop-estimasi-foot blue">Berlaku di Hari Kerja</div>
                            </div>
                        </div>

                        
                        <div class="sop-step-row blue">
                            <div class="d-flex align-items-center sop-step-row-top">
                                <div class="sop-pill-num blue">03</div>
                                <div class="sop-icon-wrap">
                                    
                                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        
                                        <rect x="18" y="14" width="64" height="74" rx="4" fill="#FFFFFF" stroke="#1D4ED8" stroke-width="2.5"></rect>
                                        <rect x="24" y="20" width="52" height="62" rx="2" fill="#EFF6FF" stroke="#BFDBFE" stroke-width="1"></rect>
                                        
                                        <line x1="30" y1="28" x2="70" y2="28" stroke="#1D4ED8" stroke-width="3" stroke-linecap="round"></line>
                                        <line x1="30" y1="36" x2="64" y2="36" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                        <line x1="30" y1="44" x2="70" y2="44" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                        <line x1="30" y1="52" x2="56" y2="52" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                        
                                        <path d="M46 66L42 84L50 80L58 84L54 66" fill="#0284C7"></path>
                                        <circle cx="50" cy="66" r="11" fill="#38BDF8" stroke="#0369A1" stroke-width="2"></circle>
                                        <path d="M46 66L48 69L54 63" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="sop-content-wrap">
                                <div>Madrasah menerbitkan <strong>Surat Keterangan Pindah / Mutasi Keluar</strong> resmi yang ditandatangani Kepala Madrasah beserta validasi status mutasi keluar pada database sistem kesiswaan.</div>
                            </div>
                            <div class="sop-estimasi-box blue">
                                <div class="sop-estimasi-head blue">
                                    <i class="fa-regular fa-calendar-check mr-1" aria-hidden="true"></i> Estimasi Waktu
                                    <span class="sop-days">1 HARI</span>
                                </div>
                                <div class="sop-estimasi-foot blue">Berlaku di Hari Kerja</div>
                            </div>
                        </div>

                        
                        <div class="sop-step-row blue">
                            <div class="d-flex align-items-center sop-step-row-top">
                                <div class="sop-pill-num blue">04</div>
                                <div class="sop-icon-wrap">
                                    
                                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        
                                        <rect x="18" y="24" width="46" height="60" rx="3" fill="#0D5C3A" stroke="#064E3B" stroke-width="2"></rect>
                                        <rect x="22" y="28" width="8" height="52" fill="#064E3B"></rect>
                                        <rect x="34" y="38" width="22" height="14" rx="2" fill="#FEF08A"></rect>
                                        <line x1="38" y1="43" x2="52" y2="43" stroke="#0D5C3A" stroke-width="1.5"></line>
                                        <line x1="38" y1="47" x2="48" y2="47" stroke="#0D5C3A" stroke-width="1.5"></line>
                                        
                                        <rect x="42" y="16" width="44" height="62" rx="3" fill="#FFFFFF" stroke="#3B82F6" stroke-width="2"></rect>
                                        <path d="M42 20L64 36L86 20" stroke="#3B82F6" stroke-width="2"></path>
                                        
                                        <circle cx="64" cy="46" r="7" fill="#F59E0B"></circle>
                                    </svg>
                                </div>
                            </div>
                            <div class="sop-content-wrap">
                                <div>Siswa atau Orang Tua / Wali menerima <strong>Surat Mutasi Keluar</strong>, <strong>Buku Rapor Asli</strong>, dan kelengkapan berkas resmi untuk diserahkan ke sekolah/madrasah tujuan.</div>
                            </div>
                        </div>

                        
                        <div class="sop-info-bar blue">
                            <i class="fa-regular fa-calendar-check fa-2x mr-3 text-primary" aria-hidden="true"></i>
                            <div>
                                <strong>KETERANGAN PENTING:</strong>
                                <span class="d-block text-muted" style="font-size: 13px;">Estimasi waktu dihitung dalam <strong>hari kerja</strong> dan dapat diselesaikan lebih cepat jika seluruh berkas persyaratan telah lengkap.</span>
                            </div>
                        </div>

                        
                        <div class="sop-footer-slogan blue">
                            <div>
                                <div class="sop-mantab-logo" style="color: #93c5fd;">MANTAB</div>
                                <div class="sop-mantab-sub">Moderate, Agamis, Nasionalis, Terdepan, Berprestasi</div>
                            </div>

                            
                            <div class="d-none d-md-block text-center" style="opacity: 0.9;">
                                <svg width="120" height="40" viewBox="0 0 120 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M60 4C54 14 50 16 46 22V38H74V22C70 16 66 14 60 4Z" fill="#BAE6FD"></path>
                                    <circle cx="60" cy="2" r="1.5" fill="#BAE6FD"></circle>
                                    <rect x="36" y="16" width="6" height="22" fill="#BAE6FD"></rect>
                                    <path d="M36 16L39 10L42 16H36Z" fill="#BAE6FD"></path>
                                    <rect x="78" y="16" width="6" height="22" fill="#BAE6FD"></rect>
                                    <path d="M78 16L81 10L84 16H78Z" fill="#BAE6FD"></path>
                                    <rect x="20" y="24" width="80" height="14" rx="2" fill="#1E3A8A" fill-opacity="0.3"></rect>
                                </svg>
                            </div>

                            <div class="sop-footer-quote">
                                <i class="fa-solid fa-circle-check mr-2 text-info" style="font-size: 20px;" aria-hidden="true"></i>
                                <span>Layanan administrasi mutasi keluar cepat, transparan, dan terintegrasi.</span>
                            </div>
                        </div>

                    </div>
                </div></div>
</section>
</template>
