<div class="text-center mb-4">
    <h3 class="mb-2">Edit User</h3>
    <p class="text-muted">Update user details</p>
</div>

<form id="commonModalForm" action="{{ route('admin.users.update', $user) }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="userName">Full Name <span class="text-danger">*</span></label>
            <input type="text" id="userName" name="name" class="form-control" placeholder="e.g. John Doe"
                value="{{ $user->name }}" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="userEmail">Email <span class="text-danger">*</span></label>
            <input type="email" id="userEmail" name="email" class="form-control" placeholder="e.g. john@example.com"
                value="{{ $user->email }}" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="userPhone">Phone <span class="text-muted">(optional)</span></label>
            <input type="text" id="userPhone" name="phone" class="form-control" placeholder="e.g. +1 234 567 8900"
                value="{{ $user->phone }}" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="userRole">Role <span class="text-danger">*</span></label>
            <select id="userRole" name="role" class="form-select">
                <option value="">-- Select Role --</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" {{ $userRole === $role->id ? 'selected' : '' }}>
                        {{ ucfirst($role->name) }}
                    </option>
                @endforeach
            </select>
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="userLocation">Location <span class="text-muted">(optional)</span></label>
            <select id="userLocation" name="location_id" class="form-select">
                <option value="">-- Select Location --</option>
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}" {{ $user->location_id === $location->id ? 'selected' : '' }}>
                        {{ $location->name }}
                    </option>
                @endforeach
            </select>
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="userStatus">Status</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="userStatus" name="status" value="active"
                    {{ $user->status === 'active' ? 'checked' : '' }} />
                <label class="form-check-label" for="userStatus">Active</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Update User</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
