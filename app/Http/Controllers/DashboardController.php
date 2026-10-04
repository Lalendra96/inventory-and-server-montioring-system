<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Inspection;
use App\Models\NightShiftIncident;
use App\Models\ProcurementRequest;
use App\Models\ServerNode;
use App\Models\SoftwareRequest;
use App\Models\SystemDowntime;
use App\Models\Ticket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $monthStart = now()->startOfMonth();
        $servers = ServerNode::query()
            ->where('is_active', true)
            ->with('latestSnapshot')
            ->orderBy('name')
            ->get();

        $serverHealth = $this->serverHealthSummary($servers);

        $stats = [
            'total' => Ticket::where('is_active', true)->count(),
            'resolved' => Ticket::where('is_active', true)->whereIn('status', ['resolved', 'verified'])->count(),
            'open' => Ticket::where('is_active', true)->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
            'high' => Ticket::where('is_active', true)->where('priority', 'high')->whereNotIn('status', ['resolved', 'verified'])->count(),
            'critical' => Ticket::where('is_active', true)->where('priority', 'critical')->whereNotIn('status', ['resolved', 'verified'])->count(),
            'inspection_rate' => $this->inspectionRate(),
            'assets' => Asset::where('is_active', true)->count(),
            'downtime' => SystemDowntime::where('is_active', true)->where('started_at', '>=', $monthStart)->count(),
            'night_incidents' => NightShiftIncident::where('is_active', true)->where('occurred_at', '>=', $monthStart)->count(),
            'software_open' => SoftwareRequest::where('is_active', true)->whereNotIn('status', ['deployed', 'verified', 'closed'])->count(),
            'replacement_open' => ProcurementRequest::where('is_active', true)->whereNotIn('status', ['completed', 'rejected'])->count(),
            'servers_total' => $serverHealth['total'],
            'servers_online' => $serverHealth['online'],
            'servers_attention' => $serverHealth['attention'],
        ];

        $recent = Ticket::with(['location', 'category', 'assignee'])
            ->where('is_active', true)
            ->latest()
            ->limit(7)
            ->get();

        $categoryData = Ticket::query()
            ->select('ticket_categories.name', DB::raw('COUNT(tickets.id) as total'))
            ->leftJoin('ticket_categories', 'tickets.category_id', '=', 'ticket_categories.id')
            ->where('tickets.is_active', true)
            ->groupBy('ticket_categories.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $locationData = Ticket::query()
            ->select('locations.name', DB::raw('COUNT(tickets.id) as total'))
            ->leftJoin('locations', 'tickets.location_id', '=', 'locations.id')
            ->where('tickets.is_active', true)
            ->groupBy('locations.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $trend = Ticket::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->where('is_active', true)
            ->groupByRaw('DATE(created_at)')
            ->orderBy('day')
            ->get();

        $systemAvailability = $this->systemAvailability($servers);

        return view('dashboard.index', compact(
            'stats',
            'recent',
            'categoryData',
            'locationData',
            'trend',
            'systemAvailability',
            'serverHealth'
        ));
    }

    private function inspectionRate(): int
    {
        $scheduled = Inspection::whereBetween('due_on', [now()->startOfMonth(), now()->endOfMonth()])->count();

        if ($scheduled === 0) {
            return 0;
        }

        $completed = Inspection::whereBetween('due_on', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('status', 'completed')
            ->count();

        return (int) round(($completed / $scheduled) * 100);
    }

    private function serverHealthSummary(Collection $servers): array
    {
        $online = 0;
        $attention = 0;

        foreach ($servers as $server) {
            $latest = $server->latestSnapshot;
            $isFresh = $latest?->captured_at?->gte(now()->subMinutes(2)) ?? false;
            $isOnline = $isFresh && (bool) $latest?->reachable;

            if ($isOnline) {
                $online++;
            }

            if (!$isOnline || $this->hasResourceWarning($latest)) {
                $attention++;
            }
        }

        return [
            'total' => $servers->count(),
            'online' => $online,
            'attention' => $attention,
        ];
    }

    private function systemAvailability(Collection $servers): array
    {
        $labels = [
            'hims' => 'HIMS',
            'lims' => 'LIMS',
            'ris_pacs' => 'RIS / PACS',
        ];

        $result = [];

        foreach ($labels as $group => $label) {
            $assigned = $servers->where('system_group', $group)->values();
            $online = $assigned->filter(function (ServerNode $server): bool {
                $latest = $server->latestSnapshot;

                return (bool) $latest?->reachable
                    && ($latest?->captured_at?->gte(now()->subMinutes(2)) ?? false);
            })->count();

            $warning = $assigned->contains(
                fn (ServerNode $server): bool => $this->hasResourceWarning($server->latestSnapshot)
            );

            if ($assigned->isEmpty()) {
                $status = 'not_configured';
            } elseif ($online === 0) {
                $status = 'offline';
            } elseif ($online < $assigned->count() || $warning) {
                $status = 'degraded';
            } else {
                $status = 'online';
            }

            $result[] = [
                'key' => $group,
                'label' => $label,
                'total' => $assigned->count(),
                'online' => $online,
                'status' => $status,
            ];
        }

        return $result;
    }

    private function hasResourceWarning(mixed $latest): bool
    {
        if (!$latest || !$latest->reachable) {
            return false;
        }

        return (float) ($latest->cpu_percent ?? 0) >= 85
            || (float) ($latest->memory_percent ?? 0) >= 85
            || (float) ($latest->disk_percent ?? 0) >= 85;
    }
}
