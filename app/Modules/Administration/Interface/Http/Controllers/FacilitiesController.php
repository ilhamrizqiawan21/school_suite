<?php

declare(strict_types=1);

namespace App\Modules\Administration\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Application\Services\FacilitiesService;
use App\Modules\Administration\Infrastructure\Models\FacilityAsset;
use App\Modules\Administration\Infrastructure\Models\FacilityLoan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FacilitiesController extends Controller
{
    public function __construct(private readonly FacilitiesService $facilities) {}

    public function index(Request $request): Response
    {
        $schoolId = (int) $request->user()->school_id;
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $assets = FacilityAsset::query()
            ->where('school_id', $schoolId)
            ->with(['activeLoan:id,asset_id,borrower_name,borrowed_at,due_at'])
            ->when($search !== '', fn ($query) => $query->where(fn ($sub) => $sub
                ->where('name', 'like', "%{$search}%")
                ->orWhere('inventory_code', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%")))
            ->when(in_array($status, ['available', 'borrowed', 'maintenance', 'inactive'], true), fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(10, ['*'], 'asset_page')->withQueryString();

        $loans = FacilityLoan::query()
            ->where('school_id', $schoolId)
            ->with('asset:id,inventory_code,name')
            ->orderByRaw('CASE WHEN returned_at IS NULL THEN 0 ELSE 1 END')
            ->latest('id')
            ->paginate(10, ['*'], 'loan_page')->withQueryString();

        return Inertia::render('Administration/Facilities/Index', [
            'assets' => $assets->items(),
            'loans' => $loans->items(),
            'asset_pages' => ['current' => $assets->currentPage(), 'last' => $assets->lastPage(), 'total' => $assets->total(), 'previous' => $assets->previousPageUrl(), 'next' => $assets->nextPageUrl()],
            'loan_pages' => ['current' => $loans->currentPage(), 'last' => $loans->lastPage(), 'total' => $loans->total(), 'previous' => $loans->previousPageUrl(), 'next' => $loans->nextPageUrl()],
            'available_assets' => FacilityAsset::query()->where('school_id', $schoolId)->where('status', 'available')->where('condition', '!=', 'damaged')->whereDoesntHave('activeLoan')->orderBy('name')->get(['id', 'inventory_code', 'name']),
            'filters' => ['search' => $search, 'status' => $status],
            'summary' => [
                'total' => FacilityAsset::where('school_id', $schoolId)->count(),
                'available' => FacilityAsset::where('school_id', $schoolId)->where('status', 'available')->count(),
                'borrowed' => FacilityAsset::where('school_id', $schoolId)->where('status', 'borrowed')->count(),
                'maintenance' => FacilityAsset::where('school_id', $schoolId)->where('status', 'maintenance')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validate([
            'inventory_code' => ['required', 'string', 'max:80', Rule::unique('facility_assets')->where('school_id', $schoolId)],
            'name' => ['required', 'string', 'max:180'],
            'category' => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:180'],
            'condition' => ['required', Rule::in(['good', 'fair', 'poor', 'damaged'])],
            'status' => ['required', Rule::in(['available', 'maintenance', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $this->facilities->createAsset($request->user(), $data);

        return back()->with('message', 'Aset berhasil ditambahkan.');
    }

    public function update(Request $request, int $assetId): RedirectResponse
    {
        $schoolId = (int) $request->user()->school_id;
        $asset = FacilityAsset::query()->where('school_id', $schoolId)->findOrFail($assetId);
        $data = $request->validate([
            'inventory_code' => ['required', 'string', 'max:80', Rule::unique('facility_assets')->where('school_id', $schoolId)->ignore($asset->id)],
            'name' => ['required', 'string', 'max:180'],
            'category' => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:180'],
            'condition' => ['required', Rule::in(['good', 'fair', 'poor', 'damaged'])],
            'status' => ['required', Rule::in(['available', 'maintenance', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->facilities->updateAsset($request->user(), $asset->id, $data);

        return back()->with('message', 'Data aset berhasil diperbarui.');
    }

    public function borrow(Request $request): RedirectResponse
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validate([
            'asset_id' => ['required', 'integer', Rule::exists('facility_assets', 'id')->where('school_id', $schoolId)],
            'borrower_name' => ['required', 'string', 'max:180'],
            'borrower_reference' => ['nullable', 'string', 'max:100'],
            'borrowed_at' => ['required', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:borrowed_at'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->facilities->borrow($request->user(), (int) $data['asset_id'], $data);

        return back()->with('message', 'Peminjaman aset berhasil dicatat.');
    }

    public function recordReturn(Request $request, int $loanId): RedirectResponse
    {
        $loan = FacilityLoan::query()->where('school_id', $request->user()->school_id)->findOrFail($loanId);
        $data = $request->validate([
            'return_condition' => ['required', Rule::in(['good', 'damaged'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->facilities->returnLoan($request->user(), $loan->id, $data['return_condition'], $data['notes'] ?? null);

        return back()->with('message', 'Pengembalian aset berhasil dicatat.');
    }
}
