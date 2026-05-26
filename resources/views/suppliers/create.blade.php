<div class="text-center mb-4">
    <h3 class="mb-2">Add New Supplier</h3>
    <p class="text-muted">Fill in the details to create a new supplier</p>
</div>

<form id="commonModalForm" action="{{ route('admin.suppliers.store') }}" method="POST">
    @csrf
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="supplierName">Name <span class="text-danger">*</span></label>
            <input type="text" id="supplierName" name="name"
                class="form-control" placeholder="e.g. ABC Suppliers Ltd." autofocus />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label" for="supplierPhone">Phone <span class="text-muted">(optional)</span></label>
            <input type="text" id="supplierPhone" name="phone"
                class="form-control" placeholder="e.g. +1 234 567 8900" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label" for="supplierAddress">Address <span class="text-muted">(optional)</span></label>
            <input type="text" id="supplierAddress" name="address"
                class="form-control" placeholder="e.g. 123 Main Street, City" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label">Status</label>
            <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" id="supplierStatus" name="status" value="active" checked />
                <label class="form-check-label" for="supplierStatus">Active</label>
            </div>
        </div>
    </div>

    <div class="text-center mt-4">
        <button type="submit" class="btn btn-primary me-2">Create Supplier</button>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
