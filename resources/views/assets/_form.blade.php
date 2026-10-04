<div class="form-grid">
    <div class="form-group">
        <label>Asset Tag *</label>
        <input class="form-control" name="asset_tag" required value="{{ old('asset_tag',$asset->asset_tag??'') }}">
    </div>
    <div class="form-group">
        <label>Asset Name *</label>
        <input class="form-control" name="name" required value="{{ old('name',$asset->name??'') }}">
    </div>
    <div class="form-group">
        <label>Category</label>
        <select class="form-control" name="category_id">
            <option value="">Select</option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}" @selected(old('category_id',$asset->category_id??null)==$c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Location</label>
        <select class="form-control" name="location_id">
            <option value="">Select</option>
            @foreach($locations as $l)
                <option value="{{ $l->id }}" @selected(old('location_id',$asset->location_id??null)==$l->id)>{{ $l->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Manufacturer</label>
        <input class="form-control" name="manufacturer" value="{{ old('manufacturer',$asset->manufacturer??'') }}">
    </div>
    <div class="form-group">
        <label>Model</label>
        <input class="form-control" name="model" value="{{ old('model',$asset->model??'') }}">
    </div>
    <div class="form-group">
        <label>Serial Number</label>
        <input class="form-control" name="serial_number" value="{{ old('serial_number',$asset->serial_number??'') }}">
    </div>
    <div class="form-group">
        <label>Status</label>
        <select class="form-control" name="status">
            @foreach(['active','repair','standby','replacement','retired'] as $s)
                <option value="{{ $s }}" @selected(old('status',$asset->status??'active')===$s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Purchase Date</label>
        <input class="form-control" type="date" name="purchase_date" value="{{ old('purchase_date',isset($asset)&&$asset->purchase_date?$asset->purchase_date->format('Y-m-d'):'') }}">
    </div>
    <div class="form-group">
        <label>Warranty Until</label>
        <input class="form-control" type="date" name="warranty_until" value="{{ old('warranty_until',isset($asset)&&$asset->warranty_until?$asset->warranty_until->format('Y-m-d'):'') }}">
    </div>
    <div class="form-group">
        <label>Supplier</label>
        <input class="form-control" name="supplier" value="{{ old('supplier',$asset->supplier??'') }}">
    </div>
    <div class="form-group">
        <label>Purchase Cost (LKR)</label>
        <input class="form-control" type="number" min="0" step="0.01" name="purchase_cost" value="{{ old('purchase_cost',$asset->purchase_cost??'') }}">
    </div>
    <div class="form-group">
        <label>IP / Network Address</label>
        <input class="form-control" name="ip_address" value="{{ old('ip_address',$asset->ip_address??'') }}">
    </div>
    <div class="form-group full">
        <label>Notes</label>
        <textarea class="form-control" name="notes">{{ old('notes',$asset->notes??'') }}</textarea>
    </div>
</div>
