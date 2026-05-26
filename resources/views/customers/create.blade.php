<div class="text-center mb-4">
    <h3 class="mb-2">Add New Customer</h3>
    <p class="text-muted">Fill in the details to create a new customer</p>
</div>

<form id="commonModalForm" action="{{ route('admin.customers.store') }}" method="POST">
    @csrf
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. John Doe" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Phone <span class="text-muted">(optional)</span></label>
            <input type="text" name="phone" class="form-control" placeholder="e.g. +1 234 567 8900" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Email <span class="text-muted">(optional)</span></label>
            <input type="email" name="email" class="form-control" placeholder="e.g. john@example.com" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label">Password <span class="text-muted">(optional)</span></label>
            <input type="password" name="password" class="form-control" placeholder="Minimum 8 characters" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label">Status</label>
            <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" id="customerStatus" name="status" value="active" checked />
                <label class="form-check-label" for="customerStatus">Active</label>
            </div>
        </div>
    </div>

    <div class="text-center mt-4">
        <button type="submit" class="btn btn-primary me-2">Create Customer</button>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
