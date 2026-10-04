<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetFault;
use App\Models\AssetMovement;
use App\Models\Location;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        $query = Asset::query()
            ->with(['category', 'location'])
            ->where('is_active', true);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->q);

            // Deliberately avoid wildcard searches. Search exact operational identifiers.
            $query->where(function ($builder) use ($term): void {
                $builder
                    ->where('asset_tag', $term)
                    ->orWhere('name', $term)
                    ->orWhere('serial_number', $term);
            });
        }

        return view('assets.index', [
            'assets' => $query
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function show(Asset $asset): View
    {
        abort_unless($asset->is_active, 404);

        $asset->load([
            'category',
            'location',
            'movements' => fn ($query) => $query->latest('moved_at'),
            'faults' => fn ($query) => $query
                ->where('is_active', true)
                ->latest('reported_at'),
        ]);

        return view('assets.show', [
            'asset' => $asset,
            'locations' => Location::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('assets.create', [
            'categories' => AssetCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'locations' => Location::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'asset_tag' => ['required', 'string', 'max:80', 'unique:assets,asset_tag'],
            'category_id' => ['nullable', 'exists:asset_categories,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'name' => ['required', 'string', 'max:180'],
            'manufacturer' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'serial_number' => ['nullable', 'string', 'max:160'],
            'status' => ['required', 'in:active,repair,standby,replacement,retired'],
            'purchase_date' => ['nullable', 'date'],
            'warranty_until' => ['nullable', 'date'],
            'supplier' => ['nullable', 'string', 'max:180'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'ip_address' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $asset = Asset::create($data);

        $audit->record(
            'ASSET_CREATED',
            $asset,
            [],
            $asset->only(['asset_tag', 'name', 'status', 'location_id'])
        );

        return redirect()
            ->route('assets.index')
            ->with('success', 'Asset registered.');
    }

    public function edit(Asset $asset): View
    {
        abort_unless($asset->is_active, 404);

        return view('assets.edit', [
            'asset' => $asset,
            'categories' => AssetCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'locations' => Location::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(
        Request $request,
        Asset $asset,
        AuditService $audit
    ): RedirectResponse {
        abort_unless($asset->is_active, 404);

        $data = $request->validate([
            'asset_tag' => [
                'required',
                'string',
                'max:80',
                'unique:assets,asset_tag,'.$asset->id,
            ],
            'category_id' => ['nullable', 'exists:asset_categories,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'name' => ['required', 'string', 'max:180'],
            'manufacturer' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'serial_number' => ['nullable', 'string', 'max:160'],
            'status' => ['required', 'in:active,repair,standby,replacement,retired'],
            'purchase_date' => ['nullable', 'date'],
            'warranty_until' => ['nullable', 'date'],
            'supplier' => ['nullable', 'string', 'max:180'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'ip_address' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $oldValues = $asset->toArray();
        $asset->update($data);

        $audit->record(
            'ASSET_UPDATED',
            $asset,
            $oldValues,
            $asset->fresh()->toArray()
        );

        return redirect()
            ->route('assets.index')
            ->with('success', 'Asset updated.');
    }

    public function move(
        Request $request,
        Asset $asset,
        AuditService $audit
    ): RedirectResponse {
        abort_unless($asset->is_active, 404);

        $data = $request->validate([
            'to_location_id' => ['required', 'exists:locations,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $fromLocationId = $asset->location_id;

        AssetMovement::create([
            'asset_id' => $asset->id,
            'from_location_id' => $fromLocationId,
            'to_location_id' => $data['to_location_id'],
            'moved_by' => $request->user()->id,
            'reason' => $data['reason'] ?? null,
            'moved_at' => now(),
        ]);

        $asset->update([
            'location_id' => $data['to_location_id'],
        ]);

        $audit->record(
            'ASSET_MOVED',
            $asset,
            ['location_id' => $fromLocationId],
            [
                'location_id' => $data['to_location_id'],
                'reason' => $data['reason'] ?? null,
            ]
        );

        return back()->with('success', 'Asset movement recorded.');
    }

    public function fault(
        Request $request,
        Asset $asset,
        AuditService $audit
    ): RedirectResponse {
        abort_unless($asset->is_active, 404);

        $data = $request->validate([
            'fault_type' => ['nullable', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        $fault = AssetFault::create([
            'asset_id' => $asset->id,
            'fault_type' => $data['fault_type'] ?? null,
            'description' => $data['description'],
            'reported_at' => now(),
            'is_active' => true,
        ]);

        $audit->record(
            'ASSET_FAULT_RECORDED',
            $fault,
            [],
            [
                'asset_id' => $asset->id,
                'fault_type' => $data['fault_type'] ?? null,
            ]
        );

        return back()->with('success', 'Fault history entry added.');
    }

    public function resolveFault(
        Request $request,
        Asset $asset,
        AssetFault $fault,
        AuditService $audit
    ): RedirectResponse {
        abort_unless($asset->is_active, 404);
        abort_unless($fault->asset_id === $asset->id, 404);

        $fault->update([
            'resolved_at' => now(),
        ]);

        $audit->record(
            'ASSET_FAULT_RESOLVED',
            $fault,
            [],
            ['asset_id' => $asset->id]
        );

        return back()->with('success', 'Fault marked resolved.');
    }

    public function disable(
        Request $request,
        Asset $asset,
        AuditService $audit
    ): RedirectResponse {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $asset->update([
            'is_active' => false,
            'disabled_at' => now(),
            'disabled_by' => $request->user()->id,
            'disable_reason' => $data['reason'],
        ]);

        $audit->record(
            'ASSET_DISABLED',
            $asset,
            [],
            ['reason' => $data['reason']]
        );

        return redirect()
            ->route('assets.index')
            ->with('success', 'Asset disabled; its history has been retained.');
    }
}
