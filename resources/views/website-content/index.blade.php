@extends('layouts.app')

@section('title', 'Website Content')

@section('page-css')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/typography.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/katex.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/editor.css') }}" />
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-semibold mb-0">Website Content</h4>
</div>

<form id="contentForm">
    @csrf
    <div class="row g-4">

        <!-- Basic Info -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Basic Information</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $siteName }}" placeholder="Enter Name" />
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <hr class="my-3" />

                    <div class="mb-3">
                        <label class="form-label">Contact Emails</label>
                        <div class="email-tag-container" id="emailContainer">
                            <div class="d-flex flex-wrap gap-1 mb-2 email-badges">
                                @if(!empty($emails))
                                    @foreach($emails as $email)
                                    <span class="badge bg-label-info d-inline-flex align-items-center gap-1 py-1 px-2 email-badge">
                                        {{ $email }}
                                        <input type="hidden" name="emails[]" value="{{ $email }}" />
                                        <i class="ti ti-x" style="cursor:pointer;"></i>
                                    </span>
                                    @endforeach
                                @endif
                            </div>
                            <input type="email" class="form-control email-input" placeholder="Type email and press Enter to add" />
                        </div>
                        <div class="invalid-feedback d-block"></div>
                    </div>

                    <div>
                        <label class="form-label">Mobile Numbers</label>
                        <div class="mobile-tag-container">
                            <div class="d-flex flex-wrap gap-1 mb-2 mobile-badges">
                                @if(!empty($mobiles))
                                    @foreach($mobiles as $mobile)
                                    <span class="badge bg-label-info d-inline-flex align-items-center gap-1 py-1 px-2 mobile-badge">
                                        {{ substr($mobile, 0, 5) . ' ' . substr($mobile, 5) }}
                                        <input type="hidden" name="mobiles[]" value="{{ $mobile }}" />
                                        <i class="ti ti-x" style="cursor:pointer;"></i>
                                    </span>
                                    @endforeach
                                @endif
                            </div>
                            <input type="text" class="form-control mobile-input" placeholder="Type 10-digit mobile and press Enter" maxlength="10" />
                        </div>
                        <div class="invalid-feedback d-block"></div>
                    </div>
                </div>
            </div>

            <!-- Business Hours -->
            @php
                $dayLabels = ['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday', 'sunday' => 'Sunday'];
                $firstOpen = '';
                $firstClose = '';
                foreach ($days as $day) {
                    $h = $businessHours[$day] ?? [];
                    if (!isset($h['closed']) || !$h['closed']) {
                        $firstOpen = $h['open'] ?? '09:00';
                        $firstClose = $h['close'] ?? '18:00';
                        break;
                    }
                }
            @endphp
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Business Hours</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Select Open Days</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($days as $day)
                                @php $hours = $businessHours[$day] ?? []; @endphp
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input day-checkbox" name="business_hours[{{ $day }}][open_day]" value="1"
                                        id="day_{{ $day }}" {{ (!isset($hours['closed']) || !$hours['closed']) ? 'checked' : '' }} />
                                    <label class="form-check-label" for="day_{{ $day }}">{{ $dayLabels[$day] }}</label>
                                    <input type="hidden" name="business_hours[{{ $day }}][closed]" value="1" class="day-closed-val" />
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Open Time</label>
                            <input type="time" name="open_time" class="form-control" id="openTimeInput" value="{{ $firstOpen }}" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Close Time</label>
                            <input type="time" name="close_time" class="form-control" id="closeTimeInput" value="{{ $firstClose }}" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Locations -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Select Locations</h5></div>
                <div class="card-body">
                    @foreach($locationModels as $location)
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" name="locations[]" value="{{ $location->id }}" id="loc_{{ $location->id }}"
                            {{ in_array($location->id, $locations ?? []) ? 'checked' : '' }} />
                        <label class="form-check-label" for="loc_{{ $location->id }}">{{ $location->name }}</label>
                    </div>
                    @endforeach
                    <div class="invalid-feedback d-block"></div>
                </div>
            </div>
        </div>

        <!-- Text Editors (full width) -->
        <div class="col-12">
            <!-- Terms & Conditions -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Terms & Conditions</h5></div>
                <div class="card-body">
                    <div id="terms-editor">{!! $termsConditions ?? '' !!}</div>
                    <textarea name="terms_conditions" id="terms-textarea" class="d-none"></textarea>
                    <div class="invalid-feedback"></div>
                </div>
            </div>

            <!-- Delivery & Returns -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Delivery & Returns</h5></div>
                <div class="card-body">
                    <div id="delivery-editor">{!! $deliveryReturns ?? '' !!}</div>
                    <textarea name="delivery_returns" id="delivery-textarea" class="d-none"></textarea>
                    <div class="invalid-feedback"></div>
                </div>
            </div>

            <!-- Privacy Policy -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Privacy Policy</h5></div>
                <div class="card-body">
                    <div id="privacy-editor">{!! $privacyPolicy ?? '' !!}</div>
                    <textarea name="privacy_policy" id="privacy-textarea" class="d-none"></textarea>
                    <div class="invalid-feedback"></div>
                </div>
            </div>

            <!-- Refund & Cancellation Policy -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Refund & Cancellation Policy</h5></div>
                <div class="card-body">
                    <div id="refund-editor">{!! $refundCancellation ?? '' !!}</div>
                    <textarea name="refund_cancellation" id="refund-textarea" class="d-none"></textarea>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="col-12">
            @can('edit website content')
            <div class="d-flex gap-2">
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
<script src="{{ asset('assets/vendor/libs/quill/katex.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/quill/quill.js') }}"></script>
<script>
$(document).ready(function () {

    // ---- Email tag input ----
    const emailInput = $('.email-input');
    const emailBadges = $('.email-badges');

    emailInput.on('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            const val = $(this).val().trim();
            if (!val) return;

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(val)) {
                toastr.error('Please enter a valid email address.');
                return;
            }

            if (emailBadges.find('input[value="' + val.replace(/"/g, '&quot;') + '"]').length) {
                toastr.warning('Email already added.');
                $(this).val('');
                return;
            }

            const badge = $(
                '<span class="badge bg-label-info d-inline-flex align-items-center gap-1 py-1 px-2 email-badge">' +
                    val +
                    '<input type="hidden" name="emails[]" value="' + val.replace(/"/g, '&quot;') + '" />' +
                    '<i class="ti ti-x" style="cursor:pointer;"></i>' +
                '</span>'
            );
            emailBadges.append(badge);
            $(this).val('');
        }
    });

    $(document).on('click', '.email-badge .ti-x', function () {
        $(this).closest('.email-badge').remove();
    });

    // ---- Mobile tag input ----
    const mobileInput = $('.mobile-input');
    const mobileBadges = $('.mobile-badges');

    mobileInput.on('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            let val = $(this).val().replace(/\s/g, '');
            if (!val) return;

            const mobileRegex = /^\d{10}$/;
            if (!mobileRegex.test(val)) {
                toastr.error('Please enter a valid 10-digit mobile number.');
                return;
            }

            if (mobileBadges.find('input[value="' + val + '"]').length) {
                toastr.warning('Mobile number already added.');
                $(this).val('');
                return;
            }

            const formatted = val.substring(0, 5) + ' ' + val.substring(5);
            const badge = $(
                '<span class="badge bg-label-info d-inline-flex align-items-center gap-1 py-1 px-2 mobile-badge">' +
                    formatted +
                    '<input type="hidden" name="mobiles[]" value="' + val + '" />' +
                    '<i class="ti ti-x" style="cursor:pointer;"></i>' +
                '</span>'
            );
            mobileBadges.append(badge);
            $(this).val('');
        }
    });

    $(document).on('click', '.mobile-badge .ti-x', function () {
        $(this).closest('.mobile-badge').remove();
    });

    // ---- Quill Editors ----
    function createEditor(containerId, textareaId) {
        const editor = new Quill('#' + containerId, {
            theme: 'snow',
            placeholder: 'Enter content here...'
        });
        editor.on('text-change', function () {
            const html = editor.root.innerHTML === '<p><br></p>' ? '' : editor.root.innerHTML;
            $('#' + textareaId).val(html);
        });
        return editor;
    }

    createEditor('terms-editor', 'terms-textarea');
    createEditor('delivery-editor', 'delivery-textarea');
    createEditor('privacy-editor', 'privacy-textarea');
    createEditor('refund-editor', 'refund-textarea');

    // ---- Submit ----
    $('#contentForm').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').text('');
        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        $.ajax({
            url: '{{ route("admin.website-content.update") }}',
            type: 'POST',
            data: form.serialize(),
            success: function (res) {
                if (res.status === 'success') {
                    toastr.success(res.message);
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
    });

});
</script>
@endsection