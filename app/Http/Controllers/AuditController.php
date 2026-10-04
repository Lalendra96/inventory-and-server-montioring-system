<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(): View
    {
        return view('audit.index', [
            'logs' => AuditLog::query()
                ->latest('occurred_at')
                ->paginate(30),
        ]);
    }

    public function verify(
        Request $request,
        AuditLog $auditLog,
        AuditService $audit
    ): RedirectResponse {
        if (! $audit->verify($auditLog)) {
            return back()->withErrors([
                'audit' => 'Integrity verification failed. Do not alter this record; '
                    .'escalate for investigation.',
            ]);
        }

        $auditLog->update([
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Audit integrity verified.');
    }
}
