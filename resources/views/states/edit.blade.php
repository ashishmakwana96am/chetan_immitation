<div class="text-center mb-4">
    <h3 class="mb-2">Edit State</h3>
    <p class="text-muted">Update state details</p>
</div>

<form id="commonModalForm" action="{{ route('admin.states.update', $state) }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="stateName">State Name <span class="text-danger">*</span></label>
            <input type="text" id="stateName" name="name"
                class="form-control" placeholder="Enter State Name"
                value="{{ $state->name }}" autofocus />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="stateShippingCharge">Shipping Charge <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">{{ currency_symbol() }}</span>
                <input type="number" id="stateShippingCharge" name="shipping_charge"
                    class="form-control" placeholder="0.00" step="0.01" min="0"
                    value="{{ $state->shipping_charge }}" />
                <div class="invalid-feedback"></div>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="stateDeliveryDays">Delivery Days <span class="text-danger">*</span></label>
            <input type="number" id="stateDeliveryDays" name="delivery_days"
                class="form-control" placeholder="Enter Delivery Days" min="0"
                value="{{ $state->delivery_days }}" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label">Status</label>
            <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" id="stateStatus" name="status" value="1"
                    {{ $state->status == 1 ? 'checked' : '' }} />
                <label class="form-check-label" for="stateStatus">Active</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Update State</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
