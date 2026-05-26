<div class="text-center mb-4">
    <h3 class="mb-2">Add New User</h3>
    <p class="text-muted">Fill in the details to create a new user</p>
</div>

<form id="commonModalForm" action="{{ route('admin.users.store') }}" method="POST">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="userName">Full Name <span class="text-danger">*</span></label>
            <input type="text" id="userName" name="name" class="form-control" placeholder="e.g. John Doe" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="userEmail">Email <span class="text-danger">*</span></label>
            <input type="email" id="userEmail" name="email" class="form-control"
                placeholder="e.g. john@example.com" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="userPhone">Phone <span class="text-muted">(optional)</span></label>
            <input type="text" id="userPhone" name="phone" class="form-control"
                placeholder="e.g. +1 234 567 8900" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="userPassword">Password <span class="text-danger">*</span></label>
            <input type="password" id="userPassword" name="password" class="form-control"
                placeholder="Minimum 8 characters" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="userRole">Role <span class="text-danger">*</span></label>
            <select id="userRole" name="role" class="form-select">
                <option value="">-- Select Role --</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="userLocation">Location <span class="text-muted">(optional)</span></label>
            <select id="userLocation" name="location_id" class="form-select">
                <option value="">-- Select Location --</option>
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="userStatus">Status</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="userStatus" name="status" value="active" checked />
                <label class="form-check-label" for="userStatus">Active</label>
            </div>
        </div>
    </div>

    <div class="text-center mt-4">
        <button type="submit" class="btn btn-primary me-2">Create User</button>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
