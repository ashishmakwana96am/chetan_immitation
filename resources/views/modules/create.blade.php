<div class="text-center mb-4">
    <h3 class="mb-2">Add New Module</h3>
    <p class="text-muted">Create a dynamic module for system navigation and permissions.</p>
</div>

<form id="commonModalForm" action="{{ route('admin.modules.store') }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    <div class="mb-3">
        <label class="form-label" for="moduleName">Module Name <span class="text-danger">*</span></label>
        <input type="text" id="moduleName" name="name" class="form-control" placeholder="e.g. Inventories" autofocus />
        <div class="invalid-feedback"></div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="moduleParent">Parent Module</label>
        <select id="moduleParent" name="parent_id" class="form-select no-select2">
            <option value="">-- None (Top Level) --</option>
            @foreach($parents as $parent)
                <option value="{{ $parent->id }}">{{ $parent->name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback"></div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="moduleIcon">Icon Class</label>
        <input type="text" id="moduleIcon" name="icon" class="form-control" placeholder="e.g. ti ti-box" />
        <div class="invalid-feedback"></div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="moduleRoute">Route Name</label>
        <input type="text" id="moduleRoute" name="route" class="form-control" placeholder="e.g. admin.products.index" />
        <div class="invalid-feedback"></div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="moduleActive">Active URL Pattern</label>
        <input type="text" id="moduleActive" name="active_pattern" class="form-control" placeholder="e.g. admin/products*" />
        <div class="invalid-feedback"></div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="modulePermission">Permission Check</label>
        <input type="text" id="modulePermission" name="permission" class="form-control" placeholder="e.g. view products" />
        <div class="invalid-feedback"></div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="moduleSort">Sort Order <span class="text-danger">*</span></label>
        <input type="number" id="moduleSort" name="sort_order" class="form-control" value="0" min="0" />
        <div class="invalid-feedback"></div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Create Module</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
