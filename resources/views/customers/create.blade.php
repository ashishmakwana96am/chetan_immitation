<div class="text-center mb-4">
    <h3 class="mb-2">Add New Customer</h3>
    <p class="text-muted">Fill in the details to create a new customer</p>
</div>

<form id="commonModalForm" action="{{ route('admin.customers.store') }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="Enter Customer Name" />
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label">Phone Number(s)</label>
            <div id="phoneNumbersList">
                <div class="d-flex gap-2 mb-2 align-items-start phone-row">
                    <div class="flex-grow-1">
                        <input type="text" name="phones[]" class="form-control phone-input" placeholder="Enter Phone Number" maxlength="10" inputmode="numeric" autocomplete="off" />
                        <div class="invalid-feedback"></div>
                    </div>
                    <button type="button" id="addPhoneBtn" class="btn btn-label-primary btn-icon" title="Add another phone number">
                        <i class="ti ti-plus"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="Enter Email Address" />
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label">GST Number</label>
            <input type="text" name="gst_no" class="form-control text-uppercase" placeholder="e.g. 24ABCDE1234F1Z5" maxlength="15" />
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="2" placeholder="Enter Address"></textarea>
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label">State</label>
            <input type="text" name="state" class="form-control" placeholder="Enter State" />
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label">Status</label>
            <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" id="customerStatus" name="status" value="1" checked />
                <label class="form-check-label" for="customerStatus">Active</label>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label">Credit Customer</label>
            <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" id="customerCreditCustomer" name="is_credit_customer" value="1" />
                <label class="form-check-label" for="customerCreditCustomer">Credit Customer</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Create Customer</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>

<script>
$(document).ready(function () {
    function restrictToDigits($input) {
        $input.on('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 10);
            checkDuplicatePhones();
        });
    }

    function checkDuplicatePhones() {
        const seen = {};
        let hasDuplicate = false;
        $('#phoneNumbersList .phone-input').each(function () {
            const $input = $(this);
            const val = $input.val().trim();
            $input.removeClass('is-invalid');
            $input.siblings('.invalid-feedback').text('');
            if (val && seen[val]) {
                hasDuplicate = true;
                $input.addClass('is-invalid');
                $input.siblings('.invalid-feedback').text('This phone number is already added.');
                seen[val].addClass('is-invalid');
                seen[val].siblings('.invalid-feedback').text('This phone number is already added.');
            } else if (val) {
                seen[val] = $input;
            }
        });
        return hasDuplicate;
    }

    restrictToDigits($('#phoneNumbersList .phone-input'));

    $('#addPhoneBtn').on('click', function () {
        const row = $(
            '<div class="d-flex gap-2 mb-2 align-items-start phone-row">' +
                '<div class="flex-grow-1">' +
                    '<input type="text" name="phones[]" class="form-control phone-input" placeholder="Enter Phone Number" maxlength="10" inputmode="numeric" autocomplete="off" />' +
                    '<div class="invalid-feedback"></div>' +
                '</div>' +
                '<button type="button" class="btn btn-label-danger btn-icon remove-phone-btn" title="Remove phone number">' +
                    '<i class="ti ti-trash"></i>' +
                '</button>' +
            '</div>'
        );
        restrictToDigits(row.find('.phone-input'));
        $('#phoneNumbersList').append(row);
    });

    $('#phoneNumbersList').on('click', '.remove-phone-btn', function () {
        $(this).closest('.phone-row').remove();
        checkDuplicatePhones();
    });

    $('#commonModalForm').on('submit', function () {
        if (checkDuplicatePhones()) {
            toastr.error('The same phone number cannot be added more than once.');
            return false;
        }
    });
});
</script>
