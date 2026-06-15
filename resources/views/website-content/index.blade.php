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

    // Initialize all 4 editors
    const termsEditor = new Quill('#terms-editor', {
        theme: 'snow',
        placeholder: 'Enter Terms & Conditions...',
        modules: { toolbar: quillToolbarOptions }
    });

    const deliveryEditor = new Quill('#delivery-editor', {
        theme: 'snow',
        placeholder: 'Enter Delivery & Returns details...',
        modules: { toolbar: quillToolbarOptions }
    });

    const privacyEditor = new Quill('#privacy-editor', {
        theme: 'snow',
        placeholder: 'Enter Privacy Policy...',
        modules: { toolbar: quillToolbarOptions }
    });

    const refundEditor = new Quill('#refund-editor', {
        theme: 'snow',
        placeholder: 'Enter Refund & Cancellation details...',
        modules: { toolbar: quillToolbarOptions }
    });

    // Helper to sync editor content to textarea
    function syncEditor(editor, textareaId) {
        const html = editor.root.innerHTML === '<p><br></p>' ? '' : editor.root.innerHTML;
        $('#' + textareaId).val(html);
    }

    const syncAll = function() {
        syncEditor(termsEditor, 'terms-textarea');
        syncEditor(deliveryEditor, 'delivery-textarea');
        syncEditor(privacyEditor, 'privacy-textarea');
        syncEditor(refundEditor, 'refund-textarea');
    };

    // Sync content on text-change for all editors
    termsEditor.on('text-change', () => syncEditor(termsEditor, 'terms-textarea'));
    deliveryEditor.on('text-change', () => syncEditor(deliveryEditor, 'delivery-textarea'));
    privacyEditor.on('text-change', () => syncEditor(privacyEditor, 'privacy-textarea'));
    refundEditor.on('text-change', () => syncEditor(refundEditor, 'refund-textarea'));

    // Initial sync
    syncAll();

    // ---- Submit ----
    $('#contentForm').on('submit', function (e) {
        e.preventDefault();

        // Extra check to ensure everything is synced
        syncAll();

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