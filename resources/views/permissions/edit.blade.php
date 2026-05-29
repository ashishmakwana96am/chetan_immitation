<div class="text-center mb-4">
    <h3 class="mb-2">Edit Permission</h3>
    <p class="text-muted">Edit permission as per your requirements.</p>
</div>

<form id="commonModalForm" action="{{ route('admin.permissions.update', $permission) }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label" for="permissionName">Permission Name <span class="text-danger">*</span></label>
        <input type="text" id="permissionName" name="name" class="form-control" placeholder="e.g. view users"
            value="{{ $permission->name }}" autofocus />
        <div class="invalid-feedback"></div>
    </div>
    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Update Permission</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
