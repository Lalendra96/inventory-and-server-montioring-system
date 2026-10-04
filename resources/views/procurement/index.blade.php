@extends('layouts.app')
@section('title','Procurement / Replacement')
@section('page-title','Procurement & Replacement Workflow')
@section('content')
    <div class="split">
        <div class="card">
            <div class="card-h">Requests</div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Asset</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Estimated</th>
                            <th>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $r)
                            <tr>
                                <td>
                                    <strong>{{ $r->request_no }}</strong>
                                </td>
                                <td>{{ ucwords(str_replace('_',' ',$r->request_type)) }}</td>
                                <td>{{ $r->asset_id ? 'Asset #'.$r->asset_id : 'New' }}</td>
                                <td>
                                    <span class="badge {{ $r->priority }}">{{ strtoupper($r->priority) }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $r->status }}">{{ strtoupper($r->status) }}</span>
                                </td>
                                <td>{{ $r->estimated_cost ? 'LKR '.number_format((float)$r->estimated_cost,2) : '—' }}</td>
                                <td>
                                    @if($r->status==='submitted' && auth()->user()->hasRole('system_admin','himu_admin','ict_manager'))
                                        <form method="post" action="{{ route('procurement.approve',$r) }}">
                                            @csrf
                                            <input type="hidden" name="decision_notes" value="Approved through system workflow">
                                            <button class="btn btn-success btn-sm">Approve</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty">No procurement/replacement requests.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-b">{{ $requests->links() }}</div>
        </div>
        <div class="card">
            <div class="card-h">Create Request</div>
            <div class="card-b">
                <form method="post" action="{{ route('procurement.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Asset (optional for new purchase)</label>
                        <select class="form-control" name="asset_id">
                            <option value="">New / not linked</option>
                            @foreach($assets as $a)
                                <option value="{{ $a->id }}">{{ $a->asset_tag }} — {{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Request Type</label>
                        <select class="form-control" name="request_type">
                            @foreach(['replacement','new_purchase','repair','upgrade'] as $t)
                                <option value="{{ $t }}">{{ ucwords(str_replace('_',' ',$t)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select class="form-control" name="priority">
                            @foreach(['normal','low','high','critical'] as $p)
                                <option value="{{ $p }}">{{ ucfirst($p) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estimated Cost</label>
                        <input class="form-control" type="number" min="0" step="0.01" name="estimated_cost">
                    </div>
                    <div class="form-group">
                        <label>Justification</label>
                        <textarea class="form-control" name="justification" required></textarea>
                    </div>
                    <button class="btn btn-primary" style="margin-top:10px">Submit</button>
                </form>
            </div>
        </div>
    </div>
@endsection
