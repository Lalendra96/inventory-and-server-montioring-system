@extends('layouts.app')
@section('title','Register Asset')
@section('page-title','Register Asset / Inventory Item')
@section('content')
    <div class="card">
        <div class="card-b">
            <form method="post" action="{{ route('assets.store') }}">
                @csrf
                @include('assets._form')
                <div style="margin-top:14px">
                    <button class="btn btn-primary">Register Asset</button>
                    <a class="btn btn-light" href="{{ route('assets.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
