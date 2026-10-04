@extends('layouts.app')
@section('title','Dashboard')
@section('page-title','ICT Operations, Support and Maintenance Dashboard')
@section('content')
    <div class="stats">
        <div class="stat">
            <div class="stat-label">Total Support Requests</div>
            <div class="stat-value">{{ $stats['total'] }}</div>
            <div class="stat-foot">All active records</div>
        </div>
        <div class="stat ok">
            <div class="stat-label">Resolved</div>
            <div class="stat-value">{{ $stats['resolved'] }}</div>
            <div class="stat-foot">Resolved / verified</div>
        </div>
        <div class="stat warn">
            <div class="stat-label">Open</div>
            <div class="stat-value">{{ $stats['open'] }}</div>
            <div class="stat-foot">Requires action</div>
        </div>
        <div class="stat danger">
            <div class="stat-label">High Priority</div>
            <div class="stat-value">{{ $stats['high'] }}</div>
            <div class="stat-foot">Active high priority</div>
        </div>
        <div class="stat danger">
            <div class="stat-label">Critical</div>
            <div class="stat-value">{{ $stats['critical'] }}</div>
            <div class="stat-foot">Immediate attention</div>
        </div>
        <div class="stat purple">
            <div class="stat-label">Preventive Inspections</div>
            <div class="stat-value">{{ $stats['inspection_rate'] }}%</div>
            <div class="stat-foot">Current month completion</div>
        </div>
    </div>
    <div class="dashboard-grid">
        <div class="card span-2">
            <div class="card-h">Support Requests Trend <span class="muted">Last 30 days</span>
            </div>
            <div class="card-b">
                <div class="chart-box">
                    <canvas data-chart="line" data-values='@json($trend->map(fn($x)=>["label"=>$x->day,"value"=>$x->total])->values())'>
                    </canvas>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-h">Requests by Category</div>
            <div class="card-b">
                <div class="chart-box">
                    <canvas data-chart="donut" data-values='@json($categoryData->map(fn($x)=>["label"=>$x->name??"Other","value"=>$x->total])->values())'>
                    </canvas>
                </div>
            </div>
        </div>
        <div class="card span-2">
            <div class="card-h">Open and Recent Issues <a class="btn btn-sm btn-primary" href="{{ route('tickets.create') }}">New Request</a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Location</th>
                            <th>Issue</th>
                            <th>Priority</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent as $t)
                            <tr>
                                <td>
                                    <a href="{{ route('tickets.show',$t) }}">
                                        <strong>{{ $t->ticket_no }}</strong>
                                    </a>
                                </td>
                                <td>{{ $t->location?->name ?? '—' }}</td>
                                <td>{{ $t->subject }}</td>
                                <td>
                                    <span class="badge {{ $t->priority }}">{{ strtoupper($t->priority) }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $t->status }}">{{ strtoupper(str_replace('_',' ',$t->status)) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty">No support requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-h">Requests by Location (Top 10)</div>
            <div class="card-b">
                <div class="chart-box">
                    <canvas data-chart="bar" data-values='@json($locationData->map(fn($x)=>["label"=>$x->name??"Unknown","value"=>$x->total])->values())'>
                    </canvas>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-h">Operational Snapshot</div>
            <div class="card-b">
                <div class="metric-row">
                    <div class="metric">
                        <strong>{{ $stats['assets'] }}</strong>
                        <br>
                        <span class="muted">Assets</span>
                    </div>
                    <div class="metric">
                        <strong>{{ $stats['downtime'] }}</strong>
                        <br>
                        <span class="muted">Downtime events</span>
                    </div>
                    <div class="metric">
                        <strong>{{ $stats['night_incidents'] }}</strong>
                        <br>
                        <span class="muted">Night incidents</span>
                    </div>
                    <div class="metric">
                        <strong>{{ $stats['software_open'] }}</strong>
                        <br>
                        <span class="muted">Software open</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-h">Equipment Replacement / Procurement</div>
            <div class="card-b">
                <div style="font-size:30px;font-weight:800">{{ $stats['replacement_open'] }}</div>
                <div class="muted">Active requests requiring workflow action</div>
                <p>
                    <a class="btn btn-light btn-sm" href="{{ route('procurement.index') }}">Open Workflow</a>
                </p>
            </div>
        </div>
        <div class="card">
            <div class="card-h">
                <span>System / Server Availability</span>
                <a class="btn btn-light btn-sm" href="{{ route('servers.index') }}">Server Dashboard</a>
            </div>

            <div class="card-b">
                <div class="availability-grid">
                    @foreach($systemAvailability as $system)
                        @php
                            $badgeClass = match($system['status']) {
                                'online' => 'active',
                                'degraded' => 'warning',
                                'offline' => 'critical',
                                default => '',
                            };

                            $statusText = match($system['status']) {
                                'online' => 'ONLINE',
                                'degraded' => 'DEGRADED',
                                'offline' => 'OFFLINE',
                                default => 'NOT ASSIGNED',
                            };
                        @endphp

                        <div class="availability-tile">
                            <strong>{{ $system['label'] }}</strong>
                            <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
                            <small>
                                @if($system['total'] > 0)
                                    {{ $system['online'] }}/{{ $system['total'] }} server{{ $system['total'] === 1 ? '' : 's' }} online
                                @else
                                    Assign servers in Administration
                                @endif
                            </small>
                        </div>
                    @endforeach

                    <div class="availability-tile availability-summary">
                        <strong>{{ $stats['servers_online'] }}/{{ $stats['servers_total'] }}</strong>
                        <span class="badge {{ $stats['servers_attention'] > 0 ? 'warning' : 'active' }}">
                            {{ $stats['servers_attention'] > 0 ? 'ATTENTION' : 'HEALTHY' }}
                        </span>
                        <small>
                            {{ $stats['servers_attention'] }} server{{ $stats['servers_attention'] === 1 ? '' : 's' }} need attention
                        </small>
                    </div>
                </div>

                <div class="availability-note">
                    Availability is based on configured server-to-system assignments and fresh SSH metric snapshots.
                    A server is treated as online only when its latest successful snapshot is no more than two minutes old.
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-h">Night Shift Incidents</div>
            <div class="card-b">
                <div style="font-size:30px;font-weight:800">{{ $stats['night_incidents'] }}</div>
                <div class="muted">Current month incidents</div>
                <p>
                    <a class="btn btn-light btn-sm" href="{{ route('incidents.index') }}">Review Incidents</a>
                </p>
            </div>
        </div>
        <div class="card">
            <div class="card-h">Software Development Requests</div>
            <div class="card-b">
                <div style="font-size:30px;font-weight:800">{{ $stats['software_open'] }}</div>
                <div class="muted">Requests not yet deployed/closed</div>
                <p>
                    <a class="btn btn-light btn-sm" href="{{ route('software.index') }}">Open Workflow</a>
                </p>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{ asset('assets/js/charts.js') }}" defer>
    </script>
@endpush
