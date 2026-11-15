<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rapor {{ count($reports ?? [$report]) > 1 ? 'Kelas' : ($reports[0]['student']['name'] ?? $report['student']['name']) }}</title>
    <style>
        @page { size: A4 portrait; margin: 14mm 14mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; color: #0f172a; line-height: 1.4; }
        h1, h2, h3, p { margin: 0; }
        .report-bundle { page-break-after: always; }
        .report-bundle:last-child { page-break-after: auto; }
        .page-break { page-break-before: always; }
        .cover-page {
            text-align: center;
            padding: 28mm 10mm 16mm;
            border: 1.5px solid #1e3a8a;
            min-height: 235mm;
            position: relative;
        }
        .cover-logo { width: 82px; height: 82px; object-fit: contain; margin-bottom: 14px; }
        .cover-kicker { font-size: 10pt; letter-spacing: 0.8px; text-transform: uppercase; color: #334155; font-weight: bold; }
        .cover-title { font-size: 18pt; font-weight: bold; text-transform: uppercase; color: #0f172a; margin: 14px 0 6px; letter-spacing: 0.5px; }
        .cover-subtitle { font-size: 11pt; color: #1e3a8a; font-weight: bold; text-transform: uppercase; margin-bottom: 26mm; }
        .cover-box {
            width: 82%;
            margin: 0 auto 24mm;
            border: 1px solid #334155;
            padding: 12px 16px;
            text-align: left;
            background: #f8fafc;
        }
        .cover-box-label { font-size: 8.5pt; color: #475569; text-transform: uppercase; font-weight: bold; margin-bottom: 4px; }
        .cover-box-value { font-size: 12pt; font-weight: bold; color: #0f172a; }
        .cover-footer { position: absolute; left: 10mm; right: 10mm; bottom: 16mm; text-align: center; }
        .cover-footer p { font-size: 10pt; font-weight: bold; text-transform: uppercase; color: #0f172a; }
        .cover-footer small { display: block; margin-top: 4px; font-size: 8.5pt; color: #475569; }
        .section-heading-center { text-align: center; font-size: 12pt; font-weight: bold; text-transform: uppercase; margin: 4px 0 12px; color: #0f172a; }
        .bio-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .bio-table td { padding: 3.5px 5px; vertical-align: top; font-size: 9pt; }
        .bio-table .bio-no { width: 22px; color: #475569; }
        .bio-table .bio-label { width: 32%; color: #334155; }
        .bio-table .bio-sep { width: 10px; text-align: center; }
        .photo-frame { width: 28mm; height: 36mm; border: 1px solid #64748b; text-align: center; font-size: 8pt; color: #64748b; background: #f8fafc; }
        .photo-frame img { width: 28mm; height: 36mm; object-fit: cover; display: block; }
        .letterhead { width: 100%; border-bottom: 2px solid #1e3a8a; padding-bottom: 8px; margin-bottom: 8px; }
        .letterhead td { border: none; padding: 0; vertical-align: middle; }
        .letterhead h2 { font-size: 13pt; text-transform: uppercase; color: #0f172a; }
        .letterhead p { font-size: 8.5pt; color: #334155; }
        .logo { width: 48px; height: 48px; object-fit: contain; }
        .identity { width: 100%; border-bottom: 2px double #64748b; padding: 6px 0 8px; margin-bottom: 10px; }
        .identity td { padding: 2px 0; vertical-align: top; font-size: 8.8pt; }
        .identity .label { width: 19%; color: #334155; }
        .identity .value { width: 34%; }
        h1.doc-title { text-align: center; font-size: 11.5pt; text-transform: uppercase; margin: 6px 0 10px; }
        h3 { font-size: 9.5pt; font-weight: bold; margin: 10px 0 4px; color: #0f172a; page-break-after: avoid; }
        .data { width: 100%; border-collapse: collapse; border: 1px solid #64748b; margin-bottom: 8px; }
        .data th { background: #f1f5f9; font-size: 8.5pt; text-align: left; font-weight: bold; color: #0f172a; }
        .data td, .data th { padding: 5px 6px; border: 1px solid #64748b; font-size: 8.7pt; }
        .data td { vertical-align: top; }
        .data .center { text-align: center; }
        .data .subject { width: 28%; }
        .data .score { width: 11%; text-align: center; font-weight: bold; }
        .competency { font-size: 8.5pt; line-height: 1.45; }
        .total td { font-weight: bold; background: #f8fafc; }
        .number { width: 28px; text-align: center; }
        tr, .keep-together { page-break-inside: avoid; }
        thead { display: table-header-group; }
        .note, .parent-response, .decision-box { padding: 7px 9px; border: 1px solid #64748b; min-height: 28px; margin-bottom: 8px; white-space: pre-line; font-size: 8.8pt; }
        .parent-response { min-height: 38px; }
        .decision-box { background: #f8fafc; font-weight: bold; }
        .signatures { width: 100%; margin-top: 12px; text-align: left; page-break-inside: avoid; }
        .signatures td { width: 50%; padding: 0 12px 0 0; vertical-align: top; font-size: 9pt; }
        .signature-name { margin-top: 42px; font-weight: bold; text-decoration: underline; }
        .signature-nip { font-size: 8.2pt; color: #334155; }
        .principal { text-align: center; padding-top: 16px !important; }
        .footer-meta { margin-top: 10px; font-size: 7.5pt; color: #475569; border-top: 1px dashed #cbd5e1; padding-top: 4px; }
    </style>
</head>
<body>
@php
    $reportList = $reports ?? [$report];
    $isIdentityOnly = $identityOnly ?? false;
    $isGradesOnly = ! $isIdentityOnly && ($gradesOnly ?? false);
    $withCover = ($isGradesOnly || $isIdentityOnly) ? false : ($includeCover ?? true);
    $genderMap = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
@endphp

@foreach($reportList as $rep)
    @php
        $isSts = ($rep['report_mode'] ?? 'sas') === 'sts';
        $reportHeading = $isSts ? 'Laporan Hasil Belajar Tengah Semester (STS)' : 'Laporan Hasil Belajar (Rapor)';
        $place = $rep['notes']['report_place'] ?? null;
        $dateText = $rep['notes']['report_date'] ?? ($rep['published_at'] ?? now()->locale('id')->translatedFormat('d F Y'));
        $placeDate = trim(($place ? $place.', ' : '').$dateText);
        $totalScore = collect($rep['grades'])->whereNotNull('final_score')->isEmpty()
            ? null
            : collect($rep['grades'])->sum('final_score');
    @endphp

    <div class="report-bundle">
        @if($withCover)
            <!-- Halaman 1: Sampul Rapor (e-Rapor Kemdikdasmen) -->
            <div class="cover-page">
                @if(!empty($rep['school']['logo_data_uri']))
                    <img class="cover-logo" src="{{ $rep['school']['logo_data_uri'] }}" alt="Logo sekolah">
                @endif
                <p class="cover-kicker">Kementerian Pendidikan Dasar dan Menengah</p>
                <p class="cover-title">{{ $reportHeading }}</p>
                <p class="cover-subtitle">Peserta Didik {{ $rep['school']['level'] ?? 'Sekolah' }}</p>

                <div class="cover-box">
                    <div class="cover-box-label">Nama Peserta Didik</div>
                    <div class="cover-box-value">{{ $rep['student']['name'] }}</div>
                </div>

                <div class="cover-box" style="margin-bottom: 0;">
                    <div class="cover-box-label">NIS / NISN</div>
                    <div class="cover-box-value">{{ $rep['student']['nis'] }} / {{ $rep['student']['nisn'] ?: '—' }}</div>
                </div>

                <div class="cover-footer">
                    <p>{{ $rep['school']['legal_name'] ?? $rep['school']['name'] }}</p>
                    <small>NPSN {{ $rep['school']['npsn'] }} · {{ $rep['school']['address'] }}</small>
                </div>
            </div>
        @endif

        @if($withCover || $isIdentityOnly)
            <!-- Halaman 2: Identitas Sekolah & Peserta Didik -->
            <div class="{{ $withCover ? 'page-break' : '' }}">
                <p class="section-heading-center">Identitas Sekolah &amp; Peserta Didik</p>

                <h3>A. Keterangan Sekolah</h3>
                <table class="bio-table">
                    <tr><td class="bio-label">Nama Sekolah</td><td class="bio-sep">:</td><td><strong>{{ $rep['school']['legal_name'] ?? $rep['school']['name'] }}</strong></td></tr>
                    <tr><td class="bio-label">NPSN / Status / Akreditasi</td><td class="bio-sep">:</td><td>{{ $rep['school']['npsn'] }} / {{ $rep['school']['school_status'] ?? '—' }} / {{ $rep['school']['accreditation'] ?? '—' }}</td></tr>
                    <tr><td class="bio-label">Alamat Sekolah</td><td class="bio-sep">:</td><td>{{ $rep['school']['address'] }}</td></tr>
                    <tr><td class="bio-label">Telepon / Surel / Laman</td><td class="bio-sep">:</td><td>{{ $rep['school']['phone'] ?? '—' }} / {{ $rep['school']['email'] ?? '—' }} / {{ $rep['school']['website'] ?? '—' }}</td></tr>
                </table>

                <h3>B. Keterangan Tentang Peserta Didik</h3>
                <table class="bio-table">
                    <tr><td class="bio-no">1.</td><td class="bio-label">Nama Lengkap Peserta Didik</td><td class="bio-sep">:</td><td><strong>{{ $rep['student']['name'] }}</strong></td></tr>
                    <tr><td class="bio-no">2.</td><td class="bio-label">NIS / NISN</td><td class="bio-sep">:</td><td>{{ $rep['student']['nis'] }} / {{ $rep['student']['nisn'] ?: '—' }}</td></tr>
                    <tr><td class="bio-no">3.</td><td class="bio-label">Tempat, Tanggal Lahir</td><td class="bio-sep">:</td><td>{{ $rep['student']['birth_place'] ?: '—' }}{{ !empty($rep['student']['birth_date']) ? ', '.\Carbon\Carbon::parse($rep['student']['birth_date'])->locale('id')->translatedFormat('d F Y') : '' }}</td></tr>
                    <tr><td class="bio-no">4.</td><td class="bio-label">Jenis Kelamin</td><td class="bio-sep">:</td><td>{{ $genderMap[$rep['student']['gender'] ?? ''] ?? ($rep['student']['gender'] ?: '—') }}</td></tr>
                    <tr><td class="bio-no">5.</td><td class="bio-label">Agama</td><td class="bio-sep">:</td><td>{{ $rep['student']['religion'] ?: '—' }}</td></tr>
                    <tr><td class="bio-no">6.</td><td class="bio-label">Alamat Peserta Didik</td><td class="bio-sep">:</td><td>{{ $rep['student']['address'] ?: '—' }}</td></tr>
                    <tr><td class="bio-no">7.</td><td class="bio-label">Sekolah Asal</td><td class="bio-sep">:</td><td>{{ $rep['student']['previous_school'] ?: '—' }}</td></tr>
                    <tr><td class="bio-no">8.</td><td class="bio-label">Diterima di Sekolah Ini</td><td class="bio-sep">:</td><td>Kelas {{ $rep['student']['accepted_grade'] ?: $rep['class_group']['name'] }}{{ !empty($rep['student']['accepted_date']) ? ' · Tanggal '.\Carbon\Carbon::parse($rep['student']['accepted_date'])->locale('id')->translatedFormat('d F Y') : '' }}</td></tr>
                    <tr><td class="bio-no">9.</td><td class="bio-label">Nama Orang Tua (Ayah / Ibu)</td><td class="bio-sep">:</td><td>{{ $rep['student']['father_name'] ?: ($rep['student']['parent_name'] ?: '—') }} / {{ $rep['student']['mother_name'] ?: '—' }}</td></tr>
                    <tr><td class="bio-no">10.</td><td class="bio-label">Nama Wali &amp; Telepon</td><td class="bio-sep">:</td><td>{{ $rep['student']['guardian_name'] ?: '—' }} · {{ $rep['student']['parent_phone'] ?: '—' }}</td></tr>
                </table>

                <table class="signatures" style="margin-top: 18px;">
                    <tr>
                        <td style="width: 35%;">
                            <div class="photo-frame">
                                @if(!empty($rep['student']['photo_data_uri']))
                                    <img src="{{ $rep['student']['photo_data_uri'] }}" alt="Foto {{ $rep['student']['name'] }}">
                                @else
                                    <div style="padding-top: 13mm;">Pas Foto<br>3 x 4 cm</div>
                                @endif
                            </div>
                        </td>
                        <td style="width: 65%; padding-left: 20px;">
                            {{ $placeDate }}<br>
                            Kepala Sekolah,
                            <div class="signature-name">{{ $rep['school']['principal_name'] ?: '................................................' }}</div>
                            @if(!empty($rep['school']['principal_nip']))
                                <div class="signature-nip">NIP. {{ $rep['school']['principal_nip'] }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        @if(! $isIdentityOnly)
        <!-- Halaman 3: Capaian Hasil Belajar & Pelengkap -->
        <div class="{{ $withCover ? 'page-break' : '' }}">
            <table class="letterhead">
                <tr>
                    @if(!empty($rep['school']['logo_data_uri']))
                        <td style="width: 56px; padding-right: 10px;"><img class="logo" src="{{ $rep['school']['logo_data_uri'] }}" alt="Logo sekolah"></td>
                    @endif
                    <td>
                        <h2>{{ $rep['school']['name'] }}</h2>
                        <p>{{ $rep['school']['address'] }} · NPSN {{ $rep['school']['npsn'] }}</p>
                    </td>
                </tr>
            </table>

            <table class="identity">
                <tr>
                    <td class="label">Nama Peserta Didik</td>
                    <td class="value">: <strong>{{ $rep['student']['name'] }}</strong></td>
                    <td class="label">Kelas / Fase</td>
                    <td>: {{ $rep['class_group']['name'] }} / Fase {{ $rep['class_group']['phase'] ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">NIS / NISN</td>
                    <td>: {{ $rep['student']['nis'] }} / {{ $rep['student']['nisn'] ?: '—' }}</td>
                    <td class="label">Semester</td>
                    <td>: {{ $rep['term']['name'] }}</td>
                </tr>
                <tr>
                    <td class="label">Sekolah</td>
                    <td>: {{ $rep['school']['name'] }}</td>
                    <td class="label">Tahun Pelajaran</td>
                    <td>: {{ $rep['term']['academic_year_name'] }}</td>
                </tr>
            </table>

            <h1 class="doc-title">{{ $reportHeading }}</h1>

            <table class="data">
                <thead>
                    <tr>
                        <th class="number">No.</th>
                        <th class="subject">Mata Pelajaran</th>
                        @if($isSts)
                            <th style="width: 24%;">Rincian Komponen (Formatif/STS)</th>
                        @endif
                        <th class="score">{{ $isSts ? 'Nilai STS' : 'Nilai Akhir' }}</th>
                        <th class="center" style="width: 8%;">Predikat</th>
                        <th>{{ $isSts ? 'Capaian Tengah Semester' : 'Capaian Kompetensi' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rep['grades'] as $index => $grade)
                        @php($score = $grade['final_score'])
                        @php($components = collect($grade['components'] ?? [])->filter(fn ($c) => ($c['raw_score'] ?? null) !== null)->map(fn ($c) => ($c['name'] ?? $c['code'] ?? 'Komp').': '.rtrim(rtrim(number_format((float) $c['raw_score'], 1, ',', '.'), '0'), ','))->implode(' · '))
                        <tr>
                            <td class="number">{{ $index + 1 }}</td>
                            <td>{{ $grade['subject_name'] }}</td>
                            @if($isSts)
                                <td style="font-size: 8pt; color: #334155;">{{ $components ?: '—' }}</td>
                            @endif
                            <td class="score">{{ $score !== null ? rtrim(rtrim(number_format((float) $score, 2, ',', '.'), '0'), ',') : '—' }}</td>
                            <td class="center"><strong>{{ $grade['predicate'] ?? '—' }}</strong></td>
                            <td class="competency">{{ $grade['competency_description'] ?: 'Belum ada deskripsi capaian kompetensi.' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isSts ? 6 : 5 }}">Belum ada nilai akademik pada rapor ini.</td></tr>
                    @endforelse
                    @if(count($rep['grades']))
                        @php($avgScore = collect($rep['grades'])->whereNotNull('final_score')->avg('final_score'))
                        <tr class="total">
                            <td colspan="{{ $isSts ? 3 : 2 }}">Jumlah &amp; Rata-rata Nilai</td>
                            <td class="score">{{ $totalScore !== null ? rtrim(rtrim(number_format((float) $totalScore, 2, ',', '.'), '0'), ',') : '—' }}</td>
                            <td class="center">{{ $avgScore !== null ? rtrim(rtrim(number_format((float) $avgScore, 2, ',', '.'), '0'), ',') : '—' }}</td>
                            <td style="font-size: 8pt; color: #475569;">Rata-rata capaian mata pelajaran</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            @if(!$isGradesOnly)
                <div class="keep-together">
                    <h3>Ekstrakurikuler</h3>
                    <table class="data">
                        <thead><tr><th class="number">No.</th><th>Kegiatan Ekstrakurikuler</th><th style="width: 16%;">Predikat</th><th>Keterangan</th></tr></thead>
                        <tbody>
                            @forelse($rep['extracurricular'] as $index => $activity)
                                <tr>
                                    <td class="number">{{ $index + 1 }}</td>
                                    <td>{{ $activity['name'] }}</td>
                                    <td class="center">{{ $activity['predicate'] ?? '—' }}</td>
                                    <td>{{ $activity['description'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4">Belum ada catatan ekstrakurikuler.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="keep-together">
                    <h3>Prestasi</h3>
                    <table class="data">
                        <thead><tr><th class="number">No.</th><th>Jenis Prestasi</th><th>Keterangan</th></tr></thead>
                        <tbody>
                            @forelse($rep['notes']['achievements'] ?? [] as $index => $achievement)
                                <tr>
                                    <td class="number">{{ $index + 1 }}</td>
                                    <td>{{ $achievement['title'] }}</td>
                                    <td>{{ implode(' - ', array_filter([$achievement['level'] ?? null, $achievement['rank'] ?? null])) ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3">Belum ada catatan prestasi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="keep-together">
                    <h3>Ketidakhadiran</h3>
                    <table class="data" style="width: 55%;">
                        <tbody>
                            @foreach(['sick' => 'Sakit', 'permitted' => 'Izin', 'absent' => 'Tanpa Keterangan'] as $key => $label)
                                <tr>
                                    <td style="width: 55%;">{{ $label }}</td>
                                    <td class="center" style="width: 25%;">{{ $rep['attendance'][$key] ?? 0 }}</td>
                                    <td>hari</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="keep-together">
                    <h3>Catatan Wali Kelas</h3>
                    <p class="note">{{ ($rep['notes']['homeroom_notes'] ?? '') ?: 'Belum ada catatan wali kelas.' }}</p>
                </div>

                @if(!$isSts && !empty($rep['notes']['promotion_decision']))
                    <div class="keep-together">
                        <h3>Keputusan Kenaikan Kelas / Kelulusan</h3>
                        <div class="decision-box">{{ $rep['notes']['promotion_decision'] }}</div>
                    </div>
                @endif

                <div class="keep-together">
                    <h3>Tanggapan Orang Tua / Wali</h3>
                    <div class="parent-response"></div>

                    <table class="signatures">
                        <tr>
                            <td>
                                Mengetahui,<br>
                                Orang Tua / Wali,
                                <div class="signature-name">( ................................................ )</div>
                            </td>
                            <td>
                                {{ $placeDate }}<br>
                                Wali Kelas,
                                <div class="signature-name">{{ $rep['academic']['homeroom_teacher'] ?: '................................................' }}</div>
                                @if(!empty($rep['academic']['homeroom_nip']))
                                    <div class="signature-nip">NIP. {{ $rep['academic']['homeroom_nip'] }}</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="principal">
                                Mengetahui,<br>
                                Kepala Sekolah,
                                <div class="signature-name">{{ $rep['school']['principal_name'] ?: '................................................' }}</div>
                                @if(!empty($rep['school']['principal_nip']))
                                    <div class="signature-nip">NIP. {{ $rep['school']['principal_nip'] }}</div>
                                @endif
                            </td>
                        </tr>
                    </table>

                    <div class="footer-meta">
                        {{ $rep['class_group']['name'] }} · {{ $rep['student']['name'] }} · SHA-256: {{ $rep['checksum'] ?? '—' }}
                    </div>
                </div>
            @else
                <table class="signatures">
                    <tr>
                        <td>
                            Mengetahui,<br>
                            Kepala Sekolah,
                            <div class="signature-name">{{ $rep['school']['principal_name'] ?: '................................................' }}</div>
                            @if(!empty($rep['school']['principal_nip']))
                                <div class="signature-nip">NIP. {{ $rep['school']['principal_nip'] }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $placeDate }}<br>
                            Wali Kelas,
                            <div class="signature-name">{{ $rep['academic']['homeroom_teacher'] ?: '................................................' }}</div>
                            @if(!empty($rep['academic']['homeroom_nip']))
                                <div class="signature-nip">NIP. {{ $rep['academic']['homeroom_nip'] }}</div>
                            @endif
                        </td>
                    </tr>
                </table>

                <div class="footer-meta">
                    {{ $rep['class_group']['name'] }} · {{ $rep['student']['name'] }} · Ringkasan Nilai &amp; Identitas
                </div>
            @endif
        </div>
        @endif
    </div>
@endforeach
</body>
</html>
