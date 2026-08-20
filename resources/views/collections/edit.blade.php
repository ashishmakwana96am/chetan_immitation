<div class="text-center mb-4">
    <h3 class="mb-2">Edit Collection</h3>
    <p class="text-muted">Update the details of the collection</p>
</div>

<form id="commonModalForm" action="{{ route('admin.collections.update', $collection) }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="collectionName">Collection Name <span class="text-danger">*</span></label>
            <input type="text" id="collectionName" name="name" value="{{ $collection->name }}"
                class="form-control" placeholder="Enter Collection Name" autofocus />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label" for="collectionShortName">Collection Short Name <span class="text-danger">*</span></label>
            <input type="text" id="collectionShortName" name="short_name" value="{{ $collection->short_name }}"
                class="form-control" placeholder="Enter Collection Short Name" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label" for="collectionStatus">Status</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="collectionStatus" name="status" value="1" {{ $collection->status == 1 ? 'checked' : '' }} />
                <label class="form-check-label" for="collectionStatus">Active</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Update Collection</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
