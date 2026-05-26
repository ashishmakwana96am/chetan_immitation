<div class="text-center mb-4">
    <h3 class="mb-2">Add New Permission</h3>
    <p class="text-muted">Permissions you may use and assign to your roles.</p>
</div>

<form id="commonModalForm" action="{{ route('admin.permissions.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label class="form-label" for="permissionName">Permission Name <span class="text-danger">*</span></label>
        <input type="text" id="permissionName" name="name" class="form-control" placeholder="e.g. view users"
            autofocus />
        <div class="invalid-feedback"></div>
    </div>
    <div class="text-center">
        <button type="submit" class="btn btn-primary me-2">Create Permission</button>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
