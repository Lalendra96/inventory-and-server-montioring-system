@extends('layouts.app')
@section('title','Assets / Inventory')
@section('page-title','Asset / Inventory Management')
@section('content')
    <div class="toolbar">
        <a class="btn btn-primary" href="{{ route('assets.create') }}">+ Register Asset</a>
        <form method="get" style="display:flex;gap:8px">
            <input class="form-control" name="q" placeholder="Asset tag, name or serial" value="{{ request('q') }}">
            <select class="form-control" name="status">
                <option value="">All statuses</option>
                @foreach(['active','repair','standby','replacement','retired'] as $s)
                    <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
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
                        <th>Asset Tag</th>
                        <th>Asset</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Serial</th>
                        <th>Status</th>
                        <th>Warranty</th>
                        <th>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $a)
                        <tr>
                            <td>
                                <a href="{{ route('assets.show',$a) }}">
                                    <strong>{{ $a->asset_tag }}</strong>
                                </a>
                            </td>
                            <td>{{ $a->name }}<br>
                                <span class="muted">{{ trim(($a->manufacturer??'').' '.($a->model??'')) }}</span>
                            </td>
                            <td>{{ $a->category?->name ?? '—' }}</td>
                            <td>{{ $a->location?->name ?? '—' }}</td>
                            <td>{{ $a->serial_number ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $a->status }}">{{ strtoupper($a->status) }}</span>
                            </td>
                            <td>{{ $a->warranty_until?->format('d M Y') ?? '—' }}</td>
                            <td>
                                <a class="btn btn-light btn-sm" href="{{ route('assets.edit',$a) }}">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty">No assets registered.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-b">{{ $assets->links() }}</div>
    </div>
@endsection
