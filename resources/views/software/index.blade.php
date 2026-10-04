@extends('layouts.app')
@section('title','Software Requests')
@section('page-title','Software Request Workflow')
@section('content')
    <div class="split">
        <div class="card">
            <div class="card-h">Software Requests</div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Request</th>
                            <th>Title</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Workflow</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $r)
                            <tr>
                                <td>
                                    <strong>{{ $r->request_no }}</strong>
                                </td>
                                <td>{{ $r->title }}</td>
                                <td>
                                    <span class="badge {{ $r->priority }}">{{ strtoupper($r->priority) }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $r->status }}">{{ strtoupper(str_replace('_',' ',$r->status)) }}</span>
                                </td>
                                <td>
                                    @if(auth()->user()->hasRole('system_admin','himu_admin','software_developer','ict_manager'))
                                        <form method="post" action="{{ route('software.status',$r) }}" style="display:flex;gap:5px">
                                            @csrf
                                            <select class="form-control" name="status">
                                                @foreach(['requested','reviewed','approved','development','internal_test','uat','approved_for_deployment','deployed','verified','closed'] as $s)
                                                    <option value="{{ $s }}" @selected($r->status===$s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-sm btn-light">Save</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty">No software requests.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-b">{{ $requests->links() }}</div>
        </div>
        <div class="card">
            <div class="card-h">New Software Request</div>
            <div class="card-b">
                <form method="post" action="{{ route('software.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Title</label>
                        <input class="form-control" name="title" required>
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
                        <label>Description / Business Need</label>
                        <textarea class="form-control" name="description" required></textarea>
                    </div>
                    <button class="btn btn-primary" style="margin-top:10px">Submit Request</button>
                </form>
            </div>
        </div>
    </div>
@endsection
