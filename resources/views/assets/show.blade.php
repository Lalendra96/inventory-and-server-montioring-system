@extends('layouts.app')
@section('title',$asset->asset_tag)
@section('page-title','Asset '.$asset->asset_tag)
@section('content')
    <div class="split">
        <div class="stack">
            <div class="card">
                <div class="card-h">{{ $asset->name }} <span class="badge {{ $asset->status }}">{{ strtoupper($asset->status) }}</span>
                </div>
                <div class="card-b">
                    <div class="metric-row">
                        <div class="metric">
                            <span class="muted">Category</span>
                            <br>
                            <strong>{{ $asset->category?->name ?? '—' }}</strong>
                        </div>
                        <div class="metric">
                            <span class="muted">Location</span>
                            <br>
                            <strong>{{ $asset->location?->name ?? '—' }}</strong>
                        </div>
                        <div class="metric">
                            <span class="muted">Serial</span>
                            <br>
                            <strong>{{ $asset->serial_number ?? '—' }}</strong>
                        </div>
                        <div class="metric">
                            <span class="muted">Warranty</span>
                            <br>
                            <strong>{{ $asset->warranty_until?->format('d M Y') ?? '—' }}</strong>
                        </div>
                    </div>
                    <p>{{ $asset->manufacturer }} {{ $asset->model }}</p>
                    <p class="muted">{{ $asset->notes }}</p>
                    <a class="btn btn-light" href="{{ route('assets.edit',$asset) }}">Edit Asset</a>
                </div>
            </div>
            <div class="card">
                <div class="card-h">Fault History</div>
                <div class="card-b">
                    @forelse($asset->faults as $f)
                        <div class="feature-row">
                            <div>
                                <strong>{{ $f->fault_type ?? 'Fault' }}</strong>
                                <div>{{ $f->description }}</div>
                                <span class="muted">{{ $f->reported_at->format('d M Y H:i') }}
                                    @if($f->resolved_at)
                                        · Resolved {{ $f->resolved_at->format('d M Y H:i') }}
                                    @endif
                                </span>
                            </div>
                            @if(!$f->resolved_at)
                                <form method="post" action="{{ route('assets.faults.resolve',[$asset,$f]) }}">
                                    @csrf
                                    <button class="btn btn-success btn-sm">Resolve</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="empty">No recorded faults.</div>
                    @endforelse
                </div>
            </div>
            <div class="card">
                <div class="card-h">Movement History</div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($asset->movements as $m)
                                <tr>
                                    <td>{{ $m->moved_at->format('d M Y H:i') }}</td>
                                    <td>#{{ $m->from_location_id ?? '—' }}</td>
                                    <td>#{{ $m->to_location_id ?? '—' }}</td>
                                    <td>{{ $m->reason ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="empty">No movement history.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="stack">
            <div class="card">
                <div class="card-h">Transfer Asset</div>
                <div class="card-b">
                    <form method="post" action="{{ route('assets.move',$asset) }}">
                        @csrf
                        <div class="form-group">
                            <label>New Location</label>
                            <select class="form-control" name="to_location_id" required>
                                @foreach($locations as $l)
                                    <option value="{{ $l->id }}" @selected($asset->location_id===$l->id)>{{ $l->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Reason</label>
                            <textarea class="form-control" name="reason"></textarea>
                        </div>
                        <button class="btn btn-primary" style="margin-top:8px">Record Transfer</button>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-h">Record Fault</div>
                <div class="card-b">
                    <form method="post" action="{{ route('assets.faults.store',$asset) }}">
                        @csrf
                        <div class="form-group">
                            <label>Fault Type</label>
                            <input class="form-control" name="fault_type">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" required></textarea>
                        </div>
                        <button class="btn btn-primary" style="margin-top:8px">Add Fault</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
