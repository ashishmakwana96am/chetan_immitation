<div class="text-center mb-4">
    <h3 class="mb-2">Add New Category</h3>
    <p class="text-muted">Fill in the details to create a new category</p>
</div>

<form id="commonModalForm" action="{{ route('admin.categories.store') }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="categoryName">Name <span class="text-danger">*</span></label>
            <input type="text" id="categoryName" name="name"
                class="form-control" placeholder="e.g. Electronics" autofocus />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label" for="categoryStatus">Status</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="categoryStatus" name="status" value="active" checked />
                <label class="form-check-label" for="categoryStatus">Active</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Create Category</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
