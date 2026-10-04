<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\ProcurementRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProcurementController extends Controller
{
    public function index(): View
    {
        return view('procurement.index', [
            'requests' => ProcurementRequest::query()
                ->latest()
                ->paginate(20),
            'assets' => Asset::query()
                ->where('is_active', true)
                ->orderBy('asset_tag')
                ->get(),
        ]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'asset_id' => ['nullable', 'exists:assets,id'],
            'request_type' => [
                'required',
                'in:replacement,new_purchase,repair,upgrade',
            ],
            'priority' => ['required', 'in:low,normal,high,critical'],
            'justification' => ['required', 'string', 'max:10000'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $nextSequence = (ProcurementRequest::max('id') ?? 0) + 1;

        $procurement = ProcurementRequest::create([
            ...$data,
            'request_no' => 'PR-'.now()->format('Ymd').'-'.str_pad(
                (string) $nextSequence,
                4,
                '0',
                STR_PAD_LEFT
            ),
            'requested_by' => $request->user()->id,
            'status' => 'submitted',
        ]);

        $audit->record(
            'PROCUREMENT_REQUEST_CREATED',
            $procurement,
            [],
            $procurement->toArray()
        );

        return back()->with(
            'success',
            'Procurement/replacement request submitted.'
        );
    }

    public function approve(
        Request $request,
        ProcurementRequest $procurement,
        AuditService $audit
    ): RedirectResponse {
        $data = $request->validate([
            'committee_reference' => ['nullable', 'string', 'max:120'],
            'decision_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $procurement->update([
            ...$data,
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $audit->record(
            'PROCUREMENT_APPROVED',
            $procurement,
            [],
            $data
        );

        return back()->with('success', 'Request approved.');
    }
}
