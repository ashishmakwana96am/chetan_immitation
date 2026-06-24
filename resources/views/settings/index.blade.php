@extends('layouts.app')

@section('title', 'System Settings')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-semibold mb-0">System Settings</h4>
</div>

<form id="settingsForm">
    @csrf
    <div class="row g-4">
        <!-- Razorpay Settings -->
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Razorpay Payment Gateway Settings</h5></div>
                <div class="card-body">
                    
                    <!-- Payment Mode Selection -->
                    <div class="mb-4">
                        <label class="form-label font-semibold d-block mb-2">Active Payment Mode</label>
                        <div class="form-check form-switch form-check-lg d-flex align-items-center">
                            <input class="form-check-input" type="checkbox" id="razorpay_payment_mode_switch" style="width: 2.75rem; height: 1.5rem; cursor: pointer;" {{ $razorpayPaymentMode === 'live' ? 'checked' : '' }} />
                            <input type="hidden" name="razorpay_payment_mode" id="razorpay_payment_mode" value="{{ $razorpayPaymentMode }}" data-original="{{ $razorpayPaymentMode }}" />
                            <label class="form-check-label font-semibold ms-3 {{ $razorpayPaymentMode === 'live' ? 'text-success' : 'text-primary' }}" id="payment_mode_label" for="razorpay_payment_mode_switch" style="font-size: 1.1rem; user-select: none; cursor: pointer;">
                                {{ $razorpayPaymentMode === 'live' ? 'Live Mode' : 'Test Mode' }}
                            </label>
                        </div>
                    </div>

                    <hr class="my-4 border-light" />

                    <!-- Test Credentials -->
                    <div class="row g-3">
                        <div class="col-12"><h6 class="mb-0 text-primary">Test Mode Credentials</h6></div>
                        <div class="col-md-6">
                            <label class="form-label" for="razorpay_test_key_id">Razorpay Test Key ID</label>
                            <input type="text" id="razorpay_test_key_id" class="form-control bg-light text-muted" value="{{ $razorpayTestKeyId }}" readonly />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="razorpay_test_key_secret">Razorpay Test Key Secret</label>
                            <div class="input-group input-group-merge">
                                <input type="password" id="razorpay_test_key_secret" class="form-control bg-light text-muted" value="{{ $razorpayTestKeySecret }}" readonly />
                                <span class="input-group-text cursor-pointer bg-light"><i class="ti ti-eye-off"></i></span>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4 border-light" />

                    <!-- Live Credentials -->
                    <div class="row g-3">
                        <div class="col-12"><h6 class="mb-0 text-success">Live Mode Credentials</h6></div>
                        <div class="col-md-6">
                            <label class="form-label" for="razorpay_live_key_id">Razorpay Live Key ID</label>
                            <input type="text" id="razorpay_live_key_id" class="form-control bg-light text-muted" value="{{ $razorpayLiveKeyId }}" readonly />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="razorpay_live_key_secret">Razorpay Live Key Secret</label>
                            <div class="input-group input-group-merge">
                                <input type="password" id="razorpay_live_key_secret" class="form-control bg-light text-muted" value="{{ $razorpayLiveKeySecret }}" readonly />
                                <span class="input-group-text cursor-pointer bg-light"><i class="ti ti-eye-off"></i></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- General Website Settings -->
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">General Website Settings</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label font-semibold" for="announcement_text">Announcement Bar Text</label>
                            <input type="text" name="announcement_text" id="announcement_text" class="form-control" value="{{ $announcementText }}" placeholder="Enter Announcement Bar Text" />
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="col-12">
            @can('edit settings')
            <div class="d-flex gap-2 mb-4">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="ti ti-device-floppy me-1"></i> Save Changes
                </button>
            </div>
            @endcan
        </div>
    </div>
</form>

@endsection

@section('page-js')
<script>
$(document).ready(function () {
    // Handle toggle switch change
    $('#razorpay_payment_mode_switch').on('change', function () {
        var isChecked = this.checked;
        var label = $('#payment_mode_label');
        if (isChecked) {
            label.text('Live Mode').removeClass('text-primary').addClass('text-success');
            $('#razorpay_payment_mode').val('live');
        } else {
            label.text('Test Mode').removeClass('text-success').addClass('text-primary');
            $('#razorpay_payment_mode').val('test');
        }
    });

    $('#settingsForm').on('submit', function (e) {
        e.preventDefault();

        var form = $(this);
        var originalMode = $('#razorpay_payment_mode').data('original');
        var currentMode = $('#razorpay_payment_mode').val();

        if (originalMode !== currentMode) {
            Swal.fire({
                title: 'Confirm Payment Mode Change',
                text: 'Are you sure you want to switch the payment gateway mode to ' + currentMode.toUpperCase() + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, change it!',
                cancelButtonText: 'Cancel',
                customClass: { confirmButton: 'btn btn-primary me-2', cancelButton: 'btn btn-label-danger' },
                buttonsStyling: false
            }).then(function (result) {
                if (result.isConfirmed) {
                    submitForm(form);
                } else {
                    // Revert UI toggle state
                    var prevChecked = originalMode === 'live';
                    $('#razorpay_payment_mode_switch').prop('checked', prevChecked).trigger('change');
                }
            });
        } else {
            submitForm(form);
        }
    });

    function submitForm(form) {
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').text('');
        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        $.ajax({
            url: '{{ route("admin.settings.update") }}',
            type: 'POST',
            data: form.serialize(),
            success: function (res) {
                if (res.status === 'success') {
                    toastr.success(res.message);
                    // Update original data attribute
                    var newMode = $('#razorpay_payment_mode').val();
                    $('#razorpay_payment_mode').data('original', newMode);
                }
                $('#submitBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Save Changes');
            },
            error: function (xhr) {
                $('#submitBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Save Changes');
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.message || {};
                    $.each(errors, function (field, messages) {
                        let input = form.find('[name="' + field + '"]');
                        if (input.length) {
                            input.addClass('is-invalid');
                            input.siblings('.invalid-feedback').text(messages[0]);
                        } else {
                            toastr.error(messages[0]);
                        }
                    });
                } else {
                    toastr.error('Something went wrong. Please try again.');
                }
            }
        });
    }
});
</script>
@endsection
