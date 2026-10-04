@extends('layouts.app')
@section('title','Night Shift Incidents')
@section('page-title','Night Shift Incidents & Handover')
@section('content')
    <div class="split">
        <div class="card">
            <div class="card-h">Incident Timeline</div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Time</th>
                            <th>Severity</th>
                            <th>Incident</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incidents as $i)
                            <tr>
                                <td>
                                    <strong>{{ $i->incident_no }}</strong>
                                </td>
                                <td>{{ $i->occurred_at->format('d M H:i') }}</td>
                                <td>
                                    <span class="badge {{ $i->severity }}">{{ strtoupper($i->severity) }}</span>
                                </td>
                                <td>{{ $i->title }}<br>
                                    <span class="muted">{{ $i->handover_notes }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $i->status }}">{{ strtoupper($i->status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty">No night-shift incidents recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-b">{{ $incidents->links() }}</div>
        </div>
        <div class="card">
            <div class="card-h">Record Incident</div>
            <div class="card-b">
                <form method="post" action="{{ route('incidents.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Location</label>
                        <select class="form-control" name="location_id">
                            <option value="">Select</option>
                            @foreach($locations as $l)
                                <option value="{{ $l->id }}">{{ $l->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Severity</label>
                        <select class="form-control" name="severity">
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Occurred At</label>
                        <input class="form-control" type="datetime-local" name="occurred_at" required>
                    </div>
                    <div class="form-group">
                        <label>Title</label>
                        <input class="form-control" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Details</label>
                        <textarea class="form-control" name="details" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Handover Notes</label>
                        <textarea class="form-control" name="handover_notes"></textarea>
                    </div>
                    <button class="btn btn-primary" style="margin-top:10px">Record Incident</button>
                </form>
            </div>
        </div>
    </div>
@endsection
