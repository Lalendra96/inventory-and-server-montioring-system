@extends('layouts.app')
@section('title','Support Requests')
@section('page-title','Support Requests')
@section('content')
    <div class="toolbar">
        <a class="btn btn-primary" href="{{ route('tickets.create') }}">+ New Support Request</a>
        <form method="get" style="display:flex;gap:8px;flex-wrap:wrap">
            <input class="form-control" style="width:230px" name="q" placeholder="Ticket or subject" value="{{ request('q') }}">
            <select class="form-control" name="status" style="width:150px">
                <option value="">All statuses</option>
                @foreach(['open','assigned','acknowledged','in_progress','escalated','resolved','verified'] as $s)
                    <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <select class="form-control" name="priority" style="width:140px">
                <option value="">All priorities</option>
                @foreach(['low','normal','high','critical'] as $p)
                    <option value="{{ $p }}" @selected(request('priority')===$p)>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
            <button class="btn btn-light">Filter</button>
        </form>
    </div>
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Issue</th>
                        <th>Assigned</th>
                        <th>Priority</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $t)
                        <tr>
                            <td>
                                <a href="{{ route('tickets.show',$t) }}">
                                    <strong>{{ $t->ticket_no }}</strong>
                                </a>
                            </td>
                            <td>{{ $t->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $t->location?->name ?? '—' }}</td>
                            <td>{{ $t->subject }}</td>
                            <td>{{ $t->assignee?->name ?? 'Unassigned' }}</td>
                            <td>
                                <span class="badge {{ $t->priority }}">{{ strtoupper($t->priority) }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $t->status }}">{{ strtoupper(str_replace('_',' ',$t->status)) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty">No tickets found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-b">{{ $tickets->links() }}</div>
    </div>
@endsection
