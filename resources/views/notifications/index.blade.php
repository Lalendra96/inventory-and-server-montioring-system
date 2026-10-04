@extends('layouts.app')
@section('title','Notifications')
@section('page-title','Notification Centre')
@section('content')
    <div class="card">
        <div class="card-h">Notifications</div>
        <div class="card-b">
            @forelse($notifications as $n)
                <div class="feature-row">
                    <div>
                        <strong>{{ data_get($n->data,'title','Notification') }}</strong>
                        <div>{{ data_get($n->data,'message','') }}</div>
                        <span class="muted">{{ $n->created_at->format('d M Y H:i') }}</span>
                    </div>
                    @if(!$n->read_at)
                        <form method="post" action="{{ route('notifications.read',$n->id) }}">
                            @csrf
                            <button class="btn btn-sm btn-light">Mark Read</button>
                        </form>
                    @else
                        <span class="badge verified">READ</span>
                    @endif
                </div>
            @empty
                <div class="empty">No notifications.</div>
            @endforelse
            {{ $notifications->links() }}</div>
    </div>
@endsection
