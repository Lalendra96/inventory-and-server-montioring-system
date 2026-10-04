@extends('layouts.app')
@section('title','Edit Asset')
@section('page-title','Edit Asset '.$asset->asset_tag)
@section('content')
    <div class="split">
        <div class="card">
            <div class="card-b">
                <form method="post" action="{{ route('assets.update',$asset) }}">
                    @csrf
                    @method('PUT')
                    @include('assets._form')
                    <div style="margin-top:14px">
                        <button class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-h">Disable Record</div>
            <div class="card-b">
                <p class="muted">Records are never deleted. Disabling preserves all historical and audit references.</p>
                <form method="post" action="{{ route('assets.disable',$asset) }}">
                    @csrf
                    <textarea class="form-control" name="reason" placeholder="Reason for disabling" required></textarea>
                    <button class="btn btn-danger" style="margin-top:10px" data-confirm="Disable this asset while retaining its history?">Disable Asset</button>
                </form>
            </div>
        </div>
    </div>
@endsection
