@extends('layouts.app')
@section('title',$ticket->ticket_no)
@section('page-title','Ticket '.$ticket->ticket_no)
@section('content')
    <div class="split">
        <div class="stack">
            <div class="card">
                <div class="card-h">
                    <span>{{ $ticket->subject }}</span>
                    <span class="badge {{ $ticket->status }}">{{ strtoupper(str_replace('_',' ',$ticket->status)) }}</span>
                </div>
                <div class="card-b">
                    <div class="metric-row">
                        <div class="metric">
                            <span class="muted">Priority</span>
                            <br>
                            <span class="badge {{ $ticket->priority }}">{{ strtoupper($ticket->priority) }}</span>
                        </div>
                        <div class="metric">
                            <span class="muted">Location</span>
                            <br>
                            <strong>{{ $ticket->location?->name ?? '—' }}</strong>
                        </div>
                        <div class="metric">
                            <span class="muted">Category</span>
                            <br>
                            <strong>{{ $ticket->category?->name ?? '—' }}</strong>
                        </div>
                        <div class="metric">
                            <span class="muted">Assigned</span>
                            <br>
                            <strong>{{ $ticket->assignee?->name ?? 'Unassigned' }}</strong>
                        </div>
                    </div>
                    <h4>Description</h4>
                    <div style="white-space:pre-wrap">{{ $ticket->description }}</div>
                </div>
            </div>
            <div class="card">
                <div class="card-h">Comments</div>
                <div class="card-b">
                    <form method="post" action="{{ route('tickets.comments.store',$ticket) }}">
                        @csrf
                        <textarea class="form-control" name="comment" placeholder="Add operational note or comment" required></textarea>
                        <label style="display:block;margin:8px 0">
                            <input type="checkbox" name="internal_only" value="1" checked> Internal note</label>
                        <button class="btn btn-primary btn-sm">Add Comment</button>
                    </form>
                    <hr style="border:0;border-top:1px solid #e2e8ee;margin:16px 0">
                    @forelse($ticket->comments as $c)
                        <div style="margin-bottom:12px">
                            <strong>User #{{ $c->user_id }}</strong>
                            <span class="muted">{{ $c->created_at->format('d M H:i') }}</span>
                            <div style="white-space:pre-wrap;margin-top:4px">{{ $c->comment }}</div>
                        </div>
                    @empty
                        <div class="empty">No comments yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="stack">
            <div class="card">
                <div class="card-h">Assignment & Workflow</div>
                <div class="card-b">
                    <form method="post" action="{{ route('tickets.assign',$ticket) }}">
                        @csrf
                        <div class="form-group">
                            <label>Assign to</label>
                            <select class="form-control" name="assigned_to">
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" @selected($ticket->assigned_to===$u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-light btn-sm" style="margin-top:8px">Update Assignment</button>
                    </form>
                    <hr style="border:0;border-top:1px solid #e2e8ee;margin:14px 0">
                    <form method="post" action="{{ route('tickets.status',$ticket) }}">
                        @csrf
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-control" name="status">
                                @foreach(['open','assigned','acknowledged','in_progress','escalated','resolved','verified'] as $s)
                                    <option value="{{ $s }}" @selected($ticket->status===$s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary btn-sm" style="margin-top:8px">Update Status</button>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-h">SLA</div>
                <div class="card-b">
                    <p>
                        <span class="muted">Response due</span>
                        <br>
                        <strong>{{ $ticket->response_due_at?->format('d M Y H:i') ?? 'Not configured' }}</strong>
                    </p>
                    <p>
                        <span class="muted">Resolution due</span>
                        <br>
                        <strong>{{ $ticket->resolution_due_at?->format('d M Y H:i') ?? 'Not configured' }}</strong>
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-h">Attachments</div>
                <div class="card-b">
                    @forelse($ticket->attachments->where('is_active',true) as $a)
                        <div>
                            <a href="{{ route('tickets.attachments.download',[$ticket,$a]) }}">{{ $a->original_name }}</a>
                            <span class="muted">({{ number_format($a->size_bytes/1024,1) }} KB)</span>
                        </div>
                    @empty
                        <div class="muted">No attachments.</div>
                    @endforelse
                </div>
            </div>
            <div class="card">
                <div class="card-h">Timeline</div>
                <div class="card-b">
                    <div class="timeline">
                        @foreach($ticket->events as $e)
                            <div class="timeline-item">
                                <strong>{{ ucwords(str_replace('_',' ',$e->event)) }}</strong>
                                <div class="timeline-time">{{ $e->occurred_at->format('d M Y H:i:s') }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
