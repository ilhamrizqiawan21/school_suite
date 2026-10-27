<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Services;

use App\Models\User;
use App\Modules\Administration\Infrastructure\Models\FacilityAsset;
use App\Modules\Administration\Infrastructure\Models\FacilityLoan;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FacilitiesService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** @param array<string, mixed> $attributes */
    public function createAsset(User $actor, array $attributes): FacilityAsset
    {
        return DB::transaction(function () use ($actor, $attributes): FacilityAsset {
            abort_unless($actor->is_active && $actor->school_id !== null, 403);
            abort_if(($attributes['condition'] ?? null) === 'damaged' && ($attributes['status'] ?? null) === 'available', 422, 'Aset rusak harus berstatus pemeliharaan atau nonaktif.');
            $asset = FacilityAsset::create([
                ...$attributes,
                'school_id' => (int) $actor->school_id,
                'created_by' => (int) $actor->id,
            ]);

            $this->audit->log('administration.facility_asset.created', 'facility_asset', (string) $asset->id,
                $asset->only(['inventory_code', 'name', 'category', 'location', 'condition', 'status']), null, $actor, (int) $actor->school_id);

            return $asset;
        });
    }

    /** @param array<string, mixed> $attributes */
    public function updateAsset(User $actor, int $assetId, array $attributes): FacilityAsset
    {
        return DB::transaction(function () use ($actor, $assetId, $attributes): FacilityAsset {
            abort_if(($attributes['condition'] ?? null) === 'damaged' && ($attributes['status'] ?? null) === 'available', 422, 'Aset rusak harus berstatus pemeliharaan atau nonaktif.');
            $asset = FacilityAsset::query()
                ->where('school_id', $actor->school_id)
                ->lockForUpdate()
                ->findOrFail($assetId);

            if ($asset->activeLoan()->exists()) {
                throw ValidationException::withMessages(['asset' => 'Aset yang sedang dipinjam tidak dapat diubah. Catat pengembaliannya terlebih dahulu.']);
            }

            $oldValues = $asset->only(['inventory_code', 'name', 'category', 'location', 'condition', 'status', 'notes']);
            $asset->fill($attributes)->save();
            $this->audit->log('administration.facility_asset.updated', 'facility_asset', (string) $asset->id,
                $asset->only(['inventory_code', 'name', 'category', 'location', 'condition', 'status', 'notes']), $oldValues, $actor, (int) $actor->school_id);

            return $asset;
        });
    }

    /** @param array{borrower_name:string, borrower_reference?:?string, borrowed_at:string, due_at?:?string, notes?:?string} $attributes */
    public function borrow(User $actor, int $assetId, array $attributes): FacilityLoan
    {
        return DB::transaction(function () use ($actor, $assetId, $attributes): FacilityLoan {
            $asset = FacilityAsset::query()
                ->where('school_id', $actor->school_id)
                ->lockForUpdate()
                ->findOrFail($assetId);

            if ($asset->status !== 'available' || $asset->activeLoan()->exists()) {
                throw ValidationException::withMessages(['asset_id' => 'Aset tidak tersedia untuk dipinjam.']);
            }
            if ($asset->condition === 'damaged') {
                throw ValidationException::withMessages(['asset_id' => 'Aset rusak tidak dapat dipinjam.']);
            }

            $loan = FacilityLoan::create([
                ...$attributes,
                'school_id' => (int) $actor->school_id,
                'asset_id' => (int) $asset->id,
                'recorded_by' => (int) $actor->id,
            ]);
            $asset->update(['status' => 'borrowed']);

            $this->audit->log('administration.facility_asset.borrowed', 'facility_loan', (string) $loan->id,
                ['asset_id' => $asset->id, 'borrower_name' => $loan->borrower_name, 'borrowed_at' => $loan->borrowed_at?->toDateString(), 'due_at' => $loan->due_at?->toDateString()], null, $actor, (int) $actor->school_id);

            return $loan;
        });
    }

    public function returnLoan(User $actor, int $loanId, string $returnCondition, ?string $notes = null): FacilityLoan
    {
        return DB::transaction(function () use ($actor, $loanId, $returnCondition, $notes): FacilityLoan {
            $loan = FacilityLoan::query()
                ->where('school_id', $actor->school_id)
                ->lockForUpdate()
                ->findOrFail($loanId);

            if ($loan->returned_at !== null) {
                throw ValidationException::withMessages(['loan' => 'Peminjaman ini sudah dikembalikan.']);
            }

            $asset = FacilityAsset::query()
                ->where('school_id', $actor->school_id)
                ->lockForUpdate()
                ->findOrFail($loan->asset_id);

            $loan->update([
                'returned_at' => now()->toDateString(),
                'return_condition' => $returnCondition,
                'notes' => $notes ?? $loan->notes,
            ]);
            $asset->update([
                'status' => $returnCondition === 'damaged' ? 'maintenance' : 'available',
                'condition' => $returnCondition === 'damaged' ? 'damaged' : 'good',
            ]);

            $this->audit->log('administration.facility_asset.returned', 'facility_loan', (string) $loan->id,
                ['asset_id' => $asset->id, 'returned_at' => $loan->returned_at?->toDateString(), 'return_condition' => $returnCondition, 'asset_status' => $asset->status], null, $actor, (int) $actor->school_id);

            return $loan->refresh();
        });
    }
}
