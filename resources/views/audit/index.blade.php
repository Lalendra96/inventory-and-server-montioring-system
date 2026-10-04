@extends('layouts.app')
@section('title','Audit Viewer')
@section('page-title','Audit Viewer & Integrity Verification')
@section('content')
    <div class="card">
        <div class="card-h">Tamper-Evident Audit Chain <span class="muted">HMAC-SHA-256</span>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Object</th>
                        <th>Hash</th>
                        <th>Verification</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $l)
                        <tr>
                            <td>{{ $l->occurred_at->format('d M Y H:i:s') }}</td>
                            <td>{{ $l->actor_id ?? 'System' }}</td>
                            <td>
                                <strong>{{ $l->action }}</strong>
                            </td>
                            <td>{{ $l->auditable_type }} #{{ $l->auditable_id }}</td>
                            <td>
                                <code title="{{ $l->integrity_hash }}">{{ substr($l->integrity_hash,0,14) }}…</code>
                            </td>
                            <td>
                                @if($l->verified_at)
                                    <span class="badge verified">VERIFIED</span>
                                @else
                                    @if(auth()->user()->hasRole('system_admin','himu_admin','auditor'))
                                        <form method="post" action="{{ route('audit.verify',$l) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-light">Verify HMAC</button>
                                        </form>
                                    @else
                                        <span class="muted">Pending</span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">No audit entries.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-b">{{ $logs->links() }}</div>
    </div>
@endsection
