@extends('layouts.app')

@section('title', 'Website Content')

@section('page-css')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/typography.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/katex.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/editor.css') }}" />
<style>
    .ql-editor {
        min-height: 400px;
        font-size: 15px;
        line-height: 1.6;
        padding: 1.25rem !important;
    }
    .ql-container.ql-snow {
        border-bottom-left-radius: 0.375rem;
        border-bottom-right-radius: 0.375rem;
        background-color: #fff;
    }
    .ql-toolbar.ql-snow {
        border-top-left-radius: 0.375rem;
        border-top-right-radius: 0.375rem;
        background-color: #f8f9fa;
    }
    html[class*="dark-style"] .ql-container.ql-snow {
        background-color: #2f3349;
        border-color: #434968;
    }
    html[class*="dark-style"] .ql-toolbar.ql-snow {
        background-color: #25293c;
        border-color: #434968;
    }
    html[class*="dark-style"] .ql-editor {
        color: #cfd3ec;
    }
    html[class*="dark-style"] .ql-snow .ql-stroke {
        stroke: #cfd3ec;
    }
    html[class*="dark-style"] .ql-snow .ql-fill {
        fill: #cfd3ec;
    }
    html[class*="dark-style"] .ql-snow .ql-picker {
        color: #cfd3ec;
    }
</style>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-semibold mb-0">Website Content</h4>
</div>

<form id="contentForm">
    @csrf
    <div class="row g-4">
        <!-- Terms & Conditions -->
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Terms & Conditions</h5></div>
                <div class="card-body">
                    <div id="terms-editor">{!! $termsConditions !!}</div>
                    <textarea name="terms_conditions" id="terms-textarea" class="d-none"></textarea>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
        </div>

        <!-- Delivery & Returns -->
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Delivery & Returns</h5></div>
                <div class="card-body">
                    <div id="delivery-editor">{!! $deliveryReturns !!}</div>
                    <textarea name="delivery_returns" id="delivery-textarea" class="d-none"></textarea>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
        </div>

        <!-- Privacy Policy -->
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Privacy Policy</h5></div>
                <div class="card-body">
                    <div id="privacy-editor">{!! $privacyPolicy !!}</div>
                    <textarea name="privacy_policy" id="privacy-textarea" class="d-none"></textarea>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
        </div>

        <!-- Refund & Cancellation -->
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Refund & Cancellation</h5></div>
                <div class="card-body">
                    <div id="refund-editor">{!! $refundCancellation !!}</div>
                    <textarea name="refund_cancellation" id="refund-textarea" class="d-none"></textarea>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
        </div>

        <!-- Razorpay Settings -->
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Razorpay Payment Gateway Settings</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold" for="razorpay_key_id">Razorpay Key ID</label>
                            <input type="text" name="razorpay_key_id" id="razorpay_key_id" class="form-control" value="{{ $razorpayKeyId }}" placeholder="Enter Razorpay Key ID" />
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold" for="razorpay_key_secret">Razorpay Key Secret</label>
                            <input type="password" name="razorpay_key_secret" id="razorpay_key_secret" class="form-control" value="{{ $razorpayKeySecret }}" placeholder="Enter Razorpay Key Secret" />
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="col-12">
            @can('edit website content')
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
<script src="{{ asset('assets/vendor/libs/quill/katex.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/quill/quill.js') }}"></script>
<script>
$(document).ready(function () {
    const quillToolbarOptions = [
        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'align': [] }],
        ['link', 'image'],
        ['clean']
    ];

    const termsEditor = new Quill('#terms-editor', {
        theme: 'snow',
        placeholder: 'Enter Terms & Conditions...',
        modules: { toolbar: quillToolbarOptions }
    });
    if ($('#terms-editor .ql-editor').length) {
        $('#terms-editor .ql-editor')[0].innerHTML = '{!! str_replace(["\n", "\r"], '', addslashes($termsConditions)) !!}';
    }

    const deliveryEditor = new Quill('#delivery-editor', {
        theme: 'snow',
        placeholder: 'Enter Delivery & Returns details...',
        modules: { toolbar: quillToolbarOptions }
    });
    if ($('#delivery-editor .ql-editor').length) {
        $('#delivery-editor .ql-editor')[0].innerHTML = '{!! str_replace(["\n", "\r"], '', addslashes($deliveryReturns)) !!}';
    }

    const privacyEditor = new Quill('#privacy-editor', {
        theme: 'snow',
        placeholder: 'Enter Privacy Policy...',
        modules: { toolbar: quillToolbarOptions }
    });
    if ($('#privacy-editor .ql-editor').length) {
        $('#privacy-editor .ql-editor')[0].innerHTML = '{!! str_replace(["\n", "\r"], '', addslashes($privacyPolicy)) !!}';
    }

    const refundEditor = new Quill('#refund-editor', {
        theme: 'snow',
        placeholder: 'Enter Refund & Cancellation details...',
        modules: { toolbar: quillToolbarOptions }
    });
    if ($('#refund-editor .ql-editor').length) {
        $('#refund-editor .ql-editor')[0].innerHTML = '{!! str_replace(["\n", "\r"], '', addslashes($refundCancellation)) !!}';
    }

    function syncEditor(editor, textareaId) {
        const html = editor.root.innerHTML === '<p><br></p>' ? '' : editor.root.innerHTML;
        $('#' + textareaId).val(html);
    }

    function getEditorContent(editor) {
        const html = editor.root.innerHTML;
        return html === '<p><br></p>' ? '' : html;
    }

    const syncAll = function() {
        syncEditor(termsEditor, 'terms-textarea');
        syncEditor(deliveryEditor, 'delivery-textarea');
        syncEditor(privacyEditor, 'privacy-textarea');
        syncEditor(refundEditor, 'refund-textarea');
    };

    termsEditor.on('text-change', () => syncEditor(termsEditor, 'terms-textarea'));
    deliveryEditor.on('text-change', () => syncEditor(deliveryEditor, 'delivery-textarea'));
    privacyEditor.on('text-change', () => syncEditor(privacyEditor, 'privacy-textarea'));
    refundEditor.on('text-change', () => syncEditor(refundEditor, 'refund-textarea'));

    syncAll();

    $('#terms-textarea').data('original', $('#terms-textarea').val());
    $('#delivery-textarea').data('original', $('#delivery-textarea').val());
    $('#privacy-textarea').data('original', $('#privacy-textarea').val());
    $('#refund-textarea').data('original', $('#refund-textarea').val());

    $('#contentForm').on('submit', function (e) {
        e.preventDefault();

        syncAll();

        var form = $(this);

        var emptyFields = [];
        var fieldLabels = {
            'terms-textarea': 'Terms & Conditions',
            'delivery-textarea': 'Delivery & Returns',
            'privacy-textarea': 'Privacy Policy',
            'refund-textarea': 'Refund & Cancellation'
        };

        $.each(fieldLabels, function(textareaId, label) {
            var textarea = $('#' + textareaId);
            var val = textarea.val().trim();
            var hasContent = val !== '' && val !== '<p><br></p>';
            var originalContent = textarea.data('original') || '';

            if (!hasContent && originalContent !== '') {
                emptyFields.push(label);
            }
        });

        if (emptyFields.length > 0) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Are you sure you want to clear the content for:\n' + emptyFields.join('\n'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, clear it!',
                cancelButtonText: 'Cancel',
                customClass: { confirmButton: 'btn btn-primary me-2', cancelButton: 'btn btn-label-danger' },
                buttonsStyling: false
            }).then(function (result) {
                if (result.isConfirmed) {
                    submitForm();
                }
            });
            return;
        }

        function submitForm() {
            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').text('');
            $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

            $.ajax({
                url: '{{ route("admin.website-content.update") }}',
                type: 'POST',
                data: form.serialize() + '&confirmed_clear=1',
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
        }

        submitForm();
    });
});
</script>
@endsection