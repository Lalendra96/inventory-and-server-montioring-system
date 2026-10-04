@extends('layouts.app')
@section('title','Users & Roles')
@section('page-title','Administration · Users & Roles')
@section('content')
    <div class="split">
        <div class="card">
            <div class="card-h">User Accounts</div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Staff / Unit</th>
                            <th>Roles</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $u)
                            <tr>
                                <td>
                                    <strong>{{ $u->name }}</strong>
                                    <br>
                                    <span class="muted">{{ $u->username }} · {{ $u->email }}</span>
                                </td>
                                <td>{{ $u->staff_no ?? '—' }}<br>{{ $u->unit_code ?? '—' }}</td>
                                <td>
                                    <form method="post" action="{{ route('admin.users.roles',$u) }}">
                                        @csrf
                                        <select class="form-control" name="roles[]" multiple size="3">
                                            @foreach($roles as $r)
                                                <option value="{{ $r->id }}" @selected($u->roles->contains('id',$r->id))>{{ $r->label }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-light btn-sm" style="margin-top:4px">Update Roles</button>
                                    </form>
                                </td>
                                <td>
                                    <span class="badge {{ $u->is_active?'active':'critical' }}">{{ $u->is_active?'ACTIVE':'DISABLED' }}</span>
                                </td>
                                <td>
                                    @if($u->is_active && $u->id!==auth()->id())
                                        <form method="post" action="{{ route('admin.users.disable',$u) }}" style="margin-bottom:5px">
                                            @csrf
                                            <input type="hidden" name="reason" value="Disabled by administrator">
                                            <button class="btn btn-danger btn-sm" data-confirm="Disable this user? No records will be deleted.">Disable</button>
                                        </form>
                                    @elseif(!$u->is_active)
                                        <form method="post" action="{{ route('admin.users.enable',$u) }}">
                                            @csrf
                                            <button class="btn btn-success btn-sm">Enable</button>
                                        </form>
                                    @endif
                                    <details style="margin-top:6px">
                                        <summary class="muted" style="cursor:pointer">Reset password</summary>
                                        <form method="post" action="{{ route('admin.users.reset-password',$u) }}">
                                            @csrf
                                            <input class="form-control" type="password" name="password" minlength="10" placeholder="Temporary password" required>
                                            <button class="btn btn-light btn-sm" style="margin-top:4px">Set</button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-b">{{ $users->links() }}</div>
        </div>
        <div class="card">
            <div class="card-h">Create User</div>
            <div class="card-b">
                <form method="post" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Username *</label>
                        <input class="form-control" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Staff No.</label>
                        <input class="form-control" name="staff_no">
                    </div>
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input class="form-control" type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Unit Code</label>
                        <input class="form-control" name="unit_code">
                    </div>
                    <div class="form-group">
                        <label>Temporary Password *</label>
                        <input class="form-control" type="password" name="password" minlength="10" required>
                    </div>
                    <div class="form-group">
                        <label>Roles *</label>
                        <select class="form-control" name="roles[]" multiple size="7" required>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-primary" style="margin-top:10px">Create User</button>
                </form>
            </div>
        </div>
    </div>
@endsection
