<div class="text-center mb-4">
    <h3 class="mb-2">Add New Coupon</h3>
    <p class="text-muted">Fill in the details to create a new discount coupon</p>
</div>

<form id="commonModalForm" action="{{ route('admin.coupons.store') }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="couponName">Coupon Name <span class="text-danger">*</span></label>
            <input type="text" id="couponName" name="name"
                class="form-control" placeholder="Enter Coupon Name" autofocus />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="couponCode">Coupon Code <span class="text-danger">*</span></label>
            <input type="text" id="couponCode" name="code"
                class="form-control" placeholder="Enter Coupon Code" style="text-transform: uppercase;" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <div id="description-editor"></div>
            <textarea name="description" id="description-textarea" class="d-none"></textarea>
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="couponDiscountType">Discount Type <span class="text-danger">*</span></label>
            <select id="couponDiscountType" name="discount_type" class="form-select no-select2">
                <option value="flat">Flat Amount</option>
                <option value="percentage">Percentage (%)</option>
            </select>
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="couponDiscountValue">Discount Value <span class="text-danger">*</span></label>
            <input type="number" id="couponDiscountValue" name="discount_value"
                class="form-control" placeholder="0.00" step="0.01" min="0.01" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="couponUsageLimit">Usage Limit</label>
            <input type="number" id="couponUsageLimit" name="usage_limit"
                class="form-control" placeholder="Unlimited" min="1" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="couponStartDate">Start Date</label>
            <input type="text" id="couponStartDate" name="start_date" class="form-control flatpickr" placeholder="DD-MM-YYYY" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="couponEndDate">End Date</label>
            <input type="text" id="couponEndDate" name="end_date" class="form-control flatpickr" placeholder="DD-MM-YYYY" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label">Status</label>
            <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" id="couponStatus" name="status" value="1" checked />
                <label class="form-check-label" for="couponStatus">Active</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top mt-4">
        <button type="submit" class="btn btn-primary w-50">Create Coupon</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>

<script>
$(document).ready(function () {
    if (typeof Quill !== 'undefined') {
        const descriptionQuill = new Quill('#description-editor', {
            theme: 'snow',
            placeholder: 'Enter coupon description...'
        });
        
        descriptionQuill.on('text-change', function() {
            const html = descriptionQuill.root.innerHTML === '<p><br></p>' ? '' : descriptionQuill.root.innerHTML;
            $('#description-textarea').val(html).trigger('input');
        });
    } else {
        console.error('Quill is not loaded!');
    }
});
</script>
