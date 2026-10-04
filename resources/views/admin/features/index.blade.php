@extends('layouts.app')
@section('title','Features & Options')
@section('page-title','Administration · Features & Options')
@section('content')
    <div class="card">
        <div class="card-h">System Feature Controls <span class="muted">Admin only</span>
        </div>
        <div class="card-b">
            <div class="alert alert-error" style="margin-bottom:15px">
                <strong>Governance safeguard:</strong> disabling a feature hides operational access but
                never deletes its records. Audit logging and authentication cannot be disabled from this screen.
            </div>
            @foreach($features as $f)
                <div class="feature-row">
                    <div>
                        <strong>{{ $f->label }}</strong>
                        <div class="muted">{{ $f->description }}</div>
                        <code>{{ $f->key }}</code>
                    </div>
                    <form method="post" action="{{ route('admin.features.update',$f) }}">
                        @csrf
                        <input type="hidden" name="enabled" value="0">
                        <label class="toggle">
                            <input type="checkbox" name="enabled" value="1" @checked($f->enabled)> Enabled <button class="btn btn-light btn-sm">Save</button>
                        </label>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
@endsection
