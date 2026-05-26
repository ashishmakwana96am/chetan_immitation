<div class="text-center mb-4">
    <h3 class="mb-2">Add New Location</h3>
    <p class="text-muted">Fill in the details to create a new location</p>
</div>

<form id="commonModalForm" action="{{ route('admin.locations.store') }}" method="POST">
    @csrf
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="locationName">Name <span class="text-danger">*</span></label>
            <input type="text" id="locationName" name="name"
                class="form-control" placeholder="e.g. Main Branch" autofocus />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label" for="locationAddress">Address <span class="text-muted">(optional)</span></label>
            <input type="text" id="locationAddress" name="address"
                class="form-control" placeholder="e.g. 123 Main Street, City" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="locationStatus">Status</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="locationStatus" name="status" value="active" checked />
                <label class="form-check-label" for="locationStatus">Active</label>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="locationDefault">Set as Default</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="locationDefault" name="is_default" value="1" />
                <label class="form-check-label" for="locationDefault">Default</label>
            </div>
        </div>
    </div>

    <div class="text-center mt-4">
        <button type="submit" class="btn btn-primary me-2">Create Location</button>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
