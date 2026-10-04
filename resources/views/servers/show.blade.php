@extends('layouts.app')
@section('title',$server->name)
@section('page-title','Server: '.$server->name)
@section('content')
    <div id="server-monitor"
    data-live-url="{{ route('servers.live',$server) }}"
    data-process-url="{{ route('servers.processes',$server) }}"
    data-services-url="{{ route('servers.services',$server) }}"
    data-process-action-url="{{ route('servers.process-action',$server) }}"
    data-service-action-url="{{ route('servers.service-action',$server) }}"
    data-poll-seconds="{{ max(5,min(60,$server->poll_interval_seconds)) }}"
    data-can-control="{{ $canControl ? '1' : '0' }}">
    <div class="toolbar no-print">
        <a class="btn btn-light" href="{{ route('servers.index') }}">← All Servers</a>
        @if(auth()->user()->isAdmin())
            <a class="btn btn-light" href="{{ route('admin.servers.index') }}">Configuration</a>
        @endif
        <span id="live-state" class="badge">CONNECTING</span>
        <span class="muted" id="last-updated">Waiting for live data…</span>
    </div>
    <div class="stats server-live-stats">
        <div class="stat">
            <div class="stat-label">CPU</div>
            <div class="stat-value" data-metric="cpu_percent">—</div>
            <div class="progress">
                <i data-progress="cpu_percent">
                </i>
            </div>
        </div>
        <div class="stat ok">
            <div class="stat-label">Memory</div>
            <div class="stat-value" data-metric="memory_percent">—</div>
            <div class="progress">
                <i data-progress="memory_percent">
                </i>
            </div>
        </div>
        <div class="stat warn">
            <div class="stat-label">Root Disk</div>
            <div class="stat-value" data-metric="disk_percent">—</div>
            <div class="progress">
                <i data-progress="disk_percent">
                </i>
            </div>
        </div>
        <div class="stat purple">
            <div class="stat-label">Load (1m)</div>
            <div class="stat-value" data-metric="load_1">—</div>
            <div class="stat-foot">5m <span data-metric="load_5">—</span> · 15m <span data-metric="load_15">—</span>
            </div>
        </div>
        <div class="stat">
            <div class="stat-label">Processes</div>
            <div class="stat-value" data-metric="process_count">—</div>
            <div class="stat-foot">Live process table below</div>
        </div>
        <div class="stat">
            <div class="stat-label">Uptime</div>
            <div class="stat-value small-stat" data-metric="uptime">—</div>
            <div class="stat-foot" data-metric="os">{{ $server->os_hint ?: 'Ubuntu' }}</div>
        </div>
    </div>
    <div class="dashboard-grid">
        <div class="card span-2">
            <div class="card-h">24-hour CPU history <span class="muted">Stored snapshots</span>
            </div>
            <div class="card-b">
                <div class="chart-box">
                    <canvas data-chart="line" data-values='@json($history->map(fn($x)=>["label"=>$x->captured_at->format("H:i"),"value"=>$x->cpu_percent])->values())'>
                    </canvas>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-h">Server Identity</div>
            <div class="card-b server-detail-list">
                <div>
                    <span>Profile</span>
                    <strong>{{ $server->code }}</strong>
                </div>
                <div>
                    <span>Host</span>
                    <strong class="mono">{{ $server->host }}</strong>
                </div>
                <div>
                    <span>Environment</span>
                    <strong>{{ strtoupper($server->environment) }}</strong>
                </div>
                <div>
                    <span>Remote hostname</span>
                    <strong data-metric="hostname">—</strong>
                </div>
                <div>
                    <span>Kernel</span>
                    <strong data-metric="kernel">—</strong>
                </div>
                <div>
                    <span>Memory</span>
                    <strong data-metric="memory_text">—</strong>
                </div>
                <div>
                    <span>Disk</span>
                    <strong data-metric="disk_text">—</strong>
                </div>
            </div>
        </div>
        <div class="card span-3">
            <div class="card-h">
                <span>Live Processes</span>
                <div class="toolbar" style="margin:0">
                    <input id="process-filter" class="form-control" style="width:260px" placeholder="Filter PID, user or command">
                    <button class="btn btn-light btn-sm" type="button" id="refresh-processes">Refresh</button>
                </div>
            </div>
            <div class="table-wrap process-table-wrap">
                <table class="table" id="process-table">
                    <thead>
                        <tr>
                            <th>PID</th>
                            <th>User</th>
                            <th>State</th>
                            <th>CPU</th>
                            <th>Memory</th>
                            <th>Elapsed</th>
                            <th>Command</th>
                            <th class="no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="8" class="empty">Loading processes…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-b muted" style="font-size: 11px;">
                Process actions require an operational reason, are role-restricted, HMAC-audited,
                and retained in the server action history. Core operating-system processes are protected.
            </div>
        </div>
        <div class="card span-2">
            <div class="card-h">Allow-listed Services</div>
            <div class="table-wrap">
                <table class="table" id="services-table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Status</th>
                            <th class="no-print">Controls</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="3" class="empty">Loading services…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-h">Control Policy</div>
            <div class="card-b server-detail-list">
                <div>
                    <span>Process control</span>
                    <strong>{{ $server->allow_process_control ? 'Enabled' : 'Disabled' }}</strong>
                </div>
                <div>
                    <span>Service control</span>
                    <strong>{{ $server->allow_service_control ? 'Enabled' : 'Disabled' }}</strong>
                </div>
                <div>
                    <span>Refresh interval</span>
                    <strong>{{ $server->poll_interval_seconds }} sec</strong>
                </div>
                <div>
                    <span>SSH</span>
                    <strong>Key only / strict host key</strong>
                </div>
            </div>
        </div>
        <div class="card span-3">
            <div class="card-h">Recent Process / Service Actions <span class="muted">No deletion</span>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Target</th>
                            <th>Operation</th>
                            <th>Reason</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($actions as $action)
                            <tr>
                                <td>{{ $action->executed_at?->format('d M H:i:s') }}</td>
                                <td>{{ $action->actor?->name }}</td>
                                <td>{{ str_replace('_',' ',$action->action) }}</td>
                                <td class="mono">{{ $action->target }}</td>
                                <td>{{ strtoupper($action->signal_or_operation ?? '—') }}</td>
                                <td>{{ $action->reason }}</td>
                                <td>
                                    <span class="badge {{ $action->successful ? 'active' : 'critical' }}">{{ $action->successful ? 'SUCCESS' : 'FAILED' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty">No remote control actions have been recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
    <script src="{{ asset('assets/js/charts.js') }}" defer>
    </script>
    <script src="{{ asset('assets/js/server-monitor.js') }}" defer>
    </script>
@endpush
