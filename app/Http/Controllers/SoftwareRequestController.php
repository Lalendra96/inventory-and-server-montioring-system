<?php

namespace App\Http\Controllers;

use App\Models\SoftwareRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SoftwareRequestController extends Controller
{
    public function index(): View
    {
        return view('software.index', [
            'requests' => SoftwareRequest::query()
                ->latest()
                ->paginate(20),
        ]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'priority' => [
                'required',
                Rule::in(['low', 'normal', 'high', 'critical']),
            ],
        ]);

        $nextSequence = (SoftwareRequest::max('id') ?? 0) + 1;

        $softwareRequest = SoftwareRequest::create([
            ...$data,
            'request_no' => 'SW-'.now()->format('Ymd').'-'.str_pad(
                (string) $nextSequence,
                4,
                '0',
                STR_PAD_LEFT
            ),
            'requested_by' => $request->user()->id,
        ]);

        $audit->record(
            'SOFTWARE_REQUEST_CREATED',
            $softwareRequest,
            [],
            $softwareRequest->toArray()
        );

        return back()->with('success', 'Software request created.');
    }

    public function status(
        Request $request,
        SoftwareRequest $softwareRequest,
        AuditService $audit
    ): RedirectResponse {
        $data = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'requested',
                    'reviewed',
                    'approved',
                    'development',
                    'internal_test',
                    'uat',
                    'approved_for_deployment',
                    'deployed',
                    'verified',
                    'closed',
                ]),
            ],
        ]);

        $oldStatus = $softwareRequest->status;

        $softwareRequest->update([
            'status' => $data['status'],
            'deployed_at' => $data['status'] === 'deployed'
                ? now()
                : $softwareRequest->deployed_at,
        ]);

        $audit->record(
            'SOFTWARE_REQUEST_STATUS',
            $softwareRequest,
            ['status' => $oldStatus],
            ['status' => $data['status']]
        );

        return back()->with('success', 'Workflow status updated.');
    }
}
