<div class="text-center mb-4">
    <h3 class="mb-2">Edit Permission</h3>
    <p class="text-muted">Edit permission as per your requirements.</p>
</div>

<form id="commonModalForm" action="{{ route('admin.permissions.update', $permission) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label" for="permissionName">Permission Name <span class="text-danger">*</span></label>
        <input type="text" id="permissionName" name="name" class="form-control" placeholder="e.g. view users"
            value="{{ $permission->name }}" autofocus />
        <div class="invalid-feedback"></div>
    </div>
    <div class="text-center">
        <button type="submit" class="btn btn-primary me-2">Update Permission</button>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
