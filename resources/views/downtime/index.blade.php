@extends('layouts.app')
@section('title','System Downtime')
@section('page-title','System Downtime Tracking')
@section('content')
    <div class="split">
        <div class="card">
            <div class="card-h">Downtime History</div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>System</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Classification</th>
                            <th>Verified</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $r)
                            <tr>
                                <td>#{{ $r->system_id ?? '—' }}</td>
                                <td>{{ $r->started_at->format('d M Y H:i') }}</td>
                                <td>{{ $r->ended_at?->format('d M Y H:i') ?? 'Ongoing' }}</td>
                                <td>{{ $r->classification }}</td>
                                <td>{{ $r->verified_at ? 'Yes' : 'No' }}
                                    @if(!$r->verified_at && auth()->user()->hasRole('system_admin','himu_admin','ict_manager'))
                                        <form method="post" action="{{ route('downtime.verify',$r) }}" style="display:inline">
                                            @csrf
                                            <button class="btn btn-sm btn-light">Verify</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty">No downtime recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-b">{{ $records->links() }}</div>
        </div>
        <div class="card">
            <div class="card-h">Record Downtime</div>
            <div class="card-b">
                <form method="post" action="{{ route('downtime.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>System</label>
                        <select class="form-control" name="system_id">
                            <option value="">Select</option>
                            @foreach($systems as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Classification</label>
                        <input class="form-control" name="classification" required>
                    </div>
                    <div class="form-group">
                        <label>Started At</label>
                        <input class="form-control" type="datetime-local" name="started_at" required>
                    </div>
                    <div class="form-group">
                        <label>Ended At</label>
                        <input class="form-control" type="datetime-local" name="ended_at">
                    </div>
                    <div class="form-group">
                        <label>Impact</label>
                        <textarea class="form-control" name="impact"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Root Cause</label>
                        <textarea class="form-control" name="root_cause"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Resolution</label>
                        <textarea class="form-control" name="resolution"></textarea>
                    </div>
                    <button class="btn btn-primary" style="margin-top:10px">Record Downtime</button>
                </form>
            </div>
        </div>
    </div>
@endsection
