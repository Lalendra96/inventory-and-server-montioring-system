@extends('layouts.app')
@section('title','New Support Request')
@section('page-title','Create Support Request')
@section('content')
    <div class="card">
        <div class="card-h">Support Request Details</div>
        <div class="card-b">
            <form method="post" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label>Category</label>
                        <select class="form-control" name="category_id">
                            <option value="">Select category</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" @selected(old('category_id')==$c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Location / Unit</label>
                        <select class="form-control" name="location_id">
                            <option value="">Select location</option>
                            @foreach($locations as $l)
                                <option value="{{ $l->id }}" @selected(old('location_id')==$l->id)>{{ $l->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select class="form-control" name="priority" required>
                            @foreach(['normal','low','high','critical'] as $p)
                                <option value="{{ $p }}" @selected(old('priority','normal')===$p)>{{ ucfirst($p) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Attachments (max 10MB each)</label>
                        <input class="form-control" type="file" name="attachments[]" multiple>
                    </div>
                    <div class="form-group full">
                        <label>Subject</label>
                        <input class="form-control" name="subject" maxlength="180" required value="{{ old('subject') }}">
                    </div>
                    <div class="form-group full">
                        <label>Description</label>
                        <textarea class="form-control" name="description" required>{{ old('description') }}</textarea>
                        <div class="muted" style="font-size:11px;margin-top:5px">Do not include patient-identifiable clinical data unless explicitly authorised for the support workflow.</div>
                    </div>
                    <div class="full">
                        <button class="btn btn-primary">Create Request</button>
                        <a class="btn btn-light" href="{{ route('tickets.index') }}">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
