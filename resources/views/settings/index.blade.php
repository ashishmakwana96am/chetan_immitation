@extends('layouts.app')

@section('title', 'System Settings')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="fw-semibold mb-0">System Settings</h4>
</div>

<form id="settingsForm">
    @csrf
    <div class="row g-4">

        <!-- Payment Methods -->
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Payment Methods</h5></div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Enable or disable the payment methods shown to customers at checkout. At least one method must remain enabled.</p>
                    <div id="payment-method-error" class="alert alert-danger d-none mb-3 py-2"></div>
                    <div class="d-flex flex-column gap-3">
                        <!-- Razorpay -->
                        <div class="d-flex align-items-center justify-content-between border rounded p-3">
                            <div>
                                <p class="fw-semibold mb-0">Online Payment (Razorpay)</p>
                                <p class="text-muted small mb-0">Cards, UPI, Netbanking via Razorpay</p>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input payment-method-toggle" type="checkbox"
                                    id="payment_method_razorpay_toggle"
                                    name="payment_method_razorpay"
                                    value="1"
                                    style="width:2.5rem; height:1.35rem; cursor:pointer;"
                                    {{ $paymentMethodRazorpay ? 'checked' : '' }} />
                                <label class="form-check-label ms-2" for="payment_method_razorpay_toggle" id="razorpay_method_label">
                                    {{ $paymentMethodRazorpay ? 'Enabled' : 'Disabled' }}
                                </label>
                            </div>
                        </div>
                        <!-- COD -->
                        <div class="d-flex align-items-center justify-content-between border rounded p-3">
                            <div>
                                <p class="fw-semibold mb-0">Cash on Delivery (COD)</p>
                                <p class="text-muted small mb-0">Customer pays with cash upon delivery</p>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input payment-method-toggle" type="checkbox"
                                    id="payment_method_cod_toggle"
                                    name="payment_method_cod"
                                    value="1"
                                    style="width:2.5rem; height:1.35rem; cursor:pointer;"
                                    {{ $paymentMethodCod ? 'checked' : '' }} />
                                <label class="form-check-label ms-2" for="payment_method_cod_toggle" id="cod_method_label">
                                    {{ $paymentMethodCod ? 'Enabled' : 'Disabled' }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Razorpay Settings — only visible when Razorpay is enabled -->
        <div class="col-12" id="razorpaySettingsCard" {{ $paymentMethodRazorpay ? '' : 'style=display:none' }}>
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
                    <div class="row g-3 razorpay-credentials-section" id="razorpayTestCredentials" {{ $razorpayPaymentMode === 'test' ? '' : 'style=display:none' }}>
                        <div class="col-12"><h6 class="mb-0 text-primary">Test Mode Credentials</h6></div>
                        <div class="col-md-6">
                            <label class="form-label" for="razorpay_test_key_id">Razorpay Test Key ID</label>
                            <input type="text" name="razorpay_test_key_id" id="razorpay_test_key_id" class="form-control" value="{{ $razorpayTestKeyId }}" placeholder="Enter Razorpay test key ID" />
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="razorpay_test_key_secret">Razorpay Test Key Secret</label>
                            <div class="input-group input-group-merge" id="test-secret-group">
                                <input type="password" name="razorpay_test_key_secret" id="razorpay_test_key_secret" class="form-control" value="{{ $razorpayTestKeySecret }}" placeholder="Enter Razorpay test key secret" />
                                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <!-- Live Credentials -->
                    <div class="row g-3 razorpay-credentials-section" id="razorpayLiveCredentials" {{ $razorpayPaymentMode === 'live' ? '' : 'style=display:none' }}>
                        <div class="col-12"><h6 class="mb-0 text-success">Live Mode Credentials</h6></div>
                        <div class="col-md-6">
                            <label class="form-label" for="razorpay_live_key_id">Razorpay Live Key ID</label>
                            <input type="text" name="razorpay_live_key_id" id="razorpay_live_key_id" class="form-control" value="{{ $razorpayLiveKeyId }}" placeholder="Enter Razorpay live key ID" />
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="razorpay_live_key_secret">Razorpay Live Key Secret</label>
                            <div class="input-group input-group-merge" id="live-secret-group">
                                <input type="password" name="razorpay_live_key_secret" id="razorpay_live_key_secret" class="form-control" value="{{ $razorpayLiveKeySecret }}" placeholder="Enter Razorpay live key secret" />
                                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                            </div>
                            <div class="invalid-feedback"></div>
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

                        <div class="col-12 mt-3">
                            <div class="d-flex align-items-center justify-content-between border rounded p-3">
                                <div>
                                    <p class="fw-semibold mb-0">Coming Soon Mode</p>
                                    <p class="text-muted small mb-0">When enabled, all website pages show a "Coming Soon" screen. Admin panel remains accessible.</p>
                                </div>
                                <div class="form-check form-switch mb-0 ms-3">
                                    <input type="hidden" name="coming_soon" value="0" />
                                    <input class="form-check-input" type="checkbox"
                                        id="coming_soon_toggle"
                                        name="coming_soon"
                                        value="1"
                                        style="width:2.5rem; height:1.35rem; cursor:pointer;"
                                        {{ $comingSoon ? 'checked' : '' }} />
                                    <label class="form-check-label ms-2" for="coming_soon_toggle" id="coming_soon_label">
                                        {{ $comingSoon ? 'Enabled' : 'Disabled' }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{--
        <!-- Invoice & Bill Prefixes Settings -->
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Invoice & Bill Number Prefixes</h5></div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Configure prefix codes for sales, orders, purchases, and transfers. The sequential number starting from 01 will be appended to these prefixes.</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label font-semibold" for="prefix_online_order">Online Order Prefix</label>
                            <input type="text" name="prefix_online_order" id="prefix_online_order" class="form-control" value="{{ $prefixOnlineOrder }}" placeholder="e.g. OR" />
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-semibold" for="prefix_offline_sale">Offline Sale Prefix (Non-GST)</label>
                            <input type="text" name="prefix_offline_sale" id="prefix_offline_sale" class="form-control" value="{{ $prefixOfflineSale }}" placeholder="e.g. SA" />
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-semibold" for="prefix_offline_sale_gst">Offline Sale Prefix (GST)</label>
                            <input type="text" name="prefix_offline_sale_gst" id="prefix_offline_sale_gst" class="form-control" value="{{ $prefixOfflineSaleGst }}" placeholder="e.g. GS" />
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-semibold" for="prefix_supplier_purchase">Supplier Purchase Prefix (Non-GST)</label>
                            <input type="text" name="prefix_supplier_purchase" id="prefix_supplier_purchase" class="form-control" value="{{ $prefixSupplierPurchase }}" placeholder="e.g. PS" />
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-semibold" for="prefix_supplier_purchase_gst">Supplier Purchase Prefix (GST)</label>
                            <input type="text" name="prefix_supplier_purchase_gst" id="prefix_supplier_purchase_gst" class="form-control" value="{{ $prefixSupplierPurchaseGst }}" placeholder="e.g. GP" />
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-semibold" for="prefix_stock_transfer">Purchase Bill (Transfer) Prefix</label>
                            <input type="text" name="prefix_stock_transfer" id="prefix_stock_transfer" class="form-control" value="{{ $prefixStockTransfer }}" placeholder="e.g. ST" />
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- GST & Tax Settings -->
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">GST & Tax Settings</h5></div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Configure default GST rate and business location state for tax calculations.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold" for="purchase_gst_rate">Purchase GST Rate (%)</label>
                            <input type="number" step="0.01" name="purchase_gst_rate" id="purchase_gst_rate" class="form-control" value="{{ $purchaseGstRate }}" placeholder="e.g. 3" />
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold" for="store_state">Store/Business State</label>
                            <input type="text" name="store_state" id="store_state" class="form-control" value="{{ $storeState }}" placeholder="e.g. Gujarat" />
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        --}}

        <!-- Backup -->
        @can('download backup')
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Database Backup & Google Drive Settings</h5></div>
                    <div class="card-body">
                        <!-- Google Drive Connection Status -->
                        <div class="d-flex align-items-center justify-content-between border rounded p-3 mb-3 flex-wrap gap-3">
                            <div>
                                <p class="fw-semibold mb-0">Google Drive Integration</p>
                                @if($googleDriveConnected)
                                    <p class="text-success small mb-0">
                                        <i class="ti ti-circle-check me-1"></i> Connected to: <strong>{{ $googleDriveEmail }}</strong>
                                    </p>
                                @else
                                    <p class="text-danger small mb-0">
                                        <i class="ti ti-circle-x me-1"></i> Disconnected
                                    </p>
                                @endif
                            </div>
                            <div>
                                @if($googleDriveConnected)
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="event.preventDefault(); document.getElementById('googleDriveDisconnectForm').submit();">
                                        <i class="ti ti-logout me-1"></i> Disconnect Drive
                                    </button>
                                @else
                                    <a href="{{ route('admin.settings.google-drive.connect') }}" class="btn btn-outline-success btn-sm">
                                        <i class="ti ti-brand-google me-1"></i> Connect Google Drive
                                    </a>
                                @endif
                            </div>
                        </div>

                        <!-- Backup Action -->
                        <div class="d-flex align-items-center justify-content-between border rounded p-3 flex-wrap gap-3">
                            <div>
                                <p class="fw-semibold mb-0">Generate Backup</p>
                                <p class="text-muted small mb-0">Exports the full database to a .sql file and uploads it directly to your connected Google Drive account.</p>
                            </div>
                            <button type="button" class="btn btn-primary" id="btnDownloadBackup">
                                <i class="ti ti-database-export me-1"></i> Generate Backup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        {{-- 
        <!-- Instagram Settings -->
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="ti ti-brand-instagram me-2 text-danger"></i>Instagram Integration & Home Feed</h5>
                    @if(!empty($instagramAccessToken))
                        <span class="badge bg-label-success"><i class="ti ti-circle-check me-1"></i>Connected via Token</span>
                    @else
                        <span class="badge bg-label-warning"><i class="ti ti-info-circle me-1"></i>Profile Link Mode</span>
                    @endif
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Configure your official Instagram account link and Graph API Access Token to automatically fetch and display live Instagram posts in the <strong>"Follow Our Jewellery Journey"</strong> section on the home page.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="instagram_username">Instagram Username / Handle</label>
                            <div class="input-group">
                                <span class="input-group-text">@</span>
                                <input type="text" name="instagram_username" id="instagram_username" class="form-control" value="{{ $instagramUsername }}" placeholder="chetan_imitation" />
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="instagram_profile_url">Instagram Profile Link (Follow Button)</label>
                            <input type="url" name="instagram_profile_url" id="instagram_profile_url" class="form-control" value="{{ $instagramProfileUrl }}" placeholder="https://www.instagram.com/chetan_imitation" />
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="instagram_access_token">Instagram User Access Token (Graph API)</label>
                            <div class="input-group input-group-merge" id="insta-token-group">
                                <input type="password" name="instagram_access_token" id="instagram_access_token" class="form-control" value="{{ $instagramAccessToken }}" placeholder="Enter Long-lived Instagram Graph API access token" />
                                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                            </div>
                            <div class="form-text">
                                <i class="ti ti-info-circle me-1"></i> Enter a long-lived Instagram Access Token to display top live feed images automatically. If left empty, showcase images linking to your profile will be displayed.
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        --}}

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
    function syncRazorpayCredentialsVisibility() {
        const mode = $('#razorpay_payment_mode').val();
        $('#razorpayTestCredentials').toggle(mode === 'test');
        $('#razorpayLiveCredentials').toggle(mode === 'live');
    }

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
        syncRazorpayCredentialsVisibility();
    });

    syncRazorpayCredentialsVisibility();

    $(document).on('click', '.toggle-secret-btn', function () {
        const targetId = $(this).data('target');
        const $input = $('#' + targetId);
        const $icon = $(this).find('i');
        const isPassword = $input.attr('type') === 'password';

        $input.attr('type', isPassword ? 'text' : 'password');
        $icon.toggleClass('ti-eye-off', !isPassword).toggleClass('ti-eye', isPassword);
    });    // Payment method toggle labels + show/hide Razorpay settings card
    $(document).on('change', '.payment-method-toggle', function () {
        const isChecked = this.checked;
        const labelId = this.id === 'payment_method_razorpay_toggle' ? 'razorpay_method_label' : 'cod_method_label';
        $('#' + labelId).text(isChecked ? 'Enabled' : 'Disabled');

        // Show/hide Razorpay gateway settings card based on Razorpay toggle
        if (this.id === 'payment_method_razorpay_toggle') {
            if (isChecked) {
                $('#razorpaySettingsCard').slideDown(200);
            } else {
                $('#razorpaySettingsCard').slideUp(200);
            }
        }
    });

    // Coming soon toggle label
    $('#coming_soon_toggle').on('change', function () {
        $('#coming_soon_label').text(this.checked ? 'Enabled' : 'Disabled');
    });

    // Generate Backup via AJAX (Google Drive Only)
    $('#btnDownloadBackup').on('click', function () {
        var btn = $(this);
        toastr.info('Generating backup and uploading to Google Drive, please wait...');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');

        $.ajax({
            url: '{{ route("admin.settings.backup.run") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (res) {
                btn.prop('disabled', false).html('<i class="ti ti-database-export me-1"></i> Generate Backup');
                if (res.status === 'success') {
                    toastr.success(res.message || 'Database backup successfully uploaded to Google Drive!');
                } else {
                    toastr.error(res.message || 'Backup failed.');
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false).html('<i class="ti ti-database-export me-1"></i> Generate Backup');
                let errorMsg = 'Something went wrong while generating backup.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);
            }
        });
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
                    $('#payment-method-error').addClass('d-none');
                    setTimeout(function () {
                        window.location.reload();
                    }, 600);
                }
                $('#submitBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Save Changes');
            },
            error: function (xhr) {
                $('#submitBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Save Changes');
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.message || {};
                    // Show payment method error prominently
                    if (errors.payment_method_cod || errors.payment_method_razorpay) {
                        const msg = (errors.payment_method_cod || errors.payment_method_razorpay)[0];
                        $('#payment-method-error').text(msg).removeClass('d-none');
                        $('html, body').animate({ scrollTop: $('#payment-method-error').offset().top - 100 }, 300);
                    } else {
                        $('#payment-method-error').addClass('d-none');
                    }
                    $.each(errors, function (field, messages) {
                        if (field === 'payment_method_cod' || field === 'payment_method_razorpay') return;
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

// Direct eye-toggle handlers — same pattern as admin login page
document.addEventListener('DOMContentLoaded', function () {
    function bindSecretToggle(groupId, inputId) {
        const group = document.getElementById(groupId);
        if (!group) return;
        const toggleBtn = group.querySelector('.input-group-text');
        if (!toggleBtn) return;
        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const input = document.getElementById(inputId);
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('ti-eye-off');
                icon.classList.add('ti-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('ti-eye');
                icon.classList.add('ti-eye-off');
            }
        });
    }

    bindSecretToggle('test-secret-group', 'razorpay_test_key_secret');
    bindSecretToggle('live-secret-group', 'razorpay_live_key_secret');
});
</script>

<form action="{{ route('admin.settings.google-drive.disconnect') }}" method="POST" id="googleDriveDisconnectForm" style="display: none;">
    @csrf
</form>
@endsection
