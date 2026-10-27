<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Buku Induk - {{ $student->name }}</title>
    <style>
        @page { margin: 22mm 18mm; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.45; }
        h1 { margin: 0 0 4px; font-size: 17pt; text-align: center; }
        .school { margin: 0 0 22px; color: #4b5563; text-align: center; }
        h2 { margin: 22px 0 8px; padding: 6px 8px; border-left: 3px solid #3157a4; background: #eef3fb; font-size: 11pt; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 6px 7px; border: 1px solid #d1d5db; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-size: 9pt; }
        .label { width: 32%; color: #4b5563; }
        .date { margin-top: 22px; color: #6b7280; font-size: 8pt; text-align: right; }
    </style>
</head>
<body>
    <h1>Buku Induk Peserta Didik</h1>
    <p class="school">{{ $school['name'] }}@if($school['npsn']) · NPSN {{ $school['npsn'] }}@endif</p>

    <h2>A. Identitas peserta didik</h2>
    <table>
        <tr><td class="label">Nama lengkap</td><td>{{ $student->name }}</td></tr>
        <tr><td class="label">Nama panggilan</td><td>{{ $detail?->nickname ?: '—' }}</td></tr>
        <tr><td class="label">NIS / NISN</td><td>{{ $student->nis }} / {{ $student->nisn ?: '—' }}</td></tr>
        <tr><td class="label">NIK</td><td>{{ $detail?->nik ?: '—' }}</td></tr>
        <tr><td class="label">Jenis kelamin</td><td>{{ $student->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td></tr>
        <tr><td class="label">Tempat, tanggal lahir</td><td>{{ $student->birth_place ?: '—' }}, {{ $student->birth_date?->format('d-m-Y') ?: '—' }}</td></tr>
        <tr><td class="label">Agama</td><td>{{ $student->religion ?: '—' }}</td></tr>
        <tr><td class="label">Alamat</td><td>{{ $student->address ?: '—' }}</td></tr>
        <tr><td class="label">RT / RW</td><td>{{ $detail?->address_rt ?: '—' }} / {{ $detail?->address_rw ?: '—' }}</td></tr>
        <tr><td class="label">Desa / kecamatan</td><td>{{ $detail?->village ?: '—' }} / {{ $detail?->district ?: '—' }}</td></tr>
        <tr><td class="label">Golongan darah</td><td>{{ $detail?->blood_type ?: '—' }}</td></tr>
        <tr><td class="label">Hobi olahraga</td><td>{{ $detail?->sports_hobby ?: '—' }}</td></tr>
        <tr><td class="label">Status</td><td>{{ ['active' => 'Aktif', 'graduated' => 'Lulus', 'transferred' => 'Pindah', 'dropped_out' => 'Keluar'][$student->status] ?? $student->status }}</td></tr>
    </table>

    <h2>B. Orang tua / wali</h2>
    <table>
        <tr><td class="label">Nama</td><td>{{ $student->parent_name ?: '—' }}</td></tr>
        <tr><td class="label">Nomor kontak</td><td>{{ $student->parent_phone ?: '—' }}</td></tr>
        <tr><td class="label">Nama ayah</td><td>{{ $detail?->father_name ?: '—' }}</td></tr>
        <tr><td class="label">Pekerjaan ayah</td><td>{{ $detail?->father_occupation ?: '—' }}</td></tr>
        <tr><td class="label">Nama ibu</td><td>{{ $detail?->mother_name ?: '—' }}</td></tr>
        <tr><td class="label">Pekerjaan ibu</td><td>{{ $detail?->mother_occupation ?: '—' }}</td></tr>
    </table>

    <h2>C. Sekolah asal dan ijazah</h2>
    <table>
        <tr><td class="label">Sekolah asal</td><td>{{ $detail?->previous_school ?: '—' }}</td></tr>
        <tr><td class="label">Nomor ijazah asal</td><td>{{ $detail?->diploma_number ?: '—' }}</td></tr>
        <tr><td class="label">Tanggal ijazah asal</td><td>{{ $detail?->diploma_date?->format('d-m-Y') ?: '—' }}</td></tr>
    </table>

    <h2>D. Dokumen pribadi</h2>
    <table>
        <thead><tr><th>Jenis dokumen</th><th>Nama berkas</th><th>Ukuran</th><th>Diunggah</th></tr></thead>
        <tbody>
            @forelse($documents as $document)
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

    <h2>E. Riwayat kelas</h2>
    <table>
        <thead><tr><th>Tahun ajaran</th><th>Semester</th><th>Kelas</th><th>No. absen</th><th>Status</th></tr></thead>
        <tbody>
            @forelse($classHistory as $enrollment)
                <tr>
                    <td>{{ $enrollment->term?->academicYear?->name ?: '—' }}</td>
                    <td>{{ $enrollment->term?->name ?: '—' }}</td>
                    <td>{{ $enrollment->classGroup?->name ?: '—' }}</td>
                    <td>{{ $enrollment->roll_number ?? '—' }}</td>
                    <td>{{ ['active' => 'Aktif', 'promoted' => 'Naik kelas', 'retained' => 'Tinggal kelas', 'graduated' => 'Lulus', 'transferred' => 'Pindah', 'withdrawn' => 'Keluar'][$enrollment->status] ?? $enrollment->status }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada riwayat penempatan kelas.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="date">Dicetak {{ now()->locale('id')->translatedFormat('d F Y') }}</p>
</body>
</html>
