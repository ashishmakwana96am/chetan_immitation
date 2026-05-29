<div class="text-center mb-4">
    <h3 class="mb-2">Edit Customer</h3>
    <p class="text-muted">Update customer details</p>
</div>

<form id="commonModalForm" action="{{ route('admin.customers.update', $customer) }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control"
                placeholder="e.g. John Doe" value="{{ $customer->name }}" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Phone <span class="text-muted">(optional)</span></label>
            <input type="text" name="phone" class="form-control"
                placeholder="e.g. +1 234 567 8900" value="{{ $customer->phone }}" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Email <span class="text-muted">(optional)</span></label>
            <input type="email" name="email" class="form-control"
                placeholder="e.g. john@example.com" value="{{ $customer->email }}" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label">Status</label>
            <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" id="customerStatus" name="status" value="active"
                    {{ $customer->status === 'active' ? 'checked' : '' }} />
                <label class="form-check-label" for="customerStatus">Active</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Update Customer</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
