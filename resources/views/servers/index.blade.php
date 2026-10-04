@extends('layouts.app')

@section('title', 'Server Monitoring')
@section('page-title', 'Server Monitoring Dashboard')

@section('content')
    <div class="toolbar server-page-toolbar no-print">
        <div>
            <h2 class="section-title server-page-title">Infrastructure Health</h2>
            <div class="muted">
                Live SSH-based monitoring for configured Ubuntu servers. A server is considered online
                when its latest successful snapshot is no more than two minutes old.
            </div>
        </div>

        @if(auth()->user()->isAdmin())
            <a class="btn btn-primary" href="{{ route('admin.servers.index') }}">
                Configure Servers
            </a>
        @endif
    </div>

    <div class="server-summary-grid">
        <div class="server-summary-card">
            <span class="server-summary-label">Configured</span>
            <strong>{{ $summary['total'] }}</strong>
            <span class="muted">Active server profiles</span>
        </div>

        <div class="server-summary-card server-summary-ok">
            <span class="server-summary-label">Online</span>
            <strong>{{ $summary['online'] }}</strong>
            <span class="muted">Seen within 2 minutes</span>
        </div>

        <div class="server-summary-card {{ $summary['attention'] > 0 ? 'server-summary-warn' : 'server-summary-ok' }}">
            <span class="server-summary-label">Needs Attention</span>
            <strong>{{ $summary['attention'] }}</strong>
            <span class="muted">Offline, stale or resource warning</span>
        </div>

        <div class="server-summary-card">
            <span class="server-summary-label">Not Checked</span>
            <strong>{{ $summary['not_checked'] }}</strong>
            <span class="muted">No metric snapshot yet</span>
        </div>
    </div>

    @if($servers->isEmpty())
        <div class="card">
            <div class="empty">
                No active servers are configured. System Administrators can add server profiles under
                Administration → Server Configuration.
            </div>
        </div>
    @else
        <div class="server-grid server-grid-dashboard">
            @foreach($servers as $server)
                @php
                    $latest = $server->latestSnapshot;
                    $isFresh = $latest?->captured_at?->gte(now()->subMinutes(2)) ?? false;
                    $isOnline = $isFresh && (bool) $latest?->reachable;
                    $resourceWarning = $isOnline && (
                        (float) ($latest?->cpu_percent ?? 0) >= 85
                        || (float) ($latest?->memory_percent ?? 0) >= 85
                        || (float) ($latest?->disk_percent ?? 0) >= 85
                    );

                    $statusClass = !$latest || !$isOnline
                        ? 'critical'
                        : ($resourceWarning ? 'warning' : 'active');

                    $statusText = !$latest
                        ? 'NOT CHECKED'
                        : (!$isOnline ? 'STALE / OFFLINE' : ($resourceWarning ? 'ATTENTION' : 'ONLINE'));
                @endphp

                <a class="card server-card server-card-enhanced" href="{{ route('servers.show', $server) }}">
                    <div class="server-card-head">
                        <div>
                            <div class="server-name-row">
                                <strong>{{ $server->name }}</strong>
                                <span class="server-group-label">
                                    {{ strtoupper(str_replace('_', ' / ', $server->system_group ?? 'other')) }}
                                </span>
                            </div>
                            <div class="muted mono">{{ $server->host }}:{{ $server->ssh_port }}</div>
                        </div>

                        <span class="badge {{ $statusClass }}">
                            {{ $statusText }}
                        </span>
                    </div>

                    <div class="server-metrics-mini">
                        <div>
                            <span>CPU</span>
                            <strong>{{ $latest?->cpu_percent !== null ? number_format($latest->cpu_percent, 1).'%' : '—' }}</strong>
                            <div class="mini-meter">
                                <i style="width: {{ min(100, (float) ($latest?->cpu_percent ?? 0)) }}%"></i>
                            </div>
                        </div>

                        <div>
                            <span>Memory</span>
                            <strong>{{ $latest?->memory_percent !== null ? number_format($latest->memory_percent, 1).'%' : '—' }}</strong>
                            <div class="mini-meter">
                                <i style="width: {{ min(100, (float) ($latest?->memory_percent ?? 0)) }}%"></i>
                            </div>
                        </div>

                        <div>
                            <span>Disk</span>
                            <strong>{{ $latest?->disk_percent !== null ? number_format($latest->disk_percent, 1).'%' : '—' }}</strong>
                            <div class="mini-meter">
                                <i style="width: {{ min(100, (float) ($latest?->disk_percent ?? 0)) }}%"></i>
                            </div>
                        </div>

                        <div>
                            <span>Processes</span>
                            <strong>{{ $latest?->process_count ?? '—' }}</strong>
                            <div class="server-submetric">Load {{ $latest?->load_1 !== null ? number_format($latest->load_1, 2) : '—' }}</div>
                        </div>
                    </div>

                    <div class="server-card-footer">
                        <div>
                            <span class="muted">Last snapshot</span>
                            <strong>{{ $latest?->captured_at?->format('d M Y H:i:s') ?? 'Not available' }}</strong>
                        </div>
                        <span class="server-open-link">Open Live View →</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
