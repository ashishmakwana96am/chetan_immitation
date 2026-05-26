<div class="text-center mb-4">
    <h3 class="mb-2">Edit Category</h3>
    <p class="text-muted">Update category details</p>
</div>

<form id="commonModalForm" action="{{ route('admin.categories.update', $category) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="categoryName">Name <span class="text-danger">*</span></label>
            <input type="text" id="categoryName" name="name"
                class="form-control" placeholder="e.g. Electronics"
                value="{{ $category->name }}" autofocus />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label" for="categoryStatus">Status</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="categoryStatus" name="status" value="active"
                    {{ $category->status === 'active' ? 'checked' : '' }} />
                <label class="form-check-label" for="categoryStatus">Active</label>
            </div>
        </div>
    </div>

    <div class="text-center mt-4">
        <button type="submit" class="btn btn-primary me-2">Update Category</button>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
