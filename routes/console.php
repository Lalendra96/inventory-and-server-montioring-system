<?php

use App\Models\Inspection;
use App\Models\InspectionSchedule;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\OperationsAlert;
use App\Services\AuditService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('himu:generate-inspections', function () {
    $today = today();
    $created = 0;

    InspectionSchedule::query()
        ->where('is_active', true)
        ->whereDate('next_due_on', '<=', $today)
        ->orderBy('id')
        ->chunkById(100, function ($schedules) use (&$created, $today) {
            foreach ($schedules as $schedule) {
                $exists = Inspection::query()
                    ->where('schedule_id', $schedule->id)
                    ->whereDate('due_on', $schedule->next_due_on)
                    ->exists();

                if (! $exists) {
                    Inspection::create([
                        'schedule_id' => $schedule->id,
                        'asset_id' => $schedule->asset_id,
                        'location_id' => $schedule->location_id,
                        'due_on' => $schedule->next_due_on,
                        'status' => 'scheduled',
                    ]);
                    $created++;
                }

                $next = match ($schedule->frequency) {
                    'weekly' => $schedule->next_due_on->copy()->addWeek(),
                    'quarterly' => $schedule->next_due_on->copy()->addMonths(3),
                    'six_monthly' => $schedule->next_due_on->copy()->addMonths(6),
                    'annual' => $schedule->next_due_on->copy()->addYear(),
                    'custom' => $schedule->next_due_on->copy()->addDays(max(1, (int) $schedule->interval_days)),
                    default => $schedule->next_due_on->copy()->addMonth(),
                };

                $schedule->update(['next_due_on' => $next]);
            }
        });

    $this->info("Generated {$created} inspection work item(s).");
})->purpose('Generate due preventive inspection work items from recurring schedules.');

Artisan::command('himu:escalate-sla', function (AuditService $audit) {
    $count = 0;
    Ticket::query()
        ->where('is_active', true)
        ->whereNull('escalated_at')
        ->whereNotIn('status', ['resolved', 'verified'])
        ->whereNotNull('resolution_due_at')
        ->where('resolution_due_at', '<=', now())
        ->chunkById(100, function ($tickets) use (&$count, $audit) {
            foreach ($tickets as $ticket) {
                $old = $ticket->status;
                $ticket->update(['status' => 'escalated', 'escalated_at' => now()]);
                $audit->record('SLA_AUTO_ESCALATED', $ticket, ['status' => $old], ['status' => 'escalated']);

                User::query()
                    ->where('is_active', true)
                    ->whereHas('roles', fn ($q) => $q->whereIn('name', ['system_admin', 'himu_admin', 'ict_manager', 'senior_ict_officer']))
                    ->each(fn (User $user) => $user->notify(new OperationsAlert(
                        'SLA escalation',
                        "{$ticket->ticket_no} has exceeded its resolution target.",
                        'critical',
                        route('tickets.show', $ticket),
                    )));
                $count++;
            }
        });
    $this->info("Escalated {$count} ticket(s).");
})->purpose('Escalate overdue SLA tickets and notify operational leads.');

Schedule::command('himu:generate-inspections')->dailyAt('00:05')->withoutOverlapping();
Schedule::command('himu:escalate-sla')->everyFiveMinutes()->withoutOverlapping();

Artisan::command('himu:collect-server-metrics', function (\App\Services\ServerMonitorService $monitor) {
    $saved = 0;
    $failed = 0;

    \App\Models\ServerNode::query()
        ->where('is_active', true)
        ->orderBy('id')
        ->each(function (\App\Models\ServerNode $server) use ($monitor, &$saved, &$failed) {
            try {
                $metrics = $monitor->metrics($server);
                \App\Models\ServerMetricSnapshot::create([
                    'server_node_id' => $server->id,
                    'captured_at' => now(),
                    'cpu_percent' => $metrics['cpu_percent'],
                    'memory_total_bytes' => $metrics['memory_total_bytes'],
                    'memory_used_bytes' => $metrics['memory_used_bytes'],
                    'memory_percent' => $metrics['memory_percent'],
                    'disk_total_bytes' => $metrics['disk_total_bytes'],
                    'disk_used_bytes' => $metrics['disk_used_bytes'],
                    'disk_percent' => $metrics['disk_percent'],
                    'load_1' => $metrics['load_1'],
                    'load_5' => $metrics['load_5'],
                    'load_15' => $metrics['load_15'],
                    'process_count' => $metrics['process_count'],
                    'uptime_seconds' => $metrics['uptime_seconds'],
                    'reachable' => true,
                ]);
                $server->forceFill(['last_seen_at' => now()])->saveQuietly();
                $saved++;
            } catch (\Throwable $e) {
                \App\Models\ServerMetricSnapshot::create([
                    'server_node_id' => $server->id,
                    'captured_at' => now(),
                    'reachable' => false,
                    'error_code' => class_basename($e),
                    'error_message' => \Illuminate\Support\Str::limit($e->getMessage(), 1000),
                ]);
                $failed++;
            }
        });

    $this->info("Stored {$saved} server snapshot(s); {$failed} failed check(s).");
})->purpose('Collect server health snapshots for monitoring history and reports.');

Schedule::command('himu:collect-server-metrics')->everyMinute()->withoutOverlapping();
