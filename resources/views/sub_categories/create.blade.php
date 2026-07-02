<div class="text-center mb-4">
    <h3 class="mb-2">Add New Sub Category</h3>
    <p class="text-muted">Fill in the details to create a new sub category</p>
</div>

<form id="commonModalForm" action="{{ route('admin.sub-categories.store') }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="parentCategory">Category <span class="text-danger">*</span></label>
            <select id="parentCategory" name="category_id" class="form-select">
                <option value="">Select Category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label" for="subCategoryName">Name <span class="text-danger">*</span></label>
            <input type="text" id="subCategoryName" name="name"
                class="form-control" placeholder="Enter Sub Category Name" autofocus />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label" for="subCategoryStatus">Status</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="subCategoryStatus" name="status" value="1" checked />
                <label class="form-check-label" for="subCategoryStatus">Active</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Create Sub Category</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
