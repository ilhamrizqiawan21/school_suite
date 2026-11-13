<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Analisis Butir Soal</title><style>
@page{margin:12mm}body{font-family:DejaVu Sans,sans-serif;font-size:9px;color:#000}h1{font-size:18px}table{border-collapse:collapse;width:100%}td,th{border:1px solid #777;padding:5px;vertical-align:top}th{background:#eee}tr{page-break-inside:avoid}thead{display:table-header-group}p{line-height:1.5}
</style></head><body><h1>Analisis Butir Soal</h1><p>{{ $report['name'] }} · {{ $report['class'] ?? '' }}<br>Guru: {{ $teacher }} · {{ now()->format('d-m-Y') }}</p><p>{{ $report['summary'] }}</p>
@foreach($report['warnings'] ?? [] as $warning)<p>{{ $warning }}</p>@endforeach
<table><thead><tr>@foreach(['No.','Kunci','Tingkat kesukaran','Daya pembeda','Sebaran jawaban','Saran'] as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead><tbody>@foreach($report['rows'] as $row)<tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@endforeach</tbody></table>
<h2>Cara membaca</h2>@foreach($report['guide'] ?? [] as $entry)<p><strong>{{ $entry['istilah'] }}</strong> — {{ $entry['arti'] }}</p>@endforeach
<p>Statistik deskriptif untuk telaah guru. Periksa kembali kunci dan konteks soal sebelum mengambil keputusan.</p></body></html>
