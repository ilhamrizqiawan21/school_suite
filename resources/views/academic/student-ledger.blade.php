<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Buku Induk Peserta Didik</title>
    <style>
        @page { margin: 16mm 15mm; }
        body { color: #1e293b; font-family: DejaVu Sans, sans-serif; font-size: 9pt; line-height: 1.38; }
        .student-sheet { page-break-after: always; }
        .student-sheet:last-child { page-break-after: auto; }
        .section-keep { page-break-inside: avoid; }
        .letterhead { width: 100%; border-bottom: 2px solid #1e3a8a; padding-bottom: 8px; margin-bottom: 12px; }
        .letterhead td { border: none; padding: 0; vertical-align: middle; }
        .letterhead-title { font-size: 13pt; font-weight: bold; color: #0f172a; margin: 0; text-transform: uppercase; }
        .letterhead-sub { font-size: 8.5pt; color: #475569; margin: 2px 0 0; }
        .doc-header-table { width: 100%; margin-bottom: 10px; border: none; }
        .doc-header-table td { border: none; padding: 0; vertical-align: top; }
        .doc-title { font-size: 14pt; font-weight: bold; color: #0f172a; margin: 0 0 4px; text-transform: uppercase; letter-spacing: 0.3px; }
        .doc-meta { font-size: 9pt; color: #334155; margin: 0; }
        .photo-box { width: 28mm; height: 36mm; border: 1px solid #94a3b8; text-align: center; vertical-align: middle; font-size: 7.5pt; color: #64748b; background: #f8fafc; }
        .photo-box img { width: 28mm; height: 36mm; object-fit: cover; display: block; }
        h2 { margin: 10px 0 5px; padding: 4px 7px; border-left: 3px solid #1d4ed8; background: #eff6ff; font-size: 9.5pt; color: #1e3a8a; text-transform: uppercase; page-break-after: avoid; }
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.data-table td, table.data-table th { padding: 4px 6px; border: 1px solid #cbd5e1; text-align: left; vertical-align: top; font-size: 8.5pt; }
        table.data-table th { background: #f1f5f9; font-size: 8pt; font-weight: bold; color: #334155; }
        .label { width: 28%; color: #475569; font-weight: bold; background: #f8fafc; }
        .val { width: 22%; color: #0f172a; }
        .signature-table { width: 100%; margin-top: 16px; border: none; page-break-inside: avoid; }
        .signature-table td { border: none; padding: 0; vertical-align: top; font-size: 8.5pt; }
        .muted { color: #64748b; font-size: 7.5pt; }
        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; background: #e2e8f0; font-size: 7.5pt; font-weight: bold; }
    </style>
</head>
<body>
@php
    $studentRecords = $records ?? [[
        'student' => $student,
        'detail' => $detail ?? null,
        'documents' => $documents ?? collect(),
        'classHistory' => $classHistory ?? collect(),
        'lifecycleHistory' => $lifecycleHistory ?? collect(),
        'reportHistory' => $reportHistory ?? [],
        'photoDataUri' => $photoDataUri ?? null,
    ]];
    $statusMap = ['active' => 'Aktif', 'graduated' => 'Lulus', 'transferred' => 'Pindah', 'dropped_out' => 'Keluar'];
    $enrollmentMap = ['active' => 'Aktif', 'promoted' => 'Naik kelas', 'retained' => 'Tinggal kelas', 'graduated' => 'Lulus', 'transferred' => 'Pindah', 'withdrawn' => 'Keluar'];
@endphp

@foreach($studentRecords as $rec)
    @php
        $st = $rec['student'];
        $dt = $rec['detail'];
        $docs = $rec['documents'];
        $classes = $rec['classHistory'];
        $lifecycles = $rec['lifecycleHistory'] ?? collect();
        $reports = $rec['reportHistory'] ?? [];
        $photoUri = $rec['photoDataUri'] ?? null;
    @endphp
    <div class="student-sheet">
        <table class="letterhead">
            <tr>
                @if(!empty($schoolLogo))
                    <td style="width: 52px; padding-right: 10px;">
                        <img src="{{ $schoolLogo }}" alt="Logo Sekolah" style="width: 46px; height: 46px; object-fit: contain;">
                    </td>
                @endif
                <td>
                    @if(!empty($school['parent_institution']))
                        <p class="letterhead-sub" style="text-transform: uppercase; font-weight: bold;">{{ $school['parent_institution'] }}</p>
                    @endif
                    <p class="letterhead-title">{{ $school['name'] }}</p>
                    <p class="letterhead-sub">
                        @if(!empty($school['npsn'])) NPSN {{ $school['npsn'] }} @endif
                        @if(!empty($school['address'])) · {{ $school['address'] }} @endif
                        @if(!empty($school['phone'])) · Telp. {{ $school['phone'] }} @endif
                    </p>
                </td>
            </tr>
        </table>

        <table class="doc-header-table">
            <tr>
                <td>
                    <p class="doc-title">Lembar Buku Induk Peserta Didik</p>
                    <p class="doc-meta">
                        <strong>Nama Peserta Didik:</strong> {{ $st->name }}<br>
                        <strong>Nomor Induk Siswa (NIS) / NISN:</strong> {{ $st->nis }} / {{ $st->nisn ?: '—' }}<br>
                        <strong>Status Saat Ini:</strong> <span class="badge">{{ $statusMap[$st->status] ?? $st->status }}</span>
                    </p>
                </td>
                <td style="width: 30mm;" align="right">
                    <div class="photo-box">
                        @if($photoUri)
                            <img src="{{ $photoUri }}" alt="Foto {{ $st->name }}">
                        @else
                            <div style="padding-top: 13mm;">Pas Foto<br>3 x 4 cm</div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <h2>A. Keterangan Pribadi Peserta Didik</h2>
        <table class="data-table">
            <tr>
                <td class="label">1. Nama Lengkap</td>
                <td class="val">{{ $st->name }}</td>
                <td class="label">7. Agama</td>
                <td class="val">{{ $st->religion ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">2. Nama Panggilan</td>
                <td class="val">{{ $dt?->nickname ?: '—' }}</td>
                <td class="label">8. Kewarganegaraan</td>
                <td class="val">{{ $dt?->citizenship ?: 'WNI' }}</td>
            </tr>
            <tr>
                <td class="label">3. NIS / NISN</td>
                <td class="val">{{ $st->nis }} / {{ $st->nisn ?: '—' }}</td>
                <td class="label">9. Anak ke- / Jml Saudara</td>
                <td class="val">{{ $dt?->child_order ?? '—' }} / {{ $dt?->siblings_count !== null ? $dt->siblings_count.' orang' : '—' }}</td>
            </tr>
            <tr>
                <td class="label">4. NIK / Nomor KK</td>
                <td class="val">{{ $dt?->nik ?: '—' }} / {{ $dt?->kk_number ?: '—' }}</td>
                <td class="label">10. Status dalam Keluarga</td>
                <td class="val">{{ $dt?->family_status ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">5. Jenis Kelamin</td>
                <td class="val">{{ $st->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                <td class="label">11. Bahasa Sehari-hari</td>
                <td class="val">{{ $dt?->daily_language ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">6. Tempat, Tanggal Lahir</td>
                <td class="val" colspan="3">{{ $st->birth_place ?: '—' }}, {{ $st->birth_date?->format('d-m-Y') ?: '—' }}</td>
            </tr>
        </table>

        <h2>B. Keterangan Tempat Tinggal, Kesehatan &amp; Kegemaran</h2>
        <table class="data-table">
            <tr>
                <td class="label">1. Alamat Jalan</td>
                <td class="val" colspan="3">{{ $st->address ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">2. RT / RW</td>
                <td class="val">{{ $dt?->address_rt ?: '—' }} / {{ $dt?->address_rw ?: '—' }}</td>
                <td class="label">6. Golongan Darah</td>
                <td class="val">{{ $dt?->blood_type ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">3. Desa/Kel. &amp; Kecamatan</td>
                <td class="val">{{ $dt?->village ?: '—' }} / {{ $dt?->district ?: '—' }}</td>
                <td class="label">7. Tinggi / Berat Badan</td>
                <td class="val">{{ $dt?->height_cm ? $dt->height_cm.' cm' : '—' }} / {{ $dt?->weight_kg ? $dt->weight_kg.' kg' : '—' }}</td>
            </tr>
            <tr>
                <td class="label">4. Kab./Kota &amp; Kode Pos</td>
                <td class="val">{{ $dt?->city ?: '—' }} {{ $dt?->postal_code ? '('.$dt->postal_code.')' : '' }}</td>
                <td class="label">8. Riwayat Kesehatan</td>
                <td class="val">{{ $dt?->health_history ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">5. Tinggal Bersama / Jarak</td>
                <td class="val">{{ $dt?->living_with ?: '—' }} ({{ $dt?->distance_km !== null ? $dt->distance_km.' km' : '—' }})</td>
                <td class="label">9. Kegemaran (Olahraga/Seni)</td>
                <td class="val">{{ collect([$dt?->sports_hobby, $dt?->arts_hobby])->filter()->implode(' / ') ?: '—' }}</td>
            </tr>
        </table>

        <h2>C. Keterangan Orang Tua Kandung / Wali</h2>
        <table class="data-table">
            <tr>
                <td class="label">1. Nama Ayah / NIK</td>
                <td class="val">{{ $dt?->father_name ?: '—' }} ({{ $dt?->father_nik ?: '—' }})</td>
                <td class="label">4. Nama Ibu / NIK</td>
                <td class="val">{{ $dt?->mother_name ?: '—' }} ({{ $dt?->mother_nik ?: '—' }})</td>
            </tr>
            <tr>
                <td class="label">2. Pendidikan &amp; Pekerjaan Ayah</td>
                <td class="val">{{ $dt?->father_education ?: '—' }} / {{ $dt?->father_occupation ?: '—' }}</td>
                <td class="label">5. Pendidikan &amp; Pekerjaan Ibu</td>
                <td class="val">{{ $dt?->mother_education ?: '—' }} / {{ $dt?->mother_occupation ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">3. Penghasilan &amp; HP Ayah</td>
                <td class="val">{{ $dt?->father_income ?: '—' }} / {{ $dt?->father_phone ?: '—' }}</td>
                <td class="label">6. Penghasilan &amp; HP Ibu</td>
                <td class="val">{{ $dt?->mother_income ?: '—' }} / {{ $dt?->mother_phone ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">7. Nama Wali &amp; Hubungan</td>
                <td class="val">{{ $st->parent_name ?: '—' }} ({{ $dt?->guardian_relation ?: '—' }})</td>
                <td class="label">8. Pekerjaan &amp; Kontak Wali</td>
                <td class="val">{{ $dt?->guardian_occupation ?: '—' }} / {{ $st->parent_phone ?: '—' }}</td>
            </tr>
            @if(!empty($dt?->guardian_address))
                <tr>
                    <td class="label">9. Alamat Wali</td>
                    <td class="val" colspan="3">{{ $dt->guardian_address }}</td>
                </tr>
            @endif
        </table>

        <h2>D. Keterangan Pendidikan Sebelumnya, Penerimaan &amp; Tamat Belajar</h2>
        <table class="data-table">
            <tr>
                <td class="label">1. Sekolah Asal</td>
                <td class="val">{{ $dt?->previous_school ?: '—' }}</td>
                <td class="label">4. Tanggal Diterima / Kelas</td>
                <td class="val">{{ $dt?->accepted_date?->format('d-m-Y') ?: '—' }} / {{ $dt?->accepted_grade ?: '—' }} ({{ $dt?->admission_type ?: '—' }})</td>
            </tr>
            <tr>
                <td class="label">2. No. &amp; Tgl. Ijazah Asal</td>
                <td class="val">{{ $dt?->diploma_number ?: '—' }} ({{ $dt?->diploma_date?->format('d-m-Y') ?: '—' }})</td>
                <td class="label">5. Tgl. Lulus/Keluar &amp; Ijazah</td>
                <td class="val">{{ $dt?->graduation_date?->format('d-m-Y') ?: '—' }} / {{ $dt?->graduation_diploma_number ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">3. Lama Belajar Asal</td>
                <td class="val">{{ $dt?->study_duration_years ?: '—' }}</td>
                <td class="label">6. Melanjutkan ke / Alasan</td>
                <td class="val">{{ collect([$dt?->continuing_to, $dt?->leaving_reason])->filter()->implode(' — ') ?: '—' }}</td>
            </tr>
        </table>

        <h2>E. Riwayat Penempatan Kelas &amp; Mutasi Akademik</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tahun Ajaran</th>
                    <th>Semester</th>
                    <th>Rombongan Belajar</th>
                    <th>No. Absen</th>
                    <th>Status Penempatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classes as $enrollment)
                    <tr>
                        <td>{{ $enrollment->term?->academicYear?->name ?: '—' }}</td>
                        <td>{{ $enrollment->term?->name ?: '—' }}</td>
                        <td>{{ $enrollment->classGroup?->name ?: '—' }}</td>
                        <td>{{ $enrollment->roll_number ?? '—' }}</td>
                        <td>{{ $enrollmentMap[$enrollment->status] ?? $enrollment->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada riwayat penempatan kelas.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if($lifecycles->isNotEmpty())
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal Efektif</th>
                        <th>Peristiwa Siklus</th>
                        <th>Kelas Asal → Tujuan</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lifecycles as $ev)
                        <tr>
                            <td>{{ $ev->effective_date?->format('d-m-Y') ?: '—' }}</td>
                            <td>{{ $enrollmentMap[$ev->action] ?? $ev->action }}</td>
                            <td>{{ $ev->sourceEnrollment?->classGroup?->name ?: '—' }} → {{ $ev->targetEnrollment?->classGroup?->name ?: '—' }}</td>
                            <td>{{ $ev->notes ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h2>F. Arsip Dokumen Pribadi</h2>
        <table class="data-table">
            <thead><tr><th>Jenis Dokumen</th><th>Nama Berkas</th><th>Ukuran</th><th>Tanggal Unggah</th></tr></thead>
            <tbody>
                @forelse($docs as $document)
                    <tr>
                        <td>{{ $document->document_type }}</td>
                        <td>{{ $document->original_name }}</td>
                        <td>{{ number_format($document->size_bytes / 1024, 0, ',', '.') }} KB</td>
                        <td>{{ $document->created_at?->format('d-m-Y') ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Belum ada dokumen.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div>
            <h2>G. Rekapitulasi Capaian Hasil Belajar (Rapor Terbit per Semester)</h2>
            <p class="muted" style="margin: 0 0 8px;">
                Data bersumber dari snapshot rapor resmi yang telah diterbitkan (Immutable · NIS {{ $st->nis }} · {{ $st->name }}).
            </p>
            @forelse($reports as $rep)
                <table class="data-table section-keep" style="margin-bottom: 10px;">
                    <thead>
                        <tr>
                            <th colspan="5" style="background: #dbeafe; color: #1e3a8a;">
                                Tahun Ajaran {{ $rep['academic_year'] }} · {{ $rep['term_name'] }} · Kelas {{ $rep['class_name'] }}
                                @if(!empty($rep['published_at'])) (Terbit: {{ $rep['published_at'] }}) @endif
                            </th>
                        </tr>
                        <tr>
                            <th style="width: 30px;">No</th>
                            <th>Mata Pelajaran</th>
                            <th style="width: 70px;">KKTP</th>
                            <th style="width: 80px;">Nilai Akhir</th>
                            <th style="width: 70px;">Predikat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rep['grades'] as $idx => $grade)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>{{ $grade['subject_name'] ?? '—' }}</td>
                                <td>{{ $grade['passing_grade'] ?? '—' }}</td>
                                <td><strong>{{ $grade['final_score'] ?? '—' }}</strong></td>
                                <td>{{ $grade['predicate'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">Tidak ada rincian mata pelajaran pada snapshot ini.</td></tr>
                        @endforelse
                        <tr>
                            <td colspan="5" style="background: #f8fafc;">
                                <strong>Kehadiran Semester:</strong>
                                Hadir: {{ $rep['attendance']['present'] ?? 0 }} hari ·
                                Sakit: {{ $rep['attendance']['sick'] ?? 0 }} hari ·
                                Izin: {{ $rep['attendance']['permitted'] ?? 0 }} hari ·
                                Tanpa Keterangan: {{ $rep['attendance']['absent'] ?? 0 }} hari
                            </td>
                        </tr>
                    </tbody>
                </table>
            @empty
                <table class="data-table">
                    <tbody>
                        <tr><td>Belum ada snapshot rapor terbit untuk peserta didik ini.</td></tr>
                    </tbody>
                </table>
            @endforelse

            <table class="signature-table">
                <tr>
                    <td style="width: 60%;">
                        <p class="muted">
                            Dicetak dari School Suite pada {{ now()->locale('id')->translatedFormat('d F Y') }}.<br>
                            Dokumen Buku Induk Peserta Didik bersifat rahasia/internal sekolah.
                        </p>
                    </td>
                    <td style="width: 40%;">
                        <p style="margin: 0 0 48px;">
                            {{ !empty($school['city']) ? $school['city'].', ' : '' }}{{ now()->locale('id')->translatedFormat('d F Y') }}<br>
                            Kepala {{ $school['name'] }}
                        </p>
                        <p style="margin: 0; font-weight: bold; text-decoration: underline;">
                            {{ !empty($school['principal_name']) ? $school['principal_name'] : '................................................' }}
                        </p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
@endforeach
</body>
</html>
