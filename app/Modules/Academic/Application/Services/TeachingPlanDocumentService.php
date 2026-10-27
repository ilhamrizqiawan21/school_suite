<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Modules\Academic\Infrastructure\Models\LessonSession;
use App\Modules\Academic\Infrastructure\Models\TeachingDocument;
use App\Modules\Academic\Infrastructure\Models\TeachingPlan;

/** Pure transformations shared by document editors and the administration-package exporter. */
class TeachingPlanDocumentService
{
    public function allocation(TeachingPlan $plan, array $overrides = []): array
    {
        $data = $plan->data ?? [];
        $jpPerWeek = max(1, (int) ($overrides['jp_per_week'] ?? data_get($data, 'waktu.jp_per_minggu', 2)));
        $weeks = $overrides['effective_weeks'] ?? data_get($data, 'waktu.minggu_efektif', []);
        $chapters = collect(data_get($data, 'chapters', []))->keyBy(fn (array $chapter) => (string) ($chapter['id'] ?? ''));
        $units = collect(data_get($data, 'units', []));
        $result = [];

        foreach (['ganjil' => '1', 'genap' => '2'] as $semester => $key) {
            $effectiveWeeks = max(0, (int) ($weeks[$key] ?? $weeks[$semester] ?? 18));
            $planned = (int) $units->filter(fn (array $unit) => $this->semesterForUnit($unit, $chapters) === $semester)
                ->sum(fn (array $unit) => max(0, (int) ($unit['jp'] ?? 0)));
            $effective = $effectiveWeeks * $jpPerWeek;
            $result[$semester] = [
                'effective_weeks' => $effectiveWeeks,
                'jp_per_week' => $jpPerWeek,
                'effective_jp' => $effective,
                'planned_jp' => $planned,
                'delta_jp' => $effective - $planned,
            ];
        }

        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    public function promesDistribution(TeachingPlan $plan, string $semester, array $overrides = []): array
    {
        $semester = $this->normalizeSemester($semester);
        $data = $plan->data ?? [];
        $chapters = collect(data_get($data, 'chapters', []))->keyBy(fn (array $chapter) => (string) ($chapter['id'] ?? ''));
        $units = collect(data_get($data, 'units', []))->filter(fn (array $unit) => $this->semesterForUnit($unit, $chapters) === $semester);
        $allocation = $this->allocation($plan, $overrides)[$semester];
        $monthStart = (int) ($overrides['monthly_period'][$semester] ?? ($semester === 'ganjil' ? 7 : 1));
        $weeks = max(0, (int) $allocation['effective_weeks']);
        $weekSlots = [];

        for ($week = 0; $week < 30; $week++) {
            $weekSlots[] = [
                'month' => (($monthStart - 1 + intdiv($week, 5)) % 12) + 1,
                'week' => ($week % 5) + 1,
                'capacity' => $week < $weeks ? (int) $allocation['jp_per_week'] : 0,
                'units' => [],
            ];
        }

        $cursor = 0;
        foreach ($units as $unit) {
            $remaining = max(0, (int) ($unit['jp'] ?? 0));
            while ($remaining > 0 && $cursor < count($weekSlots)) {
                if ($weekSlots[$cursor]['capacity'] === 0) {
                    $cursor++;

                    continue;
                }
                $chunk = min($remaining, $weekSlots[$cursor]['capacity']);
                if ($chunk > 0) {
                    $weekSlots[$cursor]['units'][] = [
                        'unit_id' => $unit['id'] ?? null,
                        'code' => $unit['code'] ?? '',
                        'title' => $unit['objective'] ?? $unit['material'] ?? 'Unit pembelajaran',
                        'jp' => $chunk,
                    ];
                    $weekSlots[$cursor]['capacity'] -= $chunk;
                    $remaining -= $chunk;
                }
                if ($weekSlots[$cursor]['capacity'] === 0) {
                    $cursor++;
                }
            }
        }

        return $weekSlots;
    }

    public function packageManifest(TeachingPlan $plan, iterable $documents, iterable $sessions = [], ?string $activeSemester = null): array
    {
        $data = $plan->data ?? [];
        $byKey = collect($documents)->keyBy(fn (TeachingDocument $document) => $document->type.':'.($document->semester ?? 'tahunan'));
        $slots = [];
        $references = collect(data_get($data, 'references', []))->filter(fn (array $row) => trim((string) ($row['content'] ?? '')) !== '');
        $slots[] = $this->slot('01-acuan', 'CP / KI dan acuan', $references->isNotEmpty(), $references->isNotEmpty() ? 'Tersedia di ATP/Silabus.' : 'Isi CP/KI pada bagian Referensi & capaian di ATP/Silabus.');
        $hasUnits = collect(data_get($data, 'chapters', []))->isNotEmpty() && collect(data_get($data, 'units', []))->isNotEmpty();
        $slots[] = $this->slot('02-atp', 'ATP / Silabus', $hasUnits, $hasUnits ? 'Bab dan unit tersedia.' : 'Tambahkan bab dan unit pembelajaran pada ATP/Silabus.');
        $hasAllocation = $byKey->has('alokasi_waktu:tahunan') || data_get($data, 'waktu.jp_per_minggu') !== null;
        $slots[] = $this->slot('03-alokasi', 'Analisis alokasi waktu', $hasAllocation, $hasAllocation ? 'Pengaturan waktu tersedia.' : 'Lengkapi alokasi waktu pada ATP/Silabus.');

        foreach ([
            ['04-prota', 'prota', null, 'Program Tahunan'],
            ['05-promes-ganjil', 'promes', 'ganjil', 'Program Semester Ganjil'],
            ['06-promes-genap', 'promes', 'genap', 'Program Semester Genap'],
            ['09-kktp-ganjil', 'kktp', 'ganjil', 'KKTP / KKM Semester Ganjil'],
            ['10-kktp-genap', 'kktp', 'genap', 'KKTP / KKM Semester Genap'],
        ] as [$id, $type, $semester, $label]) {
            $document = $byKey->get($type.':'.($semester ?? 'tahunan'));
            $slots[] = $this->slot($id, $label, $document !== null, $document ? 'Dokumen tersimpan.' : 'Buat dan simpan dokumen ini pada menu Dokumen Perangkat Pembelajaran.', $document);
        }

        $meetingSessions = collect($sessions)->filter(fn (LessonSession $session) => $session->journal !== null)->map(fn (LessonSession $session) => [
            'id' => $session->id,
            'date' => $session->date?->toDateString(),
            'start_time' => $session->start_time,
            'end_time' => $session->end_time,
            'meeting_number' => $session->meeting_number,
            'class_name' => $session->classGroup?->name ?? '—',
            'subject_name' => $session->subject?->name ?? '—',
            'topic' => $session->journal->topic,
            'theme' => $session->journal->theme,
            'period_count' => $session->journal->period_count,
            'objectives' => $session->journal->learning_objectives,
            'opening' => $session->journal->opening_activity,
            'main' => $session->journal->main_activity,
            'closing' => $session->journal->closing_activity,
            'notes' => $session->journal->notes,
            'reflection' => $session->journal->reflection,
            'follow_up' => $session->journal->follow_up,
            'attendance_summary' => $session->journal->attendance_summary ?? [],
        ])->values()->all();

        foreach (['ganjil', 'genap'] as $semester) {
            $isCurrent = $activeSemester === $semester;
            $available = $isCurrent && count($meetingSessions) > 0;
            $reason = ! $isCurrent
                ? 'Dokumen pertemuan hanya tersedia untuk semester yang sedang aktif.'
                : ($available ? 'Jurnal sesi tersimpan untuk periode aktif.' : 'Belum ada jurnal sesi tersimpan pada periode aktif.');
            $slots[] = $this->slot('0'.($semester === 'ganjil' ? '7' : '8').'-pertemuan-'.$semester, 'Jurnal pertemuan semester '.ucfirst($semester), $available, $reason, null, ['sessions' => $available ? $meetingSessions : []]);
        }

        foreach (data_get($data, 'chapters', []) as $index => $chapter) {
            $module = data_get($data, 'modul.'.($chapter['id'] ?? ''), []);
            $ready = collect(data_get($module, 'bagian', []))->contains(fn ($value) => trim((string) $value) !== '')
                || collect(data_get($module, 'langkah', []))->contains(fn (array $step) => collect($step)->contains(fn ($value) => trim((string) $value) !== ''));
            $slots[] = $this->slot(sprintf('11-modul-%02d', $index + 1), 'Modul Ajar — '.($chapter['title'] ?? 'Bab '.($index + 1)), $ready, $ready ? 'Modul tersimpan.' : 'Isi Modul Ajar pada bab ini.', null, ['chapter_id' => $chapter['id'] ?? null, 'module' => $module]);
        }

        return $slots;
    }

    public function normalizeSemester(?string $semester): ?string
    {
        if ($semester === null || $semester === '') {
            return null;
        }

        return in_array(mb_strtolower($semester), ['genap', '2', 'even'], true) ? 'genap' : 'ganjil';
    }

    private function semesterForUnit(array $unit, $chapters): string
    {
        $chapter = $chapters->get((string) ($unit['chapter_id'] ?? ''));

        return $this->normalizeSemester((string) ($chapter['semester'] ?? '1')) ?? 'ganjil';
    }

    private function slot(string $id, string $label, bool $ready, string $reason, ?TeachingDocument $document = null, array $extra = []): array
    {
        return ['id' => $id, 'label' => $label, 'status' => $ready ? 'ready' : 'skipped', 'reason' => $reason, 'document_id' => $document?->id, ...$extra];
    }
}
