<div class="text-center mb-4">
    <h3 class="mb-2">Edit Module</h3>
    <p class="text-muted">Modify this dynamic module properties.</p>
</div>

<form id="commonModalForm" action="{{ route('admin.modules.update', $module) }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label" for="moduleName">Module Name <span class="text-danger">*</span></label>
        <input type="text" id="moduleName" name="name" class="form-control" placeholder="e.g. Inventories" value="{{ $module->name }}" autofocus />
        <div class="invalid-feedback"></div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="moduleParent">Parent Module</label>
        <select id="moduleParent" name="parent_id" class="form-select no-select2">
            <option value="">-- None (Top Level) --</option>
            @foreach($parents as $parent)
                <option value="{{ $parent->id }}" {{ $module->parent_id == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback"></div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="moduleIcon">Icon Class</label>
        <input type="text" id="moduleIcon" name="icon" class="form-control" placeholder="e.g. ti ti-box" value="{{ $module->icon }}" />
        <div class="invalid-feedback"></div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="moduleRoute">Route Name</label>
        <input type="text" id="moduleRoute" name="route" class="form-control" placeholder="e.g. admin.products.index" value="{{ $module->route }}" />
        <div class="invalid-feedback"></div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="moduleActive">Active URL Pattern</label>
        <input type="text" id="moduleActive" name="active_pattern" class="form-control" placeholder="e.g. admin/products*" value="{{ $module->active_pattern }}" />
        <div class="invalid-feedback"></div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="modulePermission">Permission Check</label>
        <input type="text" id="modulePermission" name="permission" class="form-control" placeholder="e.g. view products" value="{{ $module->permission }}" />
        <div class="invalid-feedback"></div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="moduleSort">Sort Order <span class="text-danger">*</span></label>
        <input type="number" id="moduleSort" name="sort_order" class="form-control" value="{{ $module->sort_order }}" min="0" />
        <div class="invalid-feedback"></div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Update Module</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
