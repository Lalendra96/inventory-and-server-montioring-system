<?php

namespace App\Http\Controllers;

use App\Models\MonitoredSystem;
use App\Models\SystemDowntime;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemDowntimeController extends Controller
{
    public function index(): View
    {
        return view('downtime.index', [
            'records' => SystemDowntime::query()
                ->latest('started_at')
                ->paginate(20),
            'systems' => MonitoredSystem::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'system_id' => ['nullable', 'exists:monitored_systems,id'],
            'classification' => ['required', 'string', 'max:100'],
            'started_at' => ['required', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'impact' => ['nullable', 'string', 'max:5000'],
            'root_cause' => ['nullable', 'string', 'max:5000'],
            'resolution' => ['nullable', 'string', 'max:5000'],
        ]);

        $record = SystemDowntime::create([
            ...$data,
            'reported_by' => $request->user()->id,
        ]);

        $audit->record(
            'DOWNTIME_RECORDED',
            $record,
            [],
            $record->toArray()
        );

        return back()->with('success', 'Downtime record created.');
    }

    public function verify(
        Request $request,
        SystemDowntime $downtime,
        AuditService $audit
    ): RedirectResponse {
        $downtime->update([
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $audit->record(
            'DOWNTIME_VERIFIED',
            $downtime,
            [],
            ['verified_by' => $request->user()->id]
        );

        return back()->with('success', 'Downtime record verified.');
    }
}
