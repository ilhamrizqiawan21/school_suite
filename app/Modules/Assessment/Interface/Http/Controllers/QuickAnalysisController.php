<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assessment\Application\Services\AssessmentWorkbookService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Throwable;

class QuickAnalysisController extends Controller
{
    private function authorizeAccess(Request $request): void
    {
        abort_unless($request->user()?->is_active && $request->user()->school_id && $request->user()->hasRole(['teacher', 'homeroom']), 403);
    }

    public function import(Request $request, AssessmentWorkbookService $workbooks)
    {
        $this->authorizeAccess($request);
        $request->validate(['file' => ['required', 'file', 'max:5120', 'extensions:xlsx,csv']]);
        try {
            return response()->json(['rows' => $workbooks->read($request->file('file'))]);
        } catch (Throwable $e) {
            throw ValidationException::withMessages(['file' => $e instanceof RuntimeException ? $e->getMessage() : 'Berkas tidak dapat dibaca. Gunakan XLSX atau CSV yang valid.']);
        }
    }

    public function export(Request $request, AssessmentWorkbookService $workbooks)
    {
        $this->authorizeAccess($request);
        $data = $request->validate([
            'sheets' => ['required', 'array:Jawaban,Hasil,Ringkasan', 'min:1', 'max:3'],
            'sheets.*' => ['array', 'max:510'], 'sheets.*.*' => ['array', 'max:201'],
            'sheets.*.*.*' => ['nullable', function ($attribute, $value, $fail) {
                if (! is_scalar($value) || mb_strlen((string) $value) > 10000) {
                    $fail('Isi sel tidak valid atau terlalu panjang.');
                }
            }],
        ]);
        $book = $workbooks->workbook($data['sheets']);

        return response()->streamDownload(function () use ($book): void {
            try {
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, 'analisis-soal.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function pdf(Request $request)
    {
        $this->authorizeAccess($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'class' => ['nullable', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:1000'],
            'warnings' => ['array', 'max:5'], 'warnings.*' => ['string', 'max:1000'],
            'rows' => ['required', 'array', 'min:20', 'max:200'], 'rows.*' => ['array', 'size:6'],
            'rows.*.*' => ['nullable', 'string', 'max:3000'],
            'guide' => ['array', 'max:10'], 'guide.*.istilah' => ['string', 'max:100'], 'guide.*.arti' => ['string', 'max:2000'],
        ]);

        return Pdf::loadView('reports.question-analysis', ['report' => $data, 'teacher' => $request->user()->name])
            ->setPaper('a4', 'landscape')->download('analisis-soal.pdf');
    }

    public function draft(Request $request)
    {
        $this->authorizeAccess($request);
        $key = 'analysis-draft:'.$request->user()->school_id.':'.$request->user()->id;
        if ($request->isMethod('delete')) {
            Cache::forget($key);

            return response()->noContent();
        }
        if ($request->isMethod('get')) {
            $value = Cache::get($key);

            return response()->json(['draft' => $value ? json_decode(Crypt::decryptString($value), true) : null]);
        }
        $data = $request->validate([
            'nama' => ['present', 'nullable', 'string', 'max:100'], 'kelas' => ['present', 'nullable', 'string', 'max:120'],
            'jumlahSoal' => ['required', 'integer', 'min:20', 'max:200'], 'pilihan' => ['required', 'integer', 'between:3,5'],
            'langkah' => ['required', 'integer', 'between:1,3'], 'kunci' => ['required', 'array', 'max:200'],
            'kunci.*' => ['nullable', 'string', 'regex:/^[A-E]?$/'],
            'siswa' => ['present', 'array', 'max:500'], 'siswa.*.absen' => ['present', 'nullable', 'string', 'max:20'],
            'siswa.*.jawaban' => ['present', 'nullable', 'string', 'max:600'],
        ]);
        $data['nama'] = $data['nama'] ?? '';
        $data['kelas'] = $data['kelas'] ?? '';
        $data['kunci'] = array_map(fn ($value) => $value ?? '', $data['kunci']);
        $data['siswa'] = array_map(fn ($row) => ['absen' => $row['absen'] ?? '', 'jawaban' => $row['jawaban'] ?? ''], $data['siswa']);
        Cache::put($key, Crypt::encryptString(json_encode($data)), now()->addDays(30));

        return response()->noContent();
    }
}
