<div class="text-center mb-4">
    <h3 class="mb-2">Add Shipping Address</h3>
    <p class="text-muted">For {{ $customer->name }}{{ $customer->phone ? ' (' . $customer->phone . ')' : '' }}</p>
</div>

<form id="commonModalForm" action="{{ route('admin.customers.addresses.store', $customer) }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="customerAddressText">Full Address <span class="text-danger">*</span></label>
            <textarea id="customerAddressText" name="address" rows="4"
                class="form-control" placeholder="Enter complete address" autofocus></textarea>
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label" for="customerAddressState">State <span class="text-danger">*</span></label>
            <select name="state" id="customerAddressState" class="form-select select2-modal" data-placeholder="Select State">
                <option value=""></option>
                @foreach($states as $state)
                    <option value="{{ $state->name }}">{{ $state->name }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback"></div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Save Address</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="offcanvas">Cancel</button>
    </div>
</form>

<script>
$(document).ready(function() {
    if ($.fn.select2) {
        const parentModal = $('#customerAddressState').closest('#commonModal');
        $('#customerAddressState').select2({
            dropdownParent: parentModal.length ? parentModal : $(document.body),
            placeholder: 'Select State',
            allowClear: true
        });
    }
});
</script>
