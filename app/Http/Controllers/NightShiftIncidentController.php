<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\NightShiftIncident;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NightShiftIncidentController extends Controller
{
    public function index(): View
    {
        return view('incidents.index', [
            'incidents' => NightShiftIncident::query()
                ->latest('occurred_at')
                ->paginate(20),
            'locations' => Location::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['nullable', 'exists:locations,id'],
            'severity' => ['required', 'in:normal,high,critical'],
            'title' => ['required', 'string', 'max:180'],
            'details' => ['required', 'string', 'max:10000'],
            'occurred_at' => ['required', 'date'],
            'handover_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $nextSequence = (NightShiftIncident::max('id') ?? 0) + 1;

        $incident = NightShiftIncident::create([
            ...$data,
            'incident_no' => 'NS-'.now()->format('Ymd').'-'.str_pad(
                (string) $nextSequence,
                4,
                '0',
                STR_PAD_LEFT
            ),
            'reported_by' => $request->user()->id,
        ]);

        $audit->record(
            'NIGHT_INCIDENT_CREATED',
            $incident,
            [],
            $incident->toArray()
        );

        return back()->with('success', 'Night-shift incident recorded.');
    }
}
