<?php

namespace App\Http\Controllers;

use App\Models\ServerActionLog;
use App\Models\ServerMetricSnapshot;
use App\Models\ServerNode;
use App\Services\AuditService;
use App\Services\ServerMonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ServerMonitorController extends Controller
{
    public function __construct(
        private readonly ServerMonitorService $monitor,
        private readonly AuditService $audit,
    ) {
    }

    public function index(): View
    {
        $servers = ServerNode::query()
            ->where('is_active', true)
            ->with('latestSnapshot')
            ->orderBy('name')
            ->get();

        $summary = [
            'total' => $servers->count(),
            'online' => 0,
            'attention' => 0,
            'not_checked' => 0,
        ];

        foreach ($servers as $server) {
            $latest = $server->latestSnapshot;
            $fresh = $latest?->captured_at?->gte(now()->subMinutes(2)) ?? false;
            $online = $fresh && (bool) $latest?->reachable;

            if (!$latest) {
                $summary['not_checked']++;
                $summary['attention']++;
                continue;
            }

            if ($online) {
                $summary['online']++;
            }

            $resourceWarning = (float) ($latest->cpu_percent ?? 0) >= 85
                || (float) ($latest->memory_percent ?? 0) >= 85
                || (float) ($latest->disk_percent ?? 0) >= 85;

            if (!$online || $resourceWarning) {
                $summary['attention']++;
            }
        }

        $canControl = auth()->user()->hasRole('system_admin', 'himu_admin', 'ict_manager', 'system_db_officer');

        return view('servers.index', compact('servers', 'canControl', 'summary'));
    }

    public function show(ServerNode $server): View
    {
        abort_unless($server->is_active, 404);

        $history = $server->snapshots()
            ->where('captured_at', '>=', now()->subHours(24))
            ->orderBy('captured_at')
            ->get();

        $actions = $server->actionLogs()->with('actor')->latest('executed_at')->limit(30)->get();
        $canControl = auth()->user()->hasRole('system_admin', 'himu_admin', 'ict_manager', 'system_db_officer');

        return view('servers.show', compact('server', 'history', 'actions', 'canControl'));
    }

    public function live(ServerNode $server): JsonResponse
    {
        abort_unless($server->is_active, 404);

        try {
            $metrics = $this->monitor->metrics($server);
            $server->forceFill(['last_seen_at' => now()])->saveQuietly();

            return response()->json(['ok' => true, 'metrics' => $metrics]);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $this->publicError($e),
                'captured_at' => now()->toIso8601String(),
            ], 503);
        }
    }

    public function processes(Request $request, ServerNode $server): JsonResponse
    {
        abort_unless($server->is_active, 404);
        $limit = (int) $request->integer('limit', 100);

        try {
            return response()->json([
                'ok' => true,
                'processes' => $this->monitor->processes($server, $limit),
                'captured_at' => now()->toIso8601String(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $this->publicError($e)], 503);
        }
    }

    public function services(ServerNode $server): JsonResponse
    {
        abort_unless($server->is_active, 404);

        try {
            return response()->json([
                'ok' => true,
                'services' => $this->monitor->services($server),
                'captured_at' => now()->toIso8601String(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $this->publicError($e)], 503);
        }
    }

    public function processAction(Request $request, ServerNode $server): JsonResponse
    {
        $this->authoriseControl();
        abort_unless($server->is_active, 404);

        $validated = $request->validate([
            'pid' => ['required', 'integer', 'min:1'],
            'signal' => ['required', 'in:TERM,KILL'],
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);

        $successful = false;
        $exitCode = null;
        $summary = null;

        try {
            $result = $this->monitor->signalProcess($server, (int) $validated['pid'], $validated['signal']);
            $successful = (bool) $result['successful'];
            $exitCode = $result['exit_code'];
            $summary = $result['output'] ?: 'Signal sent successfully.';

            $this->audit->record('server.process.signal', $server, [], [
                'pid' => (int) $validated['pid'],
                'signal' => $validated['signal'],
                'reason' => $validated['reason'],
                'successful' => $successful,
            ]);

            return response()->json(['ok' => true, 'message' => 'Process action completed.']);
        } catch (Throwable $e) {
            $summary = $this->publicError($e);
            $this->audit->record('server.process.signal_failed', $server, [], [
                'pid' => (int) $validated['pid'],
                'signal' => $validated['signal'],
                'reason' => $validated['reason'],
                'error' => $summary,
            ]);

            return response()->json(['ok' => false, 'message' => $summary], 422);
        } finally {
            ServerActionLog::create([
                'server_node_id' => $server->id,
                'actor_id' => auth()->id(),
                'action' => 'process_signal',
                'target_type' => 'process',
                'target' => (string) $validated['pid'],
                'signal_or_operation' => $validated['signal'],
                'reason' => $validated['reason'],
                'successful' => $successful,
                'exit_code' => $exitCode,
                'result_summary' => Str::limit((string) $summary, 1000),
                'executed_at' => now(),
            ]);
        }
    }

    public function serviceAction(Request $request, ServerNode $server): JsonResponse
    {
        $this->authoriseControl();
        abort_unless($server->is_active, 404);

        $validated = $request->validate([
            'service' => ['required', 'string', 'max:180', 'regex:/^[A-Za-z0-9_.@:-]+$/'],
            'operation' => ['required', 'in:start,stop,restart'],
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);

        $successful = false;
        $exitCode = null;
        $summary = null;

        try {
            $result = $this->monitor->serviceAction($server, $validated['service'], $validated['operation']);
            $successful = (bool) $result['successful'];
            $exitCode = $result['exit_code'];
            $summary = $result['output'] ?: 'Service action completed successfully.';

            $this->audit->record('server.service.action', $server, [], [
                'service' => $validated['service'],
                'operation' => $validated['operation'],
                'reason' => $validated['reason'],
                'successful' => $successful,
            ]);

            return response()->json(['ok' => true, 'message' => 'Service action completed.']);
        } catch (Throwable $e) {
            $summary = $this->publicError($e);
            $this->audit->record('server.service.action_failed', $server, [], [
                'service' => $validated['service'],
                'operation' => $validated['operation'],
                'reason' => $validated['reason'],
                'error' => $summary,
            ]);

            return response()->json(['ok' => false, 'message' => $summary], 422);
        } finally {
            ServerActionLog::create([
                'server_node_id' => $server->id,
                'actor_id' => auth()->id(),
                'action' => 'service_action',
                'target_type' => 'service',
                'target' => $validated['service'],
                'signal_or_operation' => $validated['operation'],
                'reason' => $validated['reason'],
                'successful' => $successful,
                'exit_code' => $exitCode,
                'result_summary' => Str::limit((string) $summary, 1000),
                'executed_at' => now(),
            ]);
        }
    }

    private function authoriseControl(): void
    {
        abort_unless(
            auth()->user()->hasRole('system_admin', 'himu_admin', 'ict_manager', 'system_db_officer'),
            403
        );
    }

    private function publicError(Throwable $e): string
    {
        if ($e instanceof RuntimeException) {
            return Str::limit($e->getMessage(), 500);
        }

        report($e);
        return 'The server monitoring operation could not be completed.';
    }
}
