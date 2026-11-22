<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        schoolName?: string;
    }>(),
    {
        schoolName: 'Sekolah',
    },
);

const displaySchoolName = computed(() => props.schoolName.toUpperCase());
</script>

<template>
<section class="reference-legalisir">
<div class="sop-poster mb-4">
                            
                            <div class="sop-header-banner">
                                <div class="sop-title-badge">STANDAR OPERASIONAL PROSEDUR</div>
                                <h2 class="sop-main-title">LEGALISIR IJAZAH &amp; RAPOR</h2>
                                <div class="sop-quote-box">
                                    <span class="quote-mark">“</span> Layanan Legalisasi Ijazah Cepat, Tepat, Transparan, dan Akuntabel <span class="quote-mark">”</span>
                                </div>
                            </div>

                            
                            <div class="sop-body">
                                
                                
                                <div class="sop-step-row">
                                    <div class="d-flex align-items-center sop-step-row-top">
                                        <div class="sop-pill-num">01</div>
                                        <div class="sop-icon-wrap">
                                            
                                            <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <rect x="14" y="24" width="72" height="64" rx="6" fill="#F8FAFC" stroke="#0284C7" stroke-width="2.5"></rect>
                                                <rect x="22" y="16" width="56" height="12" rx="3" fill="#0284C7"></rect>
                                                <text x="50" y="25" font-family="'Plus Jakarta Sans', Arial, sans-serif" font-size="7.5" font-weight="800" fill="#FFFFFF" text-anchor="middle" letter-spacing="0.5">TATA USAHA</text>
                                                
                                                <rect x="22" y="36" width="22" height="20" rx="3" fill="#E0F2FE" stroke="#BAE6FD" stroke-width="1.5"></rect>
                                                <line x1="33" y1="36" x2="33" y2="56" stroke="#0284C7" stroke-width="1"></line>
                                                <line x1="22" y1="46" x2="44" y2="46" stroke="#0284C7" stroke-width="1"></line>
                                                
                                                <circle cx="68" cy="46" r="10" fill="#FEF08A" stroke="#D97706" stroke-width="1.5"></circle>
                                                <polyline points="68,40 68,46 72,46" stroke="#D97706" stroke-width="1.5" stroke-linecap="round"></polyline>
                                                
                                                <rect x="18" y="68" width="64" height="16" rx="2" fill="#CBD5E1"></rect>
                                                <rect x="42" y="62" width="16" height="12" rx="2" fill="#0369A1"></rect>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="sop-content-wrap">
                                        <div class="font-weight-bold mb-1" style="color: #0369a1; font-size: 14.5px;">Kedatangan Pemohon ke Ruang Tata Usaha</div>
                                        <div>Pemohon datang langsung ke ruang <strong>Tata Usaha (TU) {{ displaySchoolName }}</strong> pada hari dan jam kerja yang telah ditentukan, atau dapat mengirimkan <strong>perwakilan resmi</strong>.</div>
                                    </div>
                                </div>

                                
                                <div class="sop-step-row">
                                    <div class="d-flex align-items-center sop-step-row-top">
                                        <div class="sop-pill-num">02</div>
                                        <div class="sop-icon-wrap">
                                            
                                            <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                
                                                <rect x="22" y="14" width="56" height="72" rx="4" fill="#FFFFFF" stroke="#0284C7" stroke-width="2.5"></rect>
                                                <rect x="28" y="20" width="44" height="60" rx="2" fill="#F0F9FF"></rect>
                                                
                                                <line x1="34" y1="28" x2="66" y2="28" stroke="#0284C7" stroke-width="2.5" stroke-linecap="round"></line>
                                                <line x1="34" y1="36" x2="60" y2="36" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                                <line x1="34" y1="43" x2="66" y2="43" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                                
                                                <g transform="translate(52, 52)">
                                                    <circle cx="16" cy="16" r="14" fill="#EF4444" stroke="#DC2626" stroke-width="1.5"></circle>
                                                    <circle cx="16" cy="16" r="10" fill="#FEE2E2"></circle>
                                                    <path d="M12 16L15 19L20 13" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                                </g>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="sop-content-wrap">
                                        <div class="font-weight-bold mb-1" style="color: #0369a1; font-size: 14.5px;">Penyerahan Dokumen Persyaratan</div>
                                        <div class="mb-1">Pemohon menyerahkan dokumen kelengkapan sesuai ketentuan:</div>
                                        <div class="sop-sub-item">
                                            <span class="sop-badge-num">1</span>
                                            <span><strong>Jika Hadir Sendiri:</strong> Membawa <strong>Ijazah Asli</strong> dan salinan <strong>Fotokopi Ijazah</strong> yang akan dilegalisir (bersih, jelas, dan terbaca).</span>
                                        </div>
                                        <div class="sop-sub-item">
                                            <span class="sop-badge-num">2</span>
                                            <span><strong>Jika Diwakilkan:</strong> Membawa <strong>Ijazah Asli</strong>, <strong>Fotokopi Ijazah</strong>, <strong>Surat Kuasa bermaterai</strong>, dan <strong>Surat SPTJM</strong>.</span>
                                        </div>
                                    </div>
                                </div>

                                
                                <div class="sop-step-row">
                                    <div class="d-flex align-items-center sop-step-row-top">
                                        <div class="sop-pill-num">03</div>
                                        <div class="sop-icon-wrap">
                                            
                                            <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                
                                                <rect x="18" y="24" width="46" height="58" rx="4" fill="#FFFFFF" stroke="#64748B" stroke-width="2"></rect>
                                                <rect x="22" y="30" width="38" height="6" rx="1" fill="#0284C7"></rect>
                                                <line x1="24" y1="44" x2="56" y2="44" stroke="#CBD5E1" stroke-width="2" stroke-linecap="round"></line>
                                                <line x1="24" y1="52" x2="52" y2="52" stroke="#CBD5E1" stroke-width="2" stroke-linecap="round"></line>
                                                <line x1="24" y1="60" x2="48" y2="60" stroke="#CBD5E1" stroke-width="2" stroke-linecap="round"></line>
                                                
                                                <g transform="translate(42, 38)">
                                                    <circle cx="22" cy="22" r="18" fill="#FEF08A" stroke="#D97706" stroke-width="3"></circle>
                                                    <circle cx="22" cy="22" r="12" fill="#FFFFFF" opacity="0.6"></circle>
                                                    <path d="M16 22L20 26L28 17" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                    <line x1="35" y1="35" x2="50" y2="50" stroke="#78350F" stroke-width="5" stroke-linecap="round"></line>
                                                </g>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="sop-content-wrap">
                                        <div class="font-weight-bold mb-1" style="color: #0369a1; font-size: 14.5px;">Verifikasi Dokumen oleh Petugas TU</div>
                                        <div>Petugas Tata Usaha memeriksa dan memverifikasi keabsahan fisik Ijazah Asli dengan salinan fotokopi, serta mencocokkan data pada <strong>Buku Induk Siswa / Database Sekolah</strong>.</div>
                                    </div>
                                    <div class="sop-estimasi-box">
                                        <div class="sop-estimasi-head">
                                            <i class="fa-regular fa-clock mr-1" aria-hidden="true"></i> Estimasi Waktu
                                            <span class="sop-days">10-15 MENIT</span>
                                        </div>
                                        <div class="sop-estimasi-foot">Layanan Langsung</div>
                                    </div>
                                </div>

                                
                                <div class="sop-step-row">
                                    <div class="d-flex align-items-center sop-step-row-top">
                                        <div class="sop-pill-num">04</div>
                                        <div class="sop-icon-wrap">
                                            
                                            <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                
                                                <rect x="18" y="20" width="60" height="66" rx="4" fill="#F8FAFC" stroke="#334155" stroke-width="2.5"></rect>
                                                <rect x="18" y="20" width="12" height="66" rx="2" fill="#0369A1"></rect>
                                                <circle cx="24" cy="32" r="2.5" fill="#FEF08A"></circle>
                                                <circle cx="24" cy="50" r="2.5" fill="#FEF08A"></circle>
                                                <circle cx="24" cy="68" r="2.5" fill="#FEF08A"></circle>
                                                
                                                <line x1="36" y1="32" x2="70" y2="32" stroke="#94A3B8" stroke-width="2.5" stroke-linecap="round"></line>
                                                <line x1="36" y1="42" x2="66" y2="42" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                                <line x1="36" y1="52" x2="70" y2="52" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                                <line x1="36" y1="62" x2="60" y2="62" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                                
                                                <g transform="translate(60, 48) rotate(40)">
                                                    <rect x="0" y="0" width="8" height="32" rx="1.5" fill="#F59E0B" stroke="#B45309" stroke-width="1"></rect>
                                                    <path d="M0 32L4 38L8 32H0Z" fill="#1E293B"></path>
                                                </g>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="sop-content-wrap">
                                        <div class="font-weight-bold mb-1" style="color: #0369a1; font-size: 14.5px;">Pencatatan Permohonan di Buku Register</div>
                                        <div>Setelah dokumen dinyatakan lengkap dan valid, petugas TU mencatat permohonan legalisir ke dalam <strong>Buku Register Pelayanan Legalisir Ijazah</strong> untuk penomoran dan arsip dokumentasi resmi.</div>
                                    </div>
                                </div>

                                
                                <div class="sop-step-row">
                                    <div class="d-flex align-items-center sop-step-row-top">
                                        <div class="sop-pill-num">05</div>
                                        <div class="sop-icon-wrap">
                                            
                                            <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                
                                                <rect x="18" y="16" width="54" height="68" rx="4" fill="#FFFFFF" stroke="#0284C7" stroke-width="2"></rect>
                                                <line x1="26" y1="26" x2="60" y2="26" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                                <line x1="26" y1="34" x2="56" y2="34" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"></line>
                                                
                                                <path d="M26 56C30 52 32 60 36 54C40 50 42 58 48 54" stroke="#0369A1" stroke-width="2.5" stroke-linecap="round"></path>
                                                
                                                <g transform="translate(48, 42)">
                                                    
                                                    <ellipse cx="22" cy="10" rx="10" ry="6" fill="#78350F"></ellipse>
                                                    <path d="M16 10V24H28V10H16Z" fill="#92400E"></path>
                                                    
                                                    <rect x="6" y="24" width="32" height="12" rx="3" fill="#1E293B"></rect>
                                                    
                                                    <ellipse cx="22" cy="42" rx="16" ry="6" fill="#3B82F6" opacity="0.85"></ellipse>
                                                    <ellipse cx="22" cy="42" rx="12" ry="4" stroke="#FFFFFF" stroke-width="1"></ellipse>
                                                </g>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="sop-content-wrap">
                                        <div class="font-weight-bold mb-1" style="color: #0369a1; font-size: 14.5px;">Proses Legalisir Resmi</div>
                                        <div>Dokumen salinan fotokopi ijazah dibubuhi <strong>cap/stempel resmi basah {{ displaySchoolName }}</strong> dan ditandatangani oleh <strong>Kepala Madrasah/Sekolah</strong> atau pejabat yang berwenang.</div>
                                    </div>
                                    <div class="sop-estimasi-box">
                                        <div class="sop-estimasi-head">
                                            <i class="fa-regular fa-calendar-check mr-1" aria-hidden="true"></i> Estimasi Waktu
                                            <span class="sop-days">1 HARI</span>
                                        </div>
                                        <div class="sop-estimasi-foot">Maksimal / Hari Kerja</div>
                                    </div>
                                </div>

                                
                                <div class="sop-step-row">
                                    <div class="d-flex align-items-center sop-step-row-top">
                                        <div class="sop-pill-num">06</div>
                                        <div class="sop-icon-wrap">
                                            
                                            <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                
                                                <rect x="18" y="22" width="46" height="58" rx="3" fill="#0284C7" stroke="#0369A1" stroke-width="2"></rect>
                                                <rect x="22" y="26" width="8" height="50" fill="#0369A1"></rect>
                                                <rect x="34" y="34" width="22" height="14" rx="2" fill="#FEF08A"></rect>
                                                <line x1="38" y1="39" x2="52" y2="39" stroke="#0284C7" stroke-width="1.5"></line>
                                                
                                                <rect x="42" y="16" width="44" height="60" rx="3" fill="#FFFFFF" stroke="#10B981" stroke-width="2"></rect>
                                                <path d="M42 20L64 36L86 20" stroke="#10B981" stroke-width="2"></path>
                                                
                                                <circle cx="64" cy="46" r="9" fill="#10B981"></circle>
                                                <path d="M60 46L63 49L69 43" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="sop-content-wrap">
                                        <div class="font-weight-bold mb-1" style="color: #15803d; font-size: 14.5px;">Pengambilan Hasil Legalisir Sah</div>
                                        <div>Pemohon atau perwakilan mengambil kembali <strong>Ijazah Asli</strong> beserta dokumen fotokopi yang telah selesai dilegalisir secara sah sesuai dengan jumlah permohonan yang diserahkan.</div>
                                    </div>
                                </div>

                                
                                <div class="sop-info-bar mb-0">
                                    <i class="fa-solid fa-shield-halved fa-2x mr-3 text-success" aria-hidden="true"></i>
                                    <div>
                                        <strong>PELAYANAN BEBAS BIAYA (GRATIS):</strong>
                                        <span class="d-block" style="font-size: 12.5px; opacity: 0.9;">Seluruh proses pengesahan &amp; legalisir ijazah di {{ displaySchoolName }} adalah <strong>Rp 0,- (Bebas Pungli)</strong>.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
</section>
</template>
