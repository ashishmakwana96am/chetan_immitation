<div class="text-center mb-4">
    <h3 class="mb-2">Edit Role</h3>
    <p class="text-muted">Update role name and permissions</p>
</div>

<form id="commonModalForm" action="{{ route('admin.roles.update', $role) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="mb-4">
        <label class="form-label" for="roleName">Role Name <span class="text-danger">*</span></label>
        <input type="text" id="roleName" name="name" class="form-control" placeholder="e.g. manager" value="{{ $role->name }}" autofocus />
        <div class="invalid-feedback"></div>
    </div>

    <h5 class="mb-3">Role Permissions</h5>
    <div class="table-responsive mb-4">
        <table class="table table-flush-spacing">
            <tbody>
                <tr>
                    <td class="text-nowrap fw-semibold">
                        Administrator Access
                        <i class="ti ti-info-circle ms-1" data-bs-toggle="tooltip"
                            title="Allows full access to the system"></i>
                    </td>
                    <td>
                        <div class="form-check">
                            @php $allChecked = $permissions->flatten()->count() === count($rolePermissionIds); @endphp
                            <input class="form-check-input" type="checkbox" id="selectAllPermissions"
                                {{ $allChecked ? 'checked' : '' }} />
                            <label class="form-check-label" for="selectAllPermissions">Select All</label>
                        </div>
                    </td>
                </tr>
                @foreach ($permissions as $module => $modulePermissions)
                    @php
                        $moduleIds = $modulePermissions->pluck('id')->toArray();
                        $moduleChecked = count(array_intersect($moduleIds, $rolePermissionIds)) === count($moduleIds);
                    @endphp
                    <tr>
                        <td class="text-nowrap fw-semibold text-capitalize">{{ $module }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check me-3 me-lg-5">
                                    <input class="form-check-input module-select-all" type="checkbox"
                                        data-module="{{ $module }}" {{ $moduleChecked ? 'checked' : '' }} />
                                    <label class="form-check-label fw-semibold">All</label>
                                </div>
                                @foreach ($modulePermissions as $permission)
                                    <div class="form-check me-3 me-lg-5">
                                        <input class="form-check-input permission-checkbox" type="checkbox"
                                            name="permissions[]" value="{{ $permission->id }}"
                                            id="perm-{{ $permission->id }}" data-module="{{ $module }}"
                                            {{ in_array($permission->id, $rolePermissionIds) ? 'checked' : '' }} />
                                        <label class="form-check-label text-capitalize"
                                            for="perm-{{ $permission->id }}">
                                            {{ ucfirst(implode(' ', array_slice(explode(' ', $permission->name), 0, -1))) }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="text-center">
        <button type="submit" class="btn btn-primary me-2">Update Role</button>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
