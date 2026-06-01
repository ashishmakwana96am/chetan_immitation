<div class="text-center mb-4">
    <h3 class="mb-2">Change Password</h3>
    <p class="text-muted">Update your account credentials securely.</p>
</div>

<form id="commonModalForm" action="{{ route('admin.profile.update-password') }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    <div class="row g-3 mb-4">
        <div class="col-12">
            <label class="form-label" for="current_password">Current Password <span class="text-danger">*</span></label>
            <div class="input-group input-group-merge">
                <input type="password" id="current_password" name="current_password" class="form-control" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required />
                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                <div class="invalid-feedback"></div>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label" for="new_password">New Password <span class="text-danger">*</span></label>
            <div class="input-group input-group-merge">
                <input type="password" id="new_password" name="new_password" class="form-control" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required />
                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                <div class="invalid-feedback"></div>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label" for="new_password_confirmation">Confirm New Password <span class="text-danger">*</span></label>
            <div class="input-group input-group-merge">
                <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="form-control" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required />
                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                <div class="invalid-feedback"></div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Update Password</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
