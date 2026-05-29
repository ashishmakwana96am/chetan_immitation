<div class="text-center mb-4">
    <h3 class="mb-2">Edit Supplier</h3>
    <p class="text-muted">Update supplier details</p>
</div>

<form id="commonModalForm" action="{{ route('admin.suppliers.update', $supplier) }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="supplierName">Name <span class="text-danger">*</span></label>
            <input type="text" id="supplierName" name="name"
                class="form-control" placeholder="e.g. ABC Suppliers Ltd."
                value="{{ $supplier->name }}" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label" for="supplierPhone">Phone <span class="text-muted">(optional)</span></label>
            <input type="text" id="supplierPhone" name="phone"
                class="form-control" placeholder="e.g. +1 234 567 8900"
                value="{{ $supplier->phone }}" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label" for="supplierAddress">Address <span class="text-muted">(optional)</span></label>
            <input type="text" id="supplierAddress" name="address"
                class="form-control" placeholder="e.g. 123 Main Street, City"
                value="{{ $supplier->address }}" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label">Status</label>
            <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" id="supplierStatus" name="status" value="active"
                    {{ $supplier->status === 'active' ? 'checked' : '' }} />
                <label class="form-check-label" for="supplierStatus">Active</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Update Supplier</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
