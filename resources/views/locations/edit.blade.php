<div class="text-center mb-4">
    <h3 class="mb-2">Edit Location</h3>
    <p class="text-muted">Update location details</p>
</div>

<form id="commonModalForm" action="{{ route('admin.locations.update', $location) }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="locationName">Name <span class="text-danger">*</span></label>
            <input type="text" id="locationName" name="name"
                class="form-control" placeholder="e.g. Main Branch"
                value="{{ $location->name }}" autofocus />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label" for="locationAddress">Address <span class="text-muted">(optional)</span></label>
            <input type="text" id="locationAddress" name="address"
                class="form-control" placeholder="e.g. 123 Main Street, City"
                value="{{ $location->address }}" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Status</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="locationStatus" name="status" value="active"
                    {{ $location->status === 'active' ? 'checked' : '' }} />
                <label class="form-check-label" for="locationStatus">Active</label>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Set as Default</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="locationDefault" name="is_default" value="1"
                    {{ $location->is_default ? 'checked' : '' }} />
                <label class="form-check-label" for="locationDefault">Default</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Update Location</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
