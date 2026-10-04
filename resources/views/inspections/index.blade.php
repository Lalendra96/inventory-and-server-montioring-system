@extends('layouts.app')
@section('title','Preventive Inspections')
@section('page-title','Recurring Preventive Inspections')
@section('content')
    <div class="dashboard-grid">
        <div class="card span-2">
            <div class="card-h">Inspection Worklist</div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Due</th>
                            <th>Asset</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inspections as $i)
                            <tr>
                                <td>{{ $i->due_on->format('d M Y') }}</td>
                                <td>{{ $i->asset_id ? 'Asset #'.$i->asset_id : '—' }}</td>
                                <td>{{ $i->location_id ? 'Location #'.$i->location_id : '—' }}</td>
                                <td>
                                    <span class="badge {{ $i->status }}">{{ strtoupper($i->status) }}</span>
                                </td>
                                <td>
                                    @if($i->status!=='completed')
                                        <form method="post" action="{{ route('inspections.complete',$i) }}">
                                            @csrf
                                            <input type="hidden" name="remarks" value="Completed from worklist">
                                            <button class="btn btn-success btn-sm">Complete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty">No inspection instances generated yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-b">{{ $inspections->links() }}</div>
        </div>
        <div class="card">
            <div class="card-h">Recurring Schedules</div>
            <div class="card-b">
                @forelse($schedules as $s)
                    <div style="padding:9px 0;border-bottom:1px solid #e5ebf0">
                        <strong>{{ ucfirst($s->frequency) }}</strong>
                        <br>
                        <span class="muted">Next: {{ $s->next_due_on->format('d M Y') }}</span>
                    </div>
                @empty
                    <div class="empty">No schedules configured.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
