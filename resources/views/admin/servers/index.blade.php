@extends('layouts.app')
@section('title','Server Configuration')
@section('page-title','Server Monitoring Configuration')
@section('content')
    <div class="card" style="margin-bottom:14px">
        <div class="card-h">Add Server Profile</div>
        <div class="card-b">
            <div class="alert" style="background: #f6f9fc; border-color: #d6e0e8;">
                Use SSH public-key authentication only. The Laravel host must already trust
                each server's verified SSH host key. Do not place private-key contents, root
                passwords, or sudo passwords in the database.
            </div>
            <form method="post" action="{{ route('admin.servers.store') }}" class="form-grid">
                @csrf
                <div class="form-group">
                    <label>Code</label>
                    <input class="form-control" name="code" required placeholder="HIMS-APP-01">
                </div>
                <div class="form-group">
                    <label>Display Name</label>
                    <input class="form-control" name="name" required placeholder="HIMS Application Server">
                </div>
                <div class="form-group">
                    <label>Host / IP</label>
                    <input class="form-control" name="host" required placeholder="172.16.x.x">
                </div>
                <div class="form-group">
                    <label>SSH Port</label>
                    <input class="form-control" type="number" name="ssh_port" value="22" min="1" max="65535" required>
                </div>
                <div class="form-group">
                    <label>SSH User</label>
                    <input class="form-control" name="ssh_user" required placeholder="himu-monitor">
                </div>
                <div class="form-group">
                    <label>SSH Private Key Path on Laravel Server</label>
                    <input class="form-control mono" name="ssh_key_path" required placeholder="/var/www/.ssh/himu_monitor_ed25519">
                </div>
                <div class="form-group">
                    <label>Environment</label>
                    <select class="form-control" name="environment">
                        <option value="production">Production</option>
                        <option value="staging">Staging</option>
                        <option value="test">Test</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>System / Workload Group</label>
                    <select class="form-control" name="system_group" required>
                        <option value="other">Other / Unassigned</option>
                        <option value="hims">HIMS</option>
                        <option value="lims">LIMS</option>
                        <option value="ris_pacs">RIS / PACS</option>
                        <option value="infrastructure">Infrastructure</option>
                    </select>
                    <div class="form-help">Used by the main dashboard to calculate real HIMS/LIMS/RIS-PACS availability.</div>
                </div>
                <div class="form-group">
                    <label>OS Hint</label>
                    <input class="form-control" name="os_hint" placeholder="Ubuntu 20.04 LTS">
                </div>
                <div class="form-group">
                    <label>Refresh Interval (seconds)</label>
                    <input class="form-control" type="number" name="poll_interval_seconds" value="10" min="5" max="60" required>
                </div>
                <div class="form-group">
                    <label>Allowed systemd services (one per line)</label>
                    <textarea class="form-control mono" name="allowed_services_text" placeholder="nginx\npostgresql\nphp8.2-fpm"></textarea>
                </div>
                <div class="form-group">
                    <label>Additional protected process patterns (one per line)</label>
                    <textarea class="form-control mono" name="protected_process_patterns_text" placeholder="postgres\nmysqld\nnginx"></textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="allow_process_control" value="1"> Enable controlled process termination</label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="allow_service_control" value="1"> Enable allow-listed service start/stop/restart</label>
                </div>
                <div class="form-group full">
                    <label>Notes</label>
                    <textarea class="form-control" name="notes"></textarea>
                </div>
                <div class="full">
                    <button class="btn btn-primary">Add Server</button>
                </div>
            </form>
        </div>
    </div>
    <div class="stack">
        @forelse($servers as $server)
            <div class="card">
                <div class="card-h">
                    <span>{{ $server->name }} <span class="badge {{ $server->is_active ? 'active' : 'critical' }}">{{ $server->is_active ? 'ACTIVE' : 'DISABLED' }}</span>
                    </span>
                    <span class="mono muted">{{ $server->host }}:{{ $server->ssh_port }}</span>
                </div>
                <div class="card-b">
                    <form method="post" action="{{ route('admin.servers.update',$server) }}" class="form-grid">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label>Code</label>
                            <input class="form-control" name="code" value="{{ $server->code }}" required>
                        </div>
                        <div class="form-group">
                            <label>Name</label>
                            <input class="form-control" name="name" value="{{ $server->name }}" required>
                        </div>
                        <div class="form-group">
                            <label>Host / IP</label>
                            <input class="form-control" name="host" value="{{ $server->host }}" required>
                        </div>
                        <div class="form-group">
                            <label>SSH Port</label>
                            <input class="form-control" type="number" name="ssh_port" value="{{ $server->ssh_port }}" required>
                        </div>
                        <div class="form-group">
                            <label>SSH User</label>
                            <input class="form-control" name="ssh_user" value="{{ $server->ssh_user }}" required>
                        </div>
                        <div class="form-group">
                            <label>Private Key Path</label>
                            <input class="form-control mono" name="ssh_key_path" value="{{ $server->ssh_key_path }}" required>
                        </div>
                        <div class="form-group">
                            <label>Environment</label>
                            <input class="form-control" name="environment" value="{{ $server->environment }}" required>
                        </div>
                        <div class="form-group">
                            <label>System / Workload Group</label>
                            <select class="form-control" name="system_group" required>
                                <option value="other" @selected($server->system_group === 'other')>Other / Unassigned</option>
                                <option value="hims" @selected($server->system_group === 'hims')>HIMS</option>
                                <option value="lims" @selected($server->system_group === 'lims')>LIMS</option>
                                <option value="ris_pacs" @selected($server->system_group === 'ris_pacs')>RIS / PACS</option>
                                <option value="infrastructure" @selected($server->system_group === 'infrastructure')>Infrastructure</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>OS Hint</label>
                            <input class="form-control" name="os_hint" value="{{ $server->os_hint }}">
                        </div>
                        <div class="form-group">
                            <label>Refresh Interval</label>
                            <input
                                class="form-control"
                                type="number"
                                min="5"
                                max="60"
                                name="poll_interval_seconds"
                                value="{{ $server->poll_interval_seconds }}"
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label>Allowed Services</label>
                            <textarea class="form-control mono" name="allowed_services_text">{{ implode("\n",$server->allowed_services ?? []) }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Protected Process Patterns</label>
                            <textarea class="form-control mono" name="protected_process_patterns_text">{{ implode("\n",$server->protected_process_patterns ?? []) }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="allow_process_control" value="1" @checked($server->allow_process_control)> Enable process control</label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="allow_service_control" value="1" @checked($server->allow_service_control)> Enable service control</label>
                        </div>
                        <div class="form-group full">
                            <label>Notes</label>
                            <textarea class="form-control" name="notes">{{ $server->notes }}</textarea>
                        </div>
                        <div class="full">
                            <button class="btn btn-primary">Save Configuration</button>
                        </div>
                    </form>
                    <div class="toolbar" style="margin-top:12px">
                        @if($server->is_active)
                            <form method="post" action="{{ route('admin.servers.disable',$server) }}" class="toolbar">
                                @csrf
                                <input class="form-control" name="reason" required minlength="5" placeholder="Reason for disabling">
                                <button
                                    class="btn btn-danger"
                                    data-confirm="Disable monitoring for this server? Historical data will be retained."
                                >
                                    Disable
                                </button>
                            </form>
                        @else
                            <form method="post" action="{{ route('admin.servers.enable',$server) }}">
                                @csrf
                                <button class="btn btn-success">Re-enable</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="empty">No server profiles have been configured yet.</div>
            </div>
        @endforelse
    </div>
@endsection
