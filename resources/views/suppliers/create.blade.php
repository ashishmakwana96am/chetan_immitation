<div class="text-center mb-4">
    <h3 class="mb-2">Add New Supplier</h3>
    <p class="text-muted">Fill in the details to create a new supplier</p>
</div>

<form id="commonModalForm" action="{{ route('admin.suppliers.store') }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="supplierName">Name <span class="text-danger">*</span></label>
            <input type="text" id="supplierName" name="name"
                class="form-control" placeholder="Enter Supplier Name" autofocus />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label" for="supplierPhone">Phone</label>
            <input type="text" id="supplierPhone" name="phone"
                class="form-control" placeholder="Enter Supplier Phone" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label" for="supplierAddress">Address</label>
            <input type="text" id="supplierAddress" name="address"
                class="form-control" placeholder="Enter Supplier Address" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label">Status</label>
            <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" id="supplierStatus" name="status" value="1" checked />
                <label class="form-check-label" for="supplierStatus">Active</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Create Supplier</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
